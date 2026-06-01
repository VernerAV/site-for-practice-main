<?php
session_start();

// Подключаем конфиг
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверяем заполнены ли поля
    if (empty($_POST['email']) || empty($_POST['password'])) {
        header('Location: ../login.php?error=empty');
        exit();
    }

    $user_email = trim($_POST['email']);
    $user_password = $_POST['password'];

    try {
        // Подключение к базе данных через конфиг
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Ищем пользователя по email
        $sql = "SELECT id, email, password, role FROM users WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':email' => $user_email]);
        $user = $stmt->fetch();

        if ($user && password_verify($user_password, $user['password'])) {
            // Пароль верный, создаем сессию
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;

            // Перенаправляем в зависимости от роли
        switch ($user['role']) {
            case 'admin':
                header('Location: ../admin.php');
                break;
            case 'executor':
                header('Location: ../employee.php');
                break;
            case 'dispatcher':
                header('Location: ../dispatcher.php');
                break;
            default:
                header('Location: ../user.php');
            }
        exit();
            
        } else {
            // Неверные данные
            header('Location: ../login.php?error=invalid&email=' . urlencode($user_email));
            exit();
        }

    } catch (PDOException $e) {
        header('Location: ../login.php?error=db');
        exit();
    }
} else {
    header('Location: ../login.php');
    exit();
}
?>