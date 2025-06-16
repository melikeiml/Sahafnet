-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 14 Haz 2025, 13:45:55
-- Sunucu sürümü: 8.0.41
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `sahafnetdb`
--

DELIMITER $$
--
-- Yordamlar
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AdresEkle` (IN `p_KullaniciID` INT, IN `p_AdresBasligi` VARCHAR(100), IN `p_AcikAdres` TEXT, IN `p_Il` VARCHAR(50), IN `p_Ilce` VARCHAR(50), IN `p_Mahalle` VARCHAR(100), IN `p_PostaKodu` VARCHAR(10))   BEGIN
    INSERT INTO Adresler (KullaniciID, AdresBasligi, AcikAdres, Il, Ilce, Mahalle, PostaKodu)
    VALUES (p_KullaniciID, p_AdresBasligi, p_AcikAdres, p_Il, p_Ilce, 
            NULLIF(TRIM(p_Mahalle), ''), NULLIF(TRIM(p_PostaKodu), ''));
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AdresGetirByID` (IN `p_AdresID` INT, IN `p_KullaniciID` INT)   BEGIN
        SELECT AdresID, KullaniciID, AdresBasligi, AcikAdres, Il, Ilce, Mahalle, PostaKodu
        FROM Adresler
        WHERE AdresID = p_AdresID AND KullaniciID = p_KullaniciID;
    END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AdresGuncelle` (IN `p_AdresID` INT, IN `p_KullaniciID` INT, IN `p_AdresBasligi` VARCHAR(100), IN `p_AcikAdres` TEXT, IN `p_Il` VARCHAR(50), IN `p_Ilce` VARCHAR(50), IN `p_Mahalle` VARCHAR(100), IN `p_PostaKodu` VARCHAR(10))   BEGIN
    UPDATE Adresler
    SET AdresBasligi = p_AdresBasligi,
        AcikAdres = p_AcikAdres,
        Il = p_Il,
        Ilce = p_Ilce,
        Mahalle = NULLIF(TRIM(p_Mahalle), ''),
        PostaKodu = NULLIF(TRIM(p_PostaKodu), '')
    WHERE AdresID = p_AdresID AND KullaniciID = p_KullaniciID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_AdresSil` (IN `p_AdresID` INT, IN `p_KullaniciID` INT)   BEGIN
        -- ÖNEMLİ: Bu adres bir siparişte TeslimatAdresID olarak kullanılıyorsa
        -- ve Siparisler.TeslimatAdresID -> Adresler.AdresID foreign key'i
        -- ON DELETE RESTRICT ise bu silme işlemi hata verecektir.
        DELETE FROM Adresler
        WHERE AdresID = p_AdresID AND KullaniciID = p_KullaniciID;
    END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KategoriEkle` (IN `p_KategoriAdi` VARCHAR(100), IN `p_Aciklama` TEXT)   BEGIN
    INSERT INTO Kategoriler (KategoriAdi, Aciklama)
    VALUES (p_KategoriAdi, p_Aciklama);
    SELECT LAST_INSERT_ID() AS YeniKategoriID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KategoriGetirByID` (IN `p_KategoriID` INT)   BEGIN
    SELECT KategoriID, KategoriAdi, Aciklama
    FROM Kategoriler
    WHERE KategoriID = p_KategoriID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KategoriGuncelle` (IN `p_KategoriID` INT, IN `p_KategoriAdi` VARCHAR(100), IN `p_Aciklama` TEXT)   BEGIN
    UPDATE Kategoriler
    SET KategoriAdi = p_KategoriAdi,
        Aciklama = p_Aciklama
    WHERE KategoriID = p_KategoriID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KategoriSil` (IN `p_KategoriID` INT)   BEGIN
    -- Önce bu kategoride kitap var mı kontrol edilebilir.
    -- Ya da direkt silmeye çalışılır, hata verirse kullanıcı bilgilendirilir.
    DELETE FROM Kategoriler WHERE KategoriID = p_KategoriID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KitapAra` (IN `p_AramaTerimi` VARCHAR(255), IN `p_KategoriID` INT, IN `p_Limit` INT, IN `p_Offset` INT)   BEGIN
    SELECT k.KitapID, k.KitapAdi, k.YazarAdi, k.Fiyat, k.Kondisyon, k.KapakFotografiURL, kat.KategoriAdi
    FROM Kitaplar k
    JOIN Kategoriler kat ON k.KategoriID = kat.KategoriID
    WHERE k.Durum = 'Satışta' AND k.StokAdedi > 0
      AND (p_AramaTerimi IS NULL OR k.KitapAdi LIKE CONCAT('%', p_AramaTerimi, '%') OR k.YazarAdi LIKE CONCAT('%', p_AramaTerimi, '%'))
      AND (p_KategoriID IS NULL OR p_KategoriID = 0 OR k.KategoriID = p_KategoriID)
    ORDER BY k.ListelemeTarihi DESC
    LIMIT p_Limit OFFSET p_Offset;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KitapEkle` (IN `p_SatıcıKullaniciID` INT, IN `p_KategoriID` INT, IN `p_KitapAdi` VARCHAR(255), IN `p_YazarAdi` VARCHAR(255), IN `p_ISBN` VARCHAR(20), IN `p_YayinEvi` VARCHAR(100), IN `p_BaskiYili` INT, IN `p_SayfaSayisi` INT, IN `p_Kondisyon` VARCHAR(50), IN `p_Aciklama` TEXT, IN `p_Fiyat` DECIMAL(10,2), IN `p_StokAdedi` INT, IN `p_KapakFotografiURL` VARCHAR(500))   BEGIN
    INSERT INTO Kitaplar (SatıcıKullaniciID, KategoriID, KitapAdi, YazarAdi, ISBN, YayinEvi, BaskiYili, SayfaSayisi, Kondisyon, Aciklama, Fiyat, StokAdedi, KapakFotografiURL, Durum)
    VALUES (p_SatıcıKullaniciID, p_KategoriID, p_KitapAdi, p_YazarAdi, p_ISBN, p_YayinEvi, p_BaskiYili, p_SayfaSayisi, p_Kondisyon, p_Aciklama, p_Fiyat, p_StokAdedi, p_KapakFotografiURL, 'Satışta');
    SELECT LAST_INSERT_ID() AS YeniKitapID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KitapGetirByID` (IN `p_KitapID` INT)   BEGIN
    SELECT k.*, kat.KategoriAdi, CONCAT(u.Ad, ' ', u.Soyad) AS SaticiAdi
    FROM Kitaplar k
    JOIN Kategoriler kat ON k.KategoriID = kat.KategoriID
    JOIN Kullanicilar u ON k.SatıcıKullaniciID = u.KullaniciID
    WHERE k.KitapID = p_KitapID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KitapGuncelle` (IN `p_KitapID` INT, IN `p_KategoriID` INT, IN `p_KitapAdi` VARCHAR(255), IN `p_YazarAdi` VARCHAR(255), IN `p_ISBN` VARCHAR(20), IN `p_YayinEvi` VARCHAR(100), IN `p_BaskiYili` INT, IN `p_SayfaSayisi` INT, IN `p_Kondisyon` VARCHAR(50), IN `p_Aciklama` TEXT, IN `p_Fiyat` DECIMAL(10,2), IN `p_StokAdedi` INT, IN `p_Durum` VARCHAR(50), IN `p_KapakFotografiURL` VARCHAR(500))   BEGIN
    UPDATE Kitaplar
    SET KategoriID = p_KategoriID,
        KitapAdi = p_KitapAdi,
        YazarAdi = p_YazarAdi,
        ISBN = p_ISBN,
        YayinEvi = p_YayinEvi,
        BaskiYili = p_BaskiYili,
        SayfaSayisi = p_SayfaSayisi,
        Kondisyon = p_Kondisyon,
        Aciklama = p_Aciklama,
        Fiyat = p_Fiyat,
        StokAdedi = p_StokAdedi,
        Durum = p_Durum,
        KapakFotografiURL = p_KapakFotografiURL,
        GuncellemeTarihi = CURRENT_TIMESTAMP
    WHERE KitapID = p_KitapID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KitapSil` (IN `p_KitapID` INT)   BEGIN
    -- Gerçek silme yerine durumu değiştirmek daha iyi bir pratik olabilir.
    -- UPDATE Kitaplar SET Durum = 'Listeden Kaldırıldı' WHERE KitapID = p_KitapID;
    -- Örnek için DELETE:
    DELETE FROM Kitaplar WHERE KitapID = p_KitapID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KitapStokGuncelle` (IN `p_KitapID` INT, IN `p_YeniStokAdedi` INT)   BEGIN
    UPDATE Kitaplar
    SET StokAdedi = p_YeniStokAdedi,
        Durum = IF(p_YeniStokAdedi > 0, 'Satışta', 'Satıldı') -- Stok 0 olursa durumu 'Satıldı' yap
    WHERE KitapID = p_KitapID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KitapYorumlariniGetir` (IN `p_KitapID` INT)   BEGIN
    SELECT y.YorumID, y.Puan, y.YorumMetni, y.YorumTarihi, CONCAT(u.Ad, ' ', u.Soyad) AS YorumYapan
    FROM Yorumlar y
    JOIN Kullanicilar u ON y.KullaniciID = u.KullaniciID
    WHERE y.KitapID = p_KitapID AND y.OnayDurumu = 'Onaylandı'
    ORDER BY y.YorumTarihi DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KullaniciAdresleriniGetir` (IN `p_KullaniciID` INT)   BEGIN
    SELECT AdresID, AdresBasligi, Il, Ilce, Mahalle, AcikAdres, PostaKodu
    FROM Adresler
    WHERE KullaniciID = p_KullaniciID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KullaniciEkle` (IN `p_Ad` VARCHAR(100), IN `p_Soyad` VARCHAR(100), IN `p_Eposta` VARCHAR(255), IN `p_SifreHash` VARCHAR(255), IN `p_TelefonNo` VARCHAR(20))   BEGIN
    INSERT INTO Kullanicilar (Ad, Soyad, Eposta, SifreHash, TelefonNo, AktifMi)
    VALUES (p_Ad, p_Soyad, p_Eposta, p_SifreHash, p_TelefonNo, TRUE);
    SELECT LAST_INSERT_ID() AS YeniKullaniciID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KullaniciGetirByEposta` (IN `p_Eposta` VARCHAR(255))   BEGIN
    SELECT KullaniciID, Ad, Soyad, Eposta, SifreHash, TelefonNo, KayitTarihi, SonGirisTarihi, AktifMi
    FROM Kullanicilar
    WHERE Eposta = p_Eposta;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KullaniciGetirByID` (IN `p_KullaniciID` INT)   BEGIN
    SELECT KullaniciID, Ad, Soyad, Eposta, TelefonNo, KayitTarihi, SonGirisTarihi, AktifMi
    FROM Kullanicilar
    WHERE KullaniciID = p_KullaniciID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KullaniciGuncelle` (IN `p_KullaniciID` INT, IN `p_Ad` VARCHAR(100), IN `p_Soyad` VARCHAR(100), IN `p_Eposta` VARCHAR(255), IN `p_TelefonNo` VARCHAR(20), IN `p_AktifMi` BOOLEAN)   BEGIN
    UPDATE Kullanicilar
    SET Ad = p_Ad,
        Soyad = p_Soyad,
        Eposta = p_Eposta,
        TelefonNo = p_TelefonNo,
        AktifMi = p_AktifMi
    WHERE KullaniciID = p_KullaniciID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KullanicininSatistakiKitaplariniGetir` (IN `p_SatıcıKullaniciID` INT)   BEGIN
    SELECT k.KitapID, k.KitapAdi, k.YazarAdi, k.Fiyat, k.Kondisyon, k.StokAdedi, k.Durum, kat.KategoriAdi
    FROM Kitaplar k
    JOIN Kategoriler kat ON k.KategoriID = kat.KategoriID
    WHERE k.SatıcıKullaniciID = p_SatıcıKullaniciID
    ORDER BY k.ListelemeTarihi DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KullaniciSifreGuncelle` (IN `p_KullaniciID` INT, IN `p_YeniSifreHash` VARCHAR(255))   BEGIN
    UPDATE Kullanicilar
    SET SifreHash = p_YeniSifreHash
    WHERE KullaniciID = p_KullaniciID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KullaniciSil` (IN `p_KullaniciID` INT)   BEGIN
    -- Genellikle kullanıcıları gerçekten silmek yerine AktifMi = FALSE yapmak daha iyidir.
    -- Ancak örnek olması açısından DELETE yapalım. Cascade kuralları çalışacaktır.
    DELETE FROM Kullanicilar WHERE KullaniciID = p_KullaniciID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KullaniciSiparisleriniGetir` (IN `p_AliciKullaniciID` INT)   BEGIN
    SELECT s.SiparisID, s.SiparisTarihi, s.ToplamTutar, s.SiparisDurumu, s.KargoTakipNo,
           a.AcikAdres AS TeslimatAdresi
    FROM Siparisler s
    JOIN Adresler a ON s.TeslimatAdresID = a.AdresID
    WHERE s.AliciKullaniciID = p_AliciKullaniciID
    ORDER BY s.SiparisTarihi DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_KullaniciSonGirisGuncelle` (IN `p_KullaniciID` INT)   BEGIN
    UPDATE Kullanicilar
    SET SonGirisTarihi = CURRENT_TIMESTAMP
    WHERE KullaniciID = p_KullaniciID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_OnayBekleyenYorumlariGetir` ()   BEGIN
    SELECT y.YorumID, k.KitapAdi, CONCAT(u.Ad, ' ', u.Soyad) AS YorumYapan, y.Puan, y.YorumMetni, y.YorumTarihi
    FROM Yorumlar y
    JOIN Kitaplar k ON y.KitapID = k.KitapID
    JOIN Kullanicilar u ON y.KullaniciID = u.KullaniciID
    WHERE y.OnayDurumu = 'Beklemede'
    ORDER BY y.YorumTarihi ASC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_SatistakiKitaplariGetir` (IN `p_Limit` INT, IN `p_Offset` INT)   BEGIN
    SELECT k.KitapID, k.KitapAdi, k.YazarAdi, k.Fiyat, k.Kondisyon, k.KapakFotografiURL, kat.KategoriAdi
    FROM Kitaplar k
    JOIN Kategoriler kat ON k.KategoriID = kat.KategoriID
    WHERE k.Durum = 'Satışta' AND k.StokAdedi > 0
    ORDER BY k.ListelemeTarihi DESC
    LIMIT p_Limit OFFSET p_Offset;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_SiparisDetayEkle` (IN `p_SiparisID` INT, IN `p_KitapID` INT, IN `p_Adet` INT, IN `p_BirimFiyat` DECIMAL(10,2))   BEGIN
    INSERT INTO SiparisDetaylari (SiparisID, KitapID, Adet, BirimFiyat)
    VALUES (p_SiparisID, p_KitapID, p_Adet, p_BirimFiyat);
    -- İlgili kitabın stoğunu düşürmek için bir trigger veya burada ayrı bir SP çağrısı yapılabilir.
    -- CALL sp_KitapStokAzalt(p_KitapID, p_Adet);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_SiparisDetaylariniGetir` (IN `p_SiparisID` INT)   BEGIN
    SELECT sd.KitapID, k.KitapAdi, k.YazarAdi, sd.Adet, sd.BirimFiyat, (sd.Adet * sd.BirimFiyat) AS AraToplam
    FROM SiparisDetaylari sd
    JOIN Kitaplar k ON sd.KitapID = k.KitapID
    WHERE sd.SiparisID = p_SiparisID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_SiparisDurumGuncelle` (IN `p_SiparisID` INT, IN `p_YeniSiparisDurumu` VARCHAR(50), IN `p_KargoTakipNo` VARCHAR(100))   BEGIN
    UPDATE Siparisler
    SET SiparisDurumu = p_YeniSiparisDurumu,
        KargoTakipNo = IF(p_KargoTakipNo IS NOT NULL AND p_KargoTakipNo != '', p_KargoTakipNo, KargoTakipNo)
    WHERE SiparisID = p_SiparisID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_SiparisGetirByID` (IN `p_SiparisID` INT, IN `p_AliciKullaniciID` INT)   BEGIN
    SELECT 
        s.SiparisID, 
        s.AliciKullaniciID,
        s.SiparisTarihi, 
        s.ToplamTutar, 
        s.SiparisDurumu, 
        s.OdemeYontemi, 
        s.KargoTakipNo,
        a.AdresBasligi,
        a.AcikAdres,
        a.Il,
        a.Ilce,
        a.Mahalle,
        a.PostaKodu,
        CONCAT(u.Ad, ' ', u.Soyad) AS AliciAdSoyad,
        u.Eposta AS AliciEposta,
        u.TelefonNo AS AliciTelefon
    FROM Siparisler s
    JOIN Adresler a ON s.TeslimatAdresID = a.AdresID
    JOIN Kullanicilar u ON s.AliciKullaniciID = u.KullaniciID
    WHERE s.SiparisID = p_SiparisID AND s.AliciKullaniciID = p_AliciKullaniciID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_SiparisIptalEt` (IN `p_SiparisID` INT)   BEGIN
    -- İptal edilen siparişteki kitapların stokları iade edilmeli.
    -- Bu bir trigger ile otomatikleştirilebilir veya burada manuel yapılabilir.
    UPDATE Siparisler
    SET SiparisDurumu = 'İptal Edildi'
    WHERE SiparisID = p_SiparisID;
    -- İlgili trigger yoksa, stok iadesi için:
    -- UPDATE Kitaplar k JOIN SiparisDetaylari sd ON k.KitapID = sd.KitapID
    -- SET k.StokAdedi = k.StokAdedi + sd.Adet
    -- WHERE sd.SiparisID = p_SiparisID AND k.Durum = 'Satıldı';
    -- UPDATE Kitaplar k JOIN SiparisDetaylari sd ON k.KitapID = sd.KitapID
    -- SET k.Durum = 'Satışta'
    -- WHERE sd.SiparisID = p_SiparisID AND k.StokAdedi > 0 AND k.Durum = 'Satıldı';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_SiparisOlustur` (IN `p_AliciKullaniciID` INT, IN `p_TeslimatAdresID` INT, IN `p_ToplamTutar` DECIMAL(10,2), IN `p_OdemeYontemi` VARCHAR(50))   BEGIN
    INSERT INTO Siparisler (AliciKullaniciID, TeslimatAdresID, ToplamTutar, SiparisDurumu, OdemeYontemi)
    VALUES (p_AliciKullaniciID, p_TeslimatAdresID, p_ToplamTutar, 'Ödeme Bekleniyor', p_OdemeYontemi);
    SELECT LAST_INSERT_ID() AS YeniSiparisID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_SiparisToplamTutarGuncelle` (IN `p_SiparisID` INT)   BEGIN
    DECLARE v_ToplamTutar DECIMAL(10,2);

    SELECT SUM(Adet * BirimFiyat) INTO v_ToplamTutar
    FROM SiparisDetaylari
    WHERE SiparisID = p_SiparisID;

    UPDATE Siparisler
    SET ToplamTutar = IFNULL(v_ToplamTutar, 0)
    WHERE SiparisID = p_SiparisID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_TumAktifKullanicilariGetir` ()   BEGIN
    SELECT KullaniciID, Ad, Soyad, Eposta, TelefonNo, KayitTarihi, SonGirisTarihi
    FROM Kullanicilar
    WHERE AktifMi = TRUE;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_TumKategorileriGetir` ()   BEGIN
    SELECT KategoriID, KategoriAdi, Aciklama FROM Kategoriler ORDER BY KategoriAdi;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_YorumEkle` (IN `p_KitapID` INT, IN `p_KullaniciID` INT, IN `p_Puan` INT, IN `p_YorumMetni` TEXT)   BEGIN
    INSERT INTO Yorumlar (KitapID, KullaniciID, Puan, YorumMetni, OnayDurumu)
    VALUES (p_KitapID, p_KullaniciID, p_Puan, p_YorumMetni, 'Beklemede');
    SELECT LAST_INSERT_ID() AS YeniYorumID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_YorumGuncelle` (IN `p_YorumID` INT, IN `p_Puan` INT, IN `p_YorumMetni` TEXT, IN `p_OnayDurumu` VARCHAR(20))   BEGIN
    UPDATE Yorumlar
    SET Puan = p_Puan,
        YorumMetni = p_YorumMetni,
        OnayDurumu = p_OnayDurumu -- Bu parametre sadece admin tarafından kullanılmalı
    WHERE YorumID = p_YorumID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_YorumOnayDurumuGuncelle` (IN `p_YorumID` INT, IN `p_YeniOnayDurumu` VARCHAR(20))   BEGIN
    UPDATE Yorumlar
    SET OnayDurumu = p_YeniOnayDurumu
    WHERE YorumID = p_YorumID;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_YorumSil` (IN `p_YorumID` INT)   BEGIN
    DELETE FROM Yorumlar WHERE YorumID = p_YorumID;
