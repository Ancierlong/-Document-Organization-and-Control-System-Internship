<!DOCTYPE html>
<html>
<head>
<title>CCS Department Database Management System | Add Concept Papers</title>
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

  #otherTypeContainer {
   display: none; /* Initially hidden */
   margin-bottom: 10px;
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

// Check if the user has submitted the form
if (isset($_POST['submit'])) {
  $concept_title = mysqli_real_escape_string($conn, $_POST['concept_title']);
  $concept_date = mysqli_real_escape_string($conn, $_POST['concept_date']);
  $concept_description = mysqli_real_escape_string($conn, $_POST['concept_description']);
  $academic_year = mysqli_real_escape_string($conn, $_POST['academic_year']);
  $concept_type = mysqli_real_escape_string($conn, $_POST['concept_type']);
  $concept_resource_speaker = mysqli_real_escape_string($conn, $_POST['concept_resource_speaker']);
  $concept_evaluation_rating = mysqli_real_escape_string($conn, $_POST['concept_evaluation_rating']);
  $concept_other_type = isset($_POST['concept_other_type']) ? mysqli_real_escape_string($conn, $_POST['concept_other_type']) : '';
  date_default_timezone_set("Asia/Manila");
  $datetime = date("Y-m-d H:i:s");
  $userid = $_SESSION['user_id'];

  // Determine the final concept type
  $final_concept_type = $concept_type === 'Others' ? $concept_other_type : $concept_type;

  // File Upload Handling
  $targetDir = "../files_concept_papers/"; // Folder where files will be stored

  // Check if the folder exists; if not, create it
  if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
  }

  $uploadedFileName = '';
  if (isset($_FILES["file_name"]) && $_FILES["file_name"]["error"] == 0) {
    $fileName = basename($_FILES["file_name"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    $allowedTypes = ["jpg", "png", "gif", "pdf", "txt", "docx", "jfif", "webp", "heif", "bmp"];

    if (in_array($fileType, $allowedTypes)) {
      if (move_uploaded_file($_FILES["file_name"]["tmp_name"], $targetFilePath)) {
        $uploadedFileName = $fileName; // Store the uploaded file name
        $display = "<h2 style='color:green;'>Concept Paper Added!</h2>";
      } else {
        $display = "<h2 style='color:red;'>Error uploading file. Concept Paper Details Added (without file).</h2>";
      }
    } else {
      $display = "<h2 style='color:red;'>Invalid file type. Concept Paper Details Added (without file). Allowed types: jpg, png, gif, pdf, txt, docx, jfif, webp, heif, bmp</h2>";
    }
  } else if (isset($_FILES["file_name"]) && $_FILES["file_name"]["error"] != 4) {
    $display = "<h2 style='color:red;'>Error uploading file. Concept Paper Details Added (without file). Error Code: " . $_FILES["file_name"]["error"] . "</h2>";
  } else {
    $display = "<h2 style='color:green;'>Concept Paper Added!</h2>";
  }

  // Insert the values into the database
  $sql = "INSERT INTO conceptpapers (concept_title, concept_date, concept_description, file_name, academic_year, concept_type, concept_resource_speaker, concept_evaluation_rating, uploaded_at)
  VALUES ('$concept_title', '$concept_date', '$concept_description' , '$uploadedFileName', '$academic_year', '$final_concept_type', '$concept_resource_speaker', '$concept_evaluation_rating', '$datetime');";

  $sql .= "INSERT INTO logs_concept_papers (`type`, `modify_id`, `user`)
     VALUES ('Add', '$datetime', $userid);";

  if (mysqli_multi_query($conn, $sql)) {
    if (strpos($display, 'Error') === false && strpos($display, 'orange') === false) {
      // If no specific file upload error was set, use the success message
      if (empty($display) || $display === "<h2 style='color:orange;'>Concept Paper Details Added (without file).</h2>") {
        $display = "<h2 style='color:green;'>Concept Paper Added Successfully!</h2>";
      }
    }
  } else {
    echo "Error: " . mysqli_error($conn);
  }
}

?>


<body>
 <div class="addemp-container">
 <h4>CCS-DOCS</h4>
 <h2>Add Concept Papers</h2>
  <?php
  echo $display;
  ?>
 <hr>
  <form method="post" action="add_concept_papers.php" enctype="multipart/form-data">
  <div style="display:flex; flex-direction:row;">
  <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="concept_title">Title:</label>
        <input type="text" id="concept_title" name="concept_title" required>
      </div>
      <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="concept_type">Type:</label>
        <select list="preset" id="concept_type" name="concept_type" required onchange="toggleOtherType()">
          <option value="COP/CES">COP/CES</option>
          <option value="Enrichment">Enrichment</option>
          <option value="Others">Others</option>
        </select>
      </div>
  </div>
  <div id="otherTypeContainer">
  <div style="display:flex; flex-direction:row;">
  <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
  </div>
  <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
    <label for="concept_other_type">Specify Other Type:</label>
    <input type="text" id="concept_other_type" name="concept_other_type">
  </div>
  </div>
  </div>
  <div style="display:flex; flex-direction:row;">
  <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="academic_year">Academic Year:</label>
        <input type="text" id="academic_year" name="academic_year" required>
      </div>
      <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="concept_date">Date Accomplished:</label>
        <input type="date" id="concept_date" name="concept_date" required>
      </div>
  </div>
  <div style="display:flex; flex-direction:row;">
  <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="concept_resource_speaker">Resource Speaker:</label>
        <input type="text" id="concept_resource_speaker" name="concept_resource_speaker" required>
      </div>
      <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="concept_evaluation_rating">Evaluation Rating:</label>
        <input type="text" id="concept_evaluation_rating" name="concept_evaluation_rating" required>
      </div>
  </div>
  <div style="display:flex; flex-direction:row;">
  <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="file_name">File:</label>
        <input type="file" id="file_name" name="file_name">
      </div>
      <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
      </div>
  </div>
  <div>
        <label for="concept_description">Description:</label>
        <textarea id="concept_description" class="textarea" name="concept_description" required></textarea>
        <br>
  </div>
  <div style="">
        <input type="submit" name="submit" value="Submit New Record">
  </div>
  </form>
<script>
function toggleOtherType() {
 const conceptType = document.getElementById("concept_type");
 const otherTypeContainer = document.getElementById("otherTypeContainer");
 if (conceptType.value === "Others") {
  otherTypeContainer.style.display = "block";
  document.getElementById("concept_other_type").setAttribute("required", "");
 } else {
  otherTypeContainer.style.display = "none";
  document.getElementById("concept_other_type").removeAttribute("required");
  document.getElementById("concept_other_type").value = ""; // Clear the value when "Others" is not selected
 }
}

// Call toggleOtherType on page load to handle cases where "Others" might be pre-selected (though unlikely with current defaults)
document.addEventListener('DOMContentLoaded', toggleOtherType);
</script>

 <hr>
 <a href="view_concept_papers_test.php" class="button backtodash">Back to Dashboard</a>
 </div>
</body>
</html>