<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Карта сайта - ГБУ «Жилищник района Строгино»</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/footer_mobile.css">
    <link rel="stylesheet" href="css/sitemap.css">
</head>
<body>
    <!-- Хедер -->

    <main class="sitemap-page">
        <div class="container">
            <!-- Заголовок страницы -->
            <div class="page-header">
                <h1>Карта сайта</h1>
                <p class="subtitle">Все разделы и страницы сайта ГБУ «Жилищник района Строгино»</p>
            </div>

            <!-- Сетка разделов -->
            <div class="sitemap-grid">

                <!-- 1. Основные страницы -->
                <section class="sitemap-section">
                    <div class="section-head">
                        <span class="section-icon">🏠</span>
                        <h2>Основные страницы</h2>
                    </div>
                    <ul class="sitemap-list">
                        <li>
                            <a href="index.php">
                                <span class="link-title">Главная страница</span>
                                <span class="link-desc">Центральная страница с важными новостями и призывом к действию</span>
                            </a>
                        </li>
                        <li>
                            <a href="about.php">
                                <span class="link-title">О нас</span>
                                <span class="link-desc">Информация об организации, реквизиты, контакты</span>
                            </a>
                        </li>
                        <li>
                            <a href="news.php">
                                <span class="link-title">Новости</span>
                                <span class="link-desc">Лента новостей района с детальными страницами</span>
                            </a>
                        </li>
                        <li>
                            <a href="price.php">
                                <span class="link-title">Платные услуги</span>
                                <span class="link-desc">Каталог услуг с тарифами</span>
                            </a>
                        </li>
                        <li>
                            <a href="anti_corruption.php">
                                <span class="link-title">Противодействие коррупции</span>
                                <span class="link-desc">Меры по предупреждению коррупционных правонарушений</span>
                            </a>
                        </li>
                    </ul>
                </section>

                <!-- 2. Интерактивные сервисы -->
                <section class="sitemap-section">
                    <div class="section-head">
                        <span class="section-icon">⚙️</span>
                        <h2>Интерактивные сервисы</h2>
                    </div>
                    <ul class="sitemap-list">
                        <li>
                            <a href="contact.php">
                                <span class="link-title">Подача заявки</span>
                                <span class="link-desc">Оформление обращения или заявки на ремонт</span>
                            </a>
                        </li>
                        <li>
                            <a href="search.php">
                                <span class="link-title">Поиск по сайту</span>
                                <span class="link-desc">Быстрый поиск нужной информации</span>
                            </a>
                        </li>
                        <li>
                            <a href="check_status.php">
                                <span class="link-title">Отслеживание статуса обращения</span>
                                <span class="link-desc">Проверка состояния заявки по номеру</span>
                            </a>
                        </li>
                        <li>
                            <a href="faq.php">
                                <span class="link-title">Вопросы и ответы</span>
                                <span class="link-desc">Часто задаваемые вопросы жителей</span>
                            </a>
                        </li>
                    </ul>
                </section>

                <!-- 3. Пользовательский раздел -->
                <section class="sitemap-section">
                    <div class="section-head">
                        <span class="section-icon">👤</span>
                        <h2>Пользовательский раздел</h2>
                    </div>
                    <ul class="sitemap-list">
                        <li>
                            <a href="user.php">
                                <span class="link-title">Личный кабинет</span>
                                <span class="link-desc">Персональный раздел пользователя</span>
                            </a>
                        </li>
                        <li>
                            <a href="contact.php">
                                <span class="link-title">Создание новой заявки</span>
                                <span class="link-desc">Оформление нового обращения</span>
                            </a>
                        </li>
                    </ul>
                </section>

                <!-- 4. Административный раздел -->
                <section class="sitemap-section">
                    <div class="section-head">
                        <span class="section-icon">🛠️</span>
                        <h2>Административный раздел</h2>
                    </div>
                    <ul class="sitemap-list">
                        <li>
                            <a href="admin.php">
                                <span class="link-title">Панель управления</span>
                                <span class="link-desc">Главная страница администратора</span>
                            </a>
                        </li>
                        <li>
                            <a href="employee.php">
                                <span class="link-title">Панель управления</span>
                                <span class="link-desc">Главная страница сотрудника</span>
                            </a>
                        </li>
                        <li>
                            <a href="dispatcher.php">
                                <span class="link-title">Панель управления</span>
                                <span class="link-desc">Главная страница диспетчера</span>
                            </a>
                        </li>
                    </ul>
                </section>

                <!-- 5. Служебные страницы -->
                <section class="sitemap-section">
                    <div class="section-head">
                        <span class="section-icon">📄</span>
                        <h2>Служебные страницы</h2>
                    </div>
                    <ul class="sitemap-list">
                        <li>
                            <a href="login.php">
                                <span class="link-title">Вход</span>
                                <span class="link-desc">Авторизация на сайте</span>
                            </a>
                        </li>
                        <li>
                            <a href="register.php">
                                <span class="link-title">Регистрация</span>
                                <span class="link-desc">Создание нового аккаунта</span>
                            </a>
                        </li>
                        <li>
                            <a href="sitemap.php" class="current">
                                <span class="link-title">Карта сайта</span>
                                <span class="link-desc">Вы находитесь здесь</span>
                            </a>
                        </li>
                        <li>
                            <a href="404.php">
                                <span class="link-title">Страница 404</span>
                                <span class="link-desc">Страница не найдена</span>
                            </a>
                        </li>
                    </ul>
                </section>

            </div>

            <!-- Кнопка наверх -->
            <div class="back-top">
                <a href="#top">↑ Наверх</a>
            </div>
        </div>
    </main>

</body>
</html>