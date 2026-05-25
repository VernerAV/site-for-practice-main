<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "SELECT * FROM services ORDER BY service_name";
    $stmt = $pdo->query($sql);
    $prices = $stmt->fetchAll();
    
    if (empty($prices)) {
        echo '<tr><td colspan="4" style="text-align: center;">Услуг пока нет</td></tr>';
    } else {
        foreach ($prices as $item) {
            $section = $_GET['section'] ?? 'price';
            echo '
            <tr>
                <td>' . htmlspecialchars($item['id']) . '</td>
                <td>' . htmlspecialchars($item['service_name']) . '</td>
                <td>' . htmlspecialchars($item['price']) . ' руб.' . ($item['unit'] ? ' / ' . htmlspecialchars($item['unit']) : '') . '</td>
                <td>
                    <button class="btn btn-primary" onclick="editPrice(' . $item['id'] . ', \'' . addslashes($item['service_name']) . '\', \'' . addslashes($item['description']) . '\', ' . $item['price'] . ', \'' . addslashes($item['unit']) . '\')">Редактировать</button>
                    <a href="includes/delete_price.php?id=' . $item['id'] . '" class="btn btn-danger" onclick="return confirm(\'Удалить эту услугу?\')">Удалить</a>
                </td>
            </tr>';
        }
    }
    
} catch (PDOException $e) {
    // Для отладки можно временно вывести ошибку
    echo '<tr><td colspan="4" style="text-align: center; color: red;">Ошибка загрузки услуг: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
}
?>