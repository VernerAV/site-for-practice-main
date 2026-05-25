<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "SELECT id, email, role, created_at FROM users ORDER BY created_at DESC";
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo '<tr><td colspan="5" style="text-align: center;">Пользователей нет</td></tr>';
    } else {
        foreach ($users as $user) {
            $section = $_GET['section'] ?? 'user';
            $role_badge = $user['role'] === 'admin' ? '<span style="color: #e74c3c; font-weight: bold;">Админ</span>' : 'Пользователь';
            
            echo '
            <tr>
                <td>' . htmlspecialchars($user['id']) . '</td>
                <td>' . htmlspecialchars($user['email']) . '</td>
                <td>' . $role_badge . '</td>
                <td>' . date('d.m.Y H:i', strtotime($user['created_at'])) . '</td>
                <td>';
                
            if ($user['role'] !== 'admin') {
                echo '<a href="includes/change_role.php?id=' . $user['id'] . '&role=admin" class="btn btn-primary">Сделать админом</a>';
            } else {
                echo '<a href="includes/change_role.php?id=' . $user['id'] . '&role=user" class="btn">Сделать пользователем</a>';
            }
                
            echo '</td>
            </tr>';
        }
    }
    
} catch (PDOException $e) {
    echo '<tr><td colspan="5" style="text-align: center; color: red;">Ошибка загрузки пользователей</td></tr>';
}
?>