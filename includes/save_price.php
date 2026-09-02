<?php
session_start();
require_once 'config.php';

$allowed_roles = ['admin', 'moderator'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $price_id = $_POST['price_id'] ?? '';
    $service_name = trim($_POST['service_name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $unit = trim($_POST['unit']);
    
    try {
        if (empty($price_id)) {
            $sql = "INSERT INTO services (service_name, description, price, unit) VALUES (:name, :desc, :price, :unit)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $service_name,
                ':desc' => $description,
                ':price' => $price,
                ':unit' => $unit
            ]);
            $message = 'price_add_success';
        } else {
            $sql = "UPDATE services SET service_name = :name, description = :desc, price = :price, unit = :unit WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $service_name,
                ':desc' => $description,
                ':price' => $price,
                ':unit' => $unit,
                ':id' => $price_id
            ]);
            $message = 'price_edit_success';
        }
        
        if ($_SESSION['user_role'] === 'admin') {
            header('Location: ../admin.php?message=' . $message);
        } else {
            header('Location: ../dispatcher.php?message=' . $message);
        }
        exit();
        
    } catch (PDOException $e) {
        if ($_SESSION['user_role'] === 'admin') {
            header('Location: ../admin.php?error=db_error');
        } else {
            header('Location: ../dispatcher.php?error=db_error');
        }
        exit();
    }
} else {
    $section = $_GET['section'] ?? 'news';
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: ../admin.php?section=$section");
    } else {
        header("Location: ../dispatcher.php?section=$section");
    }
    exit();
}
?>