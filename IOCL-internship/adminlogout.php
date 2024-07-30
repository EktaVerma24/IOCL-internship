<?php
session_start();

// Destroy the session and clear session data
session_unset();
session_destroy();

// Redirect to the login page
header("Location: adminlogin.php");
exit();
?>

