<?php

// --- Database Connection and Initial Setup (Same as before) ---
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'ccs_database';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start(); // Start the session if not already started
$loggedInUserId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null; // Assuming user ID is stored in session

$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = ""; // Store output messages

// --- Function to log user actions using prepared statements ---
function logUserAction($conn, $loggedInUserId, $actionType = null) {
    if ($loggedInUserId === null) return; // Don't log if no user is logged in
    date_default_timezone_set("Asia/Manila"); // Set timezone
    $datetime = date("Y-m-d H:i:s");

    $logStmt = $conn->prepare("INSERT INTO user_logs (user, action, date) VALUES (?, ?, ?)");
    if ($logStmt) {
        $logStmt->bind_param("iss", $loggedInUserId, $actionType, $datetime);
        $logStmt->execute();
        $logStmt->close();
    } else {
        // Consider logging this error for debugging purposes
        error_log("Error preparing log statement: " . $conn->error);
    }
}

// --- Action Handling (Includes new 'unarchive_all' case) ---
switch ($action) {

    case 'backup':
        $backupResult = backupDatabase($conn, $database);
        $message = $backupResult['message'];
        date_default_timezone_set("Asia/Manila"); // Set timezone
    $datetime = date("Y-m-d H:i:s");
        if ($backupResult['success']) {
            logUserAction($conn, $loggedInUserId, 'Backup Database', $datetime);
        }
        break;
    case 'restore':
        $restoreResult = restoreDatabase($conn, isset($_GET['file']) ? $_GET['file'] : '');
        $message = $restoreResult['message'];
        date_default_timezone_set("Asia/Manila"); // Set timezone
    $datetime = date("Y-m-d H:i:s");
        if ($restoreResult['success']) {
            logUserAction($conn, $loggedInUserId, 'Restore Database', $datetime);
        }
        break;
    case 'archive_all':
        $archiveResult = archiveAllData($conn, $database);
        $message = $archiveResult['message'];
        date_default_timezone_set("Asia/Manila"); // Set timezone
    $datetime = date("Y-m-d H:i:s");
        if ($archiveResult['success']) {
            logUserAction($conn, $loggedInUserId, 'Archive All Data', $datetime);
        }
        break;
    case 'unarchive_all': // Keep the new action
        $unarchiveResult = unarchiveAllData($conn, $database);
        $message = $unarchiveResult['message'];
        date_default_timezone_set("Asia/Manila"); // Set timezone
    $datetime = date("Y-m-d H:i:s");
        if ($unarchiveResult['success']) {
            logUserAction($conn, $loggedInUserId, 'Unarchive All Data', $datetime);
        }
        break;
    case 'auto_backup':
        $message = checkAutoBackup();
        break;
}

// --- Functions (backupDatabase, restoreDatabase, archiveAllData, unarchiveAllData, checkAutoBackup, cleanOldBackups - Modified to return success status) ---

