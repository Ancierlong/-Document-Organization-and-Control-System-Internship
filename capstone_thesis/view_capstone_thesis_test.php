<?php
require '../db_connect.php';
session_start();
// If the user is not logged in redirect to the login page...
if (!isset($_SESSION['user_id'])) {
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
    /*height: 100vh;*/
}

/* Add a data attribute to the container based on role */
.searchemp-container {
    background-color: #fff;
    border-radius: 4px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
    padding: 20px;
    width: 95%;
    
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
    word-wrap: break-word;
    overflow-wrap: break-word;
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

/* Center content in action/file columns */
td.buttons, td.buttons2, td:last-child:not(.buttons):not(.buttons2) {
    text-align: center;
    white-space: nowrap;
}

td.buttons button {
    padding: 8px 16px;
    margin: 2px;
    border: none;
    border-radius: 4px;
    background-color: #24A534;
    color: #fff;
    cursor: pointer;
    transition: background-color 0.3s ease;
    display: inline-block;
}
td.buttons2 button {
    padding: 8px 16px;
    margin: 2px;
    border: none;
    border-radius: 4px;
    background-color: #FF4136;
    color: #fff;
    cursor: pointer;
    transition: background-color 0.3s ease;
    display: inline-block;
}

td.buttons button.medbtn {
    background-color: #FF4136;
}

td.buttons button:hover {
    filter: brightness(120%);
}
td.buttons2 button:hover {
    filter: brightness(120%);
}

/* --- Pagination Styles --- */
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

/* --- CSS to hide Archive column for non-Admin roles --- */
/* Select the container when the data-user-role is NOT "Admin" */
.searchemp-container:not([data-user-role="Admin"]) th:nth-child(10),
.searchemp-container:not([data-user-role="Admin"]) td:nth-child(10) {
    display: none; /* Hide the 10th column (Archive) */
}


</style>
<script>
let currentPage = 1;
let rowsPerPage = 10;
let currentSortBy = 'projectyear';
let currentSortOrder = 'DESC';

function loadTable() {
    let searchValue = document.getElementById("search").value;

    let xhr = new XMLHttpRequest();
    let url = `retrieve_capstone_thesis.php?search=${encodeURIComponent(searchValue)}&sortBy=${encodeURIComponent(currentSortBy)}&sortOrder=${encodeURIComponent(currentSortOrder)}&page=${currentPage}&rowsPerPage=${rowsPerPage}`;

    xhr.open("GET", url, true);
    xhr.responseType = 'json';

    xhr.onload = function () {
        if (xhr.status == 200) {
            const response = xhr.response;
            if (response) {
                document.getElementById("tableData").innerHTML = response.tableHtml;
                document.getElementById("paginationControls").innerHTML = response.paginationHtml;
            } else {
                document.getElementById("tableData").innerHTML = "<tr><td colspan='10'>Error loading data.</td></tr>";
                document.getElementById("paginationControls").innerHTML = "";
            }
        } else {
            document.getElementById("tableData").innerHTML = `<tr><td colspan='10'>Error loading data: ${xhr.status} ${xhr.statusText}</td></tr>`;
            document.getElementById("paginationControls").innerHTML = "";
        }
    };

     xhr.onerror = function() {
        document.getElementById("tableData").innerHTML = "<tr><td colspan='10'>Network error when loading data.</td></tr>";
        document.getElementById("paginationControls").innerHTML = "";
    };

    xhr.send();
}

function searchTable() {
    currentPage = 1;
    loadTable();
}

function sortTable(column) {
    currentSortOrder = (currentSortBy === column && currentSortOrder === 'ASC') ? 'DESC' : 'ASC';
    currentSortBy = column;
    currentPage = 1;
    loadTable();
}

function changePage(page) {
    currentPage = page;
    loadTable();
}

window.onload = function () {
    loadTable();
    document.getElementById("search").addEventListener("input", searchTable);
}

function confirmDelete() {
    return confirm("Are you sure you want to Archive this record?");
};
</script>
</head>
<body>
<div class="searchemp-container" data-user-role="<?php echo htmlspecialchars($user_role); ?>">
    <div class="tophead1">
        <img src="../img/perpetual-logo.png" alt="School Logo" />
    </div>
    <div class="tophead2">
        <h4>CCS-DOCS</h4>
        <h2>Capstone / Thesis List</h2>
        <h3>Search: <input type="text" class="search" id="search" placeholder="Search..."></h3>
        <a href="add_capstone_thesis.php" class="button">Add Capstone/Thesis</a>
    </div>
    <hr>

    <table class="table">
        <thead>
            <tr>
                <th onclick="sortTable('projecttype')">Project Type</th>
                <th onclick="sortTable('projecttitle')">Title</th>
                <th onclick="sortTable('projectdescription')">Description</th>
                <th onclick="sortTable('projectcategory')">Category</th>
                <th onclick="sortTable('projectyear')">Year</th>
                <th onclick="sortTable('projectproponents')">Proponents</th>
                <th onclick="sortTable('projectrecommendation')">Recommendation</th>
                <th>File</th>
                <th>Edit</th>
                <th>Archive</th> </tr>
        </thead>
        <tbody id="tableData">
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