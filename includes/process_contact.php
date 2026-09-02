<?php
session_start();
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'errors' => ['Неверный метод']]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'errors' => ['Ошибка формата данных']]);
    exit;
}

// ===== КЛИЕНТСКАЯ ВАЛИДАЦИЯ =====
function validateStep(stepIndex) {
    let errors = [];
    const block = document.getElementById('messageBlock');

    // Шаг 0: Тип
    if (stepIndex === 0) {
        if (!formData.type) {
            errors.push('Выберите тип обращения (Заявка или Обращение)');
        }
    }

    // Шаг 1: Категория
    if (stepIndex === 1) {
        if (!formData.category_id) {
            errors.push('Выберите категорию');
        }
    }

    // Шаг 2: Срочность (только для заявок, не для общих категорий)
    if (stepIndex === 2 && formData.type === 'request' && !isCommonCategory()) {
        if (!formData.urgency) {
            errors.push('Выберите срочность');
        }
    }

    // Шаг 3: Объём
    if (stepIndex === 3 && formData.type === 'request' && !isCommonCategory()) {
        if (!formData.volume) {
            errors.push('Выберите объём работ');
        }
    }

    // Шаг 4: Адрес (только для заявок)
    if (stepIndex === 4 && formData.type === 'request') {
        const street = document.getElementById('street').value.trim();
        if (!street) {
            errors.push('Введите адрес');
        } else if (!isValidAddress(street)) {
            errors.push('Адрес не найден в списке допустимых. Выберите из подсказок.');
        }
        const isCommon = isCommonCategory();
        if (!isCommon) {
            const apartment = document.getElementById('apartment').value.trim();
            if (!apartment) {
                errors.push('Укажите номер квартиры');
            }
        }
    }

    // Шаг 5: Материалы
    if (stepIndex === 5 && formData.type === 'request' && !isCommonCategory()) {
        if (formData.materials_needed === null || formData.materials_needed === undefined) {
            errors.push('Выберите готовность к работе');
        }
    }

    // Шаг 6: Контакты
    if (stepIndex === 6) {
        const ln = document.getElementById('lastName').value.trim();
        const fn = document.getElementById('firstName').value.trim();
        const em = document.getElementById('userEmail').value.trim();
        const msg = document.getElementById('messageText').value.trim();
        if (ln.length < 2) errors.push('Фамилия должна содержать минимум 2 символа');
        if (fn.length < 2) errors.push('Имя должно содержать минимум 2 символа');
        if (!em || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) errors.push('Введите корректный email');
        if (msg.length < 10) errors.push('Опишите проблему подробнее (минимум 10 символов)');
    }

    // Шаг 7: Итог – здесь проверки не нужны, только финальная отправка

    // Показываем ошибки
    if (errors.length > 0) {
        block.innerHTML = `<div class="error-msg">${errors.join('<br>')}</div>`;
        return false;
    } else {
        block.innerHTML = '';
        return true;
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $cat_stmt = $pdo->prepare("SELECT work_type, name, base_hours FROM categories WHERE id = ?");
    $cat_stmt->execute([$data['category_id']]);
    $cat = $cat_stmt->fetch();
    if (!$cat) throw new Exception('Категория не найдена');
    $work_type = $cat['work_type'];
    $cat_name = $cat['name'];
// Обращение
if ($type === 'appeal') {
    $request_data = [
        'user_name' => $data['user_name'],
        'user_email' => $data['user_email'],
        'phone' => $data['phone'] ?? '',
        'address' => $data['address'] ?? '',
        'category_id' => $data['category_id'],
        'subject' => 'Обращение: ' . $cat_name,
        'message' => $data['message'],
        'work_type' => $work_type,
        'urgency' => 'normal',
        'volume' => 'medium',
        'floor' => null,
        'has_elevator' => 1,
        'materials_needed' => 0,
        'estimated_hours' => null
    ];
    $request_id = saveRequest($request_data);

    // ---- ДОБАВЛЯЕМ АВТОМАТИЧЕСКОЕ НАЗНАЧЕНИЕ ДЛЯ ОБРАЩЕНИЙ ----
    $executor_id = findBestExecutor($data['category_id'], $work_type, 'normal', null);
    if ($executor_id) {
        $assign_stmt = $pdo->prepare("UPDATE message SET assigned_to = ?, assigned_at = NOW(), assign_comment = 'Автоматическое назначение по нагрузке' WHERE id = ?");
        $assign_stmt->execute([$executor_id, $request_id]);
        logAssignment($request_id, $executor_id, 'auto', 'Автоматическое назначение по нагрузке');
        error_log("process_contact: обращение #$request_id назначено на исполнителя $executor_id");
    } else {
        error_log("process_contact: для обращения #$request_id не найден исполнитель");
    }
    // -----------------------------------------------------------

    echo json_encode(['success' => true, 'request_id' => $request_id, 'type' => 'appeal']);
    exit;
}

    // Заявка
    $estimated_hours = calculateEstimatedHours(
        $data['category_id'],
        $work_type,
        $data['volume'],
        $data['urgency'],
        isset($data['floor']) ? (int)$data['floor'] : null,
        isset($data['has_elevator']) ? (int)$data['has_elevator'] : 1,
        isset($data['materials_needed']) ? (int)$data['materials_needed'] : 0
    );

    $request_data = [
        'user_name' => $data['user_name'],
        'user_email' => $data['user_email'],
        'phone' => $data['phone'] ?? '',
        'address' => $data['address'] ?? '',
        'category_id' => $data['category_id'],
        'subject' => 'Заявка: ' . $cat_name,
        'message' => $data['message'],
        'work_type' => $work_type,
        'urgency' => $data['urgency'],
        'volume' => $data['volume'],
        'floor' => isset($data['floor']) ? (int)$data['floor'] : null,
        'has_elevator' => isset($data['has_elevator']) ? (int)$data['has_elevator'] : 1,
        'materials_needed' => isset($data['materials_needed']) ? (int)$data['materials_needed'] : 0,
        'estimated_hours' => $estimated_hours
    ];
    $request_id = saveRequest($request_data);

    // Автоматическое назначение
    $executor_id = findBestExecutor($data['category_id'], $work_type, $data['urgency'], $estimated_hours);
    if ($executor_id) {
        $assign_stmt = $pdo->prepare("UPDATE message SET assigned_to = ?, assigned_at = NOW(), assign_comment = 'Автоматическое назначение по нагрузке' WHERE id = ?");
        $assign_stmt->execute([$executor_id, $request_id]);
        logAssignment($request_id, $executor_id, 'auto', 'Автоматическое назначение по нагрузке');
        error_log("process_contact: заявка #$request_id назначена на исполнителя $executor_id");
    } else {
        error_log("process_contact: для заявки #$request_id не найден исполнитель");
    }

    echo json_encode(['success' => true, 'request_id' => $request_id, 'type' => 'request', 'estimated_hours' => $estimated_hours]);

} catch (PDOException $e) {
    error_log("Ошибка в process_contact: " . $e->getMessage());
    echo json_encode(['success' => false, 'errors' => ['Ошибка базы данных']]);
} catch (Exception $e) {
    error_log("Ошибка в process_contact: " . $e->getMessage());
    echo json_encode(['success' => false, 'errors' => [$e->getMessage()]]);
}