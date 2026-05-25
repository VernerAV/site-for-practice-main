<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/check_auth.php';
checkAuth();

$user_id = $_SESSION['user_id'];

// Получаем данные пользователя
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);

    // Основная информация пользователя 
    $user_sql = "SELECT u.email, u.role, up.first_name, up.last_name, up.middle_name, 
                        up.birth_date, up.address, up.phone 
                 FROM users u 
                 LEFT JOIN user_profiles up ON u.id = up.user_id 
                 WHERE u.id = :user_id";
    
    $user_stmt = $pdo->prepare($user_sql);
    $user_stmt->execute([':user_id' => $user_id]);
    $user_data = $user_stmt->fetch();

    // Заявки пользователя из таблицы messages
    $requests_sql = "SELECT * FROM message 
                     WHERE user_email = :user_email 
                     ORDER BY created_at DESC";
    $requests_stmt = $pdo->prepare($requests_sql);
    $requests_stmt->execute([':user_email' => $user_data['email']]);
    $requests = $requests_stmt->fetchAll();

} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

// Проверяем наличие непрочитанных заявок для подсветки вкладки
$has_unread_requests = false;
if (!empty($requests)) {
    foreach ($requests as $request) {
        if (!$request['is_read']) {
            $has_unread_requests = true;
            break;
        }
    }
}

// Функции для разбора адреса на компоненты
function extractStreetFromAddress($address) {
    if (empty($address)) return '';
    
    // Удаляем детали адреса (подъезд, этаж, кв.) - более точная регулярка
    $patterns = [
        '/,\s*подъезд\s+\S+.*$/iu',    // удаляет "подъезд X" и всё после него
        '/,\s*этаж\s+\S+.*$/iu',        // удаляет "этаж X" и всё после него  
        '/,\s*кв\.\s+\S+.*$/iu',        // удаляет "кв. X" и всё после него
        '/,\s*квартира\s+\S+.*$/iu',    // удаляет "квартира X" и всё после него
    ];
    
    $street = trim($address);
    
    foreach ($patterns as $pattern) {
        // Пробуем каждую регулярку
        $test = preg_replace($pattern, '', $street);
        if ($test !== $street) {
            // Если что-то заменилось, берем этот результат
            $street = trim($test, ', ');
            break;
        }
    }
    
    return $street;
}

function extractEntranceFromAddress($address) {
    if (empty($address)) return '';
    
    // Ищем "подъезд" в строке (только первое вхождение)
    if (preg_match('/подъезд\s+(\S+)/iu', $address, $matches)) {
        // Убираем запятые если есть
        return trim($matches[1], ', ');
    }
    return '';
}

function extractFloorFromAddress($address) {
    if (empty($address)) return '';
    
    // Ищем "этаж" в строке (только первое вхождение)
    if (preg_match('/этаж\s+(\S+)/iu', $address, $matches)) {
        // Убираем запятые если есть
        return trim($matches[1], ', ');
    }
    return '';
}

