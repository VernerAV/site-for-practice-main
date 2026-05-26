<?php
ob_start();
// Настройки базы данных (из дампа u90998hq_vorav)
define('DB_HOST',  'MySQL-8.4');     // или '127.0.0.1'
define('DB_NAME', 'u90998hq_vorav'); // имя базы из дампа
define('DB_USER', 'root');           // пользователь OSPanel по умолчанию
define('DB_PASS', '');               // пароль пустой

// Настройки сайта
define('SITE_NAME', 'ГБУ "Жилищник Района Строгино"');

// Настройки загрузки файлов
define('UPLOAD_DIR', 'uploads/');

// Подключение к БД через PDO
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Если хотите видеть сообщение об успехе, раскомментируйте:
    // echo "Подключение к БД успешно!";
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}
?>