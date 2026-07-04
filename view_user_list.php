<?php
require '../db_connect.php';
session_start();

// If the user is not logged in, redirect to the login page.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Get the user's role from the session
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : ''; // Get the role

// Display alert message if set in session
if (isset($_SESSION['message'])) {
    echo "<script>alert('" . htmlspecialchars($_SESSION['message'], ENT_QUOTES, 'UTF-8') . "');</script>";
    unset($_SESSION['message']); // Remove message after displaying
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS Department Document Management System - User List</title>
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
            /* height: 100vh; */ /* Use min-height if content can exceed viewport */
            min-height: 95vh; /* Added min-height */
        }

        /* Add a data attribute to the container based on role */
        .searchemp-container {
            background-color: #fff;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            padding: 20px;
            width: 95%; /* Increased width for better table display */
             /* Added max-width */
            box-sizing: border-box; /* Include padding in width */
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
            width: 45%;
            display: inline-block;
            vertical-align: top;
            margin-left: 12.5px;
            margin-right: 12.5px;
        }

        .searchemp-container .tophead2 {
            width: 45%;
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
            display: block;
            margin: 0 auto;
            width: 60%;
            height: auto;
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
            margin-top: 20px;
        }

        .searchemp-container th,
        .searchemp-container td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
            word-wrap: break-word; /* Ensure text wraps */
            overflow-wrap: break-word; /* Ensure text wraps */
        }

        .searchemp-container th {
            background-color: #E74C3C;
            color: #fff;
            cursor: pointer;
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

        .searchemp-divider {
            width: 30%;
            display: inline-block;
            margin-left: 12.5px;
            margin-right: 12.5px;
            vertical-align: top;
        }

        /* Center content in action columns */
        td.buttons2 { /* Applies to Change Role and Reset Password columns */
          text-align: center;
          white-space: nowrap; /* Prevent wrapping in button cells */
        }

        td.buttons2 form { /* Style forms within button cells */
           display: inline-block;
           margin: 0 2px; /* Adjust spacing between forms */
        }

        td.buttons2 button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        td.buttons2 button:hover {
            filter: brightness(120%);
        }

        /* Specific button colors */
        td.buttons2 button[value="Admin"] { background-color: Red; }
        td.buttons2 button[value="Faculty"] { background-color: Green; }
        td.buttons2 button[value="Council"] { background-color: Blue; }
        td.buttons2 button[name="submit"] { background-color: #ff851b; } /* Reset Password button */


        .grayed {
            opacity: 0.5;
            pointer-events: none;
            cursor: not-allowed;
        }

        .buttons-container { /* Container for role change buttons */
          display: flex;
          flex-direction: column; /* Stack buttons vertically if needed */
          gap: 5px; /* Space between buttons */
          align-items: center; /* Center buttons horizontally */
        }

         .buttons-container form { /* Make forms take full width within container */
            width: 100%;
            display: block; /* Stack forms vertically */
        }

         .buttons-container button { /* Make buttons take full width within forms */
            width: 100%;
         }


        .role-admin { color: red; font-weight: bold; }
        .role-faculty { color: green; font-weight: bold; }
        .role-council { color: blue; font-weight: bold; }

        /* --- Pagination Styles (Copied from capstone thesis) --- */
        .pagination {
            display: flex;
            justify-content: center;
            padding: 15px 0;
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

        /* --- CSS to hide Change Role and Reset Password columns for non-Admin/Faculty roles --- */
        /* Select the container when the data-user-role is NOT "Admin" AND NOT "Faculty" */
        .searchemp-container:not([data-user-role*="Admin"]):not([data-user-role*="Faculty"]) th:nth-child(8), /* Reset Password column header */
        .searchemp-container:not([data-user-role*="Admin"]):not([data-user-role*="Faculty"]) td:nth-child(8) { /* Reset Password column data */
            display: none; /* Hide the column */
        }
         /* Keep Change Role column hidden for non-Admin as before */
        .searchemp-container:not([data-user-role*="Admin"]) th:nth-child(7), /* Change Role column header */
        .searchemp-container:not([data-user-role*="Admin"]) td:nth-child(7) { /* Change Role column data */
            display: none; /* Hide the column */
        }
    </style>
    <script>
        let currentPage = 1;
        let rowsPerPage = 10; // You can adjust this default
        let currentSortBy = 'role'; // Default sort column
        let currentSortOrder = 'ASC'; // Default sort order

        function loadTable() {
            let searchValue = document.getElementById("search").value;

            let xhr = new XMLHttpRequest();
            let url = `retrieve_users.php?search=${encodeURIComponent(searchValue)}&sortBy=${encodeURIComponent(currentSortBy)}&sortOrder=${encodeURIComponent(currentSortOrder)}&page=${currentPage}&rowsPerPage=${rowsPerPage}`;

            xhr.open("GET", url, true);
            xhr.responseType = 'json'; // Expect JSON response

            xhr.onload = function () {
                if (xhr.status == 200) {
                    const response = xhr.response;
                    if (response) {
                        document.getElementById("tableData").innerHTML = response.tableHtml;
                        document.getElementById("paginationControls").innerHTML = response.paginationHtml;
                    } else {
                        document.getElementById("tableData").innerHTML = "<tr><td colspan='8'>Error loading data.</td></tr>";
                        document.getElementById("paginationControls").innerHTML = "";
                    }
                } else {
                    document.getElementById("tableData").innerHTML = `<tr><td colspan='8'>Error loading data: ${xhr.status} ${xhr.statusText}</td></tr>`;
                    document.getElementById("paginationControls").innerHTML = "";
                }
            };

            xhr.onerror = function() {
                document.getElementById("tableData").innerHTML = "<tr><td colspan='8'>Network error when loading data.</td></tr>";
                document.getElementById("paginationControls").innerHTML = "";
            };

            xhr.send();
        }

        function searchTable() {
            currentPage = 1; // Reset to first page on new search
            loadTable();
        }

        function sortTable(column) {
            // Toggle sort order if clicking the same column, otherwise default to ASC
            currentSortOrder = (currentSortBy === column && currentSortOrder === 'ASC') ? 'DESC' : 'ASC';
            currentSortBy = column;
            currentPage = 1; // Reset to first page on sort
            loadTable();
        }

        // This function is called by the pagination buttons generated in retrieve_users.php
        function changePage(page) {
            currentPage = page;
            loadTable();
        }

        // Initial load and event listener for search input
        window.onload = function () {
            loadTable();
            document.getElementById("search").addEventListener("input", searchTable); // Real-time search
        };

        // Function for password reset confirmation - Now accepts username
        function confirmResetPassword(username) {
            return confirm(`Are you sure you want to reset the password for user "${username}"?`);
        }
    </script>
</head>
<body>
<div class="searchemp-container" data-user-role="<?php echo htmlspecialchars($user_role, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="tophead1">
        <img src="../img/perpetual-logo.png" alt="School Logo" />
    </div>
    <div class="tophead2">
        <h4>CCS-DOCS</h4>
        <h2>User List</h2>
        <h3>Search: <input type="text" class="search" id="search" placeholder="Search by Role or Name..."></h3>
        <a href="add_user2.php" class="button">Add User</a>
    </div>
    <hr>

    <table class="table">
        <thead>
            <tr>
                <th onclick="sortTable('role')">Role</th>
                <th onclick="sortTable('username')">User Name</th>
                <th onclick="sortTable('full_name')">Name</th>
                <th onclick="sortTable('email')">Email</th>
                <th onclick="sortTable('reg_date')">Registered Date</th>
                <th onclick="sortTable('created_by')">Created By</th>
                <th>Change Role</th>
                <th>Reset Password</th>
            </tr>
        </thead>
        <tbody id="tableData">
            <tr>
                <td colspan='8' style='text-align: center;'>Loading users...</td>
            </tr>
        </tbody>
    </table>

    <div id="paginationControls" class="pagination">
        </div>

    <br><br>
    <div style="width:100%; text-align: center;">
        <a href="../dashboard.php" class="button backtodash" style="display: inline-block; width: auto;">Back to Dashboard</a>
    </div>
</div>
</body>
</html>