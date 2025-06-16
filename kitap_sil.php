<?php
// Veritabanı bağlantısını ve oturumları dahil et
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/database.php'; // $db PDO nesnesi burada oluşturulur.

// Temel URL (yönlendirme için)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host_server = $_SERVER['HTTP_HOST'];
$base_url = "/sahafnet/"; // Projenizin kök dizinine göre ayarlayın

// Giriş yapmış kullanıcı ID'sini al
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Sadece POST isteklerini ve giriş yapmış kullanıcıları kabul et
if ($_SERVER["REQUEST_METHOD"] == "POST" && $current_user_id) {
    
    $kitap_id_to_delete = isset($_POST['kitap_id_sil']) ? intval($_POST['kitap_id_sil']) : null;
    $redirect_url = $base_url . "profil.php#sattiklarim"; // Varsayılan geri dönüş

    if (empty($kitap_id_to_delete)) {
        $_SESSION['error_message'] = "Silinecek kitap ID'si belirtilmemiş.";
        header("Location: " . $redirect_url);
        exit();
    }

    try {
        // Önce kitabın gerçekten bu kullanıcıya ait olup olmadığını kontrol et (güvenlik için)
        $stmt_check = $db->prepare("SELECT SatıcıKullaniciID, KapakFotografiURL FROM Kitaplar WHERE KitapID = :p_KitapID");
        $stmt_check->bindParam(':p_KitapID', $kitap_id_to_delete, PDO::PARAM_INT);
        $stmt_check->execute();
        $kitap_to_check = $stmt_check->fetch();
        $stmt_check->closeCursor();

        if (!$kitap_to_check) {
            $_SESSION['error_message'] = "Silinmek istenen kitap bulunamadı.";
            header("Location: " . $redirect_url);
            exit();
        }

        if ($kitap_to_check['SatıcıKullaniciID'] != $current_user_id) {
            $_SESSION['error_message'] = "Bu kitabı silme yetkiniz yok.";
            header("Location: " . $redirect_url);
            exit();
        }

        // Kitabı silmek için saklı yordamı çağır
        $stmt_delete = $db->prepare("CALL sp_KitapSil(:p_KitapID)");
        $stmt_delete->bindParam(':p_KitapID', $kitap_id_to_delete, PDO::PARAM_INT);
        $stmt_delete->execute();
        $stmt_delete->closeCursor();

        // Kapak fotoğrafını sunucudan sil (eğer varsa ve placeholder değilse)
        if (!empty($kitap_to_check['KapakFotografiURL']) && file_exists($kitap_to_check['KapakFotografiURL']) && strpos($kitap_to_check['KapakFotografiURL'], 'placeholder') === false) {
            if (!unlink($kitap_to_check['KapakFotografiURL'])) {
                // Silme başarısız olursa logla, ama ana işlemi engelleme
                error_log("Kapak fotoğrafı silinemedi: " . $kitap_to_check['KapakFotografiURL']);
            }
        }

        $_SESSION['success_message'] = "Kitap başarıyla silindi.";
        header("Location: " . $redirect_url);
        exit();

    } catch (PDOException $e) {
        error_log("Kitap silme hatası: " . $e->getMessage());
        // Foreign key hatası (örn: 1451) alırsanız, bu kitabın siparişlerde kullanıldığı anlamına gelir.
        if ($e->getCode() == '23000' || strpos($e->getMessage(), '1451') !== false ) { // Integrity constraint violation
             $_SESSION['error_message'] = "Bu kitap bir veya daha fazla siparişte kullanıldığı için silinemez. Önce ilgili siparişlerin düzenlenmesi/silinmesi gerekir veya kitabı 'Listeden Kaldırıldı' olarak işaretleyebilirsiniz.";
        } else {
            $_SESSION['error_message'] = "Kitap silinirken bir veritabanı hatası oluştu: " . $e->getCode();
        }
        header("Location: " . $redirect_url);
        exit();
    }

} else {
    // POST değilse veya kullanıcı giriş yapmamışsa
    $_SESSION['error_message'] = "Geçersiz istek veya oturum bulunamadı.";
    header("Location: " . $base_url . "index.php");
    exit();
}
?>