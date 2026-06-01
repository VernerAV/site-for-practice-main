<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/check_auth.php';
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
            $stmt = $pdo->prepare("INSERT INTO users (email, password, role, status) VALUES (?, ?, ?, 'active')");
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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        /* дополнительные стили для фильтров и уведомлений */
        .filter-bar { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
        .filter-group { display: inline-flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 12px; font-weight: 600; color: #6c757d; }
        .request-row.unread { background: #fff3e0; }
        .badge-new { background: #ff4757; color: white; border-radius: 20px; padding: 2px 8px; font-size: 11px; margin-left: 8px; }
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
                                <button class="btn-edit" onclick="editNews(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['title'])) ?>', '<?= addslashes(htmlspecialchars($item['description'])) ?>')" title="Редактировать">✏️</button>
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
                                <button class="btn-edit" onclick="editPrice(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['service_name'])) ?>', '<?= addslashes(htmlspecialchars($item['description'])) ?>', <?= (float)$item['price'] ?>, '<?= addslashes(htmlspecialchars($item['unit'])) ?>')" title="Редактировать">✏️</button>
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

            <!-- ЗАЯВКИ (с фильтрами, назначением, массовым удалением) -->
            <div id="requests" class="section <?php echo $active_section=='requests'?'active':''; ?>">
                <h2>Управление заявками <?php if($new_requests_count) echo "<span class='badge-new'>$new_requests_count новых</span>"; ?></h2>

                <!-- Фильтры -->
                <div class="filter-bar">
                    <form method="GET" id="filterForm">
                        <input type="hidden" name="section" value="requests">
                        <div class="filter-group">
                            <label>Статус:</label>
                            <select name="status_filter" onchange="this.form.submit()">
                                <option value="all" <?= ($_GET['status_filter']??'all')=='all'?'selected':'' ?>>Все</option>
                                <option value="новая" <?= ($_GET['status_filter']??'')=='новая'?'selected':'' ?>>Новая</option>
                                <option value="в работе" <?= ($_GET['status_filter']??'')=='в работе'?'selected':'' ?>>В работе</option>
                                <option value="выполнена" <?= ($_GET['status_filter']??'')=='выполнена'?'selected':'' ?>>Выполнена</option>
                            </select>
                        </div>
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
                            <tr><th width="40"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th><th>ID</th><th>Имя</th><th>Email</th><th>Телефон</th><th>Тема</th><th>Сообщение</th><th>Статус</th><th>Назначен</th><th>Дата</th><th>Действия</th></tr>
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
                                        <td><div class="message-preview"><?= htmlspecialchars(mb_substr($req['message'],0,100)) ?>...</div></td>
                                        <td><span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($req['status']) ?></span></td>
                                        <td>
                                            <form class="assign-form" data-id="<?= $req['id'] ?>">
                                                <select name="employee_id" class="assign-select">
                                                    <option value="">Не назначен</option>
                                                    <?php
                                                    $empStmt = $pdo->query("SELECT u.id, CONCAT(up.last_name, ' ', up.first_name) as name FROM users u LEFT JOIN user_profiles up ON u.id=up.user_id WHERE u.role IN ('executor','dispatcher','moderator') ORDER BY up.last_name");
                                                    foreach($empStmt->fetchAll() as $emp) {
                                                        $selected = ($req['assigned_to'] == $emp['id']) ? 'selected' : '';
                                                        echo "<option value='{$emp['id']}' $selected>" . htmlspecialchars($emp['name']) . "</option>";
                                                    }
                                                    ?>
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

            <!-- СОТРУДНИКИ (новая вкладка) -->
            <div id="employees" class="section <?php echo $active_section == 'employees' ? 'active' : ''; ?>">
                <h2>Управление сотрудниками</h2>
                <button class="btn btn-primary" onclick="showEmployeeForm()">➕ Добавить сотрудника</button>

                <div id="employeeForm" class="form-card" style="display: none; margin-top: 20px; background: #f8f9fa; padding: 20px; border-radius: 8px;">
                    <h3 id="employeeFormTitle">Добавление сотрудника</h3>
                    <form id="employeeFormElement" method="POST" action="includes/save_employee.php">
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
                                        <a href="includes/delete_employee.php?id=<?= $emp['id'] ?>" class="btn-delete" onclick="return confirm('Удалить сотрудника?')" title="Удалить">🗑️</a>
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

<!-- Модальные окна -->
<div class="modal-overlay" id="requestModal"><div class="modal"><div class="modal-header"><h3 id="modalTitle">Заявка #</h3><button class="modal-close" onclick="closeModal()">×</button></div><div class="modal-body" id="modalContent"></div></div></div>

<script>
// Функции для новостей и цен
function showNewsForm() { document.getElementById('news-form').style.display='block'; document.getElementById('news_id').value=''; document.getElementById('news_title').value=''; document.getElementById('news_description').value=''; }
function hideNewsForm() { document.getElementById('news-form').style.display='none'; }
window.editNews = function(id,title,desc){ document.getElementById('news-form').style.display='block'; document.getElementById('news_id').value=id; document.getElementById('news_title').value=title; document.getElementById('news_description').value=desc; };
function showPriceForm() { document.getElementById('price-form').style.display='block'; document.getElementById('price_id').value=''; document.getElementById('service_name').value=''; document.getElementById('price_description').value=''; document.getElementById('service_price').value=''; document.getElementById('service_unit').value=''; }
function hidePriceForm() { document.getElementById('price-form').style.display='none'; }
window.editPrice = function(id,name,desc,price,unit){ document.getElementById('price-form').style.display='block'; document.getElementById('price_id').value=id; document.getElementById('service_name').value=name; document.getElementById('price_description').value=desc; document.getElementById('service_price').value=price; document.getElementById('service_unit').value=unit; };

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
function hideEmployeeForm() { document.getElementById('employeeForm').style.display = 'none'; }
function editEmployee(id) {
    fetch('includes/get_employee.php?id=' + id)
        .then(response => response.json())
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
                        });
                } else {
                    document.getElementById('emp_position_id').innerHTML = '<option value="">Сначала выберите отдел</option>';
                }
                document.getElementById('emp_role').value = emp.role;
            } else alert('Ошибка: ' + data.error);
        }).catch(err => alert('Ошибка: ' + err.message));
}
document.addEventListener('DOMContentLoaded', function() {
    const deptSelect = document.getElementById('emp_department_id');
    if (deptSelect) {
        deptSelect.addEventListener('change', function() {
            let deptId = this.value;
            let posSelect = document.getElementById('emp_position_id');
            if (!deptId) { posSelect.innerHTML = '<option value="">Сначала выберите отдел</option>'; return; }
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
                }).catch(err => { posSelect.innerHTML = '<option value="">Ошибка загрузки</option>'; console.error(err); });
        });
    }
});

