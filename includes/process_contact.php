<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Проверка, что это POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'errors' => ['Неверный метод']]);
    exit;
}

// Получение данных
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'errors' => ['Ошибка формата данных']]);
    exit;
}

// Валидация
$errors = [];

// Общие поля
if (empty($data['user_name']) || strlen($data['user_name']) < 2) {
    $errors[] = 'Введите имя (минимум 2 символа)';
}
if (empty($data['user_email']) || !filter_var($data['user_email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Введите корректный email';
}
if (empty($data['category_id']) || !is_numeric($data['category_id'])) {
    $errors[] = 'Выберите категорию';
}
if (empty($data['message']) || strlen($data['message']) < 10) {
    $errors[] = 'Опишите проблему подробнее (минимум 10 символов)';
}

// Проверка типа
$type = $data['type'] ?? '';
if (!in_array($type, ['request', 'appeal'])) {
    $errors[] = 'Выберите тип обращения';
}

// Для заявок дополнительные поля
if ($type === 'request') {
    if (empty($data['urgency']) || !in_array($data['urgency'], ['normal','high','emergency'])) {
        $errors[] = 'Укажите срочность';
    }
    if (empty($data['volume']) || !in_array($data['volume'], ['small','medium','large'])) {
        $errors[] = 'Укажите объём работ';
    }
    if (empty($data['address'])) {
        $errors[] = 'Укажите адрес';
    }
    // Проверка этажа (если указан, должен быть числом)
    if (isset($data['floor']) && $data['floor'] !== '' && !is_numeric($data['floor'])) {
        $errors[] = 'Этаж должен быть числом';
    }
}

// Если есть ошибки – возвращаем
if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Подготовка данных для сохранения
try {
    // Определяем work_type по категории
    $cat_stmt = $pdo->prepare("SELECT work_type, name FROM categories WHERE id = ?");
    $cat_stmt->execute([$data['category_id']]);
    $cat = $cat_stmt->fetch();
    if (!$cat) {
        echo json_encode(['success' => false, 'errors' => ['Категория не найдена']]);
        exit;
    }
    $work_type = $cat['work_type'];
    
    // Для обращения (appeal) – просто сохраняем без расчёта времени и без назначения
    if ($type === 'appeal') {
        $request_data = [
            'user_name' => $data['user_name'],
            'user_email' => $data['user_email'],
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'category_id' => $data['category_id'],
            'subject' => 'Обращение: ' . $cat['name'],
            'message' => $data['message'],
            'work_type' => $work_type,
            'urgency' => $data['urgency'] ?? 'normal',
            'volume' => 'medium', // не используется
            'floor' => null,
            'has_elevator' => 1,
            'materials_needed' => 0,
            'estimated_hours' => null
        ];
        $request_id = saveRequest($request_data);
        echo json_encode(['success' => true, 'request_id' => $request_id, 'type' => 'appeal']);
        exit;
    }
    
    // Для заявки (request) – считаем время и ищем исполнителя
    // Получаем базовые часы из категории
    $base_hours = $cat['base_hours'] ?? 1.0;
    // Рассчитываем время
    $estimated_hours = calculateEstimatedHours(
        $data['category_id'],
        $work_type,
        $data['volume'],
        $data['urgency'],
        isset($data['floor']) ? (int)$data['floor'] : null,
        isset($data['has_elevator']) ? (int)$data['has_elevator'] : 1,
        isset($data['materials_needed']) ? (int)$data['materials_needed'] : 0
    );
    
    // Сохраняем заявку
    $request_data = [
        'user_name' => $data['user_name'],
        'user_email' => $data['user_email'],
        'phone' => $data['phone'] ?? '',
        'address' => $data['address'] ?? '',
        'category_id' => $data['category_id'],
        'subject' => 'Заявка: ' . $cat['name'],
        'message' => $data['message'],
        'work_type' => $work_type,
        'urgency' => $data['urgency'],
        'volume' => $data['volume'],
        'floor' => isset($data['floor']) ? (int)$data['floor'] : null,
        'has_elevator' => isset($data['has_elevator']) ? (int)$data['has_elevator'] : 1,
        'materials_needed' => isset($data['materials_needed']) ? (int)$data['materials_needed'] : 0,
        'estimated_hours' => $estimated_hours
    ];
    $request_id = saveRequest($request_data);
    
    // Автоматическое назначение исполнителя
    $executor_id = findBestExecutor($data['category_id'], $work_type, $estimated_hours);
    if ($executor_id) {
        // Обновляем assigned_to в message
        $assign_stmt = $pdo->prepare("UPDATE message SET assigned_to = ?, assigned_at = NOW(), assign_comment = 'Автоматическое назначение по нагрузке' WHERE id = ?");
        $assign_stmt->execute([$executor_id, $request_id]);
        // Логируем
        logAssignment($request_id, $executor_id, 'auto', 'Автоматическое назначение по нагрузке');
    }
    
    echo json_encode(['success' => true, 'request_id' => $request_id, 'type' => 'request', 'estimated_hours' => $estimated_hours]);
    
} catch (PDOException $e) {
    error_log("Ошибка в process_contact: " . $e->getMessage());
    echo json_encode(['success' => false, 'errors' => ['Ошибка базы данных']]);
}