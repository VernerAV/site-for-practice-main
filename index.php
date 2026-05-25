<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ГБУ Жилищник района Строгино</title>
	 <!-- Подключение CSS -->
    <link rel="stylesheet" href="css/style.css">
	
</head>
<body>
    <!-- Заголовок -->
    <header>
       <?php include "templates/header.php" ?>
    </header>
<!-- меню -->

<!-- Основная страница -->
    <main>
<section class="banner">
    <div class="container">
        <div class="content">
            <h1>Срочный ремонт в вашем доме</h1>
            <p class="subtitle">Диспечерская служба работает 24/7</p>
            
            <div class="phone">
                <a href="tel:+74955395353">8 (495) 539-53-53</a>
                <span>Круглосуточно</span>
            </div>
            
           <a href="contact.php" class="btn">Оставить заявку на ремонт</a>
            
            <div class="tags">
                <span>Вода</span>
                <span>Лифты</span>
                <span>Отопление</span>
                <span>Электрика</span>
            </div>
        </div>
    </div>
</section>

        <!-- Таблица график приема -->
        <div class="schedule">
			<h1>График приема населения</h1>
            <table class="iksweb">
		<tr>
			<td></td>
			<td class="grey">Директор</td>
			<td>Первый заместитель, заместитель директора</td>
			<td class="grey">Главный инженер</td>
			<td>Начальники участков учреждения</td>
		</tr>
		<tr>
			<td>Понедельник</td>
			<td class="grey"></td>
			<td>17:00 - 20:00</td>
			<td class="grey"></td>
			<td></td>
		</tr>
		<tr>
			<td>Вторник</td>
			<td class="grey">16:00 - 19:00</td>
			<td></td>
			<td class="grey">17:00 - 20:00</td>
			<td>17:00 - 20:00</td>
		</tr>
		<tr>
			<td>Среда</td>
			<td class="grey"></td>
			<td></td>
			<td class="grey"></td>
			<td></td>
		</tr>
		<tr>
			<td>Четверг</td>
			<td class="grey">17:00 - 20:00</td>
			<td></td>
			<td class="grey"></td>
			<td></td>
		</tr>
		<tr>
			<td>Пятница</td>
			<td class="grey"></td>
			<td>16:00 - 19:00</td>
			<td class="grey"></td>
			<td></td>
		</tr>
		<tr>
			<td>Суббота</td>
			<td class="grey"></td>
			<td></td>
			<td class="grey">10:00 - 14:00</td>
			<td>10:00 - 14:00</td>
		</tr>
            </table>
        </div>

        <div class="news">
            <div class="block_news">
                <div class="block">
                    <img src="" alt="">
                    <h1></h1>
                    <p></p>
                    <p class="time"></p>
                </div>
            </div>
            <div class="rectangle"></div>
        </div>
    </main>

<section class="news-slider-section">
    <div class="container">
        <h1 class="section-title">Последние новости</h1>
        
        <div class="news-slider-container">
            <button class="slider-nav prev" onclick="slideNews(-1)">‹</button>
            
            <div class="news-slider-wrapper">
                <div class="news-slider-track" id="newsSliderTrack">
                    <!-- Слайды загрузятся через JS -->
                </div>
            </div>
            
            <button class="slider-nav next" onclick="slideNews(1)">›</button>
        </div>
    </div>
</section>

