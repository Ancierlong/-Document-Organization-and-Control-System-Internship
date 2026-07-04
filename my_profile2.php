<?php
session_start();
require '../db_connect.php'; // Include database connection

 if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
  }

$user_id = $_SESSION['user_id']; // Assuming user ID is stored in the session

// Fetch current user details
$stmt = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
if (!$stmt) {
    $_SESSION['error'] = "Database error: " . $conn->error;
    header("Location: my_profile2.php"); // Reload page
    exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $current_name = $row['full_name'];
    $current_email = $row['email'];
} else {
    $_SESSION['error'] = "User not found.";
    header("Location: my_profile2.php");
    exit;
}

$stmt->close();

// Process form submission
if (isset($_POST['submit'])) {
    $new_name = trim($_POST['full_name']);
    $new_email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Set new values only if fields are not empty
    $updated_name = !empty($new_name) ? $new_name : $current_name;
    $updated_email = !empty($new_email) ? $new_email : $current_email;

    $update_successful = false; // Flag to track update success
    $password_changed = false;
    $name_changed = false;
    $email_changed = false;

    // If user wants to change password
    if (!empty($current_password) && !empty($new_password) && !empty($confirm_password)) {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($stored_password);
            $stmt->fetch();
            $stmt->close(); // Close this statement

            if (password_verify($current_password, $stored_password)) {
                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE users SET password = ?, full_name = ?, email = ? WHERE id = ?");
                    $update_stmt->bind_param("sssi", $hashed_password, $updated_name, $updated_email, $user_id);
                    if ($update_stmt->execute()) {
                        $update_successful = true;
                        $password_changed = true;
                        if ($updated_name !== $current_name) {
                            $name_changed = true;
                        }
                        if ($updated_email !== $current_email) {
                            $email_changed = true;
                        }
                    } else {
                        $_SESSION['error'] = "Error updating profile: " . $update_stmt->error;
                    }
                    $update_stmt->close();
                } else {
                    $_SESSION['error'] = "New passwords do not match.";
                    header("Location: my_profile2.php");
                    exit;
                }
            } else {
                $_SESSION['error'] = "Current password is incorrect.";
                header("Location: my_profile2.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "User not found.";
            header("Location: my_profile2.php");
            exit;
        }
    } else {
        // Update only name and email if no password change
        $update_stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $updated_name, $updated_email, $user_id);
        if ($update_stmt->execute()) {
            $update_successful = true;
            if ($updated_name !== $current_name) {
                $name_changed = true;
            }
            if ($updated_email !== $current_email) {
                $email_changed = true;
            }
        } else {
            $_SESSION['error'] = "Error updating profile: " . $update_stmt->error;
        }
        $update_stmt->close();
    }

    // Log the changes
    date_default_timezone_set("Asia/Manila");
    $datetime = date("Y-m-d H:i:s");
    $log_action = "Profile updated: ";
    $changes = [];

    if ($name_changed) {
        $changes[] = "Name changed from '" . $current_name . "' to '" . $updated_name . "'";
    }
    if ($email_changed) {
        $changes[] = "Email changed from '" . $current_email . "' to '" . $updated_email . "'";
    }
    if ($password_changed) {
        $changes[] = "Password changed";
    }

    if (!empty($changes)) {
        $log_action .= implode(", and ", $changes) . ".";
        $logStmt = $conn->prepare("INSERT INTO user_logs (action, user, date) VALUES (?, ?, ?)");
        if ($logStmt) {
            $logStmt->bind_param("sis", $log_action, $user_id, $datetime);
            $logStmt->execute();
            $logStmt->close();
        } else {
            error_log("Error preparing profile update log statement: " . $conn->error);
        }
    }


    if ($update_successful) {
        $_SESSION['success'] = "Profile Updated!";
    }

    $conn->close();
    header("Location: my_profile2.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
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
    .addemp-container input[type="email"],
    .addemp-container input[type="password"],
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

    .addemp-container .button:hover {
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
    }

    .addemp-container .backtodash:hover {
        background-color: #d47716;
    }
    </style>
</head>
<body>
    <div class="addemp-container">
    <h4>CCS-DOCS</h4>
    <h2>My Profile</h2>
    <?php
    //echo $display;
    ?>
    <?php
    if (isset($_SESSION['error'])) {
        echo "<h2 style='color: red'>" . $_SESSION['error'] . "</h2>";
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo "<h2 style='color: green'>" . $_SESSION['success'] . "</h2>";
        unset($_SESSION['success']);
    }
    ?>

    <form method="POST" action="">
    <div style="display:flex; flex-direction:row;">
    <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label>Full Name:</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($current_name) ?>"><br>
    </div>

    <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($current_email) ?>"><br>
    </div>
    </div>
        <hr>
        <h2>Change Password</h2>
        <div style="display:flex; flex-direction:row;">
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label>Current Password:</label>
        <input type="password" name="current_password"><br>
    </div>
    <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label>New Password:</label>
        <input type="password" name="new_password"><br>
    </div>
    </div>
    <div style="display:flex; flex-direction:row;">
    <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label>Confirm New Password:</label>
        <input type="password" name="confirm_password"><br>
    </div>
    <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
    </div>
    </div>
    <br><br>
        <input type="submit" name="submit" value="Update Profile">
    </form>
        <hr>
    <a href="../dashboard.php" class="button backtodash">Back to Dashboard</a>

</div>
</body>
</html>