<?php
$page_title = "Siparişi Tamamla";
require_once 'includes/header.php'; // $db, $base_url, $current_user_id burada dahil edilir

// Giriş yapmamış kullanıcıları giriş sayfasına yönlendir
if (!$current_user_id) {
    $_SESSION['error_message'] = "Sipariş verebilmek için lütfen giriş yapın.";
    header("Location: " . $base_url . "giris.php?return_url=" . urlencode($base_url."sepet.php"));
    exit();
}

// Sepet boşsa, sepet sayfasına yönlendir
$sepet = isset($_SESSION['sepet']) ? $_SESSION['sepet'] : [];
if (empty($sepet)) {
    $_SESSION['error_message'] = "Sipariş verebilmek için sepetinizde ürün bulunmalıdır.";
    header("Location: " . $base_url . "sepet.php");
    exit();
}

// Kullanıcının adreslerini çek
$adresler_kullanici = [];
try {
    $stmt_adresler = $db->prepare("CALL sp_KullaniciAdresleriniGetir(:p_KullaniciID)");
    $stmt_adresler->bindParam(':p_KullaniciID', $current_user_id, PDO::PARAM_INT);
    $stmt_adresler->execute();
    $adresler_kullanici = $stmt_adresler->fetchAll();
    $stmt_adresler->closeCursor();
} catch (PDOException $e) {
    error_log("Sipariş verme sayfası - Adresleri çekme hatası: " . $e->getMessage());
    // Hata durumunda adres listesi boş kalır, kullanıcıya mesaj gösterilebilir.
}

$hatalar_siparis = [];
$secili_adres_id = null;

// Form gönderilmiş mi kontrol et (Siparişi Onayla)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['siparis_onayla'])) {
    $secili_adres_id = isset($_POST['teslimat_adresi_id']) ? intval($_POST['teslimat_adresi_id']) : null;
    $odeme_yontemi = isset($_POST['odeme_yontemi']) ? trim($_POST['odeme_yontemi']) : 'Kapıda Ödeme'; // Varsayılan

    if (empty($secili_adres_id)) {
        $hatalar_siparis[] = "Lütfen bir teslimat adresi seçin.";
    }
    // Ödeme yöntemi için daha fazla doğrulama eklenebilir.

    if (empty($hatalar_siparis)) {
        $db->beginTransaction(); // Veritabanı işlemlerini bir transaction içinde yap
        try {
            $siparis_toplam_tutar = 0;
            foreach ($sepet as $id => $urun) {
                $siparis_toplam_tutar += $urun['fiyat'] * $urun['adet'];
            }

            // 1. Siparisler tablosuna ana sipariş kaydını at
            $stmt_siparis_olustur = $db->prepare("CALL sp_SiparisOlustur(:p_AliciKullaniciID, :p_TeslimatAdresID, :p_ToplamTutar, :p_OdemeYontemi)");
            $stmt_siparis_olustur->bindParam(':p_AliciKullaniciID', $current_user_id, PDO::PARAM_INT);
            $stmt_siparis_olustur->bindParam(':p_TeslimatAdresID', $secili_adres_id, PDO::PARAM_INT);
            $stmt_siparis_olustur->bindParam(':p_ToplamTutar', $siparis_toplam_tutar, PDO::PARAM_STR); // DECIMAL için STR
            $stmt_siparis_olustur->bindParam(':p_OdemeYontemi', $odeme_yontemi, PDO::PARAM_STR);
            $stmt_siparis_olustur->execute();
            $yeni_siparis_sonuc = $stmt_siparis_olustur->fetch();
            $yeni_siparis_id = ($yeni_siparis_sonuc && isset($yeni_siparis_sonuc['YeniSiparisID'])) ? $yeni_siparis_sonuc['YeniSiparisID'] : null;
            $stmt_siparis_olustur->closeCursor();

            if (!$yeni_siparis_id) {
                throw new PDOException("Sipariş oluşturulamadı, ID alınamadı.");
            }

            // 2. Sepetteki her bir ürün için SiparisDetaylari tablosuna kayıt at
            foreach ($sepet as $kitap_id_sepet => $urun_detay) {
                // Stok kontrolü (önemli!) - Eğer trigger yapmıyorsa burada yapılmalı
                // Veya trigger'ın hata fırlatması beklenmeli.
                // Şimdilik trigger'a güveniyoruz.
                
                $stmt_detay_ekle = $db->prepare("CALL sp_SiparisDetayEkle(:p_SiparisID, :p_KitapID, :p_Adet, :p_BirimFiyat)");
                $stmt_detay_ekle->bindParam(':p_SiparisID', $yeni_siparis_id, PDO::PARAM_INT);
                $stmt_detay_ekle->bindParam(':p_KitapID', $kitap_id_sepet, PDO::PARAM_INT);
                $stmt_detay_ekle->bindParam(':p_Adet', $urun_detay['adet'], PDO::PARAM_INT);
                $stmt_detay_ekle->bindParam(':p_BirimFiyat', $urun_detay['fiyat'], PDO::PARAM_STR); // DECIMAL için STR
                $stmt_detay_ekle->execute();
                $stmt_detay_ekle->closeCursor(); 
                // `trg_SiparisDetayEkle_StokAzalt_TutarGuncelle` trigger'ı burada çalışarak Kitaplar.StokAdedi'ni düşürmeli
                // ve Siparisler.ToplamTutar'ı güncellemelidir.
                // Eğer trigger ToplamTutar'ı her detay eklendiğinde güncellemiyorsa, en sonda sp_SiparisToplamTutarGuncelle çağrılabilir.
            }
            
            // 3. (Opsiyonel) Eğer trigger ToplamTutar'ı sonradan hesaplayacaksa:
            // $stmt_tutar_guncelle = $db->prepare("CALL sp_SiparisToplamTutarGuncelle(:p_SiparisID)");
            // $stmt_tutar_guncelle->bindParam(':p_SiparisID', $yeni_siparis_id, PDO::PARAM_INT);
            // $stmt_tutar_guncelle->execute();
            // $stmt_tutar_guncelle->closeCursor();

            $db->commit(); // Tüm işlemler başarılıysa transaction'ı onayla

            // 4. Sepeti boşalt
            $_SESSION['sepet'] = array();

            $_SESSION['success_message'] = "Siparişiniz başarıyla oluşturuldu! Sipariş Numaranız: #" . $yeni_siparis_id;
            header("Location: " . $base_url . "profil.php#siparislerim");
            exit();

        } catch (PDOException $e) {
            $db->rollBack(); // Hata oluşursa tüm işlemleri geri al
            error_log("Sipariş oluşturma hatası: " . $e->getMessage());
            // Check constraint 'chk_stok' hatası (SQLSTATE 23000 veya farklı olabilir)
            if (strpos($e->getMessage(), 'chk_stok') !== false || (isset($e->errorInfo[1]) && $e->errorInfo[1] == 3819) ) {
                 $hatalar_siparis[] = "Sipariş oluşturulamadı: Bir veya daha fazla kitabın stoğu yetersiz. Lütfen sepetinizi kontrol edin.";
            } else {
                 $hatalar_siparis[] = "Sipariş oluşturulurken bir veritabanı hatası oluştu. Lütfen daha sonra tekrar deneyin.";
            }
        }
    }
}

