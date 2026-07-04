<!DOCTYPE html>
<html>
<head>
  <title>CCS Department Document Management System | Add User</title>
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
      width: 600px;
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

<?php
session_start();
// If the user is not logged in redirect to the login page...
if (!isset($_SESSION['username'])) {
	header('Location: ../index.php');

	if (!isset($_SESSION['employeeid'])) {
    header('Location: ../dashboard.php');
  }
  exit;
}
$userlog = $_SESSION['username'];
$conn = require '../db_connect.php';
$display ="";

// Check if the user has submitted the form
if (isset($_POST['submit'])) {
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Insert the values into the database
    $sql = "INSERT INTO users (role, username, full_name, email, created_by) VALUES ('$role', '$username', '$name', '$email', '$userlog')";

    if (mysqli_query($conn, $sql)) {
        $display = "<h2 style='color:red;'>User Added!</h2>";
    } else {
        echo "Error: " . mysqli_error($conn);
    } 
}

// Close the database connection
// mysqli_close($conn);
?>


<body>
  <div class="addemp-container">
	<img src="img\perpetual-logo.png">
	<h4>CCS Department Document Management System</h4>
	<h2>Add User</h2>
    <?php
    echo $display;
    ?>
	<hr>
    <form method="post" action="add_user.php">
        <div style="width:45%; display:inline-block; margin-left: 12.5px; margin-right: 12.5px;">
		    <label for="role">Role:</label>
            <select list="preset" id="role" name="role" required>
                <option value="Admin">Admin</option>
                <option value="Faculty">Faculty</option>
                <option value="Student">Student</option>
            </select>
            <br>
            <label for="username">User Name:</label>
            <input type="text" id="username" name="username" required>
            <br>
		</div>
		<div style="width:45%; display:inline-block; margin-left:12.5px; margin-right: 12.5px;">
            <label for="full_name">Full Name:</label>
            <input type="text" id="full_name" name="full_name" required>
            <br>        
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            <br>
		</div>
		<div style="">
            <input type="submit" name="submit" value="Submit New Record" onclick="uploadFile()">
		</div>
    </form>
	<hr>
  <!-- 
  <script>
function uploadFile() {
    let file = document.getElementById("file_name").files[0];
    if (!file) {
        document.getElementById("status").innerText = "No file selected!";
        return;
    }

    let formData = new FormData();
    formData.append("file", file);

    fetch("capstone_thesis_file_upload.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(result => document.getElementById("status").innerText = result)
    .catch(error => document.getElementById("status").innerText = "Upload failed.");
}
</script>
-->
	<a href="view_user_list.php" class="button backtodash">Back to Dashboard</a>
  </div>
</body>
</html>