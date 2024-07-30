<?php
$host = getenv('DB_HOST');       // Database hostname (provided by Render)
$username = getenv('DB_USER');   // Database username (provided by Render)
$password = getenv('DB_PASS');   // Database password (provided by Render)
$database = getenv('DB_NAME');   // Database name (provided by Render)
$port = getenv('DB_PORT');       // Database port (usually 3306 for MySQL)

// Create connection
$conn = new mysqli($host, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>


