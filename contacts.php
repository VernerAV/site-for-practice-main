<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакты - ГБУ «Жилищник района Строгино»</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/contacts.css">
</head>
<body>
    <!-- Хедер -->
    <?php require 'templates/header.php'; ?>

    <main class="contacts-page">
        <div class="container">
            <!-- Заголовок -->
            <div class="page-header">
                <h1>Контакты</h1>
                <p class="subtitle">Как с нами связаться и где нас найти</p>
            </div>

            <!-- Основная сетка -->
            <div class="contacts-grid">
                <!-- Левая колонка: контакты -->
                <div class="contact-info-block">
                    <div class="info-card">
                        <div class="icon">📍</div>
                        <h3>Адрес</h3>
                        <p>123181, г. Москва,<br>ул. Маршала Катукова, д. 9, к. 3</p>
                    </div>

                    <div class="info-card">
                        <div class="icon">📞</div>
                        <h3>Телефоны</h3>
                        <p><strong>Приёмная:</strong> <a href="tel:+74957583822">(495) 758-38-22</a></p>
                        <p><strong>Диспетчерская (круглосуточно):</strong> <a href="tel:+74955395353">8 (495) 539-53-53</a></p>
                    </div>

                    <div class="info-card">
                        <div class="icon">✉️</div>
                        <h3>Электронная почта</h3>
                        <p><a href="mailto:gbu-strogino@mail.ru">gbu-strogino@mail.ru</a></p>
                    </div>

                    <div class="info-card">
                        <div class="icon">🕒</div>
                        <h3>Режим работы</h3>
                        <p><strong>Пн–Чт:</strong> 9:00 – 18:00</p>
                        <p><strong>Пт:</strong> 9:00 – 16:45</p>
                        <p><strong>Обед:</strong> 13:00 – 13:45</p>
                        <p><strong>Сб–Вс:</strong> выходной</p>
                        <p class="note">Диспетчерская работает круглосуточно</p>
                    </div>
                </div>

                <!-- Правая колонка: карта + доп. инфо -->
                <div class="map-block">
                    <h3>Схема проезда</h3>
                    <div class="map-wrapper">
                        <!-- Яндекс.Карты (можно заменить на изображение) -->
                        <iframe src="https://yandex.ru/map-widget/v1/?um=constructor%3A1a2b3c4d5e6f7g8h9i0j&source=constructor" 
                                width="100%" height="350" frameborder="0" allowfullscreen>
                        </iframe>
                    </div>

                    <div class="map-links">
                        <a href="https://yandex.ru/maps/213/moscow/house/ulitsa_marshala_katukova_9k3/Z04YdQBkTkADQFtvfXx0dX5iYg==/" 
                           target="_blank" class="map-link">Яндекс.Карты</a>
                        <a href="https://www.google.com/maps/search/ул.+Маршала+Катукова,+9+к.3+Москва/" 
                           target="_blank" class="map-link">Google Карты</a>
                    </div>

                    <div class="additional-info">
                        <h4>Как добраться</h4>
                        <p><strong>Метро:</strong> «Строгино» (выход к ул. Маршала Катукова), пешком 5 минут.</p>
                        <p><strong>Автобусы:</strong> № 137, 277, 626, 652, 687, 743 (остановка «Улица Маршала Катукова, 9»).</p>
                    </div>

                    <div class="requisites">
                        <h4>Реквизиты (кратко)</h4>
                        <p><strong>ОГРН:</strong> 5137746251935</p>
                        <p><strong>ИНН:</strong> 7734715527</p>
                        <p><strong>КПП:</strong> 773401001</p>
                        <p class="more-link"><a href="about.php">Подробнее на странице «О нас»</a></p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Футер -->
    <?php require 'templates/footer.php'; ?>
</body>
</html>