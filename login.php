<?php
session_start();
// Если пользователь уже авторизован, перенаправляем
if (isset($_SESSION['user_id'])) {
    header('Location: user.php');
    exit();
}

if (isset($_GET['timeout'])) {
    echo '<div class="alert alert-warning">Ваша сессия истекла из-за неактивности. Пожалуйста, войдите снова.</div>';
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в личный кабинет - ГБУ "Жилищник Района Строгино"</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    
    <div class="login-container">
        <div class="logo">
            <h1>ГБУ "Жилищник Района Строгино"</h1>
            <p>Вход в личный кабинет</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="error">
                <?php 
                $errors = [
                    'invalid' => 'Неверный email или пароль',
                    'empty' => 'Заполните все поля',
                    'db' => 'Ошибка базы данных'
                ];
                echo $errors[$_GET['error']] ?? 'Произошла ошибка';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="success">
                Регистрация успешна! Войдите в систему.
            </div>
        <?php endif; ?>

        <form action="includes/auth.php" method="POST">
            <div class="form-group">
                <label for="email">Электронная почта</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo $_GET['email'] ?? ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Войти</button>
        </form>
        
        <div class="links">
            <a href="register.php">Регистрация</a>
            <a href="forgot-password.php">Забыли пароль?</a>
        </div>
    </div>
</body>
</html>