<?php
session_start();
require_once 'config.php';
require_once 'check_auth.php';
checkAuth();

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Доступ запрещён']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Неверный метод']);
    exit;
}

$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Не указан ID заявки']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($action === 'assign') {
        // Назначение сотрудника
        $employee_id = (int)($_POST['employee_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($employee_id > 0) {
            $stmt = $pdo->prepare("UPDATE message SET assigned_to = ?, assigned_at = NOW(), assign_comment = ?, status = 'в работе' WHERE id = ?");
            $stmt->execute([$employee_id, $comment, $id]);

            // Логируем назначение
            $logStmt = $pdo->prepare("INSERT INTO assignment_log (request_id, request_type, assigned_to, assigned_at, type, performed_by, comment) VALUES (?, 'user', ?, NOW(), 'manual', ?, ?)");
            $logStmt->execute([$id, $employee_id, $_SESSION['user_id'], $comment]);

            echo json_encode(['success' => true, 'message' => 'Сотрудник назначен']);
        } else {
            // Снять назначение
            $stmt = $pdo->prepare("UPDATE message SET assigned_to = NULL, assigned_at = NULL, assign_comment = NULL WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Назначение снято']);
        }
    } elseif ($action === 'update_status') {
        // Обновление статуса и ответа
        $status = $_POST['status'] ?? '';
        $admin_response = trim($_POST['admin_response'] ?? '');
        $mark_read = isset($_POST['mark_read']) ? 1 : 0;

        $updates = [];
        $params = [':id' => $id];
        if (!empty($status)) {
            $updates[] = "status = :status";
            $params[':status'] = $status;
        }
        if (!empty($admin_response)) {
            $updates[] = "admin_response = :response";
            $params[':response'] = $admin_response;
            $updates[] = "responded_at = NOW()";
            $updates[] = "responded_by = :admin_id";
            $params[':admin_id'] = $_SESSION['user_id'];
        }
        if ($mark_read) {
            $updates[] = "is_read = 1";
        }

        if (!empty($updates)) {
            $sql = "UPDATE message SET " . implode(", ", $updates) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }
        echo json_encode(['success' => true, 'message' => 'Данные обновлены']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}