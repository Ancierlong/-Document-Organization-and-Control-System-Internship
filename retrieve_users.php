<?php
session_start();

// If the user is not logged in, redirect to the login page.
// This script should ideally not be accessed directly via browser
// but this check is good practice if it were.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    exit;
}

require '../db_connect.php';

$loggedInUsername = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$loggedInRole = isset($_SESSION['role']) ? $_SESSION['role'] : ''; // Assuming role is stored in session

$search = isset($_GET['search']) ? $_GET['search'] : '';
// Define allowed sortable columns
$allowedSortColumns = ['role', 'username', 'full_name', 'email', 'reg_date', 'created_by'];
$sortBy = isset($_GET['sortBy']) && in_array($_GET['sortBy'], $allowedSortColumns) ? $conn->real_escape_string($_GET['sortBy']) : 'role'; // Sanitize sortBy input
$sortOrder = isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'DESC' ? 'DESC' : 'ASC';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Ensure page is at least 1
$rowsPerPage = isset($_GET['rowsPerPage']) ? max(5, (int)$_GET['rowsPerPage']) : 10; // Ensure rowsPerPage is at least 5
$offset = ($page - 1) * $rowsPerPage;

// Prepare the search term for the LIKE clause
$searchTermLike = "%" . $search . "%";

// --- Get total number of records (Using Prepared Statement) ---
$totalSql = "SELECT COUNT(*) AS total FROM users WHERE `role` LIKE ? OR username LIKE ? OR full_name LIKE ? OR email LIKE ? OR reg_date LIKE ? OR created_by LIKE ?";

$totalStmt = $conn->prepare($totalSql);
// Bind parameters - adjust types if your columns are different
$totalStmt->bind_param("ssssss", $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike);
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $rowsPerPage);

// Ensure requested page is not beyond the last page, unless there are no records
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $rowsPerPage;
} elseif ($totalPages == 0) {
     $page = 1; // If no records, stay on page 1
     $offset = 0;
}


// --- Fetch paginated results (Using Prepared Statement) ---
$sql = "SELECT * FROM users
        WHERE `role` LIKE ? OR username LIKE ? OR full_name LIKE ?
        OR email LIKE ? OR reg_date LIKE ? OR created_by LIKE ?
        ORDER BY $sortBy $sortOrder
        LIMIT ?, ?";

$stmt = $conn->prepare($sql);
// Bind parameters - adjust types if your columns are different, and note the 'ii' for limit and offset
$stmt->bind_param("ssssssii", $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $searchTermLike, $offset, $rowsPerPage);
$stmt->execute();
$result = $stmt->get_result();

// Determine if the logged-in user has the 'Admin' or 'Faculty' role (case-insensitive check)
$loggedInUserHasAdminRole = strpos(strtolower($loggedInRole), 'admin') !== false;
$loggedInUserHasFacultyRole = strpos(strtolower($loggedInRole), 'faculty') !== false;


// Start building the table HTML string
$tableHtml = '';

