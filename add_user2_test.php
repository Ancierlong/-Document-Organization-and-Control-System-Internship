<?php
session_start();
// If the user is not logged in redirect to the login page...
if (!isset($_SESSION['user_id'])) {
	header('Location: ../index.php');

	if (!isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
  }
  exit;
}
$userlog = $_SESSION['username'];
require '../db_connect.php';
$display ="";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = trim($_POST['role']);
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    
    // Default password is "12345", but it will be hashed before storing
    $default_password = "12345";
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

    // Insert user into database
    $stmt = $conn->prepare("INSERT INTO users (role, username, password, full_name, email, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $role, $username, $hashed_password, $full_name, $email, $userlog);

    if ($stmt->execute()) {
        echo "User added successfully! Default password is 12345.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
</head>
<body>
    <form method="POST" action="">
        
        <label for="role">Role:</label>
            <select list="preset" id="role" name="role" required>
                <option value="Admin">Admin</option>
                <option value="Faculty">Faculty</option>
                <option value="Student">Student</option>
            </select>

        <label for="username">Username:</label>
        <input type="text" name="username" required><br>

        <label for="full_name">Full Name:</label>
        <input type="text" name="full_name" required><br>

        <label for="email">Email:</label>
        <input type="email" name="email" required><br>

        <button type="submit">Add User</button>
    </form>
</body>
</html>
