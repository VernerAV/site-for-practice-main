<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $news_id = $_POST['news_id'] ?? '';
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    try {
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Обработка загрузки изображения
        $image_name = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['image']['type'];
            
            if (in_array($file_type, $allowed_types)) {
                $image_name = uniqid() . '_' . $_FILES['image']['name'];
                $upload_path = '../uploads/news/' . $image_name;
                move_uploaded_file($_FILES['image']['tmp_name'], $upload_path);
            }
        }
        
        if (empty($news_id)) {
            // Добавление новой новости
            $sql = "INSERT INTO news (title, description, image) VALUES (:title, :description, :image)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':image' => $image_name
            ]);
            $message = 'add_success';
        } else {
            // Обновление существующей новости
            if ($image_name) {
                $sql = "UPDATE news SET title = :title, description = :description, image = :image WHERE id = :id";
                $params = [
                    ':title' => $title,
                    ':description' => $description,
                    ':image' => $image_name,
                    ':id' => $news_id
                ];
            } else {
                $sql = "UPDATE news SET title = :title, description = :description WHERE id = :id";
                $params = [
                    ':title' => $title,
                    ':description' => $description,
                    ':id' => $news_id
                ];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $message = 'edit_success';
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