<?php
/**
 * Функции для работы с заявками и распределением
 */

/**
 * Рассчитать примерное время выполнения заявки
 */
function calculateEstimatedHours($category_id, $type, $volume, $urgency, $floor, $has_elevator, $materials_needed) {
    global $pdo;
    
    // Базовое время из категории
    $stmt = $pdo->prepare("SELECT base_hours FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $base = $stmt->fetchColumn();
    if (!$base) $base = 1.0;
    
    // Коэффициенты
    $volume_factors = ['small' => 0.8, 'medium' => 1.0, 'large' => 1.5];
    $urgency_factors = ['normal' => 1.0, 'high' => 1.2, 'emergency' => 1.5];
    
    $hours = $base * ($volume_factors[$volume] ?? 1.0) * ($urgency_factors[$urgency] ?? 1.0);
    
    // Если выездная и есть этаж
    if ($type === 'field' && $floor && $floor > 5 && !$has_elevator) {
        $hours += ($floor - 5) * 0.2; // +0.2 ч за каждый этаж выше 5-го
    }
    
    // Если нужны материалы
    if ($materials_needed) {
        $hours += 0.5;
    }
    
    return round($hours, 2);
}

/**
 * Найти лучшего исполнителя (с минимальной загрузкой)
 */
function findBestExecutor($category_id, $work_type, $estimated_hours) {
    global $pdo;
    
    // 1. Найти отдел по категории
    $dept_stmt = $pdo->prepare("SELECT id, department_name FROM department_rules WHERE category_id = ? LIMIT 1");
    $dept_stmt->execute([$category_id]);
    $department = $dept_stmt->fetch();
    if (!$department) return null;
    
    // 2. Найти должности для этого отдела
    $pos_stmt = $pdo->prepare("SELECT position_id FROM department_positions WHERE department_rule_id = ?");
    $pos_stmt->execute([$department['id']]);
    $position_ids = $pos_stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($position_ids)) return null;
    
    $placeholders = implode(',', array_fill(0, count($position_ids), '?'));
    
    // 3. Найти сотрудников с нужным отделом, должностью, типом работ и активных
    //    И подсчитать их текущую загрузку (сумма estimated_hours активных заявок того же типа работ)
    $sql = "
        SELECT u.id,
               COALESCE(SUM(m.estimated_hours), 0) as current_load
        FROM users u
        JOIN user_profiles up ON u.id = up.user_id
        LEFT JOIN message m ON u.id = m.assigned_to 
            AND m.status IN ('новая', 'в работе')
            AND m.work_type = :work_type
        WHERE u.role = 'executor'
          AND u.is_active = 1
          AND up.department = :department
          AND up.position IN (SELECT name FROM positions WHERE id IN ($placeholders))
          AND (up.work_type = :work_type OR up.work_type = 'both')
        GROUP BY u.id
        ORDER BY current_load ASC
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $params = array_merge([$department['department_name']], $position_ids);
    $stmt->execute(array_combine(
        array_merge([':work_type', ':department'], array_fill(0, count($position_ids), '?')),
        array_merge([$work_type, $department['department_name']], $position_ids)
    ));
    // Проще переделаем с использованием именованных параметров
    // Перепишем запрос с явными плейсхолдерами
    $sql = "
        SELECT u.id,
               COALESCE(SUM(m.estimated_hours), 0) as current_load
        FROM users u
        JOIN user_profiles up ON u.id = up.user_id
        LEFT JOIN message m ON u.id = m.assigned_to 
            AND m.status IN ('новая', 'в работе')
            AND m.work_type = :work_type
        WHERE u.role = 'executor'
          AND u.is_active = 1
          AND up.department = :department
          AND up.position IN (SELECT name FROM positions WHERE id IN ($placeholders))
          AND (up.work_type = :work_type OR up.work_type = 'both')
        GROUP BY u.id
        ORDER BY current_load ASC
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $params = array_merge([':work_type' => $work_type, ':department' => $department['department_name']], $position_ids);
    // Так как плейсхолдеры для IN ($placeholders) – это позиционные параметры, а у нас есть именованные,
    // проще использовать позиционные для всего запроса:
    $sql = "
        SELECT u.id,
               COALESCE(SUM(m.estimated_hours), 0) as current_load
        FROM users u
        JOIN user_profiles up ON u.id = up.user_id
        LEFT JOIN message m ON u.id = m.assigned_to 
            AND m.status IN ('новая', 'в работе')
            AND m.work_type = ?
        WHERE u.role = 'executor'
          AND u.is_active = 1
          AND up.department = ?
          AND up.position IN (SELECT name FROM positions WHERE id IN ($placeholders))
          AND (up.work_type = ? OR up.work_type = 'both')
        GROUP BY u.id
        ORDER BY current_load ASC
        LIMIT 1
    ";
    $params = array_merge([$work_type, $department['department_name'], $work_type], $position_ids);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    return $result ? $result['id'] : null;
}

/**
 * Сохранить заявку (гостевую) в таблицу message
 */
function saveRequest($data) {
    global $pdo;
    
    // Подготовка данных
    $user_name = htmlspecialchars($data['user_name'] ?? '', ENT_QUOTES);
    $user_email = htmlspecialchars($data['user_email'] ?? '', ENT_QUOTES);
    $phone = htmlspecialchars($data['phone'] ?? '', ENT_QUOTES);
    $address = htmlspecialchars($data['address'] ?? '', ENT_QUOTES);
    $category_id = (int)$data['category_id'];
    $subject = htmlspecialchars($data['subject'] ?? '', ENT_QUOTES);
    $message_text = htmlspecialchars($data['message'] ?? '', ENT_QUOTES);
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
 * Запись в журнал назначений
 */
function logAssignment($request_id, $executor_id, $type = 'auto', $comment = '') {
    global $pdo;
    $sql = "INSERT INTO assignment_log (request_id, request_type, assigned_to, assigned_at, type, performed_by, comment)
            VALUES (?, 'guest', ?, NOW(), ?, 1, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$request_id, $executor_id, $type, $comment]);
}
?>