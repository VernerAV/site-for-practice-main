<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once 'includes/config.php';
require_once 'includes/check_auth.php';
function getAvailableEmployees($category_id) {
    global $pdo;
    static $cache = [];
    if (isset($cache[$category_id])) return $cache[$category_id];
    
    // Найти отдел по категории
    $dept_stmt = $pdo->prepare("SELECT id, department_name FROM department_rules WHERE category_id = ? LIMIT 1");
    $dept_stmt->execute([$category_id]);
    $dept = $dept_stmt->fetch();
    if (!$dept) {
        $cache[$category_id] = [];
        return [];
    }
    // Найти должности для этого отдела
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
checkAuth();

if (!isAdmin()) {
    header('Location: user.php');
    exit();
}

// Определяем активную секцию
$active_section = $_GET['section'] ?? $_SESSION['admin_active_section'] ?? 'news';
$_SESSION['admin_active_section'] = $active_section;

// Сообщения
$message = $_SESSION['admin_message'] ?? '';
$message_type = $_SESSION['admin_message_type'] ?? '';
unset($_SESSION['admin_message'], $_SESSION['admin_message_type']);

// Статистика новых заявок
$new_requests_count = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM message WHERE is_read = 0");
    $new_requests_count = $stmt->fetchColumn();
} catch (PDOException $e) {}

// Получаем списки должностей и отделов для выпадающих списков
$positions = $pdo->query("SELECT id, name FROM positions ORDER BY name")->fetchAll();
$departments = $pdo->query("SELECT id, department_name FROM department_rules ORDER BY department_name")->fetchAll();

// Обработка добавления/редактирования сотрудника
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['employee_submit'])) {
    $emp_id = (int)($_POST['emp_id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $last_name = trim($_POST['last_name'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
    $position_id = (int)($_POST['position_id'] ?? 0);
    $department_id = (int)($_POST['department_id'] ?? 0);
    $role = $_POST['role'] ?? 'executor';

    try {
        if ($emp_id == 0) {
            // Добавление
            if (empty($password)) throw new Exception('Пароль обязателен');
            if (empty($email)) throw new Exception('Email обязателен');
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO users (email, password, role, is_active) VALUES (?, ?, ?, 1)");
            $stmt->execute([$email, $hashed, $role]);
            $new_id = $pdo->lastInsertId();
            $emp_number = 'EMP' . str_pad($new_id, 5, '0', STR_PAD_LEFT);
            
            $pos_name = $pdo->prepare("SELECT name FROM positions WHERE id = ?");
            $pos_name->execute([$position_id]);
            $position_text = $pos_name->fetchColumn();
            
            $dept_name = $pdo->prepare("SELECT department_name FROM department_rules WHERE id = ?");
            $dept_name->execute([$department_id]);
            $department_text = $dept_name->fetchColumn();
            
            $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, last_name, first_name, middle_name, phone, birth_date, position, department, employee_id) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$new_id, $last_name, $first_name, $middle_name, $phone, $birth_date, $position_text, $department_text, $emp_number]);
            $pdo->commit();
            $_SESSION['admin_message'] = "Сотрудник добавлен. Табельный номер: $emp_number";
            $_SESSION['admin_message_type'] = 'success';
        } else {
            // Редактирование
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE users SET email = ?, role = ? WHERE id = ?");
            $stmt->execute([$email, $role, $emp_id]);
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $emp_id]);
            }
            $pos_name = $pdo->prepare("SELECT name FROM positions WHERE id = ?");
            $pos_name->execute([$position_id]);
            $position_text = $pos_name->fetchColumn();
            
            $dept_name = $pdo->prepare("SELECT department_name FROM department_rules WHERE id = ?");
            $dept_name->execute([$department_id]);
            $department_text = $dept_name->fetchColumn();
            
            $stmt = $pdo->prepare("UPDATE user_profiles SET last_name = ?, first_name = ?, middle_name = ?, phone = ?, birth_date = ?, position = ?, department = ? WHERE user_id = ?");
            $stmt->execute([$last_name, $first_name, $middle_name, $phone, $birth_date, $position_text, $department_text, $emp_id]);
            $pdo->commit();
            $_SESSION['admin_message'] = "Данные сотрудника обновлены";
            $_SESSION['admin_message_type'] = 'success';
        }
    } catch (Exception $e) {
        if (isset($pdo)) $pdo->rollBack();
        $_SESSION['admin_message'] = 'Ошибка: ' . $e->getMessage();
        $_SESSION['admin_message_type'] = 'error';
    }
    header("Location: admin.php?section=employees");
    exit;
}