function backupDatabase($conn, $database) {
    $backupDir = 'backups/';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0777, true); // Consider more restrictive permissions than 0777
    }

    $backupFile = $backupDir . 'CCS_DATABASE_' . date('Y-m-d_H-i-s') . '.sql';
    $tables = [];
    $excludedTable = 'users';
    $query = $conn->query("SHOW TABLES");
    if (!$query) {
        return ['success' => false, 'message' => "❌ Error fetching tables: " . $conn->error];
    }
    while ($row = $query->fetch_array()) {
        if ($row[0] !== $excludedTable) {
            $tables[] = $row[0];
        }
    }

    $sqlScript = "-- SQL Backup - Generated on " . date('Y-m-d H:i:s') . "\n";
    $sqlScript .= "SET FOREIGN_KEY_CHECKS=0;\n\n"; // Disable FK checks at the beginning

    foreach ($tables as $table) {
        $result = $conn->query("SELECT * FROM `$table`");
        if (!$result) {
            $conn->query("SET FOREIGN_KEY_CHECKS=1;"); // Re-enable FK checks on error
            return ['success' => false, 'message' => "❌ Error selecting from table `$table`: " . $conn->error];
        }
        $numColumns = $result->field_count;

        $createTableResult = $conn->query("SHOW CREATE TABLE `$table`");
        if (!$createTableResult) {
            $conn->query("SET FOREIGN_KEY_CHECKS=1;"); // Re-enable FK checks on error
            return ['success' => false, 'message' => "❌ Error fetching CREATE TABLE statement for `$table`: " . $conn->error];
        }
        $row2 = $createTableResult->fetch_row(); // Use fetch_row for numeric index
        $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
        $sqlScript .= $row2[1] . ";\n\n"; // Use index 1 for the CREATE TABLE statement

        while ($row = $result->fetch_assoc()) {
            $sqlScript .= "INSERT INTO `$table` VALUES(";
            $values = array_map(function ($value) use ($conn) {
                // Handle null values explicitly and escape strings
                if ($value === null) {
                    return "NULL";
                } else {
                    // Use real_escape_string for security
                    return "'" . $conn->real_escape_string($value) . "'";
                }
            }, array_values($row));
            $sqlScript .= implode(",", $values) . ");\n";
        }
        $sqlScript .= "\n";
        $result->free(); // Free result set memory
    }

    $sqlScript .= "\nSET FOREIGN_KEY_CHECKS=1;\n"; // Re-enable FK checks at the end

    if (file_put_contents($backupFile, $sqlScript) === false) {
        return ['success' => false, 'message' => "❌ Error writing backup file to disk."];
    }

    cleanOldBackups($backupDir);
    file_put_contents('last_backup.txt', time());
    return ['success' => true, 'message' => "✅ Database backup completed! File: $backupFile", 'file' => $backupFile];
}

