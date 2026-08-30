<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/check_auth.php';

$user_data = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT u.email, up.first_name, up.last_name, up.phone, up.address
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $user_data = $stmt->fetch();
}

// Функции разбора адреса
function extractStreetFromAddress($address) {
    if (empty($address)) return '';
    $patterns = ['/,\s*подъезд\s+\S+.*$/iu', '/,\s*этаж\s+\S+.*$/iu', '/,\s*кв\.\s+\S+.*$/iu', '/,\s*квартира\s+\S+.*$/iu', '/,\s*домофон\s+\S+.*$/iu'];
    $street = trim($address);
    foreach ($patterns as $pattern) {
        $test = preg_replace($pattern, '', $street);
        if ($test !== $street) { $street = trim($test, ', '); break; }
    }
    return $street;
}
function extractEntranceFromAddress($address) {
    if (empty($address)) return '';
    if (preg_match('/подъезд\s+(\S+)/iu', $address, $matches)) return trim($matches[1], ', ');
    return '';
}
function extractFloorFromAddress($address) {
    if (empty($address)) return '';
    if (preg_match('/этаж\s+(\S+)/iu', $address, $matches)) return trim($matches[1], ', ');
    return '';
}
function extractApartmentFromAddress($address) {
    if (empty($address)) return '';
    if (preg_match('/(?:кв\.|квартира)\s+(\S+)/iu', $address, $matches)) return trim($matches[1], ', ');
    return '';
}
function extractIntercomFromAddress($address) {
    if (empty($address)) return '';
    if (preg_match('/домофон\s+(\S+)/iu', $address, $matches)) return trim($matches[1], ', ');
    return '';
}

