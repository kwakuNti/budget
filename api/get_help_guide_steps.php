<?php
require_once '../config/database.php';
require_once '../config/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $page = $_GET['page'] ?? '';
    $after_order = (int)($_GET['after_order'] ?? 0);
    
    if (empty($page)) {
        echo json_encode(['success' => false, 'error' => 'Page parameter required']);
        exit;
    }
    
    // Get the next help guide step on this page with order greater than current
    $stmt = $pdo->prepare("
        SELECT * FROM walkthrough_steps 
        WHERE walkthrough_type = 'help_guide' 
        AND page_url = ? 
        AND step_order > ? 
        AND is_active = 1
        ORDER BY step_order ASC 
        LIMIT 1
    ");
    
    $stmt->execute([$page, $after_order]);
    $step = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($step) {
        echo json_encode([
            'success' => true,
            'step' => $step
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'step' => null,
            'message' => 'No more help guide steps on this page'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to get help guide steps: ' . $e->getMessage()
    ]);
}
?>
