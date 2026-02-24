<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bookhub";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    exit("Connection failed: " . $conn->connect_error);
}
?>