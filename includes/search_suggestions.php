<?php
// includes/search_suggestions.php - улучшенная версия
require_once 'config.php';

header('Content-Type: application/json');

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$suggestions = [];

if (strlen($query) >= 2) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $searchTerm = "%" . $query . "%";
        $words = explode(' ', $query);
        
        // Добавляем полнотекстовый поиск если есть индекс
        $hasFulltext = false;
        try {
            $stmt = $pdo->query("SHOW INDEX FROM news WHERE Key_name = 'title'");
            $hasFulltext = $stmt->rowCount() > 0;
        } catch (Exception $e) {
            $hasFulltext = false;
        }
        
        // Поиск в новостях
        if ($hasFulltext) {
            $ftQuery = implode('* ', $words) . '*';
            $sql = "
                SELECT title, 'news' as type
                FROM news 
                WHERE MATCH(title) AGAINST(? IN BOOLEAN MODE)
                ORDER BY MATCH(title) AGAINST(? IN BOOLEAN MODE) DESC
                LIMIT 5
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$ftQuery, $ftQuery]);
        } else {
            $sql = "
                SELECT title, 'news' as type
                FROM news 
                WHERE title LIKE ?
                ORDER BY created_at DESC
                LIMIT 5
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$searchTerm]);
        }
        
        $newsResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Поиск в услугах
        $stmt = $pdo->prepare("
            SELECT service_name as title, 'service' as type
            FROM services 
            WHERE service_name LIKE ? 
            ORDER BY service_name
            LIMIT 5
        ");
        $stmt->execute([$searchTerm]);
        $serviceResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Объединяем результаты
        $allResults = array_merge($newsResults, $serviceResults);
        
        // Добавляем статические подсказки
        $staticSuggestions = [
            ['title' => 'График отключения горячей воды', 'type' => 'page'],
            ['title' => 'Задолженность по ЖКУ', 'type' => 'page'],
            ['title' => 'Записаться на прием к директору', 'type' => 'page'],
            ['title' => 'Тарифы на коммунальные услуги', 'type' => 'page'],
            ['title' => 'Ремонт подъезда', 'type' => 'service'],
            ['title' => 'Вывоз мусора', 'type' => 'service'],
            ['title' => 'Уборка территории', 'type' => 'service'],
            ['title' => 'Контакты управляющей компании', 'type' => 'page']
        ];
        
        foreach ($staticSuggestions as $suggestion) {
            $titleLower = strtolower($suggestion['title']);
            $queryLower = strtolower($query);
            
            // Проверяем совпадение
            if (strpos($titleLower, $queryLower) !== false) {
                $allResults[] = $suggestion;
            }
            
            // Проверяем каждое слово
            foreach ($words as $word) {
                if (strlen($word) > 2 && strpos($titleLower, strtolower($word)) !== false) {
                    $allResults[] = $suggestion;
                    break;
                }
            }
        }
        
        // Убираем дубликаты и ограничиваем
        $uniqueResults = [];
        $seenTitles = [];
        
        foreach ($allResults as $result) {
            $title = $result['title'];
            if (!in_array($title, $seenTitles) && count($uniqueResults) < 8) {
                $uniqueResults[] = $result;
                $seenTitles[] = $title;
            }
        }
        
        // Формируем массив только заголовков для обратной совместимости
        $suggestions = array_map(function($item) {
            return $item['title'];
        }, $uniqueResults);
        
    } catch (PDOException $e) {
        error_log("Search suggestions error: " . $e->getMessage());
    }
}

echo json_encode($suggestions);
?>