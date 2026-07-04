<?php
// Database credentials
$servername = "localhost";  // MySQL host (usually localhost)
$username = "root";         // Default MySQL username
$password = "";             // Default MySQL password (empty by default)
$dbname = "ccs_database";    // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// If connection is successful, you can use $conn in other files.

// Return the connection object for reuse
return $conn;

//COPY PASTE THIS -> $conn = require 'db_connect.php';
?>