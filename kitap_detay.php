<?php
// $page_title dinamik olarak ayarlanacak
require_once 'includes/header.php'; // $db, $base_url, $current_user_id burada dahil edilir

$kitap_id = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $kitap_id = intval($_GET['id']);
} else {
    $_SESSION['error_message'] = "Geçersiz kitap ID'si belirtildi.";
    header("Location: " . $base_url . "index.php");
    exit();
}

$kitap_detay = null;
$satici_bilgileri = null;
$kategori_adi = null;

try {
    $stmt_kitap = $db->prepare("CALL sp_KitapGetirByID(:p_KitapID)");
    $stmt_kitap->bindParam(':p_KitapID', $kitap_id, PDO::PARAM_INT);
    $stmt_kitap->execute();
    $kitap_detay = $stmt_kitap->fetch();
    $stmt_kitap->closeCursor();

    if ($kitap_detay) {
        // Sayfa başlığını JavaScript ile güncelleyeceğiz (header.php zaten yüklendi)
        $satici_bilgileri = $kitap_detay['SaticiAdi'];
        $kategori_adi = $kitap_detay['KategoriAdi'];
    } else {
        $_SESSION['error_message'] = "Aradığınız kitap bulunamadı veya şu anda mevcut değil.";
        header("Location: " . $base_url . "index.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Kitap detay sayfası - Kitap çekme hatası: " . $e->getMessage());
    $_SESSION['error_message'] = "Kitap bilgileri yüklenirken bir sorun oluştu.";
    header("Location: " . $base_url . "index.php");
    exit();
}

// Kitaba ait onaylanmış yorumları çek
$yorumlar = [];
try {
    $stmt_yorumlar = $db->prepare("CALL sp_KitapYorumlariniGetir(:p_KitapID)");
    $stmt_yorumlar->bindParam(':p_KitapID', $kitap_id, PDO::PARAM_INT);
    $stmt_yorumlar->execute();
    $yorumlar = $stmt_yorumlar->fetchAll();
    $stmt_yorumlar->closeCursor();
} catch (PDOException $e) {
    error_log("Kitap detay sayfası - Yorumları çekme hatası: " . $e->getMessage());
}

// Ortalama Puan Hesaplama
$ortalama_puan = 0;
if (count($yorumlar) > 0) {
    $toplam_puan_sum = 0;
    foreach($yorumlar as $y) {
        $toplam_puan_sum += $y['Puan'];
    }
    $ortalama_puan = $toplam_puan_sum / count($yorumlar);
}

// Sayfa başlığını dinamik olarak ayarlamak için JavaScript
if ($kitap_detay) {
    echo "<script>document.title = '" . addslashes(htmlspecialchars($kitap_detay['KitapAdi'])) . " - SahafNet';</script>";
}
?>

<div class="row mb-5">
    <div class="col-md-4">
        <?php
            $kapak_gorseli = $base_url . 'images/placeholder_kapak.png';
            if (!empty($kitap_detay['KapakFotografiURL'])) {
                if (!filter_var($kitap_detay['KapakFotografiURL'], FILTER_VALIDATE_URL) && strpos($kitap_detay['KapakFotografiURL'], 'uploads/') === 0) {
                    $kapak_gorseli = $base_url . htmlspecialchars($kitap_detay['KapakFotografiURL']);
                } elseif (filter_var($kitap_detay['KapakFotografiURL'], FILTER_VALIDATE_URL)) {
                    $kapak_gorseli = htmlspecialchars($kitap_detay['KapakFotografiURL']);
                }
            }
        ?>
        <img src="<?php echo $kapak_gorseli; ?>" class="img-fluid rounded shadow-sm mb-3" alt="<?php echo htmlspecialchars($kitap_detay['KitapAdi']); ?>" style="max-height: 500px; object-fit: contain;">
      <div class="d-grid gap-2">
             <?php if ($kitap_detay['StokAdedi'] > 0 && $kitap_detay['Durum'] == 'Satışta'): ?>
                <form action="<?php echo htmlspecialchars($base_url . 'sepet_islemleri.php'); ?>" method="POST">
                    <input type="hidden" name="action" value="ekle">
                    <input type="hidden" name="kitap_id" value="<?php echo $kitap_detay['KitapID']; ?>">
                    <input type="hidden" name="kitap_adi" value="<?php echo htmlspecialchars($kitap_detay['KitapAdi']); ?>">
                    <input type="hidden" name="fiyat" value="<?php echo $kitap_detay['Fiyat']; ?>">
                    <div class="input-group mb-3">
                        <input type="number" name="adet" class="form-control form-control-sm" value="1" min="1" max="<?php echo $kitap_detay['StokAdedi']; ?>" style="max-width: 70px;">
                        <button class="btn btn-success btn-lg flex-grow-1" type="submit">
                             Sepete Ekle
                        </button>
                    </div>
                </form>
             <?php elseif ($kitap_detay['Durum'] == 'Satıldı'): ?>
                <button class="btn btn-danger btn-lg" type="button" disabled>Bu Kitap Satıldı</button>
             <?php else: ?>
                <button class="btn btn-warning btn-lg" type="button" disabled>Şu An Satışta Değil</button>
            <?php endif; ?>
        </div>
        <small class="text-muted d-block mt-2">Satıcı: <?php echo htmlspecialchars($satici_bilgileri ?: 'Bilinmiyor'); ?></small>
        <small class="text-muted d-block">Stok: <?php echo $kitap_detay['StokAdedi'] > 0 ? htmlspecialchars($kitap_detay['StokAdedi']) . ' adet' : 'Tükendi'; ?></small>
    </div>

    <div class="col-md-8">
        <h2><?php echo htmlspecialchars($kitap_detay['KitapAdi']); ?></h2>
        <h5 class="text-muted mb-3"><?php echo htmlspecialchars($kitap_detay['YazarAdi']); ?></h5>
        
        <div class="mb-3">
            <strong>Kategori:</strong> <a href="<?php echo htmlspecialchars($base_url . 'kitaplar.php?kategori_id=' . $kitap_detay['KategoriID']); ?>"><?php echo htmlspecialchars($kategori_adi ?: 'Belirtilmemiş'); ?></a> <br>
            <strong>Kondisyon:</strong> <span class="badge bg-secondary"><?php echo htmlspecialchars($kitap_detay['Kondisyon']); ?></span> <br>
            <?php if ($ortalama_puan > 0): ?>
                 <strong>Ortalama Puan:</strong> <?php echo number_format($ortalama_puan, 1); ?> / 5 (<?php echo count($yorumlar); ?> yorum)
            <?php else: ?>
                 <strong>Puan:</strong> Henüz yorum yapılmamış.
            <?php endif; ?>
        </div>

        <ul class="nav nav-tabs mb-3" id="kitapDetayTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="aciklama-tab" data-bs-toggle="tab" data-bs-target="#aciklama-tab-pane" type="button" role="tab" aria-controls="aciklama-tab-pane" aria-selected="true">Açıklama</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="detaylar-tab" data-bs-toggle="tab" data-bs-target="#detaylar-tab-pane" type="button" role="tab" aria-controls="detaylar-tab-pane" aria-selected="false">Kitap Bilgileri</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="yorumlar-tab" data-bs-toggle="tab" data-bs-target="#yorumlar-tab-pane" type="button" role="tab" aria-controls="yorumlar-tab-pane" aria-selected="false">Yorumlar (<?php echo count($yorumlar); ?>)</button>
            </li>
        </ul>
        <div class="tab-content" id="kitapDetayTabContent">
            <div class="tab-pane fade show active" id="aciklama-tab-pane" role="tabpanel" aria-labelledby="aciklama-tab" tabindex="0">
                <p><?php echo nl2br(htmlspecialchars($kitap_detay['Aciklama'] ?: 'Bu kitap için bir açıklama girilmemiştir.')); ?></p>
            </div>
            <div class="tab-pane fade" id="detaylar-tab-pane" role="tabpanel" aria-labelledby="detaylar-tab" tabindex="0">
                <table class="table table-sm table-striped">
                    <?php if (!empty($kitap_detay['ISBN'])): ?>
                    <tr><th style="width:30%;">ISBN:</th><td><?php echo htmlspecialchars($kitap_detay['ISBN']); ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($kitap_detay['YayinEvi'])): ?>
                    <tr><th>Yayınevi:</th><td><?php echo htmlspecialchars($kitap_detay['YayinEvi']); ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($kitap_detay['BaskiYili'])): ?>
                    <tr><th>Baskı Yılı:</th><td><?php echo htmlspecialchars($kitap_detay['BaskiYili']); ?></td></tr>
                    <?php endif; ?>
                    <?php if (!empty($kitap_detay['SayfaSayisi'])): ?>
                    <tr><th>Sayfa Sayısı:</th><td><?php echo htmlspecialchars($kitap_detay['SayfaSayisi']); ?></td></tr>
                    <?php endif; ?>
                     <tr><th>Listeleme Tarihi:</th><td><?php echo date("d.m.Y H:i", strtotime($kitap_detay['ListelemeTarihi'])); ?></td></tr>
                </table>
            </div>
            <div class="tab-pane fade" id="yorumlar-tab-pane" role="tabpanel" aria-labelledby="yorumlar-tab" tabindex="0">
                <?php
                // yorum_ekle.php'den gelen başarı veya hata mesajlarını göster
                if (isset($_SESSION['success_message'])) {
                    echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
                    unset($_SESSION['success_message']);
                }
                if (isset($_SESSION['error_message'])) {
                    echo '<div class="alert alert-danger">' . htmlspecialchars($_SESSION['error_message']) . '</div>';
                    unset($_SESSION['error_message']);
                }

                // yorum_ekle.php'den gelen form doğrulama hatalarını göster
                $form_errors_yorum = isset($_SESSION['form_errors']) ? $_SESSION['form_errors'] : [];
                $form_values_yorum = isset($_SESSION['form_values']) ? $_SESSION['form_values'] : ['puan' => '', 'yorum_metni' => ''];
                if (!empty($form_errors_yorum)) {
                    echo '<div class="alert alert-danger">';
                    foreach ($form_errors_yorum as $form_hata_yorum) {
                        echo '<p class="mb-0">' . htmlspecialchars($form_hata_yorum) . '</p>';
                    }
                    echo '</div>';
                }
                unset($_SESSION['form_errors']); 
                ?>

                <?php if (!empty($yorumlar)): ?>
                    <?php foreach ($yorumlar as $yorum): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo htmlspecialchars($yorum['YorumYapan'] ?: 'Anonim'); ?> 
                                    <small class="text-muted">- <?php echo date("d.m.Y H:i", strtotime($yorum['YorumTarihi'])); ?></small>
                                </h6>
                                <p class="mb-1"><strong>Puan: <?php echo str_repeat('⭐', $yorum['Puan']) . str_repeat('☆', 5 - $yorum['Puan']); ?> (<?php echo $yorum['Puan']; ?>/5)</strong></p>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($yorum['YorumMetni'] ?: 'Yorum metni girilmemiş.')); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Bu kitap için henüz yorum yapılmamış. İlk yorumu siz yapın!</p>
                <?php endif; ?>
                <hr>
                
                <?php if ($current_user_id): ?>
                    <h5 id="yorum-formu-alani">Yorum Yap</h5> {/* Forma ID eklendi */}
                   <form action="<?php echo htmlspecialchars($base_url . 'sepet_islemleri.php'); ?>" method="POST">
    </form>
                        <input type="hidden" name="kitap_id" value="<?php echo $kitap_id; ?>">
                        <div class="mb-3">
                            <label for="puan" class="form-label">Puanınız:</label>
                            <select name="puan" id="puan" class="form-select" required style="width: auto;">
                                <option value="">Seçin...</option>
                                <option value="5" <?php echo (isset($form_values_yorum['puan']) && $form_values_yorum['puan'] == 5 ? 'selected' : ''); ?>>5 Yıldız (Harika)</option>
                                <option value="4" <?php echo (isset($form_values_yorum['puan']) && $form_values_yorum['puan'] == 4 ? 'selected' : ''); ?>>4 Yıldız (İyi)</option>
                                <option value="3" <?php echo (isset($form_values_yorum['puan']) && $form_values_yorum['puan'] == 3 ? 'selected' : ''); ?>>3 Yıldız (Orta)</option>
                                <option value="2" <?php echo (isset($form_values_yorum['puan']) && $form_values_yorum['puan'] == 2 ? 'selected' : ''); ?>>2 Yıldız (Kötü Değil)</option>
                                <option value="1" <?php echo (isset($form_values_yorum['puan']) && $form_values_yorum['puan'] == 1 ? 'selected' : ''); ?>>1 Yıldız (Kötü)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="yorum_metni" class="form-label">Yorumunuz:</label>
                            <textarea name="yorum_metni" id="yorum_metni" rows="3" class="form-control"><?php echo isset($form_values_yorum['yorum_metni']) ? htmlspecialchars($form_values_yorum['yorum_metni']) : ''; ?></textarea>
                        </div>
                        <?php unset($_SESSION['form_values']); // Değerleri kullandıktan sonra sil ?>
                        <button type="submit" class="btn btn-primary">Yorumu Gönder</button>
                    </form>
                <?php else: ?>
                    <p>Yorum yapabilmek için <a href="<?php echo htmlspecialchars($base_url . 'giris.php?return_url=' . urlencode($_SERVER['REQUEST_URI'])); ?>">giriş yapmanız</a> gerekmektedir.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>