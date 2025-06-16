<?php
$page_title = "Yeni Kitap Ekle";
require_once 'includes/header.php'; // $db, $base_url, $current_user_id burada dahil edilir

// Giriş yapmamış kullanıcıları giriş sayfasına yönlendir
if (!$current_user_id) {
    $_SESSION['error_message'] = "Kitap ekleyebilmek için lütfen giriş yapın.";
    header("Location: " . $base_url . "giris.php");
    exit();
}

// Kategorileri çek (dropdown için)
$kategoriler_options = [];
try {
    $stmt_cats = $db->prepare("CALL sp_TumKategorileriGetir()");
    $stmt_cats->execute();
    $kategoriler_options = $stmt_cats->fetchAll();
    $stmt_cats->closeCursor();
} catch (PDOException $e) {
    error_log("Kitap ekleme sayfası - Kategori çekme hatası: " . $e->getMessage());
    // Hata durumunda kategori listesi boş kalır, kullanıcıya mesaj gösterilebilir.
}


$hatalar = [];
// Formdan gelen değerleri tutmak için değişkenler (hata durumunda formda kalması için)
$kitap_adi = isset($_POST['kitap_adi']) ? trim($_POST['kitap_adi']) : '';
$yazar_adi = isset($_POST['yazar_adi']) ? trim($_POST['yazar_adi']) : '';
$kategori_id = isset($_POST['kategori_id']) ? trim($_POST['kategori_id']) : '';
$isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : '';
$yayin_evi = isset($_POST['yayin_evi']) ? trim($_POST['yayin_evi']) : '';
$baski_yili = isset($_POST['baski_yili']) ? trim($_POST['baski_yili']) : '';
$sayfa_sayisi = isset($_POST['sayfa_sayisi']) ? trim($_POST['sayfa_sayisi']) : '';
$kondisyon = isset($_POST['kondisyon']) ? trim($_POST['kondisyon']) : '';
$aciklama = isset($_POST['aciklama']) ? trim($_POST['aciklama']) : '';
$fiyat = isset($_POST['fiyat']) ? trim($_POST['fiyat']) : '';
$stok_adedi = isset($_POST['stok_adedi']) ? trim($_POST['stok_adedi']) : 1; // Varsayılan 1


