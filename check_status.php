<?php
session_start();
require_once 'includes/config.php';

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$request = null;
$error = null;

if ($request_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT m.*, c.name as category_name,
                   u.email as assigned_email,
                   CONCAT(up.last_name, ' ', up.first_name) as assigned_name
            FROM message m
            LEFT JOIN categories c ON m.category_id = c.id
            LEFT JOIN users u ON m.assigned_to = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE m.id = ?
        ");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();
        if (!$request) {
            $error = 'Заявка с таким номером не найдена.';
        }
    } catch (PDOException $e) {
        $error = 'Ошибка базы данных.';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проверка статуса заявки</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/check_status.css">
</head>
<body>
<?php include 'templates/header.php'; ?>

<div class="check-status-container">
    <h1>🔍 Проверка статуса заявки</h1>

    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <div class="form-wrapper">
            <form method="get" action="">
                <div class="form-group">
                    <label for="id">Введите номер заявки:</label>
                    <input type="number" id="id" name="id" min="1" placeholder="Например, 123" required>
                </div>
                <button type="submit" class="btn btn-primary">Проверить</button>
            </form>
        </div>
    <?php elseif ($request): ?>
        <div class="result-card">
            <div class="result-header">
                <span class="request-number">Заявка #<?= $request['id'] ?></span>
                <span class="status-badge status-<?= htmlspecialchars($request['status']) ?>">
                    <?= htmlspecialchars($request['status']) ?>
                </span>
            </div>
            <div class="result-body">
                <div class="detail-row">
                    <span class="label">Тема:</span>
                    <span class="value"><?= htmlspecialchars($request['subject']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Категория:</span>
                    <span class="value"><?= htmlspecialchars($request['category_name'] ?? 'Не указана') ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Дата создания:</span>
                    <span class="value"><?= date('d.m.Y H:i', strtotime($request['created_at'])) ?></span>
                </div>
                <?php if (!empty($request['message'])): ?>
                <div class="detail-row">
                    <span class="label">Описание:</span>
                    <span class="value"><?= nl2br(htmlspecialchars($request['message'])) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($request['admin_response'])): ?>
                <div class="detail-row">
                    <span class="label">Ответ администратора:</span>
                    <span class="value"><?= nl2br(htmlspecialchars($request['admin_response'])) ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($request['assigned_name'])): ?>
                <div class="detail-row">
                    <span class="label">Исполнитель:</span>
                    <span class="value"><?= htmlspecialchars($request['assigned_name']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($request['estimated_hours'] !== null): ?>
                <div class="detail-row">
                    <span class="label">Оценочное время (ч):</span>
                    <span class="value"><?= number_format($request['estimated_hours'], 2, ',', ' ') ?></span>
                </div>
                <?php endif; ?>
            </div>
            <div class="result-footer">
                <a href="check_status.php" class="btn btn-secondary">Проверить другую заявку</a>
                <a href="index.php" class="btn btn-primary">На главную</a>
            </div>
        </div>
    <?php else: ?>
        <div class="form-wrapper">
            <p class="info-text">Введите номер заявки, чтобы узнать её текущий статус.</p>
            <form method="get" action="">
                <div class="form-group">
                    <label for="id">Номер заявки:</label>
                    <input type="number" id="id" name="id" min="1" placeholder="Например, 123" required>
                </div>
                <button type="submit" class="btn btn-primary">Проверить</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>
</body>
</html>