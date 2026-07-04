<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}

// Get database connection
$conn = require '../db_connect.php';

$userid = $_SESSION['user_id'];

// Initialize message variable
$updateMessage = "";

// Ensure `$id` is retrieved correctly
$id = $_POST['id'] ?? null; 

if (!$id) {
    die("Error: No ID provided.");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
  $ojt_full_name = $_POST['ojt_full_name'];
  $ojt_email = $_POST['ojt_email'];
  $ojt_telno = $_POST['ojt_telno'];
  $ojt_address = $_POST['ojt_address'];
  $ojt_description = $_POST['ojt_description'];
  $ojt_company = $_POST['ojt_company'];
  $uploadedat = $_POST['uploadedat'];
  $id = $_POST['id']; // The ID of the record being updated

  // Update OJT Record
  $sql_update = "UPDATE `ojtrecords` SET 
      `ojt_full_name` = ?, 
      `ojt_email` = ?, 
      `ojt_telno` = ?, 
      `ojt_address` = ?, 
      `ojt_description` = ?, 
      `ojt_company` = ? 
      WHERE `id` = ?";

  $stmt = $conn->prepare($sql_update);
  $stmt->bind_param("ssssssi", $ojt_full_name, $ojt_email, $ojt_telno, $ojt_address, $ojt_description, $ojt_company, $id);
  
  if ($stmt->execute()) {
      // Insert log entry after a successful update
      $sql_log = "INSERT INTO logs_ojt_records (`type`, `modified_item`, `user`) 
                  VALUES ('Modify', ?, ?)";

      $stmt_log = $conn->prepare($sql_log);
      $stmt_log->bind_param("si", $uploadedat, $userid);
      $stmt_log->execute();
      $stmt_log->close();

      $updateMessage = "<h2 style='color: green; text-align: center;'>OJT Record Updated!</h2>";
  } else {
      $updateMessage = "<h2 style='color: red; text-align: center;'>Error updating record: " . $stmt->error . "</h2>";
  }
  $stmt->close();
}


// Retrieve OJT Record
$sql2 = "SELECT * FROM `ojtrecords` WHERE `id` = ?";
$stmt = $conn->prepare($sql2);
$stmt->bind_param("i", $id);
$stmt->execute();
$result2 = $stmt->get_result();

if ($row2 = $result2->fetch_assoc()) {
    $ojt_full_name = htmlspecialchars($row2['ojt_full_name'], ENT_QUOTES, 'UTF-8');
    $ojt_company = htmlspecialchars($row2['ojt_company'], ENT_QUOTES, 'UTF-8');
    $ojt_email = htmlspecialchars($row2['ojt_email'], ENT_QUOTES, 'UTF-8');
    $ojt_telno = htmlspecialchars($row2['ojt_telno'], ENT_QUOTES, 'UTF-8');
    $ojt_address = htmlspecialchars($row2['ojt_address'], ENT_QUOTES, 'UTF-8');
    $ojt_description = htmlspecialchars($row2['ojt_description'], ENT_QUOTES, 'UTF-8');
    $uploadedat = htmlspecialchars($row2['uploaded_at'], ENT_QUOTES, 'UTF-8');
} else {
    $updateMessage = "<p style='color: red; text-align: center;'>No record found.</p>";
}
$stmt->close();

// Fetch active companies from company_linkages (where archive = 0)
$companies = [];
$sql_companies = "SELECT `company_name` FROM `company_linkages` WHERE `archive` = 0";
$result_companies = $conn->query($sql_companies);

while ($row = $result_companies->fetch_assoc()) {
    $companies[] = $row['company_name'];
}

// Ensure the current OJT company is included in the list if it's not in company_linkages
if (!in_array($ojt_company, $companies) && !empty($ojt_company)) {
    array_unshift($companies, $ojt_company);
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>CCS Department Database Management System | Edit OJT Record</title>
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
<body>
<div class="addemp-container">
<h4>CCS-DOCS</h4>
    <h2>Edit OJT Record</h2>
    <?= $updateMessage ?>
    <hr>
    <form method="post" action="edit_ojt_records.php?id=<?= $id ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
      <div style="display:flex; flex-direction:row;">
      <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="ojt_full_name">Name:</label>
        <input type="text" id="ojt_full_name" name="ojt_full_name" value="<?= $ojt_full_name ?>" required>
        </div>
      <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="ojt_email">Email:</label>
        <input type="email" id="ojt_email" name="ojt_email" value="<?= $ojt_email ?>" required>
        </div>
      </div>
      <div style="display:flex; flex-direction:row;">
      <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="ojt_telno">Tel No.:</label>
        <input type="text" id="ojt_telno" name="ojt_telno" value="<?= $ojt_telno ?>" required>
        </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="ojt_address">Address:</label>
        <input type="text" id="ojt_address" name="ojt_address" value="<?= $ojt_address ?>" required>
        </div>
      </div>
        <label for="ojt_description">OJT Description:</label>
        <textarea id="ojt_description" class="textarea" name="ojt_description" required><?= $ojt_description ?></textarea>
        <div style="display:flex; flex-direction:row;">
      <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="ojt_company">Company:</label>
        <select id="ojt_company" name="ojt_company" required>
            <option value="" disabled>-- Select a company --</option>
            <?php foreach ($companies as $company): ?>
                <option value="<?= htmlspecialchars($company) ?>" <?= ($company === $ojt_company) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($company) ?>
                </option>
            <?php endforeach; ?>
        </select>
          </div>
            </div>
        <br><br>
        <input type="hidden" id="uploadedat" name="uploadedat" value="<?= $uploadedat ?>" required>
        <input type="submit" name="submit" value="Update Record">
    </form>
    <hr>
    <a href="view_ojt_records.php" class="button backtodash">Back to Dashboard</a>
</div>
</body>
</html>
