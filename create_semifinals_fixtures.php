<?php
require_once 'config.php';
require_once 'functions.php';

// Create a new database connection
$conn = new mysqli($host, $user, $pwd, $db);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create semi-final fixtures
createSemiFinalFixtures($conn);

// Close the database connection
$conn->close();
?>