function restoreDatabase($conn, $file) {
    $backupDir = 'backups/';
    $filePath = $backupDir . basename($file); // Use basename to prevent directory traversal

    if (empty($file) || !file_exists($filePath)) {
        return ['success' => false, 'message' => "❌ Error: Backup file '$file' not found!", 'file' => $file];
    }

    $sql = file_get_contents($filePath);

    if ($sql === false) {
        return ['success' => false, 'message' => "❌ Error: Could not read backup file.", 'file' => $file];
    }

    // Temporarily disable foreign key checks
    if (!$conn->query("SET FOREIGN_KEY_CHECKS=0;")) {
        return ['success' => false, 'message' => "❌ Error disabling foreign key checks: " . $conn->error, 'file' => $file];
    }

    // Execute the SQL script using multi_query for efficiency and safety
    if ($conn->multi_query($sql)) {
        do {
            // Store result to flush buffer
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    }

    $restoreError = $conn->error; // Check for errors after multi_query

    // Always re-enable foreign key checks
    if (!$conn->query("SET FOREIGN_KEY_CHECKS=1;")) {
        // If re-enabling fails, report both errors if applicable
        if($restoreError) $restoreError .= "<br>";
        $restoreError .= "❌ Error re-enabling foreign key checks: " . $conn->error;
    }

    if ($restoreError) {
        return ['success' => false, 'message' => "❌ Database restore failed: <br>" . $restoreError, 'file' => $file];
    } else {
        return ['success' => true, 'message' => "✅ Database restored successfully from $file!", 'file' => $file];
    }
}

function archiveAllData($conn, $database) {
    $excludedTable = 'users';
    $query = $conn->query("SHOW TABLES");
    if (!$query) {
        return ['success' => false, 'message' => "❌ Error fetching tables: " . $conn->error];
    }
    $archivedTables = [];
    $warnings = [];

    while ($row = $query->fetch_array()) {
        $tableName = $row[0];
        if ($tableName !== $excludedTable) {
            // Check if the table has an 'archive' column
            $checkColumn = $conn->query("SHOW COLUMNS FROM `$tableName` LIKE 'archive'");
            if (!$checkColumn) {
                // Log this error but maybe continue? Or return error.
                return ['success' => false, 'message' => "❌ Error checking columns for table `$tableName`: " . $conn->error];
            }

            if ($checkColumn->num_rows > 0) {
                $updateSql = "UPDATE `$tableName` SET `archive` = 1";
                if ($conn->query($updateSql)) {
                    $archivedTables[] = $tableName;
                } else {
                    // Return immediately on failure for a specific table
                    return ['success' => false, 'message' => "❌ Error archiving table `$tableName`: " . $conn->error];
                }
            } else {
                // Collect warnings for tables without the column
                $warnings[] = "Table `$tableName` does not have an 'archive' column.";
            }
            $checkColumn->free(); // Free result set memory
        }
    }
    $query->free(); // Free result set memory

    $message = "";
    $success = false;
    if (!empty($archivedTables)) {
        $message .= "✅ Data in tables set to archived (archive = 1): " . implode(", ", $archivedTables);
        $success = true;
    } else {
        $message .= "ℹ️ No tables were modified for archiving.";
    }

    if (!empty($warnings)) {
        $message .= "<br>⚠️ Warnings: <br>" . implode("<br>", $warnings);
    }

    return ['success' => $success, 'message' => $message];
}

function unarchiveAllData($conn, $database) {
    $excludedTable = 'users';
    $query = $conn->query("SHOW TABLES");
    if (!$query) {
        return ['success' => false, 'message' => "❌ Error fetching tables: " . $conn->error];
    }
    $unarchivedTables = [];
    $warnings = [];

    while ($row = $query->fetch_array()) {
        $tableName = $row[0];
        if ($tableName !== $excludedTable) {
            // Check if the table has an 'archive' column
            $checkColumn = $conn->query("SHOW COLUMNS FROM `$tableName` LIKE 'archive'");
            if (!$checkColumn) {
                return ['success' => false, 'message' => "❌ Error checking columns for table `$tableName`: " . $conn->error];
            }

            if ($checkColumn->num_rows > 0) {
                // --- The only difference from archiveAllData is setting archive = 0 ---
                $updateSql = "UPDATE `$tableName` SET `archive` = 0";
                if ($conn->query($updateSql)) {
                    $unarchivedTables[] = $tableName;
                } else {
                    return ['success' => false, 'message' => "❌ Error unarchiving table `$tableName`: " . $conn->error];
                }
            } else {
                $warnings[] = "Table `$tableName` does not have an 'archive' column.";
            }
            $checkColumn->free();
        }
    }
    $query->free();

    $message = "";
    $success = false;
    if (!empty($unarchivedTables)) {
        $message .= "✅ Data in tables set to unarchived (archive = 0): " . implode(", ", $unarchivedTables);
        $success = true;
    } else {
        $message .= "ℹ️ No tables were modified for unarchiving.";
    }

    if (!empty($warnings)) {
        $message .= "<br>⚠️ Warnings: <br>" . implode("<br>", $warnings);
    }

    return ['success' => $success, 'message' => $message];
}

function checkAutoBackup() {
    // Backup every 15 days
    // Note: The mechanism to *trigger* the automatic backup based on this check is not implemented here.
    // This function only *reports* if the backup is due based on the 'last_backup.txt' file.
    // To automate backups, you would typically use a cron job or a scheduled task on the server
    // to call a script that performs the backup logic if the check indicates it's due.
    $backupInterval = 15 * 24 * 60 * 60; // 15 days in seconds
    $lastBackupTime = file_exists('last_backup.txt') ? (int)file_get_contents('last_backup.txt') : 0;
    $currentTime = time();

    if ($lastBackupTime === 0) {
        return "ℹ️ Automatic backup has not run yet. It's scheduled to run if older than 15 days.";
    }

    $nextBackupTimestamp = $lastBackupTime + $backupInterval;
    $nextBackupDate = date('Y-m-d H:i:s', $nextBackupTimestamp);

    if ($currentTime >= $nextBackupTimestamp) {
        return "⚠️ Automatic backup is due (last backup: " . date('Y-m-d H:i:s', $lastBackupTime) . "). This check indicates it's due now or was due on: " . $nextBackupDate;
        // To actually trigger an automatic backup here, you would add the backupDatabase() call.
        // For example:
        // global $conn, $database; // Need to bring these into scope
        // $autoBackupResult = backupDatabase($conn, $database);
        // return "⚠️ Automatic backup was due and attempted. Result: " . $autoBackupResult['message'];
    } else {
        return "ℹ️ Last automatic backup: " . date('Y-m-d H:i:s', $lastBackupTime) . ". Next automatic backup is due after: " . $nextBackupDate;
    }
}

function cleanOldBackups($backupDir) {
    $files = glob($backupDir . 'CCS_DATABASE_*.sql');
    if ($files === false) return; // Error reading directory

    // Keep the latest 24 backups
    $maxBackups = 24;
    if (count($files) > $maxBackups) {
        // Sort files by modification time, oldest first
        usort($files, function ($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        // Delete the oldest files until only maxBackups remain
        $filesToDelete = count($files) - $maxBackups;
        for ($i = 0; $i < $filesToDelete; $i++) {
            if (file_exists($files[$i])) {
                unlink($files[$i]);
            }
        }
    }
}


// --- Manual Restore via Upload (Modified to log action) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
    if ($_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['sql_file']['tmp_name'];
        $fileName = basename($_FILES['sql_file']['name']); // Sanitize filename
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension !== 'sql') {
            $message = "❌ Error: Only .sql files are allowed.";
        } else {
            $sql = file_get_contents($fileTmpPath);
            if ($sql === false) {
                $message = "❌ Error: Could not read uploaded SQL file.";
            } else {
                // Use the restore function logic for consistency
                if (!$conn->query("SET FOREIGN_KEY_CHECKS=0;")) {
                    $message = "❌ Error disabling foreign key checks: " . $conn->error;
                } else {
                    if ($conn->multi_query($sql)) {
                        do {
                            if ($result = $conn->store_result()) { $result->free(); }
                        } while ($conn->more_results() && $conn->next_result());
                    }
                    $restoreError = $conn->error;

                    if (!$conn->query("SET FOREIGN_KEY_CHECKS=1;")) {
                        if($restoreError) $restoreError .= "<br>";
                        $restoreError .= "❌ Error re-enabling foreign key checks: " . $conn->error;
                    }

                    if ($restoreError) {
                        $message = "❌ Manual restore failed: <br>" . $restoreError;
                    } else {
                        $message = "✅ Data restored successfully from uploaded file '$fileName'!";
                        logUserAction($conn, $loggedInUserId, 'restore_database', 'Uploaded: ' . $fileName);
                    }
                }
            }
        }
    } else {
        // Handle upload errors more specifically if needed
        $message = "❌ Error uploading file. Code: " . $_FILES['sql_file']['error'];
    }
}


// --- Close connection at the very end ---
// Note: The connection is closed here. If you need to perform other database
// operations *after* including this file or calling functions within it,
// you would need to either keep the connection open longer or re-establish it.
$conn->close();
?>

<script>
    // confirmAction JavaScript function (Same as previous correct version, includes 'unarchive_all')
    function confirmAction(action, file = '') {
        let message = '';
        let proceed = false; // Flag to check if confirmation is needed

        switch (action) {
            case 'backup':
                message = "Are you sure you want to BACKUP the database?";
                proceed = true;
                break;
            case 'restore':
                message = "⚠️ WARNING: Restoring will OVERWRITE current data from file '" + file + "'! This cannot be undone. Proceed?";
                proceed = true;
                break;
            case 'archive_all':
                message = "⚠️ WARNING: This will set 'archive' = 1 for ALL data in relevant tables (excluding 'users'). Continue?";
                proceed = true;
                break;
            case 'unarchive_all': // Kept the new case
                message = "Are you sure you want to set 'archive' = 0 for ALL data in relevant tables (excluding 'users')?";
                proceed = true;
                break;
            case 'auto_backup':
                // No confirmation needed, just navigate
                window.location.href = `?action=${action}`;
                return; // Exit function early
            default:
                console.error("Unknown action:", action);
                return; // Exit if action is unknown
        }

        if (proceed && confirm(message)) {
            // Construct the URL and navigate
            let url = `?action=${action}`;
            if (file) {
                // Ensure the filename is properly encoded for the URL
                url += '&file=' + encodeURIComponent(file);
            }
             // Preserve the current page number if restoring from a paged list
            const urlParams = new URLSearchParams(window.location.search);
            const currentPage = urlParams.get('page');
            if (currentPage) {
                 url += '&page=' + encodeURIComponent(currentPage);
            }

            window.location.href = url;
        }
    }

    // --- Optional: Add confirmation for manual upload ---
    // Note: This targets the form submission directly.
    function confirmUpload(form) {
        if (confirm("⚠️ WARNING: Uploading and restoring this SQL file will OVERWRITE current data! This cannot be undone. Proceed?")) {
            return true; // Allow form submission
        } else {
            // Optional: Reset the file input if cancelled
            form.reset();
            return false; // Prevent form submission
        }
    }
</script>

<!DOCTYPE html>
<html>
<head>
    <title>Database Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #7F1416; /* Original background */
            margin-top: 20px;
            margin-bottom: 20px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            /*height: 100vh;*/ /* Commented out as in original */
        }

        /* Using original container class name */
        .addemp-container {
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            padding: 25px;
            width: 100%;
        }

        .addemp-container h2{
            text-align: center;
            margin-bottom: 20px;
        }

        .addemp-container h3{
            text-align: center;
            margin-bottom: 20px;
        }

        .addemp-container h4{ /* Kept this even if not used by current elements */
            text-align: center;
            margin-bottom: 20px;
        }

        .addemp-container img { /* Kept this even if not used by current elements */
            align: center;
            width: 50%; /* Adjust the desired width */
            height: auto; /* Maintain aspect ratio */
        }

        .addemp-container select { /* Kept this even if not used by current elements */
            width: 100%;
            padding: 7.5px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .addemp-container hr {
            border: none;
            height: 1px;
            background-color: #ddd;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        /* Original input styles */
        .addemp-container input[type="text"],
        .addemp-container input[type="date"],
        .addemp-container input[type="file"] {
            width: 100%;
            padding: 7.5px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .addemp-container label {
            font-size: 13px;
            font-weight: bold;
            /* display: block; - Add this if labels should be on their own line */
            /* margin-bottom: 5px; - Add spacing below label if needed */
        }

        /* Original general button style */
        .addemp-container .button {
            display: inline-flex; /* Changed from inline-block for flex alignment */
            justify-content: center;
            text-align: center;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #4CAF50; /* Default Green */
            color: #fff;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
            margin: 5px 5px; /* Original margin */
            /* width: 33%; */ /* Removed fixed width, let flexbox handle it */
            flex-grow: 1; /* Allow buttons to share space */
            min-width: 120px; /* Prevent buttons from becoming too small */
            font-size: 14px; /* Adjusted font size slightly */
            cursor: pointer; /* Ensure cursor changes */
        }

        .addemp-container .button:hover {
            background-color: #3d8b40;
        }

        .buttons-container {
            width: 100%;
            display: flex;
            flex-direction: row;
            flex-wrap: wrap; /* Allow wrapping */
            gap: 10px; /* Use gap for spacing */
            justify-content: center; /* Center buttons if they wrap */
            margin-bottom: 15px; /* Add some space below the button group */
        }

        /* Original style for restore links (button2 class) */
        /* Note: We are using <a> tag now, not button */
        .addemp-container .button2 {
            /* This class is no longer used for restore, but kept for reference */
            display: block; /* Original was block */
            text-align: center;
            margin-bottom: 10px;
            padding: 10px; /* Original padding */
            background-color: #4CAF50; /* Original color */
            color: #fff;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .addemp-container .button2:hover {
            background-color: #3d8b40; /* Original hover */
        }

        /* Original style for submit button */
        .addemp-container input[type="submit"] {
            background-color: #4CAF50;
            font-size: 16px;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px; /* Adjusted margin */
            width: auto; /* Let button size naturally */
            /* transform: translateX(+50%); */ /* Removed transform */
            display: block; /* Make it block level */
            margin-left: auto; /* Center using margin */
            margin-right: auto; /* Center using margin */
            transition: background-color 0.3s ease;
        }

        .addemp-container input[type="submit"]:hover {
            background-color: #3d8b40;
        }

        /* Original Back to Dashboard style */
        .addemp-container .backtodash {
            background-color: #ff851b;
            /* width:95%; */ /* Removed fixed width */
            display: block; /* Make it block to take width */
            width: calc(100% - 10px); /* Adjust width considering margin */
            margin-left: 5px;
            margin-right: 5px;
            margin-top: 15px; /* Add space above */
        }

        .addemp-container .backtodash:hover {
            background-color: #d47716;
        }

        /* Original Message style */
        .message {
            padding: 10px;
            margin: 15px 0; /* Adjusted margin */
            border-radius: 4px;
            font-weight: bold;
            text-align: center;
            /* Default background/color if no specific type */
            background-color: #f0f0f0;
            color: #333;
            border: 1px solid #ccc;
        }
        /* Original specific message types (used if needed) */
        .message.success {
            background-color: #dff0d8; /* Lighter green */
            color: #3c763d;
            border-color: #d6e9c6;
        }
        .message.error {
            background-color: #f2dede; /* Lighter red */
            color: #a94442;
            border-color: #ebccd1;
        }
        /* Add warning/info if desired, matching original pattern */
        .message.warning {
            background-color: #fcf8e3; /* Lighter yellow */
            color: #8a6d3b;
            border-color: #faebcc;
        }
        .message.info {
            background-color: #d9edf7; /* Lighter blue */
            color: #31708f;
            border-color: #bce8f1;
        }


        /* Original Table Styles */
        table {
            width: 100%;
            border-collapse: collapse; /* Add collapse */
            margin-bottom: 15px; /* Add margin below table */
        }

        td, th { /* Apply padding and border to cells */
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left; /* Default alignment */
        }

        th { /* Header specific style */
            background-color: #f2f2f2; /* Light grey header */
        }

        .tablelabel {
            /* text-align: center; */ /* Original - removed for left align */
        }

        .tablebutton {
            text-align: center; /* Center align button/link cell */
        }

        /* Original styles for specific buttons (if IDs were used before) */
        #truncate {
            background-color: #FF4136;
        }

        #truncate:hover {
            background-color: #C12C24;
        }

        #archive_all {
            background-color: #F0AD4E; /* Yellow/Orange color for archive */
        }

        #archive_all:hover {
            background-color: #E08E0B;
        }

        /* --- NEW Style for Unarchive Button --- */
        /* Making it consistent with archive button style */
        #unarchive_all {
            background-color: #5bc0de; /* Light blue / Info color */
        }
        #unarchive_all:hover {
            background-color: #31b0d5; /* Darker info blue */
        }

        /* Style for restore link in table */
        .restore-link {
            display: inline-block; /* Allow padding */
            padding: 5px 10px;
            background-color: #d9534f; /* Red */
            color: white;
            text-decoration: none;
            border-radius: 3px;
            font-size: 13px;
            transition: background-color 0.2s ease;
        }
        .restore-link:hover {
            background-color: #c9302c; /* Darker Red */
            text-decoration: none;
            color: white;
        }

        /* --- Pagination Styles (Replicated from Logs Page) --- */
        .pagination {
            display: flex; /* Keep flexbox for centering */
            justify-content: center; /* Center the pagination */
            padding: 10px 0; /* Add padding */
            list-style: none; /* Remove default list bullets */
            margin-top: 10px; /* Match margin from logs page */
        }

        .pagination li {
            margin: 0 5px; /* Match margin from logs page */
        }

        .pagination li a,
        .pagination li span {
            display: block; /* Make clickable area larger */
            padding: 5px 8px; /* Match padding from logs page */
            border: 1px solid #ccc; /* Match border from logs page */
            /* margin-right: 5px; -- Moved to li margin */
            text-decoration: none;
            color: #333;
            border-radius: 4px; /* Match border radius from logs page */
            transition: background-color 0.3s ease;
        }

        .pagination li a:hover {
            background-color: #ddd; /* Match hover background */
        }

        .pagination li.active span {
            background-color: #007bff; /* Match active background color */
            color: white; /* Match active text color */
            border-color: #007bff; /* Match active border color */
            cursor: default;
        }

         /* Style for disabled links/spans (kept similar to previous version, slightly adjusted) */
        .pagination li.disabled a,
        .pagination li.disabled span {
            color: #ccc;
            pointer-events: none;
            cursor: default;
            background-color: #f9f9f9;
            border-color: #ccc; /* Keep border for disabled */
        }


    </style>
