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
$userid = $_SESSION['user_id'];

// Initialize variables for the form
$id = null;
$activity_title = '';
$activity_date = '';
$activity_description = '';
$file_name = '';
$academic_year = '';
$posted_by = '';
$uploadedat = '';

// Handle form submission
if (isset($_POST['submit'])) {
    $activitytitle = mysqli_real_escape_string($conn, $_POST['activity_title']);
    $activitydate = mysqli_real_escape_string($conn, $_POST['activity_date']);
    $activitydescription = mysqli_real_escape_string($conn, $_POST['activity_description']);
    $academicyear = mysqli_real_escape_string($conn, $_POST['academic_year']);
    $postedby = mysqli_real_escape_string($conn, $_POST['posted_by']);
    $uploadedat = mysqli_real_escape_string($conn, $_POST['uploadedat']);
    $id = mysqli_real_escape_string($conn, $_POST['id']);

    // --- File Upload Handling ---
    $targetDir = "../file_activity_reports/"; // Adjust the path as needed

    // Check if the folder exists; if not, create it
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $newFileName = null;
    if (isset($_FILES["file_name"]) && $_FILES["file_name"]["error"] == UPLOAD_ERR_OK) {
        $newFileName = basename($_FILES["file_name"]["name"]);
        $targetFilePath = $targetDir . $newFileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = ["jpg", "png", "gif", "pdf", "txt", "docx", "jfif", "webp", "heif", "bmp"];

        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES["file_name"]["tmp_name"], $targetFilePath)) {
                // File uploaded successfully, $newFileName holds the name to save
            } else {
                $display = "<h2 style='color:red;'>Error uploading file.</h2>";
                $newFileName = null;
            }
        } else {
            $display = "<h2 style='color:red;'>Invalid file type. Allowed types: " . implode(", ", $allowedTypes) . "</h2>";
            $newFileName = null;
        }
    } elseif (isset($_FILES["file_name"]) && $_FILES["file_name"]["error"] != UPLOAD_ERR_NO_FILE) {
        $display = "<h2 style='color:red;'>File upload error: Code " . $_FILES["file_name"]["error"] . "</h2>";
    }
    // --- End File Upload Handling ---

    // --- Database Update ---
    $sql = "UPDATE `activityreports` SET
            `activity_title`=?,
            `activity_date`=?,
            `activity_description`=?,
            `academic_year`=?,
            `posted_by`=?";

    if ($newFileName !== null) {
        $sql .= ", `file_name`=?";
    }

    $sql .= " WHERE `id` = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if ($newFileName !== null) {
            $stmt->bind_param("ssssssi", $activitytitle, $activitydate, $activitydescription, $academicyear, $postedby, $newFileName, $id);
        } else {
            $stmt->bind_param("sssssi", $activitytitle, $activitydate, $activitydescription, $academicyear, $postedby, $id);
        }

        if ($stmt->execute()) {
            $display = "<h2 style='color:green;'>Activity Report Updated!</h2>";
        } else {
            echo "Error updating record: " . $stmt->error;
        }
        $stmt->close();

        // Log the update
        $log_sql = "INSERT INTO logs_activity_reports (`type`, `modified_item`, `user`) VALUES ('Modify', ?, ?)";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("si", $uploadedat, $userid);
        $log_stmt->execute();
        $log_stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
}

// Retrieve data for display
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id"]) && !isset($_POST['submit'])) {
    $id = mysqli_real_escape_string($conn, $_POST["id"]);
} elseif (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($conn, $_GET["id"]);
}

if ($id) {
    $sql2 = "SELECT `id`, `activity_title`, `activity_date`, `activity_description`, `file_name`, `uploaded_at` , `academic_year` , `posted_by` FROM `activityreports` WHERE `id` = ?";
    $stmt_select = $conn->prepare($sql2);
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result2 = $stmt_select->get_result();

    if ($result2 && $row2 = $result2->fetch_assoc()) {
        $id = $row2['id'];
        $activity_title = $row2['activity_title'];
        $activity_date = $row2['activity_date'];
        $activity_description = $row2['activity_description'];
        $file_name = $row2['file_name'];
        $academic_year = $row2['academic_year'];
        $posted_by = $row2['posted_by'];
        $uploadedat = $row2['uploaded_at'];
    } else {
        echo "Error retrieving activity report data.";
    }
    $stmt_select->close();
}

mysqli_close($conn);
?>


<body>
    <div class="addemp-container">
    <h4>CCS-DOCS</h4>
    <h2>Edit Activity Reports</h2>
        <?php
        echo $display;
        ?>
    <hr>
        <form method="post" action="edit_activity_reports.php" enctype="multipart/form-data">
        <div style="display:flex; flex-direction:row;">
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
                <label for="activity_title">Title:</label>
                <input type="text" id="activity_title" name="activity_title" value="<?php echo htmlspecialchars($activity_title, ENT_QUOTES, 'UTF-8'); ?>" required>
                <br>
        </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
                <label for="activity_date">Date:</label>
                <input type="date" id="activity_date" name="activity_date" value="<?php echo htmlspecialchars($activity_date, ENT_QUOTES, 'UTF-8'); ?>" required>
                <br>
        </div>
        </div>
        <div style="display:flex; flex-direction:row;">
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
                <label for="posted_by">Posted By:</label>
                <input type="text" id="posted_by" name="posted_by" value="<?php echo htmlspecialchars($posted_by, ENT_QUOTES, 'UTF-8'); ?>" required>
                <br>
        </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
                <label for="academic_year">Academic Year:</label>
                <input type="text" id="academic_year" name="academic_year" value="<?php echo htmlspecialchars($academic_year, ENT_QUOTES, 'UTF-8'); ?>" required>
                <br>
        </div>
        </div>
        <div style="display:flex; flex-direction:row;">
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
                <label for="File">File:</label>
                <input type="file" id="file_name" name="file_name">

                <?php
                $folder_path = "../file_activity_reports/"; // Adjust the path as needed

                if (!empty($file_name)) {
                    $file_path = $folder_path . $file_name;

                    if (file_exists($file_path)) {
                        echo "Current File: <a href='../file_activity_reports/". htmlspecialchars($file_name) ."'>" . htmlspecialchars($file_name) . "</a> (Leave empty to keep the current file)";
                    } else {
                        echo "<p class='error'>No Current file.</p>";
                    }
                } else {
                    echo "<p>No file selected.</p>";
                }
                ?>
            </div>
        <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
        </div>
        </div>
        <div style="">
                <label for="activity_description">Description:</label>
                <textarea id="activity_description" class="textarea" name="activity_description" required><?php echo htmlspecialchars($activity_description, ENT_QUOTES, 'UTF-8'); ?></textarea>
                <br>
        </div>
        <div style="">
        <input type='hidden' id="uploadedat" name='uploadedat' value="<?php echo htmlspecialchars($uploadedat, ENT_QUOTES, 'UTF-8'); ?>">
            <input type='hidden' id="id" name='id' value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="submit" name="submit" value="Update Record">
        </div>
        </form>
    <hr>
    <a href="view_activity_reports_test.php" class="button backtodash">Back to List</a>
    </div>
</body>
</html>