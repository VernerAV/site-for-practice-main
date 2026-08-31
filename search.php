<?php
// search.php - Полноценный поиск по всему сайту
require_once 'includes/config.php';

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$results = [];
$totalResults = 0;
$categories = [];
$error = '';

// ==================== ФУНКЦИИ ====================
function highlightText($text, $searchWords) {
    if (empty($text) || empty($searchWords)) return htmlspecialchars($text);
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

function createExcerpt($text, $searchWords, $length = 200) {
    $cleanText = strip_tags($text);
    $cleanText = str_replace(["\r", "\n"], ' ', $cleanText);
    if (empty($searchWords)) {
        if (strlen($cleanText) > $length) {
            return substr($cleanText, 0, $length) . '...';
        }
        return $cleanText;
    }
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
        $start = max(0, $bestPosition - 50);
        $excerpt = substr($cleanText, $start, $length);
        if ($start > 0) $excerpt = '...' . $excerpt;
        if (strlen($cleanText) > $start + $length) $excerpt .= '...';
    } else {
        $excerpt = substr($cleanText, 0, $length);
        if (strlen($cleanText) > $length) $excerpt .= '...';
    }
    return $excerpt;
}

// ==================== СПЕЦИАЛЬНЫЕ ДЕЙСТВИЯ ====================
$actions = [
    [
        'keywords' => ['график', 'отключение', 'вода', 'горячая вода', 'отключение воды'],
        'title' => 'График отключения горячей воды',
        'description' => 'Узнайте график отключения горячей воды в Москве на 2026 год. Проверьте даты отключения по своему адресу.',
        'url' => 'https://www.mos.ru/otvet-dom-i-dvor/grafik-otklucheniya-goryachei-vodi/',
        'type' => 'action',
        'relevance' => 5
    ],
    [
        'keywords' => ['прием', 'директор', 'записаться', 'график приема'],
        'title' => 'График приема населения',
        'description' => 'Время приёма директора, заместителей и главного инженера.',
        'url' => 'index.php#schedule',
        'type' => 'action',
        'relevance' => 5
    ],
    [
        'keywords' => ['показания', 'счетчики', 'передать показания', 'показания счетчиков'],
        'title' => 'Передать показания счетчиков',
        'description' => 'Передайте показания воды и тепла через портал Мос.ру',
        'url' => 'https://www.mos.ru/services/pokazaniya-vodi-i-tepla/',
        'type' => 'action',
        'relevance' => 5
    ],
    [
        'keywords' => ['оплатить', 'жкх', 'оплата жкх', 'коммунальные', 'епд'],
        'title' => 'Оплата услуг ЖКХ',
        'description' => 'Оплатите коммунальные услуги онлайн через портал Мос.ру',
        'url' => 'https://pgu.mos.ru/ru/application/guis/-47/',
        'type' => 'action',
        'relevance' => 5
    ],
    [
        'keywords' => ['заявка', 'ремонт', 'срочный ремонт', 'аварийная', 'вызвать мастера'],
        'title' => 'Оставить заявку на ремонт',
        'description' => 'Оформите заявку на ремонт в вашей квартире или в подъезде.',
        'url' => 'contact.php',
        'type' => 'action',
        'relevance' => 5
    ]
];