</head>
<body>
<div class="addemp-container">
<h4>CCS-DOCS</h4>
    <h2>Database Management</h2>

    <?php if (!empty($message)):
        // --- Simplified Message Class Logic (matching original CSS classes) ---
        $message_class = ''; // Default, no extra class
        if (strpos($message, '✅') === 0) $message_class = 'success';
        elseif (strpos($message, '❌') === 0) $message_class = 'error';
        elseif (strpos($message, '⚠️') === 0) $message_class = 'warning';
        elseif (strpos($message, 'ℹ️') === 0) $message_class = 'info';
    ?>
        <div class="message <?php echo $message_class; ?>"><?php echo nl2br(htmlspecialchars($message)); ?></div>
    <?php endif; ?>

    <div class="buttons-container">
        <button class="button" onclick="confirmAction('backup')">Backup Database</button>
        <button class="button" onclick="confirmAction('auto_backup')">Check Auto Backup</button>
        <button id="archive_all" class="button" onclick="confirmAction('archive_all')">Archive All Data</button>
        <button id="unarchive_all" class="button" onclick="confirmAction('unarchive_all')">Unarchive All Data</button>
    </div>

    <hr> <h3>Available Backups</h3>
    <table>
        <thead>
            <tr>
                <th>Filename</th>
                <th class="tablebutton">Actions</th> </tr>
        </thead>
        <tbody>
        <?php
        $backupDir = 'backups/';
        $files = []; // Initialize files array

        if (is_dir($backupDir)) {
            $allFiles = glob($backupDir . 'CCS_DATABASE_*.sql');
            if ($allFiles !== false) {
                // Sort newest first
                usort($allFiles, function ($a, $b) { return filemtime($b) - filemtime($a); });
                $files = $allFiles; // Assign sorted files
            } else {
                 echo "<tr><td colspan='2' style='text-align:center; color: red;'>Error reading backup directory.</td></tr>";
            }
        } else {
            echo "<tr><td colspan='2' style='text-align:center;'>Backup directory '$backupDir' does not exist.</td></tr>";
        }

        // --- Pagination Logic ---
        $itemsPerPage = 5;
        $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Ensure page is at least 1
        $totalFiles = count($files);
        $totalPages = ceil($totalFiles / $itemsPerPage);
        $offset = ($currentPage - 1) * $itemsPerPage;

        // Get files for the current page
        $pagedFiles = array_slice($files, $offset, $itemsPerPage);

        if (empty($pagedFiles)) {
             if (!empty($files)) {
                 // If files exist but the current page has none, it means the page number is too high
                 echo "<tr><td colspan='2' style='text-align:center;'>No backups found on this page. <a href='?page=1'>Go to first page</a></td></tr>";
             } elseif (!is_dir($backupDir) || $allFiles === false || empty($allFiles)) {
                 // Message already displayed if directory error or no files found at all
             } else {
                 echo "<tr><td colspan='2' style='text-align:center;'>No backup files found.</td></tr>";
             }
        } else {
            foreach ($pagedFiles as $file) {
                $fileName = basename($file);
                $fileModTime = date('Y-m-d H:i:s', filemtime($file));
                // Pass the current page number in the restore link to return to the same page after restoring
                echo "<tr>
                                    <td class='tablelabel'> <a href='$file' download>$fileName</a>
                                        <br><small><em>(Created: $fileModTime)</em></small>
                                    </td>
                                    <td class='tablebutton'> <a class='restore-link' href='#' onclick=\"confirmAction('restore', '$fileName'); return false;\">Restore</a>
                                    </td>
                                </tr>";
            }
        }
        ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): // Display pagination only if there's more than one page ?>
        <ul class="pagination">
            <li class="<?php if($currentPage <= 1){ echo 'disabled'; } ?>">
                <a href="<?php if($currentPage > 1){ echo "?page=".($currentPage - 1); } else { echo '#'; } ?>">Previous</a>
            </li>
            <?php
            // Display page numbers (simple: all pages)
            for ($i = 1; $i <= $totalPages; $i++):
            ?>
                <li class="<?php if($i == $currentPage){ echo 'active'; } ?>">
                    <?php if($i == $currentPage): ?>
                        <span><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href='?page=<?php echo $i; ?>'><?php echo $i; ?></a>
                    <?php endif; ?>
                </li>
            <?php endfor; ?>
            <li class="<?php if($currentPage >= $totalPages){ echo 'disabled'; } ?>">
                <a href="<?php if($currentPage < $totalPages){ echo "?page=".($currentPage + 1); } else { echo '#'; } ?>">Next</a>
            </li>
        </ul>
    <?php endif; ?>

    <hr> <h3>Manually Restore from SQL File</h3> <form action="" method="post" enctype="multipart/form-data" onsubmit="return confirmUpload(this);">
        <div> <label for="sql_file_input">Select .sql file:</label> <input type="file" id="sql_file_input" name="sql_file" accept=".sql" required>
        </div>
        <input type="submit" value="Upload & Restore">
    </form>

    <a href="../dashboard.php" class="button backtodash">Back to Dashboard</a>
</div>
</body>
</html>