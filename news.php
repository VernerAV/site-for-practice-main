<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новости - ГБУ "Жилищник Района Строгино"</title>
    <link rel="stylesheet" href="css/news.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Хедер -->
    <?php include 'templates/header.php'; ?>
    
    <main class="news-page">
        <!-- Закрепленная новость -->
        <section class="pinned-news">
            <div class="container">
                <a href="hot-water-schedule.php" class="pinned-card">
                    <div class="pinned-badge">
                        <i class="fas fa-thumbtack"></i>
                        Актуально
                    </div>
                    <div class="pinned-content">
                        <div class="pinned-text">
                            <h2>График отключения горячей воды</h2>
                            <p>Узнайте расписание профилактических работ в вашем доме. Проверьте даты отключения горячей воды для подготовки.</p>
                            <div class="pinned-meta">
                                <span class="meta-item">
                                    <i class="far fa-calendar"></i>
                                    Актуально до 31.08.2024
                                </span>
                                <span class="meta-item">
                                    <i class="fas fa-home"></i>
                                    Все дома района
                                </span>
                            </div>
                        </div>
                        <div class="pinned-icon">
                            <i class="fas fa-fire-alt"></i>
                        </div>
                    </div>
                    <div class="pinned-arrow">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </a>
            </div>
        </section>

        <!-- Основные новости -->
        <section class="news-list">
            <div class="container">
                <div class="section-header">
                    <h1>Все новости</h1>
                    <div class="news-filters">
                        <button class="filter-btn active" data-filter="all">Все</button>
                        <button class="filter-btn" data-filter="important">Важные</button>
                        <button class="filter-btn" data-filter="services">Услуги</button>
                        <button class="filter-btn" data-filter="repairs">Ремонты</button>
                    </div>
                </div>

                <div class="news-grid" id="newsGrid">
                    <?php
                    try {
                        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                        $pdo = new PDO($dsn, DB_USER, DB_PASS);
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        
                        // Получаем новости
                        $sql = "SELECT n.*, 
                                       DATE_FORMAT(n.created_at, '%d.%m.%Y') as formatted_date,
                                       DATE_FORMAT(n.created_at, '%H:%i') as formatted_time
                                FROM news n 
                                ORDER BY n.created_at DESC";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute();
                        $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (empty($news)) {
                            echo '<div class="no-news">
                                    <i class="far fa-newspaper"></i>
                                    <h3>Новостей пока нет</h3>
                                    <p>Следите за обновлениями на нашем сайте</p>
                                  </div>';
                        } else {
                            foreach ($news as $item) {
                                // Определяем категорию 
                                $category = getNewsCategory($item['title'], $item['description']);
                                $tags = getNewsTags($item['title'], $item['description']);
                                
                                $imageSrc = $item['image'] ? 'uploads/news/' . $item['image'] : 'images/default-news.jpg';
                                $description = mb_strlen($item['description']) > 150 
                                    ? mb_substr(strip_tags($item['description']), 0, 150) . '...' 
                                    : strip_tags($item['description']);
                                
                                echo '
                                <article class="news-card" data-category="' . $category . '">
                                    <div class="card-image">
                                        <img src="' . $imageSrc . '" alt="' . htmlspecialchars($item['title']) . '" 
                                             onerror="this.src=\'images/default-news.jpg\'">
                                        <div class="card-category">' . $category . '</div>
                                        <div class="card-date">
                                            <span class="date-day">' . date('d', strtotime($item['created_at'])) . '</span>
                                            <span class="date-month">' . getRussianMonth(date('m', strtotime($item['created_at']))) . '</span>
                                        </div>
                                    </div>
                                    <div class="card-content">
                                        <div class="card-header">
                                            <h3 class="card-title">' . htmlspecialchars($item['title']) . '</h3>
                                            <div class="card-time">
                                                <i class="far fa-clock"></i>
                                                ' . $item['formatted_time'] . '
                                            </div>
                                        </div>
                                        <p class="card-description">' . htmlspecialchars($description) . '</p>
                                        
                                        <div class="card-tags">';
                                
                                foreach ($tags as $tag) {
                                    echo '<span class="tag">' . $tag . '</span>';
                                }
                                
                                echo '</div>
                                        
                                        <div class="card-footer">
                                            <a href="news_details.php?id=' . $item['id'] . '" class="read-more">
                                                Читать подробнее
                                                <i class="fas fa-arrow-right"></i>
                                            </a>
                                            <div class="card-actions">
                                                <button class="action-btn share-btn" data-id="' . $item['id'] . '" title="Поделиться">
                                                    <i class="fas fa-share-alt"></i>
                                                </button>
                                                <button class="action-btn save-btn" data-id="' . $item['id'] . '" title="Сохранить">
                                                    <i class="far fa-bookmark"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </article>';
                            }
                        }
                    } catch (PDOException $e) {
                        echo '<div class="error-message">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h3>Ошибка загрузки новостей</h3>
                                <p>Попробуйте обновить страницу позже</p>
                              </div>';
                    }
                    
                    // Вспомогательные функции
                    function getNewsCategory($title, $description) {
                        $text = strtolower($title . ' ' . $description);
                        
                        if (strpos($text, 'ремонт') !== false || strpos($text, 'работ') !== false) {
                            return 'Ремонты';
                        } elseif (strpos($text, 'вода') !== false || strpos($text, 'отключ') !== false) {
                            return 'Услуги';
                        } elseif (strpos($text, 'важн') !== false || strpos($text, 'срочн') !== false) {
                            return 'Важные';
                        } else {
                            return 'Новости';
                        }
                    }
                    
                    function getNewsTags($title, $description) {
                        $text = strtolower($title . ' ' . $description);
                        $tags = [];
                        $allTags = [
                            'Вода', 'Отопление', 'Лифт', 'Электрика', 
                            'Двор', 'Благоустройство', 'Тарифы', 'Оплата'
                        ];
                        
                        foreach ($allTags as $tag) {
                            if (strpos($text, strtolower($tag)) !== false) {
                                $tags[] = $tag;
                                if (count($tags) >= 3) break;
                            }
                        }
                        
                        return $tags;
                    }
                    
                    function getRussianMonth($month) {
                        $months = [
                            '01' => 'янв', '02' => 'фев', '03' => 'мар', '04' => 'апр',
                            '05' => 'май', '06' => 'июн', '07' => 'июл', '08' => 'авг',
                            '09' => 'сен', '10' => 'окт', '11' => 'ноя', '12' => 'дек'
                        ];
                        return $months[$month] ?? $month;
                    }
                    ?>
                </div>

                <!-- Пагинация -->
                <div class="pagination">
                    <button class="pagination-btn prev" disabled>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span class="pagination-current">1</span>
                    <span class="pagination-total">из 5</span>
                    <button class="pagination-btn next">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </section>


    </main>

    <!-- Футер -->
    <?php include 'templates/footer.php'; ?>

    <script src="js/news.js"></script>
</body>
</html>