<?php
session_start();

// Ensure the user is logged in and has the correct role
// This check means roles Council, Faculty, Student are FORBIDDEN from accessing this script.
// Make sure roles that ARE allowed to reject (e.g., Admin) are NOT in this list.
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] === 'Council' || $_SESSION['role'] === 'Faculty' || $_SESSION['role'] === 'Student')) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

require '../db_connect.php';

header('Content-Type: application/json'); // Set header for JSON response

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the ID and rejection reason from the POST data
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $rejectionReason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : '';
    // We also need the 'uploaded_at' value for logging, which should ideally be sent from the frontend
    // However, retrieve_concept_papers.php has it. A safer approach might be to fetch it from the DB here using the $id.
    // For now, let's assume 'uploaded_at' is also sent in the POST request like it was for pdffinalv2.php
    $uploaded_at = isset($_POST['uploaded_at']) ? $_POST['uploaded_at'] : null; // Assuming this is sent from the frontend

    // Get the user ID from the session for the log
    $userId = $_SESSION['user_id'];

    // Validate input
    if ($id <= 0) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'message' => 'Invalid ID provided.']);
        exit;
    }
     // Basic validation for rejection reason
     if (empty($rejectionReason)) {
         http_response_code(400); // Bad Request
         echo json_encode(['success' => false, 'message' => 'Rejection reason cannot be empty.']);
         exit;
     }
     // Ensure we have the uploaded_at value for logging (basic check)
     if (empty($uploaded_at)) {
          // You might want to fetch this from the DB using $id instead if it's not reliably sent from frontend
          error_log("Reject script called without 'uploaded_at' for ID: " . $id . " by user " . $userId);
          // Decide if this should be a fatal error or just a log entry without modify_id
          // For now, we'll log a warning and continue, inserting NULL or an empty string for modify_id
     }


    // Prepare the UPDATE statement using prepared statements
    // Include updating the rejection_reason column
    $sql = "UPDATE conceptpapers SET status = 'Rejected', rejection_reason = ? WHERE id = ?";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters
        $stmt->bind_param("si", $rejectionReason, $id); // "s" for string, "i" for integer

        // Execute the statement
        if ($stmt->execute()) {
            // Check if any rows were affected (if the ID existed and was updated)
            if ($stmt->affected_rows > 0) {

                // --- Insert Log Entry (Replicating original update_pdf.php logic) ---
                $sql_insert_log = "INSERT INTO `logs_concept_papers` (`type`, `modify_id`, `user`) VALUES (?, ?, ?)";
                $stmt_insert_log = $conn->prepare($sql_insert_log);

                if ($stmt_insert_log === false) {
                    // Log preparation error server-side
                    error_log("MySQL Prepare Error (Reject Log): " . $conn->error . " for ID " . $id . " by user " . $userId);
                     // Log insertion failed, but the main update succeeded. Decide how critical logging is.
                } else {
                    $logType = "Rejected"; // Log type indicating rejection
                    // Use the uploaded_at value for modify_id and bind as string (as per original behavior)
                    // If $uploaded_at was not sent, use NULL or an empty string depending on DB schema
                    $logModifyId = $uploaded_at ?? ''; // Use '' if $uploaded_at is null/empty

                    $stmt_insert_log->bind_param("sss", $logType, $logModifyId, $userId); // Bind types as string, string, string

                    if (!$stmt_insert_log->execute()) {
                         // Log execution error server-side
                         error_log("MySQL Execute Error (Reject Log): " . $stmt_insert_log->error . " for ID " . $id . " by user " . $userId);
                         // Log insertion failed, but status update succeeded.
                    }
                    $stmt_insert_log->close(); // Close statement
                }
                // --- End Log Insertion ---

                // Return success with the reason (for display in file 1)
                echo json_encode(['success' => true, 'message' => 'Concept Paper rejected.', 'rejection_reason' => $rejectionReason]);

            } else {
                // No rows affected - might mean the ID didn't exist or status was already Rejected
                 // Fetch the current status and reason to give a more specific message
                 $checkSql = "SELECT status, rejection_reason, uploaded_at FROM conceptpapers WHERE id = ?"; // Also fetch uploaded_at here if needed for log fallback
                 if ($checkStmt = $conn->prepare($checkSql)) {
                     $checkStmt->bind_param("i", $id);
                     $checkStmt->execute();
                     $checkResult = $checkStmt->get_result();
                     if ($checkRow = $checkResult->fetch_assoc()) {
                         if ($checkRow['status'] === 'Rejected') {
                             echo json_encode(['success' => false, 'message' => 'Concept Paper is already rejected.', 'rejection_reason' => $checkRow['rejection_reason']]);
                         } else {
                             // This case is unlikely if $stmt->affected_rows was 0 but the ID exists and isn't rejected
                             echo json_encode(['success' => false, 'message' => 'Concept Paper found but status not changed (possibly already rejected with the same reason).']);
                         }
                     } else {
                         echo json_encode(['success' => false, 'message' => 'Concept Paper not found.']);
                     }
                     $checkStmt->close();
                 } else {
                     error_log("MySQL Prepare/Execute Error (Reject Status Check): " . $conn->error . " for ID " . $id . " by user " . $userId);
                     echo json_encode(['success' => false, 'message' => 'Error checking record status.']);
                 }
            }
        } else {
            // Execution failed
            error_log("MySQL Execute Error (Reject Update): " . $stmt->error . " for ID " . $id . " by user " . $userId);
            http_response_code(500); // Internal Server Error
            echo json_encode(['success' => false, 'message' => 'Database update failed.']);
        }

        // Close statement
        $stmt->close();
    } else {
        // Prepare failed
        error_log("MySQL Prepare Error (Reject Update): " . $conn->error . " by user " . $userId);
        http_response_code(500); // Internal Server Error
        echo json_encode(['success' => false, 'message' => 'Database query preparation failed.']);
    }
} else {
    // Not a POST request
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

// Close database connection
$conn->close();

?>