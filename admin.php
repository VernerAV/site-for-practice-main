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

// Статистика для бейджей
$unread_messages_count = 0;
$unanswered_messages_count = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM message WHERE is_read = 0");
    $unread_messages_count = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM message WHERE admin_response IS NULL");
    $unanswered_messages_count = $stmt->fetchColumn();
} catch (PDOException $e) {}

// Список сотрудников для выпадающих списков (для назначения заявок)
$employees = [];
try {
    $stmt = $pdo->prepare("SELECT u.id, u.email, up.first_name, up.last_name, up.position 
                           FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id 
                           WHERE u.role IN ('executor','dispatcher','moderator') 
                           ORDER BY up.last_name, up.first_name");
    $stmt->execute();
    $employees = $stmt->fetchAll();
} catch (PDOException $e) {}

// Обработка добавления/редактирования сотрудника (для вкладки employees)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['employee_submit'])) {
    $emp_id = (int)($_POST['emp_id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $last_name = trim($_POST['last_name'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
    $position = trim($_POST['position'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $role = $_POST['role'] ?? 'executor';

    try {
        if ($emp_id == 0) {
            if (empty($password)) throw new Exception('Пароль обязателен');
            if (empty($email)) throw new Exception('Email обязателен');
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$email, $hashed, $role]);
            $new_id = $pdo->lastInsertId();
            $emp_number = 'EMP' . str_pad($new_id, 5, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, last_name, first_name, middle_name, phone, birth_date, position, department, employee_id) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$new_id, $last_name, $first_name, $middle_name, $phone, $birth_date, $position, $department, $emp_number]);
            $pdo->commit();
            $_SESSION['admin_message'] = "Сотрудник добавлен. Табельный номер: $emp_number";
            $_SESSION['admin_message_type'] = 'success';
        } else {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE users SET email = ?, role = ? WHERE id = ?");
            $stmt->execute([$email, $role, $emp_id]);
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $emp_id]);
            }
            $stmt = $pdo->prepare("UPDATE user_profiles SET last_name = ?, first_name = ?, middle_name = ?, phone = ?, birth_date = ?, position = ?, department = ? WHERE user_id = ?");
            $stmt->execute([$last_name, $first_name, $middle_name, $phone, $birth_date, $position, $department, $emp_id]);
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
  
</head>
<body>
<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<header>
    <div style="max-width:100%;">
        <div class="admin-nav">
            <h1 style="margin:0">Панель администратора</h1>
            <div>
                <span><?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
                <a href="index.php">Главная</a>
                <a href="includes/logout.php">Выйти</a>
            </div>
        </div>
    </div>
</header>

<div class="admin-container">
    <div class="admin-content">
        <aside class="admin-sidebar">
            <ul>
                <li><a href="?section=news" class="<?php echo $active_section=='news'?'active':''; ?>">Новости</a></li>
                <li><a href="?section=prices" class="<?php echo $active_section=='prices'?'active':''; ?>">Цены</a></li>
                <li><a href="?section=requests" class="<?php echo $active_section=='requests'?'active':''; ?>">Заявки</a></li>
                <li><a href="?section=messages" class="<?php echo $active_section=='messages'?'active':''; ?>">Сообщения <?php if($unread_messages_count) echo "<span class='sidebar-badge'>$unread_messages_count</span>"; ?></a></li>
                <li><a href="?section=employees" class="<?php echo $active_section=='employees'?'active':''; ?>">Сотрудники</a></li>
            </ul>
        </aside>

        <main class="admin-main">
            <!-- ===================== НОВОСТИ ===================== -->
            <!-- НОВОСТИ (карточки, красивая форма) -->
