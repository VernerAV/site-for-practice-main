<?php
session_start(); // Нужно для работы сессий, вызываем до любого вывода
require_once 'includes/config.php';
require_once 'includes/check_auth.php';

// Текущий пользователь (если авторизован)
$current_user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT u.email, up.first_name, up.last_name, up.phone, up.address
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();
}

// Категории с example_problems
$categories = [];
try {
    $cat_stmt = $pdo->query("SELECT id, name, example_problems FROM categories ORDER BY name");
    $categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Ошибка загрузки категорий: " . $e->getMessage());
}

// Обработка формы
$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_name = trim($_POST['user_name'] ?? '');
    $user_email = trim($_POST['user_email'] ?? '');
    $user_phone = trim($_POST['user_phone'] ?? '');
    $user_address = trim($_POST['user_address'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $message_text = trim($_POST['message'] ?? '');

    if (empty($user_name) || strlen($user_name) < 2) $errors[] = 'Введите корректное имя (минимум 2 символа)';
    if (empty($user_email) || !filter_var($user_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Введите корректный email';
    if ($category_id <= 0) $errors[] = 'Выберите категорию обращения';
    if (empty($message_text) || strlen($message_text) < 10) $errors[] = 'Опишите проблему подробнее (минимум 10 символов)';

    if (empty($errors)) {
        try {
            // Название категории
            $cat_name = '';
            foreach ($categories as $cat) {
                if ($cat['id'] == $category_id) {
                    $cat_name = $cat['name'];
                    break;
                }
            }
            $subject = "Заявка: " . $cat_name;
            
            // Формируем сообщение, добавляя адрес, если он указан
            $full_message = $message_text;
            if (!empty($user_address)) {
                $full_message .= "\n\n📍 Адрес: " . $user_address;
            }

            $sql = "INSERT INTO message (user_name, user_email, phone, address, category_id, subject, message, created_at, status) 
                    VALUES (:name, :email, :phone, :addr, :cat_id, :subject, :msg, NOW(), 'новая')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => htmlspecialchars($user_name, ENT_QUOTES),
                ':email' => htmlspecialchars($user_email, ENT_QUOTES),
                ':phone' => htmlspecialchars($user_phone ?? '', ENT_QUOTES),
                ':addr' => htmlspecialchars($user_address ?? '', ENT_QUOTES),
                ':cat_id' => $category_id,
                ':subject' => $subject,
                ':msg' => htmlspecialchars($full_message, ENT_QUOTES)
            ]);
            $request_id = $pdo->lastInsertId();

            // Автоматическое назначение сотрудника (категория -> отдел -> должность)
            $dept_sql = "SELECT id, department_name FROM department_rules WHERE category_id = ? LIMIT 1";
            $dept_stmt = $pdo->prepare($dept_sql);
            $dept_stmt->execute([$category_id]);
            $department = $dept_stmt->fetch();

            if ($department) {
                $pos_sql = "SELECT position_id FROM department_positions WHERE department_rule_id = ?";
                $pos_stmt = $pdo->prepare($pos_sql);
                $pos_stmt->execute([$department['id']]);
                $position_ids = $pos_stmt->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($position_ids)) {
                    $placeholders = implode(',', array_fill(0, count($position_ids), '?'));
                    $emp_sql = "
                        SELECT u.id
                        FROM users u
                        JOIN user_profiles up ON u.id = up.user_id
                        WHERE u.role = 'executor'
                          AND u.is_active = 1
                          AND up.department = ?
                          AND up.position IN (SELECT name FROM positions WHERE id IN ($placeholders))
                        LIMIT 1
                    ";
                    $emp_stmt = $pdo->prepare($emp_sql);
                    $params = array_merge([$department['department_name']], $position_ids);
                    $emp_stmt->execute($params);
                    $employee = $emp_stmt->fetch();

                    if ($employee) {
                        $assign_sql = "UPDATE message SET assigned_to = :emp_id, assigned_at = NOW(), assign_comment = 'Автоматическое назначение по категории' WHERE id = :id";
                        $assign_stmt = $pdo->prepare($assign_sql);
                        $assign_stmt->execute([':emp_id' => $employee['id'], ':id' => $request_id]);

                        $log_sql = "INSERT INTO assignment_log (request_id, request_type, assigned_to, assigned_at, type, performed_by, comment) 
                                    VALUES (:rid, 'user', :emp, NOW(), 'auto', 1, 'Автоматическое распределение')";
                        $log_stmt = $pdo->prepare($log_sql);
                        $log_stmt->execute([':rid' => $request_id, ':emp' => $employee['id']]);
                    }
                }
            }

            $success_message = "✅ Заявка №{$request_id} успешно создана! Мы свяжемся с вами в течение 24 часов.";
            $_POST = []; // очищаем форму
        } catch (PDOException $e) {
            error_log("Ошибка при создании заявки: " . $e->getMessage());
            $errors[] = "Ошибка базы данных. Попробуйте позже.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подача заявки - ГБУ Жилищник Строгино</title>
    <link rel="stylesheet" href="css/header_mobile.css">
    <link rel="stylesheet" href="css/style_mobile.css">
    <link rel="stylesheet" href="css/contact.css">
    <style>
        /* Дополнительные стили для подсказок */
        .form-hint {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
            display: block;
        }
        .form-hint strong {
            color: #495057;
        }
        .error {
            border-color: #dc3545 !important;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
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
                <ul><?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="message success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="requestForm" class="form_flex">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Ваше имя <span>*</span></label>
                    <input type="text" class="form-input" name="user_name" 
                           value="<?= isset($_POST['user_name']) ? htmlspecialchars($_POST['user_name']) : ($current_user ? htmlspecialchars(trim(($current_user['first_name']??'').' '.($current_user['last_name']??'')) ?: $current_user['email']) : '') ?>" 
                           required minlength="2" placeholder="Иван Иванов">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email <span>*</span></label>
                    <input type="email" class="form-input" name="user_email" 
                           value="<?= isset($_POST['user_email']) ? htmlspecialchars($_POST['user_email']) : ($current_user ? htmlspecialchars($current_user['email']) : '') ?>" 
                           required placeholder="example@mail.ru">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Номер телефона</label>
                <input type="tel" class="form-input" name="user_phone" id="phoneInput"
                       value="<?= isset($_POST['user_phone']) ? htmlspecialchars($_POST['user_phone']) : ($current_user && !empty($current_user['phone']) ? htmlspecialchars($current_user['phone']) : '') ?>" 
                       placeholder="+7 (999) 999-99-99">
                <span class="form-hint">Необязательное поле</span>
            </div>

            <div class="form-group">
                <label class="form-label">Адрес (улица, дом, квартира)</label>
                <input type="text" class="form-input" name="user_address" id="addressInput"
                       value="<?= isset($_POST['user_address']) ? htmlspecialchars($_POST['user_address']) : ($current_user && !empty($current_user['address']) ? htmlspecialchars($current_user['address']) : '') ?>" 
                       placeholder="например: ул. Исаковского, д.8 к.1, кв.1">
                <span class="form-hint">Укажите адрес, чтобы специалист быстрее сориентировался</span>
            </div>
            
            <div class="form-group">
                <label class="form-label">Тема обращения <span>*</span></label>
                <select class="form-input" name="category_id" id="categorySelect" required>
                    <option value="">-- Выберите категорию --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" data-example="<?= htmlspecialchars($cat['example_problems'] ?? '') ?>" 
                            <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Подробное описание проблемы <span>*</span></label>
                <textarea class="form-input" name="message" id="messageTextarea" required minlength="10" 
                          placeholder="Опишите проблему максимально подробно..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                <span class="form-hint" id="exampleHint"></span>
            </div>
            
            <button type="submit" class="submit-btn">Отправить заявку</button>
        </form>
        
        <div class="info-box">
            <h3>Как работает сервис:</h3>
            <ul class="info-list">
                <li>Заявка регистрируется в системе в течение 5 минут</li>
                <li>Специалист свяжется с вами в течение 24 часов</li>
                <li>Среднее время решения проблемы: 1-3 рабочих дня</li>
                <li>Для экстренных случаев звоните: <strong>+7 (495) 758-38-22</strong></li>
                <li>Работаем: Пн-Пт 8:00-20:00, Сб 9:00-18:00, Вс 9:00-16:00</li>
            </ul>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
    // Маска телефона
    const phoneInput = document.getElementById('phoneInput');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value[0] === '7' || value[0] === '8') value = value.substring(1);
                if (value.length > 10) value = value.substring(0, 10);
                let formatted = '+7 (';
                if (value.length > 0) formatted += value.substring(0, 3);
                if (value.length > 3) formatted += ') ' + value.substring(3, 6);
                if (value.length > 6) formatted += '-' + value.substring(6, 8);
                if (value.length > 8) formatted += '-' + value.substring(8, 10);
                e.target.value = formatted;
            }
        });
    }

    // Динамическая подсказка с примерами проблем
    const categorySelect = document.getElementById('categorySelect');
    const exampleHint = document.getElementById('exampleHint');
    
    function updateExampleHint() {
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        const example = selectedOption?.getAttribute('data-example');
        if (example && example.trim()) {
            // Заменяем переносы строк на <br>
            const exampleHtml = example.replace(/\n/g, '<br>');
            exampleHint.innerHTML = '<strong>💡 Примеры проблем:</strong><br>' + exampleHtml;
        } else {
            exampleHint.innerHTML = '';
        }
    }
    
    categorySelect.addEventListener('change', updateExampleHint);
    // Вызываем сразу, если уже выбран какой-то вариант (например, после отправки формы)
    updateExampleHint();

    // Валидация формы перед отправкой
    document.getElementById('requestForm').addEventListener('submit', function(e) {
        const name = this.querySelector('input[name="user_name"]');
        const email = this.querySelector('input[name="user_email"]');
        const cat = this.querySelector('select[name="category_id"]');
        const msg = this.querySelector('textarea[name="message"]');
        let hasError = false;
        
        // Убираем предыдущие классы ошибок
        document.querySelectorAll('.form-input.error').forEach(el => el.classList.remove('error'));
        
        if (name.value.trim().length < 2) { name.classList.add('error'); hasError = true; }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) { email.classList.add('error'); hasError = true; }
        if (!cat.value) { cat.classList.add('error'); hasError = true; }
        if (msg.value.trim().length < 10) { msg.classList.add('error'); hasError = true; }
        
        if (hasError) {
            e.preventDefault();
            alert('Пожалуйста, исправьте ошибки в форме (обязательные поля выделены).');
        }
    });
    
    // Авто-скрытие сообщений через 10 секунд
    setTimeout(() => {
        document.querySelectorAll('.message').forEach(msg => {
            msg.style.transition = 'opacity 0.5s';
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 500);
        });
    }, 10000);
</script>

</body>
</html>