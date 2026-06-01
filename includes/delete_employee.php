<?php
session_start();
require_once 'config.php';
require_once 'check_auth.php';
checkAuth();

if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    $_SESSION['message'] = 'Не указан ID сотрудника';
    $_SESSION['message_type'] = 'error';
    header('Location: ../admin.php?section=employees');
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("DELETE FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$id]);
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $pdo->commit();
    $_SESSION['message'] = "Сотрудник удалён";
    $_SESSION['message_type'] = 'success';
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['message'] = "Ошибка: " . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}
header("Location: ../admin.php?section=employees");
exit;