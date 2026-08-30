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

// Устанавливаем заголовок JSON
header('Content-Type: application/json; charset=utf-8');

// Получаем ID сотрудника из GET-параметра
$employee_id = (int)($_GET['id'] ?? 0);

if ($employee_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Не указан ID сотрудника']);
    exit;
}

try {
    // Получаем данные пользователя и его профиль
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.email,
            u.role,
            up.first_name,
            up.last_name,
            up.middle_name,
            up.phone,
            up.birth_date,
            up.employee_id,
            up.position,
            up.department,
            d.id as department_id,
            p.id as position_id
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        LEFT JOIN department_rules d ON d.department_name = up.department
        LEFT JOIN positions p ON p.name = up.position
        WHERE u.id = ? AND u.role IN ('executor', 'dispatcher', 'moderator')
        LIMIT 1
    ");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo json_encode(['success' => false, 'error' => 'Сотрудник не найден']);
        exit;
    }

    // Если department_id или position_id не найдены (могут быть NULL), оставляем как есть
    echo json_encode([
        'success' => true,
        'employee' => [
            'id' => (int)$employee['id'],
            'email' => $employee['email'],
            'role' => $employee['role'],
            'first_name' => $employee['first_name'] ?? '',
            'last_name' => $employee['last_name'] ?? '',
            'middle_name' => $employee['middle_name'] ?? '',
            'phone' => $employee['phone'] ?? '',
            'birth_date' => $employee['birth_date'] ?? '',
            'employee_id' => $employee['employee_id'] ?? '',
            'position' => $employee['position'] ?? '',
            'department' => $employee['department'] ?? '',
            'department_id' => $employee['department_id'] ?? '',
            'position_id' => $employee['position_id'] ?? ''
        ]
    ]);
    exit;
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Ошибка базы данных: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}