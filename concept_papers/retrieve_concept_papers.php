<?php
session_start();

// If the user is not logged in, return an error for AJAX.
if (!isset($_SESSION['user_id'])) {
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

// Ensure 'role' is set to avoid undefined index error
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// Get parameters from GET request (sent by AJAX in File 1)
$search = isset($_GET['search']) ? $_GET['search'] : ''; // Get search directly for param binding
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'concept_date';
$sortOrder = isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'DESC' ? 'DESC' : 'ASC';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Ensure page >= 1
$rowsPerPage = isset($_GET['rowsPerPage']) ? max(5, (int)$_GET['rowsPerPage']) : 10; // Ensure rowsPerPage >= 5

// --- Sanitize & Validate $sortBy and $sortOrder ---
// Define allowed columns for sorting
$allowedSortColumns = ['concept_title', 'concept_description', 'concept_type', 'academic_year', 'concept_date', 'concept_resource_speaker', 'file_name', 'concept_evaluation_rating', 'status', 'uploaded_at']; // Add all sortable columns
if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'concept_date'; // Default if invalid sort column is provided
}
// Sanitize sort order (only 'ASC' or 'DESC')
if (!in_array($sortOrder, ['ASC', 'DESC'])) {
    $sortOrder = 'DESC'; // Default if invalid sort order
}
// --- End Sanitization ---


// --- Use Prepared Statements for Security ---

// Get total number of records for pagination
// Using LIKE for search requires binding the wildcard % with the term
$totalSql = "SELECT COUNT(*) AS total FROM conceptpapers
             WHERE (concept_title LIKE ? OR concept_description LIKE ? OR concept_type LIKE ? OR academic_year LIKE ? OR concept_date LIKE ? OR concept_resource_speaker LIKE ? OR file_name LIKE ? OR concept_evaluation_rating LIKE ? OR status LIKE ?)
             AND archive = 0";

$totalStmt = $conn->prepare($totalSql);
$searchTermLike = "%" . $search . "%";
// Bind the search term to each LIKE clause
$totalStmt->bind_param("sssssssss", $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike);
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
$sql = "SELECT id, concept_title, concept_description, concept_type, academic_year, concept_date,
               concept_resource_speaker, file_name, concept_evaluation_rating, status, uploaded_at,
               rejection_reason
        FROM conceptpapers
        WHERE (concept_title LIKE ? OR
               concept_description LIKE ? OR
               concept_type LIKE ? OR
               academic_year LIKE ? OR
               concept_date LIKE ? OR
               concept_resource_speaker LIKE ? OR
               `file_name` LIKE ? OR
               concept_evaluation_rating LIKE ? OR
               status LIKE ?)
        AND archive = 0
        ORDER BY " . $sortBy . " " . $sortOrder . "
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);
// Bind parameters for the WHERE clause (search term) and LIMIT/OFFSET
$searchTermLike = "%" . $search . "%"; // Re-declare for this statement
$stmt->bind_param("sssssssssii", $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $offset, $rowsPerPage);
$stmt->execute();
$result = $stmt->get_result();

// --- End Prepared Statements ---


// Start building the response array
$response = [];
$tableHtml = '';

