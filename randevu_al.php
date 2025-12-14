<?php
require_once 'config.php';

if (!girisKontrol()) {
    yonlendir('kullanici_giris.php');
}

$hata = '';
$basari = '';

// İşletme ID kontrolü
if (!isset($_GET['isletme']) || !is_numeric($_GET['isletme'])) {
    yonlendir('kullanici_panel.php');
}

$isletmeID = (int)$_GET['isletme'];

// İşletme bilgilerini getir
$isletme_sorgu = $conn->prepare("SELECT * FROM isletmeler WHERE isletmeID = ?");
$isletme_sorgu->bind_param("i", $isletmeID);
$isletme_sorgu->execute();
$isletme = $isletme_sorgu->get_result()->fetch_assoc();

if (!$isletme) {
    yonlendir('kullanici_panel.php');
}

// Randevu oluşturma
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['randevu_olustur'])) {
    $calisanID = (int)$_POST['calisan'];
    $tarih = guvenliVeri($_POST['tarih']);
    $saat = guvenliVeri($_POST['saat']);
    $kullaniciID = $_SESSION['kullanici_id'];
    
    $baslangic = $tarih . ' ' . $saat;
    $bitis = date('Y-m-d H:i:s', strtotime($baslangic . ' +' . RANDEVU_SURESI . ' minutes'));
    
    // Müsaitlik kontrolü
    $musait_kontrol = $conn->prepare("
        SELECT m.musaitlikID 
        FROM musaitlik m
        WHERE m.calisanID = ? 
        AND m.tarih = ? 
        AND ? BETWEEN m.baslangicSaati AND m.bitisSaati
    ");
    $musait_kontrol->bind_param("iss", $calisanID, $tarih, $saat);
    $musait_kontrol->execute();
    
    if ($musait_kontrol->get_result()->num_rows == 0) {
        $hata = 'Seçilen saat için müsaitlik bulunamadı!';
    } else {
        // Çakışma kontrolü
        $cakisma = $conn->prepare("
            SELECT randevuID FROM randevular 
            WHERE calisanID = ? 
            AND durum IN ('beklemede', 'onaylandi')
            AND (
                (randevuBaslangic <= ? AND randevuBitis > ?) OR
                (randevuBaslangic < ? AND randevuBitis >= ?)
            )
        ");
        $cakisma->bind_param("issss", $calisanID, $baslangic, $baslangic, $bitis, $bitis);
        $cakisma->execute();
        
        if ($cakisma->get_result()->num_rows > 0) {
            $hata = 'Bu saat için zaten randevu alınmış!';
        } else {
            // Randevu oluştur
            $randevu = $conn->prepare("
                INSERT INTO randevular (isletmeID, kullaniciID, calisanID, randevuBaslangic, randevuBitis, durum)
                VALUES (?, ?, ?, ?, ?, 'beklemede')
            ");
            $randevu->bind_param("iiiss", $isletmeID, $kullaniciID, $calisanID, $baslangic, $bitis);
            
            if ($randevu->execute()) {
                $basari = 'Randevunuz başarıyla oluşturuldu! İşletme tarafından onaylanacaktır.';
            } else {
                $hata = 'Randevu oluşturulurken bir hata oluştu!';
            }
            $randevu->close();
        }
        $cakisma->close();
    }
    $musait_kontrol->close();
}

// Çalışanları getir
$calisanlar = $conn->query("
    SELECT * FROM calisanlar 
    WHERE isletmeID = $isletmeID AND randevualinabilirmi = 1
");

// Seçilen çalışan için müsait saatleri getir
$musait_saatler = [];
if (isset($_GET['calisan']) && is_numeric($_GET['calisan'])) {
    $secili_calisan = (int)$_GET['calisan'];
    $bugun = date('Y-m-d');
    
    $musaitlik_sorgu = $conn->prepare("
        SELECT tarih, baslangicSaati, bitisSaati 
        FROM musaitlik 
        WHERE calisanID = ? AND tarih >= ?
        ORDER BY tarih, baslangicSaati
    ");
    $musaitlik_sorgu->bind_param("is", $secili_calisan, $bugun);
    $musaitlik_sorgu->execute();
    $musaitlik = $musaitlik_sorgu->get_result();
    
    while ($m = $musaitlik->fetch_assoc()) {
        $tarih = $m['tarih'];
        $baslangic = strtotime($m['baslangicSaati']);
        $bitis = strtotime($m['bitisSaati']);
        
        if (!isset($musait_saatler[$tarih])) {
            $musait_saatler[$tarih] = [];
        }
        
        // Her 60 dakikalık slot oluştur
        for ($saat = $baslangic; $saat < $bitis; $saat += RANDEVU_SURESI * 60) {
            $saat_str = date('H:i:00', $saat);
            
            // Bu saatte randevu var mı kontrol et
            $dolu_mu = $conn->query("
                SELECT randevuID FROM randevular 
                WHERE calisanID = $secili_calisan 
                AND durum IN ('beklemede', 'onaylandi')
                AND DATE(randevuBaslangic) = '$tarih'
                AND TIME(randevuBaslangic) = '$saat_str'
            ")->num_rows > 0;
            
            $musait_saatler[$tarih][] = [
                'saat' => $saat_str,
                'dolu' => $dolu_mu
            ];
        }
    }
    $musaitlik_sorgu->close();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu Al - <?php echo htmlspecialchars($isletme['isletmead']); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <a href="#main-content" class="skip-link">Ana içeriğe geç</a>
    
    <header>
        <nav aria-label="Ana navigasyon">
            <div class="container">
                <h1>Randevu Platformu</h1>
                <ul class="nav-menu">
                    <li><a href="kullanici_panel.php">Panel</a></li>
                    <li><a href="cikis.php">Çıkış</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main id="main-content" class="container">
        <h2><?php echo htmlspecialchars($isletme['isletmead']); ?> - Randevu Al</h2>
        
        <div class="info">
            <p><strong>İşletme Tipi:</strong> <?php echo htmlspecialchars($isletme['isletmetipi']); ?></p>
            <p><strong>Adres:</strong> <?php echo htmlspecialchars($isletme['adres']); ?></p>
            <p><strong>Telefon:</strong> <?php echo htmlspecialchars($isletme['isletmetelno']); ?></p>
        </div>
        
        <?php 
        if ($hata) echo hataGoster($hata);
        if ($basari) echo basariGoster($basari);
        ?>
        
        <!-- Çalışan Seçimi -->
        <section>
            <h3>1. Çalışan Seçin</h3>
            <form method="GET" action="">
                <input type="hidden" name="isletme" value="<?php echo $isletmeID; ?>">
                <div class="form-group">
                    <label for="calisan">Çalışan:</label>
                    <select id="calisan" name="calisan" onchange="this.form.submit()" required>
                        <option value="">Seçiniz...</option>
                        <?php while ($calisan = $calisanlar->fetch_assoc()): ?>
                            <option value="<?php echo $calisan['calisanID']; ?>"
                                    <?php echo (isset($secili_calisan) && $secili_calisan == $calisan['calisanID']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($calisan['adsoyad']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
        </section>

        <?php if (!empty($musait_saatler)): ?>
        <!-- Müsait Saatler -->
        <section>
            <h3>2. Tarih ve Saat Seçin</h3>
            <?php foreach ($musait_saatler as $tarih => $saatler): ?>
                <div style="margin-bottom: 2rem;">
                    <h4><?php echo date('d.m.Y - l', strtotime($tarih)); ?></h4>
                    <div class="randevu-grid">
                        <?php foreach ($saatler as $slot): ?>
                            <?php if ($slot['dolu']): ?>
                                <div class="randevu-slot dolu" aria-label="Dolu">
                                    <?php echo substr($slot['saat'], 0, 5); ?>
                                    <br><small>(Dolu)</small>
                                </div>
                            <?php else: ?>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="calisan" value="<?php echo $secili_calisan; ?>">
                                    <input type="hidden" name="tarih" value="<?php echo $tarih; ?>">
                                    <input type="hidden" name="saat" value="<?php echo $slot['saat']; ?>">
                                    <button type="submit" name="randevu_olustur" 
                                            class="randevu-slot"
                                            aria-label="<?php echo substr($slot['saat'], 0, 5); ?> saatine randevu al">
                                        <?php echo substr($slot['saat'], 0, 5); ?>
                                        <br><small>(Müsait)</small>
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
        <?php elseif (isset($secili_calisan)): ?>
            <div class="alert alert-info">Bu çalışan için henüz müsait randevu saati bulunmamaktadır.</div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Randevu Platformu. Tüm hakları saklıdır.</p>
        </div>
    </footer>
</body>
</html>