<?php
require_once 'includes/config.php';

if (!isset($_GET['id'])) {
    header('Location: news.php');
    exit();
}

$news_id = intval($_GET['id']);

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Получаем текущую новость
    $sql = "SELECT *, 
                   DATE_FORMAT(created_at, '%d.%m.%Y') as formatted_date,
                   DATE_FORMAT(created_at, '%H:%i') as formatted_time
            FROM news WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $news_id]);
    $news = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$news) {
        header('Location: news.php');
        exit();
    }
    
    // Получаем предыдущую и следующую новости
    $prev_next_sql = "SELECT id, title FROM news 
                     WHERE id < :id 
                     ORDER BY id DESC 
                     LIMIT 1";
    $stmt = $pdo->prepare($prev_next_sql);
    $stmt->execute([':id' => $news_id]);
    $prev_news = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $prev_next_sql = "SELECT id, title FROM news 
                     WHERE id > :id 
                     ORDER BY id ASC 
                     LIMIT 1";
    $stmt = $pdo->prepare($prev_next_sql);
    $stmt->execute([':id' => $news_id]);
    $next_news = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Получаем 3 последние новости (связанные)
    $related_sql = "SELECT id, title, description, image, created_at 
                   FROM news 
                   WHERE id != :id 
                   ORDER BY created_at DESC 
                   LIMIT 3";
    $stmt = $pdo->prepare($related_sql);
    $stmt->execute([':id' => $news_id]);
    $related_news = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die('Ошибка загрузки новости');
}

