<?php
session_start();
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подать заявку или обращение</title>
    <link rel="stylesheet" href="css/header_mobile.css">
    <link rel="stylesheet" href="css/contact.css">
    <link rel="stylesheet" href="css/address-autocomplete.css">
</head>
<body>
<?php include 'templates/header.php'; ?>

<div class="form-container">
    <div class="header_contact">
        <h1>Сервис подачи заявок и обращений</h1>
        <p>Заполните несколько шагов, и мы решим вашу проблему</p>
    </div>
    <div class="form-content">
        <div id="messageBlock"></div>
        <div class="progress-bar" id="progressBar"></div>

        <form id="contactForm" novalidate>
            <!-- Шаг 1: Тип -->
            <div class="step active" data-step="0">
                <h2>Что вы хотите сделать?</h2>
                <div class="info-notice">
                    ⚠️ <strong>Обратите внимание:</strong> Услуги в квартире могут быть платными.
                    <a href="price.php" target="_blank">Прайс-лист</a>
                </div>
                <div class="card-grid" id="typeGrid">
                    <div class="card" data-value="request">
                        <span class="icon">🛠️</span>
                        <div class="title">Заявка</div>
                        <div class="desc">Нужен выезд специалиста</div>
                    </div>
                    <div class="card" data-value="appeal">
                        <span class="icon">✉️</span>
                        <div class="title">Обращение</div>
                        <div class="desc">Вопрос, жалоба, консультация</div>
                    </div>
                </div>
            </div>

            <!-- Шаг 2: Категория -->
            <div class="step" data-step="1">
                <h2>Выберите категорию</h2>
                <div class="card-grid card-grid-small" id="categoryGrid"></div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="goStep(-1)">← Назад</button>
                </div>
            </div>

            <!-- Шаг 3: Срочность (только для квартирных) -->
            <div class="step" data-step="2" id="stepUrgency">
                <h2>Насколько срочно?</h2>
                <div class="card-grid" id="urgencyGrid">
                    <div class="card" data-value="normal">
                        <div class="title">🟢 Планово</div>
                        <div class="desc">3–5 дней</div>
                    </div>
                    <div class="card" data-value="high">
                        <div class="title">🟡 Оперативно</div>
                        <div class="desc">1–2 дня</div>
                    </div>
                    <div class="card" data-value="emergency">
                        <div class="title">🔴 Срочно</div>
                        <div class="desc">Сегодня</div>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="goStep(-1)">← Назад</button>
                </div>
            </div>

            <!-- Шаг 4: Объём (только для квартирных) -->
            <div class="step" data-step="3" id="stepVolume">
                <h2>Объём работ</h2>
                <div class="card-grid" id="volumeGrid">
                    <div class="card" data-value="small">
                        <div class="title">📏 Мелкий</div>
                        <div class="desc">До 1 часа</div>
                    </div>
                    <div class="card" data-value="medium">
                        <div class="title">📐 Средний</div>
                        <div class="desc">1–3 часа</div>
                    </div>
                    <div class="card" data-value="large">
                        <div class="title">📦 Крупный</div>
                        <div class="desc">От 3 часов</div>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="goStep(-1)">← Назад</button>
                </div>
            </div>

            <!-- Шаг 5: Адрес -->
            <div class="step" data-step="4" id="stepAddress">
                <h2>Адрес и доступ</h2>
                <div class="form-group">
                    <label>Улица и дом <span style="color:red">*</span></label>
                    <input type="text" id="street" required placeholder="ул. Исаковского, д.8 к.1">
                    <div class="hint">Начните вводить адрес, появится список подсказок</div>
                    <input type="hidden" id="full_address" name="address">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Подъезд</label>
                        <input type="text" id="entrance" placeholder="№ подъезда">
                    </div>
                    <div class="form-group field-floor">
                        <label>Этаж</label>
                        <input type="text" id="floor" placeholder="№ этажа">
                    </div>
                    <div class="form-group field-apartment">
                        <label>Квартира <span id="apartmentRequired" style="color:red">*</span></label>
                        <input type="text" id="apartment" placeholder="№ квартиры" required>
                    </div>
                </div>
                <div class="form-group field-intercom">
                    <label>Код домофона</label>
                    <input type="text" id="intercom" placeholder="1234 или #5678">
                </div>
                <div class="form-group">
                    <label>Есть ли лифт?</label>
                    <select id="hasElevator">
                        <option value="1">Да</option>
                        <option value="0">Нет</option>
                    </select>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="goStep(-1)">← Назад</button>
                    <button type="button" class="btn btn-primary" id="step5Next" disabled>Далее →</button>
                </div>
            </div>

            <!-- Шаг 6: Материалы (только для квартирных) -->
            <div class="step" data-step="5" id="stepMaterials">
                <h2>Готовность к работе</h2>
                <div class="card-grid" id="materialsGrid">
                    <div class="card" data-value="0">
                        <div class="title">✅ Всё есть</div>
                        <div class="desc">Купили заранее</div>
                    </div>
                    <div class="card" data-value="2">
                        <div class="title">❓ Не знаю</div>
                        <div class="desc">Мастер скажет</div>
                    </div>
                    <div class="card" data-value="1">
                        <div class="title">🛒 Ничего нет</div>
                        <div class="desc">Нужно закупать</div>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="goStep(-1)">← Назад</button>
                </div>
            </div>

            <!-- Шаг 7: Контакты -->
            <div class="step" data-step="6">
                <h2>Ваши контакты</h2>
                <div class="form-row">
                    <div class="form-group">
                        <label>Фамилия <span style="color:red">*</span></label>
                        <input type="text" id="lastName" required>
                    </div>
                    <div class="form-group">
                        <label>Имя <span style="color:red">*</span></label>
                        <input type="text" id="firstName" required>
                    </div>
                    <div class="form-group">
                        <label>Отчество</label>
                        <input type="text" id="middleName">
                    </div>
                </div>
                <div class="form-group">
                    <label>Email <span style="color:red">*</span></label>
                    <input type="email" id="userEmail" required>
                </div>
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="tel" id="userPhone" placeholder="+7 (999) 999-99-99">
                </div>
                <div class="form-group">
                    <label>Подробное описание <span style="color:red">*</span></label>
                    <textarea id="messageText" rows="4" required></textarea>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="goStep(-1)">← Назад</button>
                    <button type="button" class="btn btn-primary" id="step7Next" disabled>Далее →</button>
                </div>
            </div>

            <!-- Шаг 8: Итог -->
            <div class="step" data-step="7">
                <h2>Проверка данных</h2>
                <div id="summaryBlock"></div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="goStep(-1)">← Назад</button>
                    <button type="button" class="btn btn-success" id="submitBtn">✅ Отправить</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
