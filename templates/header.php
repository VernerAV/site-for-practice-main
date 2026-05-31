<?php 
session_start(); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <!-- Подключение CSS -->
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/header_mobile.css">
    <!-- Скрипт анимация -->
     <script src="js/search.js" defer></script>
     <script src="js/isAccessibilityMode.js" defer></script>
</head>
<body>
    <!-- ПК -->
    <div class="header">
        <div class="icon">
            <img src="img/icons/icon.png" alt="icon">
            <h1>ГБУ "Жилищник Района Строгино"</h1>
        </div>
   
  <!-- Поиск -->
<div id="search">
    <form action="search.php" method="get">
        <input type="text" name="query" id="searchInput" placeholder="Поиск новостей, услуг..." 
               autocomplete="off">
        <button type="submit">
            <img src="img/icons/search.png" alt="Поиск">
        </button>
    </form>
    <div class="search-suggestions" id="searchSuggestions"></div>
</div>

<script>
// AJAX подсказки для поиска в шапке
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const suggestionsBox = document.getElementById('searchSuggestions');
    let timeoutId;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(timeoutId);
        const query = this.value.trim();
        
        if (query.length >= 2) {
            timeoutId = setTimeout(() => {
                fetchSuggestions(query);
            }, 300);
        } else {
            suggestionsBox.style.display = 'none';
        }
    });
    
    function fetchSuggestions(query) {
        fetch('includes/search_suggestions.php?query=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(suggestions => {
                if (suggestions.length > 0) {
                    suggestionsBox.innerHTML = suggestions.map(suggestion => 
                        `<div class="search-suggestion-item" onclick="selectSuggestion('${suggestion.replace("'", "\\'")}')">
                            ${suggestion}
                        </div>`
                    ).join('');
                    suggestionsBox.style.display = 'block';
                } else {
                    suggestionsBox.style.display = 'none';
                }
            });
    }
    
    // Закрытие подсказок при клике вне поля
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.style.display = 'none';
        }
    });
});

function selectSuggestion(text) {
    document.getElementById('searchInput').value = text;
    document.getElementById('searchSuggestions').style.display = 'none';
    document.querySelector('#search form').submit();
}
</script>
        


        
        <div class="enter">
            <?php 
              if (isset($_SESSION['user_id'])): 
                $user_email = $_SESSION['user_email'] ?? '';
                $user_name = $_SESSION['user_name'] ?? '';
            ?>
        <div class="user-info">
            <div class="user-avatar">
                <svg width="30" height="30" viewBox="0 0 30 30">
                    <circle cx="15" cy="15" r="15" fill="#3498db"/>
                    <text x="15" y="20" text-anchor="middle" fill="white" font-size="14">
                        <?php 
                        // Первая буква email или имени
                        echo strtoupper(substr($user_email ?: ($user_name ?: 'U'), 0, 1)); 
                        ?>
                    </text>
                </svg>
            </div>
            <div class="user-details">
                <span class="user-email"><?php echo htmlspecialchars($user_email); ?></span>
                <?php if (!empty($user_name)): ?>
                    <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
                <?php endif; ?>
            </div>
            <a href="includes/logout.php" class="logout-btn">Выйти</a>
        </div>
            <?php else: ?>
                <a href="login.php" class="login-btn">Вход/регистрация</a>
            <?php endif; ?>
        </div>
<!-- мобильная версия -->
        <div class="mobile-controls">
    <button class="hamburger-btn" id="hamburgerBtn" aria-label="Меню">
        <span></span><span></span><span></span>
    </button>
</div>
    </div>
 
<button id="accessibilityToggle" aria-label="Версия для слабовидящих">
  👁 Версия для слабовидящих
</button>
    <nav class="main-menu">

        <ul>
            <li><a href="index.php">Главная</a></li>
            <li><a href="news.php">Новости</a></li>
            <li><a href="price.php">Платные услуги</a></li>
            <li><a href="about.php">О нас</a></li>

            <?php if (isset($_SESSION['user_role'])): ?>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <!-- Админ: Заявки + Панель администратора -->
                    <li><a href="employee.php">Заявки</a></li>
                    <li><a href="admin.php">Панель администратора</a></li>
                <?php elseif ($_SESSION['user_role'] === 'user'): ?>
                    <!-- Обычный пользователь: Оставить заявку + Личный кабинет -->
                    <li><a href="contact.php">Оставить заявку</a></li>
                    <li><a href="admin.php">Личный кабинет</a></li>
                <?php endif; ?>
            <?php else: ?>
                <!-- Неавторизованный: только Оставить заявку -->
                <li><a href="contact.php">Оставить заявку</a></li>
            <?php endif; ?>
        </ul>


        <div class="contact-info">
            <p>Телефон: 8(495) 758-38-22</p>
            <p>Эл. почта: gbu-strogino@mail.ru</p>
        </div>
    </nav>
        <script src="js/mobile-header.js" defer></script>
        <!-- Боковое меню (мобильное) -->
<div class="side-menu" id="sideMenu">
    <div class="side-menu-header">
        <button class="close-menu" id="closeMenuBtn">&times;</button>
    </div>
    <ul>
         <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
            <li><a href="employee.php">Заявки</a></li>
            <li><a href="admin.php">Панель администратора</a></li>
        <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'user'): ?>
            <li><a href="contact.php">Оставить заявку</a></li>
            <li><a href="admin.php">Личный кабинет</a></li>
        <?php else: ?>
            <li><a href="login.php">Вход / Регистрация</a></li>
            <li><a href="contact.php">Оставить заявку</a></li>
            <?php endif; ?>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="includes/logout.php">Выйти</a></li>
        <?php endif; ?>
        <li><a href="index.php">Главная</a></li>
        <li><a href="news.php">Новости</a></li>
        <li><a href="price.php">Платные услуги</a></li>
        <li><a href="about.php">О нас</a></li>
       
    </ul>
    <div class="side-contact">
        <p>Телефон: 8(495) 758-38-22</p>
        <p>Эл. почта: gbu-strogino@mail.ru</p>
    </div>
</div>
<div class="overlay" id="overlay"></div>

<script src="js/mobile-header.js"></script>
</body>
</html>