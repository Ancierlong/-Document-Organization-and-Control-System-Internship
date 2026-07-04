<!DOCTYPE html>
<html>
<head>
  <title>CCS Department Database Management System | Activity Reports </title>
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

    .searchemp-container {
      background-color: #fff;
      border-radius: 4px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
      padding: 20px;
      max-width: 100%;
    }

	.searchemp-container .tophead1 {
	  width: 50%;
	  display: inline-block;
      vertical-align: top;
	  margin-left: 12.5px;
	  margin-right: 12.5px;
	}

	.searchemp-container .tophead2 {
	  width: 40%;
	  display: inline-block;
      vertical-align: top;
	  margin-left: 12.5px;
	  margin-right: 12.5px;
	}
	
    .searchemp-container h2{
      text-align: center;
      margin-bottom: 20px;
    }
	
	.searchemp-container h3{
      text-align: center;
      margin-bottom: 20px;
    }
	
	.searchemp-container h4{
      text-align: center;
      margin-bottom: 10px;
    }
	
	.searchemp-container img {
	  align: center;
      width: 60%; /* Adjust the desired width */
      height: auto; /* Maintain aspect ratio */
    }

    .searchemp-container hr {
      border: none;
      height: 1px;
      background-color: #ddd;
      margin-top: 20px;
      margin-bottom: 20px;
    }

    .searchemp-container table {
      max-width: 100%;
      border-collapse: collapse;
      font-family: sans-serif;
      font-size: 14px;
    }

    .searchemp-container th,
    .searchemp-container td {
      padding: 10px;
      text-align: left;
      border: 1px solid #ddd;
    }

    .searchemp-container th {
      background-color: #E74C3C;
      color: #fff;
    }

    .searchemp-container tr:nth-child(even) {
      background-color: #f2f2f2;
    }

    .searchemp-container tr:hover {
      background-color: #ddd;
    }

    .searchemp-container .button {
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

    .searchemp-container .button:hover {
      background-color: #45a049;
    }

    .searchemp-container .returner {
      background-color: #0074D9; /*#39cccc;*/
    }

    .searchemp-container .returner:hover {
      background-color: #005AA6; /*#2d9999;*/
    }
	
	.searchemp-container .backtodash {
      background-color: #ff851b;
    }

    .searchemp-container .backtodash:hover {
      background-color: #d47716;
    }
	
	.searchemp-divider {
	  width: 30%;
	  display: inline-block;
	  margin-left: 12.5px;
	  margin-right: 12.5px;
	  vertical-align: top;
	}

    td.buttons {
      text-align: center;
    }

    td.buttons button {
      padding: 8px 16px;
      margin-right: 8px;
      border: none;
      border-radius: 4px;
      background-color: #24A534;
      color: #fff;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    
    td.buttons button.medbtn {
      background-color: #FF4136;
    }

    td.buttons button:hover {
      filter: brightness(120%);
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

// Connect to database
$conn = require '../db_connect.php';

session_regenerate_id();

// Get search query
// $query = $_GET['query'];

// Perform search query
$sql = "SELECT `concept_title`, `concept_date`, `concept_description`,`file_name` ,`uploaded_at`  FROM conceptpapers";
$result = $conn->query($sql);
?>

<body>
  <div class="searchemp-container">
    <div class="tophead1">
	<img src="img\perpetual-logo.png" />
	</div>
	<div class="tophead2">
	<h4>CCS Department Captone/Thesis & Document Management System</h4>
	<h2>Concept Papers</h2>
	</div>
	<hr>

<table class="table">
      <thead>
          <tr>
            <th>Title</th>
            <th>Date</th>
            <th>Description</th>
            <th>File</th>
          </tr>
      </thead>
      <tbody>

<?php while ($row = $result->fetch_assoc()): 
     $filePath = "../files_concept_papers/" . $row["file_name"];
?>
  <div>
  <tr>
    <td><?php echo $row['concept_title']; ?></td>
    <td><?php echo $row['concept_date']; ?></td>
    <td><?php echo $row['concept_description']; ?></td>
    <td><?php echo "<p><a href='$filePath' target='_blank'>" . $row["file_name"] . "</a>";?></td>
    <form action="edit_concept_papers.php" method="post">
    <input type="hidden" name="employeeid" value="<?php echo $row['concept_title'];?> ">
    <td class="buttons"><button type="submit" value="view">View Profile</td>
    </form>
  </div>
<?php endwhile; ?>

</tr>
</tbody>
</table>

<?php
// Close database connection
// mysqli_close($db);
?>
    <br><br>
	<div style="width:46%; display:inline-block; margin-left: 15px; margin-right: 15px;">
	<a href="../dashboard.php" class="button returner">Return</a>
	</div>
	<div style="width:46%; display:inline-block; margin-left: 15px; margin-right: 15px;">
	<a href="../dashboard.php" class="button backtodash">Back to Dashboard</a>
	</div>
  </div>
</body>
</html>