<script src="js/address-autocomplete.js"></script>
<script>
// Получаем категории из PHP
const categories = <?php
    try {
        $stmt = $pdo->query("SELECT id, name, work_type, description, is_common FROM categories ORDER BY name");
        echo json_encode($stmt->fetchAll());
    } catch (PDOException $e) {
        echo '[]';
    }
?>;

let currentStep = 0;
const formData = {
    type: null,
    category_id: null,
    urgency: 'normal',
    volume: 'medium',
    address: '',
    floor: '',
    has_elevator: 1,
    materials_needed: 0,
    user_email: '',
    user_phone: '',
    message: '',
    intercom: '',
    first_name: '',
    last_name: '',
    middle_name: '',
    is_common: false
};

function isCommonCategory() {
    if (!formData.category_id) return false;
    const cat = categories.find(c => c.id == formData.category_id);
    return cat ? cat.is_common == 1 : false;
}

function getTotalSteps() {
    if (formData.type === 'appeal') return 4;
    if (isCommonCategory()) return 5;
    return 8;
}

function rebuildProgress() {
    const bar = document.getElementById('progressBar');
    const total = getTotalSteps();
    let html = '';
    const mapping = {
        4: [0, 1, 6, 7],
        5: [0, 1, 4, 6, 7],
        8: [0, 1, 2, 3, 4, 5, 6, 7]
    };
    const steps = mapping[total] || [0, 1, 2, 3, 4, 5, 6, 7];
    for (let i = 0; i < steps.length; i++) {
        const stepIndex = steps[i];
        const isActive = (currentStep === stepIndex);
        const isDone = (currentStep > stepIndex);
        let cls = 'progress-dot';
        if (isActive) cls += ' active';
        if (isDone) cls += ' done';
        html += `<div class="${cls}" data-step="${stepIndex}" data-display="${i+1}">${i+1}</div>`;
    }
    bar.innerHTML = html;
    bar.querySelectorAll('.progress-dot').forEach(dot => {
        dot.addEventListener('click', function() {
            const targetStep = parseInt(this.dataset.step);
            const stepsAll = document.querySelectorAll('.step');
            if (stepsAll[targetStep] && stepsAll[targetStep].style.display !== 'none') {
                stepsAll[currentStep].classList.remove('active');
                currentStep = targetStep;
                stepsAll[currentStep].classList.add('active');
                updateProgress();
                updateButtons();
                if (currentStep === 7) generateSummary();
            }
        });
    });
}