END$$

--
-- İşlevler
--
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_KitapOrtalamaPuani` (`p_KitapID` INT) RETURNS DECIMAL(3,2) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE v_OrtalamaPuan DECIMAL(3,2);

    SELECT AVG(Puan) INTO v_OrtalamaPuan
    FROM Yorumlar
    WHERE KitapID = p_KitapID AND OnayDurumu = 'Onaylandı';

    RETURN IFNULL(v_OrtalamaPuan, 0.00);
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `fn_KullaniciAktifSiparisSayisi` (`p_KullaniciID` INT) RETURNS INT DETERMINISTIC READS SQL DATA BEGIN
    DECLARE v_SiparisSayisi INT;

    SELECT COUNT(*) INTO v_SiparisSayisi
    FROM Siparisler
    WHERE AliciKullaniciID = p_KullaniciID
      AND SiparisDurumu NOT IN ('İptal Edildi', 'Teslim Edildi'); -- Veya sadece 'İptal Edildi' dışındakiler

    RETURN IFNULL(v_SiparisSayisi, 0);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `adresler`
--

CREATE TABLE `adresler` (
  `AdresID` int NOT NULL,
  `KullaniciID` int NOT NULL,
  `AdresBasligi` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Il` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Ilce` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Mahalle` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `AcikAdres` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `PostaKodu` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `adresler`
