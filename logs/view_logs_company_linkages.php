<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require '../db_connect.php'; // Include the database connection file

$itemsPerPage = 10;

// Function to generate the log table HTML (can be called via AJAX)
function generateLogTableHTML($conn, $itemsPerPage, $currentPage, $searchTerm, $sortBy = 'l.date', $sortDirection = 'DESC') {

    function getLogData($conn, $limit, $offset, $searchTerm = '', $sortBy = 'l.date', $sortDirection = 'DESC') {
        $sql = "
            SELECT
                l.type AS log_type,
                cl.company_name AS modified_item_title, -- Corrected table alias to cl
                u.username AS modified_by_user,
                l.date AS log_date
            FROM
                logs_company_linkages l
            JOIN
                company_linkages cl ON l.modified_item = cl.uploaded_at -- Corrected table alias to cl
            JOIN
                users u ON l.user = u.id
        ";

        $whereClauses = [];
        if (!empty($searchTerm)) {
            // Using LIKE with wildcards requires manual escaping if not using prepared statements
            $escapedSearchTerm = $conn->real_escape_string($searchTerm);
            $whereClauses[] = "(l.type LIKE '%$escapedSearchTerm%'
                                OR cl.company_name LIKE '%$escapedSearchTerm%' -- Corrected table alias to cl
                                OR u.username LIKE '%$escapedSearchTerm%'
                                OR l.date LIKE '%$escapedSearchTerm%')";
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        // Basic validation for sortBy and sortDirection to prevent SQL injection
        // Added 'cl.company_name' to valid sort columns
        $validSortColumns = ['l.type', 'cl.company_name', 'u.username', 'l.date'];
        $validSortDirections = ['ASC', 'DESC'];

        $cleanSortBy = in_array($sortBy, $validSortColumns) ? $sortBy : 'l.date';
        $cleanSortDirection = in_array(strtoupper($sortDirection), $validSortDirections) ? strtoupper($sortDirection) : 'DESC';


        $sql .= " ORDER BY $cleanSortBy $cleanSortDirection LIMIT $limit OFFSET $offset";

        $result = $conn->query($sql);

        if (!$result) {
             // In a production environment, log the error and show a generic message
            // die('Query failed: ' . $conn->error);
             error_log('Log data query failed: ' . $conn->error); // Log the error
            return []; // Return empty array on failure
        }

        $logData = array();
        while ($row = $result->fetch_assoc()) {
            $logData[] = $row;
        }

        return $logData;
    }

    function getTotalLogCount($conn, $searchTerm = '') {
        $sql = "SELECT COUNT(*) AS total FROM logs_company_linkages l
                     JOIN company_linkages cl ON l.modified_item = cl.uploaded_at -- Corrected table alias to cl
                     JOIN users u ON l.user = u.id";
        if (!empty($searchTerm)) {
            // Using LIKE with wildcards requires manual escaping if not using prepared statements
            $escapedSearchTerm = $conn->real_escape_string($searchTerm);
            $sql .= " WHERE l.type LIKE '%$escapedSearchTerm%'
                                OR cl.company_name LIKE '%$escapedSearchTerm%' -- Corrected table alias to cl
                                OR u.username LIKE '%$escapedSearchTerm%'
                                OR l.date LIKE '%$escapedSearchTerm%'";
        }
        $result = $conn->query($sql);
         if (!$result) {
            // In a production environment, log the error and show a generic message
            // die('Error fetching total count: ' . $conn->error);
             error_log('Total log count query failed: ' . $conn->error); // Log the error
             return 0; // Return 0 on failure
        }
        $row = $result->fetch_assoc();
        return $row['total'];
    }

    $totalLogs = getTotalLogCount($conn, $searchTerm);
    $totalPages = ceil($totalLogs / $itemsPerPage);

     // Ensure currentPage is within valid bounds
    // If totalLogs is 0, totalPages will be 0, so ensure currentPage is at least 1
    $currentPage = max(1, min($currentPage, $totalPages > 0 ? $totalPages : 1));
    $offset = ($currentPage - 1) * $itemsPerPage;


    $logData = getLogData($conn, $itemsPerPage, $offset, $searchTerm, $sortBy, $sortDirection);

    $html = '';

    if (!empty($logData) || $totalLogs > 0) { // Show table header even if no data if total > 0 (filtered)
        $html .= "<table id='logTable'>";
        $html .= "<thead><tr>
                    <th data-sort-by='l.type' class='sortable'>Action</th>
                    <th data-sort-by='cl.company_name' class='sortable'>Company Name</th> <th data-sort-by='u.username' class='sortable'>User</th>
                    <th data-sort-by='l.date' class='sortable'>Date</th>
                  </tr></thead>";
        $html .= "<tbody>";
        if (!empty($logData)) {
            foreach ($logData as $log) {
                $html .= "<tr>";
                $html .= "<td>" . htmlspecialchars($log['log_type']) . "</td>";
                $html .= "<td>" . htmlspecialchars($log['modified_item_title']) . "</td>";
                $html .= "<td>" . htmlspecialchars($log['modified_by_user']) . "</td>";
                $html .= "<td>" . htmlspecialchars($log['log_date']) . "</td>";
                $html .= "</tr>";
            }
        } else {
             $html .= "<tr><td colspan='4'>No matching log data found.</td></tr>";
        }

        $html .= "</tbody>";
        $html .= "</table> <br>";

        // Pagination Links
        if ($totalPages > 1) { // Only show pagination if more than one page
            $html .= "<div class='pagination-links'>";
             // Optional: Add Previous/Next buttons or ellipsis for many pages
             $range = 2; // Number of pages to show around the current page
             $start = max(1, $currentPage - $range);
             $end = min($totalPages, $currentPage + $range);

             if ($start > 1) {
                 // Changed href to '#' and added data-page attribute
                 $html .= "<a href='#' data-page='1'>1</a>";
                 if ($start > 2) {
                     $html .= "<span>...</span>";
                 }
             }

            for ($i = $start; $i <= $end; $i++) {
                $activeClass = ($i == $currentPage) ? 'current' : '';
                // Changed href to '#' and added data-page attribute
                $html .= "<a href='#' data-page='$i' class='$activeClass'>$i</a>";
            }

             if ($end < $totalPages) {
                  if ($end < $totalPages - 1) {
                     $html .= "<span>...</span>";
                 }
                 // Changed href to '#' and added data-page attribute
                 $html .= "<a href='#' data-page='$totalPages'>$totalPages</a>";
            }


            $html .= "</div>";
        }

    } else {
        $html .= "<p>No log data found.</p>";
    }

    return $html;
}

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $currentPage = isset($_POST['page']) ? intval($_POST['page']) : 1; // Ensure integer
    $searchTerm = isset($_POST['search']) ? $_POST['search'] : '';
    $sortBy = isset($_POST['sortBy']) ? $_POST['sortBy'] : 'l.date';
    $sortDirection = isset($_POST['sortDirection']) ? $_POST['sortDirection'] : 'DESC';

    // Input validation for safety
    $currentPage = max(1, $currentPage); // Page number cannot be less than 1
    $validSortColumns = ['l.type', 'cl.company_name', 'u.username', 'l.date']; // Corrected table alias
    $validSortDirections = ['ASC', 'DESC'];
    $sortBy = in_array($sortBy, $validSortColumns) ? $sortBy : 'l.date';
    $sortDirection = in_array(strtoupper($sortDirection), $validSortDirections) ? strtoupper($sortDirection) : 'DESC';


    echo generateLogTableHTML($conn, $itemsPerPage, $currentPage, $searchTerm, $sortBy, $sortDirection);
    exit; // Exit after handling AJAX
}

