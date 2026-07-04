<?php

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'ccs_database';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = ""; // Store output messages

switch ($action) {
    case 'backup':
        $message = backupDatabase($conn, $database);
        break;
    case 'restore':
        $message = restoreDatabase($conn, isset($_GET['file']) ? $_GET['file'] : '');
        break;
    case 'truncate':
        $message = truncateDatabase($conn, $database);
        break;
    case 'auto_backup':
        $message = checkAutoBackup();
        break;
}

function backupDatabase($conn, $database) {
    $backupDir = 'backups/';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0777, true);
    }

    $backupFile = $backupDir . 'CCS_DATABASE_' . date('Y-m-d_H-i-s') . '.sql';
    $tables = [];
    $excludedTable = 'users';
    $query = $conn->query("SHOW TABLES");
    while ($row = $query->fetch_array()) {
        if ($row[0] !== $excludedTable) {
            $tables[] = $row[0];
        }
    }

    $sqlScript = "";
    foreach ($tables as $table) {
        $result = $conn->query("SELECT * FROM $table");
        $numColumns = $result->field_count;

        $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
        $row2 = $conn->query("SHOW CREATE TABLE $table")->fetch_array();
        $sqlScript .= "\n" . $row2[1] . ";\n\n";

        while ($row = $result->fetch_assoc()) {
            $sqlScript .= "INSERT INTO `$table` VALUES(";
            $values = array_map(function ($value) use ($conn) {
                return isset($value) ? "'" . $conn->real_escape_string($value) . "'" : "NULL";
            }, array_values($row));
            $sqlScript .= implode(",", $values) . ");\n";
        }
        $sqlScript .= "\n";
    }

    file_put_contents($backupFile, $sqlScript);
    cleanOldBackups($backupDir);
    file_put_contents('last_backup.txt', time());
    return "✅ Database backup completed! File: $backupFile";
}

function restoreDatabase($conn, $file) {
    $backupDir = 'backups/';
    $filePath = $backupDir . $file;

    if (!file_exists($filePath)) {
        return "❌ Error: Backup file not found!";
    }

    $sql = file_get_contents($filePath);

    if (!$sql) {
        return "❌ Error: Could not read backup file.";
    }

    $conn->query("SET FOREIGN_KEY_CHECKS=0;");
    $queries = explode(";\n", $sql);
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            if (!$conn->query($query)) {
                return "❌ Error executing query: " . $conn->error;
            }
        }
    }
    $conn->query("SET FOREIGN_KEY_CHECKS=1;");
    return "✅ Database restored successfully!";
}

function truncateDatabase($conn, $database) {
    $excludedTable = 'users';
    $query = $conn->query("SHOW TABLES");
    while ($row = $query->fetch_array()) {
        if ($row[0] !== $excludedTable) {
            $conn->query("TRUNCATE TABLE `" . $row[0] . "`");
        }
    }
    return "⚠️ Database truncated, excluding table: $excludedTable";
}

function checkAutoBackup() {
    $backupInterval = 90 * 24 * 60 * 60;
    $lastBackupTime = file_exists('last_backup.txt') ? file_get_contents('last_backup.txt') : 0;
    $nextBackupDate = date('Y-m-d H:i:s', $lastBackupTime + $backupInterval);
    return "ℹ️ Next automatic backup is scheduled for: " . $nextBackupDate;
}

