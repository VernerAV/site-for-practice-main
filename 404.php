<?php
http_response_code(404); // Устанавливаем HTTP статус 404
$query = isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES, 'UTF-8') : '';
// Логируем ошибку
$log = date('Y-m-d H:i:s') . ' - 404 - ' . $_SERVER['REQUEST_URI'] . ' - ' . $_SERVER['HTTP_REFERER'] . "\n";
file_put_contents('log/404.log', $log, FILE_APPEND);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница не найдена - Ошибка 404</title>
    <link rel="stylesheet" href="css/404.css">
    <link rel="stylesheet" href="css/mobile_all.css">
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🔍</div>
        <div class="error-code">404</div>
        <h1 class="error-title">Страница не найдена</h1>
        
        <p class="error-message">
            К сожалению, запрашиваемая вами страница не существует или была перемещена.
            Возможно, вы ошиблись при вводе адреса или страница была удалена.
        </p>
        
    
        
        <!-- Кнопки действий -->
        <div class="action-buttons">
            <a href="index.php" class="btn btn-primary">На главную</a>
            <a href="javascript:history.back()" class="btn">Вернуться назад</a>
            <a href="contact.php" class="btn">Связаться с нами</a>
        </div>
        
        <!-- Популярные страницы -->
        <div class="suggestions">
            <h3>Возможно, вы искали:</h3>
            <ul>
                <li><a href="index.php">Главная страница</a></li>
                <li><a href="about.php">О компании</a></li>
                <li><a href="price.php">Прайс-лист</a></li>
                <li><a href="contact.php">Контакты</a></li>
                <li><a href="news.php">Новости</a></li>
            </ul>
        </div>
        
        <!-- Информация о ошибке -->
        <div style="margin-top: 30px; font-size: 14px; opacity: 0.7;">
            <p>Ошибка 404: Страница не найдена | <?php echo date('d.m.Y H:i:s'); ?></p>
        </div>
    </div>
    
</body>
</html>