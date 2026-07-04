<?php
session_start();

// --- Security: Ensure user is logged in ---
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
// Optional: Check for specific role/permissions if needed
// if (!isset($_SESSION['employeeid'])) { // This check might be redundant or incorrect depending on your auth logic
//     header('Location: ../dashboard.php');
//     exit;
// }

// --- Database Connection ---
$conn = require '../db_connect.php'; // Ensure this path is correct and returns a valid connection
if (!$conn) {
    die("Database connection failed."); // Basic error handling for connection
}

$display = ""; // Variable to hold status messages
$id = null; // Initialize ID variable
$userid = $_SESSION['user_id'];

// --- Form Submission Handling ---
if (isset($_POST['submit'])) {
    // --- Sanitize and Retrieve Form Data ---
    $concepttitle = mysqli_real_escape_string($conn, $_POST['concept_title']);
    $conceptdate = mysqli_real_escape_string($conn, $_POST['concept_date']);
    $conceptdescription = mysqli_real_escape_string($conn, $_POST['concept_description']);
    $academicyear = mysqli_real_escape_string($conn, $_POST['academic_year']);
    $resourcespeaker = mysqli_real_escape_string($conn, $_POST['concept_resource_speaker']);
    $evaluation = mysqli_real_escape_string($conn, $_POST['concept_evaluation_rating']); // Ensure this field exists in your form/DB
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $uploaded_at = mysqli_real_escape_string($conn, $_POST['uploaded_at']); // Get ID from hidden field

    // --- Determine Concept Type to Save ---
    $selected_type_option = mysqli_real_escape_string($conn, $_POST['concept_type']); // Value from dropdown
    $type_to_save = $selected_type_option; // Default to dropdown value
    if ($selected_type_option == "Others") {
        // If "Others" is selected, get the value from the specific text input
        if (isset($_POST['concept_type_other']) && !empty(trim($_POST['concept_type_other']))) {
            $type_to_save = mysqli_real_escape_string($conn, trim($_POST['concept_type_other']));
        } else {
            // Handle case where 'Others' is selected but the text box is empty - assign a default or show error
            $type_to_save = 'Other'; // Or potentially prevent submission / set $display error
            // $display = "<h2 style='color:red;'>Please specify the type when 'Others' is selected.</h2>";
            // Consider adding validation logic here
        }
    }

    // --- File Upload Handling ---
    $new_filename = null; // To store the name of the successfully uploaded new file
    $upload_error = false;
    $old_filename = null; // To store the name of the old file

    // Fetch the current filename before potentially overwriting it
    $sql_select_old_file = "SELECT `file_name` FROM `conceptpapers` WHERE `id` = ?";
    $stmt_select_old_file = $conn->prepare($sql_select_old_file);
    $stmt_select_old_file->bind_param("i", $id);
    $stmt_select_old_file->execute();
    $result_old_file = $stmt_select_old_file->get_result();
    if ($result_old_file && $row_old_file = $result_old_file->fetch_assoc()) {
        $old_filename = $row_old_file['file_name'];
    }
    $stmt_select_old_file->close();


    // Check if a file was actually uploaded via the form
    if (isset($_FILES['file_name']) && $_FILES['file_name']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['file_name']['tmp_name'];
        $original_filename = basename($_FILES['file_name']['name']);
        $file_ext = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'doc', 'docx']; // Define allowed file types

        // --- Basic Security Checks ---
        if (in_array($file_ext, $allowed_extensions)) {
            $upload_dir = '../files_concept_papers/'; // Define your upload directory (relative to this script or absolute)
            $destination = $upload_dir . $original_filename;

            // --- Ensure upload directory exists ---
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true); // Create directory recursively if it doesn't exist
            }

            // --- Move the uploaded file ---
            if (move_uploaded_file($file_tmp_path, $destination)) {
                // File uploaded successfully, $new_filename holds the name to save in DB
                $new_filename = $original_filename;
            } else {
                $display = "<h2 style='color:red;'>Error moving uploaded file. Check permissions for " . htmlspecialchars($upload_dir) . "</h2>";
                $upload_error = true;
                error_log("File upload move error for: " . $original_filename); // Log error server-side
            }
        } else {
            $display = "<h2 style='color:red;'>Invalid file type. Allowed types: " . implode(', ', $allowed_extensions) . "</h2>";
            $upload_error = true;
        }
    } elseif (isset($_FILES['file_name']) && $_FILES['file_name']['error'] != UPLOAD_ERR_NO_FILE) {
        // Handle other potential upload errors (size limit, partial upload, etc.)
        $display = "<h2 style='color:red;'>File upload error: Code " . $_FILES['file_name']['error'] . "</h2>";
        $upload_error = true;
        error_log("File upload error code: " . $_FILES['file_name']['error'] . " for ID: " . $id); // Log error
    }
    // --- End File Upload Handling ---


    // --- Build and Execute SQL UPDATE Query ---
    if (!$upload_error) { // Proceed only if there wasn't an upload error
        if ($new_filename !== null) {
            // If a new file was successfully uploaded, update the filename and potentially reset approval
            $sql = "UPDATE `conceptpapers` SET
                                    `concept_title`=?, `concept_date`=?, `concept_description`=?,
                                    `file_name`=?, `academic_year`=?, `concept_type`=?,
                                    `concept_resource_speaker`=?, `concept_evaluation_rating`=?,
                                    `status`= 'Pending' -- Reset approval status? Check your logic
                                WHERE `id` = ?";

            $stmt = $conn->prepare($sql);
            // Bind parameters: s=string, i=integer
            $stmt->bind_param("ssssssssi",
                $concepttitle, $conceptdate, $conceptdescription,
                $new_filename, $academicyear, $type_to_save,
                $resourcespeaker, $evaluation, // Assuming evaluation is numeric, adjust if not (e.g., 's')
                $id
            );

            if ($stmt->execute()) {
                // Log the file update
                $log_sql = "INSERT INTO `logs_concept_papers` (`type`, `modify_id`, `user`) VALUES (?, ?, ?)";
                $log_stmt = $conn->prepare($log_sql);
                $log_message = "File updated. New filename: " . $new_filename;
                $modify = "Modify";
                $log_stmt->bind_param("sss", $modify, $uploaded_at, $userid);
                $log_stmt->execute();
                $log_stmt->close();
            } else {
                // Consider logging the error if the update fails
                error_log("Error updating concept paper with ID " . $id . ": " . $stmt->error);
            }
        } else {
            // If no new file was uploaded, update other fields but keep the existing filename
            $sql = "UPDATE `conceptpapers` SET
                                    `concept_title`=?, `concept_date`=?, `concept_description`=?,
                                    `academic_year`=?, `concept_type`=?,
                                    `concept_resource_speaker`=?, `concept_evaluation_rating`=?
                                    -- Do not update file_name or approved status
                                WHERE `id` = ?";
            $stmt = $conn->prepare($sql);
            // Bind parameters:
            $stmt->bind_param("sssssssi",
                $concepttitle, $conceptdate, $conceptdescription,
                $academicyear, $type_to_save,
                $resourcespeaker, $evaluation, // Assuming evaluation is numeric
                $id
            );

            if ($stmt->execute()) {
                // Log the update of other details (excluding file)
                $log_sql = "INSERT INTO `logs_concept_papers` (`type`, `modify_id`, `user`) VALUES (?, ?, ?)";
                $log_stmt = $conn->prepare($log_sql);
                $log_message = "Concept paper details updated (excluding file).";
                $modify = "Modify";
                $log_stmt->bind_param("sss", $modify , $uploaded_at, $userid);
                $log_stmt->execute();
                $log_stmt->close();
            } else {
                // Consider logging the error if the update fails
                error_log("Error updating concept paper details (excluding file) with ID " . $id . ": " . $stmt->error);
            }
        }
        // --- Execute the statement ---
        if ($stmt->execute()) {
            $display = "<h2 style='color:green;'>Concept Paper Updated!</h2>";
        } else {
            $display = "<h2 style='color:red;'>Error updating record: " . $stmt->error . "</h2>";
            error_log("SQL Update Error: " . $stmt->error . " SQL: " . $sql); // Log the error
        }
        $stmt->close();
    }
}
    // --- End Build and Execute SQL ---

