<?php
$page_title = "Ana Sayfa";
require_once 'includes/header.php'; // $db ve $base_url burada dahil edilir
?>

<div class="row mb-5"> <div class="col-md-12">
        <h1>SahafNet'e Hoş Geldiniz!</h1>
        <p class="lead">İkinci el kitaplarınızı alıp satabileceğiniz en iyi platform.</p>
        <hr>
    </div>
</div>

<div class="row mb-5"> <div class="col-md-8">
        <h2>Son Eklenen Kitaplar</h2>
        <?php
        try {
            $stmt_kitaplar = $db->prepare("CALL sp_SatistakiKitaplariGetir(6, 0)");
            $stmt_kitaplar->execute();
            $kitaplar = $stmt_kitaplar->fetchAll();
            $stmt_kitaplar->closeCursor(); // Sonuç setiyle işimiz bitti.

            if (count($kitaplar) > 0) {
                echo '<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">';
                foreach ($kitaplar as $kitap) {
                    echo '<div class="col">';
                    echo '  <div class="card h-100 shadow-sm">';
                    // Kapak fotoğrafı
                    $kapak_foto_url = $base_url . 'images/placeholder_kapak.png'; // Varsayılan
                    if (!empty($kitap['KapakFotografiURL'])) {
                        // Eğer tam bir URL değilse ve uploads/ ile başlıyorsa base_url ekle
                        if (!filter_var($kitap['KapakFotografiURL'], FILTER_VALIDATE_URL) && strpos($kitap['KapakFotografiURL'], 'uploads/') === 0) {
                            $kapak_foto_url = $base_url . htmlspecialchars($kitap['KapakFotografiURL']);
                        } elseif (filter_var($kitap['KapakFotografiURL'], FILTER_VALIDATE_URL)) {
                            $kapak_foto_url = htmlspecialchars($kitap['KapakFotografiURL']);
                        }
                    }
                    echo '    <img src="' . $kapak_foto_url . '" class="card-img-top" alt="' . htmlspecialchars($kitap['KitapAdi']) . '" style="height: 300px; object-fit: contain; padding: 10px;">';
                    echo '    <div class="card-body d-flex flex-column">';
                    echo '      <h5 class="card-title">' . htmlspecialchars($kitap['KitapAdi']) . '</h5>';
                    echo '      <p class="card-text text-muted mb-2"><small>' . htmlspecialchars($kitap['YazarAdi']) . '</small></p>';
                    echo '      <p class="card-text"><strong>' . htmlspecialchars(number_format($kitap['Fiyat'], 2, ',', '.')) . ' TL</strong></p>';
                    echo '      <a href="' . htmlspecialchars($base_url) . 'kitap_detay.php?id=' . $kitap['KitapID'] . '" class="btn btn-primary btn-sm mt-auto">Detayları Gör</a>';
                    echo '    </div>';
                    echo '  </div>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<p>Henüz satışta olan kitap bulunmamaktadır.</p>';
            }
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Kitaplar yüklenirken bir hata oluştu: ' . $e->getMessage() . '</div>';
            // Geliştirme aşamasında daha detaylı loglama yapılabilir:
            // error_log("Kitap listeleme hatası (index.php): " . $e->getMessage());
        }
        ?>
    </div>
    <div class="col-md-4">
        <h4>Kategoriler</h4>
        <?php
        try {
            $stmt_kategoriler = $db->prepare("CALL sp_TumKategorileriGetir()");
            $stmt_kategoriler->execute();
            $kategoriler = $stmt_kategoriler->fetchAll();
            $stmt_kategoriler->closeCursor(); // Sonuç setiyle işimiz bitti.

            if (count($kategoriler) > 0) {
                echo '<ul class="list-group shadow-sm">';
                foreach ($kategoriler as $kategori) {
                    echo '<a href="' . htmlspecialchars($base_url) . 'kitaplar.php?kategori_id=' . $kategori['KategoriID'] . '" class="list-group-item list-group-item-action">';
                    echo htmlspecialchars($kategori['KategoriAdi']);
                    // İsteğe bağlı: Kategoriye ait kitap sayısı
                    // echo ' <span class="badge bg-secondary float-end">N</span>';
                    echo '</a>';
                }
                echo '</ul>';
            } else {
                echo '<p>Kategori bulunamadı.</p>';
            }
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Kategoriler yüklenirken hata: ' . $e->getMessage() . '</div>';
            // error_log("Kategori listeleme hatası (index.php): " . $e->getMessage());
        }
        ?>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>