<?php
// includes/mark_message_read.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit();
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'error' => 'Неверный ID сообщения']);
    exit();
}

$message_id = intval($_POST['id']);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    
    $sql = "UPDATE message SET is_read = 1 WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $message_id]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    error_log("Ошибка при обновлении статуса сообщения: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Ошибка сервера']);
}
?>