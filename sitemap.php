<?php
// Очищаем буфер, чтобы гарантировать, что ничего не выведется до заголовков
ob_clean();
header('Content-Type: application/xml; charset=utf-8');

// Подключаем базу данных
require_once 'includes/config.php';

// Базовый URL вашего сайта (замените на свой)
$base_url = 'https://ваш-сайт.ru/';

// Статические страницы
$pages = [
    ['loc' => 'index.php', 'priority' => '1.0', 'changefreq' => 'daily'],
    ['loc' => 'news.php', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['loc' => 'price.php', 'priority' => '0.8', 'changefreq' => 'weekly'],
    ['loc' => 'about.php', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => 'contact.php', 'priority' => '0.8', 'changefreq' => 'weekly'],
    ['loc' => 'check_status.php', 'priority' => '0.5', 'changefreq' => 'monthly'],
    ['loc' => 'login.php', 'priority' => '0.3', 'changefreq' => 'monthly'],
    ['loc' => 'search.php', 'priority' => '0.2', 'changefreq' => 'never'],
];

// Динамические новости из базы данных
try {
    $stmt = $pdo->query("SELECT id, created_at FROM news ORDER BY created_at DESC");
    while ($row = $stmt->fetch()) {
        $pages[] = [
            'loc' => 'news_details.php?id=' . (int)$row['id'],
            'priority' => '0.6',
            'changefreq' => 'weekly',
            'lastmod' => date('Y-m-d', strtotime($row['created_at']))
        ];
    }
} catch (Exception $e) {
    // Если БД не ответила, просто пропускаем новости
}

// Выводим XML-заголовок и подключаем XSL-стиль (для красивого отображения в браузере)
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<?xml-stylesheet type="text/xsl" href="sitemap.xsl"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <?php foreach ($pages as $page): ?>
    <url>
        <loc><?= htmlspecialchars($base_url . $page['loc'], ENT_XML1, 'UTF-8') ?></loc>
        <?php if (isset($page['lastmod'])): ?>
        <lastmod><?= htmlspecialchars($page['lastmod'], ENT_XML1, 'UTF-8') ?></lastmod>
        <?php endif; ?>
        <changefreq><?= htmlspecialchars($page['changefreq'], ENT_XML1, 'UTF-8') ?></changefreq>
        <priority><?= htmlspecialchars($page['priority'], ENT_XML1, 'UTF-8') ?></priority>
    </url>
    <?php endforeach; ?>
</urlset>