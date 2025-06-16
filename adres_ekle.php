<?php
$page_title = "Yeni Adres Ekle";
require_once 'includes/header.php'; // $db, $base_url, $current_user_id burada dahil edilir

// Giriş yapmamış kullanıcıları giriş sayfasına yönlendir
if (!$current_user_id) {
    $_SESSION['error_message'] = "Adres ekleyebilmek için lütfen giriş yapın.";
    header("Location: " . htmlspecialchars($base_url . "giris.php"));
    exit();
}

$hatalar_adres_ekle = [];
// Formdan gelen değerleri tutmak için değişkenler (hata durumunda formda kalması için)
$adres_basligi_val = '';
$acik_adres_val = '';
$il_val = '';
$ilce_val = '';
$mahalle_val = '';
$posta_kodu_val = '';

// Eğer bir önceki sayfaya geri dönmek için return_url parametresi varsa alalım
$return_url = isset($_GET['return_url']) ? trim($_GET['return_url']) : $base_url . 'profil.php#adreslerim';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $adres_basligi_val = trim($_POST['adres_basligi']);
    $acik_adres_val = trim($_POST['acik_adres']);
    $il_val = trim($_POST['il']);
    $ilce_val = trim($_POST['ilce']);
    $mahalle_val = trim($_POST['mahalle']);
    $posta_kodu_val = trim($_POST['posta_kodu']);

    // Doğrulamalar
    if (empty($adres_basligi_val)) {
        $hatalar_adres_ekle[] = "Adres başlığı boş bırakılamaz.";
    }
    if (empty($acik_adres_val)) {
        $hatalar_adres_ekle[] = "Açık adres boş bırakılamaz.";
    }
    if (empty($il_val)) {
        $hatalar_adres_ekle[] = "İl boş bırakılamaz.";
    }
    if (empty($ilce_val)) {
        $hatalar_adres_ekle[] = "İlçe boş bırakılamaz.";
    }
    // Posta kodu için daha detaylı format kontrolü eklenebilir (örn: sadece 5 haneli sayı)
    if (!empty($posta_kodu_val) && (!is_numeric($posta_kodu_val) || strlen($posta_kodu_val) != 5)) {
        $hatalar_adres_ekle[] = "Posta kodu 5 haneli bir sayı olmalıdır.";
    }


    if (empty($hatalar_adres_ekle)) {
        try {
            // sp_AdresEkle(IN p_KullaniciID, IN p_AdresBasligi, IN p_AcikAdres, IN p_Il, IN p_Ilce, IN p_Mahalle, IN p_PostaKodu)
            $stmt = $db->prepare("CALL sp_AdresEkle(:p_KullaniciID, :p_AdresBasligi, :p_AcikAdres, :p_Il, :p_Ilce, :p_Mahalle, :p_PostaKodu)");
            
            $stmt->bindParam(':p_KullaniciID', $current_user_id, PDO::PARAM_INT);
            $stmt->bindParam(':p_AdresBasligi', $adres_basligi_val, PDO::PARAM_STR);
            $stmt->bindParam(':p_AcikAdres', $acik_adres_val, PDO::PARAM_STR);
            $stmt->bindParam(':p_Il', $il_val, PDO::PARAM_STR);
            $stmt->bindParam(':p_Ilce', $ilce_val, PDO::PARAM_STR);
            
            // Mahalle ve Posta Kodu boşsa NULL olarak gönderilsin (saklı yordam NULLIF ile hallediyor)
            $stmt->bindParam(':p_Mahalle', $mahalle_val, PDO::PARAM_STR);
            $stmt->bindParam(':p_PostaKodu', $posta_kodu_val, PDO::PARAM_STR);
            
            $stmt->execute();
            // sp_AdresEkle yordamı LAST_INSERT_ID() döndürmüyordu, bu yüzden fetch() yapmıyoruz.
            // Eğer döndürseydi, yeni adresin ID'sini alabilirdik.
            $stmt->closeCursor();

            $_SESSION['success_message_adres'] = "Yeni adres başarıyla eklendi.";
            header("Location: " . htmlspecialchars($return_url)); // Geldiği sayfaya veya profil sayfasına yönlendir
            exit();

        } catch (PDOException $e) {
            error_log("Adres ekleme SQL hatası: " . $e->getMessage() . " (Kod: " . $e->getCode() . ")");
            $hatalar_adres_ekle[] = "Adres eklenirken bir veritabanı hatası oluştu. Lütfen daha sonra tekrar deneyin.";
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
                <li class="breadcrumb-item active" aria-current="page">Yeni Adres Ekle</li>
            </ol>
        </nav>

        <div class="card">
            <div class="card-header">
                <h2>Yeni Adres Ekle</h2>
            </div>
            <div class="card-body">
                <?php
                if (!empty($hatalar_adres_ekle)) {
                    echo '<div class="alert alert-danger">';
                    foreach ($hatalar_adres_ekle as $hata) {
                        echo '<p class="mb-0">' . htmlspecialchars($hata) . '</p>';
                    }
                    echo '</div>';
                }
                ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($return_url != ($base_url . 'profil.php#adreslerim') ? '?return_url='.urlencode($return_url) : '')); ?>" method="post">
                    <div class="mb-3">
                        <label for="adres_basligi" class="form-label">Adres Başlığı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="adres_basligi" name="adres_basligi" value="<?php echo htmlspecialchars($adres_basligi_val); ?>" required placeholder="Ev Adresim, İş Adresim vb.">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="il" class="form-label">İl <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="il" name="il" value="<?php echo htmlspecialchars($il_val); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="ilce" class="form-label">İlçe <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="ilce" name="ilce" value="<?php echo htmlspecialchars($ilce_val); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="mahalle" class="form-label">Mahalle (İsteğe Bağlı)</label>
                            <input type="text" class="form-control" id="mahalle" name="mahalle" value="<?php echo htmlspecialchars($mahalle_val); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="posta_kodu" class="form-label">Posta Kodu (İsteğe Bağlı)</label>
                            <input type="text" class="form-control" id="posta_kodu" name="posta_kodu" value="<?php echo htmlspecialchars($posta_kodu_val); ?>" maxlength="5">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="acik_adres" class="form-label">Açık Adres (Cadde, Sokak, No, Daire vb.) <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="acik_adres" name="acik_adres" rows="3" required><?php echo htmlspecialchars($acik_adres_val); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Adresi Kaydet</button>
                    <a href="<?php echo htmlspecialchars($return_url); ?>" class="btn btn-secondary">İptal</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
