<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../includes/config.php';
require_once '../includes/check_auth.php';

header('Content-Type: application/json');
if (!isAdmin()) {
    echo json_encode([]);
    exit;
}
$department_id = (int)($_GET['department_id'] ?? 0);
if (!$department_id) {
    echo json_encode([]);
    exit;
}
try {
    $stmt = $pdo->prepare("
        SELECT p.id, p.name
        FROM positions p
        JOIN department_positions dp ON p.id = dp.position_id
        WHERE dp.department_rule_id = ?
        ORDER BY p.name
    ");
    $stmt->execute([$department_id]);
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($positions);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>