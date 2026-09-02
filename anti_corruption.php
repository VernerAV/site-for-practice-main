<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Противодействие коррупции - ГБУ «Жилищник района Строгино»</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/anti_corruption.css">
</head>
<body>
    <!-- Хедер -->
    <?php require 'templates/header.php'; ?>

    <main class="anti-corruption-page">
        <div class="container">
            <!-- Заголовок -->
            <div class="page-header">
                <h1>Противодействие коррупции</h1>
                <p class="subtitle">Информация о мерах по предупреждению коррупционных правонарушений</p>
            </div>

            <!-- Вступление -->
            <section class="intro-section">
                <div class="intro-card">
                    <div class="intro-icon">⚖️</div>
                    <div class="intro-text">
                        <h2>О противодействии коррупции</h2>
                        <p>
                            Государственное бюджетное учреждение города Москвы «Жилищник района Строгино» 
                            ведёт активную работу по противодействию коррупции, строго соблюдая требования 
                            Федерального закона от 25.12.2008 № 273-ФЗ «О противодействии коррупции».
                        </p>
                        <p>
                            Мы стремимся обеспечить прозрачность и открытость своей деятельности, 
                            исключить любые проявления коррупции в работе с жителями и партнёрами.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Документы -->
            <section class="documents-section">
                <h2>Нормативные документы</h2>
                <div class="documents-grid">
                    <div class="doc-card">
                        <div class="doc-icon">📄</div>
                        <h3>Федеральное законодательство</h3>
                        <ul>
                            <li>
                                <a href="https://www.consultant.ru/document/cons_doc_LAW_82959/" target="_blank">
                                    Федеральный закон № 273-ФЗ «О противодействии коррупции»
                                </a>
                            </li>
                            <li>
                                <a href="https://www.consultant.ru/document/cons_doc_LAW_183906/" target="_blank">
                                    Федеральный закон № 230-ФЗ «О противодействии легализации доходов»
                                </a>
                            </li>
                            <li>
                                <a href="https://www.consultant.ru/document/cons_doc_LAW_10699/" target="_blank">
                                    Уголовный кодекс РФ (статьи о коррупционных преступлениях)
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="doc-card">
                        <div class="doc-icon">📑</div>
                        <h3>Региональные и ведомственные документы</h3>
                        <ul>
                            <li>
                                <a href="https://www.mos.ru/authority/documents/" target="_blank">
                                    Законы города Москвы по противодействию коррупции
                                </a>
                            </li>
                            <li>
                                <a href="https://www.mos.ru/dgkh/" target="_blank">
                                    Приказы Департамента ЖКХ города Москвы
                                </a>
                            </li>
                            <li>
                                <a href="#" target="_blank">
                                    Антикоррупционная политика ГБУ «Жилищник района Строгино»
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="doc-card">
                        <div class="doc-icon">📋</div>
                        <h3>Внутренние документы учреждения</h3>
                        <ul>
                            <li>
                                <a href="#" target="_blank">
                                    План противодействия коррупции на 2025-2026 гг.
                                </a>
                            </li>
                            <li>
                                <a href="#" target="_blank">
                                    Кодекс этики и служебного поведения сотрудников
                                </a>
                            </li>
                            <li>
                                <a href="#" target="_blank">
                                    Положение о конфликте интересов
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- План мероприятий -->
            <section class="plan-section">
                <h2>План мероприятий по противодействию коррупции</h2>
                <div class="plan-list">
                    <div class="plan-item">
                        <div class="plan-number">1</div>
                        <div class="plan-content">
                            <h3>Проведение проверок соблюдения антикоррупционного законодательства</h3>
                            <p>Ежеквартальные внутренние проверки деятельности сотрудников и руководителей.</p>
                            <span class="plan-period">Периодичность: ежеквартально</span>
                        </div>
                    </div>
                    <div class="plan-item">
                        <div class="plan-number">2</div>
                        <div class="plan-content">
                            <h3>Обучение сотрудников основам противодействия коррупции</h3>
                            <p>Проведение семинаров и тренингов для сотрудников всех уровней.</p>
                            <span class="plan-period">Периодичность: 2 раза в год</span>
                        </div>
                    </div>
                    <div class="plan-item">
                        <div class="plan-number">3</div>
                        <div class="plan-content">
                            <h3>Анализ жалоб и обращений граждан</h3>
                            <p>Мониторинг обращений на предмет возможных коррупционных нарушений.</p>
                            <span class="plan-period">Периодичность: постоянно</span>
                        </div>
                    </div>
                    <div class="plan-item">
                        <div class="plan-number">4</div>
                        <div class="plan-content">
                            <h3>Публикация отчётов о работе на официальном сайте</h3>
                            <p>Обеспечение прозрачности деятельности учреждения.</p>
                            <span class="plan-period">Периодичность: ежегодно</span>
                        </div>
                    </div>
                    <div class="plan-item">
                        <div class="plan-number">5</div>
                        <div class="plan-content">
                            <h3>Взаимодействие с правоохранительными органами</h3>
                            <p>Сотрудничество с прокуратурой и другими контролирующими органами.</p>
                            <span class="plan-period">Периодичность: по мере необходимости</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Контакты ответственных лиц -->
            <section class="contacts-section">
                <h2>Ответственные лица</h2>
                <div class="contacts-grid">
                    <div class="contact-person">
                        <div class="person-avatar">👤</div>
                        <div class="person-info">
                            <h3>Иванов Иван Иванович</h3>
                            <p class="position">Директор ГБУ «Жилищник района Строгино»</p>
                            <p><strong>Телефон:</strong> <a href="tel:+74957583822">(495) 758-38-22</a> (доб. 101)</p>
                            <p><strong>Email:</strong> <a href="mailto:director@gbu-strogino.ru">director@gbu-strogino.ru</a></p>
                            <p><strong>Приём:</strong> четверг 17:00–20:00</p>
                        </div>
                    </div>
                    <div class="contact-person">
                        <div class="person-avatar">👤</div>
                        <div class="person-info">
                            <h3>Петрова Мария Сергеевна</h3>
                            <p class="position">Юрисконсульт (ответственный за антикоррупционную работу)</p>
                            <p><strong>Телефон:</strong> <a href="tel:+74957583822">(495) 758-38-22</a> (доб. 205)</p>
                            <p><strong>Email:</strong> <a href="mailto:jurist@gbu-strogino.ru">jurist@gbu-strogino.ru</a></p>
                            <p><strong>Приём:</strong> вторник, пятница 10:00–17:00</p>
                        </div>
                    </div>
                    <div class="contact-person">
                        <div class="person-avatar">📞</div>
                        <div class="person-info">
                            <h3>Горячая линия по вопросам коррупции</h3>
                            <p class="position">Круглосуточный приём сообщений</p>
                            <p><strong>Телефон:</strong> <a href="tel:+74957583822">(495) 758-38-22</a> (доб. 999)</p>
                            <p><strong>Email:</strong> <a href="mailto:anticorr@gbu-strogino.ru">anticorr@gbu-strogino.ru</a></p>
                            <p class="note">Анонимность гарантируется</p>
                        </div>
                    </div>
                </div>
            </section>


            <!-- Полезные ссылки -->
            <section class="links-section">
                <h2>Дополнительные ресурсы</h2>
                <div class="external-links">
                    <a href="https://gbu-strogino.mos.ru/about/protivodeystvie-korruptsii/" target="_blank" class="ext-link">
                        <span class="ext-icon">🔗</span>
                        Генеральная прокуратура РФ
                    </a>
                    <a href="https://gbu-strogino.mos.ru/about/protivodeystvie-korruptsii/" target="_blank" class="ext-link">
                        <span class="ext-icon">🔗</span>
                        Антикоррупционный портал Москвы
                    </a>
                    <a href="https://gbu-strogino.mos.ru/about/protivodeystvie-korruptsii/" target="_blank" class="ext-link">
                        <span class="ext-icon">🔗</span>
                        Портал «Наш город. Москва»
                    </a>
                    <a href="https://gbu-strogino.mos.ru/about/protivodeystvie-korruptsii/" target="_blank" class="ext-link">
                        <span class="ext-icon">🔗</span>
                        Открытые данные Москвы
                    </a>
                </div>
            </section>
        </div>
    </main>

    <!-- Футер -->
    <?php require 'templates/footer.php'; ?>
</body>
</html>