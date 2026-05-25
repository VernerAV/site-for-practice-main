<?php
session_start();
require_once 'check_auth.php';
checkAuth();

if (!isAdmin()) {
    header('Location: user.php');
    exit();
}

$id = $_GET['id'] ?? 0;
require_once 'config.php';

// Получаем сообщение
$stmt = $pdo->prepare("SELECT * FROM message WHERE id = ?");
$stmt->execute([$id]);
$message = $stmt->fetch();

if (!$message) {
    header('Location: admin_messages.php');
    exit();
}

// Помечаем как прочитанное
$pdo->prepare("UPDATE message SET is_read = 1 WHERE id = ?")->execute([$id]);

// Обработка ответа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_response'])) {
    $response = trim($_POST['response'] ?? '');
    
    if (!empty($response)) {
        $stmt = $pdo->prepare("UPDATE message SET 
            admin_response = ?, 
            responded_at = NOW(), 
            responded_by = ? 
            WHERE id = ?");
        
        $stmt->execute([
            htmlspecialchars($response),
            $_SESSION['user_email'],
            $id
        ]);
        
        header("Location: view_message.php?id=$id&success=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сообщение #<?php echo $id; ?> - Админ панель</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <!-- ... шапка ... -->
    
    <div class="admin-container">
        <h2>Сообщение #<?php echo $id; ?></h2>
        
        <div class="message-details">
            <div class="detail-row">
                <strong>От:</strong> <?php echo htmlspecialchars($message['user_name']); ?>
                &lt;<?php echo htmlspecialchars($message['user_email']); ?>&gt;
            </div>
            <div class="detail-row">
                <strong>Тема:</strong> <?php echo htmlspecialchars($message['subject']); ?>
            </div>
            <div class="detail-row">
                <strong>Дата:</strong> <?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>
            </div>
            <div class="detail-row">
                <strong>Сообщение:</strong>
                <div class="message-content">
                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                </div>
            </div>
            
            <!-- Техническая информация -->
            <div class="tech-info">
                <small>
                    IP: <?php echo htmlspecialchars($message['ip_address']); ?> |
                    Браузер: <?php echo htmlspecialchars(substr($message['user_agent'] ?? '', 0, 100)); ?>
                </small>
            </div>
        </div>
        
        <!-- Ответ администратора -->
        <div class="admin-response-section">
            <h3>Ответ администратора</h3>
            
            <?php if (!empty($message['admin_response'])): ?>
                <div class="existing-response">
                    <?php echo nl2br(htmlspecialchars($message['admin_response'])); ?>
                    <div class="response-meta">
                        Ответил: <?php echo htmlspecialchars($message['responded_by']); ?> |
                        Дата: <?php echo date('d.m.Y H:i', strtotime($message['responded_at'])); ?>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST">
                    <textarea name="response" rows="6" placeholder="Введите ответ пользователю..." required></textarea>
                    <button type="submit" name="send_response" class="btn btn-success">Отправить ответ</button>
                </form>
            <?php endif; ?>
        </div>
        
        <a href="admin_messages.php" class="btn">← Назад к списку</a>
    </div>
</body>
</html>