<?php
require '../db_connect.php';
session_start();
// If the user is not logged in redirect to the login page...
if (!isset($_SESSION['user_id'])) { // Changed from 'username' to 'user_id' for consistency
	header('Location: ../index.php');
    exit;
}

// Get the user's role from the session
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : ''; // Get the role

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS Department Capstone/Thesis & Document Management System</title>
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
      /* min-height: 100vh; Adjust if needed */
    }

        /* Add a data attribute to the container based on role */
    .searchemp-container {
      background-color: #fff;
      border-radius: 4px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
      padding: 20px;
      width: 95%; /* Adjusted width */
      max-width: 95%; /* Added max-width */
      position: relative;
    }

    .search {
      width: 100%;
      padding: 7.5px;
      margin-bottom: 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
      box-sizing: border-box;
    }

	.searchemp-container .tophead1 {
	  width: 45%; /* Adjusted width */
	  display: inline-block;
      vertical-align: top;
	  margin-left: 12.5px;
	  margin-right: 12.5px;
	}

	.searchemp-container .tophead2 {
	  width: 45%; /* Adjusted width */
	  display: inline-block;
      vertical-align: top;
	  margin-left: 12.5px;
	  margin-right: 12.5px;
	}

    .searchemp-container h2{
      text-align: center;
      margin-bottom: 20px;
    }

	.searchemp-container h3{
      text-align: center;
      margin-bottom: 20px;
    }

	.searchemp-container h4{
      text-align: center;
      margin-bottom: 10px;
    }

	.searchemp-container img {
	  display: block; /* Added to center img */
      margin: 0 auto; /* Added to center img */
      width: 60%; /* Adjust the desired width */
      height: auto; /* Maintain aspect ratio */
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
        table-layout: fixed; /* Added: Fix table layout */
        margin-top: 20px;
    }

    .searchemp-container th,
    .searchemp-container td {
      padding: 10px;
      text-align: left;
      border: 1px solid #ddd;
        word-wrap: break-word; /* Added: Break long words */
        overflow-wrap: break-word; /* Added: Break long words (newer standard) */
    }

    /* Explicitly set widths for columns (Sum should be approx 100%) */
    /* There are 8 columns: 6 data + Edit + Archive */
    .searchemp-container th:nth-child(1), .searchemp-container td:nth-child(1) { width: 12%; } /* Name */
    .searchemp-container th:nth-child(2), .searchemp-container td:nth-child(2) { width: 12%; } /* Email */
    .searchemp-container th:nth-child(3), .searchemp-container td:nth-child(3) { width: 10%; } /* Contact no. */
    .searchemp-container th:nth-child(4), .searchemp-container td:nth-child(4) { width: 15%; } /* Address */
    .searchemp-container th:nth-child(5), .searchemp-container td:nth-child(5) { width: 15%; } /* Job Description */
    .searchemp-container th:nth-child(6), .searchemp-container td:nth-child(6) { width: 15%; } /* Company */
    .searchemp-container th:nth-child(7), .searchemp-container td:nth-child(7) { width: 10%; } /* Edit */
    .searchemp-container th:nth-child(8), .searchemp-container td:nth-child(8) { width: 10%; } /* Archive */


    .searchemp-container th {
      background-color: #E74C3C;
      color: #fff;
      cursor: pointer; /* Indicate sortable columns */
    }

    .searchemp-container th:hover {
      background-color: #c0392b;
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
      background-color: #0074D9;
    }

    .searchemp-container .returner:hover {
      background-color: #005AA6;
    }

	.searchemp-container .backtodash {
      background-color: #ff851b;
    }

    .searchemp-container .backtodash:hover {
      background-color: #d47716;
    }


    /* Styles for action buttons */
    td.buttons,
    td.buttons2 {
      text-align: center;
      padding: 5px;
      /* Ensure buttons/forms within stay centered */
      display: table-cell; /* Explicitly set display */
      vertical-align: middle; /* Vertically align content */
    }

     td.buttons form,
     td.buttons2 form {
         display: inline-block; /* Make forms inline so buttons appear next to each other if needed */
         margin: 0 2px; /* Small margin between form blocks */
     }


    td.buttons button,
    td.buttons2 button {
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      color: #fff;
      cursor: pointer;
      transition: background-color 0.3s ease;
      display: inline-block;
      margin: 2px; /* Small margin around buttons */
      white-space: nowrap; /* Prevent button text from wrapping */
    }

    /* Specific button colors */
    /* Adjusted selectors for clarity */
    .edit-button { background-color: #007bff; } /* Blue */
    .archive-button { background-color: #FF4136; } /* Red */


    td.buttons button:hover,
    td.buttons2 button:hover {
      filter: brightness(120%);
    }

    /* --- Pagination Styles --- */
    .pagination {
        display: flex;
        justify-content: center;
        padding: 15px 0; /* Increased padding */
        list-style: none;
        margin-top: 10px;
        flex-wrap: wrap;
    }

    .pagination-button {
        padding: 8px 12px;
        border: 1px solid #ccc;
        margin: 0 4px;
        text-decoration: none;
        color: #333;
        border-radius: 4px;
        transition: background-color 0.3s ease;
        background-color: #fff;
        cursor: pointer;
        font-size: 14px;
        display: inline-block;
        min-width: 30px;
        text-align: center;
    }

    .pagination-button:hover {
        background-color: #eee;
    }

    .pagination-button.current-page {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
        cursor: default;
        pointer-events: none;
    }

    .pagination-button:disabled {
        color: #ccc;
        pointer-events: none;
        cursor: default;
        background-color: #f9f9f9;
        border-color: #ccc;
    }

    /* --- CSS to hide Archive column for non-Admin roles --- */
    /* Select the container when the data-user-role is NOT "Admin" */
    /* Column is 8th (Archive) */
    .searchemp-container:not([data-user-role="Admin"]) th:nth-child(8), /* Archive */
    .searchemp-container:not([data-user-role="Admin"]) td:nth-child(8) {
        display: none; /* Hide column 8 */
    }

    </style>
    <script>
        let currentPage = 1;
        let rowsPerPage = 10;
        let currentSortBy = 'ojt_full_name';
        let currentSortOrder = 'DESC';
        let currentSearchValue = ''; // Store current search value

        function loadTable() {
            currentSearchValue = document.getElementById("search").value; // Get current search value

            let xhr = new XMLHttpRequest();
            // Pass all parameters to the data retrieval script
            let url = `retrieve_ojt_records.php?search=${encodeURIComponent(currentSearchValue)}&sortBy=${encodeURIComponent(currentSortBy)}&sortOrder=${encodeURIComponent(currentSortOrder)}&page=${currentPage}&rowsPerPage=${rowsPerPage}`;

            xhr.open("GET", url, true);
            xhr.responseType = 'json'; // Expect JSON response

            xhr.onload = function () {
                if (xhr.status == 200) {
                     const response = xhr.response;
                     if (response) {
                         // Assuming response is a JSON object with tableHtml and paginationHtml properties
                        document.getElementById("tableData").innerHTML = response.tableHtml;
                        document.getElementById("paginationControls").innerHTML = response.paginationHtml;
                     } else {
                         console.error("Error: No JSON response received.");
                          // Use max possible colspan for error message (8 columns)
                         document.getElementById("tableData").innerHTML = "<tr><td colspan='8' style='text-align: center;'>Error loading data. Empty response.</td></tr>";
                         document.getElementById("paginationControls").innerHTML = "";
                     }
                } else {
                    console.error("Error loading table:", xhr.status, xhr.statusText);
                     // Use max possible colspan for error message (8 columns)
                    document.getElementById("tableData").innerHTML = `<tr><td colspan='8' style='text-align: center;'>Error loading data: ${xhr.status} ${xhr.statusText}</td></tr>`;
                    document.getElementById("paginationControls").innerHTML = "";
                }
            };

            xhr.onerror = function () {
                console.error("Network error loading table.");
                 // Use max possible colspan for error message (8 columns)
                document.getElementById("tableData").innerHTML = "<tr><td colspan='8' style='text-align: center;'>Network error loading data.</td></tr>";
                document.getElementById("paginationControls").innerHTML = "";
            };
            xhr.send();
        }

        function searchTable() {
            currentPage = 1; // Reset to first page on search
            loadTable();
        }

        function sortTable(column) {
            currentSortOrder = (currentSortBy === column && currentSortOrder === 'ASC') ? 'DESC' : 'ASC';
            currentSortBy = column;
            currentPage = 1; // Reset to first page on sort
            loadTable();
        }

        // This function is called by the buttons generated by PHP
        function changePage(page) {
            currentPage = page;
             // When changing page via pagination buttons, the other parameters
             // (search, sort) are already stored in the JS variables,
             // so we just call loadTable() which uses those variables.
            loadTable();
        }

        // Function for the Archive button (called via onsubmit in the form)
        function confirmDelete() {
            return confirm("Are you sure you want to Archive this record? This action cannot be undone.");
        };

        // Make functions globally accessible for pagination/buttons generated by PHP
        // These need to be global because PHP generates HTML with inline onclicks
        window.changePage = changePage;
        window.confirmDelete = confirmDelete;


        // Call loadTable() when the window finishes loading
        window.onload = loadTable;


    </script>
</head>
<body>
    <div class="searchemp-container" data-user-role="<?php echo htmlspecialchars($user_role); ?>">
    <div class="tophead1">
	<img src="../img/perpetual-logo.png" alt="University Logo"/>
	</div>
	<div class="tophead2">
	<h4>CCS-DOCS</h4>
	<h2>OJT Records</h2>
    <h3>Search: <input type="text" class="search"id="search" placeholder="Search by Name or Company..."></h3>
      <div style="width:30%; display:inline-block; margin-left: 12.5px; margin-right: 12.5px;">
          <a href="add_ojt_records.php" class="button">Add OJT Profile <br></a>
      </div>
      <div style="width:30%; display:inline-block; margin-left: 12.5px; margin-right: 12.5px;">
          <a href="add_ojt_company.php" class="button">Add Companies / Linkages</a>
      </div>
      <div style="width:30%; display:inline-block; margin-left: 12.5px; margin-right: 12.5px;">
          <a href="view_ojt_companies.php" class="button">View Companies / Linkages</a>
      </div>
	</div>
  <hr>
<table>
    <thead>
        <tr>
            <th onclick="sortTable('ojt_full_name')">Name</th>
            <th onclick="sortTable('ojt_email')">Email</th>
            <th onclick="sortTable('ojt_telno')">Contact no.</th>
            <th onclick="sortTable('ojt_address')">Address</th> <th onclick="sortTable('ojt_description')">Job Description</th>
            <th onclick="sortTable('ojt_company')">Company</th>
            <th>Edit</th>
            <th>Archive</th>
        </tr>
    </thead>
    <tbody id="tableData">
        </tbody>
</table>

<div id="paginationControls" class="pagination">
    </div>


<br><br>
	<div style="width:100%; text-align: center;"> <a href="../dashboard.php" class="button backtodash" style="display: inline-block; width: auto;">Back to Dashboard</a> </div>
  </div>
</body>
</html>
