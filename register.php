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
    <link rel="stylesheet" href="css/mobile_all.css">
</head>
<body>
    <div class="register-container">
        <!-- Кнопки навигации -->
        <div class="nav-buttons">
            <a href="javascript:history.back()" class="nav-btn back-btn">
                ← Назад
            </a>
            <a href="index.php" class="nav-btn home-btn">
                🏠 На главную
            </a>
        </div>

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
                }, 2000);
            </script>
        <?php endif; ?>

        <form action="includes/register_process.php" method="POST" id="registerForm">
            <div class="form-group">
                <label for="email">Электронная почта *</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_GET['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
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
                       value="<?php echo htmlspecialchars($_GET['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="last_name">Фамилия</label>
                <input type="text" id="last_name" name="last_name" 
                       value="<?php echo htmlspecialchars($_GET['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <!-- ПОЛЕ ТЕЛЕФОНА С МАСКОЙ (исправленная) -->
            <div class="form-group">
                <label for="phone">Телефон</label>
                <input type="tel" id="phone" name="phone" 
                       placeholder="+7 (999) 999-99-99"
                       value="<?php echo htmlspecialchars($_GET['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" maxlength="18">
                <small class="form-hint">Введите 10 цифр после +7</small>
            </div>
            
            <button type="submit" class="btn-register">Зарегистрироваться</button>
        </form>
        
        <div class="links">
            <a href="login.php">Уже есть аккаунт? Войти</a>
        </div>
    </div>

<script>
    // ===== МАСКА ТЕЛЕФОНА  =====
    (function() {
        const phoneInput = document.getElementById('phone');

        function formatPhoneNumber(value) {
            // Удаляем все нецифровые символы
            let digits = value.replace(/\D/g, '');
            
            // Если есть 7 или 8 в начале, удаляем их (потому что добавим +7 сами)
            if (digits.startsWith('7') || digits.startsWith('8')) {
                digits = digits.substring(1);
            }
            // Ограничиваем 10 цифрами
            if (digits.length > 10) {
                digits = digits.slice(0, 10);
            }

            let result = '';
            if (digits.length > 0) {
                result = '+7';
                // Форматируем: +7 (XXX) XXX-XX-XX
                result += ' (';
                result += digits.substring(0, 3);
                if (digits.length > 3) {
                    result += ') ';
                    result += digits.substring(3, 6);
                    if (digits.length > 6) {
                        result += '-';
                        result += digits.substring(6, 8);
                        if (digits.length > 8) {
                            result += '-';
                            result += digits.substring(8, 10);
                        }
                    }
                }
            }
            return result;
        }

        // Обработчик ввода
        phoneInput.addEventListener('input', function(e) {
            // Получаем текущее значение
            let raw = this.value;
            // Удаляем все нецифровые
            let digits = raw.replace(/\D/g, '');
            
            // Если есть 7 или 8 в начале, удаляем (чтобы не дублировать +7)
            if (digits.startsWith('7') || digits.startsWith('8')) {
                digits = digits.substring(1);
            }
            // Ограничиваем 10 цифр
            if (digits.length > 10) {
                digits = digits.slice(0, 10);
            }

            // Форматируем
            const formatted = formatPhoneNumber(digits);
            
            // Устанавливаем значение, только если оно изменилось
            if (this.value !== formatted) {
                this.value = formatted;
            }

            // Устанавливаем курсор в конец
            const pos = this.value.length;
            this.setSelectionRange(pos, pos);
        });

        // При фокусе: если поле пустое, вставляем +7 (
        phoneInput.addEventListener('focus', function() {
            if (this.value === '') {
                this.value = '+7 (';
                this.setSelectionRange(4, 4);
            }
        });

        // При потере фокуса: если введено менее 10 цифр, оставляем как есть
        // (ничего не делаем)

        // Обработка вставки из буфера
        phoneInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text');
            // Извлекаем цифры из вставленного текста
            let digits = pasted.replace(/\D/g, '');
            if (digits.startsWith('7') || digits.startsWith('8')) {
                digits = digits.substring(1);
            }
            if (digits.length > 10) {
                digits = digits.slice(0, 10);
            }
            this.value = formatPhoneNumber(digits);
            this.setSelectionRange(this.value.length, this.value.length);
        });

        // Инициализация: если значение уже есть, форматируем
        if (phoneInput.value) {
            let digits = phoneInput.value.replace(/\D/g, '');
            if (digits.startsWith('7') || digits.startsWith('8')) {
                digits = digits.substring(1);
            }
            if (digits.length > 10) {
                digits = digits.slice(0, 10);
            }
            phoneInput.value = formatPhoneNumber(digits);
        }
    })();

    // Остальные функции (проверка пароля и т.д.) остаются без изменений
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

    // Валидация формы
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

        // Проверка телефона (если заполнен)
        const phone = document.getElementById('phone').value;
        if (phone) {
            const digits = phone.replace(/\D/g, '');
            // Убираем 7/8 в начале
            let clean = digits;
            if (clean.startsWith('7') || clean.startsWith('8')) {
                clean = clean.substring(1);
            }
            if (clean.length !== 10) {
                e.preventDefault();
                alert('Введите корректный номер телефона (10 цифр после +7)');
                return false;
            }
        }
    });
</script>
</body>
</html>
