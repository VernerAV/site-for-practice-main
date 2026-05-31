<?php
session_start();
require_once 'config.php';
require_once 'check_auth.php';
checkAuth();

if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_submit'])) {
    header('Location: ../admin.php?section=users');
    exit;
}

$user_id = (int)($_POST['user_id'] ?? 0);
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$name = trim($_POST['name'] ?? '');
$role = $_POST['role'] ?? 'user';
$status = $_POST['status'] ?? 'active';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($user_id == 0) {
        // Добавление
        if (empty($password)) throw new Exception('Пароль обязателен для нового пользователя');
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, password, role, status, name) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$email, $hashed, $role, $status, $name]);
        $_SESSION['admin_message'] = "Пользователь добавлен";
    } else {
        // Редактирование
        $updates = [];
        $params = [':email' => $email, ':role' => $role, ':status' => $status, ':name' => $name, ':id' => $user_id];
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $updates[] = "password = :password";
            $params[':password'] = $hashed;
        }
        $sql = "UPDATE users SET email = :email, role = :role, status = :status, name = :name" . (empty($updates) ? "" : ", " . implode(", ", $updates)) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $_SESSION['admin_message'] = "Данные пользователя обновлены";
    }
    $_SESSION['admin_message_type'] = 'success';
} catch (Exception $e) {
    $_SESSION['admin_message'] = 'Ошибка: ' . $e->getMessage();
    $_SESSION['admin_message_type'] = 'error';
}
header("Location: ../admin.php?section=users");
exit;