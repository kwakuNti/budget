<?php
/**
 * Favicon Include File
 * Include this file in the <head> section of all pages
 * Usage: <?php include '../includes/favicon.php'; ?>
 */

// Determine the correct path to public folder based on current file location
$favicon_path = '../public/';

// If we're already in the public folder or root, adjust path
if (strpos($_SERVER['REQUEST_URI'], '/public/') !== false) {
    $favicon_path = './';
} elseif (strpos($_SERVER['REQUEST_URI'], '/templates/') === false && strpos($_SERVER['REQUEST_URI'], '/actions/') === false && strpos($_SERVER['REQUEST_URI'], '/api/') === false) {
    $favicon_path = './public/';
}
?>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo $favicon_path; ?>favicon.ico">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $favicon_path; ?>favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $favicon_path; ?>favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $favicon_path; ?>apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo $favicon_path; ?>android-chrome-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo $favicon_path; ?>android-chrome-512x512.png">
    <link rel="manifest" href="<?php echo $favicon_path; ?>site.webmanifest">
