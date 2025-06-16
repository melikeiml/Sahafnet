<?php
// Sayfa başlığı kategoriye göre değişebilir
$page_title = "Tüm Kitaplar";
require_once 'includes/header.php'; // $db, $base_url, $current_user_id burada dahil edilir

$secili_kategori_id = isset($_GET['kategori_id']) && is_numeric($_GET['kategori_id']) ? intval($_GET['kategori_id']) : 0;
$arama_terimi = isset($_GET['q']) ? trim($_GET['q']) : null;

$sayfa = isset($_GET['sayfa']) && is_numeric($_GET['sayfa']) ? max(1, intval($_GET['sayfa'])) : 1;
$limit = 9; // Sayfa başına gösterilecek kitap sayısı
$offset = ($sayfa - 1) * $limit;

$kitaplar = [];
$toplam_kitap_sayisi = 0;
$kategori_adi_baslik = '';

try {
    // Önce seçili kategori adını alalım (başlık için)
    if ($secili_kategori_id > 0) {
        $stmt_cat_title = $db->prepare("CALL sp_KategoriGetirByID(:p_KategoriID)");
        $stmt_cat_title->bindParam(':p_KategoriID', $secili_kategori_id, PDO::PARAM_INT);
        $stmt_cat_title->execute();
        $kategori_bilgisi = $stmt_cat_title->fetch();
        if ($kategori_bilgisi) {
            $kategori_adi_baslik = htmlspecialchars($kategori_bilgisi['KategoriAdi']);
            $page_title = $kategori_adi_baslik . " Kategorisindeki Kitaplar";
        }
        $stmt_cat_title->closeCursor();
    } elseif ($arama_terimi) {
        $page_title = "'" . htmlspecialchars($arama_terimi) . "' Arama Sonuçları";
    }
    
    // Sayfa başlığını dinamik olarak ayarlamak için JavaScript (header.php'den sonra)
    // Bu JavaScript echo'sunu HTML'in <head> kısmına daha yakın bir yere taşımak daha iyi olurdu,
    // ama header.php zaten yüklendiği için şimdilik burada.
    echo "<script>document.title = '" . addslashes($page_title) . " - SahafNet';</script>";


    // Kitapları ve toplam sayısını çekmek için sp_KitapAra yordamını kullanalım.
    // Bu yordamın sayfalama ve toplam sayı döndürme için güncellenmesi gerekebilir.
    // Şimdilik, önce tüm eşleşenleri çekip sayısını alacağız, sonra limitli sorgu yapacağız.
    // VEYA sp_KitapAra'ya bir OUT parametresi ekleyerek toplam sayıyı alabiliriz.
    // Basitlik için, önce tüm sonuçları çekip sayısını alalım (çok sayıda kitapta performans sorunu olabilir)
    
    // Önce toplam kitap sayısını bulalım (filtreli)
    // Bu kısım optimize edilebilir. İdealde tek bir SP çağrısı ile hem veri hem toplam sayı alınır.
    $count_sql = "SELECT COUNT(*) FROM Kitaplar k 
                  WHERE k.Durum = 'Satışta' AND k.StokAdedi > 0";
    if ($secili_kategori_id > 0) {
        $count_sql .= " AND k.KategoriID = :kategori_id";
    }
    if ($arama_terimi) {
        $count_sql .= " AND (k.KitapAdi LIKE :arama_terimi OR k.YazarAdi LIKE :arama_terimi_yazar)";
    }
    $stmt_count = $db->prepare($count_sql);
    if ($secili_kategori_id > 0) {
        $stmt_count->bindParam(':kategori_id', $secili_kategori_id, PDO::PARAM_INT);
    }
    if ($arama_terimi) {
        $arama_like = "%" . $arama_terimi . "%";
        $stmt_count->bindParam(':arama_terimi', $arama_like, PDO::PARAM_STR);
        $stmt_count->bindParam(':arama_terimi_yazar', $arama_like, PDO::PARAM_STR);
    }
    $stmt_count->execute();
    $toplam_kitap_sayisi = $stmt_count->fetchColumn();
    $stmt_count->closeCursor();

    $toplam_sayfa = ceil($toplam_kitap_sayisi / $limit);


    // Şimdi kitapları sayfalama ile çekelim
    // sp_KitapAra(IN p_AramaTerimi VARCHAR(255), IN p_KategoriID INT, IN p_Limit INT, IN p_Offset INT)
    $stmt_kitaplar = $db->prepare("CALL sp_KitapAra(:p_AramaTerimi, :p_KategoriID, :p_Limit, :p_Offset)");
    $stmt_kitaplar->bindParam(':p_AramaTerimi', $arama_terimi, PDO::PARAM_STR); // Arama terimi null olabilir
    $stmt_kitaplar->bindParam(':p_KategoriID', $secili_kategori_id, PDO::PARAM_INT); // Kategori ID 0 ise tümü
    $stmt_kitaplar->bindParam(':p_Limit', $limit, PDO::PARAM_INT);
    $stmt_kitaplar->bindParam(':p_Offset', $offset, PDO::PARAM_INT);
    
    $stmt_kitaplar->execute();
    $kitaplar = $stmt_kitaplar->fetchAll();
    $stmt_kitaplar->closeCursor();

} catch (PDOException $e) {
    error_log("Kitaplar sayfası - Veri çekme hatası: " . $e->getMessage());
    echo "<div class='alert alert-danger'>Kitaplar yüklenirken bir sorun oluştu.</div>";
}
?>

