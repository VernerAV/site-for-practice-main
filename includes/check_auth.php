<?php
function checkAuth() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../login.php');
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function getUserRole() {
    return $_SESSION['user_role'] ?? 'user';
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function isModerator() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'moderator';
}

function isAdminOrModerator() {
    return isAdmin() || isModerator();
}
?>