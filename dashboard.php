<?php
session_start();
// If the user is not logged in redirect to the login page...
if (!isset($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

?>

<!DOCTYPE html>
<html>
<head>
  <title>CCS Department Database Management System | Dashboard</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #7F1416;
      margin: 0;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
    }

    .dashboard-container {
      background-color: #fff;
      border-radius: 4px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
      padding: 25px;
      width: 90%;
    }

    .dashboard-container h2 {
      text-align: center;
      margin-bottom: 20px;
    }
	
	.dashboard-container h3 {
      text-align: center;
      margin-bottom: 20px;
    }
	
	.dashboard-container h4 {
      text-align: center;
      margin-bottom: 20px;
    }
	
	.dashboard-container hr {
      border: none;
      height: 1px;
      background-color: #ddd;
      margin-top: 20px;
      margin-bottom: 20px;
    }
	
	.dashboard-container img {
	  align: center;
      width: 100%; /* Adjust the desired width */
      height: auto; /* Maintain aspect ratio */
    }

    .dashboard-container .button {
      display: inline-flex;
      justify-content: center;
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

    .total-container{
      width: 100%;
      display: flex;
      flex-direction: row;
    }

    .p-style{
      display: inline-flex;
      justify-content: center;
      text-align: center;
      margin-bottom: 10px;
      padding: 10px;
      background-color: #4CAF50;
      color: #fff;
      text-decoration: none;
      border: none;
      border-radius: 4px;
      transition: background-color 0.3s ease;
      margin: 5px 5px;
      width: 25%;
      font-size: 20px;
    }

    .buttons-container {
      width: 100%;
      display: flex;
      flex-direction: row;
    }
    
    .button{
      display: inline-flex;
      justify-content: center;
      text-align: center;
      margin-bottom: 10px;
      padding: 10px;
      background-color: #4CAF50;
      color: #fff;
      text-decoration: none;
      border: none;
      border-radius: 4px;
      transition: background-color 0.3s ease;
      margin: 5px 5px;
      width: 25%;
      font-size: 22px;
    }
    
    .button2{
      display: inline-flex;
      justify-content: center;
      text-align: center;
      margin-bottom: 10px;
      padding: 10px;
      background-color: #4CAF50;
      color: #fff;
      text-decoration: none;
      border: none;
      border-radius: 4px;
      transition: background-color 0.3s ease;
      margin: 5px 5px;
      width: 33%;
      font-size: 22px;
    }
    .button3{
      display: inline-flex;
      justify-content: center;
      text-align: center;
      margin-bottom: 10px;
      padding: 10px;
      text-decoration: none;
      border: none;
      border-radius: 4px;
      margin: 5px 5px;
      width: 33%;
      font-size: 22px;
    }
    
    .dashboard-container .button:hover, .button2:hover {
      background-color: #3d8b40;
    }

    .dashboard-container .logout {
      background-color: #FF4136;
    }

    .dashboard-container .logout:hover {
      background-color: #C12C24;
    }

    .dashboard-container .login-button {
      width: 100%;
      font-size: 17px;
      padding: 10px;
      background-color: #FF4136;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .dashboard-container .login-button:hover {
      background-color: #DB362D;
    }

    .icon {
      max-width: 75px;
      max-height: 75px;
      margin: 0 5px 0 0;
    }
    .logos {
        display: flex;
        flex-direction: row;
        }
  </style>
  <script>
    function confirmLogout() {
      var confirmation = confirm("Are you sure you want to logout?");
      if (confirmation) {
        window.location.href = "logout.php";
      }
    }
  </script>
</head>
<?php
require 'db_connect.php';

$role = $_SESSION['role'];
$display = $_SESSION['username'];

$sql1 = "SELECT COUNT(*) AS total1 FROM thesiscapstoneprojects where archive = 0";
$totalResult1 = $conn->query($sql1);
$totalRow1 = $totalResult1->fetch_assoc();
$totalRecords1 = $totalRow1['total1'];

$sql2 = "SELECT COUNT(*) AS total2 FROM conceptpapers where archive = 0";
$totalResult2 = $conn->query($sql2);
$totalRow2 = $totalResult2->fetch_assoc();
$totalRecords2 = $totalRow2['total2'];

$sql3 = "SELECT COUNT(*) AS total3 FROM activityreports where archive = 0";
$totalResult3 = $conn->query($sql3);
$totalRow3 = $totalResult3->fetch_assoc();
$totalRecords3 = $totalRow3['total3'];

$sql4 = "SELECT COUNT(*) AS total4 FROM ojtrecords where archive = 0";
$totalResult4 = $conn->query($sql4);
$totalRow4 = $totalResult4->fetch_assoc();
$totalRecords4 = $totalRow4['total4'];

$sql5 = "SELECT COUNT(*) AS total5 FROM company_linkages where archive = 0";
$totalResult5 = $conn->query($sql5);
$totalRow5 = $totalResult5->fetch_assoc();
$totalRecords5 = $totalRow5['total5'];


?>
<body>
  <div class="dashboard-container">
    <div style="display: flex; flex-direction: row;">
    <div style="width: 100%; margin-left:12.5px; margin-right: 12.5px;">
    <img src="logos/ccs.png" style="width: 150px;height: auto;">
    </div>
    <div style="width: 5000%; margin-left:12.5px; margin-right: 12.5px;">
    <h2>UPHSD MOLINO</h2>
    <h2>Welcome <?php echo $_SESSION['username']?>!</h2>
    <h3><!--CCS Document Management System-->
        CCS-DOCS
        <br><br>
        College of Computer Studies' Document Organization and Control System</h3>
    </div>
    <div style="width: 100%; margin-left:12.5px; margin-right: 12.5px;">
    <img src="logos/UPHSD.png" style="width: 150px; height: auto;">
    </div>
    </div>
	<hr>
    <!--
    <a href="searchemployee.php" class="button" >Search Capstone/Thesis OG</a>
    <a href="viewemployeelogs.php" class="button" >View Capstone/Thesis OG </a>
    -->
<!--    
  <div class="total-container">
    <p class="p-style"><b><img src="open-book.png" alt="icon" class="icon"><br>Capstone/Thesis Total: <br><?php echo $totalRecords1?></b></p>
    <p class="p-style"><b><img src="concept.png" alt="icon" class="icon"><br>Concept Papers Total: <br><?php echo $totalRecords2?></p>
    <p class="p-style"><b><img src="checklist.png" alt="icon" class="icon"><br>Activity Reports Total: <br><?php echo $totalRecords3?></b></p>
    <p class="p-style"><b><img src="open-book.png" alt="icon" class="icon"><br>Capstone/Thesis Total: <br><?php echo $totalRecords4?></b></p>
  </div>
-->
  <div class="buttons-container">
    <a href="capstone_thesis\view_capstone_thesis_test.php" class="button" > <img src="open-book (1).png" alt="icon" class="icon"><b>Research Papers<br>Total: <?php echo $totalRecords1?></b></a>
    <a href="concept_papers\view_concept_papers_test.php" class="button" > <img src="concept.png" alt="icon" class="icon"><b>Concept Papers<br>Total: <?php echo $totalRecords2?></b></a>
    <a href="activity_reports\view_activity_reports_test.php" class="button"> <img src="checklist.png" alt="icon" class="icon"><b>Activity Reports<br>Total: <?php echo $totalRecords3?></b></a>
    <a href="ojt_records\view_ojt_records.php" class="button"> <img src="headhunting.png" alt="icon" class="icon"><b>OJT Records<br>Total: <?php echo $totalRecords4?></b></a>
    <a href="ojt_records\view_ojt_companies.php" class="button"> <img src="location.png" alt="icon" class="icon"><b>Company / Linkages<br>Total: <?php echo $totalRecords5?></b></a>
  </div>
  <?php
    if ($role !== "Council" && $role !== "Faculty") {
    ?>
    <div class="buttons-container">
    <a href="logs\view_logs_capstone_thesis.php" class="button"><b>Logs Research Papers</b></a>
    <a href="logs\view_logs_concept_papers.php" class="button" > <b>Logs Concept Papers</b></a>
    <a href="logs\view_logs_activity_reports.php" class="button"> <b>Logs Activity Reports</b></a>
    <a href="logs\view_logs_ojt_records.php" class="button"> <b>Logs OJT Records</b></a>
    <a href="logs\view_logs_company_linkages.php" class="button"> <b>Logs Company / Linkages</b></a>
  </div>
  <?Php
    }
    ?>
    <hr>
  <div class="buttons-container">
    <?php
    if ($role === "Council") {
      
    ?>
    <button class="button3" disabled href="">System Settings</button>
    <button class="button3" disabled href="">User Accounts</button>
    <a href="ccsfunds\funds.php" class="button2" >CCS Funds</a> 
    <a class="button2" href="users\my_profile2.php">My Profile</a>
    
    <?Php
    } else if ($role === "Faculty") {
      
      ?>
    <a href="system_config\system_settings.php" class="button2" >System Settings</a>
    <a href="users\view_user_list.php" class="button2" >User Accounts</a>
    <a href="ccsfunds\funds.php" class="button2" >CCS Funds</a>
    <a href="users\my_profile2.php" class="button2" >My Profile</a>
      
    <?Php
    } else  {
    ?>
    <a href="system_config\system_settings.php" class="button2" >System Settings</a>
    <a href="users\view_user_list.php" class="button2" >User Accounts</a>
    <a href="logs\view_logs_user_action.php" class="button2" >User Logs</a>
    <a href="ccsfunds\funds.php" class="button2" >CCS Funds</a>
    <a href="users\my_profile2.php" class="button2" >My Profile</a>
    <?Php 
    }
    ?>

    
    <hr>

  </div>  
    <hr>
    <input class="login-button" type="button" value="Logout" onclick="confirmLogout()">
    <!--a href="logout.php" class="button logout">Logout</a-->
  </div>
  <?php 
  ?>
</body>
</html>
