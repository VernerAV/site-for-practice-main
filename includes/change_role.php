<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if (isset($_GET['id']) && isset($_GET['role'])) {
    $user_id = intval($_GET['id']);
    $new_role = $_GET['role'] === 'admin' ? 'admin' : 'user';
    
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "UPDATE users SET role = :role WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':role' => $new_role,
            ':id' => $user_id
        ]);
        
        header('Location: ../admin.php?message=role_changed');
        exit();
        
    } catch (PDOException $e) {
        header('Location: ../admin.php?error=role_change_error');
        exit();
    }
} else {
    header('Location: ../admin.php');
    exit();
}
?>