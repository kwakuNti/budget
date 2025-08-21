<?php
session_start();
$_SESSION['user_id'] = 2;
$_SESSION['user_type'] = 'personal';

echo "Testing minimal personal dashboard...\n";

require_once 'config/connection.php';

try {
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT user_type, first_name, last_name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || $user['user_type'] !== 'personal') {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
    } else {
        echo json_encode(['success' => true, 'user' => $user]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
