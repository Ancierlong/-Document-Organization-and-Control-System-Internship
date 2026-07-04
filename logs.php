<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Table</title>
    <style>
        body {
            font-family: sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            cursor: pointer; /* Indicate sortable columns */
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        /* Add more CSS for better styling as needed */
    </style>
</head>
<body>
    <h1>Log Table</h1>

    <?php

    require 'db_connect.php'; // Include the database connection file

    function getLogData($conn) {
        // Execute the query using the provided connection
        $sql = "
            SELECT
                l.type AS log_type,
                tcp.projecttitle AS modified_item_title,
                u.username AS modified_by_user,
                l.date AS log_date
            FROM
                logs_thesis_capstone_projects l
            JOIN
                thesiscapstoneprojects tcp ON l.modified_item = tcp.uploaded_at
            JOIN
                users u ON l.user = u.id
            ORDER BY
                l.date DESC;
        ";
        $result = $conn->query($sql);

        if (!$result) {
            die('Query failed: ' . $conn->error);
        }

        // Fetch the results
        $logData = array();
        while ($row = $result->fetch_assoc()) {
            $logData[] = $row;
        }

        return $logData;
    }

    try {
        // Assuming $conn is established in db_connect.php
        $logData = getLogData($conn);

        if (!empty($logData)) {
            echo "<table>";
            echo "<thead><tr><th>Type</th><th>Modified Item</th><th>User</th><th>Date</th></tr></thead>";
            echo "<tbody>";
            foreach ($logData as $log) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($log['log_type']) . "</td>";
                echo "<td>" . htmlspecialchars($log['modified_item_title']) . "</td>";
                echo "<td>" . htmlspecialchars($log['modified_by_user']) . "</td>";
                echo "<td>" . htmlspecialchars($log['log_date']) . "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
        } else {
            echo "<p>No log data found.</p>";
        }

    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // You might want to close the connection outside the try-catch block if needed
    // if ($conn) {
    //     $conn->close();
    // }

    ?>

    <script>
        // Basic JavaScript for making the table interactive (e.g., sorting)
        const table = document.querySelector('table');
        const headers = table.querySelectorAll('th');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        headers.forEach(header => {
            header.addEventListener('click', () => {
                const columnIndex = Array.from(headers).indexOf(header);
                const currentSort = header.getAttribute('data-sort') || 'asc';
                const newSort = currentSort === 'asc' ? 'desc' : 'asc';

                rows.sort((rowA, rowB) => {
                    const cellA = rowA.querySelectorAll('td')[columnIndex].textContent.toLowerCase();
                    const cellB = rowB.querySelectorAll('td')[columnIndex].textContent.toLowerCase();

                    if (cellA < cellB) {
                        return newSort === 'asc' ? -1 : 1;
                    } else if (cellA > cellB) {
                        return newSort === 'asc' ? 1 : -1;
                    } else {
                        return 0;
                    }
                });

                tbody.innerHTML = ''; // Clear the table body
                rows.forEach(row => tbody.appendChild(row)); // Append sorted rows

                // Update sort direction for the clicked header
                headers.forEach(h => h.removeAttribute('data-sort'));
                header.setAttribute('data-sort', newSort);
            });
        });
    </script>

</body>
</html>