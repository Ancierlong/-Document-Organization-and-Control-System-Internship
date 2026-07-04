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

        .searchemp-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .searchemp-container h3 {
            text-align: center;
            margin-bottom: 20px;
        }

        .searchemp-container h4 {
            text-align: center;
            margin-bottom: 10px;
        }

        .searchemp-container img {
            display: block; /* Added to center img */
            margin: 0 auto; /* Added to center img */
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
            table-layout: fixed; /* Keep fixed layout */
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

        /* Explicitly set widths for columns (Sum should be approx 100%) */
        /* Adjusted widths to sum to 100% for 13 columns */
        .searchemp-container th:nth-child(1), .searchemp-container td:nth-child(1) { width: 12%; } /* Title */
        .searchemp-container th:nth-child(2), .searchemp-container td:nth-child(2) { width: 18%; } /* Description */
        .searchemp-container th:nth-child(3), .searchemp-container td:nth-child(3) { width: 5%; } /* Type */
        .searchemp-container th:nth-child(4), .searchemp-container td:nth-child(4) { width: 7%; } /* Academic Year */
        .searchemp-container th:nth-child(5), .searchemp-container td:nth-child(5) { width: 8%; } /* Date Accomplished */
        .searchemp-container th:nth-child(6), .searchemp-container td:nth-child(6) { width: 8%; } /* Resource Speaker */
        .searchemp-container th:nth-child(7), .searchemp-container td:nth-child(7) { width: 9%; } /* File */
        .searchemp-container th:nth-child(8), .searchemp-container td:nth-child(8) { width: 5%; } /* Evaluation Rating */
        .searchemp-container th:nth-child(9), .searchemp-container td:nth-child(9) { width: 5%; } /* Status */
        .searchemp-container th:nth-child(10), .searchemp-container td:nth-child(10) { width: 5%; } /* Edit */
        .searchemp-container th:nth-child(11), .searchemp-container td:nth-child(11) { width: 6%; } /* Approve */
        .searchemp-container th:nth-child(12), .searchemp-container td:nth-child(12) { width: 6%; } /* Reject */
        .searchemp-container th:nth-child(13), .searchemp-container td:nth-child(13) { width: 6%; } /* Archive */


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
        .approve-button { background-color: #24A534; } /* Green */
        .reject-button { background-color: #FF4136; } /* Red */
        .archive-button { background-color: #FF4136; } /* Red */
        .edit-button { background-color: #007bff; } /* Blue */


        td.buttons button:hover,
        td.buttons2 button:hover {
            filter: brightness(120%);
        }

        /* Status cell styles */
        .status-cell {
            min-width: 80px;
            text-align: center; /* Center status text */
        }

        .rejection-reason {
            font-style: italic;
            font-size: 0.9em;
            color: #555;
            /* Removed display: block; to make it inline */
            margin-top: 0; /* Removed top margin */
            margin-left: 5px; /* Added left margin for spacing from status text */
            display: block; /* Added display block back for clarity */
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

        /* --- CSS to hide Approve, Reject, Archive columns for non-Admin roles --- */
        /* Select the container when the data-user-role is NOT "Admin" */
        /* Columns are 11th (Approve), 12th (Reject), and 13th (Archive) */
        .searchemp-container:not([data-user-role="Admin"]) th:nth-child(11), /* Approve */
        .searchemp-container:not([data-user-role="Admin"]) td:nth-child(11),
        .searchemp-container:not([data-user-role="Admin"]) th:nth-child(12), /* Reject */
        .searchemp-container:not([data-user-role="Admin"]) td:nth-child(12),
        .searchemp-container:not([data-user-role="Admin"]) th:nth-child(13), /* Archive */
        .searchemp-container:not([data-user-role="Admin"]) td:nth-child(13) {
            display: none; /* Hide columns 11, 12, and 13 */
        }

        /* --- Modal Overlay Styles --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7); /* Semi-transparent black background */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000; /* Ensure it's on top of other content */
            visibility: hidden; /* Hidden by default */
            opacity: 0;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .modal-overlay.visible {
            visibility: visible;
            opacity: 1;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
            max-width: 400px; /* Max width for the modal box */
            width: 90%; /* Responsive width */
            text-align: center;
            transform: scale(0.9); /* Start slightly smaller */
            transition: transform 0.3s ease;
        }

         .modal-overlay.visible .modal-content {
             transform: scale(1); /* Scale to normal when visible */
         }

        .modal-content h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
        }

        .modal-content textarea {
            width: calc(100% - 22px); /* Adjust for padding and border */
            min-height: 80px;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Include padding and border in element's total width and height */
            resize: vertical; /* Allow vertical resizing */
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        .modal-content .button-row {
            display: flex;
            justify-content: center;
            gap: 10px; /* Space between buttons */
        }

        .modal-content button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 14px;
        }

        .modal-content .confirm-reject-button {
            background-color: #FF4136; /* Red */
            color: white;
        }

        .modal-content .confirm-reject-button:hover {
            background-color: #c0392b;
        }

        .modal-content .cancel-reject-button {
            background-color: #ccc; /* Gray */
            color: #333;
        }

        .modal-content .cancel-reject-button:hover {
            background-color: #bbb;
        }


    </style>
</head>
<body>
    <div class="searchemp-container" data-user-role="<?php echo htmlspecialchars($user_role); ?>">
        <div class="tophead1">
            <img src="../img/perpetual-logo.png" alt="University Logo" />
        </div>
        <div class="tophead2">
            <h4>CCS-DOCS</h4>
            <h2>Concept Paper List</h2>
            <h3>Search: <input type="text" class="search" id="search"
                    placeholder="Search by Title or Date..."></h3>
            <a href="add_concept_papers.php" class="button">Add Concept Paper</a>
        </div>

        <hr>
        <table>
            <thead>
                <tr>
                    <th onclick="sortTable('concept_title')">Title</th>
                    <th onclick="sortTable('concept_description')">Description</th>
                    <th onclick="sortTable('concept_type')">Type</th>
                    <th onclick="sortTable('academic_year')">Academic Year</th>
                    <th onclick="sortTable('concept_date')">Date Accomplished<br>(yyyy/mm/dd)</th>
                    <th onclick="sortTable('concept_resource_speaker')">Resource Speaker</th>
                    <th onclick="sortTable('file_name')">File</th>
                    <th onclick="sortTable('concept_evaluation_rating')">Evaluation Rating</th>
                    <th onclick="sortTable('status')" class="status-cell-header">Status</th>
                    <th>Edit</th>
                    <th>Approve</th>
                    <th>Reject</th>
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

    <div class="modal-overlay" id="rejectionModalOverlay">
        <div class="modal-content">
            <h3>Provide Rejection Reason</h3>
            <textarea id="rejectionReasonInputModal" placeholder="Enter reason for rejection..."></textarea>
            <div class="button-row">
                <button type="button" class="confirm-reject-button" id="confirmRejectModalButton">Confirm Reject</button>
                <button type="button" class="cancel-reject-button" id="cancelRejectModalButton">Cancel</button>
            </div>
        </div>
    </div>
    <script>
        let currentPage = 1;
        let rowsPerPage = 10;
        let currentSortBy = 'concept_date';
        let currentSortOrder = 'DESC';
        let currentSearchValue = ''; // Store current search value

        // Get modal elements
        const rejectionModalOverlay = document.getElementById('rejectionModalOverlay');
        const rejectionReasonInputModal = document.getElementById('rejectionReasonInputModal');
        const confirmRejectModalButton = document.getElementById('confirmRejectModalButton');
        const cancelRejectModalButton = document.getElementById('cancelRejectModalButton');

        // Variable to store the ID and uploaded_at data for the item being rejected
        let currentRejectItemData = null; // Use an object to store multiple pieces of data


        function loadTable() {
            currentSearchValue = document.getElementById("search").value; // Get current search value

            let xhr = new XMLHttpRequest();
            // Pass all parameters to the data retrieval script
            let url = `retrieve_concept_papers.php?search=${encodeURIComponent(currentSearchValue)}&sortBy=${encodeURIComponent(currentSortBy)}&sortOrder=${encodeURIComponent(currentSortOrder)}&page=${currentPage}&rowsPerPage=${rowsPerPage}`;

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
                         // Use max possible colspan for error message
                         document.getElementById("tableData").innerHTML = "<tr><td colspan='13' style='text-align: center;'>Error loading data. Empty response.</td></tr>";
                         document.getElementById("paginationControls").innerHTML = "";
                     }
                } else {
                    console.error("Error loading table:", xhr.status, xhr.statusText);
                    // Use max possible colspan for error message
                    document.getElementById("tableData").innerHTML = `<tr><td colspan='13' style='text-align: center;'>Error loading data: ${xhr.status} ${xhr.statusText}</td></tr>`;
                    document.getElementById("paginationControls").innerHTML = "";
                }
            };

            xhr.onerror = function () {
                console.error("Network error loading table.");
                 // Use max possible colspan for error message
                document.getElementById("tableData").innerHTML = "<tr><td colspan='13' style='text-align: center;'>Network error loading data.</td></tr>";
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

        // Helper function to show the modal
        // Now accepts an object containing the item data (id and uploaded_at)
        function showRejectionModal(itemData) {
            currentRejectItemData = itemData; // Store the data object {id: ..., uploaded_at: ...}
            rejectionReasonInputModal.value = ''; // Clear previous input
            rejectionModalOverlay.classList.add('visible'); // Show the modal
            rejectionReasonInputModal.focus(); // Focus the textarea
        }

        // Helper function to hide the modal
        function hideRejectionModal() {
            rejectionModalOverlay.classList.remove('visible'); // Hide the modal
            currentRejectItemData = null; // Clear the stored data
            rejectionReasonInputModal.value = ''; // Clear the textarea
            // Re-enable buttons if they were disabled
            confirmRejectModalButton.disabled = false;
            cancelRejectModalButton.disabled = false;
        }

        // Function to handle the rejection process via AJAX
        function processRejection() {
            // Get data from the stored object
            const id = currentRejectItemData ? currentRejectItemData.id : null;
            // Get the uploaded_at value from the stored data
            const uploaded_at = currentRejectItemData ? currentRejectItemData.uploaded_at : null;
            const rejectionReason = rejectionReasonInputModal.value.trim();

            if (!id) {
                console.error("No item data stored for rejection.");
                hideRejectionModal();
                return;
            }

            if (!rejectionReason) {
                alert('Please provide a reason for rejection.');
                rejectionReasonInputModal.focus();
                return;
            }

            // Optional: Check if uploaded_at is missing before sending
            if (!uploaded_at) {
                 console.warn("Uploaded_at value is missing for rejection log for ID:", id);
                 // Decide if you want to stop here or send it as null/empty string.
                 // Sending it as null or an empty string matches the revert logic
                 // in reject_concept_paper.php if $_POST['uploaded_at'] is not set.
            }


            // Disable buttons while processing
            confirmRejectModalButton.disabled = true;
            cancelRejectModalButton.disabled = true;

            // Send AJAX request to reject_concept_paper.php
            fetch('reject_concept_paper.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    id: id,
                    rejection_reason: rejectionReason,
                    uploaded_at: uploaded_at // *** INCLUDE uploaded_at in the POST data ***
                })
            })
            .then(response => {
                 // Always attempt to read as text first to debug non-JSON responses
                 return response.text().then(text => ({
                     ok: response.ok,
                     status: response.status,
                     text: text,
                     contentType: response.headers.get('content-type')
                 }));
            })
             .then(data => {
                 if (!data.ok) {
                      console.error('HTTP Error:', data.status, data.text);
                      // Try parsing JSON from the error response if it's JSON
                      if (data.contentType && data.contentType.includes('application/json')) {
                           try {
                               const errorJson = JSON.parse(data.text);
                               throw new Error(`HTTP error! status: ${data.status}, message: ${errorJson.message || data.text}`);
                           } catch (e) {
                               // Failed to parse error JSON, throw original text
                               throw new Error(`HTTP error! status: ${data.status}, response: ${data.text}`);
                           }
                      } else {
                           // Not JSON, just throw the text
                           throw new Error(`HTTP error! status: ${data.status}, response: ${data.text}`);
                      }
                 }

                 // Try parsing JSON from the success response
                 try {
                      const jsonData = JSON.parse(data.text);
                       if (jsonData.success) {
                           alert(jsonData.message); // Show success message
                            loadTable(); // Reload table to reflect changes
                       } else {
                           // Handle error message from PHP, might still be JSON but indicate failure
                            const errorMessage = jsonData.message || 'Unknown error occurred.';
                            alert('Rejection failed: ' + errorMessage);
                       }
                 } catch (jsonError) {
                      console.error('JSON Parsing Error:', jsonError, 'Response text:', data.text);
                      alert('An unexpected error occurred. Could not parse server response.');
                 }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('An error occurred while submitting the rejection: ' + error.message); // Show specific error message
            })
            .finally(() => {
                // Always hide the modal and re-enable buttons regardless of success or failure
                hideRejectionModal();
            });
        }

        // Function to handle Approve action via AJAX (NOTE: This is commented out in the PHP, current approve is a form submit)
        // If you want Approve to be AJAX, uncomment this and modify the Approve button HTML in retrieve_concept_papers.php
        /*
        function processApprove(id, uploaded_at) { // Added uploaded_at parameter if needed
             if (!confirm("Are you sure you want to Approve this concept paper?")) {
                return; // Do nothing if user cancels
             }

             // Send AJAX request to approve_concept_paper.php (assuming this file exists and handles the update)
             fetch('approve_concept_paper.php', {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/x-www-form-urlencoded',
                 },
                 body: new URLSearchParams({
                     id: id,
                     uploaded_at: uploaded_at // Include uploaded_at if approve_concept_paper.php needs it
                 })
             })
             .then(response => {
                  return response.text().then(text => ({
                     ok: response.ok,
                     status: response.status,
                     text: text,
                     contentType: response.headers.get('content-type')
                 }));
             })
             .then(data => {
                 if (!data.ok) {
                      console.error('HTTP Error:', data.status, data.text);
                      // Handle error response similarly to rejection
                      if (data.contentType && data.contentType.includes('application/json')) {
                           try {
                               const errorJson = JSON.parse(data.text);
                               throw new Error(`HTTP error! status: ${data.status}, message: ${errorJson.message || data.text}`);
                           } catch (e) {
                               throw new Error(`HTTP error! status: ${data.status}, response: ${data.text}`);
                           }
                      } else {
                           throw new Error(`HTTP error! status: ${data.status}, response: ${data.text}`);
                      }
                 }

                 try {
                      const jsonData = JSON.parse(data.text);
                       if (jsonData.success) {
                           alert(jsonData.message); // Show success message
                            loadTable(); // Reload table to reflect changes
                       } else {
                           const errorMessage = jsonData.message || 'Unknown error occurred.';
                           alert('Approval failed: ' + errorMessage);
                       }
                 } catch (jsonError) {
                      console.error('JSON Parsing Error:', jsonError, 'Response text:', data.text);
                      alert('An unexpected error occurred. Could not parse server response.');
                 }
             })
             .catch(error => {
                 console.error('Fetch error:', error);
                 alert('An error occurred while submitting the approval: ' + error.message);
             });
         }
        */


        // Event Delegation function to handle clicks on dynamic buttons
        function setupActionListeners() {
            const tableBody = document.getElementById('tableData');
            if (!tableBody) {
                console.error("Table body with id 'tableData' not found.");
                return;
            }

            tableBody.addEventListener('click', function (event) {
                const target = event.target;

                // Handle Reject button click - Show the modal
                if (target.classList.contains('reject-button')) { // Use classList.contains for robustness
                    const id = target.dataset.id; // Get data-id
                    const uploaded_at = target.dataset.uploaded_at; // *** GET DATA-UPLOADED_AT ATTRIBUTE ***

                    if (id) { // Ensure id exists
                         // Pass an object containing both id and uploaded_at to the modal show function
                         showRejectionModal({ id: id, uploaded_at: uploaded_at });
                    } else {
                         console.error("Reject button clicked but no data-id found.");
                    }
                }

                 // Handle Approve button click (remains unchanged, form submission)
                 // Note: The approve button is currently inside a form that submits to pdffinalv2.php
                 // This JS listener will fire, but the default form submission will happen unless prevented.
                 // The uploaded_at is already in a hidden input in that form generated by PHP.
                if (target.classList.contains('approve-button')) {
                     const id = target.dataset.id;
                     // If you switch to AJAX for Approve, uncomment the block below
                     /*
                     const uploaded_at = target.dataset.uploaded_at; // Assuming you add this data attribute
                     if (id) {
                          processApprove(id, uploaded_at); // Call the approve function
                     } else {
                          console.error("Approve button clicked but no data-id found.");
                     }
                     */
                 }

                 // Handle Archive button click (remains unchanged, form submission)
                 if (target.classList.contains('archive-button')) {
                     // Handled by form submit and confirmDelete function
                 }


            });

             // Delegation for pagination buttons (remains unchanged)
             const paginationDiv = document.getElementById('paginationControls');
             if(paginationDiv) {
                 paginationDiv.addEventListener('click', function(event) {
                     const target = event.target;
                     if (target.classList.contains('pagination-button') && !target.classList.contains('current-page') && !target.disabled) {
                          // Handled by inline onclick in PHP generated HTML calling changePage(page_number)
                     }
                 });
             }
        }


        // Add event listeners for the modal buttons (remains unchanged)
        confirmRejectModalButton.addEventListener('click', processRejection);
        cancelRejectModalButton.addEventListener('click', hideRejectionModal);

        // Optional: Close modal if clicking outside the content (remains unchanged)
        rejectionModalOverlay.addEventListener('click', function(event) {
            if (event.target === rejectionModalOverlay) {
                hideRejectionModal();
            }
        });


        // Initial load and event listeners setup (remains unchanged)
        window.onload = function () {
            loadTable(); // This loads the initial data and pagination
            document.getElementById("search").addEventListener("input", searchTable); // Real-time search
            // setupActionListeners() is called by DOMContentLoaded now
        };

        // Attach the single delegation listener when the DOM is ready (remains unchanged)
        document.addEventListener('DOMContentLoaded', function () {
            setupActionListeners(); // Attach the delegated listener handler
        });


        // Function for the Archive button (called via onsubmit in the form) (remains unchanged)
        function confirmDelete() {
            return confirm("Are you sure you want to Archive this record? This action cannot be undone.");
        };

        // Helper function to escape HTML for displaying rejection reason safely (remains unchanged)
        function escapeHTML(str) {
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        }

        // Make functions globally accessible for pagination/buttons generated by PHP (remains unchanged)
        // These need to be global because PHP generates HTML with inline onclicks
        window.changePage = changePage;
        window.confirmDelete = confirmDelete;
        // window.showRejectionModal = showRejectionModal; // No longer needs to be global if only called by setupActionListeners

    </script>
</body>

</html>