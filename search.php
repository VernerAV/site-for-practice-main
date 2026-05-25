<?php
// search.php - Полноценный поиск по всему сайту
require_once 'includes/config.php';

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$results = [];
$totalResults = 0;
$categories = [];
$error = '';

if (!empty($query)) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $searchTerm = "%" . $query . "%";
        $words = explode(' ', $query);
        
        // Функция для подсветки текста
        function highlightText($text, $searchWords) {
            if (empty($text) || empty($searchWords)) return htmlspecialchars($text);
            
            // Очищаем текст от HTML тегов для подсветки
            $cleanText = strip_tags($text);
            
            foreach ($searchWords as $word) {
                $word = trim($word);
                if (strlen($word) > 2) {
                    $cleanText = preg_replace(
                        '/(' . preg_quote($word, '/') . ')/iu', 
                        '<span class="highlight">$1</span>', 
                        $cleanText
                    );
                }
            }
            return $cleanText;
        }
        
        // Функция для создания отрывка текста
        function createExcerpt($text, $searchWords, $length = 200) {
            $cleanText = strip_tags($text);
            $cleanText = str_replace(["\r", "\n"], ' ', $cleanText);
            
            if (empty($searchWords)) {
                // Если нет слов для поиска, берем начало текста
                if (strlen($cleanText) > $length) {
                    return substr($cleanText, 0, $length) . '...';
                }
                return $cleanText;
            }
            
            // Находим первое вхождение любого из слов поиска
            $bestPosition = null;
            foreach ($searchWords as $word) {
                $word = trim($word);
                if (strlen($word) > 2) {
                    $pos = stripos($cleanText, $word);
                    if ($pos !== false && ($bestPosition === null || $pos < $bestPosition)) {
                        $bestPosition = $pos;
                    }
                }
            }
            
            if ($bestPosition !== null) {
                // Берем текст вокруг найденного слова
                $start = max(0, $bestPosition - 50);
                $excerpt = substr($cleanText, $start, $length);
                
                // Добавляем многоточие если текст обрезан
                if ($start > 0) {
                    $excerpt = '...' . $excerpt;
                }
                if (strlen($cleanText) > $start + $length) {
                    $excerpt = $excerpt . '...';
                }
            } else {
                // Если слов не найдено, берем начало текста
                $excerpt = substr($cleanText, 0, $length);
                if (strlen($cleanText) > $length) {
                    $excerpt .= '...';
                }
            }
            
            return $excerpt;
        }
        
        // ПОИСК В НОВОСТЯХ
        $stmt = $pdo->prepare("
            SELECT 
                'news' as type,
                id,
                title,
                description,
                created_at as date
            FROM news 
            WHERE title LIKE ? OR description LIKE ?
            ORDER BY created_at DESC
            LIMIT 20
        ");
        
        $stmt->execute([$searchTerm, $searchTerm]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['url'] = 'news.php?id=' . $row['id'];
            $row['excerpt'] = createExcerpt($row['description'], $words);
            $row['highlighted_excerpt'] = highlightText($row['excerpt'], $words);
            $row['highlighted_title'] = highlightText($row['title'], $words);
            $row['date_formatted'] = date('d.m.Y', strtotime($row['date']));
            $row['relevance'] = 1;
            
            $results[] = $row;
            $categories['news'] = ($categories['news'] ?? 0) + 1;
        }
        
        // ПОИСК В УСЛУГАХ (ПРАЙС-ЛИСТ)
        $stmt = $pdo->prepare("
            SELECT 
                'service' as type,
                id,
                service_name as title,
                description as content
            FROM services 
            WHERE service_name LIKE ? OR description LIKE ?
            ORDER BY service_name
            LIMIT 20
        ");
        
        $stmt->execute([$searchTerm, $searchTerm]);
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['url'] = 'price.php';
            $row['excerpt'] = createExcerpt($row['content'], $words);
            $row['highlighted_excerpt'] = highlightText($row['excerpt'], $words);
            $row['highlighted_title'] = highlightText($row['title'], $words);
            $row['date_formatted'] = '';
            $row['relevance'] = 1;
            
            $results[] = $row;
            $categories['services'] = ($categories['services'] ?? 0) + 1;
        }
        
        // ПОИСК В СТАТИЧЕСКИХ СТРАНИЦАХ
        $staticPages = [
            [
                'type' => 'page',
                'title' => 'О компании',
                'content' => 'Государственное бюджетное учреждение города Москвы «Жилищник района Строгино». Контактная информация, реквизиты организации.',
                'url' => 'about.php'
            ],
            [
                'type' => 'page', 
                'title' => 'Прайс-лист услуг',
                'content' => 'Актуальные цены на все услуги управляющей компании. Стоимость работ по содержанию и ремонту жилого фонда.',
                'url' => 'price.php'
            ],
            [
                'type' => 'page',
                'title' => 'Контакты',
                'content' => 'Контактная информация, адреса, телефоны, электронная почта для связи с управляющей компанией.',
                'url' => 'about.php'
            ],
            [
                'type' => 'page',
                'title' => 'Новости',
                'content' => 'Актуальные новости и объявления управляющей компании. Графики отключения воды, собрания жильцов, важная информация.',
                'url' => 'news.php'
            ]
        ];
        
        foreach ($staticPages as $page) {
            $titleMatch = false;
            $contentMatch = false;
            
            // Проверяем совпадение в заголовке
            foreach ($words as $word) {
                $word = trim($word);
                if (strlen($word) > 2) {
                    if (stripos($page['title'], $word) !== false) {
                        $titleMatch = true;
                    }
                    if (stripos($page['content'], $word) !== false) {
                        $contentMatch = true;
                    }
                }
            }
            
            if ($titleMatch || $contentMatch) {
                $page['excerpt'] = createExcerpt($page['content'], $words);
                $page['highlighted_excerpt'] = highlightText($page['excerpt'], $words);
                $page['highlighted_title'] = highlightText($page['title'], $words);
                $page['date_formatted'] = '';
                $page['relevance'] = $titleMatch ? 2 : 1;
                
                $results[] = $page;
                $categories['pages'] = ($categories['pages'] ?? 0) + 1;
            }
        }
        
        // Сортируем результаты по релевантности
        usort($results, function($a, $b) {
            return ($b['relevance'] ?? 0) <=> ($a['relevance'] ?? 0);
        });
        
        $totalResults = count($results);
        
    } catch (PDOException $e) {
        $error = "Ошибка базы данных: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск по сайту: <?php echo htmlspecialchars($query); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/search.css">
</head>
<body>
    <!-- Подключаем хедер -->
    <?php 
    if (file_exists('templates/header.php')) {
        include 'templates/header.php';
    }
    ?>
    
    <main class="search-page">
        <!-- Шапка поиска -->
        <div class="search-header">
            <h1 class="search-title">Поиск по сайту</h1>
            
            <!-- Форма поиска -->
            <form action="search.php" method="get" class="search-form-large">
                <input type="text" 
                       name="query" 
                       value="<?php echo htmlspecialchars($query); ?>"
                       placeholder="Введите поисковый запрос..."
                       class="search-input-large"
                       autocomplete="off"
                       required>
                <button type="submit" class="search-button-large">Искать</button>
            </form>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Статистика поиска -->
            <?php if (!empty($query)): ?>
                <div class="search-stats">
                    <?php if ($totalResults > 0): ?>
                        <p>
                            Найдено результатов: <strong><?php echo $totalResults; ?></strong> 
                            по запросу "<span class="search-query"><?php echo htmlspecialchars($query); ?></span>"
                        </p>
                        
                        <?php if (!empty($categories)): ?>
                            <div class="search-categories">
                                <?php if (isset($categories['news'])): ?>
                                    <span class="category-badge news">Новости: <?php echo $categories['news']; ?></span>
                                <?php endif; ?>
                                <?php if (isset($categories['services'])): ?>
                                    <span class="category-badge services">Услуги: <?php echo $categories['services']; ?></span>
                                <?php endif; ?>
                                <?php if (isset($categories['pages'])): ?>
                                    <span class="category-badge pages">Страницы: <?php echo $categories['pages']; ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Результаты поиска -->
        <div class="results-container">
            <?php if (!empty($query)): ?>
                
                <?php if ($totalResults > 0): ?>
                    
                    <div class="results-count">
                        Результаты поиска (<?php echo $totalResults; ?>):
                    </div>
                    
                    <div class="results-list">
                        <?php foreach ($results as $result): ?>
                            <div class="result-item">
                                <div class="result-header">
                                    <span class="result-type result-type-<?php echo $result['type']; ?>">
                                        <?php 
                                        if ($result['type'] == 'news') {
                                            echo 'Новость';
                                        } elseif ($result['type'] == 'service') {
                                            echo 'Услуга';
                                        } elseif ($result['type'] == 'page') {
                                            echo 'Страница';
                                        } else {
                                            echo $result['type'];
                                        }
                                        ?>
                                    </span>
                                    
                                    <?php if (!empty($result['date_formatted'])): ?>
                                        <span class="result-date"><?php echo $result['date_formatted']; ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <h3 class="result-title">
                                    <a href="<?php echo htmlspecialchars($result['url']); ?>">
                                        <?php echo $result['highlighted_title'] ?? htmlspecialchars($result['title']); ?>
                                    </a>
                                </h3>
                                
                                <?php if (!empty($result['highlighted_excerpt'])): ?>
                                    <div class="result-excerpt">
                                        <?php echo $result['highlighted_excerpt']; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="result-meta">
                                    <a href="<?php echo htmlspecialchars($result['url']); ?>" class="result-url">
                                        <?php echo htmlspecialchars($result['url']); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                <?php else: ?>
                    
                    <div class="no-results">
                        <div class="no-results-icon">🔍</div>
                        <h3>По запросу "<span class="search-query"><?php echo htmlspecialchars($query); ?></span>" ничего не найдено</h3>
                        <p>Попробуйте изменить поисковый запрос или проверьте правильность написания.</p>
                        
                        <div class="search-tips">
                            <h3>Советы по поиску:</h3>
                            <ul>
                                <li>Убедитесь, что все слова написаны правильно</li>
                                <li>Попробуйте использовать другие ключевые слова</li>
                                <li>Попробуйте более общие запросы</li>
                                <li>Используйте меньше слов в запросе</li>
                            </ul>
                        </div>
                        
                        <!-- Предлагаем популярные запросы -->
                        <div class="search-suggestions">
                            <h4>Возможно, вы ищете:</h4>
                            <div class="suggested-queries">
                                <a href="search.php?query=горячая+вода" class="suggested-query">горячая вода</a>
                                <a href="search.php?query=задолженность" class="suggested-query">задолженность</a>
                                <a href="search.php?query=ремонт" class="suggested-query">ремонт</a>
                                <a href="search.php?query=тарифы" class="suggested-query">тарифы</a>
                                <a href="search.php?query=новости" class="suggested-query">новости</a>
                                <a href="search.php?query=контакты" class="suggested-query">контакты</a>
                            </div>
                        </div>
                    </div>
                    
                <?php endif; ?>
                
            <?php else: ?>
                
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <h3>Введите поисковый запрос</h3>
                    <p>Найдите нужную информацию на нашем сайте с помощью поиска</p>
                    
                    <div class="search-tips">
                        <h3>Что можно найти:</h3>
                        <ul>
                            <li><strong>Новости и объявления</strong> - актуальная информация от управляющей компании</li>
                            <li><strong>Услуги и цены</strong> - полный прайс-лист всех услуг</li>
                            <li><strong>Контакты</strong> - телефоны, адреса, электронная почта</li>
                            <li><strong>Документы</strong> - уставные документы, отчеты, лицензии</li>
                            <li><strong>Информацию по ЖКХ</strong> - тарифы, графики отключений</li>
                        </ul>
                    </div>
                </div>
                
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Подключаем футер -->
    <?php 
    if (file_exists('templates/footer.php')) {
        include 'templates/footer.php';
    }
    ?>
    
    <script>
    // Автоматический фокус на поле поиска
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('.search-input-large');
        if (searchInput && !searchInput.value) {
            searchInput.focus();
        }
    });
    </script>
</body>
</html>