function extractApartmentFromAddress($address) {
    if (empty($address)) return '';
    
    // Ищем "кв." или "квартира" в строке (только первое вхождение)
    if (preg_match('/(?:кв\.|квартира)\s+(\S+)/iu', $address, $matches)) {
        // Убираем запятые если есть
        return trim($matches[1], ', ');
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/user.css">
</head>
<body>
    <div class="container">
        <!-- Сообщения об успехе/ошибках -->
        <?php if (isset($_SESSION['request_success'])): ?>
            <div class="alert alert-success">
                <?php 
                echo htmlspecialchars($_SESSION['request_success']);
                unset($_SESSION['request_success']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['request_errors'])): ?>
            <div class="alert alert-error">
                <h4>Ошибки при создании заявки:</h4>
                <ul>
                    <?php foreach ($_SESSION['request_errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                    <?php unset($_SESSION['request_errors']); ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <header>
            <h1>Личный кабинет</h1>
            <nav>
                <a href="index.php">Главная</a>
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin.php">Панель администратора</a>
                <?php endif; ?>
                <a href="includes/logout.php">Выйти</a>
            </nav>
        </header>

        <div class="user-content">
            <!-- Вкладки -->
            <div class="tabs">
                <button class="tab-button active" onclick="showTab('profile')">Профиль</button>
                <button class="tab-button <?php echo $has_unread_requests ? 'unread' : ''; ?>" 
                        onclick="showTab('requests')">Мои заявки</button>
                <button class="tab-button" onclick="showTab('new-request')">Новая заявка</button>
            </div>

            <!-- Вкладка профиля -->
            <div id="profile" class="tab-content active">
                <h2>Личная информация</h2>
                <form action="includes/update_profile.php" method="POST">
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" value="<?php echo $user_data['email']; ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Фамилия:</label>
                        <input type="text" name="last_name" value="<?php echo $user_data['last_name'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Имя:</label>
                        <input type="text" name="first_name" value="<?php echo $user_data['first_name'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Отчество:</label>
                        <input type="text" name="middle_name" value="<?php echo $user_data['middle_name'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Дата рождения:</label>
                        <input type="date" name="birth_date" value="<?php echo $user_data['birth_date'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Телефон:</label>
                        <input type="tel" name="phone" value="<?php echo $user_data['phone'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Улица и дом *:</label>
                        <input type="text" id="street" name="street" 
                            value="<?php echo htmlspecialchars(extractStreetFromAddress($user_data['address'] ?? '')); ?>" 
                            placeholder="Начните вводить улицу..." required>
                        <!-- Скрытое поле для полного адреса -->
                        <input type="hidden" id="full_address" name="address" value="<?php echo htmlspecialchars($user_data['address'] ?? ''); ?>">
                    </div>

                    <!-- Детали адреса -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Подъезд:</label>
                            <input type="text" id="entrance" name="entrance" 
                                value="<?php echo htmlspecialchars(extractEntranceFromAddress($user_data['address'] ?? '')); ?>" 
                                placeholder="№ подъезда">
                        </div>
                        
                        <div class="form-group">
                            <label>Этаж:</label>
                            <input type="text" id="floor" name="floor" 
                                value="<?php echo htmlspecialchars(extractFloorFromAddress($user_data['address'] ?? '')); ?>" 
                                placeholder="№ этажа">
                        </div>
                        
                        <div class="form-group">
                            <label>Квартира *:</label>
                            <input type="text" id="apartment" name="apartment" 
                                value="<?php echo htmlspecialchars(extractApartmentFromAddress($user_data['address'] ?? '')); ?>" 
                                placeholder="№ квартиры" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary">Сохранить изменения</button>
                </form>
            </div>

            <!-- Вкладка заявок -->
            <div id="requests" class="tab-content">
                <h2>Мои заявки</h2>
                
                <?php if (empty($requests)): ?>
                    <div class="no-requests">
                        <div class="empty-state">
                            <div class="empty-icon">📋</div>
                            <h3>У вас пока нет заявок</h3>
                            <p>Оставьте свою первую заявку на услугу</p>
                            <button onclick="showTab('new-request')" class="btn-primary">Создать заявку</button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="requests-stats">
                        <div class="stat-item">
                            <span class="stat-count"><?php echo count($requests); ?></span>
                            <span class="stat-label">Всего заявок</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-count"><?php echo count(array_filter($requests, function($r) { return !$r['is_read']; })); ?></span>
                            <span class="stat-label">Новых</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-count"><?php echo count(array_filter($requests, function($r) { return !empty($r['admin_response']); })); ?></span>
                            <span class="stat-label">С ответом</span>
                        </div>
                    </div>
                    
                    <div class="requests-list">
                        <?php foreach ($requests as $request): ?>
                            <div class="request-card <?php echo $request['is_read'] ? 'read' : 'unread'; ?>">
                                <div class="request-card-header">
                                    <div class="request-title">
                                        <h3><?php echo htmlspecialchars($request['subject']); ?></h3>
                                        <div class="request-meta-badges">
                                            <span class="request-id">#<?php echo htmlspecialchars($request['id']); ?></span>
                                            <?php if (!$request['is_read']): ?>
                                                <span class="badge-new">НОВАЯ</span>
                                            <?php endif; ?>
                                            <?php if (!empty($request['admin_response'])): ?>
                                                <span class="badge-answered">✅ ОТВЕЧЕНО</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="request-date">
                                        <?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <div class="request-content">
                                    <div class="message-preview">
                                        <?php 
                                        $message = $request['message'];
                                        // Обрезаем длинное сообщение для предпросмотра
                                        if (strlen($message) > 300) {
                                            $message = substr($message, 0, 300) . '...';
                                        }
                                        echo nl2br(htmlspecialchars($message));
                                        ?>
                                    </div>
                                    
                                    <?php if (!empty($request['admin_response'])): ?>
                                        <div class="admin-response">
                                            <div class="response-header">
                                                <h4>Ответ администратора</h4>
                                                <?php if ($request['responded_at']): ?>
                                                    <span class="response-date">
                                                        <?php echo date('d.m.Y H:i', strtotime($request['responded_at'])); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="response-content">
                                                <?php echo nl2br(htmlspecialchars($request['admin_response'])); ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="request-card-footer">
                                    <div class="request-actions">
                                        <?php if (!$request['is_read']): ?>
                                            <form action="includes/mark_as_read.php" method="POST" class="inline-form">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" class="btn-secondary btn-small">
                                                    Отметить как прочитанное
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <button type="button" class="btn-view-details" 
                                                onclick="showRequestDetails(<?php echo $request['id']; ?>)">
                                            Подробнее
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Модальное окно для просмотра полной заявки -->
            <div id="requestModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modalTitle"></h3>
                        <button class="modal-close" onclick="closeModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div id="modalContent"></div>
                    </div>
                </div>
            </div>

            <!-- Вкладка новой заявки -->
            <div id="new-request" class="tab-content">
                <h2>Оставить заявку</h2>
                <form action="includes/create_message.php" method="POST" id="requestForm">
                    <div class="form-group">
                        <label>Тип услуги:</label>
                        <select name="service_type" required>
                            <option value="">Выберите услугу</option>
                            <option value="Ремонт">Ремонт</option>
                            <option value="Уборка">Уборка</option>
                            <option value="Вывоз мусора">Вывоз мусора</option>
                            <option value="Техническое обслуживание">Техническое обслуживание</option>
                            <option value="Консультация">Консультация</option>
                            <option value="Другое">Другое</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Описание проблемы/запроса:</label>
                        <textarea name="description" rows="5" required 
                                placeholder="Подробно опишите вашу проблему или запрос"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Адрес выполнения работ (если отличается от указанного в профиле):</label>
                        <textarea name="address" rows="3" 
                                placeholder="<?php echo htmlspecialchars($user_data['address'] ?? ''); ?>"></textarea>
                        <small class="form-text">Если оставить пустым, будет использован адрес из вашего профиля</small>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Предпочтительная дата:</label>
                            <input type="date" name="preferred_date" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Предпочтительное время:</label>
                            <input type="time" name="preferred_time">
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Отправить заявку</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Скрыть все вкладки
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });

            // Показать выбранную вкладку
            document.getElementById(tabName).classList.add('active');
            
            // Найти кнопку по id вкладки и активировать ее
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(btn => {
                if (btn.textContent.includes(tabName === 'profile' ? 'Профиль' : 
                                            tabName === 'requests' ? 'Мои заявки' : 'Новая заявка')) {
                    btn.classList.add('active');
                }
            });
            
            // Если открыли вкладку с заявками, убираем подсветку
            if (tabName === 'requests') {
                const requestsBtn = document.querySelector('.tab-button.unread');
                if (requestsBtn) {
                    requestsBtn.classList.remove('unread');
                }
            }
        }

        // Функция для показа деталей заявки
        function showRequestDetails(requestId) {
            fetch('includes/get_request_details.php?id=' + requestId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('modalTitle').textContent = data.subject;
                        document.getElementById('modalContent').innerHTML = `
                            <div class="request-full-details">
                                <div class="detail-section">
                                    <h4>Сообщение:</h4>
                                    <div class="detail-content">${data.message}</div>
                                </div>
                                
                                ${data.admin_response ? `
                                <div class="detail-section">
                                    <h4>Ответ администратора:</h4>
                                    <div class="detail-content">${data.admin_response}</div>
                                    <div class="response-meta">
                                        Ответ дан: ${data.responded_at}
                                    </div>
                                </div>
                                ` : ''}
                                
                                <div class="detail-section">
                                    <h4>Информация о заявке:</h4>
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <strong>Номер:</strong> ${data.id}
                                        </div>
                                        <div class="detail-item">
                                            <strong>Создана:</strong> ${data.created_at}
                                        </div>
                                        <div class="detail-item">
                                            <strong>Статус:</strong> ${data.is_read ? 'Прочитано' : 'Новая'}
                                        </div>
                                        ${data.ip_address ? `
                                        <div class="detail-item">
                                            <strong>IP адрес:</strong> ${data.ip_address}
                                        </div>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('requestModal').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    alert('Не удалось загрузить детали заявки');
                });
        }

        // Функции для модального окна
        function closeModal() {
            document.getElementById('requestModal').style.display = 'none';
        }

        // Закрытие модального окна при клике вне его
        window.onclick = function(event) {
            const modal = document.getElementById('requestModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>

    <script src="js/address-autocomplete.js"></script>
</body>
</html>