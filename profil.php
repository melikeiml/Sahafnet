<?php
$page_title = "Profilim";
require_once 'includes/header.php'; // $db, $base_url, $current_user_id burada dahil edilir

// Giriş yapmamış kullanıcıları giriş sayfasına yönlendir
if (!$current_user_id) {
    $_SESSION['error_message'] = "Profil sayfasını görüntüleyebilmek için lütfen giriş yapın.";
    header("Location: " . $base_url . "giris.php");
    exit();
}

// Kullanıcı bilgilerini çek
$kullanici_bilgileri = null;
try {
    $stmt_user = $db->prepare("CALL sp_KullaniciGetirByID(:p_KullaniciID)");
    $stmt_user->bindParam(':p_KullaniciID', $current_user_id, PDO::PARAM_INT);
    $stmt_user->execute();
    $kullanici_bilgileri = $stmt_user->fetch();
    $stmt_user->closeCursor();
} catch (PDOException $e) {
    error_log("Profil sayfası - Kullanıcı bilgileri çekme hatası: " . $e->getMessage());
}

// Kullanıcının adreslerini çek
$adresler = [];
try {
    $stmt_adresler = $db->prepare("CALL sp_KullaniciAdresleriniGetir(:p_KullaniciID)");
    $stmt_adresler->bindParam(':p_KullaniciID', $current_user_id, PDO::PARAM_INT);
    $stmt_adresler->execute();
    $adresler = $stmt_adresler->fetchAll();
    $stmt_adresler->closeCursor();
} catch (PDOException $e) {
    error_log("Profil sayfası - Adresleri çekme hatası: " . $e->getMessage());
}

// Kullanıcının siparişlerini çek
$siparisler = [];
try {
    $stmt_siparisler = $db->prepare("CALL sp_KullaniciSiparisleriniGetir(:p_AliciKullaniciID)");
    $stmt_siparisler->bindParam(':p_AliciKullaniciID', $current_user_id, PDO::PARAM_INT);
    $stmt_siparisler->execute();
    $siparisler = $stmt_siparisler->fetchAll();
    $stmt_siparisler->closeCursor();
} catch (PDOException $e) {
    error_log("Profil sayfası - Siparişleri çekme hatası: " . $e->getMessage());
}

// Kullanıcının satışa çıkardığı kitapları çek
$sattigi_kitaplar = [];
try {
    // Yordam parametre adının MySQL'deki ile aynı olduğundan emin olun (p_SaticiKullaniciID veya p_SatıcıKullaniciID)
    $stmt_sattigi_kitaplar = $db->prepare("CALL sp_KullanicininSatistakiKitaplariniGetir(:p_SaticiKullaniciID)");
    $stmt_sattigi_kitaplar->bindParam(':p_SaticiKullaniciID', $current_user_id, PDO::PARAM_INT);
    $stmt_sattigi_kitaplar->execute();
    $sattigi_kitaplar = $stmt_sattigi_kitaplar->fetchAll();
    $stmt_sattigi_kitaplar->closeCursor();
} catch (PDOException $e) {
    error_log("Profil sayfası - Satıştaki kitapları çekme hatası: " . $e->getMessage());
}

?>

