<?php
session_start();
require_once 'config.php';
require_once 'check_auth.php';
checkAuth();

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['request_id']) || !isset($_POST['employee_id'])) {
    echo json_encode(['success' => false, 'error' => 'Неверный запрос']);
    exit;
}

$request_id = (int)$_POST['request_id'];
$employee_id = (int)$_POST['employee_id'];

try {
    $pdo->beginTransaction();

    if ($employee_id <= 0) {
        // Снять назначение
        $stmt = $pdo->prepare("UPDATE message SET assigned_to = NULL, assigned_at = NULL, assign_comment = NULL WHERE id = ?");
        $stmt->execute([$request_id]);
        $log = $pdo->prepare("INSERT INTO assignment_log (request_id, request_type, assigned_to, assigned_at, type, performed_by, comment) VALUES (?, 'user', ?, NOW(), 'manual', ?, 'Назначение снято')");
        $log->execute([$request_id, $_SESSION['user_id'], $_SESSION['user_id']]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Назначение снято']);
        exit;
    }

    // Назначить сотрудника
    $stmt = $pdo->prepare("UPDATE message SET assigned_to = ?, assigned_at = NOW(), assign_comment = 'Назначено вручную администратором' WHERE id = ?");
    $stmt->execute([$employee_id, $request_id]);
    $log = $pdo->prepare("INSERT INTO assignment_log (request_id, request_type, assigned_to, assigned_at, type, performed_by, comment) VALUES (?, 'user', ?, NOW(), 'manual', ?, 'Назначено вручную')");
    $log->execute([$request_id, $employee_id, $_SESSION['user_id']]);
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Назначено успешно']);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>