<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
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
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if (empty($price_id)) {
            // Добавление новой услуги
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
            // Обновление существующей услуги
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
        
        header('Location: ../admin.php?message=' . $message);
        exit();
        
    } catch (PDOException $e) {
        header('Location: ../admin.php?error=db_error');
        exit();
    }
} else {
    $section = $_GET['section'] ?? 'news';
    header("Location: ../admin.php?section=$section");
    exit();
}
?>