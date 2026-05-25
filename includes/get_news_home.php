<?php
require_once 'config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Берем последние 10 новостей для слайдера
    $sql = "SELECT id, title, description, image, created_at FROM news ORDER BY created_at DESC LIMIT 10";
    $stmt = $pdo->query($sql);
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Возвращаем JSON для AJAX запроса
    header('Content-Type: application/json');
    echo json_encode($news);
    
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Ошибка загрузки новостей']);
}
?>