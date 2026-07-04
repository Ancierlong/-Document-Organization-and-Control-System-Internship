<?php

session_start();

// Set content type header for JSON response
header('Content-Type: application/json');

// If the user is not logged in, return a JSON error response
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

// Retrieve required session variables with null coalescing for safety
$conceptPaperId = $_SESSION['concept_paper_id'] ?? null; // The concept paper ID (kept for potential future use, but won't be used in the reverted log)
$uploadedTimestamp = $_SESSION['uploaded_timestamp'] ?? null; // This variable holds the value from $_SESSION['PDF-ID2']
$userId = $_SESSION['user_id'] ?? null; // Get the user ID

// Ensure required session variables are set
// Note: We still need conceptPaperId for the status update query
if ($conceptPaperId === null || $uploadedTimestamp === null || $userId === null) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Missing required session data.']);
    exit;
}

// Require the database connection script
$conn = require '../db_connect.php';

// Check if connection was successful (in case db_connect.php didn't exit on failure)
if ($conn->connect_error) {
    error_log("Database Connection Error in update_pdf.php: " . $conn->connect_error);
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}


// Check if the request method is POST and necessary file/data are present
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["pdf"]) && isset($_POST["file_path"])) {

    $targetFile = $_POST["file_path"];
    $uploadedFile = $_FILES["pdf"];

    // --- Security Validations (Keeping these optimizations) ---

    // Validate file path using realpath to prevent directory traversal attacks
    $baseDir = realpath("../files_concept_papers/");
    $realTarget = realpath($targetFile);

    // Check if realpath succeeded and the target is within the allowed base directory
    if ($realTarget === false || $baseDir === false || strpos($realTarget, $baseDir) !== 0) {
        error_log("Invalid file path attempted: " . $targetFile . " by user " . $userId); // Log suspicious activity
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Invalid file path provided.']);
        $conn->close();
        exit;
    }

    // Validate file type using fileinfo (more reliable than $_FILES['type'])
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $uploadedMimeType = $finfo->file($uploadedFile["tmp_name"]);
    $allowedMimeTypes = ['application/pdf'];

    if (!in_array($uploadedMimeType, $allowedMimeTypes)) {
        error_log("Attempted upload with disallowed MIME type: " . $uploadedMimeType . " by user " . $userId);
        http_response_code(415); // Unsupported Media Type
        echo json_encode(['success' => false, 'message' => 'Error: Only PDF files are allowed.']);
        $conn->close();
        exit;
    }

    // Validate file size
    $maxFileSize = 10 * 1024 * 1024; // Example: 10MB, adjust as needed
    if ($uploadedFile["size"] > $maxFileSize) {
        error_log("Attempted upload exceeding max file size: " . $uploadedFile["size"] . " by user " . $userId);
        http_response_code(413); // Payload Too Large
        echo json_encode(['success' => false, 'message' => 'Error: File size exceeds limit (' . ($maxFileSize / 1024 / 1024) . 'MB).']);
        $conn->close();
        exit;
    }

    // --- File Replacement ---

    // Move uploaded file to replace the existing one at the validated path
    if (move_uploaded_file($uploadedFile["tmp_name"], $realTarget)) {

        // --- Database Updates ---

        // 1. Update database status (Keeping this logic)
        $sql_update_status = "UPDATE `conceptpapers` SET `status` = 'Approved' WHERE `id` = ?";
        $stmt_update_status = $conn->prepare($sql_update_status);

        if ($stmt_update_status === false) {
            error_log("MySQL Prepare Error (Status Update): " . $conn->error . " by user " . $userId);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error during status update preparation.']);
            $conn->close();
            exit;
        }

        $stmt_update_status->bind_param("i", $conceptPaperId);

        if ($stmt_update_status->execute()) {
            $stmt_update_status->close(); // Close statement immediately after execution

            // --- 2. Insert log entry (REVERTED LOGIC BELOW) ---

            $sql_insert_log = "INSERT INTO `logs_concept_papers` (`type`, `modify_id`, `user`) VALUES (?, ?, ?)";
            $stmt_insert_log = $conn->prepare($sql_insert_log);

            if ($stmt_insert_log === false) {
                // Log preparation error, but status update succeeded.
                error_log("MySQL Prepare Error (Log Insert - Reverted): " . $conn->error . " by user " . $userId);
                 // Decide if log failure should stop the process. Keeping previous behavior, it didn't.
            } else {
                $logType = "Approved"; // Log type
                // Revert to using the uploaded timestamp for modify_id and binding as string
                // Using the $uploadedTimestamp variable which holds the original $_SESSION['PDF-ID2'] value
                $stmt_insert_log->bind_param("sss", $logType, $uploadedTimestamp, $userId); // Bind types as string, string, string

                if (!$stmt_insert_log->execute()) {
                     error_log("MySQL Execute Error (Log Insert - Reverted): " . $stmt_insert_log->error . " by user " . $userId);
                     // Log execution error, but status update succeeded.
                }
                $stmt_insert_log->close(); // Close statement
            }

            // --- Success Response (Keeping this) ---
            // Report success even if log insertion failed (based on previous behavior).
            http_response_code(200); // OK
            echo json_encode(['success' => true, 'message' => 'PDF updated and status set to Approved.']);

        } else {
            // Error updating database status (Keeping this logic)
            error_log("MySQL Execute Error (Status Update): " . $stmt_update_status->error . " by user " . $userId);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error updating database status.']);
             $stmt_update_status->close();
        }

    } else {
        // Error moving file (Keeping this logic)
        error_log("Error moving uploaded file to target: " . $realTarget . " by user " . $userId);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error saving the modified PDF file on the server.']);
    }
} else {
    // Invalid request method or missing expected POST data/file (Keeping this logic)
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid request or missing required data.']);
}

$conn->close(); // Close database connection (Keeping this)
?>