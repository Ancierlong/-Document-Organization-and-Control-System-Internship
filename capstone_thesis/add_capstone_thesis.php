<!DOCTYPE html>
<html>
<head>
    <title>CCS Department Document Management System | Add Capstone/Thesis</title>
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
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $proponents = mysqli_real_escape_string($conn, $_POST['proponents']);
    $recommendation = mysqli_real_escape_string($conn, $_POST['recommendation']);
    date_default_timezone_set("Asia/Manila");
    $datetime = date("Y-m-d H:i:s");
    $userid = $_SESSION['user_id'];

    // --- File Upload Handling ---
    $targetDir = "../files_capstone_thesis/"; // Folder where files will be stored

    // Check if the folder exists; if not, create it
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = null;
    if (isset($_FILES["file_name"]) && $_FILES["file_name"]["error"] == UPLOAD_ERR_OK) {
        $fileName = basename($_FILES["file_name"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        // Allowed file types
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
    $sql = "INSERT INTO thesiscapstoneprojects (projecttype, projecttitle, projectdescription, projectcategory, projectyear, projectproponents, projectrecommendation, file_name, uploaded_at)
            VALUES ('$type', '$title', '$description', '$category', '$year', '$proponents', '$recommendation', '$fileName', '$datetime');";

    $sql .= "INSERT INTO logs_thesis_capstone_projects (`type`, `modified_item`, `user`)
              VALUES ('Add', '$datetime', $userid);";

    if (mysqli_multi_query($conn, $sql)) {
    $display = "<h2 style='color:green;'>Capstone/Thesis Added!</h2>";
    } else {
    $display =  "Error: " . mysqli_error($conn);
    }
}

// Close the database connection
// mysqli_close($conn);
?>


<body>
    <div class="addemp-container">
    <h4>CCS-DOCS</h4>
    <h2>Add Capstone/Thesis</h2>
        <?php
        echo $display;
        ?>
    <hr>
        <form method="post" action="add_capstone_thesis.php" enctype="multipart/form-data">
            <div style="display:flex; flex-direction:row;">
            <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
            <label for="type">Type:</label>
                <input type="text" id="type" name="type" required>
                <br>
            </div>
            <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" required>
                <br>
            </div>
            </div>
            <div style="display:flex; flex-direction:row;">
            <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
                <label for="proponents">Proponents:</label>
                <input type="text" id="proponents" name="proponents" required>
                <br>
            </div>
            <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
                    <label for="category">Category:</label>
                    <select id="category" name="category" required>
                        <option value="" selected disabled>-- Select Category --</option> <option value="Client Based">Client Based</option>
                        <option value="Start Up">Start Up</option>
                    </select>
                    </div>
            </div>
            <div style="display:flex; flex-direction:row;">
            <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
                <label for="year">Date(Year):</label>
                <input type="number" id="year" name="year" required placeholder="YYYY" min="1900" max="2100">
                <br>
            </div>
            <div style="width:50%; margin-left:12.5px; margin-right: 12.5px;">
                <label for="file_name">File:</label>
                <input type="file" id="file_name" name="file_name">
            </div>
            </div>
            <div>
                <label for="recommendation">Recommendation:</label>
                <textarea id="recommendation" class="textarea" name="recommendation" required></textarea>
                <br>
            </div>
            <div style="">
                <label for="description">Description:</label>
                <textarea id="description" class="textarea" name="description" required></textarea>
                <br>
            </div>
            <div style="">
                <input type="submit" name="submit" value="Submit New Record">
            </div>

        </form>
    <hr>
    <a href="view_capstone_thesis_test.php" class="button backtodash">Back to Dashboard</a>
    </div>
</body>
</html>