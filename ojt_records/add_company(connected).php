<!DOCTYPE html>
<html>
<head>
  <title>CCS Department Database Management System | Add Ojt Record</title>
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
    .addemp-container input[type="url"],
    .addemp-container input[type="date"] {
      width: 100%;
      padding: 7.5px;
      margin-bottom: 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-sizing: border-box;
    }

    .textarea {
      font-family: Arial, sans-serif;
      width: 100%;
      height: 100px;
      resize: none; 
      padding: 7.5px;
      margin-bottom: 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-sizing: border-box;
    }

    .addemp-container input[type="file"] {
      width: 100%;
      padding: 5.5px;
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
$display= "";
$conn = require '../db_connect.php';

if (isset($_POST['submit'])) {
  $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
  $company_description = mysqli_real_escape_string($conn, $_POST['company_description']);
  $company_website = "http://" . mysqli_real_escape_string($conn, $_POST['company_website']);
  $company_telno = mysqli_real_escape_string($conn, $_POST['company_telno']);
  $company_email = mysqli_real_escape_string($conn, $_POST['company_email']);
  $company_address = mysqli_real_escape_string($conn, $_POST['company_address']);
  $contact_person = mysqli_real_escape_string($conn, $_POST['contact_person']);
  $contact_person_email = mysqli_real_escape_string($conn, $_POST['contact_person_email']);
  $contact_person_telno = mysqli_real_escape_string($conn, $_POST['contact_person_telno']);
  date_default_timezone_set("Asia/Manila");
  $datetime = date("Y-m-d H:i:s");
  $userid = $_SESSION['user_id'];

  
  // Insert the values into the database
  $sql = "INSERT INTO company_linkages (company_name , company_description, company_website, company_telno, company_email, company_address, contact_person, contact_person_email, contact_person_telno, date_added, uploaded_at) 
  VALUES ('$company_name', '$company_description', '$company_website' , '$company_telno' , '$company_email' ,'$company_address','$contact_person','$contact_person_email','$contact_person_telno','$datetime', '$datetime');";

  $sql .= "INSERT INTO logs_company_linkages (`type`, `modified_item`, `user`) 
  VALUES ('Add', '$datetime', $userid);";

  if (mysqli_multi_query($conn, $sql)) {
      $display = "<h2 style='color:green;'>Company/Linkage Added!</h2>";
  } else {
      echo "Error: " . mysqli_error($conn);
  } 
}

?>
<body>
  <div class="addemp-container">
	<h4>CCS-DOCS</h4>
	<h2>Add Company / Linkage</h2>
  <?php
    echo $display;
    ?>
	<hr>
<form method="post" action="add_company(connected).php?redirect=<?php echo urlencode($_GET['redirect']); ?>">
<div style="display:flex; flex-direction:row;">
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
            <label for="company_name">Company Name:</label>
            <input type="text" id="company_name" name="company_name" required>
        </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
            <label for="company_website">Company Website:</label>
            <input type="text" id="company_website" name="company_website" required>
        </div>
    </div>
    <div style="display:flex; flex-direction:row;">
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
            <label for="company_telno">Company Tel No.:</label>
            <input type="text" id="company_telno" name="company_telno" required>
        </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
            <label for="company_email">Company Email:</label>
            <input type="email" id="company_email" name="company_email" required>
        </div>
        </div>
    <div style="display:flex; flex-direction:row;">
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
            <label for="company_address">Company Address:</label>
            <input type="text" id="company_address" name="company_address" required>
        </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
            <label for="contact_person">Contact Person:</label>
            <input type="text" id="contact_person" name="contact_person" required>
        </div>
    </div>
    <div style="display:flex; flex-direction:row;">
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
            <label for="contact_person_email">Contact Person Email:</label>
            <input type="email" id="contact_person_email" name="contact_person_email" required>
        </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
            <label for="contact_person_telno">Contact Person Tel No.:</label>
            <input type="text" id="contact_person_telno" name="contact_person_telno" required>
        </div>
    </div>
        <div style="">
            <label for="company_description">Description:</label>
            <textarea id="company_description" class="textarea" name="company_description" required></textarea>
            <br>
		</div>
		<div style="">
            <input type="submit" name="submit" value="Update Record">
		</div>
</form>

<hr>
	<a href="add_ojt_records.php" class="button backtodash">Return</a>
  </div>
</body>
</html>
