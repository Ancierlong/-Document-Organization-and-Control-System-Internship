<?php
session_start();

// If the user is not logged in, return an error for AJAX.
if (!isset($_SESSION['user_id'])) { // Changed from 'username' to 'user_id' for consistency
    http_response_code(403); // Forbidden
    // Using JSON for error response as the front-end is expecting JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session expired or unauthorized. Please log in.']);
    exit;
}

require '../db_connect.php';

// Headers to prevent caching the data fetched via AJAX
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// Ensure 'role' is set (used here to conditionally show Archive button *within* the TD)
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Get parameters from GET request (sent by AJAX)
$search = isset($_GET['search']) ? $_GET['search'] : ''; // Get search directly for param binding
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'company_name';
$sortOrder = isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'DESC' ? 'DESC' : 'ASC';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Ensure page >= 1
$rowsPerPage = isset($_GET['rowsPerPage']) ? max(5, (int)$_GET['rowsPerPage']) : 10; // Ensure rowsPerPage >= 5

// --- Sanitize & Validate $sortBy and $sortOrder ---
// Define allowed columns for sorting (matching view file headers)
$allowedSortColumns = ['company_name', 'company_description', 'company_website', 'company_telno', 'company_email', 'company_address', 'contact_person', 'contact_person_email', 'contact_person_telno', 'uploaded_at']; // Added uploaded_at if sortable
if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'company_name'; // Default if invalid
}
// Sanitize sort order (only 'ASC' or 'DESC')
if (!in_array($sortOrder, ['ASC', 'DESC'])) {
     $sortOrder = 'DESC'; // Default if invalid or not provided
}
// --- End Sanitization ---


// --- Use Prepared Statements for Security ---

// Get total number of records for pagination
// Using LIKE for search requires binding the wildcard % with the term
// Updated WHERE clause to match the search fields used in the frontend placeholder
$totalSql = "SELECT COUNT(*) AS total FROM company_linkages
             WHERE (company_name LIKE ? OR company_website LIKE ?)
             AND archive = 0";

$totalStmt = $conn->prepare($totalSql);
$searchTermLike = "%" . $search . "%";
// Bind the search term to the relevant LIKE clauses
$totalStmt->bind_param("ss", $searchTermLike, $searchTermLike);
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $rowsPerPage);

// Calculate offset AFTER getting total pages, in case current page is beyond total
$page = min($page, $totalPages > 0 ? $totalPages : 1); // Adjust page if it exceeds total pages (handle 0 records)
$offset = ($page - 1) * $rowsPerPage;


// Fetch paginated results using prepared statement
// ORDER BY column/direction names cannot be parameters, so they are concatenated AFTER validation
$sql = "SELECT id, company_name, company_description, company_website, company_telno, company_email, company_address,
               contact_person, contact_person_email, contact_person_telno, uploaded_at
        FROM company_linkages
        WHERE (company_name LIKE ? OR
               company_description LIKE ? OR
               company_website LIKE ? OR
               company_telno LIKE ? OR
               company_email LIKE ? OR
               company_address LIKE ? OR
               contact_person LIKE ? OR
               contact_person_email LIKE ? OR
               contact_person_telno LIKE ? OR
               uploaded_at LIKE ?) -- Added uploaded_at to search fields
        AND archive = 0
        ORDER BY " . $sortBy . " " . $sortOrder . "
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);
// Bind parameters for the WHERE clause (search term) and LIMIT/OFFSET
$searchTermLike = "%" . $search . "%"; // Re-declare for this statement
$stmt->bind_param("ssssssssssii", $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $offset, $rowsPerPage);
$stmt->execute();
$result = $stmt->get_result();

// --- End Prepared Statements ---


// Start building the response array
$response = [];
$tableHtml = '';

