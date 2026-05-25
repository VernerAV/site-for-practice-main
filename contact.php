<?php
require_once 'includes/config.php';

try {
    // Подключаемся с данными из config.php
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $connection_error = "Ошибка подключения к базе данных:<br><strong>" . $e->getMessage() . "</strong>";
    error_log("DB Connection Error: " . $e->getMessage());
    $pdo = null;
}

// Обработка формы
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем и очищаем данные
    $user_name = trim($_POST['user_name'] ?? '');
    $user_email = trim($_POST['user_email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Валидация
    if (empty($user_name) || strlen($user_name) < 2) {
        $errors[] = 'Введите корректное имя (минимум 2 символа)';
    }
    
    if (empty($user_email) || !filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Введите корректный email адрес';
    }
    
    if (empty($subject)) {
        $errors[] = 'Выберите тему обращения';
    }
    
    if (empty($message) || strlen($message) < 10) {
        $errors[] = 'Опишите проблему подробнее (минимум 10 символов)';
    }
    
    // Если нет ошибок и есть подключение к БД
    if (empty($errors) && $pdo) {
        try {
            // ВСТАВКА В ТАБЛИЦУ REQUESTS
            $sql = "INSERT INTO requests (user_name, user_email, subject, message, created_at) 
                    VALUES (:name, :email, :subject, :message, NOW())";
            
            $stmt = $pdo->prepare($sql);
            
            $result = $stmt->execute([
                ':name' => htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'),
                ':email' => htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8'),
                ':subject' => htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'),
                ':message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
            ]);
            
            if ($result) {
                // Получаем ID вставленной записи
                $request_id = $pdo->lastInsertId();
                $success_message = "✅ Заявка №{$request_id} успешно создана! Мы свяжемся с вами в течение 24 часов.";
                
                // Очищаем форму
                $_POST = [];
            } else {
                $errors[] = "Ошибка при сохранении заявки";
            }
            
        } catch (PDOException $e) {
            // Логируем полную ошибку
            error_log("MySQL Insert Error [contact.php]: " . $e->getMessage() . 
                     "\nSQL: " . $sql . 
                     "\nData: " . print_r($_POST, true));
            
            // Для пользователя - понятное сообщение
            if (strpos($e->getMessage(), 'requests') !== false) {
                $errors[] = "Ошибка: таблица 'requests' не найдена в базе данных.";
                $errors[] = "Убедитесь, что таблица существует со следующими полями:";
                $errors[] = "id, user_name, user_email, subject, message, created_at";
            } else {
                $errors[] = "Ошибка базы данных: " . $e->getMessage();
            }
        }
    } elseif (empty($errors) && !$pdo) {
        $errors[] = "Нет подключения к базе данных. Обратитесь к администратору.";
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подача заявки - ГБУ Жилищник Строгино</title>
    <link rel="stylesheet" href="css/contact.css">
</head>
<body>

 <?php include 'templates/header.php'; ?>

    <div class="form-container">
        <div class="header_contact">
            <h1>Сервис подачи заявок на обслуживание</h1>
        </div>
        
        <div class="form-content">
            <?php if (!empty($errors)): ?>
                <div class="message error">
                    <strong>Ошибки при заполнении формы:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="message success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="requestForm" class="form_flex">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Ваше имя <span>*</span></label>
                        <input type="text" 
                               class="form-input" 
                               name="user_name" 
                               value="<?php echo htmlspecialchars($_POST['user_name'] ?? ''); ?>" 
                               required 
                               minlength="2"
                               placeholder="Иван Иванов"
                               autocomplete="name">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email <span>*</span></label>
                        <input type="email" 
                               class="form-input" 
                               name="user_email" 
                               value="<?php echo htmlspecialchars($_POST['user_email'] ?? ''); ?>" 
                               required
                               placeholder="example@mail.ru"
                               autocomplete="email">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Номер телефона</label>
                    <input type="tel" 
                           class="form-input" 
                           name="user_phone" 
                           value="<?php echo htmlspecialchars($_POST['user_phone'] ?? ''); ?>" 
                           placeholder="+7 (999) 999-99-99"
                           autocomplete="tel"
                           id="phoneInput">
                    <span class="form-hint">Необязательное поле</span>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Тема обращения <span>*</span></label>
                    <select class="form-input" name="subject" required>
                        <option value="">-- Выберите тему обращения --</option>
                        <option value="Ремонт помещений" <?php echo ($_POST['subject'] ?? '') == 'Ремонт помещений' ? 'selected' : ''; ?>>Ремонт помещений</option>
                        <option value="Уборка территории" <?php echo ($_POST['subject'] ?? '') == 'Уборка территории' ? 'selected' : ''; ?>>Уборка территории</option>
                        <option value="Электрика" <?php echo ($_POST['subject'] ?? '') == 'Электрика' ? 'selected' : ''; ?>>Электрические работы</option>
                        <option value="Сантехника" <?php echo ($_POST['subject'] ?? '') == 'Сантехника' ? 'selected' : ''; ?>>Сантехнические работы</option>
                        <option value="Лифт" <?php echo ($_POST['subject'] ?? '') == 'Лифт' ? 'selected' : ''; ?>>Неисправность лифта</option>
                        <option value="Мусор" <?php echo ($_POST['subject'] ?? '') == 'Мусор' ? 'selected' : ''; ?>>Вывоз мусора</option>
                        <option value="Отопление" <?php echo ($_POST['subject'] ?? '') == 'Отопление' ? 'selected' : ''; ?>>Проблемы с отоплением</option>
                        <option value="Крыша" <?php echo ($_POST['subject'] ?? '') == 'Крыша' ? 'selected' : ''; ?>>Протечка крыши</option>
                        <option value="Подъезд" <?php echo ($_POST['subject'] ?? '') == 'Подъезд' ? 'selected' : ''; ?>>Уборка/ремонт подъезда</option>
                        <option value="Другое" <?php echo ($_POST['subject'] ?? '') == 'Другое' ? 'selected' : ''; ?>>Другое</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Подробное описание проблемы <span>*</span></label>
                    <textarea class="form-input" 
                              name="message" 
                              required 
                              minlength="10"
                              placeholder="Опишите проблему максимально подробно. Укажите адрес, этаж, конкретное место..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    <span class="form-hint">Минимум 10 символов. Будьте максимально конкретны</span>
                </div>
                
                <button type="submit" class="submit-btn">Отправить заявку</button>
            </form>
            
            <div class="info-box">
                <h3>Как работает сервис:</h3>
                <ul class="info-list">
                    <li>Заявка регистрируется в системе в течение 5 минут</li>
                    <li>Специалист свяжется с вами в течение 24 часов</li>
                    <li>Среднее время решения проблемы: 1-3 рабочих дня</li>
                    <li>Для экстренных случаев звоните: <strong>+7 (495) 123-45-67</strong></li>
                    <li>Работаем: Пн-Пт 8:00-20:00, Сб 9:00-18:00, Вс 9:00-16:00</li>
                </ul>
            </div>
        </div>
    </div>

     <?php include 'templates/footer.php'; ?>

    <script>
        // Маска для телефона
        document.getElementById('phoneInput').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            
            if (value.length > 0) {
                // Убираем код страны если начинается с 7 или 8
                if (value[0] === '7' || value[0] === '8') {
                    value = value.substring(1);
                }
                
                // Ограничиваем 10 цифрами
                if (value.length > 10) {
                    value = value.substring(0, 10);
                }
                
                // Форматируем номер
                let formatted = '+7 (';
                if (value.length > 0) {
                    formatted += value.substring(0, 3);
                }
                if (value.length > 3) {
                    formatted += ') ' + value.substring(3, 6);
                }
                if (value.length > 6) {
                    formatted += '-' + value.substring(6, 8);
                }
                if (value.length > 8) {
                    formatted += '-' + value.substring(8, 10);
                }
                
                e.target.value = formatted;
            }
        });
        
        // Автоматическое скрытие сообщений через 10 секунд
        setTimeout(function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(function(message) {
                message.style.display = 'none';
            });
        }, 10000);
        
        // Валидация формы перед отправкой
        document.getElementById('requestForm').addEventListener('submit', function(e) {
            const nameInput = this.querySelector('input[name="user_name"]');
            const emailInput = this.querySelector('input[name="user_email"]');
            const subjectSelect = this.querySelector('select[name="subject"]');
            const messageTextarea = this.querySelector('textarea[name="message"]');
            let hasError = false;
            
            // Убираем предыдущие ошибки
            document.querySelectorAll('.form-input.error').forEach(function(el) {
                el.classList.remove('error');
            });
            
            // Проверка имени
            if (nameInput.value.trim().length < 2) {
                nameInput.classList.add('error');
                hasError = true;
            }
            
            // Проверка email
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailInput.value.trim())) {
                emailInput.classList.add('error');
                hasError = true;
            }
            
            // Проверка темы
            if (!subjectSelect.value) {
                subjectSelect.classList.add('error');
                hasError = true;
            }
            
            // Проверка сообщения
            if (messageTextarea.value.trim().length < 10) {
                messageTextarea.classList.add('error');
                hasError = true;
            }
            
            if (hasError) {
                e.preventDefault();
                alert('Пожалуйста, исправьте ошибки в форме');
            }
        });
    </script>

</body>
</html>