// Удаление сотрудника
if (isset($_GET['delete_employee'])) {
    $id = (int)$_GET['delete_employee'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $pdo->commit();
        $_SESSION['admin_message'] = "Сотрудник удалён";
        $_SESSION['admin_message_type'] = 'success';
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['admin_message'] = "Ошибка: " . $e->getMessage();
        $_SESSION['admin_message_type'] = 'error';
    }
    header("Location: admin.php?section=employees");
    exit;
}

// Получаем список всех сотрудников для назначения (используется в JS)
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
    <title>Панель администратора</title>
    <link rel="stylesheet" href="css/admin.css">
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
    </style>
</head>
<body>
<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<header>
    <div class="admin-nav">
        <h1>Панель администратора</h1>
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
                <li><a href="?section=employees" class="<?php echo $active_section=='employees'?'active':''; ?>">Сотрудники</a></li>
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

                <!-- Фильтры -->
                <!-- Табы для статусов -->
            <div class="tab-bar" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ddd; padding-bottom: 10px;">
                <?php
                // Текущий статус (по умолчанию 'all')
                $current_status = $_GET['status_filter'] ?? 'all';
                // Базовые параметры для ссылок (кроме status_filter)
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

            <!-- Остальные фильтры (отдел, дата, назначение) -->
            <div class="filter-bar" style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;">
                <form method="GET" id="filterForm">
                    <input type="hidden" name="section" value="requests">
                    <!-- Сохраняем текущий статус, если он не all -->
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

                <div class="bulk-actions mb-20">
                    <button class="btn btn-danger" onclick="showBulkDeleteModal()" id="bulkDeleteBtn" style="display: none;">🗑️ Удалить выбранные (0)</button>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr><th width="40"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                            <th>ID</th>
                            <th>Имя</th>
                            <th>Email</th>
                            <th>Телефон</th>
                            <th>Тема</th>
                            <th>Статус</th>
                            <th>Назначен</th>
                            <th>Дата</th>
                            <th>Действия</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            // Базовый запрос
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

                            // ===== ИСПРАВЛЕННЫЙ ЗАПРОС ДЛЯ СОТРУДНИКОВ С ЗАГРУЗКОЙ =====
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
                            // ====================================================

                            if(empty($requests)) {
                                echo '<tr><td colspan="11" class="text-center">Заявок не найдено</td></tr>';
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
                                        <td><input type="checkbox" class="request-checkbox" value="<?= $req['id'] ?>" onchange="updateBulkDeleteCount()"></td>
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
                                            <button class="btn-delete" onclick="deleteSingleRequest(<?= $req['id'] ?>)" title="Удалить">🗑️</button>
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

            <!-- СОТРУДНИКИ -->
            <div id="employees" class="section <?php echo $active_section == 'employees' ? 'active' : ''; ?>">
                <h2>Управление сотрудниками</h2>
                <button class="btn btn-primary" onclick="showEmployeeForm()">➕ Добавить сотрудника</button>

                <div id="employeeForm" class="form-card" style="display: none; margin-top: 20px; background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <h3 id="employeeFormTitle">Добавление сотрудника</h3>
                    <form id="employeeFormElement" method="POST" action="admin.php?section=employees">
                        <input type="hidden" name="employee_submit" value="1">
                        <input type="hidden" name="emp_id" id="emp_id" value="0">
                        <div class="form-row">
                            <div class="form-group"><label>Email *</label><input type="email" name="email" id="emp_email" required></div>
                            <div class="form-group"><label>Пароль *</label><input type="password" name="password" id="emp_password" placeholder="Оставьте пустым при редактировании"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Фамилия</label><input type="text" name="last_name" id="emp_last_name"></div>
                            <div class="form-group"><label>Имя</label><input type="text" name="first_name" id="emp_first_name"></div>
                            <div class="form-group"><label>Отчество</label><input type="text" name="middle_name" id="emp_middle_name"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Телефон</label><input type="text" name="phone" id="emp_phone"></div>
                            <div class="form-group"><label>Дата рождения</label><input type="date" name="birth_date" id="emp_birth_date"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Отдел</label>
                                <select name="department_id" id="emp_department_id" required>
                                    <option value="">Выберите отдел</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['department_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Должность</label>
                                <select name="position_id" id="emp_position_id" required>
                                    <option value="">Сначала выберите отдел</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Роль</label>
                                <select name="role" id="emp_role">
                                    <option value="executor">Исполнитель</option>
                                    <option value="dispatcher">Диспетчер</option>
                                    <option value="moderator">Модератор</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">💾 Сохранить</button>
                        <button type="button" class="btn btn-secondary" onclick="hideEmployeeForm()">Отмена</button>
                    </form>
                </div>

                <div class="table-container mt-20">
                    <table class="table">
                        <thead><tr><th>ID</th><th>ФИО</th><th>Email</th><th>Телефон</th><th>Дата рождения</th><th>Табельный номер</th><th>Должность</th><th>Отдел</th><th>Роль</th><th>Действия</th></tr></thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->prepare("
                                SELECT u.id, u.email, u.role,
                                       up.first_name, up.last_name, up.middle_name,
                                       up.phone, up.birth_date, up.employee_id,
                                       up.position, up.department
                                FROM users u
                                LEFT JOIN user_profiles up ON u.id = up.user_id
                                WHERE u.role IN ('executor', 'dispatcher', 'moderator')
                                ORDER BY up.last_name, up.first_name
                            ");
                            $stmt->execute();
                            $employees = $stmt->fetchAll();
                            if (empty($employees)) echo '<tr><td colspan="10" class="text-center">Сотрудники не найдены</td></tr>';
                            else foreach ($employees as $emp) {
                                $fullName = trim(($emp['last_name']??'') . ' ' . ($emp['first_name']??'') . ' ' . ($emp['middle_name']??''));
                                $fullName = $fullName ?: '—';
                                $birthDate = !empty($emp['birth_date']) ? date('d.m.Y', strtotime($emp['birth_date'])) : '—';
                                ?>
                                <tr>
                                    <td><?= $emp['id'] ?></td>
                                    <td><?= htmlspecialchars($fullName) ?></td>
                                    <td><?= htmlspecialchars($emp['email']) ?></td>
                                    <td><?= htmlspecialchars($emp['phone']??'—') ?></td>
                                    <td><?= $birthDate ?></td>
                                    <td><?= htmlspecialchars($emp['employee_id']??'—') ?></td>
                                    <td><?= htmlspecialchars($emp['position']??'—') ?></td>
                                    <td><?= htmlspecialchars($emp['department']??'—') ?></td>
                                    <td><span class="status-badge"><?= htmlspecialchars($emp['role']) ?></span></td>
                                    <td class="request-actions">
                                        <button class="btn-edit" onclick="editEmployee(<?= $emp['id'] ?>)" title="Редактировать">✏️</button>
                                        <a href="admin.php?section=employees&delete_employee=<?= $emp['id'] ?>" class="btn-delete" onclick="return confirm('Удалить сотрудника?')" title="Удалить">🗑️</a>
                                    </td>
                                </tr>
                            <?php } ?>
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
            <h3>Редактирование заявки #<span id="editRequestId"></span></h3>
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
// Все сотрудники для назначения (получено из PHP)
const allEmployees = <?= json_encode($employeesWithLoad) ?>;

// Функции для сотрудников
function showEmployeeForm() {
    document.getElementById('employeeForm').style.display = 'block';
    document.getElementById('employeeFormTitle').innerText = 'Добавление сотрудника';
    document.getElementById('emp_id').value = 0;
    document.getElementById('emp_email').value = '';
    document.getElementById('emp_password').value = '';
    document.getElementById('emp_last_name').value = '';
    document.getElementById('emp_first_name').value = '';
    document.getElementById('emp_middle_name').value = '';
    document.getElementById('emp_phone').value = '';
    document.getElementById('emp_birth_date').value = '';
    document.getElementById('emp_department_id').value = '';
    let posSelect = document.getElementById('emp_position_id');
    posSelect.innerHTML = '<option value="">Сначала выберите отдел</option>';
    document.getElementById('emp_role').value = 'executor';
}

function hideEmployeeForm() {
    document.getElementById('employeeForm').style.display = 'none';
}

function editEmployee(id) {
    fetch('includes/get_employee.php?id=' + id)
        .then(response => {
            if (!response.ok) throw new Error('Ошибка сети: ' + response.status);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                let emp = data.employee;
                document.getElementById('employeeForm').style.display = 'block';
                document.getElementById('employeeFormTitle').innerText = 'Редактирование сотрудника';
                document.getElementById('emp_id').value = emp.id;
                document.getElementById('emp_email').value = emp.email;
                document.getElementById('emp_password').value = '';
                document.getElementById('emp_last_name').value = emp.last_name || '';
                document.getElementById('emp_first_name').value = emp.first_name || '';
                document.getElementById('emp_middle_name').value = emp.middle_name || '';
                document.getElementById('emp_phone').value = emp.phone || '';
                document.getElementById('emp_birth_date').value = emp.birth_date || '';
                document.getElementById('emp_department_id').value = emp.department_id || '';
                if (emp.department_id) {
                    fetch('includes/get_positions_by_department.php?department_id=' + emp.department_id)
                        .then(res => res.json())
                        .then(positions => {
                            let posSelect = document.getElementById('emp_position_id');
                            posSelect.innerHTML = '<option value="">Выберите должность</option>';
                            positions.forEach(pos => {
                                let option = document.createElement('option');
                                option.value = pos.id;
                                option.textContent = pos.name;
                                if (pos.id == emp.position_id) option.selected = true;
                                posSelect.appendChild(option);
                            });
                        })
                        .catch(err => console.error('Ошибка загрузки должностей:', err));
                } else {
                    document.getElementById('emp_position_id').innerHTML = '<option value="">Сначала выберите отдел</option>';
                }
                document.getElementById('emp_role').value = emp.role;
            } else {
                alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
            }
        })
        .catch(err => {
            console.error('Ошибка:', err);
            alert('Ошибка при загрузке данных сотрудника: ' + err.message);
        });
}

// При загрузке страницы навешиваем обработчик на выбор отдела
document.addEventListener('DOMContentLoaded', function() {
    const deptSelect = document.getElementById('emp_department_id');
    if (deptSelect) {
        deptSelect.addEventListener('change', function() {
            let deptId = this.value;
            let posSelect = document.getElementById('emp_position_id');
            if (!deptId) {
                posSelect.innerHTML = '<option value="">Сначала выберите отдел</option>';
                return;
            }
            posSelect.innerHTML = '<option value="">Загрузка...</option>';
            fetch('includes/get_positions_by_department.php?department_id=' + deptId)
                .then(response => response.json())
                .then(positions => {
                    posSelect.innerHTML = '<option value="">Выберите должность</option>';
                    positions.forEach(pos => {
                        let option = document.createElement('option');
                        option.value = pos.id;
                        option.textContent = pos.name;
                        posSelect.appendChild(option);
                    });
                })
                .catch(err => {
                    posSelect.innerHTML = '<option value="">Ошибка загрузки</option>';
                    console.error(err);
                });
        });
    }

    // Обработчики для кнопок редактирования новостей
    document.querySelectorAll('.news-card .btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const title = this.dataset.title;
            const description = this.dataset.description;
            editNews(id, title, description);
        });
    });

    // Обработчики для кнопок редактирования услуг
    document.querySelectorAll('.price-card .btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const description = this.dataset.description;
            const price = this.dataset.price;
            const unit = this.dataset.unit;
            editPrice(id, name, description, price, unit);
        });
    });

    // Закрытие модальных окон по клику на фон
    const modals = document.querySelectorAll('.modal-overlay');
    modals.forEach(modal => {
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
});