$categories = [];
try {
    $stmt = $pdo->query("SELECT id, name, work_type, base_hours, description, is_common FROM categories ORDER BY name");
    $raw = $stmt->fetchAll();
    foreach ($raw as $cat) {
        $icon = '📌';
        $name = $cat['name'];
        if (strpos($name, 'Ремонт') !== false) $icon = '🔧';
        elseif (strpos($name, 'Сантехника') !== false) $icon = '🚰';
        elseif (strpos($name, 'Электрика') !== false) $icon = '⚡';
        elseif (strpos($name, 'Благоустройство') !== false) $icon = '🌳';
        elseif (strpos($name, 'Бухгалтерия') !== false) $icon = '📄';
        elseif (strpos($name, 'Юридические') !== false) $icon = '⚖️';
        elseif (strpos($name, 'IT') !== false) $icon = '💻';
        elseif (strpos($name, 'Диспетчерская') !== false) $icon = '📞';
        elseif (strpos($name, 'Кадровые') !== false) $icon = '👤';
        $cat['icon'] = $icon;
        $categories[] = $cat;
    }
} catch (PDOException $e) {
    error_log("Ошибка загрузки категорий: " . $e->getMessage());
}
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
           <!-- Шаг 1: тип -->
            <div class="step active" data-step="0">
                <h2>Что вы хотите сделать?</h2>
                <div class="info-notice">
                    ⚠️ <strong>Обратите внимание:</strong> Услуги, связанные с ремонтом в квартире (сантехника, электрика и др.), могут быть платными.
                    Ознакомьтесь с <a href="price.php" target="_blank">прайс-листом</a>.
                </div>
                <div class="card-grid" id="typeGrid">
                    <div class="card" data-value="request">
                        <span class="icon">🛠️</span>
                        <div class="title">Заявка</div>
                        <div class="desc">Нужен выезд специалиста на место</div>
                    </div>
                    <div class="card" data-value="appeal">
                        <span class="icon">✉️</span>
                        <div class="title">Обращение</div>
                        <div class="desc">Вопрос, жалоба, консультация</div>
                    </div>
                </div>
            </div>
            
            <!-- Шаг 2: категория -->
            <div class="step" data-step="1">
                <h2>Выберите категорию</h2>
                <div class="card-grid card-grid-small" id="categoryGrid"></div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="goStep(-1)">← Назад</button>
                </div>
            </div>
            
            <!-- Шаг 3: срочность (только для квартирных заявок) -->
            <div class="step" data-step="2" id="stepUrgency">
                <h2>Насколько срочно?</h2>
                <div class="card-grid" id="urgencyGrid">
                    <div class="card" data-value="normal">
                        <div class="title">🟢 Планово</div>
                        <div class="desc">3–5 дней, проблема не мешает</div>
                    </div>
                    <div class="card" data-value="high">
                        <div class="title">🟡 Оперативно</div>
                        <div class="desc">1–2 дня, дискомфорт без риска</div>
                    </div>
                    <div class="card" data-value="emergency">
                        <div class="title">🔴 Срочно</div>
                        <div class="desc">Сегодня, риск для имущества/жизни</div>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="goStep(-1)">← Назад</button>
                </div>
            </div>
            
            <!-- Шаг 4: объём (только для квартирных заявок) -->
            <div class="step" data-step="3" id="stepVolume">
                <h2>Объём работ</h2>
                <div class="card-grid" id="volumeGrid">
                    <div class="card" data-value="small">
                        <div class="title">📏 Мелкий</div>
                        <div class="desc">Заменить лампу, отрегулировать дверь</div>
                    </div>
                    <div class="card" data-value="medium">
                        <div class="title">📐 Средний</div>
                        <div class="desc">Заменить кран, прочистить засор</div>
                    </div>
                    <div class="card" data-value="large">
                        <div class="title">📦 Крупный</div>
                        <div class="desc">Заменить стояк, ремонт кровли</div>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="goStep(-1)">← Назад</button>
                </div>
            </div>
            
            <!-- Шаг 5: Адрес и доступ (всегда для заявок) -->
            <div class="step" data-step="4" id="stepAddress">
                <h2>Адрес и доступ</h2>
                <div class="form-group">
                    <label>Улица и дом <span style="color:red">*</span></label>
                    <input type="text" id="street" name="street" value="<?= htmlspecialchars(extractStreetFromAddress($user_data['address'] ?? '')) ?>" placeholder="ул. Исаковского, д.8 к.1" required>
                    <div class="hint">Начните вводить адрес, появится список подсказок</div>
                    <input type="hidden" id="full_address" name="address" value="<?= htmlspecialchars($user_data['address'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Подъезд</label>
                        <input type="text" id="entrance" name="entrance" value="<?= htmlspecialchars(extractEntranceFromAddress($user_data['address'] ?? '')) ?>" placeholder="№ подъезда">
                    </div>
                    <div class="form-group field-floor" id="floorGroup">
                        <label>Этаж</label>
                        <input type="text" id="floor" name="floor" value="<?= htmlspecialchars(extractFloorFromAddress($user_data['address'] ?? '')) ?>" placeholder="№ этажа">
                    </div>
                    <div class="form-group field-apartment" id="apartmentGroup">
                        <label>Квартира <span id="apartmentRequired" style="color:red">*</span></label>
                        <input type="text" id="apartment" name="apartment" value="<?= htmlspecialchars(extractApartmentFromAddress($user_data['address'] ?? '')) ?>" placeholder="№ квартиры" required>
                    </div>
                </div>
                <div class="form-group field-intercom" id="intercomGroup">
                    <label>Код домофона</label>
                    <input type="text" id="intercom" name="intercom" value="<?= htmlspecialchars(extractIntercomFromAddress($user_data['address'] ?? '')) ?>" placeholder="например, 1234 или #5678">
                    <div class="hint">Укажите код домофона, чтобы мастер мог войти в подъезд</div>
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
            
            <!-- Шаг 6: материалы (только для квартирных заявок) -->
            <div class="step" data-step="5" id="stepMaterials">
                <h2>Готовность к работе</h2>
                <div class="card-grid" id="materialsGrid">
                    <div class="card" data-value="0">
                        <div class="title">✅ Всё есть</div>
                        <div class="desc">Купили заранее, лежит дома</div>
                    </div>
                    <div class="card" data-value="2">
                        <div class="title">❓ Не знаю</div>
                        <div class="desc">Мастер посмотрит и скажет</div>
                    </div>
                    <div class="card" data-value="1">
                        <div class="title">🛒 Ничего нет</div>
                        <div class="desc">Нужно закупать перед началом</div>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="goStep(-1)">← Назад</button>
                </div>
            </div>
            
            <!-- Шаг 7: контакты -->
            <div class="step" data-step="6">
                <h2>Ваши контакты и описание</h2>
                <div class="form-group">
                    <label>Ваше имя <span style="color:red">*</span></label>
                    <input type="text" id="userName" value="<?= htmlspecialchars($user_data ? (($user_data['first_name'] ?? '').' '.($user_data['last_name'] ?? '')) : '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Email <span style="color:red">*</span></label>
                    <input type="email" id="userEmail" value="<?= htmlspecialchars($user_data['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="tel" id="userPhone" value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>" placeholder="+7 (999) 999-99-99">
                </div>
                <div class="form-group">
                    <label>Подробное описание <span style="color:red">*</span></label>
                    <textarea id="messageText" rows="4" placeholder="Опишите суть проблемы или вопроса"></textarea>
                </div>
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
const categories = <?= json_encode($categories) ?>;

