<?php
$page_title = "Giriş Yap";
require_once 'includes/header.php'; // $db ve $base_url burada dahil edilir

// Eğer kullanıcı zaten giriş yapmışsa, ana sayfaya yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: " . $base_url . "index.php");
    exit();
}

// Kayıt sonrası başarı mesajı var mı kontrol et
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Mesajı gösterdikten sonra sil
}

// Profil veya diğer sayfalardan gelen hata/bilgi mesajı var mı kontrol et
$error_message_from_session = '';
if (isset($_SESSION['error_message'])) {
    $error_message_from_session = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Mesajı gösterdikten sonra sil
}

$hatalar = [];
$eposta = ''; // Formda e-postayı tutmak için

// Form gönderilmiş mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $eposta = trim($_POST['eposta']);
    $sifre = $_POST['sifre'];

    // Basit doğrulamalar
    if (empty($eposta)) {
        $hatalar[] = "E-posta alanı boş bırakılamaz.";
    } elseif (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
        $hatalar[] = "Geçerli bir e-posta adresi giriniz.";
    }
    if (empty($sifre)) {
        $hatalar[] = "Şifre alanı boş bırakılamaz.";
    }

    // Hata yoksa giriş yapmayı dene
    if (empty($hatalar)) {
        try {
            $stmt = $db->prepare("CALL sp_KullaniciGetirByEposta(:p_Eposta)");
            $stmt->bindParam(':p_Eposta', $eposta);
            $stmt->execute();
            $kullanici = $stmt->fetch();
            $stmt->closeCursor();

            if ($kullanici) {
                // Kullanıcı bulundu, şifreyi doğrula
                if (password_verify($sifre, $kullanici['SifreHash'])) {
                    // Şifre doğru, oturum başlat
                    $_SESSION['user_id'] = $kullanici['KullaniciID'];
                    $_SESSION['user_ad_soyad'] = htmlspecialchars($kullanici['Ad'] . ' ' . $kullanici['Soyad']);
                    $_SESSION['user_eposta'] = $kullanici['Eposta'];
                    
                    // Son giriş tarihini güncelle (sp_KullaniciSonGirisGuncelle saklı yordamını çağır)
                    try {
                        $stmt_update_login = $db->prepare("CALL sp_KullaniciSonGirisGuncelle(:p_KullaniciID)");
                        $stmt_update_login->bindParam(':p_KullaniciID', $kullanici['KullaniciID']);
                        $stmt_update_login->execute();
                        $stmt_update_login->closeCursor();
                    } catch (PDOException $e_login_update) {
                        error_log("Son giriş güncelleme hatası: " . $e_login_update->getMessage());
                        // Bu hata giriş işlemini engellememeli, sadece loglanır.
                    }

                    // Kullanıcıyı ana sayfaya veya istediği başka bir sayfaya yönlendir
                    header("Location: " . $base_url . "index.php");
                    exit();
                } else {
                    // Şifre yanlış
                    $hatalar[] = "E-posta veya şifre hatalı.";
                }
            } else {
                // Kullanıcı bulunamadı
                $hatalar[] = "E-posta veya şifre hatalı.";
            }

        } catch (PDOException $e) {
            error_log("Giriş hatası: " . $e->getMessage());
            $hatalar[] = "Giriş sırasında bir sorun oluştu. Lütfen daha sonra tekrar deneyin.";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message_from_session): ?>
            <div class="alert alert-warning"> <?php echo htmlspecialchars($error_message_from_session); ?>
            </div>
        <?php endif; ?>

        <?php
        // Form gönderme sonrası oluşan hataları göster
        if (!empty($hatalar)) {
            echo '<div class="alert alert-danger">';
            foreach ($hatalar as $hata) {
                echo '<p class="mb-0">' . htmlspecialchars($hata) . '</p>';
            }
            echo '</div>';
        }
        ?>

        <div class="card">
            <div class="card-header">
                <h2>Giriş Yap</h2>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div class="mb-3">
                        <label for="eposta" class="form-label">E-posta Adresiniz:</label>
                        <input type="email" class="form-control" id="eposta" name="eposta" value="<?php echo htmlspecialchars($eposta); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="sifre" class="form-label">Şifreniz:</label>
                        <input type="password" class="form-control" id="sifre" name="sifre" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
                </form>
                <p class="mt-3 text-center">
                    Hesabınız yok mu? <a href="<?php echo $base_url; ?>kayit.php">Kayıt Olun</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>