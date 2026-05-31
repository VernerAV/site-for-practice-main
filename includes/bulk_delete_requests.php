<?php
session_start();
require_once 'check_auth.php';
checkAuth();

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$ids = $data['ids'] ?? [];

if (empty($ids)) {
    echo json_encode(['success' => false, 'error' => 'Не выбраны заявки']);
    exit();
}

require_once 'config.php';

try {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "DELETE FROM message WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);
    $deletedCount = $stmt->rowCount();

    $_SESSION['admin_message'] = "✅ Удалено заявок: $deletedCount";
    $_SESSION['admin_message_type'] = "success";

    echo json_encode(['success' => true, 'deleted' => $deletedCount]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}