<?php
$host = "localhost";
$username = "root";
$password = "1234321";  // Ensure this matches the MYSQL_ROOT_PASSWORD in Docker Compose
$database = "iocl";
$port = 3307;  // Use the port you've mapped

// Create connection
$conn = new mysqli($host, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

