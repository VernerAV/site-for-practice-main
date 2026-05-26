
<?php
require 'config.php'; // или как у вас называется файл с конфигом

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Подключение к БД успешно!";
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage();
}
?>