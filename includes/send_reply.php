<?php
// includes/send_reply.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit();
}

if (!isset($_POST['id']) || !is_numeric($_POST['id']) || empty($_POST['reply'])) {
    echo json_encode(['success' => false, 'error' => 'Неверные данные']);
    exit();
}

$message_id = intval($_POST['id']);
$reply = trim($_POST['reply']);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    
    $sql = "UPDATE message SET admin_response = :reply, responded_at = NOW(), is_read = 1 WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':reply' => $reply,
        ':id' => $message_id
    ]);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    error_log("Ошибка при отправке ответа: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Ошибка сервера']);
}
?>