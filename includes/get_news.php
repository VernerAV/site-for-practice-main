<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "SELECT * FROM news ORDER BY created_at DESC";
    $stmt = $pdo->query($sql);
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($news)) {
        echo '<tr><td colspan="4" style="text-align: center;">Новостей пока нет</td></tr>';
    } else {
        foreach ($news as $item) {
            $id = (int)($item['id'] ?? 0);
            $title = htmlspecialchars($item['title'] ?? '', ENT_QUOTES);
            $description = htmlspecialchars($item['description'] ?? '', ENT_QUOTES);
            $created_at = isset($item['created_at']) ? date('d.m.Y H:i', strtotime($item['created_at'])) : '';
            
            $js_title = addslashes($title);
            $js_description = addslashes($description);
            
            echo '
            <tr>
                <td>' . $id . '</td>
                <td>' . $title . '</td>
                <td>' . $created_at . '</td>
                <td>
                    <button class="btn btn-primary" onclick="editNews(' . $id . ', \'' . $js_title . '\', \'' . $js_description . '\')">Редактировать</button>
                    <a href="includes/delete_news.php?id=' . $id . '" class="btn btn-danger" onclick="return confirm(\'Удалить эту новость?\')">Удалить</a>
                </td>
            </tr>';
        }
    }
} catch (PDOException $e) {
    echo '<tr><td colspan="4" style="text-align: center; color: red;">Ошибка загрузки новостей: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
}
?>