<div class="row">
    <div class="col-md-3">
        <h4>Kategoriler</h4>
        <div class="list-group shadow-sm mb-4">
            <a href="<?php echo htmlspecialchars($base_url . 'kitaplar.php'); ?>" 
               class="list-group-item list-group-item-action <?php echo ($secili_kategori_id == 0 && !$arama_terimi ? 'active' : ''); ?>">
               Tüm Kitaplar
            </a>
            <?php
            // Kategorileri tekrar çekip listelemek yerine $kategoriler_options (header'dan?) kullanılabilir
            // veya burada tekrar çekilebilir.
            try {
                $stmt_cats_sidebar = $db->prepare("CALL sp_TumKategorileriGetir()");
                $stmt_cats_sidebar->execute();
                $sidebar_kategoriler = $stmt_cats_sidebar->fetchAll();
                $stmt_cats_sidebar->closeCursor();
                foreach ($sidebar_kategoriler as $kategori_sidebar) {
                    echo '<a href="' . htmlspecialchars($base_url . 'kitaplar.php?kategori_id=' . $kategori_sidebar['KategoriID']) . '" 
                             class="list-group-item list-group-item-action ' . ($secili_kategori_id == $kategori_sidebar['KategoriID'] ? 'active' : '') . '">';
                    echo htmlspecialchars($kategori_sidebar['KategoriAdi']);
                    echo '</a>';
                }
            } catch (PDOException $e) {
                // Hata durumunda kategori listesi boş kalır.
            }
            ?>
        </div>
        <h4>Kitap Ara</h4>
        <form action="<?php echo htmlspecialchars($base_url . 'kitaplar.php'); ?>" method="GET" class="mb-4">
            <div class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Kitap adı veya yazar..." value="<?php echo htmlspecialchars($arama_terimi ?? ''); ?>">
                <?php if ($secili_kategori_id > 0): // Kategori seçiliyse onu da gönderelim ?>
                    <input type="hidden" name="kategori_id" value="<?php echo $secili_kategori_id; ?>">
                <?php endif; ?>
                <button class="btn btn-outline-primary" type="submit">Ara</button>
            </div>
        </form>
    </div>

    <div class="col-md-9">
        <?php if ($kategori_adi_baslik): ?>
            <h2><?php echo $kategori_adi_baslik; ?></h2>
            <hr>
        <?php elseif ($arama_terimi): ?>
            <h2>Arama Sonuçları: "<?php echo htmlspecialchars($arama_terimi); ?>"</h2>
            <p class="text-muted"><?php echo $toplam_kitap_sayisi; ?> kitap bulundu.</p>
            <hr>
        <?php else: ?>
            <h2>Tüm Kitaplar</h2>
            <hr>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>

        <?php if (count($kitaplar) > 0): ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
                <?php foreach ($kitaplar as $kitap): ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <?php
                                $kapak_gorseli_liste = $base_url . 'images/placeholder_kapak.png';
                                if (!empty($kitap['KapakFotografiURL'])) {
                                    if (!filter_var($kitap['KapakFotografiURL'], FILTER_VALIDATE_URL) && strpos($kitap['KapakFotografiURL'], 'uploads/') === 0) {
                                        $kapak_gorseli_liste = $base_url . htmlspecialchars($kitap['KapakFotografiURL']);
                                    } elseif (filter_var($kitap['KapakFotografiURL'], FILTER_VALIDATE_URL)) {
                                        $kapak_gorseli_liste = htmlspecialchars($kitap['KapakFotografiURL']);
                                    }
                                }
                            ?>
                            <a href="<?php echo htmlspecialchars($base_url . 'kitap_detay.php?id=' . $kitap['KitapID']); ?>">
                                <img src="<?php echo $kapak_gorseli_liste; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($kitap['KitapAdi']); ?>" style="height: 250px; object-fit: contain; padding: 10px;">
                            </a>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">
                                    <a href="<?php echo htmlspecialchars($base_url . 'kitap_detay.php?id=' . $kitap['KitapID']); ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($kitap['KitapAdi']); ?>
                                    </a>
                                </h5>
                                <p class="card-text text-muted mb-2"><small><?php echo htmlspecialchars($kitap['YazarAdi']); ?></small></p>
                                <p class="card-text"><strong><?php echo htmlspecialchars(number_format($kitap['Fiyat'], 2, ',', '.')) . ' TL'; ?></strong></p>
                                <a href="<?php echo htmlspecialchars($base_url . 'kitap_detay.php?id=' . $kitap['KitapID']); ?>" class="btn btn-primary btn-sm mt-auto">Detayları Gör</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($toplam_sayfa > 1): ?>
            <nav aria-label="Sayfalar" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($sayfa > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?sayfa=<?php echo $sayfa - 1; ?><?php echo $secili_kategori_id ? '&kategori_id='.$secili_kategori_id : ''; ?><?php echo $arama_terimi ? '&q='.urlencode($arama_terimi) : ''; ?>">Önceki</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                        <li class="page-item <?php echo ($i == $sayfa) ? 'active' : ''; ?>">
                            <a class="page-link" href="?sayfa=<?php echo $i; ?><?php echo $secili_kategori_id ? '&kategori_id='.$secili_kategori_id : ''; ?><?php echo $arama_terimi ? '&q='.urlencode($arama_terimi) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($sayfa < $toplam_sayfa): ?>
                        <li class="page-item">
                            <a class="page-link" href="?sayfa=<?php echo $sayfa + 1; ?><?php echo $secili_kategori_id ? '&kategori_id='.$secili_kategori_id : ''; ?><?php echo $arama_terimi ? '&q='.urlencode($arama_terimi) : ''; ?>">Sonraki</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>

        <?php else: ?>
            <p class="mt-4">
                <?php 
                if ($arama_terimi) {
                    echo "Aradığınız kriterlere uygun kitap bulunamadı.";
                } elseif ($secili_kategori_id > 0 && $kategori_adi_baslik) {
                    echo htmlspecialchars($kategori_adi_baslik) . " kategorisinde henüz kitap bulunmamaktadır.";
                } else {
                    echo "Sistemde henüz satışta olan kitap bulunmamaktadır.";
                }
                ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>