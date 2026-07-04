<?php
session_start();

// If the user is not logged in redirect to the login page...
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$conn = require '../db_connect.php';

$id = $_POST['id']; // Get the ID from the form
$activity_id = $_POST['uploaded_at']; // Get the username for logging
$userid = $_SESSION['user_id'];

$sql = "UPDATE activityreports SET archive = 1 WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Log the archive  
    $logSql = "INSERT INTO logs_activity_reports (type, modified_item, user) VALUES (?, ?, ?)";
    $logStmt = $conn->prepare($logSql);
    $action = "Archived activity report with ID $id";
    $archived = "Archive";
    $logStmt->bind_param("ssi", $archived, $activity_id, $userid);
    $logStmt->execute();
    $logStmt->close();

    // Redirect back to the previous page
    header("Location: view_activity_reports_test.php");
    exit(); // Always exit after redirection
} else {
    echo "Error archiving record.";
}

$stmt->close();
$conn->close();
?>
