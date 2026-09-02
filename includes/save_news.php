<?php
session_start();
require_once 'config.php';

// Разрешаем доступ администратору и диспетчеру (moderator)
$allowed_roles = ['admin', 'moderator'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $news_id = $_POST['news_id'] ?? '';
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    
    try {
        // Обработка загрузки изображения
        $image_name = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = $_FILES['image']['type'];
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            
            if (in_array($file_type, $allowed_types) && in_array($file_extension, $allowed_extensions)) {
                $image_name = uniqid('news_', true) . '.' . $file_extension;
                $upload_path = '../uploads/news/' . $image_name;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    throw new Exception('Не удалось загрузить файл');
                }
            } else {
                throw new Exception('Недопустимый тип файла');
            }
        }
        
        if (empty($news_id)) {
            $sql = "INSERT INTO news (title, description, image) VALUES (:title, :description, :image)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':image' => $image_name
            ]);
            $message = 'add_success';
        } else {
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
        
        // РЕДИРЕКТ В ЗАВИСИМОСТИ ОТ РОЛИ
        if ($_SESSION['user_role'] === 'admin') {
            header('Location: ../admin.php?message=' . $message);
        } else {
            header('Location: ../dispatcher.php?message=' . $message);
        }
        exit();
        
    } catch (Exception $e) {
        error_log('Ошибка сохранения новости: ' . $e->getMessage());
        if ($_SESSION['user_role'] === 'admin') {
            header('Location: ../admin.php?error=upload_error');
        } else {
            header('Location: ../dispatcher.php?error=upload_error');
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