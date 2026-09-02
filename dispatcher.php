<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once 'includes/config.php';
require_once 'includes/check_auth.php';

// Проверка прав: только диспетчер или администратор (но админ уже имеет admin.php)
checkAuth(); // проверяет, что пользователь залогинен
if (!isAdminOrModerator()) { // функция isDispatcher() должна быть в check_auth.php
    header('Location: user.php');
    exit();
}

// Определяем активную секцию
$active_section = $_GET['section'] ?? $_SESSION['dispatcher_active_section'] ?? 'news';
$_SESSION['dispatcher_active_section'] = $active_section;

// Сообщения
$message = $_SESSION['dispatcher_message'] ?? '';
$message_type = $_SESSION['dispatcher_message_type'] ?? '';
unset($_SESSION['dispatcher_message'], $_SESSION['dispatcher_message_type']);

// Статистика новых заявок
$new_requests_count = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM message WHERE is_read = 0");
    $new_requests_count = $stmt->fetchColumn();
} catch (PDOException $e) {}

// Функция для получения доступных сотрудников (такая же как в admin.php)
function getAvailableEmployees($category_id) {
    global $pdo;
    static $cache = [];
    if (isset($cache[$category_id])) return $cache[$category_id];
    
    $dept_stmt = $pdo->prepare("SELECT id, department_name FROM department_rules WHERE category_id = ? LIMIT 1");
    $dept_stmt->execute([$category_id]);
    $dept = $dept_stmt->fetch();
    if (!$dept) {
        $cache[$category_id] = [];
        return [];
    }
    $pos_stmt = $pdo->prepare("
        SELECT p.name 
        FROM department_positions dp
        JOIN positions p ON dp.position_id = p.id
        WHERE dp.department_rule_id = ?
    ");
    $pos_stmt->execute([$dept['id']]);
    $positions = $pos_stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($positions)) {
        $cache[$category_id] = [];
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($positions), '?'));
    $sql = "
        SELECT u.id, 
               CONCAT(up.last_name, ' ', up.first_name) as name,
               COALESCE(SUM(m.estimated_hours), 0) as current_load
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        LEFT JOIN message m ON u.id = m.assigned_to AND m.status IN ('новая', 'в работе')
        WHERE u.role IN ('executor','dispatcher','moderator')
          AND u.is_active = 1
          AND up.department = ?
          AND up.position IN ($placeholders)
        GROUP BY u.id, up.last_name, up.first_name
        ORDER BY current_load ASC, up.last_name
    ";
    $params = array_merge([$dept['department_name']], $positions);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $cache[$category_id] = $employees;
    return $employees;
}

