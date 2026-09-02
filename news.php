<?php
require_once 'includes/config.php';
session_start();

// Настройки пагинации
$limit = 9; // новостей на страницу
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

try {
    // Подсчёт общего количества новостей
    $countStmt = $pdo->query("SELECT COUNT(*) FROM news");
    $total = $countStmt->fetchColumn();
    $totalPages = ceil($total / $limit);

    // Запрос новостей с пагинацией
    $stmt = $pdo->prepare("
        SELECT n.*, 
               DATE_FORMAT(n.created_at, '%d.%m.%Y') as formatted_date,
               DATE_FORMAT(n.created_at, '%H:%i') as formatted_time
        FROM news n 
        ORDER BY n.created_at DESC 
        LIMIT :offset, :limit
    ");
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $news = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $news = [];
    $totalPages = 0;
    error_log("News error: " . $e->getMessage());
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
    $allTags = ['Вода', 'Отопление', 'Лифт', 'Электрика', 'Двор', 'Благоустройство', 'Тарифы', 'Оплата'];
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
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новости - ГБУ "Жилищник Района Строгино"</title>
    <link rel="stylesheet" href="css/header_mobile.css">
    <link rel="stylesheet" href="css/style_mobile.css">
    <link rel="stylesheet" href="css/news.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Хедер -->
    <?php require 'templates/header.php'; ?>
    
    <main class="news-page">
        <!-- Основные новости (без закреплённой плашки) -->
        <section class="news-list">
            <div class="container">
                <div class="section-header">
                    <h1>Все новости</h1>
                    <div class="news-filters">
                        <button class="filter-btn active" data-filter="all">Все</button>
                        <button class="filter-btn" data-filter="Важные">Важные</button>
                        <button class="filter-btn" data-filter="Услуги">Услуги</button>
                        <button class="filter-btn" data-filter="Ремонты">Ремонты</button>
                    </div>
                </div>

                <div class="news-grid" id="newsGrid">
                    <?php if (empty($news)): ?>
                        <div class="no-news">
                            <i class="far fa-newspaper"></i>
                            <h3>Новостей пока нет</h3>
                            <p>Следите за обновлениями на нашем сайте</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($news as $item): 
                            $category = getNewsCategory($item['title'], $item['description']);
                            $tags = getNewsTags($item['title'], $item['description']);
                            $imageSrc = $item['image'] ? 'uploads/news/' . $item['image'] : 'img/default-news.jpg';
                            $description = mb_strlen($item['description']) > 150 
                                ? mb_substr(strip_tags($item['description']), 0, 150) . '...' 
                                : strip_tags($item['description']);
                            $detailUrl = 'news_details.php?id=' . $item['id'];
                        ?>
                            <article class="news-card" data-category="<?= $category ?>" onclick="window.location='<?= $detailUrl ?>'" style="cursor: pointer;">
                                <div class="card-image">
                                    <img src="<?= $imageSrc ?>" alt="<?= htmlspecialchars($item['title']) ?>" 
                                         onerror="this.src='img/default-news.jpg'">
                                    <div class="card-category"><?= $category ?></div>
                                    <div class="card-date">
                                        <span class="date-day"><?= date('d', strtotime($item['created_at'])) ?></span>
                                        <span class="date-month"><?= getRussianMonth(date('m', strtotime($item['created_at']))) ?></span>
                                    </div>
                                </div>
                                <div class="card-content">
                                    <div class="card-header">
                                        <h3 class="card-title"><?= htmlspecialchars($item['title']) ?></h3>
                                        <div class="card-time">
                                            <i class="far fa-clock"></i>
                                            <?= $item['formatted_time'] ?>
                                        </div>
                                    </div>
                                    <p class="card-description"><?= htmlspecialchars($description) ?></p>
                                    
                                    <div class="card-tags">
                                        <?php foreach ($tags as $tag): ?>
                                            <span class="tag"><?= $tag ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="card-footer">
                                        <a href="<?= $detailUrl ?>" class="read-more" onclick="event.stopPropagation();">
                                            Читать подробнее
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                        <!-- Убраны кнопки "Поделиться" и "Сохранить" -->
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Пагинация -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page-1 ?>" class="pagination-btn prev">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php else: ?>
                            <button class="pagination-btn prev" disabled>
                                <i class="fas fa-chevron-left"></i>
                            </button>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="pagination-current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?page=<?= $i ?>" class="pagination-link"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <span class="pagination-total">из <?= $totalPages ?></span>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page+1 ?>" class="pagination-btn next">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <button class="pagination-btn next" disabled>
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Футер -->
    <?php include 'templates/footer.php'; ?>

    <script>
        // Фильтрация по табам (категориям)
        document.addEventListener('DOMContentLoaded', function() {
            const filterBtns = document.querySelectorAll('.filter-btn');
            const cards = document.querySelectorAll('.news-card');

            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Убираем активный класс у всех кнопок
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');

                    const filter = this.dataset.filter;

                    cards.forEach(card => {
                        const category = card.dataset.category;
                        if (filter === 'all' || category === filter) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>