

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ГБУ "Жилищник Района Строгино"</title>
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/header_mobile.css">
    <link rel="stylesheet" href="css/accessibility-mode.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/mobile_all.css">
    <script src="js/search.js" defer></script>
    <script src="js/isAccessibilityMode.js" defer></script>
</head>
    <!-- ПК -->
    <div class="header">
        <a href="index.php" class="icon">
            <img src="img/icons/icon.png" alt="icon" id="icon">
            <h1>ГБУ "Жилищник Района Строгино"</h1>
        </a>

        <!-- Поиск -->
        <div id="search">
            <form action="search.php" method="get">
                <input type="text" name="query" id="searchInput" placeholder="Поиск новостей, услуг..." autocomplete="off">
                <button type="submit">
                    <img src="img/icons/search.png" alt="Поиск">
                </button>
            </form>
            <div class="search-suggestions" id="searchSuggestions"></div>
        </div>
        
        <!-- Кнопка переключения стилей на версию для слабовидящих  -->
    <button id="accessibilityToggle" aria-label="Версия для слабовидящих">
        👁 Версия для слабовидящих
    </button>

        <div class="enter">
            <?php if (isset($_SESSION['user_id'])): 
                $user_email = $_SESSION['user_email'] ?? '';
                $user_name = $_SESSION['user_name'] ?? '';
            ?>
            <div class="user-info">
                <div class="user-avatar">
                    <svg width="30" height="30" viewBox="0 0 30 30">
                        <circle cx="15" cy="15" r="15" fill="#3498db"/>
                        <text x="15" y="20" text-anchor="middle" fill="white" font-size="14">
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
                <a href="login.php" class="login-btn">Вход/регистрация</a>
            <?php endif; ?>
        </div>

        <!-- Мобильные контролы -->
        <div class="mobile-controls">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Меню">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    <nav class="main-menu">
        <ul>
            <li><a href="index.php">Главная</a></li>
            <li><a href="news.php">Новости</a></li>
            <li><a href="price.php">Платные услуги</a></li>
            <li><a href="about.php">О нас</a></li>

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
                <?php endif; ?>
            <?php else: ?>
                <li><a href="contact.php">Оставить заявку</a></li>
                <li><a href="check_status.php">Статус заявки</a></li>
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
            <li><a href="about.php">О нас</a></li>
        </ul>
        <div class="side-contact">
            <p>Телефон: 8(495) 758-38-22</p>
            <p>Эл. почта: gbu-strogino@mail.ru</p>
        </div>
    </div>
    <div class="overlay" id="overlay"></div>
        <?php
        // ===== ХЛЕБНЫЕ КРОШКИ =====
        // Определяем текущую страницу
        $current_file = basename($_SERVER['SCRIPT_NAME']);
        $page_titles = [
            'index.php'        => 'Главная',
            'news.php'         => 'Новости',
            'about.php'        => 'О нас',
            'contact.php'      => 'Оставить заявку',
            'price.php'        => 'Платные услуги',
            'admin.php'        => 'Панель администратора',
            'employee.php'     => 'Личный кабинет сотрудника',
            'user.php'         => 'Личный кабинет',
            'login.php'        => 'Вход',
            'check_status.php' => 'Статус заявки',
            'search.php'       => 'Результаты поиска'
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
        // AJAX подсказки для поиска в шапке (безопасная версия)
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
                                item.textContent = suggestion; // защита от XSS
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
        });

        function selectSuggestion(text) {
            document.getElementById('searchInput').value = text;
            document.getElementById('searchSuggestions').style.display = 'none';
            document.querySelector('#search form').submit();
        }
    </script>