function cleanOldBackups($backupDir) {
    $files = glob($backupDir . 'CCS_DATABASE_*.sql');
    if (count($files) > 5) {
        usort($files, function ($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        unlink($files[0]);
    }
}

// Check if a file was uploaded
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
    $fileTmpPath = $_FILES['sql_file']['tmp_name'];
    $fileName = $_FILES['sql_file']['name'];
    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

    if (strtolower($fileExtension) !== 'sql') {
        $message = "❌ Error: Only .sql files are allowed.";
    } else {
        $sql = file_get_contents($fileTmpPath);
        if (!$sql) {
            $message = "❌ Error: Could not read SQL file.";
        } else {
            $insertQueries = [];
            $lines = explode("\n", $sql);
            foreach ($lines as $line) {
                $trimmedLine = trim($line);
                if (preg_match('/^INSERT INTO/i', $trimmedLine)) {
                    $insertQueries[] = $trimmedLine;
                }
            }

            if (empty($insertQueries)) {
                $message = "❌ Error: No INSERT statements found in the SQL file.";
            } else {
                $errors = [];
                foreach ($insertQueries as $query) {
                    if (!$conn->query($query)) {
                        $errors[] = "❌ Error executing query: " . $conn->error;
                    }
                }

                if (empty($errors)) {
                    $message = "✅ Data restored successfully!";
                } else {
                    $message = "⚠️ Some queries failed:<br>" . implode("<br>", $errors);
                }
            }
        }
    }
}

$conn->close();
?>

<script>
        function confirmAction(action, file = '') {
            let message = '';
            switch (action) {
                case 'backup':
                    message = "Are you sure you want to BACKUP the database?";
                    break;
                case 'restore':
                    message = "WARNING: Restoring will OVERWRITE current data! Proceed?";
                    break;
                case 'truncate':
                    message = "WARNING: This will DELETE all data except the excluded table. Continue?";
                    break;
            }
            if (confirm(message)) {
                window.location.href = `?action=${action}${file ? '&file=' + file : ''}`;
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
      background-color: #7F1416;
      margin-top: 20px;
      margin-bottom: 20px;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      /*height: 100vh;*/
    }

    .addemp-container {
      background-color: #fff;
      border-radius: 4px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
      padding: 25px;
      width: 600px;
    }

    .addemp-container h2{
      text-align: center;
      margin-bottom: 20px;
    }
	
	.addemp-container h3{
      text-align: center;
      margin-bottom: 20px;
    }
	
	.addemp-container h4{
      text-align: center;
      margin-bottom: 20px;
    }
	
	.addemp-container img {
	  align: center;
      width: 50%; /* Adjust the desired width */
      height: auto; /* Maintain aspect ratio */
    }
	
	.addemp-container select {
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

    .addemp-container input[type="text"],
    .addemp-container input[type="date"] {
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
	}
	
	.addemp-container .button {
        display: inline-flex;
      justify-content: center;
      text-align: center;
      margin-bottom: 10px;
      padding: 10px;
      background-color: #4CAF50;
      color: #fff;
      text-decoration: none;
      border: none;
      border-radius: 4px;
      transition: background-color 0.3s ease;
      margin: 5px 5px;
      width: 33%;
      font-size: 22px;
    }

    .addemp-container .button:hover {
      background-color: #3d8b40;
    }
        
    .buttons-container {
      width: 100%;
      display: flex;
      flex-direction: row;
    }

    .addemp-container .button2 {
      display: block;
      text-align: center;
      margin-bottom: 10px;
      padding: 10px;
      background-color: #4CAF50;
      color: #fff;
      text-decoration: none;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .addemp-container .button2:hover {
      background-color: #3d8b40;
    }

    .addemp-container input[type="submit"] {
      background-color: #4CAF50;
	  font-size: 16px;
      color: #fff;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
	  margin-top: 5px;
      width: 50%;
	  transform: translateX(+50%);
      transition: background-color 0.3s ease;
    }

    .addemp-container input[type="submit"]:hover {
      background-color: #3d8b40;
    }
	
	.addemp-container .backtodash {
      background-color: #ff851b;
      width:95%;
    }

    .addemp-container .backtodash:hover {
      background-color: #d47716;
    }
    .message {
        padding: 10px;
        margin: 10px 0;
        border-radius: 4px;
        font-weight: bold;
        text-align: center;
    }
    .message.success {
        background-color: #4CAF50;
        color: white;
    }
    .message.error {
        background-color: #f44336;
        color: white;
    }

    table {
        width: 100%;
    }

    tr {
    }

    .tablelabel {
        text-align: center;
    }

    .tablebutton {
    }

    #truncate {
        background-color: #FF4136;
    }

    #truncate:hover {
        background-color: #C12C24;
    }
    
    </style>
</head>
<body>
<div class="addemp-container">
    <h2>Database Management</h2>
    
    <!-- Display message here -->
    <?php if (!empty($message)): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>
    <div class="buttons-container">
    <button class="button" onclick="confirmAction('backup')">Backup Database</button>
    <button class="button" onclick="window.location.href='?action=auto_backup'">Check Auto Backup</button>
    <button id="truncate" class="button" onclick="confirmAction('truncate')">Truncate Database</button>
    </div>
    <h3>Available Backups</h3>
    <table>
        <?php
        $backupDir = 'backups/';
        if (is_dir($backupDir)) {
            $files = array_reverse(glob($backupDir . 'CCS_DATABASE_*.sql'));
            foreach ($files as $file) {
                $fileName = basename($file);
                echo "<tr>
                        <td class='tablelabel'>
                            <a href='$file' download>$fileName</a>
                        </td>
                        <td class='tablebutton'>
                            <a class='button2' href='#' onclick=\"confirmAction('restore', '$fileName')\">Restore</a>
                        </td>
                    </tr>";
            }
        }
        ?>
    </table>
    <hr>
    <!-- test file check for restore-->
    <h2>Manually upload an SQL File to Restore Database</h2>
    <div style="width:45%; display:inline-block; margin-left:12.5px; margin-right: 12.5px;">
    <form action="" method="post" enctype="multipart/form-data">
        <input type="file" name="sql_file" accept=".sql" equired>
    </div>
        <button type="submit">Upload & Restore</button>
    </form>
    <a href="../dashboard.php" class="button backtodash">Back to Dashboard</a>
</div>
</body>
</html>
