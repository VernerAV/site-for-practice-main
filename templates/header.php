<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="img/icons/icon.ico">
    <title>ГБУ "Жилищник Района Строгино"</title>
    <!-- Стили в правильном порядке -->
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/header_mobile.css">
    <link rel="stylesheet" href="css/accessibility-mode.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/footer_mobile.css">
    <script src="js/mobile-header.js" defer></script>
    <script src="js/search.js" defer></script>
    <script src="js/isAccessibilityMode.js" defer></script>
</head>

<!-- ВЕРХНЯЯ ШАПКА -->
<header class="header">
    <div class="header-inner">
        <a href="index.php" class="logo">
            <img src="img/icons/icon.png" alt="Герб">
            <span>ГБУ "Жилищник Района Строгино"</span>
        </a>

        <div class="search-wrap">
            <form action="search.php" method="get">
                <input type="text" name="query" id="searchInput" placeholder="Поиск..." autocomplete="off">
                <button type="submit" aria-label="Найти">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </button>
            </form>
            <div class="search-suggestions" id="searchSuggestions"></div>
        </div>

        <button id="accessibilityToggle" aria-label="Версия для слабовидящих">
            <span>👁</span> Версия для слабовидящих
        </button>

        <div class="user-block">
            <?php if (isset($_SESSION['user_id'])): 
                $user_email = $_SESSION['user_email'] ?? '';
                $user_name = $_SESSION['user_name'] ?? '';
            ?>
            <div class="user-info">
                <div class="avatar">
                    <svg width="32" height="32" viewBox="0 0 32 32">
                        <circle cx="16" cy="16" r="16" fill="#2c3e50"/>
                        <text x="16" y="21" text-anchor="middle" fill="white" font-size="14" font-weight="bold">
                            <?php echo strtoupper(substr($user_email ?: ($user_name ?: 'U'), 0, 1)); ?>
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
                <a href="login.php" class="login-btn">Вход / Регистрация</a>
            <?php endif; ?>
        </div>

        <button class="hamburger" id="hamburgerBtn" aria-label="Меню">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<!-- ГОРИЗОНТАЛЬНОЕ МЕНЮ -->
<nav class="main-nav">
    <div class="nav-inner">
        <ul class="nav-list">
            <li><a href="index.php">Главная</a></li>
            <li><a href="news.php">Новости</a></li>
            <li><a href="price.php">Платные услуги</a></li>
            
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">Контакты ▾</a>
                <ul class="dropdown-menu">
                    <li><a href="about.php">Об учреждении</a></li>
                    <li><a href="contacts.php">Контакты</a></li>
                    <li><a href="about.php#requisites">Реквизиты</a></li>
                </ul>
            </li>

            <li><a href="faq.php">Частые вопросы</a></li>
            <li><a href="anti_corruption.php">Антикоррупция</a></li>

            <?php if (isset($_SESSION['user_role'])): ?>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <li><a href="admin.php" class="admin-link">Панель администратора</a></li>
                <?php elseif ($_SESSION['user_role'] === 'executor'): ?>
                    <li><a href="employee.php">Личный кабинет сотрудника</a></li>
                <?php elseif ($_SESSION['user_role'] === 'dispatcher'): ?>
                    <li><a href="dispatcher.php">Личный кабинет диспетчера</a></li>
                <?php elseif ($_SESSION['user_role'] === 'user'): ?>
                    <li><a href="contact.php">Оставить заявку</a></li>
                    <li><a href="user.php">Личный кабинет</a></li>
                <?php endif; ?>
            <?php else: ?>
                <li><a href="contact.php">Оставить заявку</a></li>
                <li><a href="check_status.php">Статус заявки</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<!-- БОКОВОЕ МЕНЮ (МОБИЛЬНОЕ) -->
<div class="side-menu" id="sideMenu">
    <div class="side-menu-header">
        <button class="close-menu" id="closeMenuBtn">✕</button>
    </div>
    <ul>
        <?php if (isset($_SESSION['user_role'])): ?>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <li><a href="admin.php">Панель администратора</a></li>
            <?php elseif ($_SESSION['user_role'] === 'executor'): ?>
                <li><a href="employee.php">Личный кабинет сотрудника</a></li>
            <?php elseif ($_SESSION['user_role'] === 'dispatcher'): ?>
                <li><a href="dispatcher.php">Личный кабинет диспетчера</a></li>
            <?php elseif ($_SESSION['user_role'] === 'user'): ?>
                <li><a href="contact.php">Оставить заявку</a></li>
                <li><a href="user.php">Личный кабинет</a></li>
                <li><a href="check_status.php">Статус заявки</a></li>
            <?php endif; ?>
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
        <li><a href="about.php">Об учреждении</a></li>
        <li><a href="contacts.php">Контакты</a></li>
        <li><a href="faq.php">Частые вопросы</a></li>
        <li><a href="anti_corruption.php">Антикоррупция</a></li>
    </ul>
    <div class="side-contact">
        <p><a href="tel:+74957583822">📞 8 (495) 758-38-22</a></p>
        <p><a href="mailto:gbu-strogino@mail.ru">✉️ gbu-strogino@mail.ru</a></p>
    </div>
</div>
<div class="overlay" id="overlay"></div>

<!-- ХЛЕБНЫЕ КРОШКИ -->
<?php
$current_file = basename($_SERVER['SCRIPT_NAME']);
$page_titles = [
    'index.php'        => 'Главная',
    'news.php'         => 'Новости',
    'about.php'        => 'Об учреждении',
    'contact.php'      => 'Оставить заявку',
    'price.php'        => 'Платные услуги',
    'admin.php'        => 'Панель администратора',
    'employee.php'     => 'Личный кабинет сотрудника',
    'user.php'         => 'Личный кабинет',
    'login.php'        => 'Вход',
    'check_status.php' => 'Статус заявки',
    'search.php'       => 'Результаты поиска',
    'news_details.php' => 'Новость',
    'faq.php'          => 'Часто задаваемые вопросы',
    'anti_corruption.php' => 'Противодействие коррупции',
    'contacts.php'     => 'Контакты'
];
$current_title = $page_titles[$current_file] ?? ucfirst(str_replace('.php', '', $current_file));
$is_home = ($current_file === 'index.php');
?>
<div class="breadcrumbs">
    <ul>
        <li><a href="index.php">Главная</a></li>
        <?php if (!$is_home): ?>
            <li><span><?php echo htmlspecialchars($current_title); ?></span></li>
        <?php endif; ?>
    </ul>
</div>

<script>
// Поиск
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
                suggestionsBox.innerHTML = '';
                if (suggestions.length > 0) {
                    suggestions.forEach(suggestion => {
                        const item = document.createElement('div');
                        item.className = 'search-suggestion-item';
                        item.textContent = suggestion;
                        item.addEventListener('click', function() {
                            selectSuggestion(suggestion);
                        });
                        suggestionsBox.appendChild(item);
                    });
                    suggestionsBox.style.display = 'block';
                } else {
                    suggestionsBox.style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Ошибка загрузки подсказок:', error);
                suggestionsBox.style.display = 'none';
            });
    }

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.style.display = 'none';
        }
    });

    window.selectSuggestion = function(text) {
        document.getElementById('searchInput').value = text;
        document.getElementById('searchSuggestions').style.display = 'none';
        document.querySelector('#search form').submit();
    };
});
</script>