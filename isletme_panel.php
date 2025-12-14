<?php
require_once 'config.php';

if (!isletmeGirisKontrol()) {
    yonlendir('isletme_giris.php');
}

$hata = '';
$basari = '';
$isletmeID = $_SESSION['isletme_id'];

// Çalışan Ekleme
if (isset($_POST['calisan_ekle'])) {
    $adsoyad = guvenliVeri($_POST['adsoyad']);
    
    $ekle = $conn->prepare("INSERT INTO calisanlar (isletmeID, adsoyad, randevualinabilirmi) VALUES (?, ?, 1)");
    $ekle->bind_param("is", $isletmeID, $adsoyad);
    if ($ekle->execute()) {
        $basari = 'Çalışan başarıyla eklendi!';
    }
    $ekle->close();
}

// Müsaitlik Ekleme
if (isset($_POST['musaitlik_ekle'])) {
    $calisanID = (int)$_POST['calisan'];
    $tarih = guvenliVeri($_POST['tarih']);
    $baslangic = guvenliVeri($_POST['baslangic']);
    $bitis = guvenliVeri($_POST['bitis']);
    
    $ekle = $conn->prepare("INSERT INTO musaitlik (calisanID, tarih, baslangicSaati, bitisSaati) VALUES (?, ?, ?, ?)");
    $ekle->bind_param("isss", $calisanID, $tarih, $baslangic, $bitis);
    if ($ekle->execute()) {
        $basari = 'Müsaitlik başarıyla eklendi!';
    } else {
        $hata = 'Bu tarih ve saat için zaten kayıt var!';
    }
    $ekle->close();
}

// Randevu Durumu Güncelleme
if (isset($_GET['durum']) && isset($_GET['randevu'])) {
    $durum = guvenliVeri($_GET['durum']);
    $randevuID = (int)$_GET['randevu'];
    
    if (in_array($durum, ['onaylandi', 'iptal', 'tamamlandi'])) {
        $guncelle = $conn->prepare("UPDATE randevular SET durum = ? WHERE randevuID = ? AND isletmeID = ?");
        $guncelle->bind_param("sii", $durum, $randevuID, $isletmeID);
        if ($guncelle->execute()) {
            $basari = 'Randevu durumu güncellendi!';
        }
        $guncelle->close();
    }
}

// Çalışanları Getir
$calisanlar = $conn->query("SELECT * FROM calisanlar WHERE isletmeID = $isletmeID ORDER BY adsoyad");

// Randevuları Getir
$randevular = $conn->query("
    SELECT r.*, k.ad, k.soyad, k.telno, c.adsoyad as calisan
    FROM randevular r
    JOIN kullanicilar k ON r.kullaniciID = k.kullaniciID
    JOIN calisanlar c ON r.calisanID = c.calisanID
    WHERE r.isletmeID = $isletmeID
    ORDER BY r.randevuBaslangic DESC
");

// Müsaitleri Getir
$musaitlikler = $conn->query("
    SELECT m.*, c.adsoyad 
    FROM musaitlik m
    JOIN calisanlar c ON m.calisanID = c.calisanID
    WHERE c.isletmeID = $isletmeID
    ORDER BY m.tarih DESC, m.baslangicSaati
    LIMIT 50
");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İşletme Paneli - Randevu Platformu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <a href="#main-content" class="skip-link">Ana içeriğe geç</a>
    
    <header>
        <nav aria-label="Ana navigasyon">
            <div class="container">
                <h1>Randevu Platformu</h1>
                <ul class="nav-menu">
                    <li><a href="isletme_panel.php" aria-current="page">Panel</a></li>
                    <li><span style="color: white;"><?php echo htmlspecialchars($_SESSION['isletme_ad']); ?></span></li>
                    <li><a href="cikis.php">Çıkış</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main id="main-content" class="container">
        <h2>İşletme Yönetim Paneli</h2>
        
        <?php 
        if ($hata) echo hataGoster($hata);
        if ($basari) echo basariGoster($basari);
        ?>
        
        <!-- Çalışan Ekleme -->
        <section class="form-container">
            <h3>Yeni Çalışan Ekle</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="adsoyad">Çalışan Ad Soyad:</label>
                    <input type="text" id="adsoyad" name="adsoyad" required>
                </div>
                <button type="submit" name="calisan_ekle" class="btn btn-success">Çalışan Ekle</button>
            </form>
        </section>

        <!-- Müsaitlik Ekleme -->
        <section class="form-container">
            <h3>Müsaitlik Ekle</h3>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="calisan">Çalışan:</label>
                    <select id="calisan" name="calisan" required>
                        <option value="">Seçiniz...</option>
                        <?php 
                        $calisanlar->data_seek(0);
                        while ($c = $calisanlar->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $c['calisanID']; ?>">
                                <?php echo htmlspecialchars($c['adsoyad']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="tarih">Tarih:</label>
                    <input type="date" id="tarih" name="tarih" required 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="baslangic">Başlangıç Saati:</label>
                    <input type="time" id="baslangic" name="baslangic" required>
                </div>
                
                <div class="form-group">
                    <label for="bitis">Bitiş Saati:</label>
                    <input type="time" id="bitis" name="bitis" required>
                </div>
                
                <button type="submit" name="musaitlik_ekle" class="btn btn-success">Müsaitlik Ekle</button>
            </form>
        </section>

        <!-- Mevcut Müsaitlikler -->
        <section>
            <h3>Tanımlanmış Müsaitlikler (Son 50)</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Çalışan</th>
                            <th scope="col">Tarih</th>
                            <th scope="col">Başlangıç</th>
                            <th scope="col">Bitiş</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($musaitlikler->num_rows > 0): ?>
                            <?php while ($m = $musaitlikler->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($m['adsoyad']); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($m['tarih'])); ?></td>
                                    <td><?php echo substr($m['baslangicSaati'], 0, 5); ?></td>
                                    <td><?php echo substr($m['bitisSaati'], 0, 5); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4">Henüz müsaitlik eklenmemiş.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Randevular -->
        <section>
            <h3>Randevular</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Müşteri</th>
                            <th scope="col">Telefon</th>
                            <th scope="col">Çalışan</th>
                            <th scope="col">Tarih</th>
                            <th scope="col">Saat</th>
                            <th scope="col">Durum</th>
                            <th scope="col">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($randevular->num_rows > 0): ?>
                            <?php while ($r = $randevular->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['ad'] . ' ' . $r['soyad']); ?></td>
                                    <td><?php echo htmlspecialchars($r['telno']); ?></td>
                                    <td><?php echo htmlspecialchars($r['calisan']); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($r['randevuBaslangic'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($r['randevuBaslangic'])); ?></td>
                                    <td><?php echo ucfirst($r['durum']); ?></td>
                                    <td>
                                        <?php if ($r['durum'] == 'beklemede'): ?>
                                            <a href="?durum=onaylandi&randevu=<?php echo $r['randevuID']; ?>" 
                                               class="btn btn-success">Onayla</a>
                                            <a href="?durum=iptal&randevu=<?php echo $r['randevuID']; ?>" 
                                               class="btn btn-danger"
                                               onclick="return confirm('İptal etmek istediğinizden emin misiniz?');">İptal</a>
                                        <?php elseif ($r['durum'] == 'onaylandi'): ?>
                                            <a href="?durum=tamamlandi&randevu=<?php echo $r['randevuID']; ?>" 
                                               class="btn btn-primary">Tamamlandı</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7">Henüz randevu bulunmamaktadır.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Randevu Platformu. Tüm hakları saklıdır.</p>
        </div>
    </footer>
</body>
</html>