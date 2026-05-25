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
            $section = $_GET['section'] ?? 'news';
            // Экранируем специальные символы для JavaScript
            $js_title = htmlspecialchars($item['title'], ENT_QUOTES);
            $js_description = htmlspecialchars($item['description'], ENT_QUOTES);
            
            echo '
            <tr>
                <td>' . htmlspecialchars($item['id']) . '</td>
                <td>' . htmlspecialchars($item['title']) . '</td>
                <td>' . date('d.m.Y H:i', strtotime($item['created_at'])) . '</td>
                <td>
                    <button class="btn btn-primary" onclick="editNews(' . $item['id'] . ', \'' . addslashes($js_title) . '\', \'' . addslashes($js_description) . '\')">Редактировать</button>
                    <a href="includes/delete_news.php?id=' . $item['id'] . '" class="btn btn-danger" onclick="return confirm(\'Удалить эту новость?\')">Удалить</a>
                </td>
            </tr>';
        }
    }
    
} catch (PDOException $e) {
    // Для отладки можно вывести ошибку
    error_log("Database error: " . $e->getMessage());
    echo '<tr><td colspan="4" style="text-align: center; color: red;">Ошибка загрузки новостей: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
}
?>