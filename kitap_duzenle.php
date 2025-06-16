<?php
// $page_title dinamik olarak ayarlanacak
require_once 'includes/header.php'; // $db, $base_url, $current_user_id burada dahil edilir

// Giriş yapmamış kullanıcıları giriş sayfasına yönlendir
if (!$current_user_id) {
    $_SESSION['error_message'] = "Kitap düzenleyebilmek için lütfen giriş yapın.";
    header("Location: " . $base_url . "giris.php");
    exit();
}

$kitap_id_duzenle = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $kitap_id_duzenle = intval($_GET['id']);
} else {
    $_SESSION['error_message'] = "Düzenlenecek kitap için geçersiz ID.";
    header("Location: " . $base_url . "profil.php#sattiklarim");
    exit();
}

// Mevcut kitap bilgilerini çek
$mevcut_kitap = null;
try {
    $stmt_kitap = $db->prepare("CALL sp_KitapGetirByID(:p_KitapID)");
    $stmt_kitap->bindParam(':p_KitapID', $kitap_id_duzenle, PDO::PARAM_INT);
    $stmt_kitap->execute();
    $mevcut_kitap = $stmt_kitap->fetch();
    $stmt_kitap->closeCursor();

    if (!$mevcut_kitap) {
        $_SESSION['error_message'] = "Düzenlenecek kitap bulunamadı.";
        header("Location: " . $base_url . "profil.php#sattiklarim");
        exit();
    }

    // Güvenlik: Kullanıcı sadece kendi kitabını düzenleyebilir mi kontrol et
    if ($mevcut_kitap['SatıcıKullaniciID'] != $current_user_id) {
        $_SESSION['error_message'] = "Bu kitabı düzenleme yetkiniz yok.";
        header("Location: " . $base_url . "profil.php#sattiklarim");
        exit();
    }
    $page_title = "Kitap Düzenle: " . htmlspecialchars($mevcut_kitap['KitapAdi']);
    echo "<script>document.title = '" . addslashes($page_title) . " - SahafNet';</script>";


} catch (PDOException $e) {
    error_log("Kitap düzenleme sayfası - Kitap çekme hatası: " . $e->getMessage());
    $_SESSION['error_message'] = "Kitap bilgileri yüklenirken bir sorun oluştu.";
    header("Location: " . $base_url . "profil.php#sattiklarim");
    exit();
}


// Kategorileri çek (dropdown için)
$kategoriler_options_edit = [];
try {
    $stmt_cats_edit = $db->prepare("CALL sp_TumKategorileriGetir()");
    $stmt_cats_edit->execute();
    $kategoriler_options_edit = $stmt_cats_edit->fetchAll();
    $stmt_cats_edit->closeCursor();
} catch (PDOException $e) {
    error_log("Kitap düzenleme sayfası - Kategori çekme hatası: " . $e->getMessage());
}

$hatalar_duzenle = [];
// Formdan gelen değerleri tutmak için değişkenler (mevcut kitap bilgileriyle doldurulur)
$kitap_adi_val = isset($_POST['kitap_adi']) ? trim($_POST['kitap_adi']) : $mevcut_kitap['KitapAdi'];
$yazar_adi_val = isset($_POST['yazar_adi']) ? trim($_POST['yazar_adi']) : $mevcut_kitap['YazarAdi'];
$kategori_id_val = isset($_POST['kategori_id']) ? trim($_POST['kategori_id']) : $mevcut_kitap['KategoriID'];
$isbn_val = isset($_POST['isbn']) ? trim($_POST['isbn']) : $mevcut_kitap['ISBN'];
$yayin_evi_val = isset($_POST['yayin_evi']) ? trim($_POST['yayin_evi']) : $mevcut_kitap['YayinEvi'];
$baski_yili_val = isset($_POST['baski_yili']) ? trim($_POST['baski_yili']) : $mevcut_kitap['BaskiYili'];
$sayfa_sayisi_val = isset($_POST['sayfa_sayisi']) ? trim($_POST['sayfa_sayisi']) : $mevcut_kitap['SayfaSayisi'];
$kondisyon_val = isset($_POST['kondisyon']) ? trim($_POST['kondisyon']) : $mevcut_kitap['Kondisyon'];
$aciklama_val = isset($_POST['aciklama']) ? trim($_POST['aciklama']) : $mevcut_kitap['Aciklama'];
$fiyat_val = isset($_POST['fiyat']) ? trim($_POST['fiyat']) : $mevcut_kitap['Fiyat'];
$stok_adedi_val = isset($_POST['stok_adedi']) ? trim($_POST['stok_adedi']) : $mevcut_kitap['StokAdedi'];
$durum_val = isset($_POST['durum']) ? trim($_POST['durum']) : $mevcut_kitap['Durum'];
$mevcut_kapak_url = $mevcut_kitap['KapakFotografiURL'];