let currentStep = 0;
let formData = {
    type: null,
    category_id: null,
    urgency: null,
    volume: null,
    address: '',
    floor: '',
    has_elevator: 1,
    materials_needed: null,
    user_name: '',
    user_email: '',
    user_phone: '',
    message: '',
    intercom: ''
};

function isCommonCategory() {
    if (!formData.category_id) return false;
    const cat = categories.find(c => c.id == formData.category_id);
    return cat ? cat.is_common == 1 : false;
}

function getTotalSteps() {
    if (formData.type === 'appeal') return 3;
    if (isCommonCategory()) return 4; // тип, категория, адрес, контакты
    return 7; // тип, категория, срочность, объём, адрес, материалы, контакты
}

function rebuildProgress() {
    const bar = document.getElementById('progressBar');
    const total = getTotalSteps();
    let html = '';
    for (let i = 0; i < total; i++) {
        let stepIndex;
        if (total === 3) {
            if (i === 0) stepIndex = 0;
            else if (i === 1) stepIndex = 1;
            else stepIndex = 6;
        } else if (total === 4) {
            if (i === 0) stepIndex = 0;
            else if (i === 1) stepIndex = 1;
            else if (i === 2) stepIndex = 4;
            else stepIndex = 6;
        } else {
            stepIndex = i; // 0,1,2,3,4,5,6
        }
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
            const steps = document.querySelectorAll('.step');
            if (steps[targetStep] && steps[targetStep].style.display !== 'none') {
                steps[currentStep].classList.remove('active');
                currentStep = targetStep;
                steps[currentStep].classList.add('active');
                updateProgress();
                updateButtons();
                saveState();
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

function loadState() {
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
}

function saveState() {
    try {
        localStorage.setItem('contactFormState', JSON.stringify({
            currentStep: currentStep,
            formData: formData
        }));
    } catch(e) {}
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
    saveState();
}

function updateButtons() {
    const type = formData.type;
    const isCommon = isCommonCategory();
    const stepUrgency = document.getElementById('stepUrgency');
    const stepVolume = document.getElementById('stepVolume');
    const stepAddress = document.getElementById('stepAddress');
    const stepMaterials = document.getElementById('stepMaterials');
    if (type === 'request' && !isCommon) {
        stepUrgency.style.display = 'block';
        stepVolume.style.display = 'block';
        stepAddress.style.display = 'block';
        stepMaterials.style.display = 'block';
    } else if (type === 'request' && isCommon) {
        // Показываем только адрес
        stepUrgency.style.display = 'none';
        stepVolume.style.display = 'none';
        stepAddress.style.display = 'block';
        stepMaterials.style.display = 'none';
        // Если мы на скрытом шаге (2,3,5) – переходим на 4 или 6
        const steps = document.querySelectorAll('.step');
        if (currentStep === 2 || currentStep === 3 || currentStep === 5) {
            steps[currentStep].classList.remove('active');
            currentStep = (currentStep === 5) ? 6 : 4;
            steps[currentStep].classList.add('active');
            updateProgress();
            saveState();
        }
    } else { // appeal
        stepUrgency.style.display = 'none';
        stepVolume.style.display = 'none';
        stepAddress.style.display = 'none';
        stepMaterials.style.display = 'none';
        const steps = document.querySelectorAll('.step');
        if (currentStep >= 2 && currentStep <= 5) {
            steps[currentStep].classList.remove('active');
            currentStep = 6;
            steps[currentStep].classList.add('active');
            updateProgress();
            saveState();
        }
    }
    updateAddressFieldsVisibility();
    updateApartmentRequired();
    rebuildProgress();
}

function updateAddressFieldsVisibility() {
    const isCommon = isCommonCategory();
    // Этаж
    const floorGroup = document.getElementById('floorGroup');
    // Квартира
    const apartmentGroup = document.getElementById('apartmentGroup');
    // Домофон
    const intercomGroup = document.getElementById('intercomGroup');
    if (isCommon) {
        if (floorGroup) floorGroup.style.display = 'none';
        if (apartmentGroup) apartmentGroup.style.display = 'none';
        if (intercomGroup) intercomGroup.style.display = 'none';
    } else {
        if (floorGroup) floorGroup.style.display = 'block';
        if (apartmentGroup) apartmentGroup.style.display = 'block';
        if (intercomGroup) intercomGroup.style.display = 'block';
    }
    // Обновляем full_address (вызовем позже в checkAddressFields)
}

function updateApartmentRequired() {
    const isCommon = isCommonCategory();
    const apartmentInput = document.getElementById('apartment');
    const apartmentLabel = document.getElementById('apartmentRequired');
    if (isCommon) {
        if (apartmentInput) apartmentInput.removeAttribute('required');
        if (apartmentLabel) apartmentLabel.style.display = 'none';
    } else {
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
            saveState();
            if (inputName === 'type') {
                rebuildProgress();
            }
            setTimeout(() => {
                goStep(1);
            }, nextStepDelay);
        });
    });
}

