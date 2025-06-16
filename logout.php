<?php
// Oturumu başlat (session değişkenlerine erişebilmek için)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Temel URL'yi header.php'den almak yerine burada da tanımlayabiliriz
// veya header.php'yi include edip oradan alabiliriz.
// Basitlik için burada tanımlayalım:
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host_server = $_SERVER['HTTP_HOST'];
// Projenizin kök dizinine göre ayarlayın.
// Eğer projeniz localhost/sahafnet/ ise $base_url = "/sahafnet/";
$base_url = "/sahafnet/";


// Tüm session değişkenlerini temizle
$_SESSION = array();

// Session cookie'sini sil (isteğe bağlı ama iyi bir pratiktir)
// Eğer session.use_cookies ayarı aktifse (genellikle aktiftir)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Session'ı sonlandır
session_destroy();

// Kullanıcıyı ana sayfaya yönlendir
header("Location: " . $base_url . "index.php");
exit();
?>