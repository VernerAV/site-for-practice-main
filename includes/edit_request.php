<?php
session_start();
require_once 'config.php';
require_once 'check_auth.php';

// Только для администратора
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Доступ запрещён']);
    exit;
}

$request_id = (int)($_POST['request_id'] ?? 0);
$subject = trim($_POST['subject'] ?? '');
$status = trim($_POST['status'] ?? '');
$assigned_to = (int)($_POST['assigned_to'] ?? 0) ?: null;
$admin_response = trim($_POST['admin_response'] ?? '');

// Валидация
if (!$request_id) {
    echo json_encode(['success' => false, 'error' => 'Не указан ID заявки']);
    exit;
}

if (empty($subject)) {
    echo json_encode(['success' => false, 'error' => 'Тема не может быть пустой']);
    exit;
}

$allowed_statuses = ['новая', 'в работе', 'выполнена'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'error' => 'Недопустимый статус']);
    exit;
}

try {
    // Если назначен сотрудник – проверим, что он существует
    if ($assigned_to) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role IN ('executor','dispatcher','moderator')");
        $stmt->execute([$assigned_to]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Сотрудник не найден']);
            exit;
        }
    }

    $sql = "UPDATE message SET subject = ?, status = ?, assigned_to = ?, admin_response = ?, responded_at = NOW() WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$subject, $status, $assigned_to, $admin_response, $request_id]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}