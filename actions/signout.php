<?php
session_start();
session_unset();
session_destroy();



// Redirect to login page
header("Location: ../templates/login.php?status=success&message=Logged out successfully.");
exit();
?>
