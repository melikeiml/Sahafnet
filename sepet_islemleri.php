<?php // BU SATIR DOSYANIN EN BAŞINDA OLMALI, ÖNCESİNDE HİÇBİR ŞEY OLMAMALI (BOŞLUK, SATIR BAŞI VB.)

// Hata gösterimini geliştirme aşamasında açık tutmak iyidir, canlıda kapatılabilir.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Temel URL (yönlendirme için)
// Bu, projenizin kök dizinine göre doğru ayarlanmalıdır.
// Örnek: Eğer projeniz localhost/sahafnet/ ise $base_url = "/sahafnet/";
// Eğer projeniz localhost/ ise ve içinde sahafnet klasörü varsa yine $base_url = "/sahafnet/";
// Eğer projeniz localhost/ana_klasor/sahafnet/ ise $base_url = "/ana_klasor/sahafnet/";
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host_server = $_SERVER['HTTP_HOST'];
$base_url = "/sahafnet/"; // KENDİ PROJE YOLUNUZA GÖRE GEREKİRSE AYARLAYIN


// Sepet session'da bir dizi olarak tutulacak
if (!isset($_SESSION['sepet'])) {
    $_SESSION['sepet'] = array();
}

// Sadece POST isteklerini ve 'action' parametresi varsa işlemleri yap
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    $kitap_id = isset($_POST['kitap_id']) ? intval($_POST['kitap_id']) : null;

    // Geri dönüş URL'si
    // Formdan 'return_url' geliyorsa onu kullan, yoksa varsayılan sepet.php
    $redirect_url = isset($_POST['return_url']) && !empty($_POST['return_url']) ? $_POST['return_url'] : $base_url . "sepet.php";
    
    // Kitap detay sayfasından geliniyorsa, o sayfaya geri yönlendirmek için varsayılan URL
    $product_page_url_default = $base_url . "index.php"; // Eğer kitap_id yoksa veya bilinmiyorsa
    if ($kitap_id) {
        $product_page_url_default = $base_url . "kitap_detay.php?id=" . $kitap_id;
    }


    if ($action == 'ekle' && $kitap_id) {
        $adet = isset($_POST['adet']) && intval($_POST['adet']) > 0 ? intval($_POST['adet']) : 1;
        $kitap_adi = isset($_POST['kitap_adi']) ? $_POST['kitap_adi'] : 'Bilinmeyen Kitap';
        $fiyat = isset($_POST['fiyat']) ? floatval($_POST['fiyat']) : 0.00;
        // $kapak_url = isset($_POST['kapak_url']) ? $_POST['kapak_url'] : ''; // İsteğe bağlı

        if (isset($_SESSION['sepet'][$kitap_id])) {
            $_SESSION['sepet'][$kitap_id]['adet'] += $adet;
            $_SESSION['success_message'] = htmlspecialchars($kitap_adi) . " sepetinize eklendi (adet güncellendi).";
        } else {
            $_SESSION['sepet'][$kitap_id] = [
                'ad' => $kitap_adi,
                'fiyat' => $fiyat,
                'adet' => $adet
                // 'kapak_url' => $kapak_url
            ];
            $_SESSION['success_message'] = htmlspecialchars($kitap_adi) . " başarıyla sepetinize eklendi.";
        }
        header("Location: " . $product_page_url_default); // Kitap detay sayfasına geri dön
        exit();

    } elseif ($action == 'guncelle' && $kitap_id) {
        $adet = isset($_POST['adet']) && intval($_POST['adet']) >= 0 ? intval($_POST['adet']) : 0; 
        if (isset($_SESSION['sepet'][$kitap_id])) {
            if ($adet > 0) {
                $_SESSION['sepet'][$kitap_id]['adet'] = $adet;
                $_SESSION['success_message'] = htmlspecialchars($_SESSION['sepet'][$kitap_id]['ad']) . " adedi güncellendi.";
            } else {
                $kaldirilan_kitap_adi_guncelle = $_SESSION['sepet'][$kitap_id]['ad'];
                unset($_SESSION['sepet'][$kitap_id]);
                $_SESSION['success_message'] = htmlspecialchars($kaldirilan_kitap_adi_guncelle) . " sepetten kaldırıldı (adet 0 yapıldı).";
            }
        } else {
            $_SESSION['error_message'] = "Güncellenecek ürün sepette bulunamadı.";
        }
        header("Location: " . $redirect_url); 
        exit();

    } elseif ($action == 'kaldir' && $kitap_id) {
        if (isset($_SESSION['sepet'][$kitap_id])) {
            $kaldirilan_kitap_adi = $_SESSION['sepet'][$kitap_id]['ad'];
            unset($_SESSION['sepet'][$kitap_id]);
            $_SESSION['success_message'] = htmlspecialchars($kaldirilan_kitap_adi) . " sepetten kaldırıldı.";
        } else {
             $_SESSION['error_message'] = "Kaldırılacak ürün sepette bulunamadı.";
        }
        header("Location: " . $redirect_url); 
        exit();
    
    } elseif ($action == 'bosalt') {
        $_SESSION['sepet'] = array(); 
        $_SESSION['success_message'] = "Sepetiniz başarıyla boşaltıldı.";
        header("Location: " . $redirect_url); 
        exit();
    } else {
        // Bilinmeyen bir 'action' ise veya action doğru ama gerekli parametreler eksikse
        $_SESSION['error_message'] = "Geçersiz sepet işlemi türü veya eksik parametre.";
        header("Location: " . $base_url . "index.php"); 
        exit();
    }
} else {
    // POST değilse veya 'action' parametresi yoksa
    // Bu dosyaya doğrudan erişim olmamalı, genellikle bir formla gelinir.
    $_SESSION['error_message'] = "Geçersiz istek. Sepet işlemleri sadece form aracılığıyla yapılabilir.";
    header("Location: " . $base_url . "index.php"); 
    exit();
}
?>