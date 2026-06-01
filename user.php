<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/check_auth.php';
checkAuth();

$user_id = $_SESSION['user_id'];

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Данные пользователя
    $user_sql = "SELECT u.email, u.role, up.first_name, up.last_name, up.middle_name, 
                        up.birth_date, up.address, up.phone 
                 FROM users u 
                 LEFT JOIN user_profiles up ON u.id = up.user_id 
                 WHERE u.id = :user_id";
    $user_stmt = $pdo->prepare($user_sql);
    $user_stmt->execute([':user_id' => $user_id]);
    $user_data = $user_stmt->fetch();

    // Определяем активную вкладку
    $active_tab = $_GET['tab'] ?? 'profile';

    // Фильтры для заявок
    $status_filter = $_GET['status'] ?? 'all';
    $search = trim($_GET['search'] ?? '');
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    // Быстрые фильтры
    $preset = $_GET['preset'] ?? '';
    if ($preset === 'today') {
        $date_from = date('Y-m-d');
        $date_to = date('Y-m-d');
    } elseif ($preset === 'tomorrow') {
        $date_from = date('Y-m-d', strtotime('+1 day'));
        $date_to = date('Y-m-d', strtotime('+1 day'));
    } elseif ($preset === 'week') {
        $date_from = date('Y-m-d');
        $date_to = date('Y-m-d', strtotime('+6 days'));
    } elseif ($preset === 'month') {
        $date_from = date('Y-m-01');
        $date_to = date('Y-m-t');
    }

    $requests = [];
    $stats = [];

    if ($active_tab === 'requests') {
        $sql = "SELECT * FROM message WHERE user_email = :user_email";
        $params = [':user_email' => $user_data['email']];

        if ($status_filter === 'new') {
            $sql .= " AND status = 'новая'";
        } elseif ($status_filter === 'work') {
            $sql .= " AND status = 'в работе'";
        } elseif ($status_filter === 'completed') {
            $sql .= " AND status = 'выполнена'";
        }

        if (!empty($search)) {
            $sql .= " AND (subject LIKE :search OR message LIKE :search)";
            $params[':search'] = "%$search%";
        }
        if (!empty($date_from)) {
            $sql .= " AND DATE(created_at) >= :date_from";
            $params[':date_from'] = $date_from;
        }
        if (!empty($date_to)) {
            $sql .= " AND DATE(created_at) <= :date_to";
            $params[':date_to'] = $date_to;
        }

        $sql .= " ORDER BY created_at DESC";
        $requests_stmt = $pdo->prepare($sql);
        $requests_stmt->execute($params);
        $requests = $requests_stmt->fetchAll();

        // Статистика для вкладок
        $statsSql = "SELECT status, COUNT(*) as cnt FROM message WHERE user_email = ? GROUP BY status";
        $statsStmt = $pdo->prepare($statsSql);
        $statsStmt->execute([$user_data['email']]);
        foreach ($statsStmt->fetchAll() as $row) {
            $stats[$row['status']] = $row['cnt'];
        }
    }

    $has_unread_requests = !empty(array_filter($requests, fn($r) => !$r['is_read']));

} catch (PDOException $e) {
    die("Ошибка базы данных: " . $e->getMessage());
}