function updateProgress() {
    const dots = document.querySelectorAll('.progress-dot');
    dots.forEach(dot => {
        const stepIdx = parseInt(dot.dataset.step);
        dot.classList.remove('active', 'done');
        if (stepIdx === currentStep) dot.classList.add('active');
        else if (stepIdx < currentStep) dot.classList.add('done');
    });
}

function goStep(delta) {
    const steps = document.querySelectorAll('.step');
    let newStep = currentStep + delta;
    if (newStep < 0) newStep = 0;
    if (newStep >= steps.length) newStep = steps.length - 1;
    while (steps[newStep].style.display === 'none' && newStep >= 0 && newStep < steps.length) {
        newStep += delta;
    }
    if (newStep < 0) newStep = 0;
    if (newStep >= steps.length) newStep = steps.length - 1;
    if (steps[newStep].style.display === 'none') return;
    steps[currentStep].classList.remove('active');
    currentStep = newStep;
    steps[currentStep].classList.add('active');
    updateProgress();
    updateButtons();
    if (currentStep === 7) generateSummary();
}

function updateButtons() {
    const isCommon = isCommonCategory();
    const stepUrgency = document.getElementById('stepUrgency');
    const stepVolume = document.getElementById('stepVolume');
    const stepMaterials = document.getElementById('stepMaterials');
    const stepAddress = document.getElementById('stepAddress');
    if (formData.type === 'request' && !isCommon) {
        stepUrgency.style.display = 'block';
        stepVolume.style.display = 'block';
        stepMaterials.style.display = 'block';
        stepAddress.style.display = 'block';
    } else if (formData.type === 'request' && isCommon) {
        stepUrgency.style.display = 'none';
        stepVolume.style.display = 'none';
        stepMaterials.style.display = 'none';
        stepAddress.style.display = 'block';
        if (currentStep >= 2 && currentStep <= 3 || currentStep === 5) {
            const steps = document.querySelectorAll('.step');
            steps[currentStep].classList.remove('active');
            currentStep = (currentStep === 5) ? 6 : 4;
            steps[currentStep].classList.add('active');
            updateProgress();
        }
    } else {
        stepUrgency.style.display = 'none';
        stepVolume.style.display = 'none';
        stepMaterials.style.display = 'none';
        stepAddress.style.display = 'none';
        if (currentStep >= 2 && currentStep <= 5) {
            const steps = document.querySelectorAll('.step');
            steps[currentStep].classList.remove('active');
            currentStep = 6;
            steps[currentStep].classList.add('active');
            updateProgress();
        }
    }
    updateAddressFields();
    rebuildProgress();
    // Активируем кнопку "Далее" на контактах
    const step7Next = document.getElementById('step7Next');
    if (step7Next) {
        const ln = document.getElementById('lastName').value.trim();
        const fn = document.getElementById('firstName').value.trim();
        const em = document.getElementById('userEmail').value.trim();
        const msg = document.getElementById('messageText').value.trim();
        step7Next.disabled = !(ln.length >= 2 && fn.length >= 2 && em && msg.length >= 10);
    }
}

