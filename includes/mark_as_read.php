<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header('Location: ../login.php');
    exit();
}

$request_id = (int)($_POST['request_id'] ?? 0);
$return_tab = $_POST['return_tab'] ?? 'requests';
$user_email = $_SESSION['user_email'];

if ($request_id > 0) {
    try {
        $sql = "UPDATE message SET is_read = 1 
                WHERE id = :id AND user_email = :user_email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $request_id,
            ':user_email' => $user_email
        ]);
    } catch (PDOException $e) {
        error_log("mark_as_read error: " . $e->getMessage());
    }
}

header("Location: ../user.php?tab=" . urlencode($return_tab));
exit();