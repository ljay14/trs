<?php
session_start(); // Start the session

// Destroy all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to login page after logging out
header("Location: index.php");
exit;
?>