--

INSERT INTO `adresler` (`AdresID`, `KullaniciID`, `AdresBasligi`, `Il`, `Ilce`, `Mahalle`, `AcikAdres`, `PostaKodu`) VALUES
(1, 1, 'Ev Adresim', 'İstanbul', 'Kadıköy', 'Caferağa Mah.', 'Moda Cad. No: 123 Daire: 4', '34710'),
(2, 2, 'Ev Adresi', 'Ankara', 'Çankaya', 'Kavaklıdere Mah.', 'Tunalı Hilmi Cad. No: 89/5', '06680'),
(3, 3, 'Yazlık Adres', 'İzmir', 'Alaçatı', 'Alaçatı Mah.', '1001 Sokak No: 12', '35937'),
(4, 4, 'Ev Adresi', 'İstanbul', 'Beşiktaş', 'Levent Mah.', 'Taşlı Bayır Sk. No: 15 Daire: 3', '34330'),
(5, 5, 'Ev', 'Ankara', 'Keçiören', 'Şevkat Mah.', 'Aydınlık Cad. No: 42/7', '06120'),
(6, 6, 'İş Adresi', 'İzmir', 'Konak', 'Alsancak Mah.', 'Kıbrıs Şehitleri Cad. No: 112', '35220'),
(7, 7, 'Ev', 'Bursa', 'Nilüfer', 'Görükle Mah.', 'Uludağ Cad. No: 78/5', '16285'),
(8, 8, 'İş', 'Antalya', 'Muratpaşa', 'Meltem Mah.', 'Yavuz Selim Cad. No: 23/B', '07030'),
(9, 9, 'Ev Adresim', 'Adana', 'Seyhan', 'Çınarlı Mah.', 'Atatürk Cad. Turunç Apt. No: 56/3', '01060'),
(10, 10, 'Yaz Evi', 'Muğla', 'Bodrum', 'Gümbet Mah.', 'Deniz Cad. No: 34', '48960'),
(11, 11, 'Merkez', 'Eskişehir', 'Tepebaşı', 'Hoşnudiye Mah.', 'İsmet İnönü Cad. No: 89/12', '26130'),
(12, 12, 'Ev', 'Trabzon', 'Ortahisar', 'Erdoğdu Mah.', 'Kahramanmaraş Cad. No: 45/3', '61030'),
(13, 13, 'İşyeri', 'Konya', 'Selçuklu', 'Yazır Mah.', 'Beyşehir Cad. No: 112/A', '42250'),
(14, 14, 'Ev', 'Gaziantep', 'Şahinbey', 'Karataş Mah.', 'Atatürk Bulvarı No: 67/8', '27060'),
(15, 15, 'İş', 'Kayseri', 'Melikgazi', 'Cumhuriyet Mah.', 'İnönü Bulvarı No: 25/C', '38040'),
(16, 16, 'Öğrenci Evi', 'İstanbul', 'Üsküdar', 'Acıbadem Mah.', 'Tekin Sk. No: 18/6', '34660'),
(17, 17, 'Ev', 'Diyarbakır', 'Kayapınar', 'Peyas Mah.', 'Urfa Bulvarı No: 125/4', '21070'),
(18, 18, 'Merkez', 'Samsun', 'İlkadım', 'Kılıçdede Mah.', 'Cumhuriyet Cad. No: 45/B', '55060'),
(19, 19, 'Annem', 'Mersin', 'Yenişehir', 'Barbaros Mah.', 'Gazi Mustafa Kemal Bulvarı No: 102/7', '33110'),
(20, 20, 'İş', 'Denizli', 'Pamukkale', 'Kınıklı Mah.', 'Üniversite Cad. No: 32/A', '20070'),
(21, 21, 'Ana Adres', 'Malatya', 'Yeşilyurt', 'Çilesiz Mah.', 'İnönü Cad. No: 78/3', '44100'),
(22, 22, 'Ev', 'Şanlıurfa', 'Haliliye', 'Paşabağı Mah.', 'Hayati Harrani Cad. No: 65/5', '63100'),
(23, 23, 'Sabit Adres', 'Hatay', 'Antakya', 'Ekinci Mah.', 'Kurtuluş Cad. No: 43/B', '31050'),
(24, 24, 'İş', 'Manisa', 'Şehzadeler', 'Yarhasanlar Mah.', 'Doğu Cad. No: 25/C', '45020'),
(25, 25, 'Kış Evi', 'Erzurum', 'Yakutiye', 'Muratpaşa Mah.', 'Cumhuriyet Cad. No: 56/3', '25100'),
(26, 26, 'Fatura Adresi', 'Mardin', 'Artuklu', 'Diyarbakırkapı Mah.', '1. Cadde No: 12/A', '47100'),
(27, 27, 'Ev', 'Van', 'İpekyolu', 'Hafiziye Mah.', 'İskele Cad. No: 87/4', '65100'),
(28, 28, 'İkamet', 'Aydın', 'Efeler', 'Kurtuluş Mah.', 'Adnan Menderes Bulvarı No: 34/B', '09100'),
(29, 29, 'Ofis', 'Balıkesir', 'Karesi', 'Dumlupınar Mah.', 'Kızılay Cad. No: 18/C', '10100'),
(30, 30, 'Teslimat Adresi', 'Tekirdağ', 'Çorlu', 'Muhittin Mah.', 'Atatürk Bulvarı No: 99/1', '59850'),
(31, 31, 'OKUL', 'bartın', 'merkez', 'yeşilyöre', 'çeşm-i Cihan kız öğrenci yurdu', '46000'),
(32, 31, 'yurt', 'kahramanmaraş', 'onikişubat', 'yeşilyöre', 'avşar kampüs kız öğrenci yurdu', '46000'),
(33, 33, 'ev', 'Aydın', 'didim', 'aşklar', 'aşklar mahallesi.nursev sokak no 45', '09090'),
(34, 34, 'ev', 'bartın', 'merkez', 'tuzcular', 'kutlubey yazıcılar köyü', '74000');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kategoriler`
--

CREATE TABLE `kategoriler` (
  `KategoriID` int NOT NULL,
  `KategoriAdi` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Aciklama` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `kategoriler`
--

INSERT INTO `kategoriler` (`KategoriID`, `KategoriAdi`, `Aciklama`) VALUES
(1, 'Roman', 'Yerli ve yabancı romanlar, klasikler, modern edebiyat.'),
(2, 'Tarih', 'Dünya tarihi, Osmanlı tarihi, Türkiye Cumhuriyeti tarihi ve biyografiler.'),
(3, 'Bilim Kurgu', 'Uzay maceraları, distopik gelecekler, teknolojik kurgular.'),
(4, 'Felsefe', 'Antik felsefeden modern düşünürlere, varoluşsal sorgulamalar.'),
(5, 'Çocuk Kitapları', '0-12 yaş arası çocuklar için eğitici ve eğlenceli kitaplar.'),
(6, 'Kişisel Gelişim', 'Motivasyon, başarı, iletişim ve psikoloji üzerine kitaplar.'),
(7, 'Şiir', 'Klasik ve modern şiir kitapları, antolojiler.'),
(8, 'Biyografi', 'Ünlü kişilerin hayat hikayeleri, anılar, hatıratlar.'),
(9, 'Ekonomi', 'Finans, yatırım, ekonomi teorisi ve uygulamaları.'),
(10, 'Sağlık', 'Beslenme, fitness, alternatif tıp, sağlıklı yaşam.'),
(11, 'Mizah', 'Komik hikayeler, anekdotlar, karikatür kitapları.'),
(12, 'Sanat', 'Resim, heykel, mimari, müzik üzerine kitaplar.');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kitapeklemelog`
--

CREATE TABLE `kitapeklemelog` (
  `LogID` int NOT NULL,
  `KitapID` int DEFAULT NULL,
  `KitapAdi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `SatıcıKullaniciID` int DEFAULT NULL,
  `EklemeZamani` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `kitapeklemelog`
--

