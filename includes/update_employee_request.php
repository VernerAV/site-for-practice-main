<?php
session_start();
require_once 'config.php';
require_once 'check_auth.php';
checkAuth();

// Доступ только для executor (и admin? пусть только executor меняет статус)
if ($_SESSION['user_role'] !== 'executor') {
    $_SESSION['employee_error'] = 'Доступ запрещён.';
    header('Location: ../employee.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['request_id'], $_POST['action'])) {
    $_SESSION['employee_error'] = 'Неверный запрос.';
    header('Location: ../employee.php');
    exit;
}

$request_id = (int)$_POST['request_id'];
$action = $_POST['action'];
$executor_id = $_SESSION['user_id'];

try {
    // Проверяем, что заявка принадлежит этому исполнителю
    $check = $pdo->prepare("SELECT assigned_to, status FROM message WHERE id = ?");
    $check->execute([$request_id]);
    $req = $check->fetch();
    if (!$req || $req['assigned_to'] != $executor_id) {
        $_SESSION['employee_error'] = 'Вы не можете изменить эту заявку.';
        header('Location: ../employee.php');
        exit;
    }

    if ($action === 'take') {
        // Взять в работу
        if ($req['status'] !== 'новая') {
            $_SESSION['employee_error'] = 'Заявка уже не в статусе "новая".';
        } else {
            $stmt = $pdo->prepare("UPDATE message SET status = 'в работе' WHERE id = ?");
            $stmt->execute([$request_id]);
            $_SESSION['employee_success'] = 'Заявка взята в работу.';
        }
    } elseif ($action === 'complete') {
        // Выполнить
        if ($req['status'] !== 'в работе') {
            $_SESSION['employee_error'] = 'Заявка должна быть в статусе "в работе".';
        } else {
            $stmt = $pdo->prepare("UPDATE message SET status = 'выполнена' WHERE id = ?");
            $stmt->execute([$request_id]);
            $_SESSION['employee_success'] = 'Заявка выполнена.';
        }
    } elseif ($action === 'return') {
        // Вернуть (очистить назначение и сбросить статус)
        if ($req['status'] === 'выполнена') {
            $_SESSION['employee_error'] = 'Выполненную заявку нельзя вернуть.';
        } else {
            $stmt = $pdo->prepare("UPDATE message SET assigned_to = NULL, assigned_at = NULL, assign_comment = NULL, status = 'новая' WHERE id = ?");
            $stmt->execute([$request_id]);
            // Также можно залогировать в assignment_log
            $log = $pdo->prepare("INSERT INTO assignment_log (request_id, request_type, assigned_to, assigned_at, type, performed_by, comment) VALUES (?, 'user', ?, NOW(), 'manual', ?, 'Возврат исполнителем')");
            $log->execute([$request_id, $executor_id, $executor_id]);
            $_SESSION['employee_success'] = 'Заявка возвращена. Она появится в админ-панели для переназначения.';
        }
    } else {
        $_SESSION['employee_error'] = 'Неизвестное действие.';
    }
} catch (PDOException $e) {
    $_SESSION['employee_error'] = 'Ошибка БД: ' . $e->getMessage();
}

header('Location: ../employee.php');
exit;