// Form gönderilmiş mi kontrol et
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $kitap_adi_val = trim($_POST['kitap_adi']);
    $yazar_adi_val = trim($_POST['yazar_adi']);
    $kategori_id_val = trim($_POST['kategori_id']);
    $isbn_val = trim($_POST['isbn']);
    $yayin_evi_val = trim($_POST['yayin_evi']);
    $baski_yili_val = trim($_POST['baski_yili']);
    $sayfa_sayisi_val = trim($_POST['sayfa_sayisi']);
    $kondisyon_val = trim($_POST['kondisyon']);
    $aciklama_val = trim($_POST['aciklama']);
    $fiyat_val = trim($_POST['fiyat']);
    $stok_adedi_val = trim($_POST['stok_adedi']);
    $durum_val = trim($_POST['durum']);

    $yeni_kapak_fotografi_url = $mevcut_kapak_url; // Başlangıçta mevcut kapak geçerli

    // Basit doğrulamalar (kitap_ekle.php'dekine benzer)
    if (empty($kitap_adi_val)) $hatalar_duzenle[] = "Kitap adı boş bırakılamaz.";
    // ... Diğer tüm doğrulamaları buraya ekleyin (kitap_ekle.php'den kopyalayabilirsiniz) ...
    if (empty($fiyat_val)) {
        $hatalar_duzenle[] = "Fiyat boş bırakılamaz.";
    } elseif (!is_numeric($fiyat_val) || floatval($fiyat_val) < 0) {
        $hatalar_duzenle[] = "Geçerli bir fiyat giriniz.";
    } else {
        $fiyat_val = str_replace(',', '.', $fiyat_val);
    }
     if (empty($stok_adedi_val) && $stok_adedi_val !== '0' ) { // Stok 0 olabilir
        $hatalar_duzenle[] = "Stok adedi boş bırakılamaz.";
    } elseif (!is_numeric($stok_adedi_val) || intval($stok_adedi_val) < 0) { 
        $hatalar_duzenle[] = "Geçerli bir stok adedi giriniz.";
    } else {
        $stok_adedi_val = intval($stok_adedi_val); 
    }
    if (empty($durum_val) || !in_array($durum_val, ['Satışta', 'Satıldı', 'Listeden Kaldırıldı'])) {
        $hatalar_duzenle[] = "Lütfen geçerli bir durum seçin.";
    }


    // Dosya Yükleme İşlemleri (eğer yeni bir kapak fotoğrafı seçilmişse)
    if (isset($_FILES['kapak_fotografi']) && $_FILES['kapak_fotografi']['error'] == UPLOAD_ERR_OK) {
        $target_dir_base_edit = "uploads/";
        $target_dir_edit = $target_dir_base_edit . "kitap_kapaklari/";
        
        if (!is_dir($target_dir_base_edit)) { mkdir($target_dir_base_edit, 0777, true); }
        if (is_dir($target_dir_base_edit) && !is_dir($target_dir_edit)) { mkdir($target_dir_edit, 0777, true); }

        if (empty($hatalar_duzenle) && is_writable($target_dir_edit)) {
            $file_extension_edit = strtolower(pathinfo($_FILES["kapak_fotografi"]["name"], PATHINFO_EXTENSION));
            $new_file_name_edit = "user" . $current_user_id . "_kitap" . $kitap_id_duzenle . "_" . time() . "." . $file_extension_edit;
            $target_file_edit = $target_dir_edit . $new_file_name_edit;
            
            $allowed_types_edit = array("jpg", "jpeg", "png", "gif");
            $max_file_size_edit = 2 * 1024 * 1024; 

            if (!in_array($file_extension_edit, $allowed_types_edit)) {
                $hatalar_duzenle[] = "Sadece JPG, JPEG, PNG & GIF dosyalarına izin verilmektedir.";
            }
            if ($_FILES["kapak_fotografi"]["size"] > $max_file_size_edit) {
                $hatalar_duzenle[] = "Dosya boyutu çok büyük. Maksimum 2MB olabilir.";
            }

            if (empty($hatalar_duzenle)) { 
                if (move_uploaded_file($_FILES["kapak_fotografi"]["tmp_name"], $target_file_edit)) {
                    // Yeni kapak yüklendi, eskisini sil (eğer varsa ve placeholder değilse)
                    if ($mevcut_kapak_url && file_exists($mevcut_kapak_url) && strpos($mevcut_kapak_url, 'placeholder') === false) {
                        unlink($mevcut_kapak_url);
                    }
                    $yeni_kapak_fotografi_url = $target_file_edit; 
                } else {
                    $hatalar_duzenle[] = "Yeni kapak fotoğrafı yüklenirken bir sorun oluştu.";
                }
            }
        } elseif(empty($hatalar_duzenle) && !is_writable($target_dir_edit)) {
             $hatalar_duzenle[] = "Yükleme klasörü yazılabilir değil.";
        }
    }


    // Hata yoksa kitabı güncelle
    if (empty($hatalar_duzenle)) {
        try {
            // sp_KitapGuncelle(IN p_KitapID INT, IN p_KategoriID INT, IN p_KitapAdi VARCHAR(255), ..., IN p_KapakFotografiURL VARCHAR(500))
            $stmt_update = $db->prepare("CALL sp_KitapGuncelle(:p_KitapID, :p_KategoriID, :p_KitapAdi, :p_YazarAdi, :p_ISBN, :p_YayinEvi, :p_BaskiYili, :p_SayfaSayisi, :p_Kondisyon, :p_Aciklama, :p_Fiyat, :p_StokAdedi, :p_Durum, :p_KapakFotografiURL)");
            
            $stmt_update->bindParam(':p_KitapID', $kitap_id_duzenle, PDO::PARAM_INT);
            $stmt_update->bindParam(':p_KategoriID', $kategori_id_val, PDO::PARAM_INT);
            $stmt_update->bindParam(':p_KitapAdi', $kitap_adi_val, PDO::PARAM_STR);
            $stmt_update->bindParam(':p_YazarAdi', $yazar_adi_val, PDO::PARAM_STR);
            
            $isbn_p = !empty($isbn_val) ? $isbn_val : null;
            $stmt_update->bindParam(':p_ISBN', $isbn_p, PDO::PARAM_STR);
            $yayin_evi_p = !empty($yayin_evi_val) ? $yayin_evi_val : null;
            $stmt_update->bindParam(':p_YayinEvi', $yayin_evi_p, PDO::PARAM_STR);
            $baski_yili_p = !empty($baski_yili_val) ? intval($baski_yili_val) : null;
            $stmt_update->bindParam(':p_BaskiYili', $baski_yili_p, PDO::PARAM_INT);
            $sayfa_sayisi_p = !empty($sayfa_sayisi_val) ? intval($sayfa_sayisi_val) : null;
            $stmt_update->bindParam(':p_SayfaSayisi', $sayfa_sayisi_p, PDO::PARAM_INT);
            $stmt_update->bindParam(':p_Kondisyon', $kondisyon_val, PDO::PARAM_STR);
            $aciklama_p = !empty($aciklama_val) ? $aciklama_val : null;
            $stmt_update->bindParam(':p_Aciklama', $aciklama_p, PDO::PARAM_STR);
            $stmt_update->bindParam(':p_Fiyat', $fiyat_val, PDO::PARAM_STR);
            $stmt_update->bindParam(':p_StokAdedi', $stok_adedi_val, PDO::PARAM_INT);
            $stmt_update->bindParam(':p_Durum', $durum_val, PDO::PARAM_STR);
            $stmt_update->bindParam(':p_KapakFotografiURL', $yeni_kapak_fotografi_url, PDO::PARAM_STR); // Değişmişse yeni, değilse eski URL
            
            $stmt_update->execute();
            // Güncelleme işlemi genellikle etkilenen satır sayısı döndürür, özel bir ID değil.
            // $stmt_update->rowCount() ile kontrol edilebilir.
            $stmt_update->closeCursor();

            $_SESSION['success_message'] = "Kitap bilgileri başarıyla güncellendi!";
            header("Location: " . $base_url . "profil.php#sattiklarim");
            exit();

        } catch (PDOException $e) {
            error_log("Kitap güncelleme SQL hatası: " . $e->getMessage() . " (Kod: " . $e->getCode() . ")");
            $hatalar_duzenle[] = "Kitap güncellenirken bir veritabanı hatası oluştu. (Hata: " . $e->getCode() . ")";
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h2>Kitap Düzenle: <?php echo htmlspecialchars($mevcut_kitap['KitapAdi']); ?></h2>
            </div>
            <div class="card-body">
                <?php
                if (!empty($hatalar_duzenle)) {
                    echo '<div class="alert alert-danger">';
                    foreach ($hatalar_duzenle as $hata) {
                        echo '<p class="mb-0">' . htmlspecialchars($hata) . '</p>';
                    }
                    echo '</div>';
                }
                if (isset($_SESSION['success_message'])) { // Başka bir sayfadan gelmiş olabilir (örn: buraya direkt yönlendirme)
                    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
                    unset($_SESSION['success_message']);
                }
                ?>
                <form action="<?php echo htmlspecialchars($base_url . "kitap_duzenle.php?id=" . $kitap_id_duzenle); ?>" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="kitap_adi" class="form-label">Kitap Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="kitap_adi" name="kitap_adi" value="<?php echo htmlspecialchars($kitap_adi_val); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="yazar_adi" class="form-label">Yazar Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="yazar_adi" name="yazar_adi" value="<?php echo htmlspecialchars($yazar_adi_val); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="kategori_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select" id="kategori_id" name="kategori_id" required>
                                <option value="">Kategori Seçiniz...</option>
                                <?php foreach ($kategoriler_options_edit as $kategori_opt): ?>
                                    <option value="<?php echo $kategori_opt['KategoriID']; ?>" <?php echo ($kategori_id_val == $kategori_opt['KategoriID'] ? 'selected' : ''); ?>>
                                        <?php echo htmlspecialchars($kategori_opt['KategoriAdi']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="kondisyon" class="form-label">Kondisyon <span class="text-danger">*</span></label>
                            <select class="form-select" id="kondisyon" name="kondisyon" required>
                                <option value="">Kondisyon Seçiniz...</option>
                                <option value="Yeni Gibi" <?php echo ($kondisyon_val == 'Yeni Gibi' ? 'selected' : ''); ?>>Yeni Gibi</option>
                                <option value="Çok İyi" <?php echo ($kondisyon_val == 'Çok İyi' ? 'selected' : ''); ?>>Çok İyi</option>
                                <option value="İyi" <?php echo ($kondisyon_val == 'İyi' ? 'selected' : ''); ?>>İyi</option>
                                <option value="Orta" <?php echo ($kondisyon_val == 'Orta' ? 'selected' : ''); ?>>Orta</option>
                                <option value="Yıpranmış" <?php echo ($kondisyon_val == 'Yıpranmış' ? 'selected' : ''); ?>>Yıpranmış</option>
                            </select>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="fiyat" class="form-label">Fiyat (TL) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fiyat" name="fiyat" value="<?php echo htmlspecialchars(str_replace('.', ',', $fiyat_val)); // Gösterirken virgül ?>" required placeholder="örn: 25,50">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="stok_adedi" class="form-label">Stok Adedi <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="stok_adedi" name="stok_adedi" value="<?php echo htmlspecialchars($stok_adedi_val); ?>" required min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                             <label for="durum" class="form-label">Durum <span class="text-danger">*</span></label>
                             <select class="form-select" id="durum" name="durum" required>
                                <option value="Satışta" <?php echo ($durum_val == 'Satışta' ? 'selected' : ''); ?>>Satışta</option>
                                <option value="Satıldı" <?php echo ($durum_val == 'Satıldı' ? 'selected' : ''); ?>>Satıldı</option>
                                <option value="Listeden Kaldırıldı" <?php echo ($durum_val == 'Listeden Kaldırıldı' ? 'selected' : ''); ?>>Listeden Kaldırıldı</option>
                             </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="isbn" class="form-label">ISBN (İsteğe Bağlı)</label>
                        <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo htmlspecialchars($isbn_val); ?>">
                    </div>
                     <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="yayin_evi" class="form-label">Yayınevi (İsteğe Bağlı)</label>
                            <input type="text" class="form-control" id="yayin_evi" name="yayin_evi" value="<?php echo htmlspecialchars($yayin_evi_val); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="baski_yili" class="form-label">Baskı Yılı (İsteğe Bağlı)</label>
                            <input type="number" class="form-control" id="baski_yili" name="baski_yili" value="<?php echo htmlspecialchars($baski_yili_val); ?>" min="1000" max="<?php echo date('Y'); ?>">
                        </div>
                         <div class="col-md-4 mb-3">
                            <label for="sayfa_sayisi" class="form-label">Sayfa Sayısı (İsteğe Bağlı)</label>
                            <input type="number" class="form-control" id="sayfa_sayisi" name="sayfa_sayisi" value="<?php echo htmlspecialchars($sayfa_sayisi_val); ?>" min="1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="aciklama" class="form-label">Açıklama (İsteğe Bağlı)</label>
                        <textarea class="form-control" id="aciklama" name="aciklama" rows="3"><?php echo htmlspecialchars($aciklama_val); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="kapak_fotografi" class="form-label">Yeni Kapak Fotoğrafı (Değiştirmek İstemiyorsanız Boş Bırakın)</label>
                        <input class="form-control" type="file" id="kapak_fotografi" name="kapak_fotografi">
                        <?php if ($mevcut_kapak_url): 
                            $display_kapak_url = $base_url . 'images/placeholder_kapak.png'; // Varsayılan
                            if (!filter_var($mevcut_kapak_url, FILTER_VALIDATE_URL) && strpos($mevcut_kapak_url, 'uploads/') === 0) {
                                $display_kapak_url = $base_url . htmlspecialchars($mevcut_kapak_url);
                            } elseif (filter_var($mevcut_kapak_url, FILTER_VALIDATE_URL)) {
                                $display_kapak_url = htmlspecialchars($mevcut_kapak_url);
                            }
                        ?>
                            <small class="d-block mt-2">Mevcut Kapak: <img src="<?php echo $display_kapak_url; ?>" alt="Mevcut Kapak" style="max-height: 50px;"></small>
                        <?php endif; ?>
                    </div>
                    
                    <hr>
                    <button type="submit" class="btn btn-success w-100 mb-2">Değişiklikleri Kaydet</button>
                    <a href="<?php echo htmlspecialchars($base_url . "profil.php#sattiklarim"); ?>" class="btn btn-secondary w-100">İptal</a>
                     </form>
                </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>