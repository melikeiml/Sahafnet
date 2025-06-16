<?php
$page_title = "Alışveriş Sepetim";
require_once 'includes/header.php'; // $db, $base_url, $current_user_id burada dahil edilir

$sepet = isset($_SESSION['sepet']) ? $_SESSION['sepet'] : [];
$sepet_toplami = 0;
?>

<div class="row">
    <div class="col-12">
        <h2>Alışveriş Sepetiniz</h2>
        <hr>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>

        <?php if (!empty($sepet)): ?>
            <form action="<?php echo htmlspecialchars($base_url . 'sepet_islemleri.php'); ?>" method="POST" id="sepetGuncelleForm">
                <input type="hidden" name="action" value="guncelle">
                <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($base_url . 'sepet.php'); ?>">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Kitap Adı</th>
                                <th class="text-end">Birim Fiyat</th>
                                <th class="text-center" style="width: 15%;">Adet</th>
                                <th class="text-end">Ara Toplam</th>
                                <th class="text-center" style="width: 10%;">Kaldır</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sepet as $id => $urun): ?>
                                <?php 
                                    $ara_toplam_urun = $urun['fiyat'] * $urun['adet'];
                                    $sepet_toplami += $ara_toplam_urun;
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($base_url . 'kitap_detay.php?id=' . $id); ?>">
                                            <?php echo htmlspecialchars($urun['ad']); ?>
                                        </a>
                                    </td>
                                    <td class="text-end"><?php echo htmlspecialchars(number_format($urun['fiyat'], 2, ',', '.')) . ' TL'; ?></td>
                                    <td class="text-center">
                                        <input type="hidden" name="kitap_id_guncelle_<?php echo $id; ?>" value="<?php echo $id; ?>">
                                        <input type="number" name="adet_<?php echo $id; ?>" class="form-control form-control-sm mx-auto" value="<?php echo $urun['adet']; ?>" min="0" style="max-width: 70px;" 
                                               onchange="document.getElementById('sepetGuncelleForm_<?php echo $id; ?>_adet').value = this.value; document.getElementById('sepetGuncelleForm_<?php echo $id; ?>').submit();">
                                        
                                        <form action="<?php echo htmlspecialchars($base_url . 'sepet_islemleri.php'); ?>" method="POST" id="sepetGuncelleForm_<?php echo $id; ?>" style="display:none;">
                                            <input type="hidden" name="action" value="guncelle">
                                            <input type="hidden" name="kitap_id" value="<?php echo $id; ?>">
                                            <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($base_url . 'sepet.php'); ?>">
                                            <input type="hidden" name="adet" id="sepetGuncelleForm_<?php echo $id; ?>_adet" value="<?php echo $urun['adet']; ?>">
                                        </form>
                                    </td>
                                    <td class="text-end"><?php echo htmlspecialchars(number_format($ara_toplam_urun, 2, ',', '.')) . ' TL'; ?></td>
                                    <td class="text-center">
                                        <form action="<?php echo htmlspecialchars($base_url . 'sepet_islemleri.php'); ?>" method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="kaldir">
                                            <input type="hidden" name="kitap_id" value="<?php echo $id; ?>">
                                            <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($base_url . 'sepet.php'); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">&times;</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end border-0"></th>
                                <th class="text-end">Genel Toplam:</th>
                                <td class="text-end"><strong><?php echo htmlspecialchars(number_format($sepet_toplami, 2, ',', '.')) . ' TL'; ?></strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6 mb-2">
                        <a href="<?php echo htmlspecialchars($base_url . 'kitaplar.php'); ?>" class="btn btn-outline-secondary w-100">Alışverişe Devam Et</a>
                    </div>
                     <div class="col-md-6 mb-2">
                        <form action="<?php echo htmlspecialchars($base_url . 'sepet_islemleri.php'); ?>" method="POST" style="display: inline; width: 100%;">
                            <input type="hidden" name="action" value="bosalt">
                            <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($base_url . 'sepet.php'); ?>">
                            <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Sepetinizi boşaltmak istediğinizden emin misiniz?');">Sepeti Boşalt</button>
                        </form>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12 text-end">
                        <?php if ($current_user_id): // Sadece giriş yapmış kullanıcılar sipariş verebilir ?>
                            <a href="<?php echo htmlspecialchars($base_url . 'siparis_ver.php'); ?>" class="btn btn-lg btn-primary">Siparişi Tamamla</a>
                        <?php else: ?>
                            <p class="text-danger">Siparişi tamamlamak için lütfen <a href="<?php echo htmlspecialchars($base_url . 'giris.php?return_url=' . urlencode($base_url.'sepet.php')); ?>">giriş yapın</a> veya <a href="<?php echo htmlspecialchars($base_url . 'kayit.php'); ?>">kayıt olun</a>.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </form> <?php // #sepetGuncelleForm kapanışı (aslında adet güncelleme için bu forma gerek kalmadı) ?>
        <?php else: ?>
            <div class="alert alert-info">
                Sepetinizde henüz ürün bulunmamaktadır.
            </div>
            <p><a href="<?php echo htmlspecialchars($base_url . 'kitaplar.php'); ?>" class="btn btn-primary">Alışverişe Başla</a></p>
        <?php endif; ?>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>