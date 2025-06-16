<?php
// $page_title dinamik olarak ayarlanacak
require_once 'includes/header.php'; // $db, $base_url, $current_user_id burada dahil edilir

// Giriş yapmamış kullanıcıları giriş sayfasına yönlendir
if (!$current_user_id) {
    $_SESSION['error_message'] = "Sipariş detaylarını görüntüleyebilmek için lütfen giriş yapın.";
    header("Location: " . $base_url . "giris.php");
    exit();
}

$siparis_id = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $siparis_id = intval($_GET['id']);
} else {
    $_SESSION['error_message'] = "Geçersiz sipariş ID'si.";
    header("Location: " . $base_url . "profil.php#siparislerim");
    exit();
}

$siparis_ana_bilgiler = null;
$siparis_urunleri = [];

try {
    // Siparişin ana bilgilerini ve alıcının gerçekten bu kullanıcı olup olmadığını kontrol et
    // Yeni oluşturduğumuz sp_SiparisGetirByID yordamını kullanalım
    $stmt_siparis_ana = $db->prepare("CALL sp_SiparisGetirByID(:p_SiparisID, :p_AliciKullaniciID)");
    $stmt_siparis_ana->bindParam(':p_SiparisID', $siparis_id, PDO::PARAM_INT);
    $stmt_siparis_ana->bindParam(':p_AliciKullaniciID', $current_user_id, PDO::PARAM_INT);
    $stmt_siparis_ana->execute();
    $siparis_ana_bilgiler = $stmt_siparis_ana->fetch();
    $stmt_siparis_ana->closeCursor();

    if (!$siparis_ana_bilgiler) {
        $_SESSION['error_message'] = "Sipariş bulunamadı veya bu siparişi görüntüleme yetkiniz yok.";
        header("Location: " . $base_url . "profil.php#siparislerim");
        exit();
    }

    $page_title = "Sipariş Detayı: #" . htmlspecialchars($siparis_ana_bilgiler['SiparisID']);
    echo "<script>document.title = '" . addslashes($page_title) . " - SahafNet';</script>";

    // Siparişe ait ürünleri çek
    $stmt_urunler = $db->prepare("CALL sp_SiparisDetaylariniGetir(:p_SiparisID)");
    $stmt_urunler->bindParam(':p_SiparisID', $siparis_id, PDO::PARAM_INT);
    $stmt_urunler->execute();
    $siparis_urunleri = $stmt_urunler->fetchAll();
    $stmt_urunler->closeCursor();

} catch (PDOException $e) {
    error_log("Sipariş detay sayfası - Veri çekme hatası: " . $e->getMessage());
    $_SESSION['error_message'] = "Sipariş detayları yüklenirken bir sorun oluştu.";
    header("Location: " . $base_url . "profil.php#siparislerim");
    exit();
}
?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($base_url . 'profil.php'); ?>">Profilim</a></li>
                <li class="breadcrumb-item"><a href="<?php echo htmlspecialchars($base_url . 'profil.php#siparislerim'); ?>">Siparişlerim</a></li>
                <li class="breadcrumb-item active" aria-current="page">Sipariş #<?php echo htmlspecialchars($siparis_ana_bilgiler['SiparisID']); ?></li>
            </ol>
        </nav>

        <div class="card">
            <div class="card-header">
                <h3>Sipariş Detayı: #<?php echo htmlspecialchars($siparis_ana_bilgiler['SiparisID']); ?></h3>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h4>Sipariş Bilgileri</h4>
                        <table class="table table-sm">
                            <tr>
                                <th style="width: 40%;">Sipariş Tarihi:</th>
                                <td><?php echo date("d.m.Y H:i", strtotime($siparis_ana_bilgiler['SiparisTarihi'])); ?></td>
                            </tr>
                            <tr>
                                <th>Sipariş Durumu:</th>
                                <td><span class="badge bg-primary"><?php echo htmlspecialchars($siparis_ana_bilgiler['SiparisDurumu']); ?></span></td>
                            </tr>
                            <tr>
                                <th>Ödeme Yöntemi:</th>
                                <td><?php echo htmlspecialchars($siparis_ana_bilgiler['OdemeYontemi'] ?: '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Kargo Takip No:</th>
                                <td><?php echo htmlspecialchars($siparis_ana_bilgiler['KargoTakipNo'] ?: '-'); ?></td>
                            </tr>
                            <tr>
                                <th>Toplam Tutar:</th>
                                <td><strong><?php echo htmlspecialchars(number_format($siparis_ana_bilgiler['ToplamTutar'], 2, ',', '.')) . ' TL'; ?></strong></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h4>Teslimat Adresi</h4>
                        <p>
                            <strong><?php echo htmlspecialchars($siparis_ana_bilgiler['AdresBasligi']); ?></strong><br>
                            <?php echo htmlspecialchars($siparis_ana_bilgiler['AcikAdres']); ?><br>
                            <?php echo htmlspecialchars($siparis_ana_bilgiler['Mahalle'] ? $siparis_ana_bilgiler['Mahalle'] . ' Mah. ' : ''); ?>
                            <?php echo htmlspecialchars($siparis_ana_bilgiler['Ilce'] . ' / ' . $siparis_ana_bilgiler['Il']); ?><br>
                            <?php echo htmlspecialchars($siparis_ana_bilgiler['PostaKodu'] ?: ''); ?>
                        </p>
                        <h5>Alıcı Bilgileri</h5>
                        <p>
                            <?php echo htmlspecialchars($siparis_ana_bilgiler['AliciAdSoyad']); ?><br>
                            <?php echo htmlspecialchars($siparis_ana_bilgiler['AliciEposta']); ?><br>
                            <?php echo htmlspecialchars($siparis_ana_bilgiler['AliciTelefon'] ?: '-'); ?>
                        </p>
                    </div>
                </div>

                <h4>Sipariş Edilen Ürünler</h4>
                <?php if (!empty($siparis_urunleri)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Kitap Adı</th>
                                    <th>Yazar</th>
                                    <th class="text-end">Birim Fiyat</th>
                                    <th class="text-center">Adet</th>
                                    <th class="text-end">Ara Toplam</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $genel_toplam_kontrol = 0;
                                foreach ($siparis_urunleri as $urun): 
                                    $ara_toplam = $urun['Adet'] * $urun['BirimFiyat'];
                                    $genel_toplam_kontrol += $ara_toplam;
                                ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($base_url . 'kitap_detay.php?id=' . $urun['KitapID']); ?>">
                                                <?php echo htmlspecialchars($urun['KitapAdi']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($urun['YazarAdi']); ?></td>
                                        <td class="text-end"><?php echo htmlspecialchars(number_format($urun['BirimFiyat'], 2, ',', '.')) . ' TL'; ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($urun['Adet']); ?></td>
                                        <td class="text-end"><?php echo htmlspecialchars(number_format($ara_toplam, 2, ',', '.')) . ' TL'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-end">Genel Toplam:</th>
                                    <td class="text-end"><strong><?php echo htmlspecialchars(number_format($genel_toplam_kontrol, 2, ',', '.')) . ' TL'; ?></strong></td>
                                </tr>
                                <?php if(abs($genel_toplam_kontrol - $siparis_ana_bilgiler['ToplamTutar']) > 0.01): // Küçük bir toleransla kontrol ?>
                                    <tr>
                                        <td colspan="5" class="text-danger text-center">Uyarı: Sipariş genel toplamı ile ürün ara toplamları uyuşmuyor! (Sipariş Ana Toplam: <?php echo htmlspecialchars(number_format($siparis_ana_bilgiler['ToplamTutar'], 2, ',', '.')); ?> TL)</td>
                                    </tr>
                                <?php endif; ?>
                            </tfoot>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Bu siparişe ait ürün bulunamadı.</p>
                <?php endif; ?>
                 <div class="mt-4">
                    <a href="<?php echo htmlspecialchars($base_url . 'profil.php#siparislerim'); ?>" class="btn btn-secondary">Siparişlerime Geri Dön</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>