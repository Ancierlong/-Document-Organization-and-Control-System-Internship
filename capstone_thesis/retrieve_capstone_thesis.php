<?php
session_start();

// If the user is not logged in, redirect to the login page.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    exit;
}

require '../db_connect.php';

// The role is no longer needed here for *conditionally* generating the TD,
// as hiding is handled by CSS in the frontend based on a data attribute.
// However, you might still want the role if you have other backend logic
// dependent on it, but it's not used for the TD generation anymore.
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';


$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$allowedSortColumns = ['projecttype', 'projecttitle', 'projectdescription', 'projectcategory', 'projectyear', 'projectproponents', 'projectrecommendation', 'file_name', 'uploaded_at'];
$sortBy = isset($_GET['sortBy']) && in_array($_GET['sortBy'], $allowedSortColumns) ? $_GET['sortBy'] : 'projectyear';
$sortOrder = isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'DESC' ? 'DESC' : 'ASC';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$rowsPerPage = isset($_GET['rowsPerPage']) ? max(5, (int)$_GET['rowsPerPage']) : 10;
$offset = ($page - 1) * $rowsPerPage;

// Get total number of records (using prepared statement is safer)
$totalSql = "SELECT COUNT(*) AS total FROM thesiscapstoneprojects
             WHERE (projecttype LIKE ? OR projecttitle LIKE ? OR projectdescription LIKE ? OR projectcategory LIKE ? OR projectyear LIKE ? OR projectproponents LIKE ? OR projectrecommendation LIKE ? OR file_name LIKE ?)
             AND archive = 0";

$totalStmt = $conn->prepare($totalSql);
$searchTermLike = "%" . $search . "%";
$totalStmt->bind_param("ssssssss", $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike);
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $rowsPerPage);

// Ensure requested page is not beyond the last page
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $rowsPerPage;
} elseif ($totalPages == 0) {
    $page = 1;
    $offset = 0;
}


// Fetch paginated results (using prepared statement is safer)
$sql = "SELECT * FROM thesiscapstoneprojects
         WHERE (projecttype LIKE ? OR
               projecttitle LIKE ? OR
               projectdescription LIKE ? OR
               projectcategory LIKE ? OR
               projectyear LIKE ? OR
               projectproponents LIKE ? OR
               projectrecommendation LIKE ? OR
               `file_name` LIKE ?)
         AND archive = 0
         ORDER BY $sortBy $sortOrder
         LIMIT ?, ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssssii", $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $offset, $rowsPerPage);
$stmt->execute();
$result = $stmt->get_result();

// Start building the response array
$response = [];
$tableHtml = '';

// Generate table rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sanitizedFileName = htmlspecialchars($row["file_name"]);
        $filePath = "../files_capstone_thesis/" . urlencode($row["file_name"]);

        $tableHtml .= "<tr>";
        $tableHtml .= "<td>" . htmlspecialchars($row["projecttype"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["projecttitle"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["projectdescription"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["projectcategory"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["projectyear"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["projectproponents"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["projectrecommendation"]) . "</td>";
        $tableHtml .= "<td style='text-align: center;'><a href='$filePath' target='_blank'>" . $sanitizedFileName . "</a></td>"; // Centered file link cell

        $tableHtml .= "<td class='buttons'>
                          <form action='edit_capstone_thesis.php' method='post' style='display:inline-block;'>
                             <input type='hidden' name='id' value='{$row['id']}'>
                             <button type='submit'>Edit</button>
                          </form>
                       </td>";

        // *** SIMPLIFIED: ALWAYS generate the archive TD ***
        // The CSS in the frontend will hide this column if the user is not Admin.
        $tableHtml .= "<td class='buttons2'>
                          <form action='archive_capstone_thesis.php' method='POST' style='display:inline-block;'>
                             <input type='hidden' name='id' value='{$row['id']}'>
                             <input type='hidden' name='uploaded_at' value='{$row['uploaded_at']}'>
                             <button type='submit' onclick='return confirmDelete()'>Archive</button>
                          </form>
                       </td>";
        // *** END SIMPLIFIED ***

        $tableHtml .= "</tr>";
    }
} else {
    // Colspan should match the total number of headers (10)
    $tableHtml = "<tr><td colspan='10' style='text-align: center;'>No records found</td></tr>";
}

// Add table HTML to the response
$response['tableHtml'] = $tableHtml;


// --- Generate Optimized Pagination HTML ---
$paginationHtml = '';
if ($totalPages > 1) {
    $paginationHtml .= '<div class="pagination-buttons-container">';

    $paginationHtml .= '<button class="pagination-button" onclick="changePage(1)"' . ($page == 1 ? ' disabled' : '') . '>First</button>';
    $paginationHtml .= '<button class="pagination-button" onclick="changePage(' . ($page - 1) . ')"' . ($page == 1 ? ' disabled' : '') . '>Previous</button>';

    $numLinks = 2;
    $startPage = max(1, $page - $numLinks);
    $endPage = min($totalPages, $page + $numLinks);

    // Adjust range to always show a consistent number of links if possible
    if ($endPage - $startPage + 1 < (2 * $numLinks + 1)) {
        if ($startPage > 1) {
            $startPage = max(1, $endPage - (2 * $numLinks));
        } elseif ($endPage < $totalPages) {
             $endPage = min($totalPages, $startPage + (2 * $numLinks));
        }
    }
     // Ensure bounds are correct after adjustment
     $startPage = max(1, $startPage);
     $endPage = min($totalPages, $endPage);


    for ($i = $startPage; $i <= $endPage; $i++) {
        $activeClass = ($i == $page) ? "current-page" : "";
        $paginationHtml .= "<button class='pagination-button $activeClass' onclick='changePage($i)'>$i</button> ";
    }

    $paginationHtml .= '<button class="pagination-button" onclick="changePage(' . ($page + 1) . ')"' . ($page == $totalPages ? ' disabled' : '') . '>Next</button>';
    $paginationHtml .= '<button class="pagination-button" onclick="changePage(' . $totalPages . ')"' . ($page == $totalPages ? ' disabled' : '') . '>Last</button>';

    $paginationHtml .= '</div>';
}

$response['paginationHtml'] = $paginationHtml;

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>