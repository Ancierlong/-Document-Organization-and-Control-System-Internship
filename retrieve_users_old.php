<style>
.pagination button {
    padding: 10px 15px;
    margin: 3px;
    border: none;
    background-color: yellow;
    color: black;
    cursor: pointer;
    border-radius: 5px;
}

.pagination button:hover {
    background-color: white;
}

.pagination button:active {
    background-color: blue;
    color: orange;
}

.pagination button.current-page {
    background-color: gray;
    color: white;
    cursor: not-allowed;
}
</style>
<?php
require '../db_connect.php';

$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sortBy = isset($_GET['sortBy']) ? $_GET['sortBy'] : 'role';
$sortOrder = isset($_GET['sortOrder']) && $_GET['sortOrder'] == 'DESC' ? 'DESC' : 'ASC';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rowsPerPage = isset($_GET['rowsPerPage']) ? (int)$_GET['rowsPerPage'] : 10;
$offset = ($page - 1) * $rowsPerPage;

// Get total number of records
$totalSql = "SELECT COUNT(*) AS total FROM users WHERE `role` LIKE '%$search%' OR username LIKE '%$search%'";
$totalResult = $conn->query($totalSql);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];
$totalPages = ceil($totalRecords / $rowsPerPage);

// Fetch paginated results
$sql = "SELECT * FROM users 
        WHERE `role` LIKE '%$search%' OR username LIKE '%$search%' OR full_name LIKE '%$search%'
        OR email LIKE '%$search%' OR reg_date LIKE '%$search%'
        ORDER BY $sortBy $sortOrder 
        LIMIT $offset, $rowsPerPage";
$result = $conn->query($sql);

// Generate table rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $filePath = "../files_capstone_thesis/" . $row["file_name"];
        echo "<tr>
                <td>{$row["role"]}</td>
                <td>{$row["username"]}</td>
                <td>{$row["full_name"]}</td>
                <td>{$row["email"]}</td>
                <td>{$row["reg_date"]}</td>
                <td>{$row["created_by"]}</td>
                <!--
                <td><a href='$filePath' target='_blank'>" . $row["file_name"] . "</a></td>
                -->
                <!-- 
                sa mga future na maguupdate ng system na to tinatamad na ko magchange ng variable HAHAHA 
                ps. Angelo Calong OJT - 2025 sole backend*
                -->
                <td class='buttons'>
                <form action='edit_user.php' method='post'>
                <input type='hidden' name='id' value={$row['id']}>
                <button type='submit' value='view'>Edit
                </form>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='7'>No records found</td></tr>";
}

// Generate pagination buttons with sorting parameters
echo '<tr><td colspan="7" style="text-align: center;">';
for ($i = 1; $i <= $totalPages; $i++) {
    $disabledClass = ($i == $page) ? "current-page" : "";
    $disabledAttr = ($i == $page) ? "disabled" : "";
    echo "<button class='pagination $disabledClass' onclick='changePage($i)' $disabledAttr>$i</button> ";
}
echo '</td></tr>';

$conn->close();
?>