function updateAddressFields() {
    const isCommon = isCommonCategory();
    const floorGroup = document.querySelector('.field-floor');
    const apartmentGroup = document.querySelector('.field-apartment');
    const intercomGroup = document.querySelector('.field-intercom');
    const apartmentInput = document.getElementById('apartment');
    const apartmentLabel = document.getElementById('apartmentRequired');
    if (isCommon) {
        if (floorGroup) floorGroup.style.display = 'none';
        if (apartmentGroup) apartmentGroup.style.display = 'none';
        if (intercomGroup) intercomGroup.style.display = 'none';
        if (apartmentInput) apartmentInput.removeAttribute('required');
        if (apartmentLabel) apartmentLabel.style.display = 'none';
    } else {
        if (floorGroup) floorGroup.style.display = 'block';
        if (apartmentGroup) apartmentGroup.style.display = 'block';
        if (intercomGroup) intercomGroup.style.display = 'block';
        if (apartmentInput) apartmentInput.setAttribute('required', 'required');
        if (apartmentLabel) apartmentLabel.style.display = 'inline';
    }
}

function setupCardSelection(containerId, inputName, nextStepDelay = 300) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.querySelectorAll('.card').forEach(card => {
        card.addEventListener('click', function() {
            container.querySelectorAll('.card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            const val = this.dataset.value;
            formData[inputName] = val;
            if (inputName === 'type') {
                const cat = this.dataset.value;
                loadCategories(cat);
            }
            setTimeout(() => {
                if (inputName === 'type' && formData.type === 'appeal') {
                    const steps = document.querySelectorAll('.step');
                    steps[currentStep].classList.remove('active');
                    currentStep = 6;
                    steps[currentStep].classList.add('active');
                    updateProgress();
                    updateButtons();
                } else if (inputName === 'category_id') {
                    const isCommon = isCommonCategory();
                    if (isCommon) {
                        const steps = document.querySelectorAll('.step');
                        steps[currentStep].classList.remove('active');
                        currentStep = 4;
                        steps[currentStep].classList.add('active');
                        updateProgress();
                        updateButtons();
                    } else {
                        goStep(1);
                    }
                } else {
                    goStep(1);
                }
            }, nextStepDelay);
        });
    });
}