// --- End Form Submission Handling ---


// --- Retrieve Data for Display/Editing ---
// Get ID from POST if editing link was clicked, otherwise use ID from submission if available
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['submit'])) {
    $id = isset($_POST['id']) ? mysqli_real_escape_string($conn, $_POST['id']) : null;
} elseif (!isset($id) && isset($_GET['id'])) { // Allow getting ID from URL parameter as fallback
    $id = mysqli_real_escape_string($conn, $_GET['id']);
}

if (!$id) {
    die("Error: Concept Paper ID is missing."); // Stop if no ID is provided
}

// Fetch the current data for the given ID
$sql_select = "SELECT `id`, `concept_title`, `concept_date`, `concept_description`, `file_name`, `uploaded_at`, `academic_year`, `concept_type`, `concept_resource_speaker`, `concept_evaluation_rating`
                    FROM `conceptpapers` WHERE `id` = ?";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param("i", $id);
$stmt_select->execute();
$result = $stmt_select->get_result();

if ($result && $row = $result->fetch_assoc()) {
    // Assign fetched data to variables for the form
    $concept_title = $row['concept_title'];
    $concept_date = $row['concept_date'];
    $concept_description = $row['concept_description'];
    $current_file_name = $row['file_name']; // Store current filename separately
    $uploaded_at = $row['uploaded_at'];
    $academic_year = $row['academic_year'];
    $concept_type = isset($row['concept_type']) ? trim($row['concept_type']) : '';
    $concept_resource_speaker = $row['concept_resource_speaker'];
    $concept_evaluation_rating = $row['concept_evaluation_rating'];

    // Determine if the current type is 'Other' for conditional display logic
    $is_other_type = !in_array($concept_type, ["COP/CES", "Enrichment"], true) && !empty($concept_type);
    $other_type_style = $is_other_type ? 'display: block;' : 'display: none;';

} else {
    // Handle case where record is not found (or query failed)
    if (!isset($_POST['submit'])) { // Only show fatal error if not during a submission process
        die("Error: Could not retrieve record with ID: " . htmlspecialchars($id) . ". Error: " . $conn->error);
    } else {
        // If during submission, record might have been deleted? Show previous error message.
        // $display .= "<br><span style='color:orange;'>Could not re-fetch record after update attempt.</span>";
    }
    // Initialize variables to avoid errors in the form if record not found
    $concept_title = ''; $concept_date = ''; $concept_description = ''; $current_file_name = '';
    $academic_year = ''; $concept_type = ''; $concept_resource_speaker = ''; $concept_evaluation_rating = '';
    $is_other_type = false; $other_type_style = 'display: none;';
}
$stmt_select->close();
// --- End Data Retrieval ---