<div id="news" class="section <?php echo $active_section=='news'?'active':''; ?>">
    <h2>📰 Управление новостями</h2>
    <button class="btn btn-primary" onclick="showNewsForm()">➕ Добавить новость</button>

    <!-- Форма добавления/редактирования -->
    <div id="news-form" class="form-card" style="display:none;">
        <h3 id="newsFormTitle">Добавление новости</h3>
        <form action="includes/save_news.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="news_id" id="news_id">
            <div class="form-group">
                <label>Заголовок *</label>
                <input type="text" name="title" id="news_title" required placeholder="Введите заголовок">
            </div>
            <div class="form-group">
                <label>Описание *</label>
                <textarea name="description" id="news_description" rows="5" required placeholder="Подробное описание новости..."></textarea>
            </div>
            <div class="form-group">
                <label>Изображение</label>
                <input type="file" name="image" accept="image/jpeg,image/png,image/gif">
                <small class="form-text text-muted">Рекомендуемый размер: 800x400px</small>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-success">💾 Сохранить</button>
                <button type="button" class="btn btn-secondary" onclick="hideNewsForm()">Отмена</button>
            </div>
        </form>
    </div>

    <!-- Список новостей в виде карточек -->
    <div class="news-grid" id="newsGrid">
        <?php
        try {
            $stmt = $pdo->query("SELECT * FROM news ORDER BY created_at DESC");
            $newsList = $stmt->fetchAll();
            if (empty($newsList)) {
                echo '<div class="empty-state"><div class="empty-icon">📭</div><h3>Новостей пока нет</h3><p>Нажмите «Добавить новость», чтобы создать первую</p></div>';
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

<!-- ===================== ЦЕНЫ ===================== -->
<div id="prices" class="section <?php echo $active_section=='prices'?'active':''; ?>">
    <h2>💰 Управление ценами на услуги</h2>
    <button class="btn btn-primary" onclick="showPriceForm()">➕ Добавить услугу</button>

    <!-- Форма добавления/редактирования -->
    <div id="price-form" class="form-card" style="display:none;">
        <h3 id="priceFormTitle">Добавление услуги</h3>
        <form action="includes/save_price.php" method="POST">
            <input type="hidden" name="price_id" id="price_id">
            <div class="form-group">
                <label>Название услуги *</label>
                <input type="text" name="service_name" id="service_name" required placeholder="Например: Ремонт сантехники">
            </div>
            <div class="form-group">
                <label>Описание</label>
                <textarea name="description" id="price_description" rows="3" placeholder="Что входит в услугу, особенности..."></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Цена (руб.) *</label>
                    <input type="number" name="price" id="service_price" step="0.01" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Единица измерения</label>
                    <input type="text" name="unit" id="service_unit" placeholder="шт., м², час, услуга">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-success">💾 Сохранить</button>
                <button type="button" class="btn btn-secondary" onclick="hidePriceForm()">Отмена</button>
            </div>
        </form>
    </div>

    <!-- Список услуг в виде карточек -->
    <div class="prices-grid">
        <?php
        try {
            $stmt = $pdo->query("SELECT * FROM services ORDER BY service_name");
            $pricesList = $stmt->fetchAll();
            if (empty($pricesList)) {
                echo '<div class="empty-state"><div class="empty-icon">💸</div><h3>Услуг пока нет</h3><p>Нажмите «Добавить услугу», чтобы создать первую</p></div>';
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

<!-- ===================== ЗАЯВКИ ===================== -->
            <div id="requests" class="section <?php echo $active_section=='requests'?'active':''; ?>">
                <h2>Управление заявками</h2>
                <!-- Фильтры -->
                <div class="filter-bar">
                    <form method="GET" id="filterForm">
                        <input type="hidden" name="section" value="requests">
                        <div class="filter-group">
                            <label>Статус:</label>
                            <select name="status_filter" onchange="this.form.submit()">
                                <option value="all" <?= ($_GET['status_filter'] ?? 'all') == 'all' ? 'selected' : '' ?>>Все</option>
                                <option value="новая" <?= ($_GET['status_filter'] ?? '') == 'новая' ? 'selected' : '' ?>>Новая</option>
                                <option value="в работе" <?= ($_GET['status_filter'] ?? '') == 'в работе' ? 'selected' : '' ?>>В работе</option>
                                <option value="выполнена" <?= ($_GET['status_filter'] ?? '') == 'выполнена' ? 'selected' : '' ?>>Выполнена</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Дата от:</label>
                            <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>" onchange="this.form.submit()">
                        </div>
                        <div class="filter-group">
                            <label>Дата до:</label>
                            <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>" onchange="this.form.submit()">
                        </div>
                        <div class="filter-group">
                            <label>Назначение:</label>
                            <select name="assign_filter" onchange="this.form.submit()">
                                <option value="all" <?= ($_GET['assign_filter'] ?? 'all') == 'all' ? 'selected' : '' ?>>Все</option>
                                <option value="assigned" <?= ($_GET['assign_filter'] ?? '') == 'assigned' ? 'selected' : '' ?>>Назначенные</option>
                                <option value="unassigned" <?= ($_GET['assign_filter'] ?? '') == 'unassigned' ? 'selected' : '' ?>>Не назначенные</option>
                            </select>
                        </div>
                        <a href="?section=requests" class="btn btn-secondary btn-sm">Сбросить</a>
                    </form>
                </div>

                <div class="bulk-actions mb-20">
                    <button class="btn btn-danger" onclick="showBulkDeleteModal()" id="bulkDeleteBtn" style="display: none;">
                        🗑️ Удалить выбранные (<span id="selectedCount">0</span>)
                    </button>
                </div>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th width="40"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                                <th>ID</th>
                                <th>Имя</th>
                                <th>Email</th>
                                <th>Телефон</th>
                                <th>Тема</th>
                                <th>Сообщение</th>
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
                            $status_filter = $_GET['status_filter'] ?? 'all';
                            if ($status_filter !== 'all') {
                                $sql .= " AND m.status = ?";
                                $params[] = $status_filter;
                            }
                            if (!empty($_GET['date_from'])) {
                                $sql .= " AND DATE(m.created_at) >= ?";
                                $params[] = $_GET['date_from'];
                            }
                            if (!empty($_GET['date_to'])) {
                                $sql .= " AND DATE(m.created_at) <= ?";
                                $params[] = $_GET['date_to'];
                            }
                            $assign_filter = $_GET['assign_filter'] ?? 'all';
                            if ($assign_filter === 'assigned') {
                                $sql .= " AND m.assigned_to IS NOT NULL AND m.assigned_to != 0";
                            } elseif ($assign_filter === 'unassigned') {
                                $sql .= " AND (m.assigned_to IS NULL OR m.assigned_to = 0)";
                            }
                            $sql .= " ORDER BY m.created_at DESC";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute($params);
                            $messages = $stmt->fetchAll();
                            if(empty($messages)): ?>
                                <tr><td colspan="11" class="text-center">Заявок не найдено</td></tr>
                            <?php else: foreach($messages as $msg): ?>
                                <tr class="request-row" id="row-<?= $msg['id'] ?>">
                                    <td><input type="checkbox" class="request-checkbox" value="<?= $msg['id'] ?>" onchange="updateBulkDeleteCount()"></td>
                                    <td>#<?= $msg['id'] ?></td>
                                    <td><?= htmlspecialchars($msg['user_name']) ?></td>
                                    <td><?= htmlspecialchars($msg['user_email']) ?></td>
                                    <td><?= htmlspecialchars($msg['phone'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($msg['subject']) ?></td>
                                    <td><div class="message-preview"><?= htmlspecialchars(mb_substr($msg['message'], 0, 100)) ?>...</div></td>
                                    <td><span class="status-badge"><?= htmlspecialchars($msg['status']) ?></span></td>
                                    <td>
                                        <form class="assign-form" data-id="<?= $msg['id'] ?>">
                                            <select name="employee_id" class="assign-select">
                                                <option value="">Не назначен</option>
                                                <?php foreach($employees as $emp): ?>
                                                    <option value="<?= $emp['id'] ?>" <?= ($msg['assigned_to'] == $emp['id']) ? 'selected' : '' ?>><?= htmlspecialchars($emp['last_name'].' '.$emp['first_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="button" class="btn btn-sm btn-success assign-btn" data-id="<?= $msg['id'] ?>">Назначить</button>
                                        </form>
                                    </td>
                                    <td><?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?></td>
                                    <td class="request-actions">
                                        <button class="btn-view" onclick="showRequestDetails(<?= $msg['id'] ?>)" title="Просмотр">👁️</button>
                                        <button class="btn-delete" onclick="deleteSingleRequest(<?= $msg['id'] ?>)" title="Удалить">🗑️</button>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ===================== СООБЩЕНИЯ ПОЛЬЗОВАТЕЛЕЙ (с фильтрами, ответами) ===================== -->
            <div id="messages" class="section <?php echo $active_section=='messages'?'active':''; ?>">
                <h2>Сообщения пользователей <?php if($unread_messages_count) echo "<span class='sidebar-badge'>$unread_messages_count новых</span>"; ?></h2>
                <?php
                $search = $_GET['search'] ?? '';
                $status_filter = $_GET['status'] ?? 'all';
                $sql = "SELECT * FROM message WHERE 1=1";
                $params = [];
                if (!empty($search)) {
                    $sql .= " AND (user_name LIKE ? OR user_email LIKE ? OR subject LIKE ? OR message LIKE ?)";
                    $search_term = "%$search%";
                    $params = array_fill(0, 4, $search_term);
                }
                if ($status_filter === 'unread') $sql .= " AND is_read = 0";
                elseif ($status_filter === 'unanswered') $sql .= " AND admin_response IS NULL";
                $sql .= " ORDER BY created_at DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $msgs = $stmt->fetchAll();
                ?>
                <div class="filters mb-20">
                    <form method="GET" class="filter-form">
                        <input type="hidden" name="section" value="messages">
                        <div class="search-box">
                            <input type="text" name="search" placeholder="Поиск..." value="<?= htmlspecialchars($search) ?>">
                            <button type="submit" class="btn btn-info">🔍</button>
                        </div>
                        <select name="status">
                            <option value="all" <?= $status_filter=='all'?'selected':'' ?>>Все</option>
                            <option value="unread" <?= $status_filter=='unread'?'selected':'' ?>>Непрочитанные</option>
                            <option value="unanswered" <?= $status_filter=='unanswered'?'selected':'' ?>>Без ответа</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Фильтровать</button>
                        <a href="?section=messages" class="btn btn-secondary">Сбросить</a>
                    </form>
                </div>
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>ID</th><th>Имя</th><th>Email</th><th>Тема</th><th>Дата</th><th>Статус</th><th>Действия</th></tr></thead>
                        <tbody>
                        <?php if(empty($msgs)): ?>
                            <tr><td colspan="7" class="text-center">Сообщений нет</td></tr>
                        <?php else: foreach($msgs as $m): ?>
                            <tr class="message-row <?= !$m['is_read']?'unread':'' ?>">
                                <td>#<?= $m['id'] ?></td>
                                <td><?= htmlspecialchars($m['user_name']) ?></td>
                                <td><?= htmlspecialchars($m['user_email']) ?></td>
                                <td><?= htmlspecialchars($m['subject']) ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($m['created_at'])) ?></td>
                                <td><?= $m['admin_response'] ? '✅ Ответ дан' : ($m['is_read'] ? '👁️ Прочитано' : '🆕 Новое') ?></td>
                                <td class="message-actions">
                                    <button class="btn btn-sm btn-info" onclick="viewMessage(<?= $m['id'] ?>)" title="Просмотр">👁️</button>
                                    <?php if(!$m['admin_response']): ?>
                                        <button class="btn btn-sm btn-success" onclick="replyToMessage(<?= $m['id'] ?>)" title="Ответить">✉️</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ===================== СОТРУДНИКИ (только сотрудники) ===================== -->
            <div id="employees" class="section <?php echo $active_section=='employees'?'active':''; ?>">
                <h2>Управление сотрудниками</h2>
                <button class="btn btn-primary" onclick="showEmployeeForm()">Добавить сотрудника</button>

                <div id="employeeForm" class="employee-form">
                    <form method="POST" action="">
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
                            <div class="form-group"><label>Должность</label><input type="text" name="position" id="emp_position"></div>
                            <div class="form-group"><label>Отдел</label><input type="text" name="department" id="emp_department"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Роль</label>
                                <select name="role" id="emp_role">
                                    <option value="executor">Исполнитель</option>
                                    <option value="dispatcher">Диспетчер</option>
                                    <option value="moderator">Модератор</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">Сохранить</button>
                        <button type="button" class="btn btn-secondary" onclick="hideEmployeeForm()">Отмена</button>
                    </form>
                </div>

                <div class="table-container mt-20">
                    <table class="table">
                        <thead>
                            <tr><th>ID</th><th>ФИО</th><th>Email</th><th>Телефон</th><th>Дата рождения</th><th>Табельный номер</th><th>Должность</th><th>Отдел</th><th>Роль</th><th>Действия</th></tr>
                        </thead>
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
                            $employeesList = $stmt->fetchAll();
                            foreach($employeesList as $emp):
                                $fullName = trim(($emp['last_name']??'').' '.($emp['first_name']??'').' '.($emp['middle_name']??''));
                                if(empty($fullName)) $fullName = '—';
                                $birthDate = !empty($emp['birth_date']) ? date('d.m.Y', strtotime($emp['birth_date'])) : '—';
                            ?>
                                <tr>
                                    <td>#<?= (int)$emp['id'] ?></td>
                                    <td><?= htmlspecialchars($fullName) ?></td>
                                    <td><?= htmlspecialchars($emp['email']) ?></td>
                                    <td><?= htmlspecialchars($emp['phone'] ?? '—') ?></td>
                                    <td><?= $birthDate ?></td>
                                    <td><?= htmlspecialchars($emp['employee_id'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($emp['position'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($emp['department'] ?? '—') ?></td>
                                    <td><span class="status-badge"><?= htmlspecialchars($emp['role']) ?></span></td>
                                    <td class="request-actions">
                                        <button class="btn-edit" onclick="editEmployee(<?= (int)$emp['id'] ?>)" title="Редактировать">✏️</button>
                                        <a href="?delete_employee=<?= (int)$emp['id'] ?>&section=employees" class="btn-delete" onclick="return confirm('Удалить сотрудника?')" title="Удалить">🗑️</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Модальные окна -->
<div class="modal-overlay" id="requestModal"><div class="modal"><div class="modal-header"><h3 id="modalTitle">Заявка #</h3><button class="modal-close" onclick="closeModal()">×</button></div><div class="modal-body" id="modalContent"></div></div></div>
<div class="modal-overlay" id="messageModal"><div class="modal"><div class="modal-header"><h3 id="messageModalTitle">Сообщение #</h3><button class="modal-close" onclick="closeMessageModal()">×</button></div><div class="modal-body" id="messageModalContent"></div></div></div>

<script>
    // Функции для новостей и цен
   function showNewsForm() {
    document.getElementById('news-form').style.display = 'block';
    document.getElementById('newsFormTitle').innerText = 'Добавление новости';
    document.getElementById('news_id').value = '';
    document.getElementById('news_title').value = '';
    document.getElementById('news_description').value = '';
}
window.editNews = function(id, title, description) {
    document.getElementById('news-form').style.display = 'block';
    document.getElementById('newsFormTitle').innerText = 'Редактирование новости';
    document.getElementById('news_id').value = id;
    document.getElementById('news_title').value = title;
    document.getElementById('news_description').value = description;
};
function showPriceForm() {
    document.getElementById('price-form').style.display = 'block';
    document.getElementById('priceFormTitle').innerText = 'Добавление услуги';
    document.getElementById('price_id').value = '';
    document.getElementById('service_name').value = '';
    document.getElementById('price_description').value = '';
    document.getElementById('service_price').value = '';
    document.getElementById('service_unit').value = '';
}
window.editPrice = function(id, name, description, price, unit) {
    document.getElementById('price-form').style.display = 'block';
    document.getElementById('priceFormTitle').innerText = 'Редактирование услуги';
    document.getElementById('price_id').value = id;
    document.getElementById('service_name').value = name;
    document.getElementById('price_description').value = description;
    document.getElementById('service_price').value = price;
    document.getElementById('service_unit').value = unit;
};
    // Функции для сотрудников
    function showEmployeeForm() {
        document.getElementById('employeeForm').style.display = 'block';
        document.getElementById('emp_id').value = 0;
        document.getElementById('emp_email').value = '';
        document.getElementById('emp_password').value = '';
        document.getElementById('emp_last_name').value = '';
        document.getElementById('emp_first_name').value = '';
        document.getElementById('emp_middle_name').value = '';
        document.getElementById('emp_phone').value = '';
        document.getElementById('emp_birth_date').value = '';
        document.getElementById('emp_position').value = '';
        document.getElementById('emp_department').value = '';
        document.getElementById('emp_role').value = 'executor';
    }
    function hideEmployeeForm() { document.getElementById('employeeForm').style.display = 'none'; }
    function editEmployee(id) {
        fetch('includes/get_employee.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('employeeForm').style.display = 'block';
                    document.getElementById('emp_id').value = data.employee.id;
                    document.getElementById('emp_email').value = data.employee.email;
                    document.getElementById('emp_password').value = '';
                    document.getElementById('emp_last_name').value = data.employee.last_name || '';
                    document.getElementById('emp_first_name').value = data.employee.first_name || '';
                    document.getElementById('emp_middle_name').value = data.employee.middle_name || '';
                    document.getElementById('emp_phone').value = data.employee.phone || '';
                    document.getElementById('emp_birth_date').value = data.employee.birth_date || '';
                    document.getElementById('emp_position').value = data.employee.position || '';
                    document.getElementById('emp_department').value = data.employee.department || '';
                    document.getElementById('emp_role').value = data.employee.role;
                } else alert('Ошибка загрузки данных сотрудника');
            })
            .catch(err => alert('Ошибка: ' + err.message));
    }

    // Функции для заявок (назначение, просмотр, удаление)
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
                fetch('includes/update_message_assign.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `action=assign&id=${requestId}&employee_id=0` })
                    .then(r => r.json()).then(data => { if (data.success) location.reload(); else alert(data.error); });
            }
            return;
        }
        fetch('includes/update_message_assign.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `action=assign&id=${requestId}&employee_id=${employeeId}` })
            .then(r => r.json()).then(data => { if (data.success) location.reload(); else alert(data.error); });
    }
    function showRequestDetails(id) {
        fetch('includes/get_message.php?id=' + id)
            .then(r => r.json()).then(data => {
                if (data.success) {
                    const msg = data.message;
                    document.getElementById('modalTitle').textContent = `Заявка #${msg.id}`;
                    document.getElementById('modalContent').innerHTML = `
                        <div><strong>Имя:</strong> ${escapeHtml(msg.user_name)}</div>
                        <div><strong>Email:</strong> ${escapeHtml(msg.user_email)}</div>
                        <div><strong>Телефон:</strong> ${escapeHtml(msg.phone || '—')}</div>
                        <div><strong>Тема:</strong> ${escapeHtml(msg.subject)}</div>
                        <div><strong>Статус:</strong> <span class="status-badge">${escapeHtml(msg.status)}</span></div>
                        <div><strong>Сообщение:</strong><br><pre style="white-space:pre-wrap">${escapeHtml(msg.message)}</pre></div>
                        ${msg.admin_response ? `<div><strong>Ответ администратора:</strong><br><pre>${escapeHtml(msg.admin_response)}</pre></div>` : ''}
                        <div class="status-controls" style="margin-top:20px;">
                            <select id="statusSelect">
                                <option value="новая" ${msg.status === 'новая' ? 'selected' : ''}>Новая</option>
                                <option value="в работе" ${msg.status === 'в работе' ? 'selected' : ''}>В работе</option>
                                <option value="выполнена" ${msg.status === 'выполнена' ? 'selected' : ''}>Выполнена</option>
                            </select>
                            <button class="btn btn-success" onclick="updateRequestStatus(${msg.id})">Сохранить статус</button>
                            <button class="btn btn-secondary" onclick="closeModal()">Закрыть</button>
                        </div>
                    `;
                    document.getElementById('requestModal').style.display = 'flex';
                } else alert('Ошибка загрузки');
            });
    }
    function updateRequestStatus(id) {
        const newStatus = document.getElementById('statusSelect').value;
        fetch('includes/update_message_assign.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `action=update_status&id=${id}&status=${encodeURIComponent(newStatus)}` })
            .then(r => r.json()).then(data => { if (data.success) location.reload(); else alert('Ошибка: ' + data.error); });
    }
    function deleteSingleRequest(id) { if (confirm(`Удалить заявку #${id}?`)) window.location.href = `includes/delete_request.php?id=${id}&section=requests`; }
    let selectedRequests = [];
    function updateBulkDeleteCount() {
        const checkboxes = document.querySelectorAll('.request-checkbox:checked');
        const count = checkboxes.length;
        document.getElementById('bulkDeleteBtn').style.display = count > 0 ? 'inline-block' : 'none';
        document.getElementById('selectedCount').innerText = count;
        selectedRequests = Array.from(checkboxes).map(cb => cb.value);
    }
    function toggleSelectAll(checkbox) { document.querySelectorAll('.request-checkbox').forEach(cb => cb.checked = checkbox.checked); updateBulkDeleteCount(); }
    function showBulkDeleteModal() {
        if (selectedRequests.length === 0) return;
        if (confirm(`Удалить ${selectedRequests.length} заявок?`)) {
            fetch('includes/bulk_delete_requests.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ids: selectedRequests, section: 'requests' }) })
                .then(r => r.json()).then(data => { if (data.success) location.reload(); else alert('Ошибка: ' + data.error); });
        }
    }
    function closeModal() { document.getElementById('requestModal').style.display = 'none'; }

    // Функции для сообщений
    function viewMessage(id) {
        fetch('includes/get_message.php?id=' + id)
            .then(r => r.json()).then(data => {
                if (data.success) {
                    const msg = data.message;
                    document.getElementById('messageModalTitle').textContent = `Сообщение #${msg.id}`;
                    document.getElementById('messageModalContent').innerHTML = `
                        <div><strong>От:</strong> ${escapeHtml(msg.user_name)} (${escapeHtml(msg.user_email)})</div>
                        <div><strong>Тема:</strong> ${escapeHtml(msg.subject)}</div>
                        <div><strong>Дата:</strong> ${formatDateTime(msg.created_at)}</div>
                        <hr><div><strong>Сообщение:</strong><br><pre style="white-space:pre-wrap">${escapeHtml(msg.message)}</pre></div>
                        ${msg.admin_response ? `<hr><div><strong>Ответ администратора:</strong><br><pre style="white-space:pre-wrap">${escapeHtml(msg.admin_response)}</pre></div>` : ''}
                        <div style="margin-top:20px;">
                            ${!msg.admin_response ? `<textarea id="replyText" rows="4" style="width:100%" placeholder="Ваш ответ..."></textarea><br><button class="btn btn-success" onclick="sendReply(${msg.id})">Отправить ответ</button>` : ''}
                            <button class="btn btn-secondary" onclick="closeMessageModal()">Закрыть</button>
                        </div>
                    `;
                    document.getElementById('messageModal').style.display = 'flex';
                    if (!msg.is_read) markMessageAsRead(id);
                } else alert('Ошибка загрузки');
            });
    }
    function markMessageAsRead(id) {
        fetch('includes/mark_message_read.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `id=${id}` })
            .then(r => r.json()).then(data => { if (data.success) location.reload(); });
    }
    function sendReply(id) {
        let reply = document.getElementById('replyText').value;
        if (!reply.trim()) { alert('Введите ответ'); return; }
        fetch('includes/send_reply.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `id=${id}&reply=${encodeURIComponent(reply)}` })
            .then(r => r.json()).then(data => { if (data.success) location.reload(); else alert('Ошибка: ' + data.error); });
    }
    function closeMessageModal() { document.getElementById('messageModal').style.display = 'none'; }
    function replyToMessage(id) { viewMessage(id); }

    // Вспомогательные функции
    function escapeHtml(str) { if(!str) return ''; return str.replace(/[&<>]/g, function(m){ if(m==='&') return '&amp;'; if(m==='<') return '&lt;'; if(m==='>') return '&gt;'; return m;}); }
    function formatDateTime(dateTime) { return new Date(dateTime).toLocaleString('ru-RU'); }

    // Инициализация
    document.addEventListener('DOMContentLoaded', function() {
        initAssignButtons();
        document.getElementById('requestModal')?.addEventListener('click', function(e) { if (e.target === this) closeModal(); });
        document.getElementById('messageModal')?.addEventListener('click', function(e) { if (e.target === this) closeMessageModal(); });
        setTimeout(() => { let alert = document.querySelector('.alert'); if(alert) alert.style.opacity='0'; setTimeout(()=>alert?.remove(),500); },4000);
    });
</script>
</body>
</html>