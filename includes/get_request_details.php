<?php
session_start();
require_once 'check_auth.php';
checkAuth();

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
    exit();
}

$id = $_GET['id'] ?? 0;

require_once 'config.php';

try {
    // Исправлено: таблица message, а не requests
    $stmt = $pdo->prepare("SELECT * FROM message WHERE id = ?");
    $stmt->execute([$id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo json_encode([
            'success' => true,
            'request' => $request
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Заявка не найдена'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>