// Generate table rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sanitizedFileName = htmlspecialchars($row["file_name"]);
        // Use urlencode for the href to handle spaces and special characters
        $filePath = "../files_concept_papers/" . urlencode($row["file_name"]);

        // Check if the file exists using the absolute server path for robustness
        $uploadDir = realpath(__DIR__ . '/../files_concept_papers/');
        $fullFilePath = $uploadDir . '/' . $row["file_name"];
         // Use is_string check before file_exists to prevent errors if realpath fails
        $pdfLink = (is_string($uploadDir) && file_exists($fullFilePath)) ? "<a href='$filePath' target='_blank'>" . $sanitizedFileName . "</a>" : "File not found";


        $conceptId = htmlspecialchars($row['id']); // Get the ID for buttons
        $currentStatus = htmlspecialchars($row['status']); // Get the status

        $tableHtml .= "<tr>";
        $tableHtml .= "<td>" . htmlspecialchars($row["concept_title"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["concept_description"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["concept_type"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["academic_year"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["concept_date"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["concept_resource_speaker"]) . "</td>";
        $tableHtml .= "<td style='text-align: center;'>" . $pdfLink . "</td>"; // Use the generated link/text
        $tableHtml .= "<td>" . htmlspecialchars($row["concept_evaluation_rating"]) . "</td>";
        $tableHtml .= "<td class='status-cell' data-id='{$conceptId}'>"; // Add class and data-id for JS
            $statusText = $currentStatus;
            $statusColor = '';
            if ($currentStatus === 'Pending') {
                 $statusColor = 'orange';
            } elseif ($currentStatus === 'Approved') {
                 $statusColor = 'green';
            } elseif ($currentStatus === 'Rejected') {
                 $statusColor = 'red';
            }
            $tableHtml .= "<span style='color: {$statusColor}; font-weight: bold;'>{$statusText}</span>";
            // Display the rejection reason below the status if available and rejected
            if ($currentStatus === 'Rejected' && !empty($row['rejection_reason'])) {
                 // Sanitize reason for displaying in HTML
                 $tableHtml .= "<span class='rejection-reason'>Reason: " . htmlspecialchars($row['rejection_reason']) . "</span>"; // Added "Reason: " prefix
            }
        $tableHtml .= "</td>"; // Close status cell


        // --- Action Buttons TD (Always generate TD, button inside based on status and role) ---

        // Edit Button TD (Always generate the TD for column consistency)
        $tableHtml .= "<td class='buttons'>";
        // The Edit form/button is always present, column visibility handled by CSS (not hidden for any role now)
        // Need to check if user role is allowed to edit (assuming Admin can edit)
        if ($role === 'Admin') { // Add role check for Edit
            $tableHtml .= "<form action='edit_concept_papers.php' method='post' style='display:inline-block;'>";
            $tableHtml .= "<input type='hidden' name='id' value='{$conceptId}'>";
            // Added 'edit-button' class for styling consistency and JS targeting
            $tableHtml .= "<button type='submit' class='edit-button'>Edit</button>";
            $tableHtml .= "</form>";
        } else {
             // Optionally show a disabled button for non-Admin users
             $tableHtml .= "<button type='button' disabled style='background-color: gray; color: white; border: 1px solid gray; cursor: not-allowed;'>Edit</button>";
        }
        $tableHtml .= "</td>";

        // Approve Button TD (Always generate the TD for column consistency)
        $tableHtml .= "<td class='buttons'>";
        // Show Approve button only if status is Pending AND user is Admin
        if ($currentStatus === 'Pending' && $role === 'Admin') {
            // Approve action is reverted to form submission to pdffinalv2.php
             $tableHtml .= "<form action='pdffinalv2.php' method='post' style='display:inline-block;'>";
             $tableHtml .= "<input type='hidden' name='id' value='{$conceptId}'>";
             // Include uploaded_at if pdffinalv2.php needs it (it does for session)
             $tableHtml .= "<input type='hidden' name='uploaded_at' value='" . htmlspecialchars($row['uploaded_at'], ENT_QUOTES, 'UTF-8') . "'>";
             $tableHtml .= "<button type='submit' class='approve-button'>Approve</button>"; // Use the approve-button class
             $tableHtml .= "</form>";
        } else {
             // Optionally show a disabled button for non-pending items or non-Admin users
             // This TD will be hidden by CSS for non-Admins anyway, but adding disabled button
             // provides clarity if CSS is bypassed or for debugging.
             $tableHtml .= "<button type='button' disabled style='background-color: gray; color: white; border: 1px solid gray; cursor: not-allowed;'>Approve</button>";
        }
        $tableHtml .= "</td>";


        // Reject Button TD (Always generate the TD for column consistency)
        $tableHtml .= "<td class='buttons'>";
        // Show Reject button only if status is Pending AND user is Admin
        if ($currentStatus === 'Pending' && $role === 'Admin') {
            // Reject action is handled by JS modal+fetch, uses data-id
            // Added 'reject-button' class and 'data-id' and 'data-uploaded_at' for JS delegation and modal
             $tableHtml .= "<button type='button' class='reject-button' data-id='{$conceptId}' data-uploaded_at='" . htmlspecialchars($row['uploaded_at'], ENT_QUOTES, 'UTF-8') . "'>Reject</button>";
        } else {
             // Optionally show a disabled button for non-pending items or non-Admin users
             $tableHtml .= "<button type='button' disabled style='background-color: gray; color: white; border: 1px solid gray; cursor: not-allowed;'>Reject</button>";
        }
        $tableHtml .= "</td>";

        // Archive Button TD (Always generate the TD for column consistency)
        // Using buttons2 class for Archive (matches red color)
        $tableHtml .= "<td class='buttons2'>";
        // Show Archive form/button only if status is Approved or Rejected AND user is Admin
        if (($currentStatus === 'Approved' || $currentStatus === 'Rejected') && $role === 'Admin') {
            // Archive action is handled by a form submission (assumed)
            $tableHtml .= "<form action='archive_concept_papers.php' method='POST' onsubmit='return confirmDelete()'>";
            $tableHtml .= "<input type='hidden' name='id' value='{$conceptId}'>";
            // Include uploaded_at if archive_concept_papers.php requires it
             $tableHtml .= "<input type='hidden' name='uploaded_at' value='" . htmlspecialchars($row['uploaded_at'], ENT_QUOTES, 'UTF-8') . "'>";
            // Added 'archive-button' class
            $tableHtml .= "<button type='submit' class='archive-button'>Archive</button>";
            $tableHtml .= "</form>";
        } else {
             // Optionally show a disabled button for non-approved/rejected items or non-Admin users
             $tableHtml .= "<button type='button' disabled style='background-color: gray; color: white; border: 1px solid gray; cursor: not-allowed;'>Archive</button>";
        }
        $tableHtml .= "</td>";

        // --- End Action Buttons TD ---


        $tableHtml .= "</tr>";
    }
} else {
    // Colspan should match the total number of headers (13)
    $tableHtml = "<tr><td colspan='13' style='text-align: center;'>No records found</td></tr>";
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