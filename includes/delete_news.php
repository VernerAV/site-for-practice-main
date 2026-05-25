<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if (isset($_GET['id'])) {
    $news_id = intval($_GET['id']);
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "DELETE FROM news WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $news_id]);
        
        header('Location: ../admin.php?message=delete_success');
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