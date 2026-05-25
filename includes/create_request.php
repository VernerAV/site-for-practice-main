<?php
session_start();
require_once 'config.php';

// Быстрая проверка
if (!isset($_SESSION['user_id'])) {
    die("Ошибка авторизации");
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'] ?? '';

// Если email нет в сессии, получаем из БД
if (empty($user_email)) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $sql = "SELECT email FROM users WHERE id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch();
        
        if ($user) {
            $user_email = $user['email'];
            $_SESSION['user_email'] = $user_email;
        } else {
            die("Пользователь не найден");
        }
        
    } catch (PDOException $e) {
        die("Ошибка БД: " . $e->getMessage());
    }
}

// Получаем данные из формы
$service_type = $_POST['service_type'] ?? '';
$description = $_POST['description'] ?? '';
$address = $_POST['address'] ?? '';
$preferred_date = $_POST['preferred_date'] ?? '';
$preferred_time = $_POST['preferred_time'] ?? '';

// Минимальная валидация
if (empty($service_type) || empty($description)) {
    $_SESSION['request_errors'] = ["Заполните обязательные поля"];
    header('Location: ../user.php#new-request');
    exit();
}

// Простая вставка в message
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    
    $subject = "Заявка: " . substr($service_type, 0, 50);
    $message = "Тип: $service_type\n\nОписание:\n$description\n\n";
    
    if (!empty($address)) $message .= "Адрес: $address\n";
    if (!empty($preferred_date)) $message .= "Дата: $preferred_date";
    if (!empty($preferred_time)) $message .= " $preferred_time";
    
    $message .= "\n\nID пользователя: $user_id\nEmail: $user_email";
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $sql = "INSERT INTO messages (user_name, user_email, subject, message, ip_address, user_agent, created_at) 
            VALUES ('Пользователь', :email, :subject, :message, :ip, :agent, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':email' => $user_email,
        ':subject' => $subject,
        ':message' => $message,
        ':ip' => $ip,
        ':agent' => $agent
    ]);
    
    $_SESSION['request_success'] = "Заявка создана! №" . $pdo->lastInsertId();
    header('Location: ../user.php#requests');
    exit();
    
} catch (PDOException $e) {
    $_SESSION['request_errors'] = ["Ошибка: " . $e->getMessage()];
    header('Location: ../user.php#new-request');
    exit();
}
?>