INSERT INTO `kitapeklemelog` (`LogID`, `KitapID`, `KitapAdi`, `SatıcıKullaniciID`, `EklemeZamani`) VALUES
(9, 1, 'İnce Memed', 1, '2025-05-09 11:16:57'),
(10, 2, 'Ben, Robot', 2, '2025-05-09 11:16:57'),
(11, 3, 'Nutuk', 1, '2025-05-09 11:16:57'),
(12, 4, 'Devlet', 3, '2025-05-09 11:16:57'),
(13, 5, 'Küçük Prens', 2, '2025-05-09 11:16:57'),
(14, 6, 'Tutunamayanlar', 1, '2025-05-09 11:16:57'),
(15, 7, 'İnsanları Etkileme Sanatı', 3, '2025-05-09 11:16:57'),
(16, 8, 'Vakıf', 4, '2025-05-09 11:16:57'),
(17, 9, 'Beyaz Gemi', 5, '2025-05-09 11:16:57'),
(18, 10, 'Osmanlı İmparatorluğu', 6, '2025-05-09 11:16:57'),
(19, 11, 'Dune', 7, '2025-05-09 11:16:57'),
(20, 12, 'Sokrates\'in Savunması', 8, '2025-05-09 11:16:57'),
(21, 13, 'Şeker Portakalı', 9, '2025-05-09 11:16:57'),
(22, 14, 'Bir Ömür Nasıl Yaşanır?', 10, '2025-05-09 11:16:57'),
(23, 15, 'Saatleri Ayarlama Enstitüsü', 11, '2025-05-09 11:16:57'),
(24, 16, 'Steve Jobs', 12, '2025-05-09 11:16:57'),
(25, 17, 'Zengin Baba Yoksul Baba', 13, '2025-05-09 11:16:57'),
(26, 18, 'İyi Hissetmek', 14, '2025-05-09 11:16:57'),
(27, 19, 'Yaşlı Çocuk', 15, '2025-05-09 11:16:57'),
(28, 20, 'Bir Resmin Hikayesi', 16, '2025-05-09 11:16:57'),
(29, 21, 'Kürk Mantolu Madonna', 17, '2025-05-09 11:16:57'),
(30, 22, 'Türklerin Tarihi', 18, '2025-05-09 11:16:57'),
(31, 23, 'Otostopçunun Galaksi Rehberi', 19, '2025-05-09 11:16:57'),
(32, 24, 'Bütün Şiirleri (Nazım Hikmet)', 20, '2025-05-09 11:16:57'),
(33, 25, 'Küçük Kara Balık', 21, '2025-05-09 11:16:57'),
(34, 26, 'Yüksek Öğrenimde Öğretim Metotları', 22, '2025-05-09 11:16:57'),
(35, 27, 'Bütün Şiirleri (Cemal Süreya)', 23, '2025-05-09 11:16:57'),
(36, 28, 'Bir Bilim Adamının Romanı: Mustafa İnan', 24, '2025-05-09 11:16:57'),
(37, 29, 'Para Psikolojisi', 25, '2025-05-09 11:16:57'),
(38, 30, 'Beyin: Senin Hikayen', 26, '2025-05-09 11:16:57'),
(39, 1, 'İnce Memed', 1, '2025-05-09 11:17:46'),
(40, 2, 'Ben, Robot', 2, '2025-05-09 11:17:46'),
(41, 3, 'Nutuk', 1, '2025-05-09 11:17:46'),
(42, 4, 'Devlet', 3, '2025-05-09 11:17:46'),
(43, 5, 'Küçük Prens', 2, '2025-05-09 11:17:46'),
(44, 6, 'Tutunamayanlar', 1, '2025-05-09 11:17:46'),
(45, 7, 'İnsanları Etkileme Sanatı', 3, '2025-05-09 11:17:46'),
(46, 8, 'Vakıf', 4, '2025-05-09 11:17:46'),
(47, 9, 'Beyaz Gemi', 5, '2025-05-09 11:17:46'),
(48, 10, 'Osmanlı İmparatorluğu', 6, '2025-05-09 11:17:46'),
(49, 11, 'Dune', 7, '2025-05-09 11:17:46'),
(50, 12, 'Sokrates\'in Savunması', 8, '2025-05-09 11:17:46'),
(51, 13, 'Şeker Portakalı', 9, '2025-05-09 11:17:46'),
(52, 14, 'Bir Ömür Nasıl Yaşanır?', 10, '2025-05-09 11:17:46'),
(53, 15, 'Saatleri Ayarlama Enstitüsü', 11, '2025-05-09 11:17:46'),
(54, 16, 'Steve Jobs', 12, '2025-05-09 11:17:46'),
(55, 17, 'Zengin Baba Yoksul Baba', 13, '2025-05-09 11:17:46'),
(56, 18, 'İyi Hissetmek', 14, '2025-05-09 11:17:46'),
(57, 19, 'Yaşlı Çocuk', 15, '2025-05-09 11:17:46'),
(58, 20, 'Bir Resmin Hikayesi', 16, '2025-05-09 11:17:46'),
(59, 21, 'Kürk Mantolu Madonna', 17, '2025-05-09 11:17:46'),
(60, 22, 'Türklerin Tarihi', 18, '2025-05-09 11:17:46'),
(61, 23, 'Otostopçunun Galaksi Rehberi', 19, '2025-05-09 11:17:46'),
(62, 24, 'Bütün Şiirleri (Nazım Hikmet)', 20, '2025-05-09 11:17:46'),
(63, 25, 'Küçük Kara Balık', 21, '2025-05-09 11:17:46'),
(64, 26, 'Yüksek Öğrenimde Öğretim Metotları', 22, '2025-05-09 11:17:46'),
(65, 27, 'Bütün Şiirleri (Cemal Süreya)', 23, '2025-05-09 11:17:46'),
(66, 28, 'Bir Bilim Adamının Romanı: Mustafa İnan', 24, '2025-05-09 11:17:46'),
(67, 29, 'Para Psikolojisi', 25, '2025-05-09 11:17:46'),
(68, 30, 'Beyin: Senin Hikayen', 26, '2025-05-09 11:17:46'),
(69, 1, 'İnce Memed', 1, '2025-05-09 11:22:08'),
(70, 2, 'Ben, Robot', 2, '2025-05-09 11:22:08'),
(71, 3, 'Nutuk', 1, '2025-05-09 11:22:08'),
(72, 4, 'Devlet', 3, '2025-05-09 11:22:08'),
(73, 5, 'Küçük Prens', 2, '2025-05-09 11:22:08'),
(74, 6, 'Tutunamayanlar', 1, '2025-05-09 11:22:08'),
(75, 7, 'İnsanları Etkileme Sanatı', 3, '2025-05-09 11:22:08'),
(76, 8, 'Vakıf', 4, '2025-05-09 11:22:08'),
(77, 9, 'Beyaz Gemi', 5, '2025-05-09 11:22:08'),
(78, 10, 'Osmanlı İmparatorluğu', 6, '2025-05-09 11:22:08'),
(79, 11, 'Dune', 7, '2025-05-09 11:22:08'),
(80, 12, 'Sokrates\'in Savunması', 8, '2025-05-09 11:22:08'),
(81, 13, 'Şeker Portakalı', 9, '2025-05-09 11:22:08'),
(82, 14, 'Bir Ömür Nasıl Yaşanır?', 10, '2025-05-09 11:22:08'),
(83, 15, 'Saatleri Ayarlama Enstitüsü', 11, '2025-05-09 11:22:08'),
(84, 16, 'Steve Jobs', 12, '2025-05-09 11:22:08'),
(85, 17, 'Zengin Baba Yoksul Baba', 13, '2025-05-09 11:22:08'),
(86, 18, 'İyi Hissetmek', 14, '2025-05-09 11:22:08'),
(87, 19, 'Yaşlı Çocuk', 15, '2025-05-09 11:22:08'),
(88, 20, 'Bir Resmin Hikayesi', 16, '2025-05-09 11:22:08'),
(89, 21, 'Kürk Mantolu Madonna', 17, '2025-05-09 11:22:08'),
(90, 22, 'Türklerin Tarihi', 18, '2025-05-09 11:22:08'),
(91, 23, 'Otostopçunun Galaksi Rehberi', 19, '2025-05-09 11:22:08'),
(92, 24, 'Bütün Şiirleri (Nazım Hikmet)', 20, '2025-05-09 11:22:08'),
(93, 25, 'Küçük Kara Balık', 21, '2025-05-09 11:22:08'),
(94, 26, 'Yüksek Öğrenimde Öğretim Metotları', 22, '2025-05-09 11:22:08'),
(95, 27, 'Bütün Şiirleri (Cemal Süreya)', 23, '2025-05-09 11:22:08'),
(96, 28, 'Bir Bilim Adamının Romanı: Mustafa İnan', 24, '2025-05-09 11:22:08'),
(97, 29, 'Para Psikolojisi', 25, '2025-05-09 11:22:08'),
(98, 30, 'Beyin: Senin Hikayen', 26, '2025-05-09 11:22:08'),
(99, 1, 'İnce Memed', 1, '2025-05-09 11:24:31'),
(100, 2, 'Ben, Robot', 2, '2025-05-09 11:24:31'),
(101, 3, 'Nutuk', 1, '2025-05-09 11:24:31'),
(102, 4, 'Devlet', 3, '2025-05-09 11:24:31'),
(103, 5, 'Küçük Prens', 2, '2025-05-09 11:24:31'),
(104, 6, 'Tutunamayanlar', 1, '2025-05-09 11:24:31'),
(105, 7, 'İnsanları Etkileme Sanatı', 3, '2025-05-09 11:24:31'),
(106, 8, 'Vakıf', 4, '2025-05-09 11:24:31'),
(107, 9, 'Beyaz Gemi', 5, '2025-05-09 11:24:31'),
(108, 10, 'Osmanlı İmparatorluğu', 6, '2025-05-09 11:24:31'),
(109, 11, 'Dune', 7, '2025-05-09 11:24:31'),
(110, 12, 'Sokrates\'in Savunması', 8, '2025-05-09 11:24:31'),
(111, 13, 'Şeker Portakalı', 9, '2025-05-09 11:24:31'),
(112, 14, 'Bir Ömür Nasıl Yaşanır?', 10, '2025-05-09 11:24:31'),
(113, 15, 'Saatleri Ayarlama Enstitüsü', 11, '2025-05-09 11:24:31'),
(114, 16, 'Steve Jobs', 12, '2025-05-09 11:24:31'),
(115, 17, 'Zengin Baba Yoksul Baba', 13, '2025-05-09 11:24:31'),
(116, 18, 'İyi Hissetmek', 14, '2025-05-09 11:24:31'),
(117, 19, 'Yaşlı Çocuk', 15, '2025-05-09 11:24:31'),
(118, 20, 'Bir Resmin Hikayesi', 16, '2025-05-09 11:24:31'),
(119, 21, 'Kürk Mantolu Madonna', 17, '2025-05-09 11:24:31'),
(120, 22, 'Türklerin Tarihi', 18, '2025-05-09 11:24:31'),
(121, 23, 'Otostopçunun Galaksi Rehberi', 19, '2025-05-09 11:24:31'),
(122, 24, 'Bütün Şiirleri (Nazım Hikmet)', 20, '2025-05-09 11:24:31'),
(123, 25, 'Küçük Kara Balık', 21, '2025-05-09 11:24:31'),
(124, 26, 'Yüksek Öğrenimde Öğretim Metotları', 22, '2025-05-09 11:24:31'),
(125, 27, 'Bütün Şiirleri (Cemal Süreya)', 23, '2025-05-09 11:24:31'),
(126, 28, 'Bir Bilim Adamının Romanı: Mustafa İnan', 24, '2025-05-09 11:24:31'),
(127, 29, 'Para Psikolojisi', 25, '2025-05-09 11:24:31'),
(128, 30, 'Beyin: Senin Hikayen', 26, '2025-05-09 11:24:31'),
(129, 31, 'Gece yarısı kütüphanesi', 31, '2025-05-09 12:07:28'),
(130, 32, 'Gece yarısı kütüphanesi', 31, '2025-05-09 12:08:06'),
(131, 33, 'Yaşamak', 31, '2025-05-09 12:40:33'),
(132, 34, 'kendine hoş geldin', 32, '2025-05-11 11:58:19'),
(133, 35, 'kendine hoş geldin', 32, '2025-05-11 12:00:27');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kitaplar`
--

CREATE TABLE `kitaplar` (
  `KitapID` int NOT NULL,
  `SatıcıKullaniciID` int NOT NULL,
  `KategoriID` int NOT NULL,
  `KitapAdi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `YazarAdi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ISBN` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `YayinEvi` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `BaskiYili` int DEFAULT NULL,
  `SayfaSayisi` int DEFAULT NULL,
  `Kondisyon` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Aciklama` text COLLATE utf8mb4_unicode_ci,
  `Fiyat` decimal(10,2) NOT NULL,
  `StokAdedi` int NOT NULL DEFAULT '1',
  `ListelemeTarihi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `GuncellemeTarihi` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `Durum` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Satışta',
  `KapakFotografiURL` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ;

--
-- Tablo döküm verisi `kitaplar`
--

