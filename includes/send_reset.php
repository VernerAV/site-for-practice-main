<?php
session_start();
require_once 'config.php';

// Проверяем метод
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../forgot-password.php?error=invalid');
    exit;
}

$email = trim($_POST['email'] ?? '');
if (empty($email)) {
    header('Location: ../forgot-password.php?error=empty');
    exit;
}

// Проверяем, существует ли пользователь с таким email
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        header('Location: ../forgot-password.php?error=invalid');
        exit;
    }
    $user_id = $user['id'];

    // Генерируем уникальный токен
    $token = bin2hex(random_bytes(32)); // 64 символа
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Сохраняем токен в БД
    $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
    $stmt->execute([$token, $expires, $user_id]);

    // Формируем ссылку для сброса
    $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . urlencode($token);
    // Если сайт не использует HTTPS, замените на http://

    // Тема и тело письма
    $subject = "Восстановление пароля на сайте ГБУ 'Жилищник Района Строгино'";
    $message = "Здравствуйте!\n\nВы запросили восстановление пароля на сайте ГБУ 'Жилищник Района Строгино'.\n\n";
    $message .= "Для установки нового пароля перейдите по ссылке:\n" . $reset_link . "\n\n";
    $message .= "Ссылка действительна в течение 1 часа.\n\n";
    $message .= "Если вы не запрашивали восстановление, просто проигнорируйте это письмо.\n\n";
    $message .= "С уважением,\nАдминистрация сайта.";

    $headers = "From: no-reply@strogino.ru\r\n";
    $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";

    // Отправка письма
    $mail_sent = mail($email, $subject, $message, $headers);

    if ($mail_sent) {
        header('Location: ../forgot-password.php?sent=1');
        exit;
    } else {
        // Логируем ошибку отправки
        error_log("Failed to send reset email to $email");
        header('Location: ../forgot-password.php?error=mail_fail');
        exit;
    }
} catch (PDOException $e) {
    error_log("DB error in send_reset: " . $e->getMessage());
    header('Location: ../forgot-password.php?error=db_error');
    exit;
}