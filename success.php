<?php
session_start();
require_once 'includes/config.php';

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$request_id) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, subject, status, created_at FROM message WHERE id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();
    if (!$request) {
        $error = 'Заявка не найдена.';
    }
} catch (PDOException $e) {
    $error = 'Ошибка базы данных.';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявка создана</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .success-container {
            max-width: 700px;
            margin: 50px auto;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success-icon { font-size: 72px; margin-bottom: 20px; }
        .success-title { font-size: 28px; color: #1a5f7a; margin-bottom: 10px; }
        .request-number { font-size: 20px; background: #f0f7fa; padding: 12px 24px; border-radius: 8px; display: inline-block; margin: 20px 0; }
        .info-block { text-align: left; background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .info-block p { margin: 8px 0; }
        .links { margin-top: 30px; display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; }
        .links a { display: inline-block; padding: 12px 25px; background: #1a5f7a; color: #fff; text-decoration: none; border-radius: 6px; transition: 0.3s; }
        .links a:hover { background: #0e4054; }
        .links .secondary { background: #6c757d; }
        .links .secondary:hover { background: #5a6268; }
        .error { color: #dc3545; font-size: 18px; }
    </style>
</head>
<body>
<?php require 'templates/header.php'; ?>
<div class="success-container">
    <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
        <a href="index.php" class="btn">Вернуться на главную</a>
    <?php else: ?>
        <div class="success-icon">✅</div>
        <h1 class="success-title">Заявка успешно создана!</h1>
        <div class="request-number">Номер заявки: <strong>#<?= $request['id'] ?></strong></div>
        <div class="info-block">
            <p><strong>Тема:</strong> <?= htmlspecialchars($request['subject']) ?></p>
            <p><strong>Статус:</strong> <?= htmlspecialchars($request['status']) ?></p>
            <p><strong>Дата:</strong> <?= date('d.m.Y H:i', strtotime($request['created_at'])) ?></p>
        </div>
        <p>Мы свяжемся с вами в ближайшее время.</p>
        <div class="links">
            <a href="user.php">🔐 Войти / Зарегистрироваться</a>
            <a href="check_status.php" class="secondary">🔍 Проверить статус</a>
        </div>
    <?php endif; ?>
</div>
<?php include 'templates/footer.php'; ?>
</body>
</html>