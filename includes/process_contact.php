<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json; charset=utf-8');

// ===== 1. Метод =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'errors' => ['Неверный метод']]);
    exit;
}

// ===== 2. Парсинг JSON =====
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    echo json_encode(['success' => false, 'errors' => ['Ошибка формата данных']]);
    exit;
}

// ===== 3. СЕРВЕРНАЯ ВАЛИДАЦИЯ =====
$errors = [];

$type = $data['type'] ?? '';           // 'request' или 'appeal'
$category_id = (int)($data['category_id'] ?? 0);
$user_name = trim($data['user_name'] ?? '');
$user_email = trim($data['user_email'] ?? '');
$phone = trim($data['phone'] ?? '');
$address = trim($data['address'] ?? '');
$message = trim($data['message'] ?? '');

if (!in_array($type, ['request', 'appeal'], true)) {
    $errors[] = 'Выберите тип обращения (Заявка или Обращение)';
}
if ($category_id <= 0) {
    $errors[] = 'Выберите категорию';
}
if (mb_strlen($user_name) < 2) {
    $errors[] = 'Имя должно содержать минимум 2 символа';
}
if (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Введите корректный email';
}
if (mb_strlen($message) < 10) {
    $errors[] = 'Опишите проблему подробнее (минимум 10 символов)';
}

// Поля только для заявки
$urgency   = null;
$volume    = null;
$floor     = null;
$has_elevator = 1;
$materials_needed = 0;

if ($type === 'request') {
    $urgency = $data['urgency'] ?? '';
    $volume  = $data['volume']  ?? '';

    if (!in_array($urgency, ['low', 'normal', 'high'], true)) {
        $errors[] = 'Выберите срочность';
    }
    if (!in_array($volume, ['small', 'medium', 'large'], true)) {
        $errors[] = 'Выберите объём работ';
    }
    if ($address === '') {
        $errors[] = 'Введите адрес';
    }

    if (isset($data['floor']) && $data['floor'] !== '') {
        $floor = (int)$data['floor'];
    }
    $has_elevator = isset($data['has_elevator']) ? (int)$data['has_elevator'] : 1;
    $materials_needed = isset($data['materials_needed']) ? (int)$data['materials_needed'] : 0;
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== 4. ОСНОВНАЯ ЛОГИКА =====
try {
    // Категория
    $cat_stmt = $pdo->prepare("SELECT work_type, name, base_hours FROM categories WHERE id = ?");
    $cat_stmt->execute([$category_id]);
    $cat = $cat_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cat) {
        throw new Exception('Категория не найдена');
    }
    $work_type = $cat['work_type'];
    $cat_name  = $cat['name'];

    // ---------- ОБРАЩЕНИЕ ----------
    if ($type === 'appeal') {
        $request_data = [
            'user_name'        => $user_name,
            'user_email'       => $user_email,
            'phone'            => $phone,
            'address'          => $address,
            'category_id'      => $category_id,
            'subject'          => 'Обращение: ' . $cat_name,
            'message'          => $message,
            'work_type'        => $work_type,
            'urgency'          => 'normal',
            'volume'           => 'medium',
            'floor'            => null,
            'has_elevator'     => 1,
            'materials_needed' => 0,
            'estimated_hours'  => null,
        ];
        $request_id = saveRequest($request_data);

        // Автоназначение
        $executor_id = findBestExecutor($category_id, $work_type, 'normal', null);
        if ($executor_id) {
            $assign_stmt = $pdo->prepare("
                UPDATE message 
                SET assigned_to = ?, assigned_at = NOW(),
                    assign_comment = 'Автоматическое назначение по нагрузке'
                WHERE id = ?
            ");
            $assign_stmt->execute([$executor_id, $request_id]);
            logAssignment($request_id, $executor_id, 'auto', 'Автоматическое назначение по нагрузке');
            error_log("process_contact: обращение #$request_id назначено на исполнителя $executor_id");
        } else {
            error_log("process_contact: для обращения #$request_id не найден исполнитель");
        }

        echo json_encode([
            'success'    => true,
            'request_id' => $request_id,
            'type'       => 'appeal',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ---------- ЗАЯВКА ----------
    $estimated_hours = calculateEstimatedHours(
        $category_id,
        $work_type,
        $volume,
        $urgency,
        $floor,
        $has_elevator,
        $materials_needed
    );

    $request_data = [
        'user_name'        => $user_name,
        'user_email'       => $user_email,
        'phone'            => $phone,
        'address'          => $address,
        'category_id'      => $category_id,
        'subject'          => 'Заявка: ' . $cat_name,
        'message'          => $message,
        'work_type'        => $work_type,
        'urgency'          => $urgency,
        'volume'           => $volume,
        'floor'            => $floor,
        'has_elevator'     => $has_elevator,
        'materials_needed' => $materials_needed,
        'estimated_hours'  => $estimated_hours,
    ];
    $request_id = saveRequest($request_data);

    // Автоназначение
    $executor_id = findBestExecutor($category_id, $work_type, $urgency, $estimated_hours);
    if ($executor_id) {
        $assign_stmt = $pdo->prepare("
            UPDATE message 
            SET assigned_to = ?, assigned_at = NOW(),
                assign_comment = 'Автоматическое назначение по нагрузке'
            WHERE id = ?
        ");
        $assign_stmt->execute([$executor_id, $request_id]);
        logAssignment($request_id, $executor_id, 'auto', 'Автоматическое назначение по нагрузке');
        error_log("process_contact: заявка #$request_id назначена на исполнителя $executor_id");
    } else {
        error_log("process_contact: для заявки #$request_id не найден исполнитель");
    }

    echo json_encode([
        'success'         => true,
        'request_id'      => $request_id,
        'type'            => 'request',
        'estimated_hours' => $estimated_hours,
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("Ошибка БД в process_contact: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'errors'  => ['Ошибка базы данных']
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Ошибка в process_contact: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'errors'  => [$e->getMessage()]
    ], JSON_UNESCAPED_UNICODE);
}