<?php
$servername = 'localhost';
$username = 'root';
$password = '';
$database = 'capstone';


$conn = new mysqli($servername, $username, $password, $database);


if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}
