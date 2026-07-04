<?php

session_start();
// If the user is not logged in redirect to the login page...
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
  }

$conn = require '../db_connect.php';
$display = "";

// Include PHPMailer library (assuming it's in a 'PHPMailer' directory)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = trim($_POST['role']);
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $userlog2 = trim($_SESSION['username']); // Get username of logged-in user doing the adding
    date_default_timezone_set("Asia/Manila");
    $datetime = date("Y-m-d H:i:s");
    $userid = $_SESSION['user_id']; // Get ID of logged-in user doing the adding

    // --- Generate 6-character random password ---
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $charactersLength = strlen($characters);
    $generated_password = '';
    for ($i = 0; $i < 6; $i++) {
        // Use random_int for better randomness if PHP 7+ (highly recommended)
        try {
             $generated_password .= $characters[random_int(0, $charactersLength - 1)];
        } catch (Exception $e) {
             // Fallback for older PHP or if random_int fails (less secure)
             $generated_password .= $characters[rand(0, $charactersLength - 1)];
        }
    }
    // --- End Password Generation ---

    // Hash the *generated* password for security
    $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);

    // Check if username already exists
    $check_stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $display = "<span style='color: red;'>Username is already taken. Please choose another one.</span>";
    } else {
        // Insert user into database
        // Corrected bind_param types: role, username, password, full_name, email are strings (s), created_by is string (s), reg_date is string (s)
        $stmt = $conn->prepare("INSERT INTO users (role, username, password, full_name, email, created_by, reg_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        // Bind the logged-in user's username ($userlog2) as the 'created_by' value
        $stmt->bind_param("sssssss", $role, $username, $hashed_password, $full_name, $email, $userlog2, $datetime);


        if ($stmt->execute()) {
            // Log the action in user_logs table
            // Make sure logs_user table and columns (type, modified_item, user) exist
            // Assuming 'modified_item' should store info about the added user, maybe username?
            // Assuming 'user' refers to the user performing the action (logged-in user ID)
            //$log_stmt = $conn->prepare("INSERT INTO logs_user (type, modified_item, user_id) VALUES (?, ?, ?)"); // Assuming column name is user_id
            //$action = 'Add User';
            //$item_info = "Username: " . $username; // Log which user was added
            //$log_stmt->bind_param("ssi", $action, $item_info, $userid); // Bind logged-in user's ID
            //$log_stmt->execute();
            //$log_stmt->close();

            // --- Send email with generated password ---
            $mail = new PHPMailer(true);

            try {
                // SMTP settings
                $mail->isSMTP();
                $mail->Host = 'smtp-relay.brevo.com'; // Replace with your SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = ''; // Replace with your SMTP username
                $mail->Password = ''; // Replace with your SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS encryption
                $mail->Port = 587; // Or your SMTP port

                // Recipients
                $mail->setFrom('gelocalong@gmail.com', 'CCS DMS Admin'); // Replace with your email and system name
                $mail->addAddress($email, $full_name);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'New Account Created';
                $mail->Body = '<p>Dear ' . htmlspecialchars($full_name) . ',</p>'
                              . '<p>A new account has been created for you on our system.</p>'
                              . '<p>Your username is: <strong>' . htmlspecialchars($username) . '</strong></p>'
                              . '<p>Your temporary password is: <strong>' . htmlspecialchars($generated_password) . '</strong></p>'
                              . '<p>Please log in and change your password as soon as possible.</p>'
                              . '<p>Thank you,</p>'
                              . '<p>Your System Administrators</p>';
                $mail->AltBody = 'Dear ' . $full_name . ",\n\nA new account has been created for you on our system.\nYour username is: " . $username . "\nYour temporary password is: " . $generated_password . "\nPlease log in and change your password as soon as possible.\n\nThank you,\nYour System Administrators";

                $mail->send();
                $display = "<span style='color: green;'>User added successfully! Password has been sent to " . htmlspecialchars($email) . "</span>";

            } catch (Exception $e) {
                $display = "<span style='color: orange;'>User added successfully, but failed to send password to email. Generated password is: <strong>" . htmlspecialchars($generated_password) . "</strong> Error: " . htmlspecialchars($mail->ErrorInfo) . "</span>";
                error_log("Error sending email: " . $mail->ErrorInfo);
            }
            // --- End Email Sending ---

        } else {
            $display = "<span style='color: red;'>Error adding user: " . htmlspecialchars($stmt->error) . "</span>";
            error_log("Error adding user: " . $stmt->error); // Log error for debugging
        }
        $stmt->close();
    }

    $check_stmt->close();
    $conn->close();
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
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

    /* Removed the specific h3 style for password note */
    .addemp-container h4{
        text-align: center;
        margin-bottom: 20px;
    }

    /* Style for the feedback message */
    .feedback-message {
        text-align: center;
        margin-bottom: 20px;
        font-size: 1.1em; /* Slightly larger */
        /* Color is set dynamically in PHP */
    }


    .addemp-container img {
        display: block; /* Center image */
        margin-left: auto; /* Center image */
        margin-right: auto; /* Center image */
        width: 50%; /* Adjust the desired width */
        height: auto; /* Maintain aspect ratio */
        margin-bottom: 10px; /* Add space below image */
    }

    .addemp-container select {
        width: 100%;
        padding: 7.5px;
        margin-bottom: 20px; /* Increased margin */
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
    .addemp-container input[type="date"] {
        width: 100%;
        padding: 7.5px;
        margin-bottom: 15px; /* Increased margin */
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
    }

    .addemp-container label {
        font-size: 13px;
        font-weight: bold;
        display: block; /* Make labels block elements */
        margin-bottom: 5px; /* Add space below labels */
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
        margin-top: 15px; /* Added margin top */
        width: 50%;
        display: block; /* Center button */
        margin-left: auto; /* Center button */
        margin-right: auto; /* Center button */
        /* transform: translateX(+50%); */ /* Removed transform */
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

    /* Added style for form group */
     .form-group {
          margin-bottom: 15px; /* Add space between form groups */
     }

     /* Style for inline form elements if needed */
     .form-row {
          display: flex;
          gap: 20px; /* Space between inline elements */
     }
     .form-row .form-group {
          flex: 1; /* Make elements share space equally */
          margin-bottom: 0; /* Reset bottom margin for inline groups */
     }

    </style>
</head>
<body>
<div class="addemp-container">
 <h4>CCS-DOCS</h4>
  <h2>Add User</h2>

  <?php if (!empty($display)): ?>
      <div class="feedback-message"><?php echo $display; ?></div>
  <?php endif; ?>

  <hr>
  <form method="POST" action="">
    <div class="form-row"> <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                    <option value="Admin">Admin</option>
                    <option value="Faculty">Faculty</option>
                    <option value="Council">Council</option>
                </select>
            </div>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
    </div>

    <div class="form-row"> <div class="form-group">
                 <label for="full_name">Full Name:</label>
                 <input type="text" id="full_name" name="full_name" required>
             </div>
             <div class="form-group">
                 <label for="email">Email:</label>
                 <input type="email" id="email" name="email" required>
             </div>
    </div>

    <input type="submit" value="Add User"> </form>
  <hr>
  <a href="view_user_list.php" class="button backtodash">Back to User List</a> </div>
</body>
</html>
