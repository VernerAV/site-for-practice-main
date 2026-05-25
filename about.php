<?php
// about.php - Страница "О компании"
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>О компании - Жилищник района Строгино</title>
    <link rel="stylesheet" href="css/about.css">
</head>
<body>
    <!-- Подключаем хедер -->
    <?php 
    if (file_exists('templates/header.php')) {
        include 'templates/header.php';
    }
    ?>
    
    <main class="container">
        <!-- Шапка страницы -->
        <header class="page-header">
            <h1>О компании</h1>
            <div class="page-subtitle">Государственное бюджетное учреждение города Москвы «Жилищник района Строгино»</div>
        </header>
        
        <!-- Основное содержание -->
        <section class="content-section">
            <h2>О компании</h2>
            
            <div class="content-text">
                В связи с реорганизацией, во исполнение постановления Правительства Москвы от 14 марта 2013г. № 146-ПП 
                «О проведении эксперимента по оптимизации деятельности отдельных государственных учреждений города Москвы 
                государственных унитарных предприятий города Москвы, осуществляющих деятельность в сфере городского хозяйства 
                города Москвы», в форме преобразования Государственного унитарного предприятия города Москвы Дирекция единого 
                заказчика района «Строгино» (ГУП ДЕЗ района «Строгино») в Государственное бюджетное учреждение города Москвы 
                «Жилищник района Строгино» (ГБУ «Жилищник района Строгино»), ГБУ «Жилищник района Строгино» является 
                правопреемником по всем обязательствам ГУП ДЕЗ района «Строгино».
            </div>
            
            <!-- Реквизиты компании -->
            <div class="company-details">
                <h3 style="color: #2c3e50; margin-bottom: 20px; font-size: 20px;">Реквизиты организации</h3>
                <div class="details-grid">
                    <div class="detail-item">
                        <span class="info-label">Полное наименование:</span>
                        <span class="info-value">Государственное бюджетное учреждение города Москвы «Жилищник района Строгино»</span>
                    </div>
                    <div class="detail-item">
                        <span class="info-label">Сокращенное наименование:</span>
                        <span class="info-value">ГБУ «Жилищник района Строгино»</span>
                    </div>
                    <div class="detail-item">
                        <span class="info-label">ОГРН:</span>
                        <span class="info-value">5137746251935</span>
                    </div>
                    <div class="detail-item">
                        <span class="info-label">ИНН:</span>
                        <span class="info-value">7734715527</span>
                    </div>
                    <div class="detail-item">
                        <span class="info-label">КПП:</span>
                        <span class="info-value">773401001</span>
                    </div>
                </div>
            </div>
            
            <!-- Контактная информация -->
            <div class="contact">
                <h3>Контактная информация</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Почтовый адрес:</span>
                        <span class="info-value">123181, г. Москва, ул. Маршала Катукова, д. 9, к. 3</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Фактический адрес:</span>
                        <span class="info-value">123181, г. Москва, ул. Маршала Катукова, д. 9, к. 3</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Телефон:</span>
                        <span class="info-value">(495) 758-38-22</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Сайт управляющей компании:</span>
                        <span class="info-value"><a href="http://www.gbu-strogino.ru" target="_blank">www.gbu-strogino.ru</a></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Электронная почта:</span>
                        <span class="info-value"><a href="mailto:gbu-strogino@mail.ru">gbu-strogino@mail.ru</a></span>
                    </div>
                </div>
            </div>
            
            <!-- Официальные сайты -->
            <div class="official-links">
                <h3 style="color: #2c3e50; margin-bottom: 15px; font-size: 20px;">Официальные сайты по раскрытию информации</h3>
                <ul class="links-list">
                    <li>
                        <a href="http://www.reformagkh.ru" target="_blank" rel="noopener noreferrer">
                            www.reformagkh.ru
                        </a>
                    </li>
                    <li>
                        <a href="http://www.dom.mos.ru" target="_blank" rel="noopener noreferrer">
                            www.dom.mos.ru
                        </a>
                    </li>
                </ul>
            </div>
        </section>
    </main>
    
    <!-- Подключаем футер -->
    <?php 
    if (file_exists('templates/footer.php')) {
        include 'templates/footer.php';
    }
    ?>
</body>
</html>