<?php
session_start();
require_once 'config.php';

$allowed_roles = ['admin', 'moderator'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: ../login.php');
    exit();
}

if (isset($_GET['id'])) {
    $price_id = intval($_GET['id']);
    
    try {
        $sql = "DELETE FROM services WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $price_id]);
        
        if ($_SESSION['user_role'] === 'admin') {
            header('Location: ../admin.php?message=price_delete_success');
        } else {
            header('Location: ../dispatcher.php?message=price_delete_success');
        }
        exit();
        
    } catch (PDOException $e) {
        if ($_SESSION['user_role'] === 'admin') {
            header('Location: ../admin.php?error=delete_error');
        } else {
            header('Location: ../dispatcher.php?error=delete_error');
        }
        exit();
    }
} else {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: ../admin.php');
    } else {
        header('Location: ../dispatcher.php');
    }
    exit();
}
?>