// Функции разбора адреса (без изменений)
function extractStreetFromAddress($address) {
    if (empty($address)) return '';
    $patterns = ['/,\s*подъезд\s+\S+.*$/iu', '/,\s*этаж\s+\S+.*$/iu', '/,\s*кв\.\s+\S+.*$/iu', '/,\s*квартира\s+\S+.*$/iu'];
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
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/user.css">
    <style>
        /* Дополнительные стили для фильтров и вкладок */
        .filter-bar {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .filter-group {
            display: inline-flex;
            flex-direction: column;
            gap: 5px;
        }
        .filter-group label {
            font-size: 12px;
            font-weight: 600;
            color: #6c757d;
        }
        .filter-group input, .filter-group select {
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .tabs-status {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .tab-status {
            background: none;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 16px;
            border-bottom: 2px solid transparent;
            text-decoration: none;
            color: #333;
        }
        .tab-status.active {
            border-bottom-color: #3498db;
            color: #3498db;
            font-weight: bold;
        }
        .badge-count {
            background: #e74c3c;
            color: white;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 12px;
            margin-left: 5px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-new { background: #ff4757; color: white; }
        .status-progress { background: #ffa502; color: white; }
        .status-completed { background: #2ed573; color: white; }
        .quick-filters {
            display: flex;
            gap: 10px;
            margin-left: auto;
        }
        .quick-filters a {
            background: #e9ecef;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            color: #495057;
        }
        .quick-filters a:hover {
            background: #dee2e6;
        }
        /* Стили для вкладок "Профиль" и "Заявки" */
        .tabs {
            display: flex;
            gap: 20px;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 25px;
        }
        .tab-button {
            background: none;
            border: none;
            padding: 10px 0;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            color: #6c757d;
            transition: all 0.2s;
            position: relative;
        }
        .tab-button.active {
            color: #3498db;
        }
        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #3498db;
        }
        .tab-button.unread::after {
            background: #ff4757;
        }
        /* Стили для карточек заявок */
        .request-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 16px;
            padding: 20px;
            transition: box-shadow 0.2s;
        }
        .request-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .request-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        .request-title h3 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        .request-meta-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .request-id {
            background: #e9ecef;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            color: #495057;
        }
        .badge-new, .badge-answered {
            font-size: 11px;
            padding: 2px 6px;
            border-radius: 12px;
            font-weight: bold;
        }
        .badge-new { background: #ff4757; color: white; }
        .badge-answered { background: #2ed573; color: white; }
        .request-date {
            font-size: 13px;
            color: #6c757d;
        }
        .message-preview {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            margin: 12px 0;
            font-size: 14px;
        }
        .admin-response {
            background: #e8f5e9;
            padding: 12px;
            border-radius: 6px;
            margin-top: 12px;
        }
        .request-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-view-details {
            background: #3498db;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .empty-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div class="container">
    <?php if (isset($_SESSION['request_success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['request_success']); unset($_SESSION['request_success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['request_errors'])): ?>
        <div class="alert alert-error">
            <h4>Ошибки при создании заявки:</h4>
            <ul><?php foreach ($_SESSION['request_errors'] as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; unset($_SESSION['request_errors']); ?></ul>
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
        <div class="tabs">
            <a href="?tab=profile" class="tab-button <?= $active_tab === 'profile' ? 'active' : '' ?>">Профиль</a>
            <a href="?tab=requests&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>" class="tab-button <?= $active_tab === 'requests' ? 'active' : '' ?> <?= $has_unread_requests ? 'unread' : '' ?>">Мои заявки</a>
        </div>

        <!-- Вкладка профиля -->
        <div id="profile" class="tab-content <?= $active_tab === 'profile' ? 'active' : '' ?>">
            <h2>Личная информация</h2>
            <form action="includes/update_profile.php" method="POST">
                <div class="form-group"><label>Email:</label><input type="email" value="<?= $user_data['email'] ?>" disabled></div>
                <div class="form-group"><label>Фамилия:</label><input type="text" name="last_name" value="<?= $user_data['last_name'] ?? '' ?>"></div>
                <div class="form-group"><label>Имя:</label><input type="text" name="first_name" value="<?= $user_data['first_name'] ?? '' ?>"></div>
                <div class="form-group"><label>Отчество:</label><input type="text" name="middle_name" value="<?= $user_data['middle_name'] ?? '' ?>"></div>
                <div class="form-group"><label>Дата рождения:</label><input type="date" name="birth_date" value="<?= $user_data['birth_date'] ?? '' ?>"></div>
                <div class="form-group"><label>Телефон:</label><input type="tel" name="phone" value="<?= $user_data['phone'] ?? '' ?>"></div>
                <div class="form-group">
                    <label>Улица и дом *:</label>
                    <input type="text" id="street" name="street" value="<?= htmlspecialchars(extractStreetFromAddress($user_data['address'] ?? '')) ?>" required>
                    <input type="hidden" id="full_address" name="address" value="<?= htmlspecialchars($user_data['address'] ?? '') ?>">
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Подъезд:</label><input type="text" id="entrance" name="entrance" value="<?= htmlspecialchars(extractEntranceFromAddress($user_data['address'] ?? '')) ?>" placeholder="№ подъезда"></div>
                    <div class="form-group"><label>Этаж:</label><input type="text" id="floor" name="floor" value="<?= htmlspecialchars(extractFloorFromAddress($user_data['address'] ?? '')) ?>" placeholder="№ этажа"></div>
                    <div class="form-group"><label>Квартира *:</label><input type="text" id="apartment" name="apartment" value="<?= htmlspecialchars(extractApartmentFromAddress($user_data['address'] ?? '')) ?>" placeholder="№ квартиры" required></div>
                </div>
                <button type="submit" class="btn-primary">Сохранить изменения</button>
            </form>
        </div>

        <!-- Вкладка заявок -->
        <div id="requests" class="tab-content <?= $active_tab === 'requests' ? 'active' : '' ?>">
            <h2>Мои заявки</h2>

            <!-- Статусные вкладки -->
            <div class="tabs-status">
                <?php
                $base_params = ['tab' => 'requests', 'search' => $search, 'date_from' => $date_from, 'date_to' => $date_to];
                $build_url = function($status) use ($base_params) {
                    $params = array_merge($base_params, ['status' => $status]);
                    return '?' . http_build_query($params);
                };
                ?>
                <a href="<?= $build_url('all') ?>" class="tab-status <?= $status_filter=='all'?'active':'' ?>">Все</a>
                <a href="<?= $build_url('new') ?>" class="tab-status <?= $status_filter=='new'?'active':'' ?>">
                    Новые <?= isset($stats['новая']) ? "<span class='badge-count'>{$stats['новая']}</span>" : '' ?>
                </a>
                <a href="<?= $build_url('work') ?>" class="tab-status <?= $status_filter=='work'?'active':'' ?>">
                    В работе <?= isset($stats['в работе']) ? "<span class='badge-count'>{$stats['в работе']}</span>" : '' ?>
                </a>
                <a href="<?= $build_url('completed') ?>" class="tab-status <?= $status_filter=='completed'?'active':'' ?>">
                    Выполненные <?= isset($stats['выполнена']) ? "<span class='badge-count'>{$stats['выполнена']}</span>" : '' ?>
                </a>
            </div>

            <!-- Быстрые фильтры по дате -->
            <div class="filter-bar">
                <div class="quick-filters">
                    <a href="?tab=requests&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>&preset=today">Сегодня</a>
                    <a href="?tab=requests&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>&preset=tomorrow">Завтра</a>
                    <a href="?tab=requests&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>&preset=week">Эта неделя</a>
                    <a href="?tab=requests&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>&preset=month">Этот месяц</a>
                </div>
            </div>

            <!-- Форма поиска и произвольных дат -->
            <form method="GET" class="filter-bar">
                <input type="hidden" name="tab" value="requests">
                <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                <div class="filter-group">
                    <label>Поиск:</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Тема или сообщение">
                </div>
                <div class="filter-group">
                    <label>Дата от:</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="filter-group">
                    <label>Дата до:</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <button type="submit" class="btn-primary btn-sm">Применить</button>
                <a href="?tab=requests&status=<?= urlencode($status_filter) ?>" class="btn-secondary btn-sm">Сбросить</a>
            </form>

            <?php if (empty($requests)): ?>
                <div class="no-requests">
                    <div class="empty-state">
                        <div class="empty-icon">📋</div>
                        <h3>Заявок не найдено</h3>
                        <p>Попробуйте изменить параметры фильтрации</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="requests-stats">
                    <div class="stat-item"><span class="stat-count"><?= count($requests) ?></span><span class="stat-label">Найдено</span></div>
                </div>
                <div class="requests-list">
                    <?php foreach ($requests as $request): ?>
                        <?php
                        $status_class = '';
                        switch ($request['status']) {
                            case 'новая': $status_class = 'status-new'; $status_text = 'Новая'; break;
                            case 'в работе': $status_class = 'status-progress'; $status_text = 'В работе'; break;
                            case 'выполнена': $status_class = 'status-completed'; $status_text = 'Выполнена'; break;
                            default: $status_class = 'status-new'; $status_text = $request['status'];
                        }
                        ?>
                        <div class="request-card <?= $request['is_read'] ? 'read' : 'unread' ?>">
                            <div class="request-card-header">
                                <div class="request-title">
                                    <h3><?= htmlspecialchars($request['subject']) ?></h3>
                                    <div class="request-meta-badges">
                                        <span class="request-id">#<?= $request['id'] ?></span>
                                        <?php if (!$request['is_read']): ?><span class="badge-new">НОВАЯ</span><?php endif; ?>
                                        <?php if (!empty($request['admin_response'])): ?><span class="badge-answered">✅ ОТВЕЧЕНО</span><?php endif; ?>
                                    </div>
                                </div>
                                <div class="request-date"><?= date('d.m.Y H:i', strtotime($request['created_at'])) ?></div>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                            </div>
                            <div class="request-content">
                                <div class="message-preview">
                                    <?php $msg = $request['message']; echo nl2br(htmlspecialchars(strlen($msg) > 300 ? substr($msg, 0, 300) . '...' : $msg)); ?>
                                </div>
                                <?php if (!empty($request['admin_response'])): ?>
                                    <div class="admin-response">
                                        <div class="response-header"><h4>Ответ администратора</h4><?php if ($request['responded_at']): ?><span class="response-date"><?= date('d.m.Y H:i', strtotime($request['responded_at'])) ?></span><?php endif; ?></div>
                                        <div class="response-content"><?= nl2br(htmlspecialchars($request['admin_response'])) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="request-card-footer">
                                <div class="request-actions">
                                    <?php if (!$request['is_read']): ?>
                                        <form action="includes/mark_as_read.php" method="POST" class="inline-form">
                                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                            <button type="submit" class="btn-secondary btn-small">Отметить как прочитанное</button>
                                        </form>
                                    <?php endif; ?>
                                    <button type="button" class="btn-view-details" onclick="showRequestDetails(<?= $request['id'] ?>)">Подробнее</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Модальное окно для просмотра деталей -->
<div id="requestModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle"></h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body"><div id="modalContent"></div></div>
    </div>
</div>

<script>
    function showRequestDetails(requestId) {
        fetch('includes/get_request_details.php?id=' + requestId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const req = data.request;
                    document.getElementById('modalTitle').textContent = `Заявка #${req.id}: ${req.subject}`;
                    let html = `<div><strong>Сообщение:</strong><br><pre style="white-space:pre-wrap">${escapeHtml(req.message)}</pre></div>`;
                    if (req.admin_response) html += `<div><strong>Ответ администратора:</strong><br><pre style="white-space:pre-wrap">${escapeHtml(req.admin_response)}</pre><div>Ответ дан: ${req.responded_at}</div></div>`;
                    html += `<div><strong>Статус:</strong> ${req.status}</div><div><strong>Создана:</strong> ${req.created_at}</div>`;
                    document.getElementById('modalContent').innerHTML = html;
                    document.getElementById('requestModal').style.display = 'block';
                } else alert('Ошибка загрузки');
            }).catch(error => { console.error(error); alert('Не удалось загрузить детали'); });
    }

    function closeModal() { document.getElementById('requestModal').style.display = 'none'; }
    window.onclick = function(event) { const modal = document.getElementById('requestModal'); if (event.target === modal) modal.style.display = 'none'; }
    function escapeHtml(text) { return text.replace(/[&<>]/g, function(m){ if(m==='&') return '&amp;'; if(m==='<') return '&lt;'; if(m==='>') return '&gt;'; return m;}); }
</script>
<script src="js/address-autocomplete.js"></script>
</body>
</html>