<section class="paid-services">
    <div class="container">
        <h1 class="section-title">Платные услуги</h1>
        
        <!-- Блок 1 -->
        <div class="service-block">
            <div class="service-content">
                <h3>Работают штатные специалисты</h3>
                <p>Все работы выполняются штатными специалистами управляющей компании, что гарантирует:</p>
                <ul>
                    <li><strong>Качество</strong> — мастера знают специфику инженерных систем вашего дома</li>
                    <li><strong>Надежность</strong> — официальный договор и ответственность за результат</li>
                    <li><strong>Безопасность</strong> — все сотрудники прошли проверку и имеют допуски</li>
                </ul>
                <a href="contact.php" class="btn btn-primary">Оставить заявку на ремонт</a>
            </div>
            <div class="service-image">
                <img src="img/specialists.png" alt="Штатные специалисты">
            </div>
        </div>
        
        <!-- Блок 2 -->
        <div class="service-block reverse">
            <div class="service-content">
                <h3>Гарантия на все виды работ</h3>
                <p>Мы предоставляем гарантию на все виды работ, что обеспечивает:</p>
                <ul>
                    <li><strong>Бесплатное устранение недостатков</strong> в течение установленного срока</li>
                    <li><strong>Оперативный выезд мастера</strong> при гарантийном случае</li>
                    <li><strong>Прозрачные условия</strong> и полную ответственность за результат</li>
                    <li><strong>Спокойствие и уверенность</strong> в качестве услуг</li>
                </ul>
                <a href="contact.php" class="btn btn-primary">Оставить заявку на ремонт</a>
            </div>
            <div class="service-image">
                <img src="img/warranty.png" alt="Гарантия на работы">
            </div>
        </div>
        
        <!-- Блок 3 -->
        <div class="service-block">
            <div class="service-content">
                <h3>Прозрачные цены без накруток</h3>
                <p>Мы гарантируем прозрачное ценообразование:</p>
                <ul>
                    <li><strong>Фиксированные цены</strong> по официальным тарифам УК</li>
                    <li><strong>Подробная смета</strong> до начала работ</li>
                    <li><strong>Отсутствие скрытых платежей</strong> и комиссий</li>
                    <li><strong>Возможность рассчитать точную стоимость онлайн</strong></li>
                </ul>
                <a href="contact.php" class="btn btn-primary">Оставить заявку на ремонт</a>
            </div>
            <div class="service-image">
                <img src="img/prices.png" alt="Прозрачные цены">
            </div>
        </div>
    </div>
</section>

<section class="useful-links">
    <div class="container">
        <h2 class="section-title">Полезные ресурсы</h2>
        <p class="section-subtitle">Все необходимые сервисы для жителей в одном месте</p>
        
        <div class="links-grid">
            <!-- Портал госуслуг Москвы -->
            <div class="link-card mos">
                <div class="link-icon">
                    <img src="img/icons/mos-ru.svg" alt="Мос.ру">
                </div>
                <div class="link-content">
                    <h3>Портал госуслуг Москвы</h3>
                    <div class="link-items">
                        <a href="https://www.mos.ru/pgu2/landing/172687" target="_blank" class="link-item">
                            <span class="link-icon-small">📄</span>
                            <span>Электронный ЕПД</span>
                        </a>
                        <a href="https://www.mos.ru/services/pokazaniya-vodi-i-tepla/" target="_blank" class="link-item">
                            <span class="link-icon-small">📊</span>
                            <span>Передача показаний счетчиков</span>
                        </a>
                        <a href="https://pgu.mos.ru/ru/application/guis/-47/" target="_blank" class="link-item">
                            <span class="link-icon-small">💳</span>
                            <span>Оплата услуг ЖКХ</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Правительственные ресурсы -->
            <div class="link-card government">
                <div class="link-icon">
                    <img src="img/icons/museum.png" alt="Правительство">
                </div>
                <div class="link-content">
                    <h3>Правительство Москвы</h3>
                    <div class="link-items">
                        <a href="https://www.mos.ru" target="_blank" class="link-item">
                            <span class="link-icon-small">🏛️</span>
                            <span>Официальный портал mos.ru</span>
                        </a>
                        <a href="https://dom.mos.ru" target="_blank" class="link-item">
                            <span class="link-icon-small">🏠</span>
                            <span>Портал "Дома Москвы"</span>
                        </a>
                        <a href="https://www.mos.ru/dgkh/" target="_blank" class="link-item">
                            <span class="link-icon-small">🔧</span>
                            <span>Департамент ЖКХ</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Общественные сервисы -->
            <div class="link-card public">
                <div class="link-icon">
                    <img src="img/icons/live-stream.png" alt="Общественные">
                </div>
                <div class="link-content">
                    <h3>Общественные сервисы</h3>
                    <div class="link-items">
                        <a href="https://gorod.mos.ru" target="_blank" class="link-item">
                            <span class="link-icon-small">🌆</span>
                            <span>Портал "Наш город Москва"</span>
                        </a>
                        <a href="https://reformagkh.ru" target="_blank" class="link-item">
                            <span class="link-icon-small">🔄</span>
                            <span>Портал "Реформа ЖКХ"</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

    <footer>
        <?php include "templates/footer.php"?>
    </footer>

        <script src="js/news-slider.js"></script>
</body>
</html>