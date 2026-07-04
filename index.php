<?php
session_start();
require 'db_connect.php';

$display ="";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    

    $stmt = $conn->prepare("SELECT id, role, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $userid = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $username;
            date_default_timezone_set("Asia/Manila");
            $datetime = date("Y-m-d H:i:s");

            // Log successful login
            $logStmt = $conn->prepare("INSERT INTO user_logs (action, user, date) VALUES (?, ?, ?)");
            $logStatus = "success";
            $action = "Login";
            $logStmt->bind_param("sis", $action, $userid, $datetime);
            $logStmt->execute();
            $logStmt->close();

            header("Location: dashboard.php"); // Redirect to dashboard
            exit;
        } else {
            //echo "Invalid password.";
            $display = "<h3 style='color:orange'> Invalid password.";

            // Log failed login - invalid password
            $logStmt = $conn->prepare("INSERT INTO user_logs (action, user, date) VALUES (?, ?, ?)");
            $logStatus = "failed - invalid password";
            $action = "Login Failed Wrong Password";
            $logStmt->bind_param("sis", $action ,$userid ,$datetime);
            //$logStmt->execute();
            //$logStmt->close();
        }
    } else {
        //echo "User not found.";
        $display = "<h3 style='color:red'>User not found. </h3>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body {
            font-family: sans-serif;
            background-color: #7F1416;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .login-container {
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Reduced shadow for a cleaner look */
            padding: 30px; /* Increased padding for better spacing */
            width: 70%; /* Set a fixed width for better alignment */
            max-width: 90%; /* Ensure responsiveness on smaller screens */
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 25px; /* Increased margin */
            color: black; /* Darker text for better readability */
        }

        .login-container h3 {
            text-align: center;
            margin-bottom: 15px;
            color: black;
            font-size: 1.2em; /* Slightly smaller font size */
        }

        .login-container h4 {
            text-align: center;
            margin-bottom: 20px;
            color: black;
            font-size: 1em; /* Even smaller font size */
        }

        .login-container img {
            display: block; /* To center the image properly */
 /* Center horizontally and add margin below */
            width: 150px; /* Adjust the desired width */
            height: auto; /* Maintain aspect ratio */
        }

        .login-container hr {
            border: none;
            height: 1px;
            background-color: #ddd;
            margin-top: 25px;
            margin-bottom: 25px;
        }

        .login-container label {
            display: block; /* Make labels take full width for better alignment */
            font-size: 14px;
            color: #333;
            margin-bottom: 5px; /* Add some space below the label */
        }

        .login-container form input[type="text"],
        .login-container form input[type="password"] {
            padding: 12px; /* Increased padding */
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            transition: border-color 0.3s ease;
            width: calc(100% - 24px); /* Adjust width to account for padding and border */
            box-sizing: border-box; /* Include padding and border in the element's total width and height */
            font-size: 16px; /* Slightly larger font size for input */
        }

        .login-container form input[type="text"]:focus,
        .login-container form input[type="password"]:focus {
            border-color: #007BFF;
            outline: none; /* Remove default focus outline */
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5); /* Add a subtle focus shadow */
        }

        .login-container input[type="submit"] {
            background-color: #007BFF; /* Updated primary color */
            font-size: 16px;
            font-weight: bold;
            color: #fff;
            padding: 12px 20px; /* Adjusted padding */
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        .login-container input[type="submit"]:hover {
            background-color: #0056b3; /* Darker shade on hover */
        }

        .button {
            display: block;
            text-align: center;
            margin-bottom: 15px; /* Adjusted margin */
            padding: 12px; /* Adjusted padding */
            background-color: #28a745; /* Updated button color */
            color: #fff;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: #1e7e34; /* Darker shade on hover */
        }

        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 20px; /* Add spacing between form groups */
        }

        .form-row {
            display: flex;
            gap: 20px; /* Space between the two input fields */
        }

        .form-row > div {
            flex: 1; /* Distribute equal width to the child divs */
        }

        .logos {
        display: flex;
        flex-direction: row;
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="logos">
    <div style="width: 100%; margin-left:12.5px; margin-right: 12.5px;">
    <img src="logos/ccs.png">
    </div>
    <div style="width: 5000%; margin-left:12.5px; margin-right: 12.5px;">
    <h2>UPHSD MOLINO</h2>
    <h2>
        <!--CCS Document Management System-->
        CCS-DOCS
        <br><br>
        College of Computer Studies' Document Organization and Control System
    </h2>
    <?php if (!empty($display)): ?>
        <h3 class="error-message"><?php echo $display;?></h3>
    <?php endif; ?>
    </div>
    <div style="width: 100%; margin-left:12.5px; margin-right: 12.5px;">
    <img src="logos/UPHSD.png">
    </div>
    </div>
    <hr>
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label for="username"><b>Username:</b></label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password"><b>Password:</b></label>
                <input type="password" id="password" name="password" required>
            </div>
        </div>
        <div class="form-group">
            <input type="submit" value="Login">
        </div>
        <hr>
        <h3>Free Access</h3>
        <h4><a href="capstone_thesis_public_view.php" class="button">View Capstone/Thesis List Only</a></h4>
    </form>
</div>
</body>
</html>