function loadCategories(type) {
    const grid = document.getElementById('categoryGrid');
    const filtered = categories.filter(c => c.work_type === (type === 'request' ? 'field' : 'office'));
    if (filtered.length === 0) {
        grid.innerHTML = '<p style="text-align:center;color:#999;">Нет доступных категорий</p>';
        return;
    }
    let html = '';
    filtered.forEach(cat => {
        const icon = cat.name.includes('Ремонт') ? '🔧' :
                     cat.name.includes('Сантехника') ? '🚰' :
                     cat.name.includes('Электрика') ? '⚡' :
                     cat.name.includes('Благоустройство') ? '🌳' :
                     cat.name.includes('Бухгалтерия') ? '📄' :
                     cat.name.includes('Юридические') ? '⚖️' :
                     cat.name.includes('IT') ? '💻' : '📌';
        html += `<div class="card" data-value="${cat.id}" data-common="${cat.is_common}">
            <span class="icon">${icon}</span>
            <div class="title">${cat.name}</div>
            <div class="desc">${cat.description || ''}</div>
        </div>`;
    });
    grid.innerHTML = html;
    grid.querySelectorAll('.card').forEach(card => {
        card.addEventListener('click', function() {
            grid.querySelectorAll('.card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            formData.category_id = parseInt(this.dataset.value);
            formData.is_common = this.dataset.common == '1';
            updateButtons();
            setTimeout(() => {
                if (formData.is_common) {
                    const steps = document.querySelectorAll('.step');
                    steps[currentStep].classList.remove('active');
                    currentStep = 4;
                    steps[currentStep].classList.add('active');
                    updateProgress();
                    updateButtons();
                } else {
                    goStep(1);
                }
            }, 300);
        });
    });
}

function generateSummary() {
    // Принудительно сохраняем данные из полей в formData перед генерацией
    formData.last_name = document.getElementById('lastName').value.trim();
    formData.first_name = document.getElementById('firstName').value.trim();
    formData.middle_name = document.getElementById('middleName').value.trim();
    formData.user_email = document.getElementById('userEmail').value.trim();
    formData.user_phone = document.getElementById('userPhone').value.trim();
    formData.message = document.getElementById('messageText').value.trim();
    formData.address = document.getElementById('full_address').value;
    formData.intercom = document.getElementById('intercom').value.trim();
    formData.has_elevator = document.getElementById('hasElevator').value;
    formData.floor = document.getElementById('floor').value.trim();

    const block = document.getElementById('summaryBlock');
    if (!block) return;
    const cat = categories.find(c => c.id == formData.category_id);
    const catName = cat ? cat.name : 'Не выбрана';
    const urgencyMap = { normal: 'Планово (3–5 дней)', high: 'Оперативно (1–2 дня)', emergency: 'Срочно (сегодня)' };
    const volumeMap = { small: 'Мелкий', medium: 'Средний', large: 'Крупный' };
    const materialsMap = { 0: '✅ Всё есть', 1: '🛒 Ничего нет', 2: '❓ Не знаю' };
    const fullName = formData.last_name + ' ' + formData.first_name + (formData.middle_name ? ' ' + formData.middle_name : '');
    
    let html = `<div class="summary-item"><strong>Тип:</strong> ${formData.type === 'request' ? 'Заявка' : 'Обращение'}</div>`;
    html += `<div class="summary-item"><strong>Категория:</strong> ${catName}</div>`;
    if (formData.type === 'request' && !isCommonCategory()) {
        html += `<div class="summary-item"><strong>Срочность:</strong> ${urgencyMap[formData.urgency] || formData.urgency}</div>`;
        html += `<div class="summary-item"><strong>Объём:</strong> ${volumeMap[formData.volume] || formData.volume}</div>`;
        html += `<div class="summary-item"><strong>Материалы:</strong> ${materialsMap[formData.materials_needed] || 'Не выбрано'}</div>`;
    }
    if (formData.type === 'request') {
        html += `<div class="summary-item"><strong>Адрес:</strong> ${formData.address}</div>`;
        html += `<div class="summary-item"><strong>Лифт:</strong> ${formData.has_elevator == 1 ? 'Да' : 'Нет'}</div>`;
    }
    html += `<div class="summary-item"><strong>ФИО:</strong> ${fullName}</div>`;
    html += `<div class="summary-item"><strong>Email:</strong> ${formData.user_email}</div>`;
    if (formData.user_phone) html += `<div class="summary-item"><strong>Телефон:</strong> ${formData.user_phone}</div>`;
    html += `<div class="summary-item"><strong>Описание:</strong><br>${formData.message.replace(/\n/g, '<br>')}</div>`;
    block.innerHTML = html;
}

// Инициализация
document.addEventListener('DOMContentLoaded', function() {
    // Загрузка категорий при выборе типа
    // Выбор типа (Заявка / Обращение)
setupCardSelection('typeGrid', 'type', 300);
const typeCards = document.querySelectorAll('#typeGrid .card');
typeCards.forEach(card => {
    card.addEventListener('click', function() {
        const val = this.dataset.value;
        formData.type = val;
        // Загружаем категории в зависимости от типа
        loadCategories(val);
        // Для обращений тоже переходим на шаг выбора категории (индекс 1)
        setTimeout(() => {
            const steps = document.querySelectorAll('.step');
            steps[currentStep].classList.remove('active');
            currentStep = 1; // всегда на категорию
            steps[currentStep].classList.add('active');
            updateProgress();
            updateButtons();
        }, 300);
    });
});
    setupCardSelection('urgencyGrid', 'urgency', 300);
    setupCardSelection('volumeGrid', 'volume', 300);
    setupCardSelection('materialsGrid', 'materials_needed', 300);

    // Адрес
    const streetInput = document.getElementById('street');
    const apartmentInput = document.getElementById('apartment');
    const step5Next = document.getElementById('step5Next');
    const fullAddressInput = document.getElementById('full_address');

    function checkAddress() {
        const street = streetInput.value.trim();
        const apartment = apartmentInput.value.trim();
        const isCommon = isCommonCategory();
        if (street.length > 0 && (isCommon || apartment.length > 0)) {
            step5Next.disabled = false;
        } else {
            step5Next.disabled = true;
        }
        if (typeof updateFullAddress === 'function') updateFullAddress();
    }

    streetInput.addEventListener('input', checkAddress);
    apartmentInput.addEventListener('input', checkAddress);
    document.getElementById('entrance')?.addEventListener('input', checkAddress);
    document.getElementById('floor')?.addEventListener('input', checkAddress);
    document.getElementById('intercom')?.addEventListener('input', checkAddress);

    step5Next.addEventListener('click', function() {
        formData.address = fullAddressInput.value;
        formData.has_elevator = document.getElementById('hasElevator').value;
        formData.intercom = document.getElementById('intercom').value.trim();
        if (isCommonCategory()) {
            const steps = document.querySelectorAll('.step');
            steps[currentStep].classList.remove('active');
            currentStep = 6;
            steps[currentStep].classList.add('active');
            updateProgress();
            updateButtons();
        } else {
            goStep(1);
        }
    });

    // Контакты
    const lastNameInput = document.getElementById('lastName');
    const firstNameInput = document.getElementById('firstName');
    const userEmailInput = document.getElementById('userEmail');
    const messageTextarea = document.getElementById('messageText');
    const step7Next = document.getElementById('step7Next');

    function checkContacts() {
        const ln = lastNameInput.value.trim();
        const fn = firstNameInput.value.trim();
        const em = userEmailInput.value.trim();
        const msg = messageTextarea.value.trim();
        step7Next.disabled = !(ln.length >= 2 && fn.length >= 2 && em && msg.length >= 10);
        // Обновляем formData при вводе
        formData.last_name = ln;
        formData.first_name = fn;
        formData.middle_name = document.getElementById('middleName').value.trim();
        formData.user_email = em;
        formData.user_phone = document.getElementById('userPhone').value.trim();
        formData.message = msg;
    }

    lastNameInput.addEventListener('input', checkContacts);
    firstNameInput.addEventListener('input', checkContacts);
    userEmailInput.addEventListener('input', checkContacts);
    messageTextarea.addEventListener('input', checkContacts);
    document.getElementById('middleName')?.addEventListener('input', checkContacts);
    document.getElementById('userPhone')?.addEventListener('input', checkContacts);

    step7Next.addEventListener('click', function() {
        checkContacts();
        const steps = document.querySelectorAll('.step');
        steps[currentStep].classList.remove('active');
        currentStep = 7;
        steps[currentStep].classList.add('active');
        updateProgress();
        updateButtons();
        generateSummary();
    });

    // Отправка
    document.getElementById('submitBtn').addEventListener('click', function() {
        submitForm();
    });

    // Маска телефона
    const phoneInput = document.getElementById('userPhone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                if (value[0] === '7' || value[0] === '8') value = value.substring(1);
                if (value.length > 10) value = value.substring(0, 10);
                let formatted = '+7 (';
                if (value.length > 0) formatted += value.substring(0, 3);
                if (value.length > 3) formatted += ') ' + value.substring(3, 6);
                if (value.length > 6) formatted += '-' + value.substring(6, 8);
                if (value.length > 8) formatted += '-' + value.substring(8, 10);
                e.target.value = formatted;
            }
        });
    }

    // Восстановление из localStorage
    try {
        const saved = localStorage.getItem('contactFormState');
        if (saved) {
            const state = JSON.parse(saved);
            if (state.currentStep !== undefined) currentStep = state.currentStep;
            if (state.formData) {
                Object.keys(state.formData).forEach(key => {
                    if (formData.hasOwnProperty(key)) formData[key] = state.formData[key];
                });
            }
        }
    } catch(e) {}

    // Переход на сохранённый шаг
    const steps = document.querySelectorAll('.step');
    if (currentStep >= 0 && steps[currentStep] && steps[currentStep].style.display !== 'none') {
        steps.forEach((s, i) => s.classList.remove('active'));
        steps[currentStep].classList.add('active');
        updateProgress();
        updateButtons();
        if (currentStep === 7) generateSummary();
    }
    rebuildProgress();
    updateButtons();
});
function submitForm() {
    // Читаем все поля из DOM напрямую
    const lastName = document.getElementById('lastName').value.trim();
    const firstName = document.getElementById('firstName').value.trim();
    const middleName = document.getElementById('middleName').value.trim();
    const userEmail = document.getElementById('userEmail').value.trim();
    const userPhone = document.getElementById('userPhone').value.trim();
    const message = document.getElementById('messageText').value.trim();
    const address = document.getElementById('full_address').value;
    const intercom = document.getElementById('intercom').value.trim();
    const hasElevator = document.getElementById('hasElevator').value;
    const floor = document.getElementById('floor').value.trim();
    const apartment = document.getElementById('apartment').value.trim();

    // Формируем user_name
    const userName = lastName + ' ' + firstName + (middleName ? ' ' + middleName : '');

    // Записываем в formData
    formData.user_name = userName;
    formData.last_name = lastName;
    formData.first_name = firstName;
    formData.middle_name = middleName;
    formData.user_email = userEmail;
    formData.user_phone = userPhone;
    formData.message = message;
    formData.address = address;
    formData.intercom = intercom;
    formData.has_elevator = hasElevator;
    formData.floor = floor;
    formData.apartment = apartment;

    // Для общих категорий
    const isCommon = isCommonCategory();
    if (isCommon) {
        formData.urgency = 'normal';
        formData.volume = 'medium';
        formData.materials_needed = 0;
    }

    // Валидация
    let errors = [];
    if (!firstName || firstName.length < 2) errors.push('Введите имя (минимум 2 символа)');
    if (!lastName || lastName.length < 2) errors.push('Введите фамилию (минимум 2 символа)');
    if (!userEmail || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(userEmail)) errors.push('Введите корректный email');
    if (!formData.category_id) errors.push('Выберите категорию');
    if (!message || message.length < 10) errors.push('Опишите проблему (минимум 10 символов)');
    if (formData.type === 'request') {
        if (!address) errors.push('Укажите адрес');
        if (!isCommon) {
            if (!formData.urgency) errors.push('Выберите срочность');
            if (!formData.volume) errors.push('Выберите объём работ');
            if (formData.materials_needed === null) errors.push('Укажите готовность к работе');
            if (!apartment) errors.push('Укажите номер квартиры');
        }
    }

    if (errors.length > 0) {
        const block = document.getElementById('messageBlock');
        block.innerHTML = `<div class="error-msg">${errors.join('<br>')}</div>`;
        setTimeout(() => block.innerHTML = '', 8000);
        return;
    }

    // Отправка
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = 'Отправка...';

    console.log('Отправляемые данные:', formData);

    fetch('includes/process_contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'success.php?id=' + data.request_id;
        } else {
            const block = document.getElementById('messageBlock');
            block.innerHTML = `<div class="error-msg">${data.errors ? data.errors.join('<br>') : 'Ошибка отправки'}</div>`;
            setTimeout(() => block.innerHTML = '', 8000);
            btn.disabled = false;
            btn.textContent = '✅ Отправить';
        }
    })
    .catch(err => {
        const block = document.getElementById('messageBlock');
        block.innerHTML = `<div class="error-msg">Ошибка соединения: ${err.message}</div>`;
        setTimeout(() => block.innerHTML = '', 8000);
        btn.disabled = false;
        btn.textContent = '✅ Отправить';
    });
}
</script>
</body>
</html>