// --- НОВЫЕ ФУНКЦИИ ДЛЯ НОВОСТЕЙ, ЦЕН И ЗАЯВОК ---

// Новости
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

// Цены
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
            document.getElementById('editSubject').value = req.subject;
            document.getElementById('editStatus').value = req.status;
            document.getElementById('editAdminResponse').value = req.admin_response || '';
            
            // Загружаем список сотрудников
            loadAssignSelect(req.assigned_to);
            
            // Показываем модальное окно
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

// Заявки – удаление (одиночное)
function deleteSingleRequest(id) {
    if (!confirm('Удалить заявку #' + id + '?')) return;
    fetch('includes/bulk_delete_requests.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids: [id] })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Заявка удалена');
            location.reload();
        } else {
            alert('Ошибка: ' + data.error);
        }
    })
    .catch(err => {
        alert('Ошибка: ' + err.message);
    });
}

// Массовые операции
function toggleSelectAll(source) {
    const checkboxes = document.querySelectorAll('.request-checkbox');
    checkboxes.forEach(cb => cb.checked = source.checked);
    updateBulkDeleteCount();
}

function updateBulkDeleteCount() {
    const checked = document.querySelectorAll('.request-checkbox:checked');
    const btn = document.getElementById('bulkDeleteBtn');
    if (checked.length > 0) {
        btn.style.display = 'inline-block';
        btn.innerText = '🗑️ Удалить выбранные (' + checked.length + ')';
    } else {
        btn.style.display = 'none';
    }
}

function showBulkDeleteModal() {
    const checked = document.querySelectorAll('.request-checkbox:checked');
    if (checked.length === 0) return;
    if (!confirm('Удалить ' + checked.length + ' заявок?')) return;
    const ids = Array.from(checked).map(cb => parseInt(cb.value));
    fetch('includes/bulk_delete_requests.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids: ids })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Удалено заявок: ' + data.deleted);
            location.reload();
        } else {
            alert('Ошибка: ' + data.error);
        }
    })
    .catch(err => {
        alert('Ошибка: ' + err.message);
    });
}

// Вспомогательная функция для экранирования HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// (Опционально) Инициализация кнопок назначения (если используется)
function initAssignButtons() {
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
}
// Вызов инициализации, если элементы уже есть на странице
if (document.querySelector('.assign-btn')) {
    initAssignButtons();
}
</script>
</body>
</html>