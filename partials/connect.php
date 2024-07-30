<?php
$host = "localhost";
$username = "root";
$password = null;  // assuming no password
$database = "iocl";
$port = 3307;  // specify your port number here

// Create connection
$conn = new mysqli($host, $username, $password, $database, $port);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
