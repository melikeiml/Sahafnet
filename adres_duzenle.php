<?php
// $page_title dinamik olarak ayarlanacak
require_once 'includes/header.php'; // $db, $base_url, $current_user_id burada dahil edilir

// Giriş yapmamış kullanıcıları giriş sayfasına yönlendir
if (!$current_user_id) {
    $_SESSION['error_message'] = "Adres düzenleyebilmek için lütfen giriş yapın.";
    header("Location: " . htmlspecialchars($base_url . "giris.php"));
    exit();
}

$adres_id_duzenle = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $adres_id_duzenle = intval($_GET['id']);
} else {
    $_SESSION['error_message_adres'] = "Düzenlenecek adres için geçersiz ID.";
    header("Location: " . htmlspecialchars($base_url . "profil.php#adreslerim"));
    exit();
}

// Mevcut adres bilgilerini çek ve kullanıcının bu adrese sahip olup olmadığını kontrol et
$mevcut_adres = null;
try {
    $stmt_adres = $db->prepare("CALL sp_AdresGetirByID(:p_AdresID, :p_KullaniciID)");
    $stmt_adres->bindParam(':p_AdresID', $adres_id_duzenle, PDO::PARAM_INT);
    $stmt_adres->bindParam(':p_KullaniciID', $current_user_id, PDO::PARAM_INT);
    $stmt_adres->execute();
    $mevcut_adres = $stmt_adres->fetch();
    $stmt_adres->closeCursor();

    if (!$mevcut_adres) {
        $_SESSION['error_message_adres'] = "Düzenlenecek adres bulunamadı veya bu adresi düzenleme yetkiniz yok.";
        header("Location: " . htmlspecialchars($base_url . "profil.php#adreslerim"));
        exit();
    }
} catch (PDOException $e) {
    error_log("Adres düzenleme sayfası - Adres çekme hatası: " . $e->getMessage());
    $_SESSION['error_message_adres'] = "Adres bilgileri yüklenirken bir sorun oluştu.";
    header("Location: " . htmlspecialchars($base_url . "profil.php#adreslerim"));
    exit();
}

$page_title = "Adres Düzenle: " . htmlspecialchars($mevcut_adres['AdresBasligi'] ?? 'Bilinmeyen Adres');
echo "<script>document.title = '" . addslashes($page_title) . " - SahafNet';</script>";


