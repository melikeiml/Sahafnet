<?php
// Veritabanı bağlantısını ve oturumları dahil et
// Bu dosya doğrudan bir HTML çıktısı üretmeyeceği için header.php'yi çağırmıyoruz,
// sadece database.php'yi çağırarak $db ve session'lara erişim sağlıyoruz.
// Ayrıca $base_url'e de ihtiyacımız olacak yönlendirme için.

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
    
    // Formdan gelen verileri al
    $kitap_id = isset($_POST['kitap_id']) ? intval($_POST['kitap_id']) : null;
    $puan = isset($_POST['puan']) ? intval($_POST['puan']) : null;
    $yorum_metni = isset($_POST['yorum_metni']) ? trim($_POST['yorum_metni']) : '';

    $hatalar = [];
    $geri_donus_url = $base_url . "kitap_detay.php"; // Varsayılan geri dönüş

    if ($kitap_id) {
        $geri_donus_url = $base_url . "kitap_detay.php?id=" . $kitap_id;
    }

    // Basit doğrulamalar
    if (empty($kitap_id)) {
        $hatalar[] = "Kitap ID'si belirtilmemiş.";
    }
    if (empty($puan) || $puan < 1 || $puan > 5) {
        $hatalar[] = "Lütfen 1 ile 5 arasında geçerli bir puan seçin.";
    }
    // Yorum metni boş olabilir, zorunlu değilse bu kontrolü kaldırın veya güncelleyin
    if (strlen($yorum_metni) > 2000) { // Örnek bir maksimum uzunluk
        $hatalar[] = "Yorum metni çok uzun (maksimum 2000 karakter).";
    }

    // Hata yoksa yorumu kaydet
    if (empty($hatalar)) {
        try {
            // sp_YorumEkle saklı yordamını çağır
            // Saklı yordam parametreleri: p_KitapID, p_KullaniciID, p_Puan, p_YorumMetni
            $stmt = $db->prepare("CALL sp_YorumEkle(:p_KitapID, :p_KullaniciID, :p_Puan, :p_YorumMetni)");
            
            $stmt->bindParam(':p_KitapID', $kitap_id, PDO::PARAM_INT);
            $stmt->bindParam(':p_KullaniciID', $current_user_id, PDO::PARAM_INT);
            $stmt->bindParam(':p_Puan', $puan, PDO::PARAM_INT);
            
            // Yorum metni boşsa NULL gönder, değilse metni gönder
            $yorum_metni_param = !empty($yorum_metni) ? $yorum_metni : null;
            $stmt->bindParam(':p_YorumMetni', $yorum_metni_param, PDO::PARAM_STR);
            
            $stmt->execute();
            // Saklı yordam YeniYorumID döndürüyordu, ama burada doğrudan kullanmayacağız.
            // Sadece işlemin başarılı olduğunu varsayıyoruz eğer exception fırlatmazsa.
            $stmt->closeCursor();

            $_SESSION['success_message'] = "Yorumunuz başarıyla gönderildi. Onaylandıktan sonra yayınlanacaktır.";
            header("Location: " . $geri_donus_url . "#yorumlar-tab-pane"); // Yorumlar sekmesine git
            exit();

        } catch (PDOException $e) {
            error_log("Yorum ekleme hatası: " . $e->getMessage());
            $_SESSION['error_message'] = "Yorum eklenirken bir veritabanı hatası oluştu. Lütfen daha sonra tekrar deneyin.";
            header("Location: " . $geri_donus_url);
            exit();
        }
    } else {
        // Doğrulama hataları varsa, hataları session'a atıp geri yönlendir
        $_SESSION['form_errors'] = $hatalar;
        // Form değerlerini de session'a atıp formda tekrar doldurabiliriz (isteğe bağlı)
        $_SESSION['form_values'] = ['puan' => $puan, 'yorum_metni' => $yorum_metni];
        header("Location: " . $geri_donus_url . "#yorum-formu-alani"); // Formun olduğu bölüme git
        exit();
    }

} else {
    // POST değilse veya kullanıcı giriş yapmamışsa ana sayfaya yönlendir
    $_SESSION['error_message'] = "Geçersiz istek veya oturum bulunamadı.";
    header("Location: " . $base_url . "index.php");
    exit();
}
?>