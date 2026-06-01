<?php
// includes/create_message.php - создание заявки авторизованными пользователями
session_start();
require_once 'config.php';

// Проверяем авторизацию
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    $_SESSION['request_errors'] = ['Вы не авторизованы'];
    header('Location: ../user.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];

// Валидация данных
if (empty($_POST['category_id'])) {
    $errors[] = 'Выберите категорию услуги';
}

if (empty($_POST['description'])) {
    $errors[] = 'Введите описание проблемы';
}

// Если есть ошибки, возвращаем пользователя
if (!empty($errors)) {
    $_SESSION['request_errors'] = $errors;
    header('Location: ../user.php');
    exit();
}

try {
    // Подключаемся к базе данных
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Получаем данные пользователя
    $user_sql = "SELECT u.email, 
                        up.first_name, up.last_name, up.middle_name, up.phone, up.address 
                 FROM users u 
                 LEFT JOIN user_profiles up ON u.id = up.user_id 
                 WHERE u.id = :user_id";
    
    $user_stmt = $pdo->prepare($user_sql);
    $user_stmt->execute([':user_id' => $user_id]);
    $user_data = $user_stmt->fetch();

    if (!$user_data) {
        $_SESSION['request_errors'] = ['Данные пользователя не найдены'];
        header('Location: ../user.php');
        exit();
    }

    // Формируем полное имя
    $first_name = $user_data['first_name'] ?? '';
    $last_name = $user_data['last_name'] ?? '';
    $middle_name = $user_data['middle_name'] ?? '';
    $user_name = trim("{$last_name} {$first_name} {$middle_name}");
    if (empty($user_name)) {
        $user_name = explode('@', $user_data['email'])[0];
    }
    
    $user_email = $user_data['email'];
    $phone = $user_data['phone'] ?? '';
    $address = $user_data['address'] ?? '';

    // Данные из формы
    $category_id = (int)$_POST['category_id'];
    $description = trim($_POST['description']);
    $work_address = !empty($_POST['address']) ? trim($_POST['address']) : $address;
    $preferred_date = !empty($_POST['preferred_date']) ? $_POST['preferred_date'] : null;
    $preferred_time = !empty($_POST['preferred_time']) ? $_POST['preferred_time'] : null;
    
    // Технические данные
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Получаем название категории для темы
    $cat_stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $cat_stmt->execute([$category_id]);
    $category_name = $cat_stmt->fetchColumn();
    if (!$category_name) {
        $_SESSION['request_errors'] = ['Неверная категория'];
        header('Location: ../user.php');
        exit();
    }
    
    $subject = "Заявка на услугу: " . $category_name;
    if ($preferred_date) {
        $subject .= " (на " . date('d.m.Y', strtotime($preferred_date)) . ")";
    }

    // Вставка заявки в таблицу message
    $sql = "INSERT INTO message (
                user_name, 
                user_email, 
                first_name, 
                last_name, 
                middle_name, 
                phone, 
                address, 
                category_id,
                subject, 
                message, 
                work_address,
                preferred_date,
                preferred_time,
                status,
                is_read, 
                ip_address, 
                user_agent
            ) VALUES (
                :user_name, 
                :user_email, 
                :first_name, 
                :last_name, 
                :middle_name, 
                :phone, 
                :address, 
                :category_id,
                :subject, 
                :message, 
                :work_address,
                :preferred_date,
                :preferred_time,
                'новая',
                0, 
                :ip_address, 
                :user_agent
            )";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':user_name' => $user_name,
        ':user_email' => $user_email,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':middle_name' => $middle_name,
        ':phone' => $phone,
        ':address' => $address,
        ':category_id' => $category_id,
        ':subject' => $subject,
        ':message' => $description,
        ':work_address' => $work_address,
        ':preferred_date' => $preferred_date,
        ':preferred_time' => $preferred_time,
        ':ip_address' => $ip_address,
        ':user_agent' => $user_agent
    ]);

    if ($result) {
        $last_id = $pdo->lastInsertId();
        
        // --- АВТОМАТИЧЕСКОЕ НАЗНАЧЕНИЕ СОТРУДНИКА ---
        // 1. Найти отдел, связанный с категорией
        $dept_sql = "SELECT id, department_name FROM department_rules WHERE category_id = ? LIMIT 1";
        $dept_stmt = $pdo->prepare($dept_sql);
        $dept_stmt->execute([$category_id]);
        $department = $dept_stmt->fetch();
        
        if ($department) {
            // 2. Найти должности, связанные с этим отделом
            $pos_sql = "SELECT position_id FROM department_positions WHERE department_rule_id = ?";
            $pos_stmt = $pdo->prepare($pos_sql);
            $pos_stmt->execute([$department['id']]);
            $position_ids = $pos_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($position_ids)) {
                // 3. Найти сотрудника (role = 'executor', активного), у которого должность и отдел совпадают
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
                    // Назначаем заявку на этого сотрудника
                    $assign_sql = "UPDATE message SET assigned_to = :emp_id, assigned_at = NOW(), assign_comment = 'Автоматическое назначение по категории' WHERE id = :id";
                    $assign_stmt = $pdo->prepare($assign_sql);
                    $assign_stmt->execute([':emp_id' => $employee['id'], ':id' => $last_id]);
                    
                    // Логируем назначение
                    $log_sql = "INSERT INTO assignment_log (request_id, request_type, assigned_to, assigned_at, type, performed_by, comment) 
                                VALUES (:rid, 'user', :emp, NOW(), 'auto', 1, 'Автоматическое распределение')";
                    $log_stmt = $pdo->prepare($log_sql);
                    $log_stmt->execute([':rid' => $last_id, ':emp' => $employee['id']]);
                }
            }
        }
        // -------------------------------------------------
        
        $_SESSION['request_success'] = '✅ Ваша заявка успешно создана! Номер заявки: #' . $last_id;
        
        // Логирование
        error_log("Создана заявка #{$last_id} от {$user_name} ({$user_email}) - {$subject}");
        
        // Отправка уведомления администратору (опционально)
        if (defined('ADMIN_EMAIL') && ADMIN_EMAIL) {
            $to = ADMIN_EMAIL;
            $email_subject = "Новая заявка: {$subject}";
            $email_message = "📋 Новая заявка с сайта\n\n";
            $email_message .= "ID заявки: #{$last_id}\n";
            $email_message .= "Пользователь: {$user_name}\n";
            $email_message .= "Email: {$user_email}\n";
            if ($phone) $email_message .= "Телефон: {$phone}\n";
            if ($address) $email_message .= "Адрес: {$address}\n";
            if ($work_address && $work_address != $address) {
                $email_message .= "Адрес для работ: {$work_address}\n";
            }
            $email_message .= "Категория: {$category_name}\n";
            if ($preferred_date) {
                $email_message .= "Предпочтительная дата: " . date('d.m.Y', strtotime($preferred_date)) . "\n";
            }
            if ($preferred_time) {
                $email_message .= "Предпочтительное время: {$preferred_time}\n";
            }
            $email_message .= "---\n";
            $email_message .= "Сообщение:\n";
            $email_message .= "{$description}\n\n";
            $email_message .= "---\n";
            $email_message .= "Дата отправки: " . date('d.m.Y H:i:s') . "\n";
            $email_message .= "IP адрес: {$ip_address}\n";
            
            $headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n";
            $headers .= "Reply-To: {$user_email}\r\n";
            $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
            
            @mail($to, $email_subject, $email_message, $headers);
        }
        
    } else {
        throw new Exception("Не удалось создать заявку");
    }

} catch (PDOException $e) {
    error_log("Ошибка БД в create_message.php: " . $e->getMessage());
    $_SESSION['request_errors'] = ['Ошибка базы данных: ' . $e->getMessage()];
} catch (Exception $e) {
    error_log("Ошибка в create_message.php: " . $e->getMessage());
    $_SESSION['request_errors'] = ['Ошибка при создании заявки: ' . $e->getMessage()];
}

// Возвращаем пользователя в личный кабинет
header('Location: ../user.php');
exit();
?>