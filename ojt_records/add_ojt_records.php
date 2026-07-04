<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}

$conn = require '../db_connect.php'; // Keep the original database connection

$display = "";
$error_message = ""; // Variable to store the specific error message

// Fetch unique company names from the database
$query = "SELECT DISTINCT company_name FROM company_linkages WHERE company_name IS NOT NULL AND company_name <> ''AND archive = 0 ORDER BY company_name ASC";
$result = mysqli_query($conn, $query);

$companies = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $companies[] = $row['company_name'];
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if there are no companies available AND the submitted ojt_company is empty
    if (empty($companies) && empty($_POST['ojt_company'])) {
        $error_message = "<h2 style='color:red;'>Error: No company selected. Please add a company first.</h2>";
    } else {
        // Proceed with database insertion only if there are companies or a company was selected
        $ojt_full_name = mysqli_real_escape_string($conn, $_POST['ojt_full_name']);
        $ojt_company = mysqli_real_escape_string($conn, $_POST['ojt_company']);
        $ojt_email = mysqli_real_escape_string($conn, $_POST['ojt_email']);
        $ojt_address = mysqli_real_escape_string($conn, $_POST['ojt_address']);
        $ojt_telno = mysqli_real_escape_string($conn, $_POST['ojt_telno']);
        $ojt_description = mysqli_real_escape_string($conn, $_POST['ojt_description']);
        date_default_timezone_set("Asia/Manila");
        $datetime = date("Y-m-d H:i:s");
        $userid = $_SESSION['user_id'];

        $sql = "INSERT INTO ojtrecords (ojt_full_name, ojt_company, ojt_email, ojt_address, ojt_description, ojt_telno, uploaded_at)
                VALUES ('$ojt_full_name', '$ojt_company', '$ojt_email', '$ojt_address', '$ojt_description', '$ojt_telno', '$datetime');";

        $sql .= "INSERT INTO logs_ojt_records (`type`, `modified_item`, `user`)
        VALUES ('Add', '$datetime', $userid);";

        if (mysqli_multi_query($conn, $sql)) {
            $display = "<h2 style='color:green;'>OJT Record Added!</h2>";
        } else {
            $display = "<h2 style='color:red;'>Error: " . mysqli_error($conn) . "</h2>";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS Department Document Management System | Add OJT Record</title>
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
    <h2>Add OJT Record</h2>
    <?php
        // Display general success/error messages from database operations
        echo $display;
        // Display the specific error message for no company selected
        echo $error_message;
    ?>
    <hr>
    <form method="post" action="add_ojt_records.php">
    <div style="display:flex; flex-direction:row;">
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="ojt_full_name">Name:</label>
        <input type="text" id="ojt_full_name" name="ojt_full_name" required>
        </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="ojt_email">Email:</label>
        <input type="email" id="ojt_email" name="ojt_email" required>
        </div>
    </div>
    <div style="display:flex; flex-direction:row;">
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="ojt_telno">Tel No.:</label>
        <input type="text" id="ojt_telno" name="ojt_telno" required>
        </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="ojt_address">Address:</label>
        <input type="text" id="ojt_address" name="ojt_address" required>
        </div>
    </div>
        <div style="">
        <label for="ojt_description">OJT Description:</label>
        <textarea id="ojt_description" class="textarea" name="ojt_description" required></textarea>
        </div>
    <div style="display:flex; flex-direction:row;">
    <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        <label for="company_name">Company:</label>
        <?php if (!empty($companies)): ?>
            <select id="ojt_company" name="ojt_company" required>
                <option value="" disabled selected>-- Select a company --</option>
                <?php foreach ($companies as $company): ?>
                    <option value="<?php echo htmlspecialchars($company); ?>">
                        <?php echo htmlspecialchars($company); ?>
                    </option>
                <?php endforeach; ?>
            </select>
                 </div>
                 <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
             <p>Can't Find Company? </p><a href="add_company(connected).php?redirect=add_ojt_records.php">Click here to add a company</a><br>
         </div>
         </div>
        <?php else: ?>
            <p class="add-company">
            <p>No company data available.
                 <a href="add_company(connected).php?redirect=add_ojt_records.php">Add</a>
            </p>
        </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        </div>
        </div>
        <?php endif; ?>
        <div>
        <br><br><input type="submit" value="Submit New Record">
        </div>
    </form>
    <hr>
    <a href="view_ojt_records.php" class="button backtodash">Back to Dashboard</a>
  </div>
</body>
</html>