// Переопределяем updateFullAddress для учёта общих категорий
function updateFullAddressCustom() {
    const streetInput = document.getElementById('street');
    const fullAddressInput = document.getElementById('full_address');
    const entranceInput = document.getElementById('entrance');
    const floorInput = document.getElementById('floor');
    const apartmentInput = document.getElementById('apartment');
    const intercomInput = document.getElementById('intercom');
    
    if (!streetInput || !fullAddressInput) return;
    
    let street = streetInput.value.trim();
    let entrance = entranceInput ? entranceInput.value.trim() : '';
    let floor = floorInput ? floorInput.value.trim() : '';
    let apartment = apartmentInput ? apartmentInput.value.trim() : '';
    let intercom = intercomInput ? intercomInput.value.trim() : '';
    
    let fullAddress = street;
    if (entrance) fullAddress += `, подъезд ${entrance}`;
    const isCommon = isCommonCategory();
    if (!isCommon) {
        if (floor) fullAddress += `, этаж ${floor}`;
        if (apartment) fullAddress += `, кв. ${apartment}`;
        if (intercom) fullAddress += `, домофон ${intercom}`;
    }
    fullAddressInput.value = fullAddress;
}

// Заменяем глобальную функцию updateFullAddress
window.updateFullAddress = updateFullAddressCustom;

