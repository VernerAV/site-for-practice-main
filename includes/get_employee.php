<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../includes/config.php';
require_once '../includes/check_auth.php';

header('Content-Type: application/json');
if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Доступ запрещён']);
    exit;
}
$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Не указан ID']);
    exit;
}
try {
    $stmt = $pdo->prepare("
        SELECT u.id, u.email, u.role,
               up.first_name, up.last_name, up.middle_name,
               up.phone, up.birth_date, up.position, up.department,
               (SELECT id FROM positions WHERE name = up.position LIMIT 1) as position_id,
               (SELECT id FROM department_rules WHERE department_name = up.department LIMIT 1) as department_id
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($employee) {
        echo json_encode(['success' => true, 'employee' => $employee]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Сотрудник не найден']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>