// Close DB connection at the end of the script (optional, PHP often handles this)
// mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>CCS Department Database Management System | Edit Concept Paper</title>
    <style>
        /* --- Paste your CSS styles here --- */
        body {
            font-family: Arial, sans-serif;
            background-color: #7F1416; /* UPHSD Maroon */
            margin-top: 20px;
            margin-bottom: 20px;
            padding: 15px; /* Add some padding */
            display: flex;
            align-items: flex-start; /* Align top */
            justify-content: center;
            min-height: 100vh; /* Ensure body takes full height */
        }

        .addemp-container {
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            padding: 25px;
            width: 100%;
            /* Set a max-width for larger screens */
        }

        .addemp-container h2,
        .addemp-container h3,
        .addemp-container h4 {
            text-align: center;
            margin-bottom: 20px;
        }

        .addemp-container img {
            display: block; /* Center image */
            margin-left: auto;
            margin-right: auto;
            width: 150px; /* Adjust logo size */
            height: auto;
            margin-bottom: 15px;
        }

        .addemp-container label {
            display: block; /* Labels on own line */
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 5px; /* Space below label */
        }

        .addemp-container input[type="text"],
        .addemp-container input[type="file"],
        .addemp-container input[type="date"],
        .addemp-container select,
        .addemp-container textarea {
            width: 100%;
            padding: 8px; /* Slightly adjusted padding */
            margin-bottom: 15px; /* Consistent margin */
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Include padding in width */
            font-size: 14px; /* Consistent font size */
        }

        .addemp-container textarea {
            height: 100px;
            resize: vertical; /* Allow vertical resize */
            font-family: Arial, sans-serif; /* Inherit font */
        }

        .addemp-container hr {
            border: none;
            height: 1px;
            background-color: #ddd;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .addemp-container .button,
        .addemp-container input[type="submit"] {
            display: block; /* Make buttons block level */
            width: auto; /* Auto width based on content */
            min-width: 120px; /* Minimum width */
            text-align: center;
            margin-bottom: 10px;
            padding: 10px 20px;
            color: #fff;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 16px;
        }

        .addemp-container input[type="submit"] {
            background-color: #4CAF50; /* Green */
            margin-top: 15px;
            /* Center submit button */
            margin-left: auto;
            margin-right: auto;
        }
        .addemp-container input[type="submit"]:hover {
            background-color: #3d8b40;
        }

        .addemp-container .backtodash {
            background-color: #ff851b; /* Orange */
            /* Center back button */
            margin-left: auto;
            margin-right: auto;
        }
        .addemp-container .backtodash:hover {
            background-color: #d47716;
        }

        /* Style for flex layout */
        .form-row {
            display: flex;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
            gap: 20px; /* Space between columns */
            margin-bottom: 15px; /* Space below row */
        }
        .form-column {
            flex: 1; /* Each column takes equal space */
            min-width: 250px; /* Minimum width before wrapping */
        }

        /* Style for file info */
        .current-file-info {
            font-size: 0.9em;
            color: #555;
            margin-bottom: 15px;
        }

    </style>
</head>

<body>
    <div class="addemp-container">
        <h4>CCS-DOCS</h4>
        <h2>Edit Concept Paper</h2>

        <?php echo $display; // Display status messages (success/error) ?>

        <hr>

        <form method="post" action="edit_concept_papers.php" enctype="multipart/form-data">

            <div class="form-row">
                <div class="form-column">
                    <label for="concept_title">Title:</label>
                    <input type="text" id="concept_title" name="concept_title" value="<?php echo htmlspecialchars($concept_title, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="form-column">
                    <label for="concept_type">Type:</label>
                    <select id="concept_type" name="concept_type" required>
                        <option value="COP/CES" <?php echo ($concept_type == "COP/CES") ? "selected" : ""; ?>>COP/CES</option>
                        <option value="Enrichment" <?php echo ($concept_type == "Enrichment") ? "selected" : ""; ?>>Enrichment</option>
                        <option value="Others" <?php echo $is_other_type ? "selected" : ""; ?>>Others</option>
                    </select>

                    <div id="other_type_container" style="margin-top: 10px; <?php echo $other_type_style; ?>">
                        <label for="concept_type_other">Specify Other Type:</label>
                        <input type="text" id="concept_type_other" name="concept_type_other" value="<?php echo $is_other_type ? htmlspecialchars($concept_type, ENT_QUOTES, 'UTF-8') : ''; ?>">
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-column">
                    <label for="academic_year">Academic Year:</label>
                     <input type="text" id="academic_year" name="academic_year" value="<?php echo htmlspecialchars($academic_year, ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g., 2024-2025" required>
                </div>
                <div class="form-column">
                    <label for="concept_date">Date Accomplished:</label>
                    <input type="date" id="concept_date" name="concept_date" value="<?php echo htmlspecialchars($concept_date, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-column">
                    <label for="concept_resource_speaker">Resource Speaker:</label>
                    <input type="text" id="concept_resource_speaker" name="concept_resource_speaker" value="<?php echo htmlspecialchars($concept_resource_speaker, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="form-column">
                    <label for="concept_evaluation_rating">Evaluation Rating:</label>
                     <input type="text" id="concept_evaluation_rating" name="concept_evaluation_rating" value="<?php echo htmlspecialchars($concept_evaluation_rating, ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-column">
                     <label for="file_name">Upload New File (Optional):</label>
                     <input type="file" id="file_name" name="file_name">
                     <?php
$folder_path = "../files_concept_papers/"; // Adjust the path as needed

if (!empty($current_file_name)) {
    $file_path = $folder_path . $current_file_name;

    if (file_exists($file_path)) {
        echo "Current File: <a href='../files_concept_papers/". htmlspecialchars($current_file_name) ."'>" . htmlspecialchars($current_file_name) . "</a> (Leave empty to keep the current file)";
    } else {
        echo "<p class='error'>No Current file.</p>";
    }
} else {
    echo "<p>No file selected.</p>";
}
?>
                </div>
            </div>

            <div>
                <label for="concept_description">Description:</label>
                <textarea id="concept_description" name="concept_description" required><?php echo htmlspecialchars($concept_description, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <div>
            <input type='hidden' id="id" name='id' value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
            <input type='hidden' id="uploaded_at" name='uploaded_at' value="<?php echo htmlspecialchars($uploaded_at, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="submit" name="submit" value="Update Record">
            </div>

        </form>

        <hr>

        <a href="view_concept_papers_test.php" class="button backtodash">Back to List</a>
    </div>

    <script>
        // Function to toggle the visibility of the 'other type' input field
        function toggleOtherTypeInput() {
            var conceptTypeSelect = document.getElementById('concept_type');
            var otherContainer = document.getElementById('other_type_container');
            var otherInput = document.getElementById('concept_type_other'); // Get the input element itself

            if (!conceptTypeSelect || !otherContainer || !otherInput) return; // Add checks for element existence

            if (conceptTypeSelect.value === 'Others') {
                otherContainer.style.display = 'block'; // Show the container
                // No need to focus automatically unless desired
            } else {
                otherContainer.style.display = 'none'; // Hide the container
                // Optional: Clear the 'other' input value when a standard type is selected
                // otherInput.value = '';
            }
        }

        // Add event listener to the dropdown
        var conceptTypeElement = document.getElementById('concept_type');
        if (conceptTypeElement) {
            conceptTypeElement.addEventListener('change', toggleOtherTypeInput);
        }

        // Run the function once on page load to set the initial state
        window.addEventListener('DOMContentLoaded', toggleOtherTypeInput);

    </script>

</body>
</html>