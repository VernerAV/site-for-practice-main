<?php
require_once 'config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = "SELECT * FROM services ORDER BY service_name";
    $stmt = $pdo->query($sql);
    $prices = $stmt->fetchAll();
    
    if (empty($prices)) {
        echo '<tr><td colspan="5" style="text-align: center;">Услуг пока нет</td></tr>';
    } else {
        foreach ($prices as $item) {
            // Защита от null
            $id = (int)($item['id'] ?? 0);
            $service_name = htmlspecialchars($item['service_name'] ?? '', ENT_QUOTES);
            $description = htmlspecialchars($item['description'] ?? '', ENT_QUOTES);
            $price = isset($item['price']) ? number_format((float)$item['price'], 2, '.', '') : '0.00';
            $unit = htmlspecialchars($item['unit'] ?? '', ENT_QUOTES);
            
            $js_name = addslashes($service_name);
            $js_desc = addslashes($description);
            $js_price = (float)($item['price'] ?? 0);
            $js_unit = addslashes($unit);
            
            echo '
            <tr>
                <td>' . $id . '</td>
                <td>' . $service_name . '</td>
                <td>' . $description . '</td>
                <td>' . $price . ' руб.' . ($unit ? ' / ' . $unit : '') . '</td>
                <td>
                    <button class="btn btn-primary" onclick="editPrice(' . $id . ', \'' . $js_name . '\', \'' . $js_desc . '\', ' . $js_price . ', \'' . $js_unit . '\')">Редактировать</button>
                    <a href="includes/delete_price.php?id=' . $id . '" class="btn btn-danger" onclick="return confirm(\'Удалить эту услугу?\')">Удалить</a>
                </td>
            </tr>';
        }
    }
} catch (PDOException $e) {
    echo '<tr><td colspan="5" style="text-align: center; color: red;">Ошибка загрузки услуг: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
}
?>