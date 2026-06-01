<?php
session_start();
require_once 'config.php';
require_once 'check_auth.php';
checkAuth();

if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin.php?section=employees');
    exit;
}

$emp_id = (int)($_POST['emp_id'] ?? 0);
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$last_name = trim($_POST['last_name'] ?? '');
$first_name = trim($_POST['first_name'] ?? '');
$middle_name = trim($_POST['middle_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
$department_id = (int)($_POST['department_id'] ?? 0);
$position_id = (int)($_POST['position_id'] ?? 0);
$role = $_POST['role'] ?? 'executor';

try {
    // Получаем название должности и отдела по ID
    $stmtPos = $pdo->prepare("SELECT name FROM positions WHERE id = ?");
    $stmtPos->execute([$position_id]);
    $position_name = $stmtPos->fetchColumn();
    if (!$position_name) throw new Exception("Должность не найдена");

    $stmtDept = $pdo->prepare("SELECT department_name FROM department_rules WHERE id = ?");
    $stmtDept->execute([$department_id]);
    $department_name = $stmtDept->fetchColumn();
    if (!$department_name) throw new Exception("Отдел не найден");

    if ($emp_id == 0) {
        // Добавление
        if (empty($password)) throw new Exception('Пароль обязателен');
        if (empty($email)) throw new Exception('Email обязателен');
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$email, $hashed, $role]);
        $new_id = $pdo->lastInsertId();
        $emp_number = 'EMP' . str_pad($new_id, 5, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, last_name, first_name, middle_name, phone, birth_date, position, department, employee_id) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$new_id, $last_name, $first_name, $middle_name, $phone, $birth_date, $position_name, $department_name, $emp_number]);

        $pdo->commit();
        $_SESSION['message'] = "Сотрудник добавлен. Табельный номер: $emp_number";
        $_SESSION['message_type'] = 'success';
    } else {
        // Редактирование
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE users SET email = ?, role = ? WHERE id = ?");
        $stmt->execute([$email, $role, $emp_id]);
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $emp_id]);
        }
        $stmt = $pdo->prepare("UPDATE user_profiles SET last_name = ?, first_name = ?, middle_name = ?, phone = ?, birth_date = ?, position = ?, department = ? WHERE user_id = ?");
        $stmt->execute([$last_name, $first_name, $middle_name, $phone, $birth_date, $position_name, $department_name, $emp_id]);
        $pdo->commit();
        $_SESSION['message'] = "Данные сотрудника обновлены";
        $_SESSION['message_type'] = 'success';
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['message'] = 'Ошибка: ' . $e->getMessage();
    $_SESSION['message_type'] = 'error';
}
header("Location: ../admin.php?section=employees");
exit;