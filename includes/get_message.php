<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'error' => 'Неверный ID сообщения']);
    exit();
}

$message_id = intval($_GET['id']);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    
    $sql = "SELECT * FROM message WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $message_id]);
    $message = $stmt->fetch();
    
    if (!$message) {
        echo json_encode(['success' => false, 'error' => 'Сообщение не найдено']);
        exit();
    }
    
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (PDOException $e) {
    error_log("Ошибка при получении сообщения: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Ошибка сервера']);
}
?>