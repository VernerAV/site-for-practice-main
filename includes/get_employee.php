<?php
session_start();
require_once 'config.php';
require_once 'check_auth.php';

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Доступ запрещён']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Неверный ID сотрудника']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            u.id, u.email, u.role,
            up.first_name, up.last_name, up.middle_name,
            up.phone, up.birth_date, up.employee_id,
            up.position, up.department
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.id = ? AND u.role IN ('executor', 'dispatcher', 'moderator')
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$emp) {
        echo json_encode(['success' => false, 'error' => 'Сотрудник не найден']);
        exit;
    }

    // Получаем department_id по названию отдела (в БД хранится текст, а форме нужен ID)
    $department_id = null;
    if (!empty($emp['department'])) {
        $deptStmt = $pdo->prepare("SELECT id FROM department_rules WHERE department_name = ? LIMIT 1");
        $deptStmt->execute([$emp['department']]);
        $department_id = $deptStmt->fetchColumn() ?: null;
    }

    // Получаем position_id по названию должности
    $position_id = null;
    if (!empty($emp['position'])) {
        $posStmt = $pdo->prepare("SELECT id FROM positions WHERE name = ? LIMIT 1");
        $posStmt->execute([$emp['position']]);
        $position_id = $posStmt->fetchColumn() ?: null;
    }

    $emp['department_id'] = $department_id ? (int)$department_id : null;
    $emp['position_id']   = $position_id   ? (int)$position_id   : null;

    echo json_encode([
        'success'  => true,
        'employee' => $emp
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error'   => 'Ошибка БД: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}