// ==================== ОСНОВНАЯ ЛОГИКА ПОИСКА ====================
if (!empty($query)) {
    try {
        $searchTerm = "%" . $query . "%";
        $words = explode(' ', $query);

        // 1. ПОИСК В НОВОСТЯХ
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

        // 2. ПОИСК В УСЛУГАХ
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

        // 3. ПОИСК В СТАТИЧЕСКИХ СТРАНИЦАХ
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
            foreach ($words as $word) {
                $word = trim($word);
                if (strlen($word) > 2) {
                    if (stripos($page['title'], $word) !== false) $titleMatch = true;
                    if (stripos($page['content'], $word) !== false) $contentMatch = true;
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

        // 4. ПОИСК В ДЕЙСТВИЯХ (специальные предложения)
        foreach ($actions as $action) {
            $match = false;
            // Проверяем, содержит ли запрос (целиком) хотя бы одно ключевое слово
            foreach ($action['keywords'] as $keyword) {
                // Если ключевое слово найдено в запросе (регистронезависимо)
                if (stripos($query, $keyword) !== false) {
                    $match = true;
                    break;
                }
            }
            // Также проверяем по отдельным словам, на случай если запрос состоит из нескольких слов
            if (!$match) {
                foreach ($words as $word) {
                    $word = trim($word);
                    if (strlen($word) < 2) continue;
                    foreach ($action['keywords'] as $keyword) {
                        if (stripos($keyword, $word) !== false || stripos($word, $keyword) !== false) {
                            $match = true;
                            break 2;
                        }
                    }
                }
            }
            if ($match) {
                $action['highlighted_title'] = highlightText($action['title'], $words);
                $action['highlighted_excerpt'] = highlightText($action['description'], $words);
                $action['excerpt'] = $action['description'];
                $action['date_formatted'] = '';
                $results[] = $action;
                $categories['actions'] = ($categories['actions'] ?? 0) + 1;
            }
        }
        // 5. СОРТИРОВКА ПО РЕЛЕВАНТНОСТИ
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
    <link rel="stylesheet" href="css/mobile_all.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/search.css">
    <style>
        /* Стили для популярных запросов */
        .search-suggestions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .suggested-queries {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .suggested-query {
            display: inline-block;
            padding: 8px 16px;
            background: #f0f0f0;
            border-radius: 20px;
            color: #333;
            text-decoration: none;
            transition: background 0.2s;
            font-size: 14px;
        }
        .suggested-query:hover {
            background: #007bff;
            color: #fff;
        }
        .result-type-action {
            background: #ff9800;
            color: #fff;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <?php 
    if (file_exists('templates/header.php')) {
        include 'templates/header.php';
    }
    ?>
    
    <main class="search-page">
        <div class="search-header">
            <h1 class="search-title">Поиск по сайту</h1>
            
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
                                <?php if (isset($categories['actions'])): ?>
                                    <span class="category-badge actions">Действия: <?php echo $categories['actions']; ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="results-container">
            <?php if (!empty($query)): ?>
                
                <?php if ($totalResults > 0): ?>
                    <div class="results-count">Результаты поиска (<?php echo $totalResults; ?>):</div>
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
                                        } elseif ($result['type'] == 'action') {
                                            echo '🔹 Действие';
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
                                    <a href="<?php echo htmlspecialchars($result['url']); ?>" <?php echo strpos($result['url'], 'http') === 0 ? 'target="_blank"' : ''; ?>>
                                        <?php echo $result['highlighted_title'] ?? htmlspecialchars($result['title']); ?>
                                    </a>
                                </h3>
                                
                                <?php if (!empty($result['highlighted_excerpt'])): ?>
                                    <div class="result-excerpt">
                                        <?php echo $result['highlighted_excerpt']; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="result-meta">
                                    <a href="<?php echo htmlspecialchars($result['url']); ?>" class="result-url" <?php echo strpos($result['url'], 'http') === 0 ? 'target="_blank"' : ''; ?>>
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
                        
                        <!-- Предложение заявки, если ничего не найдено -->
                        <div style="margin: 25px 0; padding: 20px; background: #f0f8ff; border-radius: 8px; border-left: 4px solid #007bff;">
                            <p><strong>Не нашли нужную информацию?</strong></p>
                            <p>Оставьте заявку на ремонт или задайте вопрос – мы поможем!</p>
                            <a href="contact.php" class="btn btn-primary" style="display: inline-block; margin-top: 10px; padding: 10px 25px; background: #007bff; color: #fff; border-radius: 4px; text-decoration: none;">Оставить заявку</a>
                        </div>
                        
                        <div class="search-tips">
                            <h3>Советы по поиску:</h3>
                            <ul>
                                <li>Убедитесь, что все слова написаны правильно</li>
                                <li>Попробуйте использовать другие ключевые слова</li>
                                <li>Попробуйте более общие запросы</li>
                                <li>Используйте меньше слов в запросе</li>
                            </ul>
                        </div>
                        
                        <!-- Популярные запросы -->
                        <div class="search-suggestions">
                            <h4>Возможно, вы ищете:</h4>
                            <div class="suggested-queries">
                                <a href="search.php?query=<?php echo urlencode('горячая вода'); ?>" class="suggested-query">горячая вода</a>
                                <a href="search.php?query=<?php echo urlencode('задолженность'); ?>" class="suggested-query">задолженность</a>
                                <a href="search.php?query=<?php echo urlencode('ремонт'); ?>" class="suggested-query">ремонт</a>
                                <a href="search.php?query=<?php echo urlencode('тарифы'); ?>" class="suggested-query">тарифы</a>
                                <a href="search.php?query=<?php echo urlencode('новости'); ?>" class="suggested-query">новости</a>
                                <a href="search.php?query=<?php echo urlencode('контакты'); ?>" class="suggested-query">контакты</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <h3>Введите поисковый запрос</h3>
                    <p>Найдите нужную информацию на нашем сайте с помощью поиска</p>
                    
                    <!-- Предложение заявки при пустом запросе -->
                    <div style="margin: 25px 0; padding: 20px; background: #f0f8ff; border-radius: 8px; border-left: 4px solid #28a745;">
                        <p><strong>Нужна помощь специалиста?</strong></p>
                        <p>Оставьте заявку на ремонт, и мы решим вашу проблему!</p>
                        <a href="contact.php" class="btn btn-primary" style="display: inline-block; margin-top: 10px; padding: 10px 25px; background: #28a745; color: #fff; border-radius: 4px; text-decoration: none;">Оставить заявку</a>
                    </div>
                    
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
                    
                    <!-- Популярные запросы при пустом поиске -->
                    <div class="search-suggestions">
                        <h4>Популярные запросы:</h4>
                        <div class="suggested-queries">
                            <a href="search.php?query=<?php echo urlencode('горячая вода'); ?>" class="suggested-query">горячая вода</a>
                            <a href="search.php?query=<?php echo urlencode('график отключения'); ?>" class="suggested-query">график отключения</a>
                            <a href="search.php?query=<?php echo urlencode('ремонт'); ?>" class="suggested-query">ремонт</a>
                            <a href="search.php?query=<?php echo urlencode('тарифы'); ?>" class="suggested-query">тарифы</a>
                            <a href="search.php?query=<?php echo urlencode('новости'); ?>" class="suggested-query">новости</a>
                            <a href="search.php?query=<?php echo urlencode('контакты'); ?>" class="suggested-query">контакты</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php 
    if (file_exists('templates/footer.php')) {
        include 'templates/footer.php';
    }
    ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('.search-input-large');
        if (searchInput && !searchInput.value) {
            searchInput.focus();
        }
    });
    </script>
</body>
</html>