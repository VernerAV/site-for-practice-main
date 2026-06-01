<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/check_auth.php';
checkAuth();

$allowed_roles = ['admin', 'executor', 'dispatcher'];
if (!in_array($_SESSION['user_role'], $allowed_roles)) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Получаем информацию о сотруднике
try {
    $stmt = $pdo->prepare("SELECT first_name, last_name, middle_name, position, employee_id FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $employee = $stmt->fetch();
} catch (PDOException $e) {
    $employee = null;
}

// Получаем параметры фильтрации
$status_tab = $_GET['status_tab'] ?? 'all'; // all, new, work, completed
$search = trim($_GET['search'] ?? '');
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Базовый запрос
if ($user_role === 'admin') {
    $sql = "SELECT m.*, CONCAT(up.last_name, ' ', up.first_name) as executor_name
            FROM message m
            LEFT JOIN users u ON m.assigned_to = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE m.assigned_to IS NOT NULL AND m.assigned_to != 0";
} else {
    $sql = "SELECT m.*, CONCAT(up.last_name, ' ', up.first_name) as executor_name
            FROM message m
            LEFT JOIN users u ON m.assigned_to = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE m.assigned_to = ?";
}
$params = [];
if ($user_role !== 'admin') {
    $params[] = $user_id;
}

// Фильтр по статусу
if ($status_tab === 'new') {
    $sql .= " AND m.status = 'новая'";
} elseif ($status_tab === 'work') {
    $sql .= " AND m.status = 'в работе'";
} elseif ($status_tab === 'completed') {
    $sql .= " AND m.status = 'выполнена'";
}

// Поиск по теме или сообщению
if (!empty($search)) {
    $sql .= " AND (m.subject LIKE ? OR m.message LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Фильтр по дате
if (!empty($date_from)) {
    $sql .= " AND DATE(m.created_at) >= ?";
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $sql .= " AND DATE(m.created_at) <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY m.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Статистика для вкладок
$stats = [];
$statsSql = "SELECT status, COUNT(*) as cnt FROM message WHERE assigned_to = ? GROUP BY status";
$statsStmt = $pdo->prepare($statsSql);
$statsStmt->execute([$user_id]);
foreach ($statsStmt->fetchAll() as $row) {
    $stats[$row['status']] = $row['cnt'];
}

$success_msg = $_SESSION['employee_success'] ?? '';
$error_msg = $_SESSION['employee_error'] ?? '';
unset($_SESSION['employee_success'], $_SESSION['employee_error']);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет сотрудника</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .filter-bar { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .filter-group { display: inline-flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 12px; font-weight: 600; color: #6c757d; }
        .tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #ddd; }
        .tab-button { background: none; border: none; padding: 10px 20px; cursor: pointer; font-size: 16px; border-bottom: 2px solid transparent; }
        .tab-button.active { border-bottom-color: #3498db; color: #3498db; font-weight: bold; }
        .badge-count { background: #e74c3c; color: white; border-radius: 20px; padding: 2px 8px; font-size: 12px; margin-left: 5px; }
        .request-card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 20px; padding: 20px; }
        .request-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; border-bottom: 1px solid #ecf0f1; padding-bottom: 12px; margin-bottom: 15px; }
        .request-id { background: #3498db; color: white; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; }
        .request-status { padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; }
        .status-new { background: #ff4757; color: white; }
        .status-progress { background: #ffa502; color: white; }
        .status-completed { background: #2ed573; color: white; }
        .btn-group { display: flex; gap: 10px; margin-top: 10px; flex-wrap: wrap; }
        .btn-reply { background: #17a2b8; color: white; border: none; padding: 6px 14px; border-radius: 30px; cursor: pointer; }
        .modal-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items: center; justify-content: center; }
        .modal { background: white; border-radius: 12px; width: 500px; max-width: 90%; padding: 20px; }
        .modal-header { display: flex; justify-content: space-between; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; }
        .employee-info { background: #f8f9fa; border-radius: 12px; padding: 15px 20px; margin-bottom: 25px; display: flex; justify-content: space-between; flex-wrap: wrap; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .badge { background: #3498db; color: white; padding: 4px 10px; border-radius: 30px; font-size: 12px; }
        .empty-state { text-align: center; padding: 50px; color: #6c757d; }
    </style>
</head>
<body>

<header>
    <div class="admin-nav">
        <h1>👨‍💼 Личный кабинет сотрудника</h1>
        <div>
            <span><?= htmlspecialchars($_SESSION['user_email']) ?></span>
            <a href="index.php">Главная</a>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="admin.php">Админ-панель</a>
            <?php endif; ?>
            <a href="includes/logout.php">Выйти</a>
        </div>
    </div>
</header>

<div class="admin-container">
    <div class="employee-info">
        <div>
            <span class="employee-name">
                <?php 
                if ($employee && ($employee['last_name'] || $employee['first_name'])) {
                    echo htmlspecialchars($employee['last_name'] . ' ' . $employee['first_name'] . ' ' . $employee['middle_name']);
                } else {
                    echo htmlspecialchars($_SESSION['user_email']);
                }
                ?>
            </span>
            <?php if ($employee && $employee['position']): ?>
                <span class="badge"><?= htmlspecialchars($employee['position']) ?></span>
            <?php endif; ?>
        </div>
        <div>
            <?php if ($employee && $employee['employee_id']): ?>
                Табельный № <?= htmlspecialchars($employee['employee_id']) ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <!-- Вкладки -->
    <div class="tabs">
        <button class="tab-button <?= $status_tab == 'all' ? 'active' : '' ?>" onclick="setTab('all')">Все</button>
        <button class="tab-button <?= $status_tab == 'new' ? 'active' : '' ?>" onclick="setTab('new')">
            Новые <?= isset($stats['новая']) ? "<span class='badge-count'>{$stats['новая']}</span>" : '' ?>
        </button>
        <button class="tab-button <?= $status_tab == 'work' ? 'active' : '' ?>" onclick="setTab('work')">
            В работе <?= isset($stats['в работе']) ? "<span class='badge-count'>{$stats['в работе']}</span>" : '' ?>
        </button>
        <button class="tab-button <?= $status_tab == 'completed' ? 'active' : '' ?>" onclick="setTab('completed')">
            Выполненные <?= isset($stats['выполнена']) ? "<span class='badge-count'>{$stats['выполнена']}</span>" : '' ?>
        </button>
    </div>

    <!-- Фильтры -->
    <div class="filter-bar">
        <form method="GET" id="filterForm">
            <input type="hidden" name="status_tab" id="status_tab_input" value="<?= htmlspecialchars($status_tab) ?>">
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
            <button type="submit" class="btn btn-primary">Применить</button>
            <a href="?status_tab=<?= urlencode($status_tab) ?>" class="btn btn-secondary">Сбросить</a>
        </form>
    </div>

    <h2>📋 Мои заявки</h2>

    <?php if (empty($requests)): ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <h3>Нет заявок</h3>
            <p>В этом разделе пока нет заявок</p>
        </div>
    <?php else: ?>
        <?php foreach ($requests as $req): ?>
            <?php
            $status_class = '';
            switch ($req['status']) {
                case 'новая': $status_class = 'status-new'; $status_text = 'Новая'; break;
                case 'в работе': $status_class = 'status-progress'; $status_text = 'В работе'; break;
                case 'выполнена': $status_class = 'status-completed'; $status_text = 'Выполнена'; break;
                default: $status_class = 'status-new'; $status_text = $req['status'];
            }
            ?>
            <div class="request-card">
                <div class="request-header">
                    <div>
                        <span class="request-id">#<?= $req['id'] ?></span>
                        <?php if ($user_role === 'admin' && isset($req['executor_name'])): ?>
                            <span style="margin-left: 10px; font-size: 13px;">👤 Исполнитель: <?= htmlspecialchars($req['executor_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="request-status <?= $status_class ?>"><?= $status_text ?></span>
                    </div>
                </div>
                <div class="request-subject">
                    <strong><?= htmlspecialchars($req['subject']) ?></strong>
                </div>
                <div class="request-message">
                    <?= nl2br(htmlspecialchars($req['message'])) ?>
                </div>
                <?php if (!empty($req['admin_response'])): ?>
                    <div style="background: #e8f5e9; padding: 10px; border-radius: 8px; margin: 10px 0;">
                        <strong>📨 Ответ администратора/исполнителя:</strong><br>
                        <?= nl2br(htmlspecialchars($req['admin_response'])) ?>
                        <?php if ($req['responded_at']): ?>
                            <div style="font-size: 12px; color: #666; margin-top: 5px;">Отправлен: <?= date('d.m.Y H:i', strtotime($req['responded_at'])) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="request-meta">
                    📅 Создано: <?= date('d.m.Y H:i', strtotime($req['created_at'])) ?>
                    <?php if ($req['assigned_at']): ?>
                        &nbsp;|&nbsp;👤 Назначено: <?= date('d.m.Y H:i', strtotime($req['assigned_at'])) ?>
                    <?php endif; ?>
                </div>

                <?php if (in_array($user_role, ['executor', 'dispatcher'])): ?>
                    <div class="btn-group">
                        <?php if ($req['status'] == 'новая'): ?>
                            <form action="includes/update_employee_request.php" method="POST" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <input type="hidden" name="action" value="take">
                                <button type="submit" class="btn btn-primary">✅ Взять в работу</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($req['status'] == 'в работе'): ?>
                            <form action="includes/update_employee_request.php" method="POST" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <input type="hidden" name="action" value="complete">
                                <button type="submit" class="btn btn-success">✔️ Выполнить</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($req['status'] != 'выполнена'): ?>
                            <form action="includes/update_employee_request.php" method="POST" style="display:inline;">
                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                <input type="hidden" name="action" value="return">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Вернуть заявку? Она будет отправлена администратору для переназначения.')">↩️ Вернуть</button>
                            </form>
                        <?php endif; ?>

                        <!-- Кнопка Ответить (после ответа заявка становится выполненной) -->
                        <button class="btn-reply" onclick="openReplyModal(<?= $req['id'] ?>, '<?= addslashes(htmlspecialchars($req['subject'])) ?>')">✉️ Ответить</button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Модальное окно для ответа -->
<div id="replyModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3 id="replyModalTitle">Ответ на заявку</h3>
            <button class="modal-close" onclick="closeReplyModal()">×</button>
        </div>
        <div class="modal-body">
            <form id="replyForm" method="POST" action="includes/employee_send_reply.php">
                <input type="hidden" name="request_id" id="replyRequestId">
                <div class="form-group">
                    <label>Ваш ответ:</label>
                    <textarea name="reply_text" id="replyText" rows="6" required placeholder="Введите сообщение пользователю..."></textarea>
                </div>
                <div class="form-group">
                    <label><input type="checkbox" name="mark_read" value="1" checked> Отметить заявку как прочитанную</label>
                </div>
                <div class="form-actions" style="margin-top:15px;">
                    <button type="submit" class="btn btn-success">Отправить ответ</button>
                    <button type="button" class="btn btn-secondary" onclick="closeReplyModal()">Отмена</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function setTab(tab) {
    document.getElementById('status_tab_input').value = tab;
    document.getElementById('filterForm').submit();
}

function openReplyModal(requestId, subject) {
    document.getElementById('replyRequestId').value = requestId;
    document.getElementById('replyModalTitle').innerHTML = 'Ответ на заявку #' + requestId + ': ' + subject;
    document.getElementById('replyText').value = '';
    document.getElementById('replyModal').style.display = 'flex';
}
function closeReplyModal() {
    document.getElementById('replyModal').style.display = 'none';
}
document.getElementById('replyModal').addEventListener('click', function(e) {
    if (e.target === this) closeReplyModal();
});
</script>

</body>
</html>