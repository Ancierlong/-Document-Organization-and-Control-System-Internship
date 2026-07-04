<?php
require 'db_connect.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCS Department Document Management System</title>
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
      width: 100%;
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
	  width: 50%;
	  display: inline-block;
      vertical-align: top;
	  margin-left: 12.5px;
	  margin-right: 12.5px;
	}

	.searchemp-container .tophead2 {
	  width: 40%;
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
	  align: center;
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

    td.buttons {
      text-align: center;
    }

    td.buttons button {
      padding: 8px 16px;
      margin-right: 8px;
      border: none;
      border-radius: 4px;
      background-color: #24A534;
      color: #fff;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    
    td.buttons button.medbtn {
      background-color: #FF4136;
    }

    td.buttons button:hover {
      filter: brightness(120%);
    }
    .searchemp-container .pagination {
            display: flex; /* Use flexbox for centering */
            justify-content: center; /* Center the pagination */
            padding: 10px 0; /* Add padding */
            list-style: none; /* Keep none, though it's a div not ul */
            margin-top: 10px; /* Match margin from logs page */
        }

        .searchemp-container .pagination-button {
            /* Apply button styles here */
            padding: 5px 8px; /* Match padding from logs page */
            border: 1px solid #ccc; /* Match border from logs page */
            margin: 0 5px; /* Add margin between buttons */
            text-decoration: none; /* Buttons don't have text decoration, but keep for consistency */
            color: #333; /* Default text color */
            border-radius: 4px; /* Match border radius from logs page */
            transition: background-color 0.3s ease;
            background-color: #fff; /* Default background */
            cursor: pointer;
            font-size: 14px; /* Adjust font size if needed */
        }

        .searchemp-container .pagination-button:hover {
            background-color: #ddd; /* Match hover background */
        }

        .searchemp-container .pagination-button.current-page {
            background-color: #007bff; /* Match active background color */
            color: white; /* Match active text color */
            border-color: #007bff; /* Match active border color */
            cursor: default;
            pointer-events: none; /* Disable clicks on the current page button */
        }

         /* Style for disabled buttons (Next/Previous, though not present in this structure yet) */
        .searchemp-container .pagination button[disabled]:not(.current-page) {
             color: #ccc;
            pointer-events: none;
            cursor: default;
            background-color: #f9f9f9;
            border-color: #ccc;
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
            xhr.open("GET", `retrieve_capstone_thesis_public_view.php?search=${searchValue}&sortBy=${currentSortBy}&sortOrder=${currentSortOrder}&page=${currentPage}&rowsPerPage=${rowsPerPage}`, true);
            xhr.onload = function () {
                if (xhr.status == 200) {
                    document.getElementById("tableData").innerHTML = xhr.responseText;
                }
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
            loadTable();
        }

        function changePage(page) {
            currentPage = page;
            loadTable();
        }

        window.onload = function () {
            loadTable();
            document.getElementById("search").addEventListener("input", searchTable); // Real-time search
        };
    </script>
</head>
<body>
<div class="searchemp-container">
    <div class="tophead1">
	<img src="../img\perpetual-logo.png" />
	</div>
	<div class="tophead2">
	<h4>CCS-DOCS</h4>
	<h2>Capstone/Thesis List</h2>
    <h3>Search: <input type="text" id="search" class="search" placeholder="Search by Title or Year..."></h3>
	</div>
	<hr>

<table class="table">
    <tr>
        <th onclick="sortTable('projecttype')">Project Type</th>
        <th onclick="sortTable('projecttitle')">Title</th>
        <th onclick="sortTable('projectdescription')">Description</th>
        <th onclick="sortTable('projectcategory')">Category</th>
        <th onclick="sortTable('projectyear')">Year</th>
        <th onclick="sortTable('projectproponents')">Proponents</th>
        <th onclick="sortTable('projectrecommendation')">Recommendation</th>
        <th>File</th>
    </tr>
    <tbody id="tableData"></tbody>
</table>

<div class="pagination" id="pagination"></div>


<br><br>
	<div style="width:100%; display:inline-block; margin-left: 15px; margin-right: 15px;">
	<a href="index.php" class="button backtodash">Back to Login</a>
	</div>
  </div>
</body>
</html>
 