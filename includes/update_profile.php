<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Получаем данные из формы
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$middle_name = $_POST['middle_name'] ?? '';
$birth_date = $_POST['birth_date'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? ''; // Полный адрес из hidden поля

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    
    // Проверяем существование записи в user_profile
    $check_sql = "SELECT COUNT(*) FROM user_profiles WHERE user_id = :user_id";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([':user_id' => $user_id]);
    $exists = $check_stmt->fetchColumn();
    
    if ($exists) {
        // Обновляем существующую запись
        $sql = "UPDATE user_profiles SET 
                first_name = :first_name,
                last_name = :last_name,
                middle_name = :middle_name,
                birth_date = :birth_date,
                phone = :phone,
                address = :address
                WHERE user_id = :user_id";
    } else {
        // Создаем новую запись
        $sql = "INSERT INTO user_profiles 
                (user_id, first_name, last_name, middle_name, birth_date, phone, address)
                VALUES (:user_id, :first_name, :last_name, :middle_name, :birth_date, :phone, :address)";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':middle_name' => $middle_name,
        ':birth_date' => $birth_date,
        ':phone' => $phone,
        ':address' => $address
    ]);
    
    header('Location: ../user.php?success=1');
    exit();
    
} catch (PDOException $e) {
    die("Ошибка обновления профиля: " . $e->getMessage());
}
?>