// Initial page load (handle GET parameters)
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1; // Ensure integer
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'l.date';
$sortDirection = isset($_GET['sortDirection']) ? $_GET['sortDirection'] : 'DESC';

// Input validation for initial GET load
$currentPage = max(1, $currentPage); // Page number cannot be less than 1
$validSortColumns = ['l.type', 'cl.company_name', 'u.username', 'l.date']; // Corrected table alias
$validSortDirections = ['ASC', 'DESC'];
$sortBy = in_array($sortBy, $validSortColumns) ? $sortBy : 'l.date';
$sortDirection = in_array(strtoupper($sortDirection), $validSortDirections) ? strtoupper($sortDirection) : 'DESC';


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs Company Linkages</title>
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

        .searchemp-container {
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            padding: 20px;
            width: 90%; /* Adjust width as needed */
            /* Set a maximum width if desired */
            box-sizing: border-box; /* Include padding in width */
        }

        .searchemp-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .searchemp-container hr {
            border: none;
            height: 1px;
            background-color: #ddd;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .searchemp-container table {
            width: 100%;
            border-collapse: collapse;
            font-family: sans-serif;
            font-size: 14px;
            margin-top: 10px; /* Space between search and table */
        }

        .searchemp-container th,
        .searchemp-container td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .searchemp-container th {
            background-color: #E74C3C;
            color: #fff;
             cursor: pointer; /* Indicate that headers are clickable */
        }

         .searchemp-container th.sortable:hover {
             background-color: #c0392b; /* Darker shade on hover */
         }


        .searchemp-container tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .searchemp-container tr:hover {
            background-color: #ddd;
        }

        .searchemp-container .button {
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

        .searchemp-container .button:hover {
            background-color: #45a049;
        }

        .searchemp-container .returner {
            background-color: #0074D9; /*#39cccc;*/
        }

        .searchemp-container .returner:hover {
            background-color: #005AA6; /*#2d9999;*/
        }

        .searchemp-container .backtodash {
            background-color: #ff851b;
        }

        .searchemp-container .backtodash:hover {
            background-color: #d47716;
        }

        .searchemp-divider {
            width: 30%;
            display: inline-block;
            margin-left: 12.5px;
            margin-right: 12.5px;
            vertical-align: top;
        }

        .searchemp-container .search-pagination {
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
            gap: 10px; /* Space between search input and pagination links */
        }
        .searchemp-container .search-pagination input[type="text"] {
             flex-grow: 1; /* Allow search input to take available space */
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
             max-width: 300px; /* Limit search input width */
        }
         .searchemp-container .pagination-links {
            /* Removed float/display:inline-block styles */
            margin-top: 0; /* Adjusted margin */
            text-align: center;
             display: flex; /* Use flexbox for alignment */
             flex-wrap: wrap; /* Allow links to wrap */
             gap: 5px; /* Space between links */
             justify-content: center; /* Center pagination links */
        }

        .searchemp-container .pagination-links a,
        .searchemp-container .pagination-links span {
            padding: 5px 8px;
            border: 1px solid #ccc;
            /* Removed margin-right */
            text-decoration: none;
            color: #333;
            border-radius: 4px;
            cursor: pointer;
        }
        .searchemp-container .pagination-links a:hover {
            background-color: #ddd;
        }
        .searchemp-container .pagination-links .current {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
         /* Style for ellipsis */
         .searchemp-container .pagination-links span {
             border: none;
             background: none;
             cursor: default;
         }


    </style>
</head>
<body>
    <div class="searchemp-container">
        <h2>Logs Company Linkages</h2>

        <div class="search-pagination">
            <input type="text" id="searchInput" placeholder="Search..." value="<?php echo htmlspecialchars($searchTerm); ?>">
             </div>

        <div id="logTableContainer">
            <?php
                // This initial load includes the table and pagination links
                echo generateLogTableHTML($conn, $itemsPerPage, $currentPage, $searchTerm, $sortBy, $sortDirection);
            ?>
        </div>
        <br><br>
        <div style='width:100%; text-align: center;'> <a href='../dashboard.php' class='button backtodash' style="display: inline-block; width: auto;">Back to Dashboard</a> </div>
    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const logTableContainer = document.getElementById('logTableContainer');
        // Keep track of current state
        let currentPage = <?php echo $currentPage; ?>;
        let currentSearchTerm = '<?php echo htmlspecialchars($searchTerm); ?>';
        let currentSortBy = '<?php echo htmlspecialchars($sortBy); ?>';
        let currentSortDirection = '<?php echo htmlspecialchars($sortDirection); ?>';
        let searchTimeout;

         // Initial search input value sync
         searchInput.value = currentSearchTerm;


        // Event Listener for Search Input
        searchInput.addEventListener('input', function() { // Use 'input' for immediate feedback
            currentSearchTerm = this.value;
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                 // When search term changes, reset to page 1
                 loadLogTableData(1, currentSearchTerm, currentSortBy, currentSortDirection);
            }, 300); // Debounce search
        });

        // Event Listener for Clicks (Handles Sorting Headers and Pagination Links via Delegation)
        logTableContainer.addEventListener('click', function(event) {
            const target = event.target;

            // Handle Sorting Header Clicks
            // Check if target is a TH and has the 'sortable' class
            if (target.tagName === 'TH' && target.classList.contains('sortable')) {
                const sortBy = target.getAttribute('data-sort-by');
                if (sortBy) {
                    // If clicking the same header, toggle direction
                    if (currentSortBy === sortBy) {
                        // Toggle between ASC and DESC
                        currentSortDirection = currentSortDirection.toUpperCase() === 'ASC' ? 'DESC' : 'ASC';
                    } else { // If clicking a different header, set new sort column and default to ASC
                        currentSortBy = sortBy;
                        currentSortDirection = 'ASC'; // Default sort direction for a new column
                    }
                    // Always reset to page 1 when sorting
                    loadLogTableData(1, currentSearchTerm, currentSortBy, currentSortDirection);
                }
            }
            // Handle Pagination Link Clicks (using event delegation)
            // Check if the clicked element is an 'a' tag AND is inside an element with class 'pagination-links'
            else if (target.tagName === 'A' && target.closest('.pagination-links')) {
                event.preventDefault(); // Prevent default link behavior

                const page = target.getAttribute('data-page'); // Get page from data attribute
                if (page) {
                     // Load data for the clicked page, maintaining current search and sort
                    loadLogTableData(parseInt(page), currentSearchTerm, currentSortBy, currentSortDirection);
                }
            }
        });

        // Function to load data via AJAX
        function loadLogTableData(page = currentPage, search = currentSearchTerm, sortBy = currentSortBy, sortDirection = currentSortDirection) {
            console.log(`Loading page: ${page}, Search: "${search}", SortBy: "${sortBy}", SortDirection: "${sortDirection}"`); // Debug log
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest' // Identify as AJAX request
                },
                body: `page=${page}&search=${encodeURIComponent(search)}&sortBy=${encodeURIComponent(sortBy)}&sortDirection=${encodeURIComponent(sortDirection)}`
            })
            .then(response => {
                 if (!response.ok) {
                    // Handle HTTP errors
                    console.error('HTTP error', response.status);
                     // Optionally display an error message on the page
                     logTableContainer.innerHTML = '<p>Error loading data. Please try again.</p>';
                    return Promise.reject('HTTP error: ' + response.status);
                 }
                 return response.text();
             })
            .then(data => {
                 // Replace the entire container's content with the new table and pagination
                logTableContainer.innerHTML = data;

                // Update current state variables after successful load
                currentPage = page;
                currentSearchTerm = search;
                currentSortBy = sortBy;
                currentSortDirection = sortDirection;

                 // Optional: Update URL in browser history (HTML5 History API)
                 // This keeps the URL reflecting the current state without a full reload
                 const newState = { page: currentPage, search: currentSearchTerm, sortBy: currentSortBy, sortDirection: currentSortDirection };
                 // Use history.pushState for new entries, history.replaceState if you don't want a new history entry per click
                 // pushState is generally better for navigation links
                 const newUrl = `?page=${currentPage}&search=${encodeURIComponent(currentSearchTerm)}&sortBy=${encodeURIComponent(currentSortBy)}&sortDirection=${encodeURIComponent(currentSortDirection)}`;
                 history.pushState(newState, '', newUrl);

            })
            .catch(error => {
                console.error('Error loading log data:', error);
                // Optionally display an error message to the user (already done in .then -> !response.ok)
            });
        }

        // Handle browser back/forward button (optional but good for usability)
        window.addEventListener('popstate', function(event) {
            // Load data based on the state from history
            const state = event.state;
            if (state) {
                 // Use state data or fall back to current variables if state is incomplete
                 // Need to be careful with default values if state is partially missing
                const restoredPage = state.page !== undefined ? state.page : currentPage;
                const restoredSearch = state.search !== undefined ? state.search : currentSearchTerm;
                const restoredSortBy = state.sortBy !== undefined ? state.sortBy : currentSortBy;
                const restoredSortDirection = state.sortDirection !== undefined ? state.sortDirection : currentSortDirection;

                loadLogTableData(restoredPage, restoredSearch, restoredSortBy, restoredSortDirection);
            } else {
                // If no state (e.g., initial page load entry, or if state wasn't set initially),
                // load with current variables which would have been set from the initial GET params.
                 loadLogTableData(currentPage, currentSearchTerm, currentSortBy, currentSortDirection);
            }
        });

        // Initial setup: Ensure the search input reflects the GET param on page load
        // This is done by setting its 'value' attribute using PHP in the HTML.
        // The initial table load is also handled by PHP. Subsequent interactions use JS/AJAX.

    </script>

</body>
</html>