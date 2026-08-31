<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_GET['department_id']) || empty($_GET['department_id'])) {
    echo json_encode([]);
    exit;
}

$department_id = (int)$_GET['department_id'];

try {
    $stmt = $pdo->prepare("
        SELECT MIN(p.id) AS id, p.name
        FROM department_positions dp
        JOIN positions p ON dp.position_id = p.id
        WHERE dp.department_rule_id = ?
        GROUP BY p.name
        ORDER BY p.name
    ");
    $stmt->execute([$department_id]);
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($positions);
} catch (PDOException $e) {
    error_log("get_positions_by_department: " . $e->getMessage());
    echo json_encode([]);
}