<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'errors' => ['Неверный метод']]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'errors' => ['Ошибка формата данных']]);
    exit;
}

// Извлекаем все поля
$user_name = trim($data['user_name'] ?? '');  // полное имя
$first_name = trim($data['first_name'] ?? '');
$last_name = trim($data['last_name'] ?? '');
$middle_name = trim($data['middle_name'] ?? '');
$user_email = trim($data['user_email'] ?? '');
$phone = trim($data['user_phone'] ?? '');
$address = trim($data['address'] ?? '');
$message = trim($data['message'] ?? '');
$category_id = (int)($data['category_id'] ?? 0);
$type = $data['type'] ?? '';
$urgency = $data['urgency'] ?? 'normal';
$volume = $data['volume'] ?? 'medium';
$floor = isset($data['floor']) && $data['floor'] !== '' ? (int)$data['floor'] : null;
$has_elevator = isset($data['has_elevator']) ? (int)$data['has_elevator'] : 1;
$materials_needed = isset($data['materials_needed']) ? (int)$data['materials_needed'] : 0;
$intercom = trim($data['intercom'] ?? '');
$apartment = trim($data['apartment'] ?? '');

// Если user_name передан, а first_name/last_name пустые, формируем их из user_name
if (!empty($user_name) && (empty($first_name) || empty($last_name))) {
    $parts = explode(' ', $user_name, 3);
    if (count($parts) >= 2) {
        $last_name = $parts[0];
        $first_name = $parts[1];
        $middle_name = $parts[2] ?? '';
    } else {
        $first_name = $user_name;
        $last_name = $user_name;
    }
}

// Если first_name и last_name есть, но user_name пустой, формируем user_name
if (empty($user_name) && !empty($first_name) && !empty($last_name)) {
    $user_name = $last_name . ' ' . $first_name . ($middle_name ? ' ' . $middle_name : '');
}

$errors = [];

// Валидация общих полей
if (empty($user_name) && (empty($last_name) || empty($first_name))) {
    $errors[] = 'Введите имя и фамилию';
} else {
    // Проверяем длину, если есть поля
    if (!empty($last_name) && strlen($last_name) < 2) $errors[] = 'Фамилия должна быть не менее 2 символов';
    if (!empty($first_name) && strlen($first_name) < 2) $errors[] = 'Имя должно быть не менее 2 символов';
}
if (empty($user_email) || !filter_var($user_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Введите корректный email';
if (empty($category_id) || !is_numeric($category_id)) $errors[] = 'Выберите категорию';
if (empty($message) || strlen($message) < 10) $errors[] = 'Опишите проблему подробнее (минимум 10 символов)';
if (!in_array($type, ['request', 'appeal'])) $errors[] = 'Выберите тип обращения';

// Проверяем категорию в БД
$cat_stmt = $pdo->prepare("SELECT work_type, name, is_common FROM categories WHERE id = ?");
$cat_stmt->execute([$category_id]);
$cat = $cat_stmt->fetch();
if (!$cat) {
    $errors[] = 'Категория не найдена';
} else {
    $work_type = $cat['work_type'];
    $is_common = $cat['is_common'] == 1;
    $cat_name = $cat['name'];
}

if ($type === 'request') {
    if (empty($address)) $errors[] = 'Укажите адрес';
    if (!$is_common) {
        if (empty($urgency) || !in_array($urgency, ['normal','high','emergency'])) $errors[] = 'Выберите срочность';
        if (empty($volume) || !in_array($volume, ['small','medium','large'])) $errors[] = 'Выберите объём работ';
        if ($materials_needed === null) $errors[] = 'Укажите готовность к работе';
        if (empty($apartment)) $errors[] = 'Укажите номер квартиры';
    } else {
        $urgency = 'normal';
        $volume = 'medium';
        $materials_needed = 0;
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    // Расчёт времени только для квартирных заявок
    $estimated_hours = null;
    if ($type === 'request' && !$is_common) {
        $base_hours = 1.0;
        $volume_factors = ['small' => 0.8, 'medium' => 1.0, 'large' => 1.5];
        $urgency_factors = ['normal' => 1.0, 'high' => 1.2, 'emergency' => 1.5];
        $hours = $base_hours * ($volume_factors[$volume] ?? 1.0) * ($urgency_factors[$urgency] ?? 1.0);
        if ($floor && $floor > 5 && !$has_elevator) {
            $hours += ($floor - 5) * 0.2;
        }
        if ($materials_needed) {
            $hours += 0.5;
        }
        $estimated_hours = round($hours, 2);
    }

    // Формируем полное имя (если его нет, то из частей)
    if (empty($user_name)) {
        $user_name = $last_name . ' ' . $first_name . ($middle_name ? ' ' . $middle_name : '');
    }

    // Вставка в БД
    $sql = "INSERT INTO message 
            (user_name, first_name, last_name, middle_name, user_email, phone, address, category_id, subject, message, 
             work_type, urgency, volume, floor, has_elevator, materials_needed, estimated_hours, intercom,
             created_at, status, is_read)
            VALUES 
            (:user_name, :first_name, :last_name, :middle_name, :user_email, :phone, :address, :cat_id, :subject, :message,
             :work_type, :urgency, :volume, :floor, :has_elevator, :materials, :est_hours, :intercom,
             NOW(), 'новая', 0)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_name' => $user_name,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':middle_name' => $middle_name,
        ':user_email' => $user_email,
        ':phone' => $phone,
        ':address' => $address,
        ':cat_id' => $category_id,
        ':subject' => ($type === 'request' ? 'Заявка: ' : 'Обращение: ') . $cat_name,
        ':message' => $message,
        ':work_type' => $work_type,
        ':urgency' => $urgency,
        ':volume' => $volume,
        ':floor' => $floor,
        ':has_elevator' => $has_elevator,
        ':materials' => $materials_needed,
        ':est_hours' => $estimated_hours,
        ':intercom' => $intercom
    ]);
    $request_id = $pdo->lastInsertId();

    // Автоматическое назначение для заявок
    if ($type === 'request' && function_exists('findBestExecutor')) {
        $executor_id = findBestExecutor($category_id, $work_type, $estimated_hours);
        if ($executor_id) {
            $assign_stmt = $pdo->prepare("UPDATE message SET assigned_to = ?, assigned_at = NOW(), assign_comment = 'Автоматическое назначение' WHERE id = ?");
            $assign_stmt->execute([$executor_id, $request_id]);
            if (function_exists('logAssignment')) {
                logAssignment($request_id, $executor_id, 'auto', 'Автоматическое назначение');
            }
        }
    }

    echo json_encode(['success' => true, 'request_id' => $request_id, 'type' => $type, 'estimated_hours' => $estimated_hours]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'errors' => ['Ошибка БД: ' . $e->getMessage()]]);
}