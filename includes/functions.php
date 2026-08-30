<?php
/**
 * Сохранить заявку (гостевую) в таблицу message
 */
function saveRequest($data) {
    global $pdo;

    $user_name = $data['user_name'] ?? '';
    $user_email = $data['user_email'] ?? '';
    $phone = $data['phone'] ?? '';
    $address = $data['address'] ?? '';
    $category_id = (int)$data['category_id'];
    $subject = $data['subject'] ?? '';
    $message_text = $data['message'] ?? '';
    $work_type = $data['work_type'] ?? 'field';
    $urgency = $data['urgency'] ?? 'normal';
    $volume = $data['volume'] ?? 'medium';
    $floor = isset($data['floor']) ? (int)$data['floor'] : null;
    $has_elevator = isset($data['has_elevator']) ? (int)$data['has_elevator'] : 1;
    $materials_needed = isset($data['materials_needed']) ? (int)$data['materials_needed'] : 0;
    $estimated_hours = isset($data['estimated_hours']) ? (float)$data['estimated_hours'] : null;

    $sql = "INSERT INTO message 
            (user_name, user_email, phone, address, category_id, subject, message, 
             work_type, urgency, volume, floor, has_elevator, materials_needed, estimated_hours,
             created_at, status, is_read)
            VALUES 
            (:name, :email, :phone, :addr, :cat_id, :subj, :msg,
             :work_type, :urgency, :volume, :floor, :has_elevator, :materials, :est_hours,
             NOW(), 'новая', 0)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $user_name,
        ':email' => $user_email,
        ':phone' => $phone,
        ':addr' => $address,
        ':cat_id' => $category_id,
        ':subj' => $subject,
        ':msg' => $message_text,
        ':work_type' => $work_type,
        ':urgency' => $urgency,
        ':volume' => $volume,
        ':floor' => $floor,
        ':has_elevator' => $has_elevator,
        ':materials' => $materials_needed,
        ':est_hours' => $estimated_hours
    ]);
    return $pdo->lastInsertId();
}

/**
 * Рассчитать примерное время выполнения заявки
 */
function calculateEstimatedHours($category_id, $work_type, $volume, $urgency, $floor, $has_elevator, $materials_needed) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT base_hours FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $base = $stmt->fetchColumn();
    if (!$base) $base = 1.0;

    $volume_factors = ['small' => 0.8, 'medium' => 1.0, 'large' => 1.5];
    $urgency_factors = ['normal' => 1.0, 'high' => 1.2, 'emergency' => 1.5];

    $hours = $base * ($volume_factors[$volume] ?? 1.0) * ($urgency_factors[$urgency] ?? 1.0);

    if ($work_type === 'field' && $floor && $floor > 5 && !$has_elevator) {
        $hours += ($floor - 5) * 0.2;
    }
    if ($materials_needed) {
        $hours += 0.5;
    }

    return round($hours, 2);
}

/**
 * Найти лучшего исполнителя с наименьшей текущей загрузкой
 */
function findBestExecutor($category_id, $work_type, $urgency = 'normal', $estimated_hours = null) {
    global $pdo;

    // 1. Найти отдел по категории
    $dept_stmt = $pdo->prepare("SELECT id, department_name FROM department_rules WHERE category_id = ? LIMIT 1");
    $dept_stmt->execute([$category_id]);
    $department = $dept_stmt->fetch();
    if (!$department) {
        error_log("findBestExecutor: отдел не найден для category_id=$category_id");
        return null;
    }

    // 2. Найти все должности для этого отдела
    $pos_stmt = $pdo->prepare("
        SELECT p.name 
        FROM department_positions dp
        JOIN positions p ON dp.position_id = p.id
        WHERE dp.department_rule_id = ?
    ");
    $pos_stmt->execute([$department['id']]);
    $positions = $pos_stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($positions)) {
        error_log("findBestExecutor: нет должностей для отдела {$department['department_name']}");
        return null;
    }

    $placeholders = implode(',', array_fill(0, count($positions), '?'));

    // 3. Основной запрос – ищем исполнителя с минимальным количеством активных заявок и наименьшей загрузкой
    $sql = "
        SELECT u.id
        FROM users u
        JOIN user_profiles up ON u.id = up.user_id
        WHERE u.role = 'executor'
          AND u.is_active = 1
          AND up.department = ?
          AND up.position IN ($placeholders)
          AND (up.work_type = ? OR up.work_type = 'both')
        ORDER BY 
            (SELECT COUNT(*) FROM message m2 WHERE m2.assigned_to = u.id AND m2.status IN ('новая', 'в работе')) ASC,
            COALESCE((SELECT SUM(estimated_hours) FROM message m2 WHERE m2.assigned_to = u.id AND m2.status IN ('новая', 'в работе')), 0) ASC
        LIMIT 1
    ";

    $params = array_merge([$department['department_name']], $positions, [$work_type]);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();

    if ($result) {
        error_log("findBestExecutor: найден исполнитель ID=" . $result['id'] . " для category_id=$category_id, urgency=$urgency");
        return (int)$result['id'];
    } else {
        error_log("findBestExecutor: исполнитель не найден для department={$department['department_name']}, work_type=$work_type");
        return null;
    }
}

/**
 * Запись в журнал назначений
 */
function logAssignment($request_id, $executor_id, $type = 'auto', $comment = '') {
    global $pdo;
    $sql = "INSERT INTO assignment_log (request_id, request_type, assigned_to, assigned_at, type, performed_by, comment)
            VALUES (?, 'guest', ?, NOW(), ?, 1, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$request_id, $executor_id, $type, $comment]);
}