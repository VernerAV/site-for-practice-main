<?php
session_start();
require_once 'check_auth.php';
checkAuth();

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
    exit();
}

$id = $_GET['id'] ?? 0;

require_once 'config.php';

try {
     $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("SELECT * FROM requests WHERE id = ?");
    $stmt->execute([$id]);
    $request = $stmt->fetch();
    
    if ($request) {
        echo json_encode([
            'success' => true,
            'request' => $request
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Заявка не найдена'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>