// Form gönderilmiş mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen verileri tekrar al (zaten yukarıda alındı ama tekrar atayabiliriz)
    $kitap_adi = trim($_POST['kitap_adi']);
    $yazar_adi = trim($_POST['yazar_adi']);
    $kategori_id = trim($_POST['kategori_id']);
    $isbn = trim($_POST['isbn']);
    $yayin_evi = trim($_POST['yayin_evi']);
    $baski_yili = trim($_POST['baski_yili']);
    $sayfa_sayisi = trim($_POST['sayfa_sayisi']);
    $kondisyon = trim($_POST['kondisyon']);
    $aciklama = trim($_POST['aciklama']);
    $fiyat = trim($_POST['fiyat']);
    $stok_adedi = trim($_POST['stok_adedi']);
    
    $kapak_fotografi_url = null; 

    // Basit doğrulamalar
    if (empty($kitap_adi)) $hatalar[] = "Kitap adı boş bırakılamaz.";
    if (empty($yazar_adi)) $hatalar[] = "Yazar adı boş bırakılamaz.";
    if (empty($kategori_id)) $hatalar[] = "Kategori seçimi zorunludur.";
    if (empty($kondisyon)) $hatalar[] = "Kondisyon seçimi zorunludur.";
    if (empty($fiyat)) {
        $hatalar[] = "Fiyat boş bırakılamaz.";
    } elseif (!is_numeric($fiyat) || floatval($fiyat) < 0) {
        $hatalar[] = "Geçerli bir fiyat giriniz.";
    } else {
        $fiyat = str_replace(',', '.', $fiyat); // Virgülü noktaya çevir (DECIMAL için)
    }
    if (empty($stok_adedi)) {
        $hatalar[] = "Stok adedi boş bırakılamaz.";
    } elseif (!is_numeric($stok_adedi) || intval($stok_adedi) < 0) { 
        $hatalar[] = "Geçerli bir stok adedi giriniz.";
    } else {
        $stok_adedi = intval($stok_adedi); 
    }

    if (!empty($baski_yili) && (!is_numeric($baski_yili) || strlen($baski_yili) != 4 || intval($baski_yili) < 1000 || intval($baski_yili) > date('Y'))) {
        $hatalar[] = "Baskı yılı 4 haneli geçerli bir yıl olmalıdır.";
    }
    if (!empty($sayfa_sayisi) && (!is_numeric($sayfa_sayisi) || intval($sayfa_sayisi) < 1)) {
        $hatalar[] = "Sayfa sayısı geçerli bir sayı olmalıdır.";
    }

    // Dosya Yükleme İşlemleri
    if (isset($_FILES['kapak_fotografi']) && $_FILES['kapak_fotografi']['error'] == UPLOAD_ERR_OK) {
        $target_dir_base = "uploads/"; // Ana uploads klasörü
        $target_dir = $target_dir_base . "kitap_kapaklari/"; 
        
        // Ana uploads klasörü yoksa oluştur
        if (!is_dir($target_dir_base)) {
            if (!mkdir($target_dir_base, 0777, true)) {
                 $hatalar[] = "Ana yükleme klasörü ('uploads') oluşturulamadı.";
            }
        }
        // Alt kitap_kapaklari klasörü yoksa oluştur (ana klasör oluştuktan sonra)
        if (is_dir($target_dir_base) && !is_dir($target_dir)) {
            if (!mkdir($target_dir, 0777, true)) {
                $hatalar[] = "Kitap kapakları için yükleme klasörü ('uploads/kitap_kapaklari/') oluşturulamadı.";
            }
        }


        if (empty($hatalar) && is_writable($target_dir)) { // Klasör yazılabilirse devam et
            $file_extension = strtolower(pathinfo($_FILES["kapak_fotografi"]["name"], PATHINFO_EXTENSION));
            $new_file_name = "user" . $current_user_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_file_name;
            
            $allowed_types = array("jpg", "jpeg", "png", "gif");
            $max_file_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($file_extension, $allowed_types)) {
                $hatalar[] = "Sadece JPG, JPEG, PNG & GIF dosyalarına izin verilmektedir.";
            }
            if ($_FILES["kapak_fotografi"]["size"] > $max_file_size) {
                $hatalar[] = "Dosya boyutu çok büyük. Maksimum 2MB olabilir.";
            }

            if (empty($hatalar)) { 
                if (move_uploaded_file($_FILES["kapak_fotografi"]["tmp_name"], $target_file)) {
                    $kapak_fotografi_url = $target_file; 
                } else {
                    $hatalar[] = "Kapak fotoğrafı yüklenirken bir sorun oluştu. Klasör izinlerini kontrol edin.";
                }
            }
        } elseif(empty($hatalar) && !is_writable($target_dir)) {
             $hatalar[] = "Yükleme klasörü ('" . $target_dir . "') yazılabilir değil. Lütfen sunucu izinlerini kontrol edin.";
        }
    } elseif (isset($_FILES['kapak_fotografi']) && $_FILES['kapak_fotografi']['error'] != UPLOAD_ERR_NO_FILE && $_FILES['kapak_fotografi']['error'] != UPLOAD_ERR_OK) {
        // Dosya seçilmiş ama bir hata oluşmuş (boş dosya değilse)
        $hatalar[] = "Fotoğraf yüklenirken bir hata oluştu. Hata kodu: " . $_FILES['kapak_fotografi']['error'];
    }


    // Hata yoksa kitabı kaydet
      // Hata yoksa kitabı kaydet
    if (empty($hatalar)) {
        try {
            // SAKLI YORDAMIN PARAMETRE SIRASINA DİKKAT EDİN!
            // MySQL'deki sp_KitapEkle yordamınızın parametre sırası neyse,
            // aşağıdaki prepare ifadesindeki soru işareti sayısı ve execute dizisindeki
            // değişkenlerin sırası TAM OLARAK AYNI OLMALIDIR.
            
            // MySQL yordamınızdaki parametre sırası (ekran görüntüsüne göre):
            // 1. p_SatıcıKullaniciID (INT)
            // 2. p_KategoriID (INT)
            // 3. p_KitapAdi (VARCHAR)
            // 4. p_YazarAdi (VARCHAR)
            // 5. p_ISBN (VARCHAR)
            // 6. p_YayinEvi (VARCHAR)
            // 7. p_BaskiYili (INT)
            // 8. p_SayfaSayisi (INT)
            // 9. p_Kondisyon (VARCHAR)
            // 10. p_Aciklama (TEXT)
            // 11. p_Fiyat (DECIMAL)
            // 12. p_StokAdedi (INT)
            // 13. p_KapakFotografiURL (VARCHAR)

            $sql = "CALL sp_KitapEkle(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // 13 tane soru işareti
            $stmt = $db->prepare($sql);

            // Değişkenleri hazırlayalım (NULL olabilecekler için)
            $isbn_param = !empty($isbn) ? $isbn : null;
            $yayin_evi_param = !empty($yayin_evi) ? $yayin_evi : null;
            $baski_yili_int = !empty($baski_yili) ? intval($baski_yili) : null;
            $sayfa_sayisi_int = !empty($sayfa_sayisi) ? intval($sayfa_sayisi) : null;
            $aciklama_param = !empty($aciklama) ? $aciklama : null;
            $kapak_fotografi_param = !empty($kapak_fotografi_url) ? $kapak_fotografi_url : null;
            
            // execute() metoduna parametreleri DİZİ içinde ve DOĞRU SIRADA verin.
            // Bu sıra, sp_KitapEkle'deki parametre sırasıyla aynı olmalı.
            $stmt->execute([
                $current_user_id,    // 1. p_SatıcıKullaniciID
                $kategori_id,        // 2. p_KategoriID
                $kitap_adi,          // 3. p_KitapAdi
                $yazar_adi,          // 4. p_YazarAdi
                $isbn_param,         // 5. p_ISBN
                $yayin_evi_param,    // 6. p_YayinEvi
                $baski_yili_int,     // 7. p_BaskiYili
                $sayfa_sayisi_int,   // 8. p_SayfaSayisi
                $kondisyon,          // 9. p_Kondisyon
                $aciklama_param,     // 10. p_Aciklama
                $fiyat,              // 11. p_Fiyat (DECIMAL için string olarak gönderilmesi sorun olmaz)
                $stok_adedi,         // 12. p_StokAdedi
                $kapak_fotografi_param // 13. p_KapakFotografiURL
            ]);
            
            $yeni_kitap_sonuc = $stmt->fetch(); 
            $stmt->closeCursor();

            if ($yeni_kitap_sonuc && isset($yeni_kitap_sonuc['YeniKitapID'])) {
                $_SESSION['success_message'] = "Kitabınız başarıyla satışa eklendi!";
                header("Location: " . $base_url . "profil.php#sattiklarim");
                exit();
            } else {
                $hatalar[] = "Kitap eklendi ancak ID alınamadı. Yöneticinize başvurun.";
                error_log("Kitap eklendi ama sp_KitapEkle'den YeniKitapID gelmedi.");
            }

        } catch (PDOException $e) {
            error_log("Kitap ekleme SQL hatası (soru işaretli): " . $e->getMessage() . " (Kod: " . $e->getCode() . ")");
            $hatalar[] = "Kitap eklenirken bir veritabanı hatası oluştu. Lütfen daha sonra tekrar deneyin. (Hata: " . $e->getCode() . ")";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h2>Yeni Kitap Ekle</h2>
            </div>
            <div class="card-body">
                <?php
                if (!empty($hatalar)) {
                    echo '<div class="alert alert-danger">';
                    foreach ($hatalar as $hata) {
                        echo '<p class="mb-0">' . htmlspecialchars($hata) . '</p>';
                    }
                    echo '</div>';
                }
                ?>
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="kitap_adi" class="form-label">Kitap Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="kitap_adi" name="kitap_adi" value="<?php echo htmlspecialchars($kitap_adi); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="yazar_adi" class="form-label">Yazar Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="yazar_adi" name="yazar_adi" value="<?php echo htmlspecialchars($yazar_adi); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="kategori_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="kategori_id" name="kategori_id" required>
                                <option value="">Kategori Seçiniz...</option>
                                <?php foreach ($kategoriler_options as $kategori_opt): ?>
                                    <option value="<?php echo $kategori_opt['KategoriID']; ?>" <?php echo ($kategori_id == $kategori_opt['KategoriID'] ? 'selected' : ''); ?>>
                                        <?php echo htmlspecialchars($kategori_opt['KategoriAdi']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="kondisyon" class="form-label">Kondisyon <span class="text-danger">*</span></label>
                            <select class="form-select" id="kondisyon" name="kondisyon" required>
                                <option value="">Kondisyon Seçiniz...</option>
                                <option value="Yeni Gibi" <?php echo ($kondisyon == 'Yeni Gibi' ? 'selected' : ''); ?>>Yeni Gibi</option>
                                <option value="Çok İyi" <?php echo ($kondisyon == 'Çok İyi' ? 'selected' : ''); ?>>Çok İyi</option>
                                <option value="İyi" <?php echo ($kondisyon == 'İyi' ? 'selected' : ''); ?>>İyi</option>
                                <option value="Orta" <?php echo ($kondisyon == 'Orta' ? 'selected' : ''); ?>>Orta</option>
                                <option value="Yıpranmış" <?php echo ($kondisyon == 'Yıpranmış' ? 'selected' : ''); ?>>Yıpranmış</option>
                            </select>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="fiyat" class="form-label">Fiyat (TL) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fiyat" name="fiyat" value="<?php echo htmlspecialchars($fiyat); ?>" required placeholder="örn: 25.50 veya 25,50">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="stok_adedi" class="form-label">Stok Adedi <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="stok_adedi" name="stok_adedi" value="<?php echo htmlspecialchars($stok_adedi); ?>" required min="0"> </div>
                    </div>
                    <div class="mb-3">
                        <label for="isbn" class="form-label">ISBN (İsteğe Bağlı)</label>
                        <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo htmlspecialchars($isbn); ?>" placeholder="örn: 978-605-0000-00-0">
                    </div>
                     <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="yayin_evi" class="form-label">Yayınevi (İsteğe Bağlı)</label>
                            <input type="text" class="form-control" id="yayin_evi" name="yayin_evi" value="<?php echo htmlspecialchars($yayin_evi); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="baski_yili" class="form-label">Baskı Yılı (İsteğe Bağlı)</label>
                            <input type="number" class="form-control" id="baski_yili" name="baski_yili" value="<?php echo htmlspecialchars($baski_yili); ?>" placeholder="örn: 2015" min="1000" max="<?php echo date('Y'); ?>">
                        </div>
                         <div class="col-md-4 mb-3">
                            <label for="sayfa_sayisi" class="form-label">Sayfa Sayısı (İsteğe Bağlı)</label>
                            <input type="number" class="form-control" id="sayfa_sayisi" name="sayfa_sayisi" value="<?php echo htmlspecialchars($sayfa_sayisi); ?>" min="1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="aciklama" class="form-label">Açıklama (İsteğe Bağlı)</label>
                        <textarea class="form-control" id="aciklama" name="aciklama" rows="3"><?php echo htmlspecialchars($aciklama); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="kapak_fotografi" class="form-label">Kapak Fotoğrafı (İsteğe Bağlı - Maks 2MB: JPG, PNG, GIF)</label>
                        <input class="form-control" type="file" id="kapak_fotografi" name="kapak_fotografi">
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100">Kitabı Satışa Ekle</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>