<?php
require 'db_connect.php';

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'projectyear';
$sortOrder = isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'DESC' ? 'DESC' : 'ASC';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rowsPerPage = isset($_GET['rowsPerPage']) ? (int)$_GET['rowsPerPage'] : 10;
$offset = ($page - 1) * $rowsPerPage;

// Get total number of records
$totalSql = "SELECT COUNT(*) AS total FROM thesiscapstoneprojects 
WHERE projecttype = '%$search%' OR projecttitle LIKE '%$search%' AND archive = 0";
$totalResult = $conn->query($totalSql);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $rowsPerPage);

// Fetch paginated results
$sql = "SELECT * FROM thesiscapstoneprojects 
WHERE (projecttype LIKE '%$search%' OR 
       projecttitle LIKE '%$search%' OR 
       projectdescription LIKE '%$search%' OR 
       projectcategory LIKE '%$search%' OR 
       projectyear LIKE '%$search%' OR 
       projectproponents LIKE '%$search%' OR 
       projectrecommendation LIKE '%$search%' OR 
       `file_name` LIKE '%$search%' OR 
       uploaded_at LIKE '%$search%') 
        AND archive = 0
        ORDER BY $sortBy $sortOrder 
        LIMIT $offset, $rowsPerPage";
$result = $conn->query($sql);

// Generate table rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $filePath = "../files_capstone_thesis/" . $row["file_name"];
        echo "<tr>
                <td>{$row["projecttype"]}</td>
                <td>{$row["projecttitle"]}</td>
                <td>{$row["projectdescription"]}</td>
                <td>{$row["projectcategory"]}</td>
                <td>{$row["projectyear"]}</td>
                <td>{$row["projectproponents"]}</td>
                <td>{$row["projectrecommendation"]}</td>
                <td><a href='$filePath' target='_blank'>" . $row["file_name"] . "</a></td>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='8'>No records found</td></tr>";
}

// Generate pagination buttons with sorting parameters
echo '<tr><td colspan="8" style="text-align: center;">'; echo '<div class="pagination-buttons-container">'; // Add a container div for flexbox
for ($i = 1; $i <= $totalPages; $i++) {
  $activeClass = ($i == $page) ? "current-page" : "";
  // Removed disabled attribute from buttons that are not the current page
  echo "<button class='pagination-button $activeClass' onclick='changePage($i)'>$i</button> "; // Changed class name to avoid conflict
}
echo '</div>'; // Close the container div
echo '</td></tr>';

$conn->close(); // Close connection after fetching data
?>

