<?php
session_start();
require_once 'includes/check_auth.php';
checkAuth();

if (!isAdmin()) {
    header('Location: user.php');
    exit();
}

// Сначала подключаем config.php
require_once 'includes/config.php';
try {
    // Подключаемся с данными из config.php
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $connection_error = "Ошибка подключения к базе данных:<br><strong>" . $e->getMessage() . "</strong>";
    error_log("DB Connection Error: " . $e->getMessage());
    $pdo = null;
}

// Проверяем, что подключение к БД успешно
if (!isset($pdo) || $pdo === null) {
    die("Ошибка подключения к базе данных. Пожалуйста, проверьте настройки в config.php");
}

// Определяем активную секцию
$active_section = $_GET['section'] ?? $_SESSION['admin_active_section'] ?? 'news';
$_SESSION['admin_active_section'] = $active_section;

// Проверяем сообщения
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);

// Получаем статистику по сообщениям для подсветки
$unread_messages_count = 0;
$unanswered_messages_count = 0;
try {
    $unread_sql = "SELECT COUNT(*) as count FROM message WHERE is_read = 0";
    $unanswered_sql = "SELECT COUNT(*) as count FROM message WHERE admin_response IS NULL";
    
    $stmt = $pdo->query($unread_sql);
    $unread_messages_count = $stmt->fetch()['count'];
    
    $stmt = $pdo->query($unanswered_sql);
    $unanswered_messages_count = $stmt->fetch()['count'];
} catch (PDOException $e) {
    // Игнорируем ошибки статистики
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Панель администратора - ГБУ "Жилищник Района Строгино"</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <!-- Сообщения -->
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>" id="message-alert">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Поп-ап для просмотра заявки -->
    <div class="modal-overlay" id="requestModal">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalTitle">Заявка #</h3>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Контент загружается через JS -->
            </div>
        </div>
    </div>

    <header class="admin-header">
        <div class="admin-container">
            <nav class="admin-nav">
                <h1>Панель администратора</h1>
                <div class="admin-nav-links">
                    <span>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
                    <a href="index.php">Главная</a>
                    <a href="includes/logout.php">Выйти</a>
                </div>
            </nav>
        </div>
    </header>

    <div class="admin-container">
        <div class="admin-content">
            <aside class="admin-sidebar">
                <ul>
                    <li><a href="?section=news" class="<?php echo $active_section == 'news' ? 'active' : ''; ?>">Управление новостями</a></li>
                    <li><a href="?section=prices" class="<?php echo $active_section == 'prices' ? 'active' : ''; ?>">Управление ценами</a></li>
                    <li><a href="?section=requests" class="<?php echo $active_section == 'requests' ? 'active' : ''; ?>">Управление заявками</a></li>
                    <li><a href="?section=messages" class="<?php echo $active_section == 'messages' ? 'active' : ''; ?>">
                        Сообщения пользователей
                        <?php if ($unread_messages_count > 0): ?>
                            <span class="sidebar-badge"><?php echo $unread_messages_count; ?></span>
                        <?php endif; ?>
                    </a></li>
                    <li><a href="?section=users" class="<?php echo $active_section == 'users' ? 'active' : ''; ?>">Управление пользователями</a></li>
                </ul>
            </aside>

            <main class="admin-main">
                <!-- Секция новостей -->
                <div id="news" class="section <?php echo $active_section == 'news' ? 'active' : ''; ?>">
                    <h2>Управление новостями</h2>
                    <button class="btn btn-primary" onclick="showNewsForm()">Добавить новость</button>
                    
                    <!-- Форма добавления/редактирования новости -->
                    <div id="news-form" style="display: none; margin-top: 20px;">
                        <form action="includes/save_news.php?section=<?php echo $active_section; ?>" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="news_id" id="news_id">
                            <div class="form-group">
                                <label>Заголовок</label>
                                <input type="text" name="title" id="news_title" required>
                            </div>
                            <div class="form-group">
                                <label>Описание</label>
                                <textarea name="description" id="news_description" rows="5" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Изображение</label>
                                <input type="file" name="image" id="news_image" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-success">Сохранить</button>
                            <button type="button" class="btn" onclick="hideNewsForm()">Отмена</button>
                        </form>
                    </div>

                    <!-- Список новостей -->
                    <div class="table-container mt-20">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Заголовок</th>
                                    <th>Дата</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (file_exists('includes/get_news.php')): ?>
                                    <?php include 'includes/get_news.php'; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <div class="empty-state">
                                                <div class="empty-icon">📰</div>
                                                <h3>Файл get_news.php не найден</h3>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Секция цен -->
                <div id="prices" class="section <?php echo $active_section == 'prices' ? 'active' : ''; ?>">
                    <h2>Управление ценами на услуги</h2>
                    <button class="btn btn-primary" onclick="showPriceForm()">Добавить услугу</button>
                    
                    <!-- Форма добавления/редактирования цены -->
                    <div id="price-form" style="display: none; margin-top: 20px;">
                        <form action="includes/save_price.php?section=<?php echo $active_section; ?>" method="POST">
                            <input type="hidden" name="price_id" id="price_id">
                            <div class="form-group">
                                <label>Название услуги</label>
                                <input type="text" name="service_name" id="service_name" required>
                            </div>
                            <div class="form-group">
                                <label>Описание</label>
                                <textarea name="description" id="price_description" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Цена (руб.)</label>
                                <input type="number" name="price" id="service_price" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label>Единица измерения</label>
                                <input type="text" name="unit" id="service_unit" placeholder="шт., м², час и т.д.">
                            </div>
                            <button type="submit" class="btn btn-success">Сохранить</button>
                            <button type="button" class="btn" onclick="hidePriceForm()">Отмена</button>
                        </form>
                    </div>

                    <!-- Список цен -->
                    <div class="table-container mt-20">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Услуга</th>
                                    <th>Описание</th>
                                    <th>Цена</th>
                                    <th>Ед. изм.</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (file_exists('includes/get_prices.php')): ?>
                                    <?php include 'includes/get_prices.php'; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="empty-state">
                                                <div class="empty-icon">💰</div>
                                                <h3>Файл get_prices.php не найден</h3>
                                                <p>Создайте файл includes/get_prices.php</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Секция заявок -->
                <div id="requests" class="section <?php echo $active_section == 'requests' ? 'active' : ''; ?>">
                    <h2>Управление заявками</h2>
                    
                    <div class="bulk-actions mb-20">
                        <button class="btn btn-danger" onclick="showBulkDeleteModal()" id="bulkDeleteBtn" style="display: none;">
                            🗑️ Удалить выбранные (0)
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
                                    <th>Дата</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT * FROM requests ORDER BY created_at DESC";
                                
                                try {
                                    $stmt = $pdo->query($sql);
                                    $requests = $stmt->fetchAll();
                                    
                                    if (empty($requests)) {
                                        ?>
                                        <tr>
                                            <td colspan="10" class="no-data">
                                                <div class="empty-state">
                                                    <div class="empty-icon">📭</div>
                                                    <h3>Заявок не найдено</h3>
                                                    <p>Еще никто не оставил заявку</p>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    } else {
                                        foreach ($requests as $request) {
                                            $status_class = 'status-' . ($request['status'] ?? 'new');
                                            $status_text = [
                                                'new' => 'Новая',
                                                'in_progress' => 'В работе',
                                                'completed' => 'Завершена'
                                            ][$request['status'] ?? 'new'] ?? 'Новая';
                                            ?>
                                            <tr class="request-row <?php echo $status_class; ?>" id="row-<?php echo $request['id']; ?>">
                                                <td data-label=""><input type="checkbox" class="request-checkbox" value="<?php echo $request['id']; ?>" onchange="updateBulkDeleteCount()"></td>
                                                <td data-label="ID">#<?php echo $request['id']; ?></td>
                                                <td data-label="Имя"><strong><?php echo htmlspecialchars($request['user_name']); ?></strong></td>
                                                <td data-label="Email"><a href="mailto:<?php echo htmlspecialchars($request['user_email']); ?>"><?php echo htmlspecialchars($request['user_email']); ?></a></td>
                                                <td data-label="Телефон"><?php echo !empty($request['user_phone']) ? htmlspecialchars($request['user_phone']) : '—'; ?></td>
                                                <td data-label="Тема"><?php echo htmlspecialchars($request['subject']); ?></td>
                                                <td data-label="Сообщение"><div class="message-preview"><?php echo htmlspecialchars(mb_substr($request['message'], 0, 100)) . '...'; ?></div></td>
                                                <td data-label="Статус"><span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                                                <td data-label="Дата"><?php echo date('d.m.Y H:i', strtotime($request['created_at'])); ?></td>
                                                <td data-label="Действия" class="request-actions">
                                                    <button class="btn-view" onclick="showRequestDetails(<?php echo $request['id']; ?>)" title="Просмотр">👁️</button>
                                                    <button class="btn-edit" onclick="editRequest(<?php echo $request['id']; ?>)" title="Редактировать">✏️</button>
                                                    <button class="btn-delete" onclick="deleteSingleRequest(<?php echo $request['id']; ?>)" title="Удалить">🗑️</button>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                } catch (PDOException $e) {
                                    ?>
                                    <tr>
                                        <td colspan="10" class="error-message">Ошибка загрузки заявок: <?php echo $e->getMessage(); ?></td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Секция сообщений пользователей -->
                <div id="messages" class="section <?php echo $active_section == 'messages' ? 'active' : ''; ?>">
                    <h2>Сообщения пользователей 
                        <?php if ($unread_messages_count > 0): ?>
                            <span class="messages-tab-badge"><?php echo $unread_messages_count; ?> новых</span>
                        <?php endif; ?>
                    </h2>
                    
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
                    
                    if ($status_filter === 'unread') {
                        $sql .= " AND is_read = 0";
                    } elseif ($status_filter === 'unanswered') {
                        $sql .= " AND admin_response IS NULL";
                    }
                    
                    $sql .= " ORDER BY created_at DESC";
                    
                    try {
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        $messages = $stmt->fetchAll();
                    } catch (PDOException $e) {
                        $messages = [];
                        error_log("Ошибка загрузки сообщений: " . $e->getMessage());
                    }
                    ?>
                    
                    <div class="filters mb-20">
                        <form method="GET" class="filter-form">
                            <input type="hidden" name="section" value="messages">
                            <div class="search-box">
                                <input type="text" name="search" placeholder="Поиск по имени, email или теме..." value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-info">🔍</button>
                            </div>
                            <select name="status">
                                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>Все сообщения</option>
                                <option value="unread" <?php echo $status_filter == 'unread' ? 'selected' : ''; ?>>Непрочитанные</option>
                                <option value="unanswered" <?php echo $status_filter == 'unanswered' ? 'selected' : ''; ?>>Без ответа</option>
                            </select>
                            <button type="submit" class="btn btn-primary">Фильтровать</button>
                            <?php if (!empty($search) || $status_filter != 'all'): ?>
                                <a href="?section=messages" class="btn">Сбросить фильтры</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <div class="stats-grid mb-20">
                        <div class="stat-card"><div class="stat-value"><?php echo count($messages); ?></div><div class="stat-label">Всего сообщений</div></div>
                        <div class="stat-card"><div class="stat-value"><?php echo $unread_messages_count; ?></div><div class="stat-label">Непрочитанных</div></div>
                        <div class="stat-card"><div class="stat-value"><?php echo $unanswered_messages_count; ?></div><div class="stat-label">Без ответа</div></div>
                        <div class="stat-card"><div class="stat-value"><?php $answered = count($messages) - $unanswered_messages_count; echo $answered > 0 && count($messages) > 0 ? round(($answered / count($messages)) * 100, 1) . '%' : '0%'; ?></div><div class="stat-label">Отвечено</div></div>
                    </div>
                    
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr><th>ID</th><th>Имя</th><th>Email</th><th>Тема</th><th>Сообщение</th><th>Дата</th><th>Статус</th><th>Действия</th></tr>
                            </thead>
                            <tbody>
                                <?php if (empty($messages)): ?>
                                    <tr><td colspan="8" class="no-data"><div class="empty-state"><div class="empty-icon">📭</div><h3>Сообщения не найдены</h3></div></td></tr>
                                <?php else: ?>
                                    <?php foreach ($messages as $msg): ?>
                                        <tr class="message-row <?php echo !$msg['is_read'] ? 'unread' : ''; ?>" id="message-<?php echo $msg['id']; ?>">
                                            <td data-label="ID">#<?php echo $msg['id']; ?></td>
                                            <td data-label="Имя"><strong><?php echo htmlspecialchars($msg['user_name']); ?></strong><?php if ($msg['phone']): ?><br><small>📞 <?php echo htmlspecialchars($msg['phone']); ?></small><?php endif; ?></td>
                                            <td data-label="Email"><a href="mailto:<?php echo htmlspecialchars($msg['user_email']); ?>"><?php echo htmlspecialchars($msg['user_email']); ?></a></td>
                                            <td data-label="Тема"><?php echo htmlspecialchars($msg['subject']); ?></td>
                                            <td data-label="Сообщение"><div class="message-preview"><?php echo htmlspecialchars(mb_substr($msg['message'], 0, 100)) . '...'; ?></div></td>
                                            <td data-label="Дата"><?php echo date('d.m.Y H:i', strtotime($msg['created_at'])); ?></td>
                                            <td data-label="Статус"><?php if ($msg['admin_response']): ?><span class="badge badge-success">✅ Ответ дан</span><?php elseif ($msg['is_read']): ?><span class="badge badge-info">👁️ Прочитано</span><?php else: ?><span class="badge badge-warning">🆕 Новое</span><?php endif; ?></td>
                                            <td data-label="Действия" class="message-actions">
                                                <button class="btn btn-sm btn-info" onclick="viewMessage(<?php echo $msg['id']; ?>)" title="Просмотр">👁️</button>
                                                <?php if (!$msg['admin_response']): ?>
                                                    <button class="btn btn-sm btn-success" onclick="replyToMessage(<?php echo $msg['id']; ?>)" title="Ответить">✉️</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Секция пользователей -->
                <div id="users" class="section <?php echo $active_section == 'users' ? 'active' : ''; ?>">
                    <h2>Управление пользователями</h2>
                    <button class="btn btn-primary mb-20" onclick="showUserForm()">Добавить пользователя</button>
                    
                    <div id="user-form" style="display: none; margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                        <form action="includes/save_user.php?section=<?php echo $active_section; ?>" method="POST">
                            <input type="hidden" name="user_id" id="user_id">
                            <div class="form-group"><label>Email</label><input type="email" name="email" id="user_email" required></div>
                            <div class="form-group"><label>Пароль</label><input type="password" name="password" id="user_password" placeholder="Оставьте пустым, чтобы не менять"></div>
                            <div class="form-group"><label>Имя</label><input type="text" name="name" id="user_name"></div>
                            <div class="form-group"><label>Роль</label><select name="role" id="user_role" required><option value="user">Пользователь</option><option value="admin">Администратор</option></select></div>
                            <div class="form-group"><label>Статус</label><select name="status" id="user_status" required><option value="active">Активен</option><option value="inactive">Неактивен</option><option value="blocked">Заблокирован</option></select></div>
                            <button type="submit" class="btn btn-success">Сохранить</button>
                            <button type="button" class="btn" onclick="hideUserForm()">Отмена</button>
                        </form>
                    </div>

                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>ID</th><th>Email</th><th>Имя</th><th>Роль</th><th>Статус</th><th>Дата</th><th>Действия</th></tr></thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
                                    $users = $stmt->fetchAll();
                                    if (empty($users)) {
                                        echo '<tr><td colspan="7" class="text-center"><div class="empty-state"><div class="empty-icon">👥</div><h3>Пользователи не найдены</h3></div></td></tr>';
                                    } else {
                                        foreach ($users as $user) {
                                            $role_badge = $user['role'] == 'admin' ? 'status-badge status-in_progress' : 'status-badge status-new';
                                            $status_badge = $user['status'] == 'active' ? 'status-completed' : ($user['status'] == 'inactive' ? 'status-new' : 'status-in_progress');
                                            ?>
                                            <tr>
                                                <td data-label="ID">#<?php echo $user['id']; ?></td>
                                                <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td data-label="Имя"><?php echo htmlspecialchars($user['name'] ?? 'Не указано'); ?></td>
                                                <td data-label="Роль"><span class="<?php echo $role_badge; ?>"><?php echo $user['role'] == 'admin' ? 'Админ' : 'Пользователь'; ?></span></td>
                                                <td data-label="Статус"><span class="<?php echo $status_badge; ?>"><?php echo ucfirst($user['status']); ?></span></td>
                                                <td data-label="Дата"><?php echo date('d.m.Y H:i', strtotime($user['created_at'] ?? 'now')); ?></td>
                                                <td data-label="Действия" class="request-actions">
                                                    <button class="btn-edit" onclick="editUser(<?php echo $user['id']; ?>)" title="Редактировать">✏️</button>
                                                    <a href="includes/delete_user.php?id=<?php echo $user['id']; ?>&section=<?php echo $active_section; ?>" class="btn-delete" onclick="return confirm('Удалить пользователя?')" title="Удалить">🗑️</a>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                } catch (PDOException $e) {
                                    echo '<tr><td colspan="7" class="error-message">Ошибка загрузки пользователей: ' . $e->getMessage() . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Модальные окна -->
    <div class="modal-overlay" id="messageModal"><div class="modal"><div class="modal-header"><h3 id="messageModalTitle">Сообщение #</h3><button class="modal-close" onclick="closeMessageModal()">×</button></div><div class="modal-body" id="messageModalContent"></div></div></div>
    <div class="modal-overlay" id="replyModal"><div class="modal"><div class="modal-header"><h3>Ответить на сообщение</h3><button class="modal-close" onclick="closeReplyModal()">×</button></div><div class="modal-body"><form id="replyForm"><input type="hidden" id="replyMessageId"><div class="form-group"><label>Ответ администратора:</label><textarea id="adminReply" rows="8" required placeholder="Введите ваш ответ..."></textarea></div><div class="status-controls"><button type="submit" class="btn-status btn-save">Отправить ответ</button><button type="button" class="btn-status btn-close-modal" onclick="closeReplyModal()">Отмена</button></div></form></div></div></div>
    <div class="modal-overlay" id="bulkDeleteModal"><div class="modal"><div class="modal-header"><h3>Массовое удаление</h3><button class="modal-close" onclick="closeBulkDeleteModal()">×</button></div><div class="modal-body"><div id="bulkDeleteContent"><p>Вы уверены, что хотите удалить <strong id="selectedCount">0</strong> заявок?</p><div id="selectedList"></div><p><strong>⚠️ Внимание:</strong> Это действие нельзя отменить!</p><div class="status-controls"><button class="btn-status btn-save" onclick="performBulkDelete()">Да, удалить</button><button class="btn-status btn-close-modal" onclick="closeBulkDeleteModal()">Отмена</button></div></div><div id="bulkDeleteProgress" style="display: none; text-align: center;"><div class="spinner"></div><p>Удаление заявок...</p></div></div></div></div>

    <script>
    // Функции для работы с сообщениями
    function viewMessage(id) {
        fetch('includes/get_message.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const message = data.message;
                    document.getElementById('messageModalTitle').textContent = `Сообщение #${message.id}`;
                    document.getElementById('messageModalContent').innerHTML = `
                        <div class="message-detail"><div class="detail-label">От:</div><div class="detail-value"><strong>${escapeHtml(message.user_name)}</strong><br>📧 <a href="mailto:${escapeHtml(message.user_email)}">${escapeHtml(message.user_email)}</a>${message.user_phone ? `<br>📞 ${escapeHtml(message.user_phone)}` : ''}</div></div>
                        <div class="message-detail"><div class="detail-label">Тема:</div><div class="detail-value">${escapeHtml(message.subject)}</div></div>
                        <div class="message-detail"><div class="detail-label">Дата отправки:</div><div class="detail-value">${formatDateTime(message.created_at)}</div></div>
                        <div class="message-detail"><div class="detail-label">Сообщение:</div><div class="message-full">${escapeHtml(message.message).replace(/\n/g, '<br>')}</div></div>
                        ${message.admin_response ? `<div class="message-detail"><div class="detail-label">Ответ администратора:</div><div class="message-full" style="background:#e8f5e8;">${escapeHtml(message.admin_response).replace(/\n/g, '<br>')}</div></div>` : ''}
                        <div class="status-controls" style="margin-top:20px;">
                            ${!message.is_read ? `<button class="btn-status btn-info" onclick="markMessageAsRead(${message.id})">✅ Отметить как прочитанное</button>` : ''}
                            ${!message.admin_response ? `<button class="btn-status btn-success" onclick="openReplyModal(${message.id})">✉️ Ответить</button>` : ''}
                            <button class="btn-status btn-close-modal" onclick="closeMessageModal()">Закрыть</button>
                        </div>
                    `;
                    document.getElementById('messageModal').style.display = 'flex';
                    if (!message.is_read) markMessageAsRead(id, false);
                } else alert('Ошибка загрузки сообщения');
            }).catch(error => { console.error('Error:', error); alert('Ошибка загрузки данных'); });
    }
    
    function markMessageAsRead(id, reload = true) {
        fetch('includes/mark_message_read.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `id=${id}&section=messages` })
            .then(response => response.json())
            .then(data => { if (data.success && reload) location.reload(); })
            .catch(error => console.error('Error:', error));
    }
    
    function openReplyModal(messageId) { document.getElementById('replyMessageId').value = messageId; document.getElementById('adminReply').value = ''; document.getElementById('replyModal').style.display = 'flex'; }
    function closeReplyModal() { document.getElementById('replyModal').style.display = 'none'; }
    function closeMessageModal() { document.getElementById('messageModal').style.display = 'none'; }
    function replyToMessage(id) { openReplyModal(id); }
    
    document.getElementById('replyForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const messageId = document.getElementById('replyMessageId').value;
        const replyText = document.getElementById('adminReply').value;
        if (!replyText.trim()) { alert('Введите текст ответа'); return; }
        fetch('includes/send_reply.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `id=${messageId}&reply=${encodeURIComponent(replyText)}&section=messages` })
            .then(response => response.json())
            .then(data => { if (data.success) { alert('Ответ успешно отправлен!'); closeReplyModal(); closeMessageModal(); location.reload(); } else alert('Ошибка отправки ответа: ' + data.error); })
            .catch(error => { console.error('Error:', error); alert('Ошибка отправки ответа'); });
    });
    
    document.getElementById('messageModal').addEventListener('click', function(e) { if (e.target === this) closeMessageModal(); });
    document.getElementById('replyModal').addEventListener('click', function(e) { if (e.target === this) closeReplyModal(); });
    
    function showNewsForm() { document.getElementById('news-form').style.display = 'block'; document.getElementById('news_id').value = ''; document.getElementById('news_title').value = ''; document.getElementById('news_description').value = ''; }
    function hideNewsForm() { document.getElementById('news-form').style.display = 'none'; }
    function showPriceForm() { document.getElementById('price-form').style.display = 'block'; document.getElementById('price_id').value = ''; document.getElementById('service_name').value = ''; document.getElementById('price_description').value = ''; document.getElementById('service_price').value = ''; document.getElementById('service_unit').value = ''; }
    function hidePriceForm() { document.getElementById('price-form').style.display = 'none'; }
    function editNews(id, title, description) { document.getElementById('news_id').value = id; document.getElementById('news_title').value = title; document.getElementById('news_description').value = description; document.getElementById('news-form').style.display = 'block'; }
    function editPrice(id, name, description, price, unit) { document.getElementById('price_id').value = id; document.getElementById('service_name').value = name; document.getElementById('price_description').value = description; document.getElementById('service_price').value = price; document.getElementById('service_unit').value = unit; document.getElementById('price-form').style.display = 'block'; }
    function showUserForm() { document.getElementById('user-form').style.display = 'block'; document.getElementById('user_id').value = ''; document.getElementById('user_email').value = ''; document.getElementById('user_password').value = ''; document.getElementById('user_name').value = ''; document.getElementById('user_role').value = 'user'; document.getElementById('user_status').value = 'active'; }
    function hideUserForm() { document.getElementById('user-form').style.display = 'none'; }
    function editUser(id) { fetch('includes/get_user.php?id=' + id).then(response => response.json()).then(data => { if (data.success) { document.getElementById('user-form').style.display = 'block'; document.getElementById('user_id').value = data.user.id; document.getElementById('user_email').value = data.user.email; document.getElementById('user_name').value = data.user.name || ''; document.getElementById('user_role').value = data.user.role; document.getElementById('user_status').value = data.user.status; document.getElementById('user_password').value = ''; } else alert('Ошибка загрузки данных пользователя'); }).catch(error => { console.error('Error:', error); alert('Ошибка загрузки данных'); }); }
    
    function showRequestDetails(id) {
        fetch('includes/get_request_details.php?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const request = data.request;
                    document.getElementById('modalTitle').textContent = `Заявка #${request.id}`;
                    document.getElementById('modalContent').innerHTML = `
                        <div class="request-detail"><div class="detail-label">Имя:</div><div class="detail-value">${escapeHtml(request.user_name)}</div></div>
                        <div class="request-detail"><div class="detail-label">Email:</div><div class="detail-value"><a href="mailto:${escapeHtml(request.user_email)}">${escapeHtml(request.user_email)}</a></div></div>
                        <div class="request-detail"><div class="detail-label">Тема:</div><div class="detail-value">${escapeHtml(request.subject)}</div></div>
                        <div class="request-detail"><div class="detail-label">Статус:</div><div class="detail-value"><span class="status-badge status-${request.status || 'new'}">${getStatusText(request.status)}</span></div></div>
                        <div class="request-detail"><div class="detail-label">Дата создания:</div><div class="detail-value">${formatDateTime(request.created_at)}</div></div>
                        <div class="request-detail"><div class="detail-label">Сообщение:</div><div class="message-full">${escapeHtml(request.message)}</div></div>
                        <div class="status-controls"><select class="status-select" id="statusSelect"><option value="new" ${request.status === 'new' ? 'selected' : ''}>Новая</option><option value="in_progress" ${request.status === 'in_progress' ? 'selected' : ''}>В работе</option><option value="completed" ${request.status === 'completed' ? 'selected' : ''}>Завершена</option></select><button class="btn-status btn-save" onclick="updateRequestStatus(${request.id})">Сохранить статус</button><button class="btn-status btn-close-modal" onclick="closeModal()">Закрыть</button></div>
                    `;
                    document.getElementById('requestModal').style.display = 'flex';
                } else alert('Ошибка загрузки данных заявки');
            }).catch(error => { console.error('Error:', error); alert('Ошибка загрузки данных'); });
    }
    
    function closeModal() { document.getElementById('requestModal').style.display = 'none'; }
    function updateRequestStatus(id) { const newStatus = document.getElementById('statusSelect').value; fetch('includes/update_request_status.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: `id=${id}&status=${newStatus}&section=requests` }).then(response => response.json()).then(data => { if (data.success) { alert('Статус успешно обновлен!'); closeModal(); location.reload(); } else alert('Ошибка обновления статуса: ' + data.error); }).catch(error => { console.error('Error:', error); alert('Ошибка обновления статуса'); }); }
    function deleteSingleRequest(id) { if (confirm(`Вы уверены, что хотите удалить заявку #${id}?`)) window.location.href = `includes/delete_request.php?id=${id}&section=requests`; }
    function editRequest(id) { window.location.href = `edit_request.php?id=${id}&section=requests`; }
    
    let selectedRequests = [];
    function updateBulkDeleteCount() { const checkboxes = document.querySelectorAll('.request-checkbox:checked'); const count = checkboxes.length; const btn = document.getElementById('bulkDeleteBtn'); selectedRequests = Array.from(checkboxes).map(cb => cb.value); btn.style.display = count > 0 ? 'inline-block' : 'none'; if (count > 0) btn.textContent = `🗑️ Удалить выбранные (${count})`; }
    function toggleSelectAll(checkbox) { document.querySelectorAll('.request-checkbox').forEach(cb => cb.checked = checkbox.checked); updateBulkDeleteCount(); }
    function showBulkDeleteModal() { const selectedCount = selectedRequests.length; document.getElementById('selectedCount').textContent = selectedCount; const listDiv = document.getElementById('selectedList'); listDiv.innerHTML = ''; selectedRequests.forEach(id => { const row = document.getElementById(`row-${id}`); if (row) { const name = row.querySelector('td[data-label="Имя"]')?.innerText || ''; listDiv.innerHTML += `<div style="padding:5px 0;border-bottom:1px solid #eee;"><strong>#${id}</strong> - ${name.substring(0, 50)}</div>`; } }); document.getElementById('bulkDeleteModal').style.display = 'flex'; }
    function closeBulkDeleteModal() { document.getElementById('bulkDeleteModal').style.display = 'none'; }
    function performBulkDelete() { if (selectedRequests.length === 0) return; document.getElementById('bulkDeleteContent').style.display = 'none'; document.getElementById('bulkDeleteProgress').style.display = 'block'; fetch('includes/bulk_delete_requests.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ids: selectedRequests, section: 'requests' }) }).then(response => response.json()).then(data => { if (data.success) location.reload(); else { alert('Ошибка при удалении: ' + data.error); closeBulkDeleteModal(); } }).catch(error => { console.error('Error:', error); alert('Ошибка при удалении'); closeBulkDeleteModal(); }); }
    
    function escapeHtml(text) { const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
    function getStatusText(status) { const statusMap = { 'new': 'Новая', 'in_progress': 'В работе', 'completed': 'Завершена' }; return statusMap[status] || 'Новая'; }
    function formatDateTime(dateTime) { return new Date(dateTime).toLocaleString('ru-RU'); }
    
    document.getElementById('requestModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
    setTimeout(function() { const alert = document.getElementById('message-alert'); if (alert) { alert.style.opacity = '0'; setTimeout(() => alert.remove(), 500); } }, 5000);
    </script>
</body>
</html>