// Generate table rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $companyId = htmlspecialchars($row['id']); // Get the ID for buttons

        $tableHtml .= "<tr>";
        $tableHtml .= "<td>" . htmlspecialchars($row["company_name"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["company_description"]) . "</td>";
        // Added target='_blank' to open link in new tab
        $tableHtml .= "<td><a href='" . htmlspecialchars($row["company_website"]) . "' target='_blank'>" . htmlspecialchars($row["company_website"]) . "</a></td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["company_telno"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["company_email"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["company_address"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["contact_person"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["contact_person_email"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["contact_person_telno"]) . "</td>";


        // --- Action Buttons TD (Always generate TD for column consistency) ---

        // Edit Button TD (Always generate the TD for column consistency)
        $tableHtml .= "<td class='buttons'>";
        // The Edit form/button is always present, column visibility handled by CSS
        $tableHtml .= "<form action='edit_ojt_company.php' method='post' style='display:inline-block;'>";
        $tableHtml .= "<input type='hidden' name='id' value='{$companyId}'>";
        // Added 'edit-button' class for styling consistency and JS targeting
        $tableHtml .= "<button type='submit' class='edit-button'>Edit</button>";
        $tableHtml .= "</form>";
        $tableHtml .= "</td>";

        // Archive Button TD (Always generate the TD for column consistency)
        // Using buttons2 class for Archive (matches red color)
        $tableHtml .= "<td class='buttons2'>";
        // Show Archive form/button only if the user is Admin (based on the $role variable)
        // This is the conditional part *inside* the TD, handled by PHP
        if ($role === 'Admin') { // Only show the button if the role is Admin
            $tableHtml .= "<form action='archive_ojt_company.php' method='POST' onsubmit='return confirmDelete()'>";
            $tableHtml .= "<input type='hidden' name='id' value='{$companyId}'>";
            // Include uploaded_at if archive_ojt_company.php requires it
             $tableHtml .= "<input type='hidden' name='uploaded_at' value='{$row['uploaded_at']}'>";
            // Added 'archive-button' class
            $tableHtml .= "<button type='submit' class='archive-button'>Archive</button>";
            $tableHtml .= "</form>";
        } else {
             // Optionally show an empty cell or a placeholder if not Admin, though CSS hides the column anyway
             // An empty TD is sufficient for column structure.
        }
        $tableHtml .= "</td>";

        // --- End Action Buttons TD ---


        $tableHtml .= "</tr>";
    }
} else {
    // Colspan should match the total number of headers (11)
    $tableHtml = "<tr><td colspan='11' style='text-align: center;'>No records found</td></tr>";
}

// Add table HTML to the response array
$response['tableHtml'] = $tableHtml;


// --- Generate Optimized Pagination HTML ---
$paginationHtml = '';
// Only generate pagination HTML if there's more than one page
if ($totalPages > 1) {
    // This container div matches the ID/class used in the frontend HTML (#paginationControls / .pagination)
    $paginationHtml .= '<div class="pagination">';

    // First button
    // onclick calls the global JS changePage function
    $paginationHtml .= '<button type="button" class="pagination-button" onclick="changePage(1)"' . ($page == 1 ? ' disabled' : '') . '>First</button>';

    // Previous button
    $paginationHtml .= '<button type="button" class="pagination-button" onclick="changePage(' . ($page - 1) . ')"' . ($page == 1 ? ' disabled' : '') . '>Previous</button>';

    // Page numbers (show a limited range)
    $numLinks = 2; // Number of page links to show around the current page
    $startPage = max(1, $page - $numLinks);
    $endPage = min($totalPages, $page + $numLinks);

    // Adjust range to always show a consistent number of links if possible
    $range = $endPage - $startPage + 1;
    $desiredRange = (2 * $numLinks + 1);

     if ($range < $desiredRange) {
         if ($startPage > 1) {
             // Need to add more pages to the end, adjust startPage
             $startPage = max(1, $endPage - $desiredRange + 1);
         } elseif ($endPage < $totalPages) {
              // Need to add more pages to the beginning, adjust endPage
              $endPage = min($totalPages, $startPage + $desiredRange - 1);
         }
     }
     // Final check on bounds after adjustment
     $startPage = max(1, $startPage);
     $endPage = min($totalPages, $endPage);


    for ($i = $startPage; $i <= $endPage; $i++) {
        $activeClass = ($i == $page) ? "current-page" : "";
         // onclick calls the global JS changePage function
        $paginationHtml .= "<button type='button' class='pagination-button $activeClass' onclick='changePage($i)'>$i</button> ";
    }

    // Next button
    $paginationHtml .= '<button type="button" class="pagination-button" onclick="changePage(' . ($page + 1) . ')"' . ($page == $totalPages ? ' disabled' : '') . '>Next</button>';

    // Last button
    $paginationHtml .= '<button type="button" class="pagination-button" onclick="changePage(' . $totalPages . ')"' . ($page == $totalPages ? ' disabled' : '') . '>Last</button>';

    $paginationHtml .= '</div>'; // Close the container div
}

// Add pagination HTML to the response array
$response['paginationHtml'] = $paginationHtml;


// Set header to indicate JSON content
header('Content-Type: application/json');

// Output the JSON response
echo json_encode($response);

// Check if connection exists before closing
if ($conn) {
    $conn->close(); // Close connection
}
?>
