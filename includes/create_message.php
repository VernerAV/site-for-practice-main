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
if (empty($_POST['service_type'])) {
    $errors[] = 'Выберите тип услуги';
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
    // Используем только email из таблицы users, остальное из user_profiles
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

    // Формируем данные из профиля
    // Собираем имя из ФИО из таблицы user_profiles
    $first_name = $user_data['first_name'] ?? '';
    $last_name = $user_data['last_name'] ?? '';
    $middle_name = $user_data['middle_name'] ?? '';
    
    // Формируем полное имя
    $user_name = trim("{$last_name} {$first_name} {$middle_name}");
    if (empty($user_name)) {
        // Если ФИО нет, используем email без домена
        $user_email = $user_data['email'];
        $user_name = explode('@', $user_email)[0];
    }
    
    $user_email = $user_data['email'];
    $phone = $user_data['phone'] ?? '';
    $address = $user_data['address'] ?? '';

    // Используем адрес из формы или из профиля
    $work_address = !empty($_POST['address']) ? trim($_POST['address']) : $address;

    // Данные из формы
    $service_type = trim($_POST['service_type']);
    $description = trim($_POST['description']);
    $preferred_date = !empty($_POST['preferred_date']) ? $_POST['preferred_date'] : null;
    $preferred_time = !empty($_POST['preferred_time']) ? $_POST['preferred_time'] : null;
    
    // Технические данные
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Создаем заголовок
    $subject = "Заявка на услугу: " . $service_type;
    if (!empty($_POST['preferred_date'])) {
        $subject .= " (на " . date('d.m.Y', strtotime($_POST['preferred_date'])) . ")";
    }

    // Вставка заявки в таблицу messages
    $sql = "INSERT INTO message (
                user_name, 
                user_email, 
                first_name, 
                last_name, 
                middle_name, 
                phone, 
                address, 
                subject, 
                message, 
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
                :subject, 
                :message, 
                :is_read, 
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
        ':subject' => $subject,
        ':message' => $description,
        ':is_read' => 0,
        ':ip_address' => $ip_address,
        ':user_agent' => $user_agent
    ]);

    if ($result) {
        $last_id = $pdo->lastInsertId();
        
        // Обновляем дополнительные поля если они существуют
        // Проверяем существование столбцов
        $column_check = $pdo->query("SHOW COLUMNS FROM message")->fetchAll(PDO::FETCH_COLUMN, 0);
        $columns = array_flip($column_check);
        
        $updates = [];
        $update_params = [':id' => $last_id];
        
        if (isset($columns['service_type']) && $service_type) {
            $updates[] = 'service_type = :service_type';
            $update_params[':service_type'] = $service_type;
        }
        
        if (isset($columns['work_address']) && $work_address && $work_address != $address) {
            $updates[] = 'work_address = :work_address';
            $update_params[':work_address'] = $work_address;
        }
        
        if (isset($columns['preferred_date']) && $preferred_date) {
            $updates[] = 'preferred_date = :preferred_date';
            $update_params[':preferred_date'] = $preferred_date;
        }
        
        if (isset($columns['preferred_time']) && $preferred_time) {
            $updates[] = 'preferred_time = :preferred_time';
            $update_params[':preferred_time'] = $preferred_time;
        }
        
        if (!empty($updates)) {
            $update_sql = "UPDATE message SET " . implode(', ', $updates) . " WHERE id = :id";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute($update_params);
        }
        
        // Сохраняем сообщение об успехе
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
            $email_message .= "Тип услуги: {$service_type}\n";
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