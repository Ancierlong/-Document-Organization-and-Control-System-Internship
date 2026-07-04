<!DOCTYPE html>
<html>
<head>
  <title>CCS Department Document Management System</title>
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
    .addemp-container input[type="url"],
    .addemp-container input[type="email"],
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

	if (!isset($_SESSION['username'])) {
    header('Location: ../dashboard.php');
  }
  exit;
}

$conn = require '../db_connect.php';
$display ="";

$userid = $_SESSION['user_id'];

// Check if the user has submitted the form
if (isset($_POST['submit'])) {
  $companyname = mysqli_real_escape_string($conn, $_POST['company_name']);
  $companydescription = mysqli_real_escape_string($conn, $_POST['company_description']);
  $companywebsite = mysqli_real_escape_string($conn, $_POST['company_website']);
  $companytelno = mysqli_real_escape_string($conn, $_POST['company_telno']);
  $companyemail = mysqli_real_escape_string($conn, $_POST['company_email']);
  $comanyaddress = mysqli_real_escape_string($conn, $_POST['company_address']);
  $contactperson = mysqli_real_escape_string($conn, $_POST['contact_person']);
  $contactpersonemail = mysqli_real_escape_string($conn, $_POST['contact_person_email']);
  $contactpersontelno = mysqli_real_escape_string($conn, $_POST['contact_person_telno']);
  $id = mysqli_real_escape_string($conn, $_POST['id']);
  $uploadedat = mysqli_real_escape_string($conn, $_POST['uploadedat']);

  if (empty($_POST["file_name"])) {
  // Define update query
  $sql = "UPDATE `company_linkages` SET 
      `company_name`='$companyname',
      `company_description`='$companydescription',
      `company_website`='$companywebsite',
      `company_telno`='$companytelno',
      `company_email`='$companyemail',
      `company_address`='$comanyaddress',
      `contact_person`='$contactperson',
      `contact_person_email`='$contactpersonemail',
      `contact_person_telno`='$contactpersontelno' 
      WHERE `id` = '$id'";

  if (mysqli_query($conn, $sql)) {
      // Insert log entry after successful update
      $log_sql = "INSERT INTO logs_company_linkages (`type`, `modified_item`, `user`) 
                  VALUES ('Modify', '$uploadedat', '$userid')";
      
      mysqli_query($conn, $log_sql); // Execute logging query
      
      $display = "<h2 style='color:green;'>Company/Linkage Updated!</h2>";
  } else {
      echo "Error: " . mysqli_error($conn);
  } 
}
    else{
    // Insert the values into the database
    $sql = "UPDATE `company_linkages` SET `company_name`='$companyname',`company_description`=' $companydescription',
    `company_website`=' $companywebsite',`company_telno`='$companytelno', `company_email`=' $contactpersonemail', `company_address`='$comanyaddress', 
    `contact_person`='$contactperson ',`contact_person_email`='$contactpersonemail',`contact_person_telno`='$contactpersontelno' WHERE `id` = '$id'";

if (mysqli_query($conn, $sql)) {
  // Insert log entry after successful update
  $log_sql = "INSERT INTO logs_company_linkages (`type`, `modified_item`, `user`) 
              VALUES ('Modify Company/Linkages', '$uploadedat', '$userid')";
  
  mysqli_query($conn, $log_sql); // Execute logging query
  
  $display = "<h2 style='color:green;'>Company/Linkage Updated!</h2>";
} else {
  echo "Error: " . mysqli_error($conn);
} 
}
}

// retrieving daa from previous php file
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $id = htmlspecialchars($_POST["id"]);
}
// find the value of $id in sql
  $sql2 = "SELECT `id`, `company_name`, `company_description`, `company_website`, `company_telno`, 
  `company_email`, `company_address`, `contact_person`, `contact_person_email`, `contact_person_telno`
  ,`uploaded_at` FROM `company_linkages` WHERE `id` = $id";
  
  $result2 = $conn->query($sql2);

  if ($row2 = $result2->fetch_assoc()) {
//convert into string for value
    $id = $row2['id'];
    $company_name = $row2['company_name'];
    $company_description = $row2['company_description'];
    $company_website = $row2['company_website'];
    $company_telno = $row2['company_telno'];
    $company_email = $row2['company_email'];
    $company_address = $row2['company_address'];
    $contact_person = $row2['contact_person'];
    $contact_person_email = $row2['contact_person_email'];
    $contact_person_telno = $row2['contact_person_telno'];
    $uploadedat = $row2['uploaded_at'];

    //var_dump($concept_type);

  }