document.addEventListener('DOMContentLoaded', function() {
    loadState();
    rebuildProgress();
    
    setupCardSelection('typeGrid', 'type', 300);
    const typeCards = document.querySelectorAll('#typeGrid .card');
    typeCards.forEach(card => {
        card.addEventListener('click', function() {
            const val = this.dataset.value;
            loadCategories(val);
            saveState();
        });
        // Маска для телефона +7 (999) 999-99-99
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
    });
    
    setupCardSelection('urgencyGrid', 'urgency', 300);
    setupCardSelection('volumeGrid', 'volume', 300);
    setupCardSelection('materialsGrid', 'materials_needed', 300);
    
    // Шаг 5: адрес
    const streetInput = document.getElementById('street');
    const apartmentInput = document.getElementById('apartment');
    const intercomInput = document.getElementById('intercom');
    const step5Next = document.getElementById('step5Next');
    const fullAddressInput = document.getElementById('full_address');
    
    function checkAddressFields() {
        const street = streetInput.value.trim();
        const apartment = apartmentInput.value.trim();
        const isCommon = isCommonCategory();
        if (street.length > 0 && (isCommon || apartment.length > 0)) {
            step5Next.disabled = false;
        } else {
            step5Next.disabled = true;
        }
        // Обновляем полный адрес
        if (typeof updateFullAddressCustom === 'function') {
            updateFullAddressCustom();
        }
    }
    
    streetInput.addEventListener('input', checkAddressFields);
    apartmentInput.addEventListener('input', checkAddressFields);
    intercomInput.addEventListener('input', function() {
        if (typeof updateFullAddressCustom === 'function') updateFullAddressCustom();
    });
    document.getElementById('entrance').addEventListener('input', function() {
        if (typeof updateFullAddressCustom === 'function') updateFullAddressCustom();
    });
    document.getElementById('floor').addEventListener('input', function() {
        if (typeof updateFullAddressCustom === 'function') updateFullAddressCustom();
    });
    
    updateAddressFieldsVisibility();
    updateApartmentRequired();
    checkAddressFields();
    
    step5Next.addEventListener('click', function() {
        formData.address = fullAddressInput.value;
        formData.has_elevator = document.getElementById('hasElevator').value;
        formData.intercom = intercomInput.value.trim();
        saveState();
        // Если общая категория – сразу на контакты, иначе на следующий шаг
        if (isCommonCategory()) {
            const steps = document.querySelectorAll('.step');
            steps[currentStep].classList.remove('active');
            currentStep = 6;
            steps[currentStep].classList.add('active');
            updateProgress();
            updateButtons();
            saveState();
        } else {
            goStep(1);
        }
    });
    
    document.getElementById('submitBtn').addEventListener('click', function() {
        submitForm();
    });
    
    restoreSelection();
    
    document.querySelectorAll('#contactForm input, #contactForm select, #contactForm textarea').forEach(el => {
        el.addEventListener('change', saveState);
        el.addEventListener('input', saveState);
    });
});

function restoreSelection() {
    if (formData.type) {
        const typeCards = document.querySelectorAll('#typeGrid .card');
        typeCards.forEach(card => {
            if (card.dataset.value === formData.type) {
                card.classList.add('selected');
                loadCategories(formData.type);
            }
        });
        rebuildProgress();
    }
    if (formData.urgency) {
        const urgencyCards = document.querySelectorAll('#urgencyGrid .card');
        urgencyCards.forEach(card => {
            if (card.dataset.value === formData.urgency) card.classList.add('selected');
        });
    }
    if (formData.volume) {
        const volumeCards = document.querySelectorAll('#volumeGrid .card');
        volumeCards.forEach(card => {
            if (card.dataset.value === formData.volume) card.classList.add('selected');
        });
    }
    if (formData.materials_needed !== null) {
        const materialsCards = document.querySelectorAll('#materialsGrid .card');
        materialsCards.forEach(card => {
            if (card.dataset.value == formData.materials_needed) card.classList.add('selected');
        });
    }
    if (formData.address) {
        document.getElementById('full_address').value = formData.address;
    }
    if (formData.intercom) document.getElementById('intercom').value = formData.intercom;
    if (formData.has_elevator) document.getElementById('hasElevator').value = formData.has_elevator;
    if (formData.user_name) document.getElementById('userName').value = formData.user_name;
    if (formData.user_email) document.getElementById('userEmail').value = formData.user_email;
    if (formData.user_phone) document.getElementById('userPhone').value = formData.user_phone;
    if (formData.message) document.getElementById('messageText').value = formData.message;
    
    updateAddressFieldsVisibility();
    updateApartmentRequired();
    
    const steps = document.querySelectorAll('.step');
    if (currentStep >= 0 && steps[currentStep] && steps[currentStep].style.display !== 'none') {
        steps.forEach((s, i) => s.classList.remove('active'));
        steps[currentStep].classList.add('active');
        updateProgress();
        updateButtons();
    }
    rebuildProgress();
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
        html += `<div class="card" data-value="${cat.id}" data-common="${cat.is_common}">
            <span class="icon">${cat.icon}</span>
            <div class="title">${cat.name}</div>
            <div class="desc">${cat.description || ''}</div>
        </div>`;
    });
    grid.innerHTML = html;
    grid.querySelectorAll('.card').forEach(card => {
        if (card.dataset.value == formData.category_id) {
            card.classList.add('selected');
        }
        card.addEventListener('click', function() {
            grid.querySelectorAll('.card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            const catId = this.dataset.value;
            const isCommon = this.dataset.common == '1';
            formData.category_id = catId;
            saveState();
            updateButtons(); // обновляем видимость
            // Обновляем проверку адреса
            const streetInput = document.getElementById('street');
            const apartmentInput = document.getElementById('apartment');
            if (streetInput) {
                const event = new Event('input');
                streetInput.dispatchEvent(event);
                apartmentInput.dispatchEvent(event);
            }
            setTimeout(() => {
                if (formData.type === 'appeal') {
                    const steps = document.querySelectorAll('.step');
                    steps[currentStep].classList.remove('active');
                    currentStep = 6;
                    steps[currentStep].classList.add('active');
                    updateProgress();
                    updateButtons();
                    saveState();
                } else if (isCommon) {
                    // Переход на шаг адреса (4)
                    const steps = document.querySelectorAll('.step');
                    steps[currentStep].classList.remove('active');
                    currentStep = 4;
                    steps[currentStep].classList.add('active');
                    updateProgress();
                    updateButtons();
                    saveState();
                } else {
                    goStep(1);
                }
            }, 300);
        });
    });
}

