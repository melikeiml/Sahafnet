<?php
// Her sayfada veritabanı bağlantısını ve oturumları dahil et (database.php bunu zaten yapıyor)
require_once 'database.php'; // $db PDO nesnesi burada oluşturulur.

// Temel URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host_server = $_SERVER['HTTP_HOST'];
$base_url = "/sahafnet/"; // Projenizin kök dizinine göre ayarlayın

// Oturumda kullanıcı varsa bilgilerini alalım
$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$current_user_name = '';

if ($current_user_id) {
    try {
        $stmt_user_info = $db->prepare("SELECT Ad, Soyad FROM Kullanicilar WHERE KullaniciID = :kullanici_id");
        $stmt_user_info->bindParam(':kullanici_id', $current_user_id);
        $stmt_user_info->execute();
        $user = $stmt_user_info->fetch();
        if ($user) {
            $current_user_name = htmlspecialchars($user['Ad'] . ' ' . $user['Soyad']);
        }
        $stmt_user_info->closeCursor();
    } catch (PDOException $e) {
        error_log("Kullanıcı adı alınırken hata (header.php): " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - SahafNet' : 'SahafNet - İkinci El Kitap'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_url); ?>css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container"> <!-- Navigasyon kendi container'ını kullanabilir, bu sayfa içeriği container'ından bağımsızdır -->
    <a class="navbar-brand" href="<?php echo htmlspecialchars($base_url); ?>index.php">SahafNet</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''); ?>" href="<?php echo htmlspecialchars($base_url); ?>index.php">Ana Sayfa</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'kitaplar.php' ? 'active' : ''); ?>" href="<?php echo htmlspecialchars($base_url); ?>kitaplar.php">Tüm Kitaplar</a>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
            <?php
                $sepet_urun_sayisi = 0;
                if (isset($_SESSION['sepet']) && is_array($_SESSION['sepet'])) {
                    // Sepetteki toplam adedi göster
                    foreach ($_SESSION['sepet'] as $item) {
                        $sepet_urun_sayisi += $item['adet'];
                    }
                }
            ?>
            <a class="nav-link" href="<?php echo htmlspecialchars($base_url); ?>sepet.php">
                Sepetim 
                <?php if ($sepet_urun_sayisi > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?php echo $sepet_urun_sayisi; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <?php if ($current_user_id): ?>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Hoşgeldin, <?php echo $current_user_name; ?>
                </a>
                <ul class="dropdown-menu" aria-labelledby="navbarDropdownUser">
                    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($base_url); ?>profil.php">Profilim</a></li>
                    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($base_url); ?>kitap_ekle.php">Kitap Ekle</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?php echo htmlspecialchars($base_url); ?>logout.php">Çıkış Yap</a></li>
                </ul>
            </li>
        <?php else: ?>
            <li class="nav-item">
              <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'giris.php' ? 'active' : ''); ?>" href="<?php echo htmlspecialchars($base_url); ?>giris.php">Giriş Yap</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'kayit.php' ? 'active' : ''); ?>" href="<?php echo htmlspecialchars($base_url); ?>kayit.php">Kayıt Ol</a>
            </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
<!-- Sayfa içeriği buradan sonra gelecek -->