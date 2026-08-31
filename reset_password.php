<?php
session_start();
require_once 'includes/config.php';

$token = $_GET['token'] ?? '';
$valid = false;
$user_id = null;
$error = '';

// Проверяем токен
if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("SELECT id, reset_expires FROM users WHERE reset_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $expires = strtotime($user['reset_expires']);
            if (time() < $expires) {
                $valid = true;
                $user_id = $user['id'];
            } else {
                $error = 'Срок действия ссылки истёк. Запросите восстановление заново.';
            }
        } else {
            $error = 'Неверная ссылка для восстановления.';
        }
    } catch (PDOException $e) {
        $error = 'Ошибка базы данных. Попробуйте позже.';
    }
} else {
    $error = 'Отсутствует токен для восстановления.';
}

// Обработка отправки формы с новым паролем
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid && isset($_POST['password'])) {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 6) {
        $error = 'Пароль должен содержать минимум 6 символов.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Пароли не совпадают.';
    } else {
        try {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $stmt->execute([$hashed, $user_id]);
            // Успешно – перенаправляем на login.php с сообщением
            header('Location: login.php?password_reset=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Ошибка обновления пароля. Попробуйте позже.';
            error_log("Reset password error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сброс пароля</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/mobile_all.css">
</head>
<body>
<div class="login-container">
    <div class="nav-buttons">
        <a href="javascript:history.back()" class="nav-btn back-btn">← Назад</a>
        <a href="index.php" class="nav-btn home-btn">🏠 На главную</a>
    </div>
    <div class="logo">
        <h1>Сброс пароля</h1>
    </div>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($valid): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="password">Новый пароль (мин. 6 символов)</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label for="confirm_password">Подтверждение пароля</label>
                <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
            </div>
            <button type="submit" class="btn-login">Установить новый пароль</button>
        </form>
    <?php else: ?>
        <div class="links">
            <a href="forgot-password.php">Запросить новую ссылку для восстановления</a>
        </div>
    <?php endif; ?>
</div>
</body>
</html>