<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['request_id'])) {
    header('Location: ../user.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$request_id = intval($_POST['request_id']);

try {
    // Обновляем статус заявки только если она принадлежит текущему пользователю
    $sql = "UPDATE message SET is_read = 1 
            WHERE id = :id AND user_id = :user_id AND is_read = 0";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $request_id, ':user_id' => $user_id]);
    
} catch (PDOException $e) {
    error_log("Ошибка при обновлении статуса заявки: " . $e->getMessage());
}

header('Location: ../user.php');
exit();