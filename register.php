<?php
session_start();
require_once 'includes/config.php';

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
    <title>Регистрация - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/register.css">
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <h1><?php echo SITE_NAME; ?></h1>
            <p>Регистрация нового аккаунта</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="error">
                <?php 
                $errors = [
                    'empty' => 'Заполните все обязательные поля',
                    'email_invalid' => 'Некорректный email адрес',
                    'email_exists' => 'Пользователь с таким email уже существует',
                    'password_mismatch' => 'Пароли не совпадают',
                    'password_weak' => 'Пароль слишком слабый',
                    'db' => 'Ошибка базы данных'
                ];
                echo $errors[$_GET['error']] ?? 'Произошла ошибка';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="success">
                Регистрация успешна! Перенаправление на страницу входа...
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 2000); // Перенаправление через 2 секунды
            </script>
        <?php endif; ?>

        <form action="includes/register_process.php" method="POST" id="registerForm">
            <div class="form-group">
                <label for="email">Электронная почта *</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo $_GET['email'] ?? ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Пароль *</label>
                <input type="password" id="password" name="password" required 
                       oninput="checkPasswordStrength(this.value)">
                <div id="passwordStrength" class="password-strength"></div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Подтверждение пароля *</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                       oninput="checkPasswordMatch()">
                <div id="passwordMatch" class="password-strength"></div>
            </div>

            <div class="form-group">
                <label for="first_name">Имя</label>
                <input type="text" id="first_name" name="first_name" 
                       value="<?php echo $_GET['first_name'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <label for="last_name">Фамилия</label>
                <input type="text" id="last_name" name="last_name" 
                       value="<?php echo $_GET['last_name'] ?? ''; ?>">
            </div>

            <div class="form-group">
                <label for="phone">Телефон</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?php echo $_GET['phone'] ?? ''; ?>">
            </div>
            
            <button type="submit" class="btn-register">Зарегистрироваться</button>
        </form>
        
        <div class="links">
            <a href="login.php">Уже есть аккаунт? Войти</a>
        </div>
    </div>

    <script>
        function checkPasswordStrength(password) {
            const strengthElement = document.getElementById('passwordStrength');
            let strength = 'weak';
            let message = 'Слабый пароль';

            if (password.length >= 8) {
                strength = 'medium';
                message = 'Средний пароль';
            }
            
            if (password.length >= 8 && /[A-Z]/.test(password) && /[0-9]/.test(password)) {
                strength = 'strong';
                message = 'Сильный пароль';
            }

            strengthElement.textContent = message;
            strengthElement.className = 'password-strength strength-' + strength;
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchElement = document.getElementById('passwordMatch');

            if (confirmPassword === '') {
                matchElement.textContent = '';
                return;
            }

            if (password === confirmPassword) {
                matchElement.textContent = 'Пароли совпадают';
                matchElement.className = 'password-strength strength-strong';
            } else {
                matchElement.textContent = 'Пароли не совпадают';
                matchElement.className = 'password-strength strength-weak';
            }
        }

        // Валидация формы перед отправкой
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Пароли не совпадают!');
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                alert('Пароль должен содержать минимум 6 символов!');
                return false;
            }
        });
    </script>
</body>
</html>