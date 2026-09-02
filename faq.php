<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Часто задаваемые вопросы - ГБУ «Жилищник района Строгино»</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/faq.css">
</head>
<body>
    <!-- Хедер -->
    <?php require 'templates/header.php'; ?>

    <main class="faq-page">
        <div class="container">
            <!-- Заголовок -->
            <div class="page-header">
                <h1>Часто задаваемые вопросы</h1>
                <p class="subtitle">Ответы на самые популярные вопросы жителей</p>
            </div>

            <!-- Поиск по вопросам (опционально) -->
            <div class="faq-search">
                <input type="text" id="faqSearch" placeholder="🔍 Поиск по вопросам..." class="search-input">
            </div>

            <!-- Аккордеон FAQ -->
            <div class="faq-list" id="faqList">
                <!-- Вопрос 1 -->
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span class="question-text">Как подать заявку на ремонт в квартире?</span>
                        <span class="faq-toggle">+</span>
                    </button>
                    <div class="faq-answer">
                        <p>Вы можете подать заявку несколькими способами:</p>
                        <ul>
                            <li>Через форму на сайте в разделе <a href="contact.php">«Оставить заявку»</a>;</li>
                            <li>По телефону диспетчерской службы: <a href="tel:+74955395353">8 (495) 539-53-53</a> (круглосуточно);</li>
                            <li>Лично в офисе управляющей компании по адресу: ул. Маршала Катукова, д. 9, к. 3 (приёмная).</li>
                        </ul>
                        <p>После подачи заявки вы получите номер для отслеживания статуса.</p>
                    </div>
                </div>

                <!-- Вопрос 2 -->
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span class="question-text">Какие платные услуги предоставляет «Жилищник»?</span>
                        <span class="faq-toggle">+</span>
                    </button>
                    <div class="faq-answer">
                        <p>Мы предлагаем широкий спектр платных услуг для жителей:</p>
                        <ul>
                            <li>Сантехнические работы (замена труб, смесителей, ремонт унитазов);</li>
                            <li>Электромонтажные работы (замена проводки, установка розеток, светильников);</li>
                            <li>Отделочные работы (покраска, побелка, укладка плитки);</li>
                            <li>Ремонт и обслуживание внутридомовых инженерных систем.</li>
                        </ul>
                        <p>Полный перечень услуг и цены вы можете посмотреть на странице <a href="price.php">«Платные услуги»</a>.</p>
                    </div>
                </div>

                <!-- Вопрос 3 -->
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span class="question-text">Как узнать статус моей заявки?</span>
                        <span class="faq-toggle">+</span>
                    </button>
                    <div class="faq-answer">
                        <p>Проверить статус заявки можно двумя способами:</p>
                        <ul>
                            <li>На сайте в разделе <a href="check_status.php">«Статус заявки»</a> – введите номер, полученный при подаче;</li>
                            <li>Позвоните в диспетчерскую службу по телефону <a href="tel:+74955395353">8 (495) 539-53-53</a>.</li>
                        </ul>
                        <p>Мы стараемся обрабатывать заявки в течение 1–2 рабочих дней.</p>
                    </div>
                </div>

                <!-- Вопрос 4 -->
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span class="question-text">Где я могу передать показания счётчиков воды и тепла?</span>
                        <span class="faq-toggle">+</span>
                    </button>
                    <div class="faq-answer">
                        <p>Передать показания можно через официальный портал <a href="https://www.mos.ru/services/pokazaniya-vodi-i-tepla/" target="_blank">mos.ru</a> в разделе «Передача показаний». Также вы можете воспользоваться мобильным приложением «Госуслуги Москвы».</p>
                        <p>Если у вас возникли трудности, обратитесь в нашу приёмную по телефону <a href="tel:+74957583822">(495) 758-38-22</a>.</p>
                    </div>
                </div>

                <!-- Вопрос 5 -->
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span class="question-text">Куда обращаться с жалобой на работу управляющей компании?</span>
                        <span class="faq-toggle">+</span>
                    </button>
                    <div class="faq-answer">
                        <p>Вы можете обратиться с жалобой следующими способами:</p>
                        <ul>
                            <li>Оставить обращение через форму на сайте (раздел <a href="contact.php">«Оставить заявку»</a> – выберите тип «Жалоба»);</li>
                            <li>Позвонить на горячую линию Департамента ЖКХ города Москвы: <strong>+7 (495) 777-77-77</strong>;</li>
                            <li>Написать на электронную почту: <a href="mailto:gbu-strogino@mail.ru">gbu-strogino@mail.ru</a>.</li>
                        </ul>
                        <p>Все обращения рассматриваются в установленный законом срок.</p>
                    </div>
                </div>

                <!-- Вопрос 6 -->
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span class="question-text">Каков режим работы офиса управляющей компании?</span>
                        <span class="faq-toggle">+</span>
                    </button>
                    <div class="faq-answer">
                        <p>Офис работает по следующему графику:</p>
                        <ul>
                            <li><strong>Понедельник – четверг:</strong> 9:00 – 18:00</li>
                            <li><strong>Пятница:</strong> 9:00 – 16:45</li>
                            <li><strong>Обеденный перерыв:</strong> 13:00 – 13:45</li>
                            <li><strong>Суббота и воскресенье:</strong> выходные</li>
                        </ul>
                        <p>Диспетчерская служба работает <strong>круглосуточно</strong> по телефону <a href="tel:+74955395353">8 (495) 539-53-53</a>.</p>
                    </div>
                </div>

                <!-- Вопрос 7 -->
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span class="question-text">Как оплатить коммунальные услуги через сайт?</span>
                        <span class="faq-toggle">+</span>
                    </button>
                    <div class="faq-answer">
                        <p>Оплатить ЖКУ можно на портале <a href="https://pgu.mos.ru/ru/application/guis/-47/" target="_blank">pgu.mos.ru</a> (раздел «Оплата услуг ЖКХ»). Для этого понадобится номер лицевого счёта.</p>
                        <p>Также вы можете воспользоваться мобильными приложениями банков или платёжными терминалами. Квитанции с QR-кодом принимаются в любом отделении Почты России.</p>
                    </div>
                </div>

                <!-- Вопрос 8 -->
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span class="question-text">Что делать, если в подъезде сломался лифт?</span>
                        <span class="faq-toggle">+</span>
                    </button>
                    <div class="faq-answer">
                        <p>Немедленно сообщите о неисправности диспетчеру по телефону <a href="tel:+74955395353">8 (495) 539-53-53</a>. Также можно оставить заявку через сайт (раздел <a href="contact.php">«Оставить заявку»</a>, выберите тип «Лифт»).</p>
                        <p>Аварийная служба лифтов выезжает в течение 1 часа в рабочее время и в течение 2 часов в ночное время и выходные дни.</p>
                    </div>
                </div>

                <!-- Вопрос 9 -->
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span class="question-text">Предоставляете ли вы услуги по установке счётчиков?</span>
                        <span class="faq-toggle">+</span>
                    </button>
                    <div class="faq-answer">
                        <p>Да, мы устанавливаем счётчики воды, тепла и электроэнергии. Все работы выполняются штатными специалистами с оформлением акта и гарантией.</p>
                        <p>Для заказа услуги оставьте заявку в разделе <a href="contact.php">«Оставить заявку»</a> или позвоните в приёмную <a href="tel:+74957583822">(495) 758-38-22</a>. Стоимость уточняется при замере.</p>
                    </div>
                </div>

                <!-- Вопрос 10 -->
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false">
                        <span class="question-text">Как связаться с участковым инженером?</span>
                        <span class="faq-toggle">+</span>
                    </button>
                    <div class="faq-answer">
                        <p>Информацию о вашем участковом инженере можно получить в приёмной управляющей компании или по телефону <a href="tel:+74957583822">(495) 758-38-22</a>. Также вы можете оставить заявку на сайте, и инженер свяжется с вами в течение рабочего дня.</p>
                        <p>График приёма специалистов указан на <a href="index.php#schedule">главной странице</a> в разделе «График приёма населения».</p>
                    </div>
                </div>
            </div>

            <!-- Дополнительный блок: если не нашли ответ -->
            <div class="faq-help">
                <p>Не нашли ответ на свой вопрос?</p>
                <a href="contact.php" class="btn btn-primary">Обратиться в поддержку</a>
            </div>
        </div>
    </main>

    <!-- Футер -->
    <?php require 'templates/footer.php'; ?>

    <!-- Скрипт для аккордеона и поиска -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // === Аккордеон ===
            const questions = document.querySelectorAll('.faq-question');
            questions.forEach(button => {
                button.addEventListener('click', function() {
                    const isExpanded = this.getAttribute('aria-expanded') === 'true';
                    // Закрываем все остальные (можно убрать, если хотите, чтобы открывалось несколько)
                    // Но мы сделаем так, чтобы можно было открыть несколько (удобнее для поиска)
                    // Поэтому не закрываем автоматически все, только переключаем текущий
                    this.setAttribute('aria-expanded', !isExpanded);
                    const answer = this.nextElementSibling;
                    if (!isExpanded) {
                        answer.style.maxHeight = answer.scrollHeight + 'px';
                        this.querySelector('.faq-toggle').textContent = '−';
                    } else {
                        answer.style.maxHeight = '0';
                        this.querySelector('.faq-toggle').textContent = '+';
                    }
                });
            });

            // === Поиск по вопросам ===
            const searchInput = document.getElementById('faqSearch');
            const faqItems = document.querySelectorAll('.faq-item');

            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                faqItems.forEach(item => {
                    const questionText = item.querySelector('.question-text').textContent.toLowerCase();
                    const answerText = item.querySelector('.faq-answer').textContent.toLowerCase();
                    const match = questionText.includes(query) || answerText.includes(query);
                    item.style.display = match ? 'block' : 'none';
                    // Если поиск не пустой и найдено совпадение, автоматически раскрываем вопрос
                    if (query.length > 0 && match) {
                        const button = item.querySelector('.faq-question');
                        const answer = item.querySelector('.faq-answer');
                        button.setAttribute('aria-expanded', 'true');
                        answer.style.maxHeight = answer.scrollHeight + 'px';
                        button.querySelector('.faq-toggle').textContent = '−';
                    } else if (query.length === 0) {
                        // Если поиск очищен, закрываем все (по желанию можно оставить открытыми)
                        // Закрываем все, чтобы аккордеон был в исходном состоянии
                        const button = item.querySelector('.faq-question');
                        const answer = item.querySelector('.faq-answer');
                        button.setAttribute('aria-expanded', 'false');
                        answer.style.maxHeight = '0';
                        button.querySelector('.faq-toggle').textContent = '+';
                    }
                });
            });
        });
    </script>
</body>
</html>