<?php
session_start();
require_once 'config.php';
require_once 'check_auth.php';
checkAuth();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Не указан ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM message WHERE id = ?");
    $stmt->execute([$id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        echo json_encode(['success' => false, 'error' => 'Заявка не найдена']);
        exit;
    }

    $isStaff = isAdminOrModerator();
    if (!$isStaff) {
        if ($request['user_email'] !== $_SESSION['user_email']) {
            echo json_encode(['success' => false, 'error' => 'Доступ запрещён']);
            exit;
        }
    }

    echo json_encode(['success' => true, 'request' => $request]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}