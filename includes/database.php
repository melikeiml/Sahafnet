<?php
// Hata raporlamasını aç (geliştirme aşamasında faydalı)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Oturumları SADECE BİR KEZ başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$db_name = "SahafNetDB";
$username = "root";
$password = "Bartın7446"; // KENDİ MySQL ŞİFRENİZİ GİRİN (Eğer yoksa "" yapın)

try {
    $db = new PDO("mysql:host={$host};dbname={$db_name};charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage() .
        "<br/>Lütfen MySQL sunucunuzun çalıştığından ve veritabanı bilgilerinizin doğru olduğundan emin olun." .
        "<br/>Kullanıcı adı: " . htmlspecialchars($username) .
        "<br/>Veritabanı adı: " . htmlspecialchars($db_name));
}
?>