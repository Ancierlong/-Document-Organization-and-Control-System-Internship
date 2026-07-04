<?php
session_start();

// If the user is not logged in, redirect to the login page.
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

require '../db_connect.php';
require '../vendor/autoload.php'; // Include Composer autoloader for TCPDF

use TCPDF as TCPDF;

// Ensure 'role' is set to avoid undefined index error
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'concept_date';
$sortOrder = isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'DESC' ? 'DESC' : 'ASC';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rowsPerPage = isset($_GET['rowsPerPage']) ? (int)$_GET['rowsPerPage'] : 10;
$offset = ($page - 1) * $rowsPerPage;

// Function to insert image into PDF
function insertImageToPdf($pdfPath, $imagePath, $x, $y, $width = 0, $height = 0) {
    if (!file_exists($pdfPath)) {
        return false;
    }

    $pdf = new TCPDF();
    $pageCount = $pdf->setSourceFile($pdfPath);

    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
        $templateId = $pdf->importPage($pageNo);
        $pdf->addPage();
        $pdf->useTemplate($templateId, 0, 0, null, null, true);

        // Insert the image on the first page only (you can modify this logic)
        if ($pageNo === 1 && file_exists($imagePath)) {
            $pdf->Image($imagePath, $x, $y, $width, $height);
        }
    }

    $newPdfPath = str_replace('.pdf', '_with_image.pdf', $pdfPath);
    $pdf->Output($newPdfPath, 'F'); // Save the new PDF
    return $newPdfPath;
}

// Define image path and position (adjust these values)
$imageToInsert = '../path/to/your/image.png'; // Replace with the actual path to your image
$imageX = 10; // X-coordinate for image placement (in mm)
$imageY = 10; // Y-coordinate for image placement (in mm)
$imageWidth = 30; // Optional: Set image width (in mm)
$imageHeight = 0; // Optional: Set image height (in mm), set to 0 to maintain aspect ratio

// Handle image insertion request
if (isset($_POST['insert_image']) && isset($_POST['file_to_modify'])) {
    $originalFilePath = "../files_concept_papers/" . $_POST['file_to_modify'];
    $newFilePath = insertImageToPdf($originalFilePath, $imageToInsert, $imageX, $imageY, $imageWidth, $imageHeight);

    if ($newFilePath) {
        $_SESSION['message'] = "Image inserted successfully into " . basename($newFilePath);
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = "Failed to insert image into " . $_POST['file_to_modify'];
        $_SESSION['message_type'] = 'danger';
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING']); // Redirect to refresh the page
    exit();
}

// Get total number of records
$totalSql = "SELECT COUNT(*) AS total FROM conceptpapers WHERE concept_title LIKE '%$search%' OR concept_date LIKE '%$search%'";
$totalResult = $conn->query($totalSql);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $rowsPerPage);

// Fetch paginated results
$sql = "SELECT * FROM conceptpapers
        WHERE (concept_title LIKE '%$search%' OR concept_date LIKE '%$search%' OR concept_description LIKE '%$search%'
        OR concept_type LIKE '%$search%' OR concept_resource_speaker LIKE '%$search%'
        OR concept_evaluation_rating LIKE '%$search%' OR `file_name` LIKE '%$search%' OR uploaded_at LIKE '%$search%')
        AND archive = 0
        ORDER BY $sortBy $sortOrder
        LIMIT $offset, $rowsPerPage";
$result = $conn->query($sql);

// Display message if any
if (isset($_SESSION['message'])): ?>
    <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
        <?php echo $_SESSION['message']; ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php unset($_SESSION['message']);
          unset($_SESSION['message_type']);
endif;

// Generate table rows
echo "<table class='table table-bordered table-striped'>";
echo "<thead>
        <tr>
            <th>Concept Title</th>
            <th>Description</th>
            <th>Type</th>
            <th>Academic Year</th>
            <th>Concept Date</th>
            <th>Resource Speaker</th>
            <th>File</th>
            <th>Evaluation Rating</th>
            <th>Edit</th>";
if ($role !== 'Student' && $role !== 'Faculty') {
    echo "<th>Archive</th>";
    echo "<th>Insert Image</th>"; // New table header
}
echo "</tr>
      </thead>
      <tbody>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $filePath = "../files_concept_papers/" . $row["file_name"];

        echo "<tr>
                <td>{$row["concept_title"]}</td>
                <td>{$row["concept_description"]}</td>
                <td>{$row["concept_type"]}</td>
                <td>{$row["academic_year"]}</td>
                <td>{$row["concept_date"]}</td>
                <td>{$row["concept_resource_speaker"]}</td>
                <td><a href='$filePath' target='_blank'>" . $row["file_name"] . "</a></td>
                <td>{$row["concept_evaluation_rating"]}</td>
                <td class='buttons'>
                <form action='edit_concept_papers.php' method='post'>
                <input type='hidden' name='id' value={$row['id']}>
                <button type='submit' value='view'>Edit</button>
                </form>
                </td>";

        // Only show delete and insert image buttons if the user is NOT Student or Faculty
        if ($role !== 'Student' && $role !== 'Faculty') {
            echo "<td>
                    <form action='archive_concept_papers.php' method='POST'>
                        <input type='hidden' name='id' value='{$row['id']}'>
                        <button type='submit' onclick='return confirmDelete()'>Archive</button>
                    </form>
                  </td>";
            echo "<td>
                    <form method='post'>
                        <input type='hidden' name='file_to_modify' value='{$row['file_name']}'>
                        <button type='submit' name='insert_image'>Insert Image</button>
                    </form>
                  </td>";
        }

        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='10'>No records found</td></tr>"; // Adjusted colspan
}

echo "</tbody></table>";

// Generate pagination buttons with sorting parameters
echo '<div style="text-align: center;">';
for ($i = 1; $i <= $totalPages; $i++) {
    $disabledClass = ($i == $page) ? "current-page" : "";
    $disabledAttr = ($i == $page) ? "disabled" : "";
    echo "<button class='pagination $disabledClass' onclick='changePage($i)' $disabledAttr>$i</button> ";
}
echo '</div>';

$conn->close();
?>

<script>
    function confirmDelete() {
        return confirm("Are you sure you want to archive this concept paper?");
    }

    function changePage(page) {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('page', page);
        window.location.href = currentUrl.toString();
    }
</script>

<style>
    .pagination {
        padding: 8px 12px;
        margin: 5px;
        border: 1px solid #ccc;
        background-color: #f9f9f9;
        cursor: pointer;
    }

    .pagination.current-page {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
        cursor: default;
    }

    .pagination:hover:not(.current-page) {
        background-color: #eee;
    }
</style>

<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>