<?php
// includes/delete_request.php
session_start();
require_once 'check_auth.php';
checkAuth();

if (!isAdmin()) {
    header('Location: ../user.php');
    exit();
}

// Получаем параметры
$id = $_GET['id'] ?? 0;
$section = $_GET['section'] ?? 'requests';

// Подключаемся к БД
require_once 'config.php';

if (isset($_GET['id'])) {
    $price_id = intval($_GET['id']);
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "DELETE FROM requests WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $price_id]);
        
        header('Location: ../admin.php?message=price_delete_success');
        exit();
        
    } catch (PDOException $e) {
        header('Location: ../admin.php?error=delete_error');
        exit();
    }
} else {
    header('Location: ../admin.php');
    exit();
}
?>