<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем и очищаем данные
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    // Валидация данных
    if (empty($email) || empty($password) || empty($confirm_password)) {
        header('Location: ../register.php?error=empty&email=' . urlencode($email) . 
               '&first_name=' . urlencode($first_name) . '&last_name=' . urlencode($last_name) . 
               '&phone=' . urlencode($phone));
        exit();
    }

    // Проверка email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../register.php?error=email_invalid&email=' . urlencode($email) . 
               '&first_name=' . urlencode($first_name) . '&last_name=' . urlencode($last_name) . 
               '&phone=' . urlencode($phone));
        exit();
    }

    // Проверка совпадения паролей
    if ($password !== $confirm_password) {
        header('Location: ../register.php?error=password_mismatch&email=' . urlencode($email) . 
               '&first_name=' . urlencode($first_name) . '&last_name=' . urlencode($last_name) . 
               '&phone=' . urlencode($phone));
        exit();
    }

    // Проверка сложности пароля
    if (strlen($password) < 6) {
        header('Location: ../register.php?error=password_weak&email=' . urlencode($email) . 
               '&first_name=' . urlencode($first_name) . '&last_name=' . urlencode($last_name) . 
               '&phone=' . urlencode($phone));
        exit();
    }

    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Проверяем существует ли пользователь с таким email
        $check_sql = "SELECT id FROM users WHERE email = :email";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([':email' => $email]);
        
        if ($check_stmt->fetch()) {
            header('Location: ../register.php?error=email_exists&email=' . urlencode($email) . 
                   '&first_name=' . urlencode($first_name) . '&last_name=' . urlencode($last_name) . 
                   '&phone=' . urlencode($phone));
            exit();
        }

        // Хешируем пароль
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Начинаем транзакцию
        $pdo->beginTransaction();

        // Создаем пользователя
        $user_sql = "INSERT INTO users (email, password, role) VALUES (:email, :password, 'user')";
        $user_stmt = $pdo->prepare($user_sql);
        $user_stmt->execute([
            ':email' => $email,
            ':password' => $hashed_password
        ]);

        $user_id = $pdo->lastInsertId();

        // Создаем профиль пользователя, если есть данные
        if (!empty($first_name) || !empty($last_name) || !empty($phone)) {
            $profile_sql = "INSERT INTO user_profiles (user_id, first_name, last_name, phone) 
                           VALUES (:user_id, :first_name, :last_name, :phone)";
            $profile_stmt = $pdo->prepare($profile_sql);
            $profile_stmt->execute([
                ':user_id' => $user_id,
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':phone' => $phone
            ]);
        }

        // Подтверждаем транзакцию
        $pdo->commit();

        // Перенаправляем на страницу успеха
        header('Location: ../register.php?success=1');
        exit();

    } catch (PDOException $e) {
        // Откатываем транзакцию в случае ошибки
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        header('Location: ../register.php?error=db&email=' . urlencode($email) . 
               '&first_name=' . urlencode($first_name) . '&last_name=' . urlencode($last_name) . 
               '&phone=' . urlencode($phone));
        exit();
    }
} else {
    header('Location: ../register.php');
    exit();
}
?>