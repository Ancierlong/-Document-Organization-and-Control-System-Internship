<?php
session_start();
require 'db_connect.php'; // Ensure database connection is established

// Check if the user is logged in to log the logout action
if (isset($_SESSION['user_id'])) {
    $userid = $_SESSION['user_id'];
    $username = $_SESSION['username']; // Optionally log username

    date_default_timezone_set("Asia/Manila");
    $datetime = date("Y-m-d H:i:s");
    $action = "Logout";

    // Log the logout action
    $logStmt = $conn->prepare("INSERT INTO user_logs (action, user, date) VALUES (?, ?, ?)");
    if ($logStmt) {
        $logStmt->bind_param("sis", $action, $userid, $datetime);
        $logStmt->execute();
        $logStmt->close();
    } else {
        // Handle the error if the prepare statement fails
        error_log("Error preparing logout log statement: " . $conn->error);
        // Optionally display an error message to the user (not recommended for security reasons on logout)
    }
}

// Destroy the session
session_destroy();

// Redirect to the login page:
header('Location: index.php');
exit;
?>