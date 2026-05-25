<?php
http_response_code(404); // Устанавливаем HTTP статус 404
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница не найдена - Ошибка 404</title>
    <link rel="stylesheet" href="css/404.css">
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
        
        <!-- Поиск по сайту -->
        <div class="error-search">
            <form class="search-box" onsubmit="return searchSite()">
                <input type="text" class="search-input" placeholder="Поиск по сайту..." id="search404">
                <button type="submit" class="search-button">Найти</button>
            </form>
        </div>
        
        <!-- Кнопки действий -->
        <div class="action-buttons">
            <a href="index.php" class="btn btn-primary">На главную</a>
            <a href="javascript:history.back()" class="btn">Вернуться назад</a>
            <a href="about.php" class="btn">Связаться с нами</a>
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
    
    <script>
        // Функция поиска по сайту
        function searchSite() {
            const query = document.getElementById('search404').value.trim();
            if (query) {
                // Перенаправляем на страницу поиска или ищем
                window.location.href = '/search.php?q=' + encodeURIComponent(query);
            } else {
                alert('Введите поисковый запрос');
            }
            return false;
        }
        
        // Автофокус на поле поиска
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('search404').focus();
            
            // Анимация появления
            const elements = document.querySelectorAll('.error-container > *');
            elements.forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
        
        // Отслеживание 404 ошибок для аналитики (если нужно)
        console.log('404 страница загружена:', {
            url: window.location.href,
            referrer: document.referrer,
            timestamp: new Date().toISOString()
        });
    </script>
</body>
</html>