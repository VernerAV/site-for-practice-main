<?php
session_start();
require_once 'check_auth.php';
checkAuth();

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
    exit();
}

// Получаем данные из POST запроса
$data = json_decode(file_get_contents('php://input'), true);
$ids = $data['ids'] ?? [];
$section = $data['section'] ?? 'requests';

// Подключаемся к БД
require_once 'config.php';

if (empty($ids)) {
    echo json_encode(['success' => false, 'error' => 'Не выбраны заявки для удаления']);
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Создаем строку с плейсхолдерами для IN условия
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    
    // Удаляем выбранные заявки
    $sql = "DELETE FROM requests WHERE id IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);
    
    $deletedCount = $stmt->rowCount();
    
    $_SESSION['message'] = "✅ Удалено заявок: $deletedCount";
    $_SESSION['message_type'] = "success";
    
    echo json_encode([
        'success' => true,
        'deleted' => $deletedCount
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>