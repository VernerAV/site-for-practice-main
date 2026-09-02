<?php
session_start();
require_once 'config.php';
require_once 'check_auth.php';

if (!isAdmin()) { // только admin
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Доступ запрещён']);
    exit;
}
// остальной код без изменений...
?>