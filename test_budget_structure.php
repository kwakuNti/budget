<?php
session_start();

// Set user session for testing
$_SESSION['user_id'] = 2;
$_SESSION['first_name'] = 'Clifford';
$_SESSION['last_name'] = 'Nkansah';

// Include and test the budget data API
require_once 'api/budget_data.php';
?>