INSERT INTO `kitaplar` (`KitapID`, `SatıcıKullaniciID`, `KategoriID`, `KitapAdi`, `YazarAdi`, `ISBN`, `YayinEvi`, `BaskiYili`, `SayfaSayisi`, `Kondisyon`, `Aciklama`, `Fiyat`, `StokAdedi`, `ListelemeTarihi`, `GuncellemeTarihi`, `Durum`, `KapakFotografiURL`) VALUES
(1, 1, 1, 'İnce Memed', 'Yaşar Kemal', '9789750806801', 'Yapı Kredi Yayınları', 2015, 436, 'Yeni Gibi', 'Toros Dağları\'nda geçen destansı bir roman.', 35.00, 1, '2025-05-09 11:24:31', '2025-05-11 12:05:28', 'Satışta', ''),
(2, 2, 3, 'Ben, Robot', 'Isaac Asimov', '9786053757917', 'İthaki Yayınları', 2018, 280, 'İyi', 'Robot yasaları ve yapay zeka üzerine klasik öyküler.', 28.50, 0, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satıldı', 'uploads/ben_robot.jpg'),
(3, 1, 2, 'Nutuk', 'Mustafa Kemal Atatürk', '9789754587487', 'İş Bankası Kültür Yayınları', 2006, 632, 'Orta', 'Türkiye Cumhuriyeti\'nin kuruluşunu anlatan temel eser.', 45.00, 1, '2025-05-09 11:24:31', '2025-05-15 09:43:56', 'Satışta', NULL),
(4, 3, 4, 'Devlet', 'Platon', '9789754580509', 'İş Bankası Kültür Yayınları', 2016, 416, 'İyi', 'İdeal devlet düzeni üzerine felsefi bir diyalog.', 22.75, 0, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satıldı', 'uploads/devlet_platon.jpg'),
(5, 2, 5, 'Küçük Prens', 'Antoine de Saint-Exupéry', '9789750730285', 'Can Çocuk Yayınları', 2019, 112, 'Yeni Gibi', 'Büyüklere masallar, çocuklara hayat dersleri.', 18.00, 3, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satışta', 'uploads/kucuk_prens.jpg'),
(6, 1, 1, 'Tutunamayanlar', 'Oğuz Atay', '9789750500900', 'İletişim Yayınları', 2017, 724, 'Yıpranmış', 'Türk edebiyatının kült romanlarından.', 20.00, 0, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satıldı', NULL),
(7, 3, 6, 'İnsanları Etkileme Sanatı', 'Dale Carnegie', '9789753310118', 'Epsilon Yayınevi', 2015, 320, 'İyi', 'Kişisel gelişim ve iletişim becerileri üzerine.', 30.00, 0, '2025-05-09 11:24:31', NULL, 'Satıldı', 'uploads/insanlari_etkileme.jpg'),
(8, 4, 3, 'Vakıf', 'Isaac Asimov', '9786053758020', 'İthaki Yayınları', 2019, 256, 'Yeni Gibi', 'Galaktik bir imparatorluğun çöküşü ve yeniden kuruluşu.', 32.00, 1, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satışta', 'uploads/vakif.jpg'),
(9, 5, 1, 'Beyaz Gemi', 'Cengiz Aytmatov', '9789750817434', 'Ötüken Neşriyat', 2017, 168, 'İyi', 'Kırgız yazarın en ünlü eserlerinden biri.', 28.50, 2, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satışta', 'uploads/beyaz_gemi.jpg'),
(10, 6, 2, 'Osmanlı İmparatorluğu', 'Halil İnalcık', '9789754709612', 'Kronik Kitap', 2018, 256, 'Yeni Gibi', 'Osmanlı tarihinin derinlikli bir incelemesi.', 45.00, 0, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satıldı', 'uploads/osmanli_imparatorlugu.jpg'),
(11, 7, 3, 'Dune', 'Frank Herbert', '9786053759140', 'İthaki Yayınları', 2021, 712, 'Yeni Gibi', 'Bilim kurgu klasiği, çöl gezegeni Arrakis\'te geçen epik bir hikaye.', 65.00, 0, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satıldı', 'uploads/dune.jpg'),
(12, 8, 4, 'Sokrates\'in Savunması', 'Platon', '9789944883641', 'Türkiye İş Bankası Kültür Yayınları', 2020, 112, 'Orta', 'Antik Yunan filozofu Sokrates\'in yargılanmasını anlatan klasik eser.', 18.00, 1, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satışta', 'uploads/sokrates_savunmasi.jpg'),
(13, 9, 5, 'Şeker Portakalı', 'Jose Mauro de Vasconcelos', '9789750738609', 'Can Yayınları', 2019, 182, 'İyi', 'Brezilya\'da küçük bir çocuğun büyüme hikayesi.', 25.00, 3, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satışta', 'uploads/seker_portakali.jpg'),
(14, 10, 6, 'Bir Ömür Nasıl Yaşanır?', 'İlber Ortaylı', '9786050831894', 'Kronik Kitap', 2019, 288, 'Yeni Gibi', 'Ünlü tarihçinin hayat tecrübelerini anlattığı eser.', 32.00, 1, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satışta', 'uploads/bir_omur_nasil_yasanir.jpg'),
(15, 11, 7, 'Saatleri Ayarlama Enstitüsü', 'Ahmet Hamdi Tanpınar', '9789750828904', 'Dergah Yayınları', 2018, 382, 'İyi', 'Modern Türk edebiyatının başyapıtlarından.', 38.00, 0, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satıldı', 'uploads/saatleri_ayarlama_enstitusu.jpg'),
(16, 12, 8, 'Steve Jobs', 'Walter Isaacson', '9786051423333', 'Domingo Yayınları', 2015, 600, 'Orta', 'Apple\'ın kurucusunun kapsamlı biyografisi.', 45.00, 0, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satıldı', 'uploads/steve_jobs.jpg'),
(17, 13, 9, 'Zengin Baba Yoksul Baba', 'Robert T. Kiyosaki', '9786257979573', 'Epsilon Yayınevi', 2020, 256, 'Yeni Gibi', 'Finansal özgürlük üzerine çok satan bir kitap.', 29.90, 2, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satışta', 'uploads/zengin_baba_yoksul_baba.jpg'),
(18, 14, 10, 'İyi Hissetmek', 'David D. Burns', '9786059613767', 'Psikonet Yayınları', 2019, 408, 'İyi', 'Kanıtlanmış yeni duygu durum tedavisi.', 42.00, 0, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satıldı', 'uploads/iyi_hissetmek.jpg'),
(19, 15, 11, 'Yaşlı Çocuk', 'Aziz Nesin', '9789759038656', 'Nesin Yayınevi', 2016, 208, 'Orta', 'Ünlü yazarın mizahi öyküler derlemesi.', 22.00, 1, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satışta', 'uploads/yasli_cocuk.jpg'),
(20, 16, 12, 'Bir Resmin Hikayesi', 'Orhan Pamuk', '9789750828904', 'Yapı Kredi Yayınları', 2022, 118, 'Yeni Gibi', 'Nobelli yazarın sanat üzerine düşünceleri.', 35.00, 0, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satıldı', 'uploads/bir_resmin_hikayesi.jpg'),
(21, 17, 1, 'Kürk Mantolu Madonna', 'Sabahattin Ali', '9789753638029', 'Yapı Kredi Yayınları', 2019, 160, 'İyi', 'Türk edebiyatının klasiklerinden aşk romanı.', 20.00, 3, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satışta', 'uploads/kurk_mantolu_madonna.jpg'),
(22, 18, 2, 'Türklerin Tarihi', 'İlber Ortaylı', '9789750826214', 'Timaş Yayınları', 2020, 344, 'Yeni Gibi', 'Türklerin tarihini geniş bir perspektiften ele alan kapsamlı bir eser.', 48.00, 0, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satıldı', 'uploads/turklerin_tarihi.jpg'),
(23, 19, 3, 'Otostopçunun Galaksi Rehberi', 'Douglas Adams', '9786050949322', 'Alfa Yayıncılık', 2017, 256, 'İyi', 'Uzaya, evrene ve her şeye dair komik bir bilim kurgu klasiği.', 32.50, 1, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satışta', 'uploads/otostopcunun_galaksi_rehberi.jpg'),
(24, 20, 7, 'Bütün Şiirleri (Nazım Hikmet)', 'Nazım Hikmet Ran', '9789750817410', 'Yapı Kredi Yayınları', 2020, 1852, 'Yeni Gibi', 'Nazım Hikmet\'in bütün şiirlerini içeren kapsamlı bir derleme.', 75.00, 0, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satıldı', 'uploads/butun_siirleri_nh.jpg'),
(25, 21, 5, 'Küçük Kara Balık', 'Samed Behrengi', '9789752472570', 'Can Çocuk Yayınları', 2018, 64, 'İyi', 'Hem çocuklara hem yetişkinlere hitap eden bir klasik.', 16.00, 2, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satışta', 'uploads/kucuk_kara_balik.jpg'),
(26, 22, 6, 'Yüksek Öğrenimde Öğretim Metotları', 'Meral Aksu', '9786257773232', 'Pegem Akademi', 2019, 320, 'Orta', 'Akademisyenler ve öğretmenler için eğitim metodolojisi rehberi.', 45.00, 0, '2025-05-09 11:24:31', NULL, 'Satıldı', 'uploads/yuksek_ogrenimde_ogretim.jpg'),
(27, 23, 7, 'Bütün Şiirleri (Cemal Süreya)', 'Cemal Süreya', '9789750519079', 'Yapı Kredi Yayınları', 2021, 408, 'Yeni Gibi', 'İkinci Yeni akımının önemli şairinin tüm eserleri.', 40.00, 0, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satıldı', 'uploads/butun_siirleri_cs.jpg'),
(28, 24, 8, 'Bir Bilim Adamının Romanı: Mustafa İnan', 'Oğuz Atay', '9789754707632', 'İletişim Yayınları', 2018, 312, 'İyi', 'Prof. Dr. Mustafa İnan\'ın hayatını anlatan biyografik roman.', 33.00, 1, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satışta', 'uploads/bir_bilim_adaminin_romani.jpg'),
(29, 25, 9, 'Para Psikolojisi', 'Morgan Housel', '9786254412080', 'Sola Unitas', 2022, 240, 'Yeni Gibi', 'Para ve yatırım konusunda psikolojik yaklaşımlar.', 38.00, 0, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satıldı', 'uploads/para_psikolojisi.jpg'),
(30, 26, 10, 'Beyin: Senin Hikayen', 'David Eagleman', '9786052961803', 'Domingo Yayınları', 2020, 352, 'İyi', 'Beynin gizemleri üzerine sürükleyici bir bilimsel yolculuk.', 45.00, 0, '2025-05-09 11:24:31', '2025-05-09 11:24:31', 'Satıldı', 'uploads/beyin_senin_hikayen.jpg'),
(32, 31, 1, 'Gece yarısı kütüphanesi', 'MATT HAIG', NULL, 'domingo', 2024, 296, 'İyi', NULL, 100.50, 1, '2025-05-09 12:08:06', NULL, 'Satışta', 'uploads/kitap_kapaklari/user31_1746792486.jpg'),
(33, 31, 1, 'Yaşamak', 'Yu Hua', NULL, 'Timas', 2022, 210, 'İyi', 'Babasının kendisine bıraktığı mirası kısa sürede hiç eden genç adam, ardından tüm yakınlarını sırasıyla kaybettiği sefil ve acı dolu yaşam öyküsünü aktarıyor. Ancak tüm bunlara rağmen, hayatın ona öğrettikleriyle bambaşka bir insana dönüştüğünü de ispatlıyor.', 70.00, 1, '2025-05-09 12:40:33', NULL, 'Satışta', 'uploads/kitap_kapaklari/user31_1746794433.jpeg'),
(35, 32, 6, 'kendine hoş geldin', 'Miraç Çağrı AKTAŞ', NULL, 'Olimpos', 2018, 160, 'Orta', 'Miraç Çağrı Aktaş, Kendine Hoş Geldin adlı kitabında sizi, kendinizi bulmaya ve hak ettiğiniz değeri kendinize vermeye çağırıyor. Bu kitapla duygu dünyanızın yegane başrolü olan benliğinize geri dönecek ve kendinize tüm içtenliğinizle “Hoş Geldin!” diyeceksiniz.', 60.00, 4, '2025-05-11 12:00:27', '2025-06-14 10:11:42', 'Satışta', 'uploads/kitap_kapaklari/user32_1746964827.jpeg');

--
-- Tetikleyiciler `kitaplar`
--
DELIMITER $$
CREATE TRIGGER `trg_KitapEkle_LogYaz` AFTER INSERT ON `kitaplar` FOR EACH ROW BEGIN
    INSERT INTO KitapEklemeLog (KitapID, KitapAdi, SatıcıKullaniciID, EklemeZamani)
    VALUES (NEW.KitapID, NEW.KitapAdi, NEW.SatıcıKullaniciID, NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicilar`
--

CREATE TABLE `kullanicilar` (
  `KullaniciID` int NOT NULL,
  `Ad` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Soyad` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `Eposta` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `SifreHash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `TelefonNo` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `KayitTarihi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `SonGirisTarihi` timestamp NULL DEFAULT NULL,
  `AktifMi` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Tablo döküm verisi `kullanicilar`
--

INSERT INTO `kullanicilar` (`KullaniciID`, `Ad`, `Soyad`, `Eposta`, `SifreHash`, `TelefonNo`, `KayitTarihi`, `SonGirisTarihi`, `AktifMi`) VALUES
(1, 'Ahmet', 'Yılmaz', 'ahmet.yilmaz@example.com', '$2y$10$K7jZJ6vX0J5Z9Q8L6N1P7e1RzF5U9N3T2M8b6vS4B0G7H1J2K3L4', '5551112233', '2025-05-08 11:24:31', NULL, 1),
(2, 'Ayşe', 'Kaya', 'ayse.kaya@example.com', '$2y$10$eP0o8I5wR2uT4sL7dN9vC1xZ3qF6aB8jM0kH5gV1oS2fD9yG3iJ7', '5554445566', '2025-05-07 11:24:31', NULL, 1),
(3, 'Mehmet', 'Demir', 'mehmet.demir@example.com', '$2y$10$uN1rA7gS4jK0bM3vF9xP2qE5tH8oL6wC1zD2iJ5sV0eG7kP8oT3', '5557778899', '2025-05-06 11:24:31', NULL, 1),
(4, 'Zeynep', 'Öztürk', 'zeynep.ozturk@example.com', '$2y$10$K8jZJ6vX0J5Z9Q8L6N1P7e1RzF5U9N3T2M8b6vS4B0G7H1J2K3L4', '5551122334', '2025-05-04 11:24:31', NULL, 1),
(5, 'Mustafa', 'Çelik', 'mustafa.celik@example.com', '$2y$10$eR0o8I5wR2uT4sL7dN9vC1xZ3qF6aB8jM0kH5gV1oS2fD9yG3iJ7', '5552233445', '2025-04-29 11:24:31', NULL, 1),
(6, 'Elif', 'Arslan', 'elif.arslan@example.com', '$2y$10$uV1rA7gS4jK0bM3vF9xP2qE5tH8oL6wC1zD2iJ5sV0eG7kP8oT3', '5553344556', '2025-04-24 11:24:31', NULL, 1),
(7, 'Emre', 'Koç', 'emre.koc@example.com', '$2y$10$K7jZJ6vX0J5Z9Q8L6N1P7e1RzF5U9N3T2M8b6vS4B0G7H1J2K3L4', '5554455667', '2025-04-19 11:24:31', NULL, 1),
(8, 'Deniz', 'Yıldız', 'deniz.yildiz@example.com', '$2y$10$eP0o8I5wR2uT4sL7dN9vC1xZ3qF6aB8jM0kH5gV1oS2fD9yG3iJ7', '5555566778', '2025-04-14 11:24:31', NULL, 1),
(9, 'Burak', 'Aydın', 'burak.aydin@example.com', '$2y$10$uN1rA7gS4jK0bM3vF9xP2qE5tH8oL6wC1zD2iJ5sV0eG7kP8oT3', '5556677889', '2025-04-09 11:24:31', NULL, 1),
(10, 'Selin', 'Güneş', 'selin.gunes@example.com', '$2y$10$K7jZJ6vX0J5Z9Q8L6N1P7e1RzF5U9N3T2M8b6vS4B0G7H1J2K3L4', '5557788990', '2025-04-07 11:24:31', NULL, 1),
(11, 'Onur', 'Şahin', 'onur.sahin@example.com', '$2y$10$eP0o8I5wR2uT4sL7dN9vC1xZ3qF6aB8jM0kH5gV1oS2fD9yG3iJ7', '5558899001', '2025-04-05 11:24:31', NULL, 1),
(12, 'Gizem', 'Aktaş', 'gizem.aktas@example.com', '$2y$10$uN1rA7gS4jK0bM3vF9xP2qE5tH8oL6wC1zD2iJ5sV0eG7kP8oT3', '5559900112', '2025-04-03 11:24:31', NULL, 1),
(13, 'Can', 'Doğan', 'can.dogan@example.com', '$2y$10$K7jZJ6vX0J5Z9Q8L6N1P7e1RzF5U9N3T2M8b6vS4B0G7H1J2K3L4', '5550011223', '2025-04-01 11:24:31', NULL, 1),
(14, 'Ebru', 'Kartal', 'ebru.kartal@example.com', '$2y$10$eP0o8I5wR2uT4sL7dN9vC1xZ3qF6aB8jM0kH5gV1oS2fD9yG3iJ7', '5551122334', '2025-03-30 11:24:31', NULL, 1),
(15, 'Serkan', 'Özdemir', 'serkan.ozdemir@example.com', '$2y$10$uN1rA7gS4jK0bM3vF9xP2qE5tH8oL6wC1zD2iJ5sV0eG7kP8oT3', '5552233445', '2025-03-28 11:24:31', NULL, 1),
(16, 'Ceren', 'Yalçın', 'ceren.yalcin@example.com', '$2y$10$K7jZJ6vX0J5Z9Q8L6N1P7e1RzF5U9N3T2M8b6vS4B0G7H1J2K3L4', '5553344556', '2025-03-26 11:24:31', NULL, 1),
(17, 'Tolga', 'Çetin', 'tolga.cetin@example.com', '$2y$10$eP0o8I5wR2uT4sL7dN9vC1xZ3qF6aB8jM0kH5gV1oS2fD9yG3iJ7', '5554455667', '2025-03-24 11:24:31', NULL, 1),
(18, 'Aslı', 'Tuncer', 'asli.tuncer@example.com', '$2y$10$uN1rA7gS4jK0bM3vF9xP2qE5tH8oL6wC1zD2iJ5sV0eG7kP8oT3', '5555566778', '2025-03-22 11:24:31', NULL, 1),
(19, 'Berk', 'Erdoğan', 'berk.erdogan@example.com', '$2y$10$K7jZJ6vX0J5Z9Q8L6N1P7e1RzF5U9N3T2M8b6vS4B0G7H1J2K3L4', '5556677889', '2025-03-20 11:24:31', NULL, 1),
(20, 'Pınar', 'Aksoy', 'pinar.aksoy@example.com', '$2y$10$eP0o8I5wR2uT4sL7dN9vC1xZ3qF6aB8jM0kH5gV1oS2fD9yG3iJ7', '5557788990', '2025-03-18 11:24:31', NULL, 1),
(21, 'Kaan', 'Bulut', 'kaan.bulut@example.com', '$2y$10$uN1rA7gS4jK0bM3vF9xP2qE5tH8oL6wC1zD2iJ5sV0eG7kP8oT3', '5558899001', '2025-03-16 11:24:31', NULL, 0),
(22, 'Merve', 'Aksu', 'merve.aksu@example.com', '$2y$10$K7jZJ6vX0J5Z9Q8L6N1P7e1RzF5U9N3T2M8b6vS4B0G7H1J2K3L4', '5559900112', '2025-03-14 11:24:31', NULL, 1),
(23, 'Alper', 'Taş', 'alper.tas@example.com', '$2y$10$eP0o8I5wR2uT4sL7dN9vC1xZ3qF6aB8jM0kH5gV1oS2fD9yG3iJ7', '5550011223', '2025-03-12 11:24:31', NULL, 1),
(24, 'Gamze', 'Yıldırım', 'gamze.yildirim@example.com', '$2y$10$uN1rA7gS4jK0bM3vF9xP2qE5tH8oL6wC1zD2iJ5sV0eG7kP8oT3', '5551122334', '2025-03-10 11:24:31', NULL, 1),
(25, 'Cem', 'Türk', 'cem.turk@example.com', '$2y$10$K7jZJ6vX0J5Z9Q8L6N1P7e1RzF5U9N3T2M8b6vS4B0G7H1J2K3L4', '5552233445', '2025-03-08 11:24:31', NULL, 1),
(26, 'Melis', 'Özer', 'melis.ozer@example.com', '$2y$10$eP0o8I5wR2uT4sL7dN9vC1xZ3qF6aB8jM0kH5gV1oS2fD9yG3iJ7', '5553344556', '2025-03-06 11:24:31', NULL, 0),
(27, 'Arda', 'Korkmaz', 'arda.korkmaz@example.com', '$2y$10$uN1rA7gS4jK0bM3vF9xP2qE5tH8oL6wC1zD2iJ5sV0eG7kP8oT3', '5554455667', '2025-03-04 11:24:31', NULL, 1),
(28, 'İrem', 'Şen', 'irem.sen@example.com', '$2y$10$K7jZJ6vX0J5Z9Q8L6N1P7e1RzF5U9N3T2M8b6vS4B0G7H1J2K3L4', '5555566778', '2025-03-02 11:24:31', NULL, 1),
(29, 'Ozan', 'Güler', 'ozan.guler@example.com', '$2y$10$eP0o8I5wR2uT4sL7dN9vC1xZ3qF6aB8jM0kH5gV1oS2fD9yG3iJ7', '5556677889', '2025-02-28 11:24:31', NULL, 1),
(30, 'Nazlı', 'Acar', 'nazli.acar@example.com', '$2y$10$uN1rA7gS4jK0bM3vF9xP2qE5tH8oL6wC1zD2iJ5sV0eG7kP8oT3', '5557788990', '2025-02-26 11:24:31', NULL, 1),
(31, 'melike', 'İmalı', 'melikeimali387@gmail.com', '$2y$10$B6lZ3Nk39ke/u9O7fZwgvOBXCJqvB8A1olpPeMLBPgt5uLQOAJ5pS', '5315695876', '2025-05-09 11:29:32', '2025-05-15 14:58:41', 1),
(32, 'erdem', 'derici', 'melikeay387@gmail.com', '$2y$10$RLy1BTN9MXfM6SZOVPvCTOiyI6saco31pPdk0AruKr5yVGsMFHAFC', '5315695876', '2025-05-09 14:22:08', '2025-05-11 13:22:36', 1),
(33, 'Sevda', 'Yalçın', 'nursev.2661@gmail.com', '$2y$10$UuqtPqt0YaP6TduBsC3fFuXOpFrDEhNU00KHl35HblS6phVXtMCKG', '5315695876', '2025-05-24 10:53:42', '2025-05-24 10:53:50', 1),
(34, 'mustafa', 'kayık', 'mustafa387@gmail.com', '$2y$10$oqbyqdzZfa0YNd/DArC2cO53iZ3lM7nl6L7kHVx3CYsWLYfOWyGAO', '5315695876', '2025-06-14 10:07:00', '2025-06-14 10:07:10', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `siparisdetaylari`
--

CREATE TABLE `siparisdetaylari` (
  `SiparisDetayID` int NOT NULL,
  `SiparisID` int NOT NULL,
  `KitapID` int NOT NULL,
  `Adet` int NOT NULL DEFAULT '1',
  `BirimFiyat` decimal(10,2) NOT NULL
) ;

--
-- Tablo döküm verisi `siparisdetaylari`
--

INSERT INTO `siparisdetaylari` (`SiparisDetayID`, `SiparisID`, `KitapID`, `Adet`, `BirimFiyat`) VALUES
(1, 1, 2, 1, 28.50),
(2, 1, 5, 1, 18.00),
(3, 2, 11, 1, 65.00),
(4, 3, 12, 1, 18.00),
(5, 4, 10, 1, 45.00),
(6, 5, 13, 1, 25.00),
(7, 6, 14, 1, 32.00),
(8, 7, 15, 1, 38.00),
(9, 8, 16, 1, 45.00),
(10, 9, 17, 1, 29.90),
(11, 10, 18, 1, 42.00),
(12, 11, 19, 1, 22.00),
(13, 12, 20, 1, 35.00),
(14, 13, 21, 1, 20.00),
(15, 14, 22, 1, 48.00),
(16, 15, 23, 1, 32.50),
(17, 16, 24, 1, 75.00),
(18, 17, 25, 1, 16.00),
(19, 18, 27, 1, 40.00),
(20, 19, 28, 1, 33.00),
(21, 19, 3, 1, 22.00),
(22, 20, 29, 1, 38.00),
(23, 21, 30, 1, 45.00),
(24, 22, 1, 1, 35.00),
(25, 23, 9, 1, 28.50),
(26, 23, 4, 1, 22.75),
(27, 24, 8, 1, 32.00),
(28, 25, 5, 1, 18.00),
(29, 26, 2, 1, 25.50),
(30, 27, 11, 1, 65.00),
(31, 28, 6, 1, 20.00),
(32, 29, 10, 1, 45.00),
(33, 30, 13, 1, 25.00),
(34, 31, 3, 1, 45.00),
(35, 32, 35, 1, 60.00);

--
-- Tetikleyiciler `siparisdetaylari`
--
DELIMITER $$
CREATE TRIGGER `trg_SiparisDetayEkle_StokAzalt_TutarGuncelle` AFTER INSERT ON `siparisdetaylari` FOR EACH ROW BEGIN
    -- Kitap stoğunu azalt
    UPDATE Kitaplar
    SET StokAdedi = StokAdedi - NEW.Adet
    WHERE KitapID = NEW.KitapID;

    -- Eğer stok 0 veya altına düşerse kitabın durumunu 'Satıldı' yap
    UPDATE Kitaplar
    SET Durum = 'Satıldı'
    WHERE KitapID = NEW.KitapID AND StokAdedi <= 0;

    -- Siparişin toplam tutarını güncelle (bu, her detay eklendiğinde yapılabilir veya sipariş tamamlandığında tek seferde yapılabilir)
    -- Daha iyi bir yaklaşım, siparişin toplam tutarını ayrı bir SP ile hesaplatıp sipariş tablosuna yazdırmaktır.
    -- Ancak trigger içinde de yapılabilir:
    UPDATE Siparisler
    SET ToplamTutar = (SELECT SUM(Adet * BirimFiyat) FROM SiparisDetaylari WHERE SiparisID = NEW.SiparisID)
    WHERE SiparisID = NEW.SiparisID;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `siparisler`
--

CREATE TABLE `siparisler` (
  `SiparisID` int NOT NULL,
  `AliciKullaniciID` int NOT NULL,
  `TeslimatAdresID` int NOT NULL,
  `SiparisTarihi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ToplamTutar` decimal(10,2) NOT NULL,
  `SiparisDurumu` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ödeme Bekleniyor',
  `OdemeYontemi` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `KargoTakipNo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ;

--
-- Tablo döküm verisi `siparisler`
--

INSERT INTO `siparisler` (`SiparisID`, `AliciKullaniciID`, `TeslimatAdresID`, `SiparisTarihi`, `ToplamTutar`, `SiparisDurumu`, `OdemeYontemi`, `KargoTakipNo`) VALUES
(1, 2, 2, '2025-04-29 11:24:31', 46.50, 'Teslim Edildi', 'Kredi Kartı', 'TRK1000001'),
(2, 3, 3, '2025-05-01 11:24:31', 65.00, 'Teslim Edildi', 'Havale/EFT', 'TRK1000002'),
(3, 1, 1, '2025-05-04 11:24:31', 18.00, 'Kargoda', 'Kapıda Ödeme', 'TRK1000003'),
(4, 4, 4, '2025-05-06 11:24:31', 45.00, 'Hazırlanıyor', 'Kredi Kartı', NULL),
(5, 5, 5, '2025-05-07 11:24:31', 25.00, 'Ödeme Bekleniyor', 'Kredi Kartı', NULL),
(6, 6, 6, '2025-04-27 11:24:31', 32.00, 'Teslim Edildi', 'Kredi Kartı', 'TRK1000004'),
(7, 7, 7, '2025-05-08 11:24:31', 38.00, 'Hazırlanıyor', 'Havale/EFT', NULL),
(8, 8, 8, '2025-04-24 11:24:31', 45.00, 'Teslim Edildi', 'Kredi Kartı', 'TRK1000005'),
(9, 9, 9, '2025-05-03 11:24:31', 29.90, 'Kargoda', 'Kapıda Ödeme', 'TRK1000006'),
(10, 10, 10, '2025-05-05 11:24:31', 42.00, 'Hazırlanıyor', 'Kredi Kartı', NULL),
(11, 11, 11, '2025-04-19 11:24:31', 22.00, 'Teslim Edildi', 'Havale/EFT', 'TRK1000007'),
(12, 12, 12, '2025-04-21 11:24:31', 35.00, 'İptal Edildi', 'Kredi Kartı', NULL),
(13, 13, 13, '2025-04-30 11:24:31', 20.00, 'Kargoda', 'Kredi Kartı', 'TRK1000008'),
(14, 14, 14, '2025-05-02 11:24:31', 48.00, 'Hazırlanıyor', 'Kapıda Ödeme', NULL),
(15, 15, 15, '2025-04-17 11:24:31', 32.50, 'Teslim Edildi', 'Kredi Kartı', 'TRK1000009'),
(16, 16, 16, '2025-04-26 11:24:31', 75.00, 'Kargoda', 'Havale/EFT', 'TRK1000010'),
(17, 17, 17, '2025-04-28 11:24:31', 16.00, 'Teslim Edildi', 'Kredi Kartı', 'TRK1000011'),
(18, 18, 18, '2025-04-14 11:24:31', 40.00, 'Teslim Edildi', 'Kapıda Ödeme', 'TRK1000012'),
(19, 19, 19, '2025-04-23 11:24:31', 55.00, 'Kargoda', 'Kredi Kartı', 'TRK1000013'),
(20, 20, 20, '2025-04-25 11:24:31', 38.00, 'Hazırlanıyor', 'Havale/EFT', NULL),
(21, 22, 22, '2025-04-11 11:24:31', 45.00, 'Teslim Edildi', 'Kredi Kartı', 'TRK1000014'),
(22, 23, 23, '2025-04-20 11:24:31', 35.00, 'Kargoda', 'Kapıda Ödeme', 'TRK1000015'),
(23, 24, 24, '2025-04-22 11:24:31', 51.25, 'Teslim Edildi', 'Kredi Kartı', 'TRK1000016'),
(24, 25, 25, '2025-04-09 11:24:31', 32.00, 'Teslim Edildi', 'Havale/EFT', 'TRK1000017'),
(25, 27, 27, '2025-04-16 11:24:31', 18.00, 'İptal Edildi', 'Kredi Kartı', NULL),
(26, 28, 28, '2025-04-18 11:24:31', 25.50, 'Kargoda', 'Kapıda Ödeme', 'TRK1000018'),
(27, 29, 29, '2025-04-13 11:24:31', 65.00, 'Teslim Edildi', 'Kredi Kartı', 'TRK1000019'),
(28, 30, 30, '2025-04-15 11:24:31', 20.00, 'Hazırlanıyor', 'Havale/EFT', NULL),
(29, 1, 2, '2025-04-12 11:24:31', 45.00, 'Teslim Edildi', 'Kredi Kartı', 'TRK1000020'),
(30, 2, 3, '2025-04-10 11:24:31', 25.00, 'Kargoda', 'Kapıda Ödeme', 'TRK1000021'),
(31, 31, 31, '2025-05-15 09:43:56', 45.00, 'Ödeme Bekleniyor', 'Kapıda Ödeme', NULL),
(32, 34, 34, '2025-06-14 10:11:42', 60.00, 'Ödeme Bekleniyor', 'Kapıda Ödeme', NULL);

--
-- Tetikleyiciler `siparisler`
--
DELIMITER $$
CREATE TRIGGER `trg_SiparisIptal_StokIade` AFTER UPDATE ON `siparisler` FOR EACH ROW BEGIN
    -- Eğer sipariş durumu 'İptal Edildi' olarak değiştiyse ve daha önce iptal edilmemişse
    IF NEW.SiparisDurumu = 'İptal Edildi' AND OLD.SiparisDurumu != 'İptal Edildi' THEN
        -- İptal edilen siparişteki her bir kitap için stoğu iade et
        UPDATE Kitaplar k
        INNER JOIN SiparisDetaylari sd ON k.KitapID = sd.KitapID
        SET k.StokAdedi = k.StokAdedi + sd.Adet,
            k.Durum = IF(k.StokAdedi + sd.Adet > 0, 'Satışta', k.Durum) -- Stoğu geri alınca 'Satışta' yap
        WHERE sd.SiparisID = NEW.SiparisID;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `yorumlar`
--

CREATE TABLE `yorumlar` (
  `YorumID` int NOT NULL,
  `KitapID` int NOT NULL,
  `KullaniciID` int NOT NULL,
  `Puan` int NOT NULL,
  `YorumMetni` text COLLATE utf8mb4_unicode_ci,
  `YorumTarihi` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `OnayDurumu` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Beklemede'
) ;

--
-- Tablo döküm verisi `yorumlar`
--

INSERT INTO `yorumlar` (`YorumID`, `KitapID`, `KullaniciID`, `Puan`, `YorumMetni`, `YorumTarihi`, `OnayDurumu`) VALUES
(1, 1, 2, 5, 'Harika bir klasik, kesinlikle okunmalı! Çok akıcıydı.', '2025-05-08 11:24:31', 'Onaylandı'),
(2, 2, 1, 4, 'Asimov her zamanki gibi düşündürüyor. Robotların geleceği üzerine ilginç bir bakış açısı. Tavsiye ederim.', '2025-05-09 09:24:31', 'Onaylandı'),
(3, 5, 3, 5, 'Çocuklar için çok güzel bir kitap, yetişkinler de keyifle okuyabilir. Resimleri de harika.', '2025-05-06 11:24:31', 'Beklemede'),
(4, 1, 4, 3, 'Beklediğim kadar etkileyici değildi ama yine de güzeldi. Biraz yavaş ilerliyor.', '2025-05-04 11:24:31', 'Onaylandı'),
(5, 8, 5, 5, 'Vakıf serisi efsane! Bu kitap da serinin hakkını veriyor.', '2025-05-03 11:24:31', 'Onaylandı'),
(6, 11, 6, 4, 'Dune evreni çok zengin ve detaylı. Başlarda biraz karmaşık gelebilir ama okudukça açılıyor.', '2025-05-02 11:24:31', 'Reddedildi'),
(7, 13, 7, 5, 'Şeker Portakalı her yaşta okunması gereken, duygusal bir kitap.', '2025-05-01 11:24:31', 'Onaylandı'),
(8, 15, 8, 3, 'Saatleri Ayarlama Enstitüsü\'nü anlamak biraz zaman alıyor ama kesinlikle değerli bir eser.', '2025-04-30 11:24:31', 'Onaylandı'),
(9, 17, 9, 4, 'Zengin Baba Yoksul Baba, finansal okuryazarlık için iyi bir başlangıç kitabı.', '2025-04-29 11:24:31', 'Beklemede'),
(10, 21, 10, 5, 'Kürk Mantolu Madonna, Sabahattin Ali\'nin en dokunaklı romanlarından biri.', '2025-04-28 11:24:31', 'Onaylandı'),
(11, 3, 12, 4, 'Nutuk, her Türk gencinin okuması gereken bir eser. Tarihimizi birinci ağızdan öğrenmek önemli.', '2025-04-27 11:24:31', 'Onaylandı'),
(12, 4, 13, 5, 'Platon\'un Devlet\'i, siyaset felsefesinin temel taşlarından. Zaman zaman zorlayıcı olsa da ufuk açıcı.', '2025-04-26 11:24:31', 'Onaylandı'),
(13, 9, 14, 4, 'Beyaz Gemi, insan doğası ve masumiyet üzerine etkileyici bir novella.', '2025-04-25 11:24:31', 'Beklemede'),
(14, 10, 15, 5, 'Halil İnalcık hocadan Osmanlı tarihi okumak bir ayrıcalık.', '2025-04-24 11:24:31', 'Onaylandı'),
(15, 12, 1, 3, 'Sokrates\'in Savunması, felsefeye ilgi duyan herkesin okuması gereken kısa ama öz bir metin.', '2025-04-23 11:24:31', 'Onaylandı'),
(16, 14, 2, 5, 'İlber Ortaylı\'nın akıcı üslubuyla hayat dersleri. Keyifle okudum.', '2025-04-22 11:24:31', 'Onaylandı'),
(17, 16, 3, 4, 'Steve Jobs\'un hayatı gerçekten ilham verici. Kitap oldukça detaylı.', '2025-04-21 11:24:31', 'Reddedildi'),
(18, 18, 4, 5, 'İyi Hissetmek, bilişsel davranışçı terapiyi anlamak için harika bir kaynak.', '2025-04-20 11:24:31', 'Onaylandı'),
(19, 24, 5, 5, 'Nazım Hikmet\'in şiirleri her zaman taptaze ve güçlü.', '2025-04-19 11:24:31', 'Beklemede'),
(20, 25, 6, 4, 'Küçük Kara Balık, özgürlük ve cesaret üzerine zamansız bir hikaye.', '2025-04-18 11:24:31', 'Onaylandı'),
(21, 27, 7, 5, 'Cemal Süreya\'nın o eşsiz imgeleri... Şiir sevenler kaçırmasın.', '2025-04-17 11:24:31', 'Onaylandı'),
(22, 28, 8, 4, 'Bir Bilim Adamının Romanı, Oğuz Atay\'ın farklı bir yönünü gösteriyor. Çok başarılı.', '2025-04-16 11:24:31', 'Onaylandı'),
(23, 29, 9, 3, 'Para Psikolojisi, bazı ilginç noktalar sunsa da yer yer tekrar ediyor gibi geldi.', '2025-04-15 11:24:31', 'Onaylandı'),
(24, 30, 10, 5, 'Beyin: Senin Hikayen, karmaşık bir konuyu herkesin anlayabileceği şekilde anlatıyor.', '2025-04-14 11:24:31', 'Beklemede'),
(25, 6, 11, 2, 'Tutunamayanlar\'ı bitirmekte zorlandım. Benim için biraz ağırdı.', '2025-04-13 11:24:31', 'Onaylandı'),
(26, 23, 12, 5, 'Otostopçunun Galaksi Rehberi absürt mizahın zirvesi! Çok güldüm.', '2025-04-12 11:24:31', 'Onaylandı'),
(27, 22, 13, 4, 'Türklerin Tarihi, İlber Hoca\'nın engin bilgisiyle aydınlatıcı bir eser.', '2025-04-11 11:24:31', 'Onaylandı'),
(28, 20, 14, 5, 'Orhan Pamuk\'un sanat ve resim üzerine denemeleri çok keyifliydi.', '2025-04-10 11:24:31', 'Beklemede'),
(29, 19, 15, 3, 'Aziz Nesin\'in mizahı her zaman düşündürücü. Ancak bazı öyküler diğerlerinden daha zayıftı.', '2025-04-09 11:24:31', 'Onaylandı'),
(30, 2, 16, 4, 'Ben, Robot tekrar okudum, hala çok etkileyici.', '2025-04-08 11:24:31', 'Onaylandı'),
(31, 32, 31, 3, 'ürün gayet güzeldi satıcıdan çok memnun kaldım kitap zaten mükemmel ötesi', '2025-05-09 12:28:37', 'Beklemede');

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `adresler`
--
ALTER TABLE `adresler`
  ADD PRIMARY KEY (`AdresID`),
  ADD KEY `KullaniciID` (`KullaniciID`);

--
-- Tablo için indeksler `kategoriler`
--
ALTER TABLE `kategoriler`
  ADD PRIMARY KEY (`KategoriID`),
  ADD UNIQUE KEY `KategoriAdi` (`KategoriAdi`);

--
-- Tablo için indeksler `kitapeklemelog`
--
ALTER TABLE `kitapeklemelog`
  ADD PRIMARY KEY (`LogID`);

--
-- Tablo için indeksler `kitaplar`
--
ALTER TABLE `kitaplar`
  ADD PRIMARY KEY (`KitapID`),
  ADD KEY `SatıcıKullaniciID` (`SatıcıKullaniciID`),
  ADD KEY `KategoriID` (`KategoriID`),
  ADD KEY `idx_kitap_adi` (`KitapAdi`),
  ADD KEY `idx_yazar_adi` (`YazarAdi`);

--
-- Tablo için indeksler `kullanicilar`
--
ALTER TABLE `kullanicilar`
  ADD PRIMARY KEY (`KullaniciID`),
  ADD UNIQUE KEY `Eposta` (`Eposta`);

--
-- Tablo için indeksler `siparisdetaylari`
--
ALTER TABLE `siparisdetaylari`
  ADD PRIMARY KEY (`SiparisDetayID`),
  ADD UNIQUE KEY `uk_siparis_kitap` (`SiparisID`,`KitapID`),
  ADD KEY `KitapID` (`KitapID`);

--
-- Tablo için indeksler `siparisler`
--
ALTER TABLE `siparisler`
  ADD PRIMARY KEY (`SiparisID`),
  ADD KEY `AliciKullaniciID` (`AliciKullaniciID`),
  ADD KEY `TeslimatAdresID` (`TeslimatAdresID`);

--
-- Tablo için indeksler `yorumlar`
--
ALTER TABLE `yorumlar`
  ADD PRIMARY KEY (`YorumID`),
  ADD KEY `KitapID` (`KitapID`),
  ADD KEY `KullaniciID` (`KullaniciID`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `adresler`
--
ALTER TABLE `adresler`
  MODIFY `AdresID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- Tablo için AUTO_INCREMENT değeri `kategoriler`
--
ALTER TABLE `kategoriler`
  MODIFY `KategoriID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Tablo için AUTO_INCREMENT değeri `kitapeklemelog`
--
ALTER TABLE `kitapeklemelog`
  MODIFY `LogID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- Tablo için AUTO_INCREMENT değeri `kitaplar`
--
ALTER TABLE `kitaplar`
  MODIFY `KitapID` int NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `kullanicilar`
--
ALTER TABLE `kullanicilar`
  MODIFY `KullaniciID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- Tablo için AUTO_INCREMENT değeri `siparisdetaylari`
--
ALTER TABLE `siparisdetaylari`
  MODIFY `SiparisDetayID` int NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `siparisler`
--
ALTER TABLE `siparisler`
  MODIFY `SiparisID` int NOT NULL AUTO_INCREMENT;

--
-- Tablo için AUTO_INCREMENT değeri `yorumlar`
--
ALTER TABLE `yorumlar`
  MODIFY `YorumID` int NOT NULL AUTO_INCREMENT;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `adresler`
--
ALTER TABLE `adresler`
  ADD CONSTRAINT `adresler_ibfk_1` FOREIGN KEY (`KullaniciID`) REFERENCES `kullanicilar` (`KullaniciID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `kitaplar`
--
ALTER TABLE `kitaplar`
  ADD CONSTRAINT `kitaplar_ibfk_1` FOREIGN KEY (`SatıcıKullaniciID`) REFERENCES `kullanicilar` (`KullaniciID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `kitaplar_ibfk_2` FOREIGN KEY (`KategoriID`) REFERENCES `kategoriler` (`KategoriID`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `siparisdetaylari`
--
ALTER TABLE `siparisdetaylari`
  ADD CONSTRAINT `siparisdetaylari_ibfk_1` FOREIGN KEY (`SiparisID`) REFERENCES `siparisler` (`SiparisID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `siparisdetaylari_ibfk_2` FOREIGN KEY (`KitapID`) REFERENCES `kitaplar` (`KitapID`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `siparisler`
--
ALTER TABLE `siparisler`
  ADD CONSTRAINT `siparisler_ibfk_1` FOREIGN KEY (`AliciKullaniciID`) REFERENCES `kullanicilar` (`KullaniciID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `siparisler_ibfk_2` FOREIGN KEY (`TeslimatAdresID`) REFERENCES `adresler` (`AdresID`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Tablo kısıtlamaları `yorumlar`
--
ALTER TABLE `yorumlar`
  ADD CONSTRAINT `yorumlar_ibfk_1` FOREIGN KEY (`KitapID`) REFERENCES `kitaplar` (`KitapID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `yorumlar_ibfk_2` FOREIGN KEY (`KullaniciID`) REFERENCES `kullanicilar` (`KullaniciID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