function submitForm() {
    // Обновляем полный адрес перед отправкой
    if (typeof updateFullAddressCustom === 'function') {
        updateFullAddressCustom();
    }
    const fullAddress = document.getElementById('full_address').value;
    formData.address = fullAddress;
    formData.intercom = document.getElementById('intercom').value.trim();
    formData.user_name = document.getElementById('userName').value.trim();
    formData.user_email = document.getElementById('userEmail').value.trim();
    formData.user_phone = document.getElementById('userPhone').value.trim();
    formData.message = document.getElementById('messageText').value.trim();
    saveState();
    
    let errors = [];
    if (!formData.user_name || formData.user_name.length < 2) errors.push('Введите имя');
    if (!formData.user_email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.user_email)) errors.push('Введите корректный email');
    if (!formData.category_id) errors.push('Выберите категорию');
    if (!formData.message || formData.message.length < 10) errors.push('Опишите проблему (минимум 10 символов)');
    if (formData.type === 'request') {
        if (!formData.address) errors.push('Укажите адрес');
        if (!isCommonCategory()) {
            if (!formData.urgency) errors.push('Выберите срочность');
            if (!formData.volume) errors.push('Выберите объём работ');
            if (formData.materials_needed === null) errors.push('Укажите готовность к работе');
            const apartment = document.getElementById('apartment').value.trim();
            if (!apartment) errors.push('Укажите номер квартиры');
        }
    }
    if (errors.length > 0) {
        showMessage('error', errors.join('<br>'));
        return;
    }
    
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = 'Отправка...';
    
    fetch('includes/process_contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            let msg = `✅ Ваша заявка №${data.request_id} успешно создана!`;
            if (data.type === 'request' && data.estimated_hours && !isCommonCategory()) {
                msg += `<br>⏱️ Примерное время выполнения: ${data.estimated_hours} ч.`;
            }
            if (data.type === 'appeal' || isCommonCategory()) {
                msg += `<br><br>📌 Обработка заявлений в течение 5 рабочих дней.`;
            }
            msg += `<br><br>📌 Чтобы отслеживать статус, войдите в личный кабинет или зарегистрируйтесь.`;
            showMessage('success', msg);
            localStorage.removeItem('contactFormState');
        } else {
            showMessage('error', data.errors ? data.errors.join('<br>') : 'Ошибка при отправке');
        }
    })
    .catch(err => {
        showMessage('error', 'Ошибка соединения: ' + err.message);
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = '✅ Отправить';
    });
}

function showMessage(type, text) {
    const block = document.getElementById('messageBlock');
    block.innerHTML = `<div class="${type === 'error' ? 'error-msg' : 'success-msg'}">${text}</div>`;
    setTimeout(() => { block.innerHTML = ''; }, 10000);
}
</script>
</body>
</html>