// Функции для заявок
function initAssignButtons() {
    document.querySelectorAll('.assign-btn').forEach(btn => {
        btn.removeEventListener('click', handleAssign);
        btn.addEventListener('click', handleAssign);
    });
}
function handleAssign(e) {
    const btn = e.currentTarget;
    const form = btn.closest('.assign-form');
    const requestId = form.dataset.id;
    const employeeId = form.querySelector('.assign-select').value;
    if (!employeeId) {
        if (confirm('Снять назначение?')) {
            fetch('includes/update_message_assign.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=assign&id=${requestId}&employee_id=0` })
                .then(r=>r.json()).then(data=>{ if(data.success) location.reload(); else alert(data.error); });
        }
        return;
    }
    fetch('includes/update_message_assign.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=assign&id=${requestId}&employee_id=${employeeId}` })
        .then(r=>r.json()).then(data=>{ if(data.success) location.reload(); else alert(data.error); });
}
function showRequestDetails(id) {
    fetch('includes/get_message.php?id='+id).then(r=>r.json()).then(data=>{
        if(data.success){
            let msg = data.message;
            document.getElementById('modalTitle').innerHTML = 'Заявка #'+id;
            let html = `<div><strong>Имя:</strong> ${escapeHtml(msg.user_name)}</div>
                        <div><strong>Email:</strong> ${escapeHtml(msg.user_email)}</div>
                        <div><strong>Телефон:</strong> ${escapeHtml(msg.phone||'—')}</div>
                        <div><strong>Тема:</strong> ${escapeHtml(msg.subject)}</div>
                        <div><strong>Статус:</strong> <span class="status-badge">${escapeHtml(msg.status)}</span></div>
                        <div><strong>Назначен:</strong> ${msg.assigned_name || 'Не назначен'}</div>
                        <div><strong>Дата:</strong> ${formatDateTime(msg.created_at)}</div>
                        <hr><div><strong>Сообщение:</strong><br><pre>${escapeHtml(msg.message)}</pre></div>
                        ${msg.admin_response ? `<hr><div><strong>Ответ администратора:</strong><br><pre>${escapeHtml(msg.admin_response)}</pre></div>` : ''}
                        <div style="margin-top:20px;">
                            <select id="statusSelect">
                                <option value="новая" ${msg.status==='новая'?'selected':''}>Новая</option>
                                <option value="в работе" ${msg.status==='в работе'?'selected':''}>В работе</option>
                                <option value="выполнена" ${msg.status==='выполнена'?'selected':''}>Выполнена</option>
                            </select>
                            <button class="btn btn-success" onclick="updateRequestStatus(${msg.id})">Сохранить статус</button>
                            <button class="btn btn-secondary" onclick="closeModal()">Закрыть</button>
                        </div>`;
            document.getElementById('modalContent').innerHTML = html;
            document.getElementById('requestModal').style.display = 'flex';
        } else alert('Ошибка');
    });
}
function updateRequestStatus(id) {
    let newStatus = document.getElementById('statusSelect').value;
    fetch('includes/update_message_assign.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=update_status&id=${id}&status=${encodeURIComponent(newStatus)}` })
        .then(r=>r.json()).then(data=>{ if(data.success) location.reload(); else alert('Ошибка: '+data.error); });
}
function deleteSingleRequest(id) { if(confirm('Удалить заявку #'+id+'?')) window.location.href='includes/delete_request.php?id='+id; }
function editRequest(id) { window.location.href = 'edit_request.php?id='+id; }
let selectedRequests = [];
function updateBulkDeleteCount() {
    let cbs = document.querySelectorAll('.request-checkbox:checked');
    let count = cbs.length;
    let btn = document.getElementById('bulkDeleteBtn');
    selectedRequests = Array.from(cbs).map(cb=>cb.value);
    if(count>0) { btn.style.display='inline-block'; btn.textContent = `🗑️ Удалить выбранные (${count})`; }
    else btn.style.display='none';
}
function toggleSelectAll(cb) { document.querySelectorAll('.request-checkbox').forEach(c=>c.checked=cb.checked); updateBulkDeleteCount(); }
function showBulkDeleteModal() {
    if(selectedRequests.length===0) return;
    if(confirm(`Удалить ${selectedRequests.length} заявок?`)) {
        fetch('includes/bulk_delete_requests.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ids:selectedRequests, section:'requests'}) })
            .then(r=>r.json()).then(data=>{ if(data.success) location.reload(); else alert('Ошибка: '+data.error); });
    }
}
function closeModal() { document.getElementById('requestModal').style.display='none'; }
function escapeHtml(str){ if(!str) return ''; return str.replace(/[&<>]/g,function(m){if(m==='&')return '&amp;';if(m==='<')return '&lt;';if(m==='>')return '&gt;';return m;}); }
function formatDateTime(dt){ return new Date(dt).toLocaleString('ru-RU'); }
initAssignButtons();
</script>
</body>
</html>