<?php
$page_title = "Kayıt Ol";
require_once 'includes/header.php'; // $db ve $base_url burada dahil edilir

// Form gönderilmiş mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen verileri al
    $ad = trim($_POST['ad']);
    $soyad = trim($_POST['soyad']);
    $eposta = trim($_POST['eposta']);
    $sifre = $_POST['sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];
    $telefon = isset($_POST['telefon']) ? trim($_POST['telefon']) : null;

    $hatalar = [];

    // Basit doğrulamalar
    if (empty($ad)) {
        $hatalar[] = "Ad alanı boş bırakılamaz.";
    }
    if (empty($soyad)) {
        $hatalar[] = "Soyad alanı boş bırakılamaz.";
    }
    if (empty($eposta)) {
        $hatalar[] = "E-posta alanı boş bırakılamaz.";
    } elseif (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
        $hatalar[] = "Geçerli bir e-posta adresi giriniz.";
    }
    if (empty($sifre)) {
        $hatalar[] = "Şifre alanı boş bırakılamaz.";
    } elseif (strlen($sifre) < 6) {
        $hatalar[] = "Şifre en az 6 karakter olmalıdır.";
    }
    if ($sifre !== $sifre_tekrar) {
        $hatalar[] = "Şifreler eşleşmiyor.";
    }

    // E-posta daha önce alınmış mı kontrol et (saklı yordam sp_KullaniciGetirByEposta kullanılabilir)
    if (empty($hatalar)) {
        try {
            $stmt_check_email = $db->prepare("CALL sp_KullaniciGetirByEposta(:p_Eposta)");
            $stmt_check_email->bindParam(':p_Eposta', $eposta);
            $stmt_check_email->execute();
            if ($stmt_check_email->fetch()) {
                $hatalar[] = "Bu e-posta adresi zaten kayıtlı.";
            }
            $stmt_check_email->closeCursor();
        } catch (PDOException $e) {
            // Geliştirme aşamasında logla, kullanıcıya genel bir hata göster
            error_log("Eposta kontrol hatası: " . $e->getMessage());
            $hatalar[] = "Kayıt sırasında bir sorun oluştu. Lütfen daha sonra tekrar deneyin.";
        }
    }


    // Hata yoksa kullanıcıyı kaydet
    if (empty($hatalar)) {
        try {
            // Şifreyi hash'le
            $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);

            $stmt = $db->prepare("CALL sp_KullaniciEkle(:p_Ad, :p_Soyad, :p_Eposta, :p_SifreHash, :p_TelefonNo)");
            $stmt->bindParam(':p_Ad', $ad);
            $stmt->bindParam(':p_Soyad', $soyad);
            $stmt->bindParam(':p_Eposta', $eposta);
            $stmt->bindParam(':p_SifreHash', $sifre_hash);
            $stmt->bindValue(':p_TelefonNo', $telefon !== null ? $telefon : null, $telefon !== null ? PDO::PARAM_STR : PDO::PARAM_NULL); // Telefon opsiyonel olduğu için NULL olabilir
            $stmt->bindValue(':p_TelefonNo', $telefon !== null ? $telefon : null, $telefon !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);            
            $stmt->execute();
            $yeni_kullanici_sonuc = $stmt->fetch(); // Saklı yordam LAST_INSERT_ID() döndürüyordu
            $stmt->closeCursor();

            if ($yeni_kullanici_sonuc && isset($yeni_kullanici_sonuc['YeniKullaniciID'])) {
                // Başarılı kayıt sonrası kullanıcıyı bilgilendir ve giriş sayfasına yönlendir.
                // Veya doğrudan giriş yaptırılabilir (session başlatılarak)
                $_SESSION['success_message'] = "Kaydınız başarıyla oluşturuldu! Şimdi giriş yapabilirsiniz.";
                header("Location: " . $base_url . "giris.php");
                exit();
            } else {
                $hatalar[] = "Kullanıcı kaydı sırasında bilinmeyen bir sorun oluştu.";
            }

        } catch (PDOException $e) {
            // Geliştirme aşamasında logla, kullanıcıya genel bir hata göster
            error_log("Kullanıcı ekleme hatası: " . $e->getMessage());
            // SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry ... (E-posta unique kısıtlaması için)
            if ($e->getCode() == '23000') { // Benzersizlik hatası kodu (genellikle)
                 $hatalar[] = "Bu e-posta adresi zaten kullanımda.";
            } else {
                $hatalar[] = "Kayıt sırasında bir veritabanı hatası oluştu. Lütfen daha sonra tekrar deneyin.";
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h2>Kayıt Ol</h2>
            </div>
            <div class="card-body">
                <?php
                // Hata mesajlarını göster
                if (!empty($hatalar)) {
                    echo '<div class="alert alert-danger">';
                    foreach ($hatalar as $hata) {
                        echo '<p class="mb-0">' . htmlspecialchars($hata) . '</p>';
                    }
                    echo '</div>';
                }
                ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <label for="ad" class="form-label">Adınız:</label>
                        <input type="text" class="form-control" id="ad" name="ad" value="<?php echo isset($ad) ? htmlspecialchars($ad) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="soyad" class="form-label">Soyadınız:</label>
                        <input type="text" class="form-control" id="soyad" name="soyad" value="<?php echo isset($soyad) ? htmlspecialchars($soyad) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="eposta" class="form-label">E-posta Adresiniz:</label>
                        <input type="email" class="form-control" id="eposta" name="eposta" value="<?php echo isset($eposta) ? htmlspecialchars($eposta) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="telefon" class="form-label">Telefon Numaranız (İsteğe Bağlı):</label>
                        <input type="tel" class="form-control" id="telefon" name="telefon" value="<?php echo isset($telefon) ? htmlspecialchars($telefon) : ''; ?>" placeholder="örn: 5551234567">
                    </div>
                    <div class="mb-3">
                        <label for="sifre" class="form-label">Şifreniz:</label>
                        <input type="password" class="form-control" id="sifre" name="sifre" required>
                    </div>
                    <div class="mb-3">
                        <label for="sifre_tekrar" class="form-label">Şifrenizi Tekrar Girin:</label>
                        <input type="password" class="form-control" id="sifre_tekrar" name="sifre_tekrar" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
                </form>
                <p class="mt-3 text-center">
                    Zaten bir hesabınız var mı? <a href="<?php echo $base_url; ?>giris.php">Giriş Yapın</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>