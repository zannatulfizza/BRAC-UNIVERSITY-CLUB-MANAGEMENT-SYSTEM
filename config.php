<?php
$host = "localhost";   // database server
$user = "root";        // default XAMPP user
$pass = "";            // no password in your case
$db   = "club_management"; // database name

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<?php