/////sankjdshakdhaskdahksadsdas

// Close the database connection
// mysqli_close($conn);

?>


<body>
  <div class="addemp-container">
	<h4>CCS-DOCS</h4>
	<h2>Edit Company / Linkage</h2>
    <?php
    echo $display;
    ?>
	<hr>
    <form method="post" action="edit_ojt_company.php">
        <div style="display:flex; flex-direction:row;">
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
            <label for="company_name">Company Name:</label>
            <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8');?>" required>
        </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
            <label for="company_website">Company Website:</label>
            <input type="text" id="company_website" name="company_website" value="<?php echo htmlspecialchars($company_website, ENT_QUOTES, 'UTF-8');?>" required>
        </div>
        </div>
        <div style="display:flex; flex-direction:row;">
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
            <label for="company_telno">Company Tel No.:</label>
            <input type="text" id="company_telno" name="company_telno" value="<?php echo htmlspecialchars($company_telno, ENT_QUOTES, 'UTF-8');?>" required>
        </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
            <label for="company_email">Company Email:</label>
            <input type="email" id="company_email" name="company_email" value="<?php echo htmlspecialchars($company_email, ENT_QUOTES, 'UTF-8');?>" required>
        </div>
        </div>
        <div style="display:flex; flex-direction:row;">
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
            <label for="company_address">Company Address:</label>
            <input type="text" id="company_address" name="company_address" value="<?php echo htmlspecialchars($company_address, ENT_QUOTES, 'UTF-8');?>" required>
        </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
            <label for="contact_person">Contact Person:</label>
            <input type="text" id="contact_person" name="contact_person" value="<?php echo htmlspecialchars($contact_person, ENT_QUOTES, 'UTF-8');?>" required>
        </div>
        </div>
        <div style="display:flex; flex-direction:row;">
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
            <label for="contact_person_email">Contact Person Email:</label>
            <input type="email" id="contact_person_email" name="contact_person_email" value="<?php echo htmlspecialchars($contact_person_email, ENT_QUOTES, 'UTF-8');?>" required>
        </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
            <label for="contact_person_telno">Contact Person Tel no.:</label>
            <input type="text" id="contact_person_telno" name="contact_person_telno" value="<?php echo htmlspecialchars($contact_person_telno, ENT_QUOTES, 'UTF-8');?>" required>
        </div>
        </div>
        <!--
        <div style="width:45%; display:inline-block; margin-left: 12.5px; margin-right: 12.5px;">
            <label for="file">File:</label>
            <input type="file" id="file_name" name="file_name">
		</div>
        -->
        <div style="">
            <label for="company_description">Description:</label>
            <textarea id="company_description" class="textarea" name="company_description" required><?php echo htmlspecialchars($company_description, ENT_QUOTES, 'UTF-8');?></textarea>
            <br>
		</div>
		<div style="">
        <input type='hidden' id="id" name='id' value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
        <input type='hidden' id="uploadedat" name='uploadedat' value="<?php echo htmlspecialchars($uploadedat, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="submit" name="submit" value="Update Record" onclick="uploadFile()"></div>
    </form>
<!-- file upload test 
    <form action="conceptpaperfileupload.php" method="post" enctype="multipart/form-data">
    <input type="file" name="file">
    <button type="submit">Upload</button>
    -->
    
<!-- -->

<script>
function uploadFile() {
//
//
    let file = document.getElementById("file_name").files[0];
    if (!file) {
        document.getElementById("status").innerText = "No file selected!";
        return;
    }

    let formData = new FormData();
    formData.append("file", file);

    fetch("concept_paper_file_upload.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(result => document.getElementById("status").innerText = result)
    .catch(error => document.getElementById("status").innerText = "Upload failed.");
}
</script>


</form>
	<hr>
  <?php 
  
  ?>
	<a href="view_ojt_companies.php" class="button backtodash">Back to List</a>
  </div>
</body>
</html>