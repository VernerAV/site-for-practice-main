<?php
session_start();
// Если пользователь уже авторизован, перенаправляем
if (isset($_SESSION['user_id'])) {
    header('Location: user.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Восстановление пароля</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/mobile_all.css">
    <style>
        .success { background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 15px; }
        .info { background: #cce5ff; color: #004085; padding: 12px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="login-container">
    <div class="nav-buttons">
        <a href="javascript:history.back()" class="nav-btn back-btn">← Назад</a>
        <a href="index.php" class="nav-btn home-btn">🏠 На главную</a>
    </div>
    <div class="logo">
        <h1>Восстановление пароля</h1>
        <p>Введите email, указанный при регистрации</p>
    </div>

    <?php if (isset($_GET['sent']) && $_GET['sent'] == 1): ?>
        <div class="success">
            ✅ Инструкция по восстановлению пароля отправлена на ваш email.<br>
            Проверьте почту (включая папку "Спам").
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="error">
            <?php 
            $errors = [
                'empty'    => 'Введите email',
                'invalid'  => 'Email не найден в системе',
                'mail_fail'=> 'Не удалось отправить письмо. Попробуйте позже.',
                'db_error' => 'Ошибка базы данных. Попробуйте позже.'
            ];
            echo $errors[$_GET['error']] ?? 'Произошла ошибка. Попробуйте снова.';
            ?>
        </div>
    <?php endif; ?>

    <?php if (!isset($_GET['sent']) || $_GET['sent'] != 1): ?>
    <form action="includes/send_reset.php" method="POST">
        <div class="form-group">
            <label for="email">Электронная почта</label>
            <input type="email" id="email" name="email" required 
                   value="<?php echo htmlspecialchars($_GET['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <button type="submit" class="btn-login">Отправить ссылку для сброса</button>
    </form>
    <?php endif; ?>

    <div class="links">
        <a href="login.php">Вспомнили пароль? Войти</a>
    </div>
</div>
</body>
</html>