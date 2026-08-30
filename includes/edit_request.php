<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/check_auth.php';
checkAuth();

if (!isAdmin()) {
    header('Location: user.php');
    exit();
}

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$request_id) {
    header('Location: admin.php?section=requests');
    exit();
}

// Получаем данные заявки
$stmt = $pdo->prepare("SELECT m.*, c.name as category_name, u.email as assigned_email, 
                        CONCAT(up.last_name, ' ', up.first_name) as assigned_name
                        FROM message m
                        LEFT JOIN categories c ON m.category_id = c.id
                        LEFT JOIN users u ON m.assigned_to = u.id
                        LEFT JOIN user_profiles up ON u.id = up.user_id
                        WHERE m.id = ?");
$stmt->execute([$request_id]);
$request = $stmt->fetch();
if (!$request) {
    header('Location: admin.php?section=requests');
    exit();
}

// Обработка сохранения
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $user_name = trim($_POST['user_name'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $user_email = trim($_POST['user_email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message_text = trim($_POST['message'] ?? '');
    $status = $_POST['status'] ?? 'новая';
    $category_id = (int)($_POST['category_id'] ?? 0);
    $urgency = $_POST['urgency'] ?? 'normal';
    $volume = $_POST['volume'] ?? 'medium';
    $floor = ($_POST['floor'] !== '') ? (int)$_POST['floor'] : null;
    $has_elevator = isset($_POST['has_elevator']) ? (int)$_POST['has_elevator'] : 1;
    $materials_needed = isset($_POST['materials_needed']) ? (int)$_POST['materials_needed'] : 0;
    $assigned_to = $_POST['assigned_to'] ? (int)$_POST['assigned_to'] : null;
    $admin_response = trim($_POST['admin_response'] ?? '');

    try {
        $pdo->beginTransaction();

        // Обновляем message
        $sql = "UPDATE message SET
                    user_name = :user_name,
                    first_name = :first_name,
                    last_name = :last_name,
                    middle_name = :middle_name,
                    user_email = :user_email,
                    phone = :phone,
                    address = :address,
                    subject = :subject,
                    message = :message,
                    status = :status,
                    category_id = :category_id,
                    urgency = :urgency,
                    volume = :volume,
                    floor = :floor,
                    has_elevator = :has_elevator,
                    materials_needed = :materials_needed,
                    assigned_to = :assigned_to,
                    admin_response = :admin_response,
                    responded_at = NOW(),
                    responded_by = :admin_id
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_name' => $user_name,
            ':first_name' => $first_name,
            ':last_name' => $last_name,
            ':middle_name' => $middle_name,
            ':user_email' => $user_email,
            ':phone' => $phone,
            ':address' => $address,
            ':subject' => $subject,
            ':message' => $message_text,
            ':status' => $status,
            ':category_id' => $category_id,
            ':urgency' => $urgency,
            ':volume' => $volume,
            ':floor' => $floor,
            ':has_elevator' => $has_elevator,
            ':materials_needed' => $materials_needed,
            ':assigned_to' => $assigned_to,
            ':admin_response' => $admin_response,
            ':admin_id' => $_SESSION['user_id'],
            ':id' => $request_id
        ]);

        $pdo->commit();
        $_SESSION['admin_message'] = "Заявка #$request_id успешно обновлена";
        $_SESSION['admin_message_type'] = 'success';
        header('Location: admin.php?section=requests');
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = 'Ошибка БД: ' . $e->getMessage();
    }
}

// Получаем список категорий
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

$employees = $pdo->query("
    SELECT 
        u.id, 
        CONCAT(up.last_name, ' ', up.first_name) as full_name,
        COALESCE(SUM(m.estimated_hours), 0) as current_load
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN message m ON u.id = m.assigned_to AND m.status IN ('новая', 'в работе')
    WHERE u.role IN ('executor','dispatcher','moderator')
    GROUP BY u.id, up.last_name, up.first_name
    ORDER BY current_load ASC, up.last_name, up.first_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование заявки #<?= $request_id ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .edit-container {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .edit-container h2 {
            margin-top: 0;
            color: #2c3e50;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
        }
        .btn {
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-primary { background: #3498db; color: #fff; }
        .btn-primary:hover { background: #2980b9; }
        .btn-secondary { background: #95a5a6; color: #fff; }
        .btn-secondary:hover { background: #7f8c8d; }
        .btn-danger { background: #e74c3c; color: #fff; }
        .btn-danger:hover { background: #c0392b; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        .back-link { display: inline-block; margin-top: 20px; }
        @media (max-width: 600px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="edit-container">
    <h2>✏️ Редактирование заявки #<?= $request_id ?></h2>

    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <!-- Основные данные -->
        <div class="form-row">
            <div class="form-group">
                <label>Фамилия</label>
                <input type="text" name="last_name" value="<?= htmlspecialchars($request['last_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Имя</label>
                <input type="text" name="first_name" value="<?= htmlspecialchars($request['first_name'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Отчество</label>
            <input type="text" name="middle_name" value="<?= htmlspecialchars($request['middle_name'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Полное имя (отображается)</label>
            <input type="text" name="user_name" value="<?= htmlspecialchars($request['user_name']) ?>" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="user_email" value="<?= htmlspecialchars($request['user_email']) ?>" required>
            </div>
            <div class="form-group">
                <label>Телефон</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($request['phone'] ?? '') ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Адрес</label>
            <input type="text" name="address" value="<?= htmlspecialchars($request['address'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Тема *</label>
            <input type="text" name="subject" value="<?= htmlspecialchars($request['subject']) ?>" required>
        </div>
        <div class="form-group">
            <label>Сообщение *</label>
            <textarea name="message" rows="5" required><?= htmlspecialchars($request['message']) ?></textarea>
        </div>

        <!-- Дополнительные поля -->
        <div class="form-row">
            <div class="form-group">
                <label>Категория</label>
                <select name="category_id">
                    <option value="">Не выбрана</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $request['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Статус</label>
                <select name="status">
                    <option value="новая" <?= $request['status'] == 'новая' ? 'selected' : '' ?>>Новая</option>
                    <option value="в работе" <?= $request['status'] == 'в работе' ? 'selected' : '' ?>>В работе</option>
                    <option value="выполнена" <?= $request['status'] == 'выполнена' ? 'selected' : '' ?>>Выполнена</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Срочность</label>
                <select name="urgency">
                    <option value="normal" <?= $request['urgency'] == 'normal' ? 'selected' : '' ?>>Обычная</option>
                    <option value="high" <?= $request['urgency'] == 'high' ? 'selected' : '' ?>>Высокая</option>
                    <option value="emergency" <?= $request['urgency'] == 'emergency' ? 'selected' : '' ?>>Срочная</option>
                </select>
            </div>
            <div class="form-group">
                <label>Объём работ</label>
                <select name="volume">
                    <option value="small" <?= $request['volume'] == 'small' ? 'selected' : '' ?>>Мелкий</option>
                    <option value="medium" <?= $request['volume'] == 'medium' ? 'selected' : '' ?>>Средний</option>
                    <option value="large" <?= $request['volume'] == 'large' ? 'selected' : '' ?>>Крупный</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Этаж</label>
                <input type="number" name="floor" value="<?= $request['floor'] ?? '' ?>">
            </div>
            <div class="form-group">
                <label>Наличие лифта</label>
                <select name="has_elevator">
                    <option value="1" <?= $request['has_elevator'] == 1 ? 'selected' : '' ?>>Да</option>
                    <option value="0" <?= $request['has_elevator'] == 0 ? 'selected' : '' ?>>Нет</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Материалы готовы?</label>
            <select name="materials_needed">
                <option value="0" <?= $request['materials_needed'] == 0 ? 'selected' : '' ?>>Да, всё есть</option>
                <option value="1" <?= $request['materials_needed'] == 1 ? 'selected' : '' ?>>Нет, нужно закупить</option>
                <option value="2" <?= $request['materials_needed'] == 2 ? 'selected' : '' ?>>Не знаю</option>
            </select>
        </div>

        <!-- Блок назначения с отображением загрузки -->
        <div class="form-group">
            <label>Назначить исполнителя</label>
            <select name="assigned_to">
                <option value="">Не назначен</option>
                <?php foreach ($employees as $emp): 
                    $selected = ($request['assigned_to'] == $emp['id']) ? 'selected' : '';
                    $loadText = $emp['current_load'] > 0 ? ' (загрузка: ' . number_format($emp['current_load'], 1) . ' ч)' : ' (свободен)';
                ?>
                    <option value="<?= $emp['id'] ?>" <?= $selected ?>>
                        <?= htmlspecialchars($emp['full_name'] ?: $emp['id']) . $loadText ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Ответ администратора</label>
            <textarea name="admin_response" rows="3"><?= htmlspecialchars($request['admin_response'] ?? '') ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" name="save" class="btn btn-primary">💾 Сохранить изменения</button>
            <a href="admin.php?section=requests" class="btn btn-secondary">← Назад к списку</a>
        </div>
    </form>
</div>

</body>
</html>