// Generate table rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $userRole = strtolower($row['role']); // Normalize case

        // Disable buttons if the user already has that role (existing logic)
        $isAdmin = strpos($userRole, 'admin') !== false;
        $isFaculty = strpos($userRole, 'faculty') !== false;
        $isCouncil = strpos($userRole, 'council') !== false;

        // Check if the user in the current row is the logged-in user
        $isCurrentUserLoggedInUser = ($row['username'] === $loggedInUsername);

        // Check if the user in the current row has the specific username "Admin"
        $isUserInRowSpecificAdmin = ($row['username'] === 'Admin'); // Case-sensitive check for the specific username "Admin"

        // Determine if role change buttons should be enabled for this row
        // Buttons are enabled ONLY if:
        // 1. The logged-in user has the 'Admin' role
        // AND
        // 2. The user in the row is NOT the currently logged-in user
        // AND
        // 3. The user in the row does NOT have the specific username "Admin"
        $canChangeRoleOfThisUser = $loggedInUserHasAdminRole && !$isCurrentUserLoggedInUser && !$isUserInRowSpecificAdmin;

        // Calculate disabled states for each button based on *both*
        // the general permission ($canChangeRoleOfThisUser) and the user's current role.
        // Buttons are disabled if:
        // 1. The logged-in user cannot change the role of this user ($canChangeRoleOfThisUser is false)
        // OR
        // 2. The user in the row already has the target role
        $disableAdminButton = (!$canChangeRoleOfThisUser || $isAdmin) ? "disabled class='grayed'" : "";
        $disableFacultyButton = (!$canChangeRoleOfThisUser || $isFaculty) ? "disabled class='grayed'" : "";
        $disableCouncilButton = (!$canChangeRoleOfThisUser || $isCouncil) ? "disabled class='grayed'" : "";

        // --- Updated Logic for Reset Password Disabling ---
        // The reset password button is disabled if:
        // 1. The logged-in user is neither Admin nor Faculty.
        // OR
        // 2. (The logged-in user is Admin or Faculty) AND (the user in the current row is the logged-in user).
        // OR
        // 3. The user in the current row has the specific username "Admin".
        $canResetPassword = ($loggedInUserHasAdminRole || $loggedInUserHasFacultyRole) && !$isCurrentUserLoggedInUser && !$isUserInRowSpecificAdmin;
        $disableResetPassword = !$canResetPassword ? "disabled class='grayed'" : "";
        // --- End Updated Logic ---


        $tableHtml .= "<tr>";
        $tableHtml .= "<td>" . htmlspecialchars($row["role"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["username"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["full_name"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["email"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["reg_date"]) . "</td>";
        $tableHtml .= "<td>" . htmlspecialchars($row["created_by"]) . "</td>";

        // Role Change Buttons Cell
        // This cell is always generated, and the CSS in view_user_list.php will hide it for non-admins.
        // The buttons inside will be disabled based on the PHP logic above.
        $tableHtml .= "<td class='buttons2'>";
        $tableHtml .= "<div class='buttons-container'>";
        $tableHtml .= "<form action='promote_user.php' method='post'>
                           <input type='hidden' name='user_id' value='" . htmlspecialchars($row['id']) . "'>
                           <input type='hidden' name='un' value='" . htmlspecialchars($row['username']) . "'>
                           <button type='submit' name='promote' value='Admin' {$disableAdminButton}>Change to Admin</button>
                       </form>";
        $tableHtml .= "<form action='promote_user.php' method='post'>
                           <input type='hidden' name='user_id' value='" . htmlspecialchars($row['id']) . "'>
                           <input type='hidden' name='un' value='" . htmlspecialchars($row['username']) . "'>
                           <button type='submit' name='promote' value='Faculty' {$disableFacultyButton}>Change to Faculty</button>
                       </form>";
        $tableHtml .= "<form action='promote_user.php' method='post'>
                           <input type='hidden' name='user_id' value='" . htmlspecialchars($row['id']) . "'>
                           <input type='hidden' name='un' value='" . htmlspecialchars($row['username']) . "'>
                           <button type='submit' name='promote' value='Council' {$disableCouncilButton}>Change to Council</button>
                       </form>";
        $tableHtml .= "</div>";
        $tableHtml .= "</td>";

        // Reset Password Button Cell
        // This cell is always generated, and the CSS in view_user_list.php will hide it for roles that cannot reset passwords.
        // The button inside will be disabled based on the PHP logic above.
        $tableHtml .= "<td class='buttons2'>";
        // Pass the username to the confirm function
        $tableHtml .= "<form action='reset_password.php' method='post' onsubmit='return confirmResetPassword(\"" . htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') . "\");'>
                           <input type='hidden' name='reset_user_id' value='" . htmlspecialchars($row['id']) . "'>
                           <input type='hidden' name='email' value='" . htmlspecialchars($row['email']) . "'>
                           <button type='submit' name='submit' {$disableResetPassword}>Reset Password</button>
                       </form>";
        $tableHtml .= "</td>";

        $tableHtml .= "</tr>";
    }
} else {
    // Colspan should match the total number of headers (8)
    $tableHtml = "<tr><td colspan='8' style='text-align: center;'>No records found</td></tr>";
}


// --- Generate Optimized Pagination HTML ---
$paginationHtml = '';
if ($totalPages > 1) {
    $paginationHtml .= '<div class="pagination-buttons-container">'; // Added a container div for potential styling

    // First button
    $paginationHtml .= '<button class="pagination-button" onclick="changePage(1)"' . ($page == 1 ? ' disabled' : '') . '>First</button>';
    // Previous button
    $paginationHtml .= '<button class="pagination-button" onclick="changePage(' . ($page - 1) . ')"' . ($page == 1 ? ' disabled' : '') . '>Previous</button>';

    // Numbered page buttons
    $numLinks = 2; // Number of page links to show around the current page
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

    // Next button
    $paginationHtml .= '<button class="pagination-button" onclick="changePage(' . ($page + 1) . ')"' . ($page == $totalPages ? ' disabled' : '') . '>Next</button>';
    // Last button
    $paginationHtml .= '<button class="pagination-button" onclick="changePage(' . $totalPages . ')"' . ($page == $totalPages ? ' disabled' : '') . '>Last</button>';

    $paginationHtml .= '</div>';
}


// Prepare the response data as an associative array
$response = [
    'tableHtml' => $tableHtml,
    'paginationHtml' => $paginationHtml
];

// Set the Content-Type header to application/json
header('Content-Type: application/json');

// Encode the response array as a JSON string and output it
echo json_encode($response);

// Close statement and connection
$stmt->close();
$totalStmt->close();
$conn->close();
?>