// Sepet toplamını hesapla (HTML'de göstermek için)
$sepet_gosterim_toplami = 0;
foreach ($sepet as $id_sepet => $urun_sepet) {
    $sepet_gosterim_toplami += $urun_sepet['fiyat'] * $urun_sepet['adet'];
}
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <h3>Siparişi Tamamla</h3>
        <hr>

        <?php if (!empty($hatalar_siparis)): ?>
            <div class="alert alert-danger">
                <?php foreach ($hatalar_siparis as $hata): ?>
                    <p class="mb-0"><?php echo htmlspecialchars($hata); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="card mb-4">
                <div class="card-header">Teslimat Adresi</div>
                <div class="card-body">
                    <?php if (!empty($adresler_kullanici)): ?>
                        <p>Lütfen siparişinizin teslim edileceği adresi seçin:</p>
                        <?php foreach ($adresler_kullanici as $adres_k): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="teslimat_adresi_id" 
                                       id="adres_<?php echo $adres_k['AdresID']; ?>" 
                                       value="<?php echo $adres_k['AdresID']; ?>" 
                                       <?php echo (isset($secili_adres_id) && $secili_adres_id == $adres_k['AdresID'] ? 'checked' : ($_SERVER["REQUEST_METHOD"] != "POST" && $adres_k === reset($adresler_kullanici) ? 'checked' : '')); // Form ilk yüklendiğinde ilk adresi seçili yap ?>
                                       required>
                                <label class="form-check-label" for="adres_<?php echo $adres_k['AdresID']; ?>">
                                    <strong><?php echo htmlspecialchars($adres_k['AdresBasligi']); ?></strong><br>
                                    <?php echo htmlspecialchars($adres_k['AcikAdres']); ?>, 
                                    <?php echo htmlspecialchars($adres_k['Mahalle'] ? $adres_k['Mahalle'] . ' Mah. ' : ''); ?>
                                    <?php echo htmlspecialchars($adres_k['Ilce'] . '/' . $adres_k['Il']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                        <a href="<?php echo htmlspecialchars($base_url . 'adres_ekle.php?return_url=' . urlencode($_SERVER['REQUEST_URI'])); ?>" class="btn btn-sm btn-outline-primary">Yeni Adres Ekle</a>

                    <?php else: ?>
                        <p class="text-danger">Kayıtlı teslimat adresiniz bulunmuyor. Lütfen öncelikle bir adres ekleyin.</p>
                        <a href="<?php echo htmlspecialchars($base_url . 'adres_ekle.php?return_url=' . urlencode($_SERVER['REQUEST_URI'])); ?>" class="btn btn-primary">Adres Ekle</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Ödeme Yöntemi</div>
                <div class="card-body">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="odeme_yontemi" id="kapida_odeme" value="Kapıda Ödeme" checked required>
                        <label class="form-check-label" for="kapida_odeme">
                            Kapıda Ödeme
                        </label>
                    </div>
                    </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">Sipariş Özeti</div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($sepet as $id_ozet => $urun_ozet): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <?php echo htmlspecialchars($urun_ozet['ad']); ?> 
                                <small class="text-muted">(<?php echo $urun_ozet['adet']; ?> adet x <?php echo htmlspecialchars(number_format($urun_ozet['fiyat'], 2, ',', '.')); ?> TL)</small>
                            </span>
                            <span><?php echo htmlspecialchars(number_format($urun_ozet['fiyat'] * $urun_ozet['adet'], 2, ',', '.')); ?> TL</span>
                        </li>
                    <?php endforeach; ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center active">
                        <strong>Genel Toplam</strong>
                        <strong><?php echo htmlspecialchars(number_format($sepet_gosterim_toplami, 2, ',', '.')); ?> TL</strong>
                    </li>
                </ul>
            </div>
            
            <?php if (!empty($adresler_kullanici)): // Adres varsa sipariş butonu aktif ?>
            <button type="submit" name="siparis_onayla" class="btn btn-lg btn-success w-100">Siparişi Onayla ve Tamamla</button>
            <?php else: ?>
            <button type="submit" name="siparis_onayla" class="btn btn-lg btn-success w-100" disabled>Siparişi Onayla (Önce Adres Ekleyin)</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>