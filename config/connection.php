<?php
// config/connection.php

// config/connection.php

$servername = "localhost";
$username   = "root";
$password   = "root"; // if using MAMP default, keep it empty
$database   = "budget";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
