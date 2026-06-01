<?php
session_start();
require_once 'config.php';
require_once 'check_auth.php';
checkAuth();

// Доступ для executor, dispatcher, admin
$allowed = ['executor', 'dispatcher', 'admin'];
if (!in_array($_SESSION['user_role'], $allowed)) {
    $_SESSION['employee_error'] = 'Доступ запрещён.';
    header('Location: ../employee.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['request_id']) || empty($_POST['reply_text'])) {
    $_SESSION['employee_error'] = 'Неверные данные.';
    header('Location: ../employee.php');
    exit;
}

$request_id = (int)$_POST['request_id'];
$reply = trim($_POST['reply_text']);
$mark_read = isset($_POST['mark_read']) ? 1 : 0;
$user_id = $_SESSION['user_id'];

try {
    // Проверяем, что заявка назначена на этого сотрудника (или админ может отвечать на любую)
    if ($_SESSION['user_role'] !== 'admin') {
        $check = $pdo->prepare("SELECT assigned_to FROM message WHERE id = ?");
        $check->execute([$request_id]);
        $req = $check->fetch();
        if (!$req || $req['assigned_to'] != $user_id) {
            $_SESSION['employee_error'] = 'Вы не можете отвечать на эту заявку.';
            header('Location: ../employee.php');
            exit;
        }
    }

    // Обновляем ответ и статус прочитанного
    $sql = "UPDATE message SET admin_response = :reply, responded_at = NOW(), responded_by = :uid, is_read = :read WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':reply' => $reply,
        ':uid' => $user_id,
        ':read' => $mark_read,
        ':id' => $request_id
    ]);

    $_SESSION['employee_success'] = 'Ответ успешно отправлен пользователю.';
} catch (PDOException $e) {
    $_SESSION['employee_error'] = 'Ошибка БД: ' . $e->getMessage();
}

header('Location: ../employee.php');
exit;