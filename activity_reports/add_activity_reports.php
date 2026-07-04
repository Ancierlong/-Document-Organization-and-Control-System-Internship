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
    $activity_title = mysqli_real_escape_string($conn, $_POST['activity_title']);
    $activity_date = mysqli_real_escape_string($conn, $_POST['activity_date']);
    $activity_description = mysqli_real_escape_string($conn, $_POST['activity_description']);
    $postedby = mysqli_real_escape_string($conn, $_POST['posted_by']);
    $academicyear = mysqli_real_escape_string($conn, $_POST['academic_year']);
    date_default_timezone_set("Asia/Manila");
    $datetime = date("Y-m-d H:i:s");
    $userid = $_SESSION['user_id'];

    // --- File Upload Handling ---
    $targetDir = "../file_activity_reports/"; // Folder where files will be stored

    // Check if the folder exists; if not, create it
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = null;
    if (isset($_FILES["file_name"]) && $_FILES["file_name"]["error"] == UPLOAD_ERR_OK) {
        $fileName = basename($_FILES["file_name"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        // Allowed file types (adjust as needed)
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = ["jpg", "png", "gif", "pdf", "txt", "docx", "jfif", "webp", "heif", "bmp"];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["file_name"]["tmp_name"], $targetFilePath)) {
                // File uploaded successfully, $fileName now holds the name to save in DB
            } else {
                $display = "<h2 style='color:red;'>Error uploading file.</h2>";
                $fileName = null; // Don't save if upload failed
            }
        } else {
            $display = "<h2 style='color:red;'>Invalid file type. Allowed types: " . implode(", ", $allowedTypes) . "</h2>";
            $fileName = null; // Don't save if invalid type
        }
    } elseif (isset($_FILES["file_name"]) && $_FILES["file_name"]["error"] != UPLOAD_ERR_NO_FILE) {
        $display = "<h2 style='color:red;'>File upload error: Code " . $_FILES["file_name"]["error"] . "</h2>";
        $fileName = null;
    } else {
        // No file was uploaded (this is okay, filename in DB will be null or empty)
    }
    // --- End File Upload Handling ---

    // Insert the values into the database
    $sql = "INSERT INTO activityreports (activity_title, activity_date, activity_description, file_name, posted_by, academic_year, uploaded_at) VALUES ('$activity_title', '$activity_date', '$activity_description' , '$fileName', '$postedby', '$academicyear', '$datetime');";

    $sql .= "INSERT INTO logs_activity_reports (`type`, `modified_item`, `user`)
              VALUES ('Add', '$datetime', $userid);";

    if (mysqli_multi_query($conn, $sql)) {
        $display = "<h2 style='color:green;'>Activity Report Added!</h2>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

?>


<body>
    <div class="addemp-container">
    <h4>CCS-DOCS</h4>
    <h2>Add Activity Reports</h2>
        <?php
        echo $display;
        ?>
    <hr>
        <form method="post" action="add_activity_reports.php" enctype="multipart/form-data">
        <div style="display:flex; flex-direction:row;">
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
                <label for="activity_title">Title:</label>
                <input type="text" id="activity_title" name="activity_title" required>
                <br>
        </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
                <label for="activity_date">Date:</label>
                <input type="date" id="activity_date" name="activity_date" required>
                <br>
        </div>
        </div>
        <div style="display:flex; flex-direction:row;">
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
                <label for="posted_by">Posted By:</label>
                <input type="text" id="posted_by" name="posted_by" required>
                <br>
        </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
                <label for="academic_year">Academic Year:</label>
                <input type="text" id="academic_year" name="academic_year" required>
                <br>
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
        <div style="">
        <br>
                <label for="activity_description">Description:</label>
                <textarea id="activity_description" class="textarea" name="activity_description" required></textarea>
                <br>
        </div>
        <div style="">
                <input type="submit" name="submit" value="Submit New Record">
        </div>
        </form>
    <hr>
    <a href="view_activity_reports_test.php" class="button backtodash">Back to Dashboard</a>
    </div>
</body>
</html>