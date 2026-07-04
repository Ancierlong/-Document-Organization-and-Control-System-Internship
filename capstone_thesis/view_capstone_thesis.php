<!DOCTYPE html>
<html>
<head>
  <title>UPHSD Molino HRD Training Monitoring System | Employee Search </title>
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
      width: 100%;
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
      width: 100%;
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
$sql = "SELECT `projecttype`, `projecttitle`, `projectdescription`, `projectcategory`, `projectyear`, `projectproponents`, `projectrecommendation` FROM `thesiscapstoneprojects`";
$result = $conn->query($sql);
?>

<body>
  <div class="searchemp-container">
    <div class="tophead1">
	<img src="img\perpetual-logo.png" />
	</div>
	<div class="tophead2">
	<h4>CCS-DOCS</h4>
	<h2>Capstone/Thesis Search</h2>
	</div>
	<hr>

<table class="table">
      <thead>
          <tr>
            <th data-order="ASC">Project Type</th>
            <th>Title</th>
            <th>Description</th>
            <th>Category</th>
            <th>Year</th>
            <th>Proponents</th>
            <th>Recommendations</th>
          </tr>
      </thead>
      <tbody>

<?php while ($row = $result->fetch_assoc()): ?>
  <div>
  <tr>
    <td><?php echo $row['projecttype']; ?></td>
    <td><?php echo $row['projecttitle']; ?></td>
    <td><?php echo $row['projectdescription']; ?></td>
    <td><?php echo $row['projectcategory']?></td>
    <td><?php echo $row['projectyear']?></td>
    <td><?php echo $row['projectproponents']?></td>
    <td><?php echo $row['projectrecommendation']?></td>
    <form action="employeekeep.php" method="post">
    <input type="hidden" name="employeeid" value="<?php echo $row['projecttitle'];?> ">
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