// Список всех сотрудников для назначения (используется в JS)
$employeesWithLoad = $pdo->query("
    SELECT 
        u.id, 
        CONCAT(up.last_name, ' ', up.first_name) as name,
        COALESCE(SUM(m.estimated_hours), 0) as current_load
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN message m ON u.id = m.assigned_to AND m.status IN ('новая', 'в работе')
    WHERE u.role IN ('executor','dispatcher','moderator')
    GROUP BY u.id, up.last_name, up.first_name
    ORDER BY current_load ASC, up.last_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="img/icons/icon.ico">
    <title>Личный кабинет диспетчера</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/mobile_all.css">
    <style>
        .filter-bar { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
        .filter-group { display: inline-flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 12px; font-weight: 600; color: #6c757d; }
        .request-row.unread { background: #fff3e0; }
        .badge-new { background: #ff4757; color: white; border-radius: 20px; padding: 2px 8px; font-size: 11px; margin-left: 8px; }
        .modal-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items: center; justify-content: center; }
        .modal { background: white; border-radius: 12px; width: 600px; max-width: 90%; padding: 20px; }
        .modal-header { display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; }
        .form-control { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .form-actions { margin-top: 15px; display: flex; gap: 10px; }
        .tab-link { padding: 8px 16px; text-decoration: none; color: #333; border-radius: 4px; background: #f1f1f1; transition: background 0.2s; }
        .tab-link.active { background: #007bff; color: #fff; font-weight: bold; }
        .tab-link:hover { background: #e0e0e0; }
        .tab-link.active:hover { background: #0069d9; }
        /* Для карточек новостей и цен */
        .news-grid, .prices-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
        .news-card, .price-card { background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; }
        .news-card:hover, .price-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
        .news-image { width: 100%; height: 180px; object-fit: cover; background: #f0f2f5; }
        .news-content, .price-content { padding: 16px; }
        .news-title, .price-title { font-size: 1.2rem; font-weight: 600; margin: 0 0 8px 0; color: #2c3e50; }
        .news-description, .price-description { font-size: 0.9rem; color: #6c757d; line-height: 1.4; margin-bottom: 12px; }
        .news-date, .price-price { font-size: 0.8rem; color: #3498db; font-weight: 500; margin-bottom: 12px; }
        .price-unit { font-size: 0.8rem; color: #95a5a6; margin-left: 4px; }
        .news-actions, .price-actions { display: flex; gap: 8px; justify-content: flex-end; border-top: 1px solid #ecf0f1; padding-top: 12px; margin-top: 8px; }
        .btn-edit, .btn-delete, .btn-view { background: none; border: none; font-size: 18px; cursor: pointer; padding: 0 4px; transition: transform 0.1s; }
        .btn-edit:hover, .btn-delete:hover, .btn-view:hover { transform: scale(1.1); }
        .form-card { background: #f8f9fa; border-radius: 12px; padding: 20px; margin-bottom: 25px; border: 1px solid #e9ecef; }
        .form-card h3 { margin-top: 0; margin-bottom: 15px; font-size: 1.1rem; color: #2c3e50; }
        .form-row { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px; }
        .form-row .form-group { flex: 1; min-width: 150px; }
        .btn { display: inline-block; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-size: 14px; font-weight: 500; text-decoration: none; transition: background 0.2s, opacity 0.2s; }
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-success:hover { background: #27ae60; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-secondary:hover { background: #7f8c8d; }
        .btn-sm { padding: 4px 10px; font-size: 12px; }
        .table-container { overflow-x: auto; margin-top: 20px; }
        .table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .table th, .table td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #e9ecef; }
        .table th { background: #f8f9fa; font-weight: 600; color: #495057; }
        .table tr:hover { background: #f8f9fa; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; background: #ecf0f1; color: #2c3e50; }
        .status-new { background: #ff4757; color: white; }
        .status-progress { background: #ffa502; color: white; }
        .status-completed { background: #2ed573; color: white; }
        .empty-state { text-align: center; padding: 40px 20px; color: #6c757d; }
        .empty-icon { font-size: 48px; margin-bottom: 15px; }
        .alert { padding: 12px 20px; border-radius: 6px; margin: 20px; font-weight: 500; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<header>
    <div class="admin-nav">
        <h1>Личный кабинет диспетчера</h1>
        <div>
            <span><?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
            <a href="index.php">Главная</a>
            <a href="includes/logout.php">Выйти</a>
        </div>
    </div>
</header>

<div class="admin-container">
    <div class="admin-content">
        <aside class="admin-sidebar">
            <ul>
                <li><a href="?section=news" class="<?php echo $active_section=='news'?'active':''; ?>">Новости</a></li>
                <li><a href="?section=prices" class="<?php echo $active_section=='prices'?'active':''; ?>">Цены</a></li>
                <li><a href="?section=requests" class="<?php echo $active_section=='requests'?'active':''; ?>">Заявки <?php if($new_requests_count) echo "<span class='sidebar-badge'>$new_requests_count</span>"; ?></a></li>
            </ul>
        </aside>

        <main class="admin-main">
            <!-- НОВОСТИ -->
            <div id="news" class="section <?php echo $active_section=='news'?'active':''; ?>">
                <h2>Управление новостями</h2>
                <button class="btn btn-primary" onclick="showNewsForm()">➕ Добавить новость</button>

                <div id="news-form" class="form-card" style="display:none; margin-top:20px;">
                    <h3 id="newsFormTitle">Добавление новости</h3>
                    <form action="includes/save_news.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="news_id" id="news_id">
                        <div class="form-group"><label>Заголовок</label><input type="text" name="title" id="news_title" required></div>
                        <div class="form-group"><label>Описание</label><textarea name="description" id="news_description" rows="5" required></textarea></div>
                        <div class="form-group"><label>Изображение</label><input type="file" name="image" accept="image/*"></div>
                        <button type="submit" class="btn btn-success">💾 Сохранить</button>
                        <button type="button" class="btn btn-secondary" onclick="hideNewsForm()">Отмена</button>
                    </form>
                </div>

                <div class="news-grid" id="newsGrid">
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT * FROM news ORDER BY created_at DESC");
                        $newsList = $stmt->fetchAll();
                        if (empty($newsList)) {
                            echo '<div class="empty-state"><div class="empty-icon">📭</div><h3>Новостей пока нет</h3><p>Нажмите «Добавить новость»</p></div>';
                        } else {
                            foreach ($newsList as $item) {
                                $imagePath = !empty($item['image']) && file_exists('../uploads/news/' . $item['image']) 
                                            ? '../uploads/news/' . $item['image'] 
                                            : 'https://placehold.co/800x400?text=Новость';
                                ?>
                                <div class="news-card" data-id="<?= $item['id'] ?>">
                                    <img class="news-image" src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($item['title']) ?>" onerror="this.src='https://placehold.co/800x400?text=Нет+фото'">
                                    <div class="news-content">
                                        <h3 class="news-title"><?= htmlspecialchars($item['title']) ?></h3>
                                        <div class="news-description"><?= nl2br(htmlspecialchars(mb_substr($item['description'], 0, 150))) ?>...</div>
                                        <div class="news-date">📅 <?= date('d.m.Y H:i', strtotime($item['created_at'])) ?></div>
                                        <div class="news-actions">
                                            <button class="btn-edit"
                                                    data-id="<?= (int)$item['id'] ?>"
                                                    data-title="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-description="<?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?>"
                                                    title="Редактировать">✏️</button>
                                            <a href="includes/delete_news.php?id=<?= $item['id'] ?>" class="btn-delete" onclick="return confirm('Удалить новость?')" title="Удалить">🗑️</a>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                    } catch (PDOException $e) {
                        echo '<div class="alert alert-error">Ошибка загрузки новостей: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- ЦЕНЫ -->
            <div id="prices" class="section <?php echo $active_section=='prices'?'active':''; ?>">
                <h2>Управление ценами на услуги</h2>
                <button class="btn btn-primary" onclick="showPriceForm()">➕ Добавить услугу</button>

                <div id="price-form" class="form-card" style="display:none; margin-top:20px;">
                    <h3 id="priceFormTitle">Добавление услуги</h3>
                    <form action="includes/save_price.php" method="POST">
                        <input type="hidden" name="price_id" id="price_id">
                        <div class="form-group"><label>Название услуги *</label><input type="text" name="service_name" id="service_name" required></div>
                        <div class="form-group"><label>Описание</label><textarea name="description" id="price_description" rows="3"></textarea></div>
                        <div class="form-row">
                            <div class="form-group"><label>Цена (руб.) *</label><input type="number" name="price" id="service_price" step="0.01" required></div>
                            <div class="form-group"><label>Единица измерения</label><input type="text" name="unit" id="service_unit" placeholder="шт., м², час"></div>
                        </div>
                        <button type="submit" class="btn btn-success">💾 Сохранить</button>
                        <button type="button" class="btn btn-secondary" onclick="hidePriceForm()">Отмена</button>
                    </form>
                </div>

                <div class="prices-grid">
                    <?php
                    try {
                        $stmt = $pdo->query("SELECT * FROM services ORDER BY service_name");
                        $pricesList = $stmt->fetchAll();
                        if (empty($pricesList)) {
                            echo '<div class="empty-state"><div class="empty-icon">💸</div><h3>Услуг пока нет</h3><p>Нажмите «Добавить услугу»</p></div>';
                        } else {
                            foreach ($pricesList as $item) {
                                ?>
                                <div class="price-card" data-id="<?= $item['id'] ?>">
                                    <div class="price-content">
                                        <h3 class="price-title"><?= htmlspecialchars($item['service_name']) ?></h3>
                                        <div class="price-description"><?= nl2br(htmlspecialchars($item['description'] ?: 'Без описания')) ?></div>
                                        <div class="price-price">
                                            💵 <?= number_format($item['price'], 0, '.', ' ') ?> ₽
                                            <?php if (!empty($item['unit'])): ?>
                                                <span class="price-unit">/ <?= htmlspecialchars($item['unit']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="price-actions">
                                            <button class="btn-edit"
                                                    data-id="<?= (int)$item['id'] ?>"
                                                    data-name="<?= htmlspecialchars($item['service_name'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-description="<?= htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8') ?>"
                                                    data-price="<?= (float)$item['price'] ?>"
                                                    data-unit="<?= htmlspecialchars($item['unit'], ENT_QUOTES, 'UTF-8') ?>"
                                                    title="Редактировать">✏️</button>
                                            <a href="includes/delete_price.php?id=<?= $item['id'] ?>" class="btn-delete" onclick="return confirm('Удалить услугу?')" title="Удалить">🗑️</a>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                    } catch (PDOException $e) {
                        echo '<div class="alert alert-error">Ошибка загрузки услуг: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- ЗАЯВКИ -->
            <div id="requests" class="section <?php echo $active_section=='requests'?'active':''; ?>">
                <h2>Управление заявками <?php if($new_requests_count) echo "<span class='badge-new'>$new_requests_count новых</span>"; ?></h2>

                <!-- Табы для статусов -->
                <div class="tab-bar" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px;">
                    <?php
                    $current_status = $_GET['status_filter'] ?? 'all';
                    $params = $_GET;
                    unset($params['status_filter']);
                    $query_string = http_build_query($params);
                    $tabs = [
                        'all' => 'Все',
                        'новая' => 'Новые',
                        'в работе' => 'В работе',
                        'выполнена' => 'Выполненные'
                    ];
                    foreach ($tabs as $status_value => $label) {
                        $active = ($current_status == $status_value) ? 'active' : '';
                        $href = '?section=requests&status_filter=' . urlencode($status_value) . ($query_string ? '&' . $query_string : '');
                        echo '<a href="' . $href . '" class="tab-link ' . $active . '" style="padding: 8px 16px; text-decoration: none; color: #333; border-radius: 4px; background: ' . ($active ? '#007bff' : '#f1f1f1') . '; color: ' . ($active ? '#fff' : '#333') . '; font-weight: ' . ($active ? 'bold' : 'normal') . ';">' . $label . '</a>';
                    }
                    ?>
                </div>

                <!-- Фильтры (отдел, дата, назначение) -->
                <div class="filter-bar">
                    <form method="GET" id="filterForm">
                        <input type="hidden" name="section" value="requests">
                        <?php if ($current_status != 'all'): ?>
                            <input type="hidden" name="status_filter" value="<?= htmlspecialchars($current_status) ?>">
                        <?php endif; ?>
                        <div class="filter-group">
                            <label>Отдел:</label>
                            <select name="dept_filter" onchange="this.form.submit()">
                                <option value="all">Все отделы</option>
                                <?php
                                $depts = $pdo->query("SELECT DISTINCT department FROM user_profiles WHERE department IS NOT NULL AND department != ''")->fetchAll();
                                foreach($depts as $d): ?>
                                    <option value="<?= htmlspecialchars($d['department']) ?>" <?= ($_GET['dept_filter']??'')==$d['department']?'selected':'' ?>><?= htmlspecialchars($d['department']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Дата от:</label>
                            <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from']??'') ?>" onchange="this.form.submit()">
                        </div>
                        <div class="filter-group">
                            <label>Дата до:</label>
                            <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to']??'') ?>" onchange="this.form.submit()">
                        </div>
                        <div class="filter-group">
                            <label>Назначение:</label>
                            <select name="assign_filter" onchange="this.form.submit()">
                                <option value="all" <?= ($_GET['assign_filter']??'all')=='all'?'selected':'' ?>>Все</option>
                                <option value="assigned" <?= ($_GET['assign_filter']??'')=='assigned'?'selected':'' ?>>Назначенные</option>
                                <option value="unassigned" <?= ($_GET['assign_filter']??'')=='unassigned'?'selected':'' ?>>Не назначенные</option>
                            </select>
                        </div>
                        <a href="?section=requests" class="btn btn-secondary btn-sm">Сбросить</a>
                    </form>
                </div>

                <!-- Убрали массовое удаление, оставили только просмотр и редактирование -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Имя</th>
                                <th>Email</th>
                                <th>Телефон</th>
                                <th>Тема</th>
                                <th>Статус</th>
                                <th>Назначен</th>
                                <th>Дата</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT m.*, CONCAT(up.last_name, ' ', up.first_name) as assigned_name
                                    FROM message m
                                    LEFT JOIN users u ON m.assigned_to = u.id
                                    LEFT JOIN user_profiles up ON u.id = up.user_id
                                    WHERE 1=1";
                            $params = [];
                            if(!empty($_GET['status_filter']) && $_GET['status_filter'] != 'all') {
                                $sql .= " AND m.status = ?";
                                $params[] = $_GET['status_filter'];
                            }
                            if(!empty($_GET['dept_filter']) && $_GET['dept_filter'] != 'all') {
                                $sql .= " AND up.department = ?";
                                $params[] = $_GET['dept_filter'];
                            }
                            if(!empty($_GET['date_from'])) {
                                $sql .= " AND DATE(m.created_at) >= ?";
                                $params[] = $_GET['date_from'];
                            }
                            if(!empty($_GET['date_to'])) {
                                $sql .= " AND DATE(m.created_at) <= ?";
                                $params[] = $_GET['date_to'];
                            }
                            if(($_GET['assign_filter']??'all') == 'assigned') {
                                $sql .= " AND m.assigned_to IS NOT NULL AND m.assigned_to != 0";
                            } elseif(($_GET['assign_filter']??'all') == 'unassigned') {
                                $sql .= " AND (m.assigned_to IS NULL OR m.assigned_to = 0)";
                            }
                            $sql .= " ORDER BY m.created_at DESC";

                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);
                            $requests = $stmt->fetchAll();

                            if(empty($requests)) {
                                echo '<tr><td colspan="9" class="text-center">Заявок не найдено</td></tr>';
                            } else {
                                foreach($requests as $req) {
                                    $status_class = '';
                                    switch($req['status']) {
                                        case 'новая': $status_class = 'status-new'; break;
                                        case 'в работе': $status_class = 'status-progress'; break;
                                        case 'выполнена': $status_class = 'status-completed'; break;
                                        default: $status_class = 'status-new';
                                    }
                                    ?>
                                    <tr class="request-row <?= $req['is_read']?'':'unread' ?>" id="row-<?= $req['id'] ?>">
                                        <td>#<?= $req['id'] ?></td>
                                        <td><?= htmlspecialchars($req['user_name']) ?></td>
                                        <td><?= htmlspecialchars($req['user_email']) ?></td>
                                        <td><?= htmlspecialchars($req['phone']??'—') ?></td>
                                        <td><?= htmlspecialchars($req['subject']) ?></td>
                                        <td><span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($req['status']) ?></span></td>
                                        <td>
                                            <form class="assign-form" data-id="<?= $req['id'] ?>">
                                                <select name="employee_id" class="assign-select">
                                                    <option value="">Не назначен</option>
                                                    <?php 
                                                    $availableEmps = getAvailableEmployees($req['category_id']);
                                                    foreach ($availableEmps as $emp): 
                                                        $selected = ($req['assigned_to'] == $emp['id']) ? 'selected' : '';
                                                        $loadText = $emp['current_load'] > 0 ? ' (загрузка: ' . number_format($emp['current_load'], 1) . ' ч)' : ' (свободен)';
                                                    ?>
                                                        <option value="<?= $emp['id'] ?>" <?= $selected ?>>
                                                            <?= htmlspecialchars($emp['name']) . $loadText ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="button" class="btn btn-sm btn-success assign-btn" data-id="<?= $req['id'] ?>">Назначить</button>
                                            </form>
                                        </td>
                                        <td><?= date('d.m.Y H:i', strtotime($req['created_at'])) ?></td>
                                        <td class="request-actions">
                                            <button class="btn-view" onclick="showRequestDetails(<?= $req['id'] ?>)" title="Просмотр">👁️</button>
                                            <button class="btn-edit" onclick="editRequest(<?= $req['id'] ?>)" title="Редактировать">✏️</button>
                                            <!-- Удалили кнопку удаления -->
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Модальное окно просмотра заявки -->
<div class="modal-overlay" id="requestModal"><div class="modal"><div class="modal-header"><h3 id="modalTitle">Заявка #</h3><button class="modal-close" onclick="closeModal()">×</button></div><div class="modal-body" id="modalContent"></div></div></div>

<!-- Модальное окно редактирования заявки -->
<div id="editRequestModal" class="modal-overlay" style="display:none;">
    <div class="modal">
        <div class="modal-header">
            <h3>Редактирование заявки #<span id="editRequestIdDisplay"></span></h3>
            <button class="modal-close" onclick="closeEditModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="editRequestForm">
                <input type="hidden" name="request_id" id="editRequestId">
                <div class="form-group">
                    <label>Тема</label>
                    <input type="text" name="subject" id="editSubject" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Статус</label>
                    <select name="status" id="editStatus" class="form-control">
                        <option value="новая">Новая</option>
                        <option value="в работе">В работе</option>
                        <option value="выполнена">Выполнена</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Назначенный сотрудник</label>
                    <select name="assigned_to" id="editAssignedTo" class="form-control">
                        <option value="0">Не назначен</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ответ администратора</label>
                    <textarea name="admin_response" id="editAdminResponse" rows="4" class="form-control"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-success" onclick="saveEditedRequest()">💾 Сохранить</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Отмена</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Список всех сотрудников для назначения
const allEmployees = <?= json_encode($employeesWithLoad) ?>;

// Функции для новостей и цен (как в админке)
function showNewsForm() {
    document.getElementById('news-form').style.display = 'block';
    document.getElementById('newsFormTitle').innerText = 'Добавление новости';
    document.getElementById('news_id').value = '';
    document.getElementById('news_title').value = '';
    document.getElementById('news_description').value = '';
}
function hideNewsForm() {
    document.getElementById('news-form').style.display = 'none';
}
function editNews(id, title, description) {
    document.getElementById('news-form').style.display = 'block';
    document.getElementById('newsFormTitle').innerText = 'Редактирование новости';
    document.getElementById('news_id').value = id;
    document.getElementById('news_title').value = title;
    document.getElementById('news_description').value = description;
}

function showPriceForm() {
    document.getElementById('price-form').style.display = 'block';
    document.getElementById('priceFormTitle').innerText = 'Добавление услуги';
    document.getElementById('price_id').value = '';
    document.getElementById('service_name').value = '';
    document.getElementById('price_description').value = '';
    document.getElementById('service_price').value = '';
    document.getElementById('service_unit').value = '';
}
function hidePriceForm() {
    document.getElementById('price-form').style.display = 'none';
}
function editPrice(id, name, description, price, unit) {
    document.getElementById('price-form').style.display = 'block';
    document.getElementById('priceFormTitle').innerText = 'Редактирование услуги';
    document.getElementById('price_id').value = id;
    document.getElementById('service_name').value = name;
    document.getElementById('price_description').value = description;
    document.getElementById('service_price').value = price;
    document.getElementById('service_unit').value = unit;
}

// Заявки – просмотр
function showRequestDetails(id) {
    fetch('includes/get_request_details.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const req = data.request;
                document.getElementById('modalTitle').innerText = 'Заявка #' + req.id;
                let html = '<p><strong>Имя:</strong> ' + escapeHtml(req.user_name) + '</p>';
                html += '<p><strong>Email:</strong> ' + escapeHtml(req.user_email) + '</p>';
                html += '<p><strong>Телефон:</strong> ' + escapeHtml(req.phone || '—') + '</p>';
                html += '<p><strong>Адрес:</strong> ' + escapeHtml(req.address || '—') + '</p>';
                html += '<p><strong>Тема:</strong> ' + escapeHtml(req.subject) + '</p>';
                html += '<p><strong>Сообщение:</strong><br>' + escapeHtml(req.message) + '</p>';
                html += '<p><strong>Статус:</strong> ' + escapeHtml(req.status) + '</p>';
                html += '<p><strong>Назначен:</strong> ' + (req.assigned_to ? '#' + req.assigned_to : 'Не назначен') + '</p>';
                html += '<p><strong>Дата:</strong> ' + new Date(req.created_at).toLocaleString() + '</p>';
                if (req.admin_response) {
                    html += '<p><strong>Ответ администратора:</strong><br>' + escapeHtml(req.admin_response) + '</p>';
                }
                document.getElementById('modalContent').innerHTML = html;
                document.getElementById('requestModal').style.display = 'flex';
            } else {
                alert('Ошибка загрузки: ' + data.error);
            }
        })
        .catch(err => {
            alert('Ошибка: ' + err.message);
        });
}
function closeModal() {
    document.getElementById('requestModal').style.display = 'none';
}

// Редактирование заявки
function editRequest(id) {
    fetch('includes/get_request_details.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                alert('Ошибка загрузки данных заявки: ' + data.error);
                return;
            }
            const req = data.request;
            document.getElementById('editRequestId').value = req.id;
            document.getElementById('editRequestIdDisplay').innerText = req.id;
            document.getElementById('editSubject').value = req.subject;
            document.getElementById('editStatus').value = req.status;
            document.getElementById('editAdminResponse').value = req.admin_response || '';
            
            // Загружаем список сотрудников
            loadAssignSelect(req.assigned_to);
            
            document.getElementById('editRequestModal').style.display = 'flex';
        })
        .catch(err => {
            alert('Ошибка: ' + err.message);
        });
}

function loadAssignSelect(selectedId) {
    const select = document.getElementById('editAssignedTo');
    select.innerHTML = '<option value="0">Не назначен</option>';
    allEmployees.forEach(emp => {
        const option = document.createElement('option');
        option.value = emp.id;
        option.textContent = emp.name + (emp.current_load > 0 ? ' (загрузка: ' + emp.current_load + ' ч)' : ' (свободен)');
        if (emp.id == selectedId) option.selected = true;
        select.appendChild(option);
    });
}

function saveEditedRequest() {
    const form = document.getElementById('editRequestForm');
    const formData = new FormData(form);
    
    fetch('includes/edit_request.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Заявка успешно обновлена');
            closeEditModal();
            location.reload();
        } else {
            alert('Ошибка: ' + data.error);
        }
    })
    .catch(err => {
        alert('Ошибка сети: ' + err.message);
    });
}

function closeEditModal() {
    document.getElementById('editRequestModal').style.display = 'none';
}

// Вспомогательные функции
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Инициализация кнопок назначения
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.assign-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('.assign-form');
            const requestId = form.dataset.id;
            const select = form.querySelector('.assign-select');
            const employeeId = select.value;
            if (!employeeId) {
                alert('Выберите сотрудника');
                return;
            }
            fetch('includes/assign_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'request_id=' + requestId + '&employee_id=' + employeeId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Назначено');
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.error);
                }
            })
            .catch(err => alert('Ошибка: ' + err.message));
        });
    });

    // Обработчики для редактирования новостей и цен
    document.querySelectorAll('.news-card .btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            editNews(this.dataset.id, this.dataset.title, this.dataset.description);
        });
    });
    document.querySelectorAll('.price-card .btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            editPrice(this.dataset.id, this.dataset.name, this.dataset.description, this.dataset.price, this.dataset.unit);
        });
    });

    // Закрытие модальных окон по клику на фон
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
});
</script>
</body>
</html>