$hatalar_adres_duzenle = [];
// Formdan gelen değerleri tutmak için değişkenler (mevcut adres bilgileriyle doldurulur)
// Eğer POST edilmişse POST değerini al, edilmemişse mevcut değeri al.
$adres_basligi_val = $_POST['adres_basligi'] ?? $mevcut_adres['AdresBasligi'];
$acik_adres_val = $_POST['acik_adres'] ?? $mevcut_adres['AcikAdres'];
$il_val = $_POST['il'] ?? $mevcut_adres['Il'];
$ilce_val = $_POST['ilce'] ?? $mevcut_adres['Ilce'];
$mahalle_val = $_POST['mahalle'] ?? $mevcut_adres['Mahalle']; // Bu NULL olabilir
$posta_kodu_val = $_POST['posta_kodu'] ?? $mevcut_adres['PostaKodu']; // Bu NULL olabilir


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Form gönderildiğinde değerleri trim et
    $adres_basligi_val = trim($_POST['adres_basligi']);
    $acik_adres_val = trim($_POST['acik_adres']);
    $il_val = trim($_POST['il']);
    $ilce_val = trim($_POST['ilce']);
    $mahalle_val = trim($_POST['mahalle']);
    $posta_kodu_val = trim($_POST['posta_kodu']);

    // Doğrulamalar
    if (empty($adres_basligi_val)) {
        $hatalar_adres_duzenle[] = "Adres başlığı boş bırakılamaz.";
    }
    if (empty($acik_adres_val)) {
        $hatalar_adres_duzenle[] = "Açık adres boş bırakılamaz.";
    }
    if (empty($il_val)) {
        $hatalar_adres_duzenle[] = "İl boş bırakılamaz.";
    }
    if (empty($ilce_val)) {
        $hatalar_adres_duzenle[] = "İlçe boş bırakılamaz.";
    }
    if (!empty($posta_kodu_val) && (!is_numeric($posta_kodu_val) || strlen($posta_kodu_val) != 5)) {
        $hatalar_adres_duzenle[] = "Posta kodu 5 haneli bir sayı olmalıdır.";
    }

    if (empty($hatalar_adres_duzenle)) {
        try {
            $stmt_update = $db->prepare("CALL sp_AdresGuncelle(:p_AdresID, :p_KullaniciID, :p_AdresBasligi, :p_AcikAdres, :p_Il, :p_Ilce, :p_Mahalle, :p_PostaKodu)");
            
            $stmt_update->bindParam(':p_AdresID', $adres_id_duzenle, PDO::PARAM_INT);
            $stmt_update->bindParam(':p_KullaniciID', $current_user_id, PDO::PARAM_INT);
            $stmt_update->bindParam(':p_AdresBasligi', $adres_basligi_val, PDO::PARAM_STR);
            $stmt_update->bindParam(':p_AcikAdres', $acik_adres_val, PDO::PARAM_STR);
            $stmt_update->bindParam(':p_Il', $il_val, PDO::PARAM_STR);
            $stmt_update->bindParam(':p_Ilce', $ilce_val, PDO::PARAM_STR);
            
            // Saklı yordam NULLIF(TRIM(p_Mahalle), '') ile hallediyor, boş string gönderebiliriz.
            $stmt_update->bindParam(':p_Mahalle', $mahalle_val, PDO::PARAM_STR);
            $stmt_update->bindParam(':p_PostaKodu', $posta_kodu_val, PDO::PARAM_STR);
            
            $stmt_update->execute();
            $stmt_update->closeCursor();

            $_SESSION['success_message_adres'] = "Adres başarıyla güncellendi.";
            header("Location: " . htmlspecialchars($base_url . "profil.php#adreslerim"));
            exit();

        } catch (PDOException $e) {
            error_log("Adres güncelleme SQL hatası: " . $e->getMessage() . " (Kod: " . $e->getCode() . ")");
            $hatalar_adres_duzenle[] = "Adres güncellenirken bir veritabanı hatası oluştu. Lütfen daha sonra tekrar deneyin.";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($base_url . 'profil.php'); ?>">Profilim</a></li>
                <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($base_url . 'profil.php#adreslerim'); ?>">Adreslerim</a></li>
                <li class="breadcrumb-item active" aria-current="page">Adres Düzenle</li>
            </ol>
        </nav>

        <div class="card">
            <div class="card-header">
                <h2>Adres Düzenle: <?php echo htmlspecialchars($mevcut_adres['AdresBasligi'] ?? 'Adres'); ?></h2>
            </div>
            <div class="card-body">
                <?php
                if (!empty($hatalar_adres_duzenle)) {
                    echo '<div class="alert alert-danger">';
                    foreach ($hatalar_adres_duzenle as $hata) {
                        echo '<p class="mb-0">' . htmlspecialchars($hata) . '</p>';
                    }
                    echo '</div>';
                }
                ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?id=" . $adres_id_duzenle); ?>" method="post">
                    <div class="mb-3">
                        <label for="adres_basligi" class="form-label">Adres Başlığı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="adres_basligi" name="adres_basligi" value="<?php echo htmlspecialchars($adres_basligi_val ?? ''); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="il" class="form-label">İl <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="il" name="il" value="<?php echo htmlspecialchars($il_val ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="ilce" class="form-label">İlçe <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ilce" name="ilce" value="<?php echo htmlspecialchars($ilce_val ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="mahalle" class="form-label">Mahalle (İsteğe Bağlı)</label>
                            <input type="text" class="form-control" id="mahalle" name="mahalle" value="<?php echo htmlspecialchars($mahalle_val ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="posta_kodu" class="form-label">Posta Kodu (İsteğe Bağlı)</label>
                            <input type="text" class="form-control" id="posta_kodu" name="posta_kodu" value="<?php echo htmlspecialchars($posta_kodu_val ?? ''); ?>" maxlength="5">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="acik_adres" class="form-label">Açık Adres (Cadde, Sokak, No, Daire vb.) <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="acik_adres" name="acik_adres" rows="3" required><?php echo htmlspecialchars($acik_adres_val ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
                    <a href="<?php echo htmlspecialchars($base_url . "profil.php#adreslerim"); ?>" class="btn btn-secondary">İptal</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
