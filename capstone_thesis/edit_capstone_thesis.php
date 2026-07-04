<!DOCTYPE html>
<html>
<head>
  <title>CCS Department Document Management System | Edit Capstone/Thesis</title>
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
    .addemp-container input[type="number"],
    .addemp-container input[type="file"],
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
if (!isset($_SESSION['user_id'])) {
  header('Location: ../index.php');
  exit;
}

$conn = require '../db_connect.php';
$display ="";
$userid = $_SESSION['user_id'];

// Check if the user has submitted the form
if (isset($_POST['submit'])) {
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $proponents = mysqli_real_escape_string($conn, $_POST['proponents']);
    $recommendation = mysqli_real_escape_string($conn, $_POST['recommendation']);
    $filename = mysqli_real_escape_string($conn, $_POST['file_name']);
    $uploadedat = mysqli_real_escape_string($conn, $_POST['uploadedat']);
    $id = mysqli_real_escape_string($conn, $_POST['id']);

    // there is no file uploaded   
    if (empty($_POST["file_name"])) {
      
      $sql = "UPDATE `thesiscapstoneprojects` SET `projecttype`='$type', `projecttitle`='$title', `projectdescription`='$description',`projectcategory`='$category', `projectyear`='$year',`projectproponents`='$proponents',`projectrecommendation`='$recommendation' WHERE `id`= $id;";

      $sql .= "INSERT INTO logs_thesis_capstone_projects (`type`, `modified_item`, `user`) 
         VALUES ('Modify', '$uploadedat' , $userid);";

      if (mysqli_multi_query($conn, $sql)) {
      $display = "<h2 style='color:green;'>Activity Report Updated!</h2>";
      }   else {
      echo "Error: " . mysqli_error($conn);
      } 
    }  
    else{
    // Insert the values into the database
    $sql = "UPDATE `thesiscapstoneprojects` SET `projecttype`='$type', `projecttitle`='$title', `projectdescription`='$description',`projectcategory`='$category', `projectyear`='$year',`projectproponents`='$proponents',`projectrecommendation`='$recommendation', `file_name` = '$filename' WHERE `id`= $id;";
    
    $sql .= "INSERT INTO logs_thesis_capstone_projects (`type`, `modified_item`, `user`) 
         VALUES ('Modify', '$uploadedat', $userid);";
 
    if (mysqli_multi_query($conn, $sql)) {
        $display = "<h2 style='color:green;'>Capstone/Thesis Updated!</h2>";
    } else {
        echo "Error: " . mysqli_error($conn);
    } 
}
mysqli_close($conn);
}

// retrieving daa from previous php file
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $id = $_POST['id'];
  $conn = require '../db_connect.php';
}
// find the value of $id in sql
  $sql2 = "SELECT `id`, `projecttype`, `projecttitle`, `projectdescription`, `projectcategory`, `projectyear`, `projectproponents`, `projectrecommendation`, `uploaded_at`, `file_name` FROM `thesiscapstoneprojects` WHERE `id` = $id;";
  
  $result2 = $conn->query($sql2);

  while ($row2 = $result2->fetch_assoc()): 
//convert into string for value
    $id = $row2['id'];
    $projecttype = $row2['projecttype'];
    $projecttitle = $row2['projecttitle'];
    $projectdescription = $row2['projectdescription'];
    $projectcategory = $row2['projectcategory'];
    $projectyear = $row2['projectyear'];
    $projectproponents = $row2['projectproponents'];
    $projectrecommendation = $row2['projectrecommendation'];
    $uploadedat = $row2['uploaded_at'];
    $file_name = $row2['file_name'];



/////sankjdshakdhaskdahksadsdas

// Close the database connection
// mysqli_close($conn);
?>


<body>
  <div class="addemp-container">
	<h4>CCS-DOCS</h4>
	<h2>Edit Capstone/Thesis</h2>
    <?php
    echo $display;
    ?>
	<hr>
    <form method="post" action="edit_capstone_thesis.php">
    <div style="display:flex; flex-direction:row;">
    <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="type">Type:</label>
        <input type="text" id="type" name="type" value="<?php echo htmlspecialchars($projecttype, ENT_QUOTES, 'UTF-8'); ?>" required>
        <br>
    </div>
    <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="title">Title:</label>
        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($projecttitle, ENT_QUOTES, 'UTF-8'); ?>" required>
        <br>
    </div>
</div>
<div style="display:flex; flex-direction:row;">
    <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="proponents">Proponents:</label>
        <input type="text" id="proponents" name="proponents" value="<?php echo htmlspecialchars($projectproponents, ENT_QUOTES, 'UTF-8'); ?>" required>
        <br>
    </div>
    <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="category">Category:</label>
        <select id="category" name="category" required>
            <option value="Client Based" <?php echo ($projectcategory == "Client Based") ? "selected" : ""; ?>>Client Based</option>
            <option value="Start Up" <?php echo ($projectcategory == "Start Up") ? "selected" : ""; ?>>Start Up</option>
        </select>
        <br>
    </div>
</div>
    <div style="display:flex; flex-direction:row;">
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">

            <label for="year">Date(Year):</label>
            <input type="number" id="year" name="year" required placeholder="YYYY" min="1900" max="2100" value="<?php echo htmlspecialchars($projectyear, ENT_QUOTES, 'UTF-8'); ?>">
            <br>
        </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
		        <label for="type">File:</label>
            <input type="file" id="file_name" name="file_name">
            <?php
$folder_path = "../files_capstone_thesis/"; // Adjust the path as needed

if (!empty($file_name)) {
    $file_path = $folder_path . $file_name;

    if (file_exists($file_path)) {
      echo "Current File: <a href='../files_capstone_thesis/". htmlspecialchars($file_name) ."'>" . htmlspecialchars($file_name) . "</a> (Leave empty to keep the current file)";
    } else {
        echo "<p class='error'>No Current file.</p>";
    }
} else {
    echo "<p>No file selected.</p>";
}
?>
		    </div>
  </div>
        <div style="">
            <label for="recommendation">Recommendation:</label>
            <textarea id="recommendation" class="textarea" name="recommendation" required><?php echo htmlspecialchars($projectrecommendation, ENT_QUOTES, 'UTF-8');?></textarea>
            <br>
        </div>
        <div style="">
            <label for="description">Description:</label>
            <textarea id="description" class="textarea" name="description" required><?php echo htmlspecialchars($projectdescription, ENT_QUOTES, 'UTF-8');?></textarea>
            <br>
		</div>
		<div style="">
            <input type='hidden' id="uploadedat" name='uploadedat' value="<?php echo htmlspecialchars($uploadedat, ENT_QUOTES, 'UTF-8'); ?>">
            <input type='hidden' id="id" name='id' value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="submit" name="submit" value="Update Record" onclick="uploadFile()">
		</div>


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

    fetch("capstone_thesis_file_upload.php", {
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
  endwhile;
  ?>
	<a href="view_capstone_thesis_test.php" class="button backtodash">Back to list</a>
  </div>
</body>
</html>