<?php
// api/mark_notifications.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth_check.php';

$uid = $_SESSION['user_id'] ?? ($user['id'] ?? null);

if (!$uid) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE notifikasi SET is_read = 1 WHERE user_id = :uid");
    $stmt->execute([':uid' => $uid]);
    
    echo json_encode([
        'success' => true,
        'affected' => $stmt->rowCount()
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
