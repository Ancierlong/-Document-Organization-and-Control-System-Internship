<?php
require '../db_connect.php';
session_start(); // In case you want to log the current admin

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['promote'])) {
    $userId = (int)$_POST['user_id'];
    $user = $_SESSION['user_id'];
    $username = $_POST['un'];
    $newRole = $_POST['promote'];
    date_default_timezone_set("Asia/Manila");
    $datetime = date("Y-m-d H:i:s");
    $promotedBy = $_SESSION['username'];

    // Validate role
    $validRoles = ['Admin', 'Faculty', 'Council'];
    if (!in_array($newRole, $validRoles)) {
        echo "<p style='color: red;'>Invalid role selected.</p>";
        exit;
    }

    // Get current role
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($currentRole);

    if ($stmt->fetch()) {
        $stmt->close();

        if (strcasecmp(trim($currentRole), $newRole) === 0) {
            header("Location: {$_SERVER['HTTP_REFERER']}");
            exit;
        }

        // Update the role
        $updateStmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $updateStmt->bind_param("si", $newRole, $userId);

        if ($updateStmt->execute()) {
            $updateStmt->close();

            // Log the promotion
            //$promotedBy = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';

            $logStmt = $conn->prepare("
                INSERT INTO user_logs (action, user, date) 
                VALUES (?, ?, ?)
            ");

            $action="Change role of $username form $currentRole to $newRole";
            $logStmt->bind_param("sss", $action, $user, $datetime);
            $logStmt->execute();
            //$logStmt->close();

            echo "<p style='color: green;'>User promoted to '$newRole'. Promotion logged.</p>";
        } else {
            echo "<p style='color: red;'>Error updating role: " . $updateStmt->error . "</p>";
        }
    } else {
        echo "<p style='color: red;'>User not found.</p>";
    }
} else {
    echo "<p style='color: red;'>Invalid request.</p>";
}

$conn->close();
header("Location: {$_SERVER['HTTP_REFERER']}");
exit;
?>
