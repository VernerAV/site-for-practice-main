<?php
session_start();
require_once 'check_auth.php';
checkAuth();

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
    exit();
}

$id = $_POST['id'] ?? 0;
$status = $_POST['status'] ?? 'new';

require_once 'config.php';

try {
    $stmt = $pdo->prepare("UPDATE requests SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Статус обновлен'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>