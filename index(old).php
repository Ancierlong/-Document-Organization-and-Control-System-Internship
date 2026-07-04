<!DOCTYPE html>
<html>
<head>
  <title>CCS Department Capstone/Thesis & Document Management System</title>
  <style>
    body {
      font-family: /*Arial,*/ sans-serif;
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
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
      padding: 25px;
      width: 300px;
    }

    .login-container h2{
      text-align: center;
      margin-bottom: 20px;
    }
	
	.login-container h3{
      text-align: center;
      margin-bottom: 20px;
    }
	
	.login-container h4{
      text-align: center;
      margin-bottom: 20px;
    }
	
	.login-container img {
	  align: center;
      width: 100%; /* Adjust the desired width */
      height: auto; /* Maintain aspect ratio */
    }
	
	.login-container hr {
      border: none;
      height: 1px;
      background-color: #ddd;
      margin-top: 20px;
      margin-bottom: 20px;
    }

    .login-container label {
      font-family: Arial;
      font-size: 14px;
    }

    .login-container form input[type="text"],
    .login-container form input[type="password"] {
      padding: 10px;
      margin-bottom: 20px;
      padding-left: 40px;
      background-repeat: no-repeat;
      background-position: 10px center;
      background-size: 20px;
      border: 1px solid #ccc;
      border-radius: 6px;
      transition: border-color 0.3s ease;
      width: 80%;
    }
	
	.login-container form input[type="text"]:focus,
    .login-container form input[type="password"]:focus {
      border-color: #007BFF;
    }

    .login-container form input[type="text"] {
      background-image: url("img/usericon.png");
    }

    .login-container form input[type="password"] {
      background-image: url("img/passicon.png");
    }

    .login-container input[type="submit"] {
      background-color: #4CAF50;
      font-size: 16px;
      font-weight: bold;
      color: #fff;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      width: 100%;
      transition: background-color 0.3s ease;
    }

    .login-container input[type="submit"]:hover {
      background-color: #3d8b40;
    }

    .button {
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

    .button:hover {
      background-color: #45a049;
    }
  </style>
</head>
</html>
<?php
session_start();

// Check if the user is already logged in
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}

// Check if the user has submitted the login form
if (isset($_POST['username']) && isset($_POST['password'])) {
    // Connect to the database
    $conn = mysqli_connect("localhost", "root", "", "ccs_database");

    // Check if the connection was successful
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }

    // Retrieve the user's information from the database
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $sql = "SELECT id FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $sql);

    // Check if the query was successful
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }

    // Check if the user exists in the database
    if (mysqli_num_rows($result) == 1) {
        // Log the user in
        $_SESSION['username'] = $username;
        header("Location: dashboard.php");
        exit();
    } else {
        // Display an error message
        $error = "Invalid username or password.";
    }

    // Close the database connection
    mysqli_close($conn);
}

?>

<html>
<head>
    <title>Login</title>
</head>
<body>
    <div class="login-container">
	<img src="img\perpetual-logo.png">
    <h4>UPHSD MOLINO</h4>
    <h3>CCS Department Capstone/Thesis & Document Management System</h3>
    <hr>
    <?php if (isset($error)) { echo "<p>$error</p>"; } ?>
    <form method="post" action="index.php">
        <!--<label for="username">Username:</label>-->
        <input type="text" id="username" name="username" placeholder="Username" tabindex="1" required>
        <br>
        <!--<label for="password">Password:</label>-->
        <input type="password" id="password" name="password" placeholder="Password" tabindex="2" required>
        <br>
        <input type="submit" value="Login">
        <br>
        <hr>
        <h4>Free Access </h4>
        <h4><a href="capstone_thesis_public_view.php" class="button">View Capstone/Thesis List Only</a></h4>
        
      </form>
  </div>
</body>
</html>