<div class="row">
    <div class="col-md-3">
        <div class="card sticky-top mb-4">
            <div class="card-header">
                <h4>Profilim</h4>
            </div>
            <div class="list-group list-group-flush">
                <a href="#bilgilerim" class="list-group-item list-group-item-action active">Kişisel Bilgilerim</a>
                <a href="#adreslerim" class="list-group-item list-group-item-action">Adreslerim</a>
                <a href="#siparislerim" class="list-group-item list-group-item-action">Siparişlerim</a>
                <a href="#sattiklarim" class="list-group-item list-group-item-action">Satıştaki Kitaplarım</a>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <section id="bilgilerim" class="mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Kişisel Bilgilerim</h5>
                    </div>
                <div class="card-body">
                    <?php if ($kullanici_bilgileri): ?>
                        <table class="table table-borderless">
                            <tr>
                                <th style="width: 30%;">Ad Soyad:</th>
                                <td><?php echo htmlspecialchars($kullanici_bilgileri['Ad'] . ' ' . $kullanici_bilgileri['Soyad']); ?></td>
                            </tr>
                            <tr>
                                <th>E-posta:</th>
                                <td><?php echo htmlspecialchars($kullanici_bilgileri['Eposta']); ?></td>
                            </tr>
                            <tr>
                                <th>Telefon:</th>
                                <td><?php echo htmlspecialchars($kullanici_bilgileri['TelefonNo'] ?: '- Belirtilmemiş -'); ?></td>
                            </tr>
                            <tr>
                                <th>Kayıt Tarihi:</th>
                                <td><?php echo date("d.m.Y H:i", strtotime($kullanici_bilgileri['KayitTarihi'])); ?></td>
                            </tr>
                            <tr>
                                <th>Son Giriş:</th>
                                <td><?php echo $kullanici_bilgileri['SonGirisTarihi'] ? date("d.m.Y H:i", strtotime($kullanici_bilgileri['SonGirisTarihi'])) : '- Henüz Yok -'; ?></td>
                            </tr>
                        </table>
                    <?php else: ?>
                        <p class="text-danger">Kullanıcı bilgileri yüklenemedi.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section id="adreslerim" class="mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Adreslerim</h5>
                    <a href="<?php echo htmlspecialchars($base_url . 'adres_ekle.php'); ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-plus"></i> Yeni Adres Ekle
                    </a>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success_message_adres'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success_message_adres']; unset($_SESSION['success_message_adres']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error_message_adres'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error_message_adres']; unset($_SESSION['error_message_adres']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($adresler)): ?>
                        <div class="list-group">
                            <?php foreach ($adresler as $adres): ?>
                                <div class="list-group-item mb-2">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($adres['AdresBasligi']); ?></h6>
                                        <div>
                                            <a href="<?php echo htmlspecialchars($base_url . 'adres_duzenle.php?id=' . $adres['AdresID']); ?>" class="btn btn-sm btn-outline-primary me-1" title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="<?php echo htmlspecialchars($base_url . 'adres_sil.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Bu adresi silmek istediğinizden emin misiniz? Bu adres siparişlerde kullanılıyorsa silinemeyebilir.');">
                                                <input type="hidden" name="adres_id_sil" value="<?php echo $adres['AdresID']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Sil">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <p class="mb-1">
                                        <?php echo htmlspecialchars($adres['AcikAdres']); ?><br>
                                        <?php echo htmlspecialchars($adres['Mahalle'] ? $adres['Mahalle'] . ' Mah. ' : ''); ?>
                                        <?php echo htmlspecialchars($adres['Ilce'] . ' / ' . $adres['Il']); ?>
                                        <?php echo htmlspecialchars($adres['PostaKodu'] ? ' - ' . $adres['PostaKodu'] : ''); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Kayıtlı adresiniz bulunmamaktadır. <a href="<?php echo htmlspecialchars($base_url . 'adres_ekle.php'); ?>">Hemen bir tane ekleyin!</a></p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section id="siparislerim" class="mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Siparişlerim</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success_message_siparis'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success_message_siparis']; unset($_SESSION['success_message_siparis']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                     <?php if (isset($_SESSION['error_message_siparis'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error_message_siparis']; unset($_SESSION['error_message_siparis']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($siparisler)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Sipariş No</th>
                                        <th>Tarih</th>
                                        <th>Toplam Tutar</th>
                                        <th>Durum</th>
                                        <th>Kargo Takip</th>
                                        <th>Detay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($siparisler as $siparis): ?>
                                        <tr>
                                            <td>#<?php echo $siparis['SiparisID']; ?></td>
                                            <td><?php echo date("d.m.Y H:i", strtotime($siparis['SiparisTarihi'])); ?></td>
                                            <td><?php echo htmlspecialchars(number_format($siparis['ToplamTutar'], 2, ',', '.')) . ' TL'; ?></td>
                                            <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($siparis['SiparisDurumu']); ?></span></td>
                                            <td><?php echo htmlspecialchars($siparis['KargoTakipNo'] ?: '-'); ?></td>
                                            <td><a href="<?php echo htmlspecialchars($base_url . 'siparis_detay.php?id=' . $siparis['SiparisID']); ?>" class="btn btn-sm btn-outline-info">Görüntüle</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>Henüz siparişiniz bulunmamaktadır.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section id="sattiklarim" class="mb-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Satıştaki Kitaplarım</h5>
                    <a href="<?php echo htmlspecialchars($base_url . 'kitap_ekle.php'); ?>" class="btn btn-sm btn-success">Yeni Kitap Ekle</a>
                </div>
                <div class="card-body">
                     <?php if (isset($_SESSION['success_message'])): // Genel kitap ekleme/düzenleme/silme mesajı için ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                     <?php if (isset($_SESSION['error_message'])): // Genel kitap ekleme/düzenleme/silme mesajı için ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                     <?php if (!empty($sattigi_kitaplar)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Kitap Adı</th>
                                        <th>Yazar</th>
                                        <th>Fiyat</th>
                                        <th>Stok</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sattigi_kitaplar as $kitap): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($kitap['KitapAdi']); ?></td>
                                            <td><?php echo htmlspecialchars($kitap['YazarAdi']); ?></td>
                                            <td><?php echo htmlspecialchars(number_format($kitap['Fiyat'], 2, ',', '.')) . ' TL'; ?></td>
                                            <td><?php echo htmlspecialchars($kitap['StokAdedi']); ?></td>
                                            <td><span class="badge <?php echo $kitap['Durum'] == 'Satışta' ? 'bg-success' : ($kitap['Durum'] == 'Satıldı' ? 'bg-danger' : 'bg-warning text-dark'); ?>"><?php echo htmlspecialchars($kitap['Durum']); ?></span></td>
                                            <td>
                                                <a href="<?php echo htmlspecialchars($base_url . 'kitap_duzenle.php?id=' . $kitap['KitapID']); ?>" class="btn btn-sm btn-outline-primary mb-1 me-1" title="Düzenle"><i class="fas fa-edit"></i></a>
                                                <form action="<?php echo htmlspecialchars($base_url . 'kitap_sil.php'); ?>" method="POST" class="d-inline" onsubmit="return confirm('Bu kitabı kalıcı olarak silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!');">
                                                    <input type="hidden" name="kitap_id_sil" value="<?php echo $kitap['KitapID']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger mb-1" title="Sil"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>Henüz satışa çıkardığınız kitap bulunmamaktadır.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
