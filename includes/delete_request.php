<?php
session_start();
require_once 'check_auth.php';
checkAuth();
if (!isAdmin()) { header('Location: ../user.php'); exit; }
$id = (int)$_GET['id'];
require_once 'config.php';
$stmt = $pdo->prepare("DELETE FROM message WHERE id = ?");
$stmt->execute([$id]);
header('Location: ../admin.php?section=requests');