// Функция для обрезки текста
function truncateText($text, $length = 100) {
    if (mb_strlen($text) > $length) {
        return mb_substr(strip_tags($text), 0, $length) . '...';
    }
    return strip_tags($text);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($news['title']); ?> - Новости ЖКХ Строгино</title>
    <link rel="stylesheet" href="css/news.css">
    <link rel="stylesheet" href="css/news_detail.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Хедер -->
    <?php include 'templates/header.php'; ?>
    
    <main class="news-detail-page">
        <div class="container">
            <!-- Кнопка назад -->
            <a href="news.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                К списку новостей
            </a>
            
            <!-- Детальная новость -->
            <article class="news-detail">
                <div class="detail-header">
                    <div class="detail-category">Новость</div>
                    <h1><?php echo htmlspecialchars($news['title']); ?></h1>
                    <div class="detail-meta">
                        <span class="meta-item">
                            <i class="far fa-calendar"></i>
                            <?php echo $news['formatted_date']; ?>
                        </span>
                        <span class="meta-item">
                            <i class="far fa-clock"></i>
                            <?php echo $news['formatted_time']; ?>
                        </span>
                        <span class="meta-item">
                            <i class="far fa-eye"></i>
                            <?php echo rand(100, 500); ?> просмотров
                        </span>
                    </div>
                </div>
                
                <?php if ($news['image']): ?>
                <div class="detail-image">
                    <img src="uploads/news/<?php echo htmlspecialchars($news['image']); ?>" 
                         alt="<?php echo htmlspecialchars($news['title']); ?>"
                         onerror="this.src='images/default-news.jpg'">
                </div>
                <?php endif; ?>
                
                <div class="detail-content">
                    <?php 
                    // Обрабатываем текст новости
                    $content = htmlspecialchars($news['description']);
                    // Заменяем переносы строк на параграфы
                    $content = nl2br($content);
                    // Добавляем форматирование
                    $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
                    $content = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $content);
                    
                    echo $content;
                    ?>
                </div>
                
                <div class="detail-footer">
                    <div class="detail-tags">
                        <?php
                        // Автоматически генерируем теги из заголовка и описания
                        $text = strtolower($news['title'] . ' ' . $news['description']);
                        $tags = [];
                        
                        if (strpos($text, 'ремонт') !== false) $tags[] = 'Ремонт';
                        if (strpos($text, 'вода') !== false) $tags[] = 'Вода';
                        if (strpos($text, 'отопл') !== false) $tags[] = 'Отопление';
                        if (strpos($text, 'лифт') !== false) $tags[] = 'Лифт';
                        if (strpos($text, 'электри') !== false) $tags[] = 'Электрика';
                        if (strpos($text, 'двор') !== false) $tags[] = 'Благоустройство';
                        if (strpos($text, 'тариф') !== false) $tags[] = 'Тарифы';
                        
                        // Добавляем общие теги
                        $tags = array_merge($tags, ['ЖКХ', 'Строгино', 'Управляющая компания']);
                        $tags = array_unique(array_slice($tags, 0, 5));
                        
                        foreach ($tags as $tag) {
                            echo '<span class="tag">' . htmlspecialchars($tag) . '</span>';
                        }
                        ?>
                    </div>
                    
                    <div class="detail-share">
                        <span>Поделиться:</span>
                        <button class="share-btn" onclick="shareNews()">
                            <i class="fas fa-share-alt"></i>
                        </button>
                    </div>
                </div>
            </article>
            
            <!-- Навигация между новостями -->
            <?php if ($prev_news || $next_news): ?>
            <div class="news-navigation">
                <?php if ($prev_news): ?>
                <a href="news-details.php?id=<?php echo $prev_news['id']; ?>" class="nav-btn prev">
                    <i class="fas fa-chevron-left"></i>
                    <div>
                        <span class="nav-label">Предыдущая новость</span>
                        <span class="nav-title"><?php echo htmlspecialchars(truncateText($prev_news['title'], 50)); ?></span>
                    </div>
                </a>
                <?php else: ?>
                <div></div> <!-- Для выравнивания -->
                <?php endif; ?>
                
                <?php if ($next_news): ?>
                <a href="news-details.php?id=<?php echo $next_news['id']; ?>" class="nav-btn next">
                    <div>
                        <span class="nav-label">Следующая новость</span>
                        <span class="nav-title"><?php echo htmlspecialchars(truncateText($next_news['title'], 50)); ?></span>
                    </div>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Связанные новости -->
            <?php if (!empty($related_news)): ?>
            <section class="related-news">
                <h2>Другие новости</h2>
                <div class="related-grid">
                    <?php foreach ($related_news as $related): ?>
                    <a href="news-details.php?id=<?php echo $related['id']; ?>" class="related-card">
                        <?php if ($related['image']): ?>
                        <img src="uploads/news/<?php echo htmlspecialchars($related['image']); ?>" 
                             alt="<?php echo htmlspecialchars($related['title']); ?>"
                             onerror="this.src='images/default-news.jpg'">
                        <?php else: ?>
                        <img src="images/default-news.jpg" alt="Новость">
                        <?php endif; ?>
                        <div class="related-content">
                            <h3><?php echo htmlspecialchars(truncateText($related['title'], 70)); ?></h3>
                            <div class="related-date">
                                <i class="far fa-calendar"></i>
                                <?php echo date('d.m.Y', strtotime($related['created_at'])); ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Комментарии (опционально) -->
            <?php if (false): // Отключено по умолчанию ?>
            <section class="comments-section">
                <h2>Комментарии (<?php echo rand(1, 10); ?>)</h2>
                
                <form class="comment-form">
                    <textarea placeholder="Оставьте ваш комментарий..." required></textarea>
                    <button type="submit">Отправить комментарий</button>
                </form>
                
                <!-- Здесь можно выводить комментарии из БД -->
            </section>
            <?php endif; ?>
        </div>
    </main>

    <!-- Футер -->
    <?php include 'templates/footer.php'; ?>

    <script>
        // Функция для поделиться
        function shareNews() {
            const url = window.location.href;
            const title = document.querySelector('.news-detail h1').textContent;
            
            if (navigator.share) {
                // Современный API
                navigator.share({
                    title: title,
                    text: 'Посмотрите эту новость на сайте ЖКХ Строгино',
                    url: url
                })
                .then(() => console.log('Успешно поделились'))
                .catch(error => console.log('Ошибка при попытке поделиться:', error));
            } else if (navigator.clipboard) {
                // Копирование в буфер обмена
                navigator.clipboard.writeText(url).then(() => {
                    alert('Ссылка скопирована в буфер обмена!');
                });
            } else {
                // Старый способ
                prompt('Скопируйте эту ссылку:', url);
            }
        }
        
        // Сохранение просмотра в localStorage
        window.addEventListener('load', function() {
            const newsId = <?php echo $news_id; ?>;
            const viewedNews = JSON.parse(localStorage.getItem('viewed_news') || '[]');
            
            if (!viewedNews.includes(newsId)) {
                viewedNews.push(newsId);
                localStorage.setItem('viewed_news', JSON.stringify(viewedNews));
            }
        });
        
        // Подсветка тегов при наведении
        document.querySelectorAll('.tag').forEach(tag => {
            tag.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px)';
                this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
            });
            
            tag.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
            
            // Клик по тегу для поиска новостей с этим тегом
            tag.addEventListener('click', function(e) {
                e.preventDefault();
                const tagText = this.textContent;
                window.location.href = `news.php?tag=${encodeURIComponent(tagText)}`;
            });
        });
    </script>
</body>
</html>