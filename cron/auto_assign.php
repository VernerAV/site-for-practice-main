<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$sql = "SELECT id, category_id, work_type, estimated_hours 
        FROM message 
        WHERE assigned_to IS NULL AND status = 'новая'";
$stmt = $pdo->query($sql);
$requests = $stmt->fetchAll();

foreach ($requests as $req) {
    $executor_id = findBestExecutor($req['category_id'], $req['work_type'], $req['estimated_hours']);
    if ($executor_id) {
        $update = $pdo->prepare("UPDATE message SET assigned_to = ?, assigned_at = NOW(), assign_comment = 'Автоматическое перераспределение (cron)' WHERE id = ?");
        $update->execute([$executor_id, $req['id']]);
        logAssignment($req['id'], $executor_id, 'auto', 'Автоматическое перераспределение (cron)');
        echo "Заявка #{$req['id']} назначена на $executor_id\n";
    } else {
        echo "Для заявки #{$req['id']} не найден исполнитель\n";
    }
}