<?php
$host = getenv('DB_HOST') ?: 'localhost';  // Fallback to localhost if not set
$username = getenv('DB_USER') ?: 'root';   // Fallback to root if not set
$password = getenv('DB_PASS') ?: '';       // Fallback to empty string if not set
$database = getenv('DB_NAME') ?: 'iocl';   // Fallback to iocl if not set
$port = getenv('DB_PORT') ?: 3306;         // Fallback to 3306 if not set

// Create connection
$conn = new mysqli($host, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error); // Log error to file
    die("Connection failed: Unable to connect to the database.");
}
?>



