<?php
require_once 'config.php';

if (!girisKontrol()) {
    yonlendir('kullanici_giris.php');
}

$hata = '';
$basari = '';

// Filtreleme
$filtre_tip = isset($_GET['tip']) ? guvenliVeri($_GET['tip']) : '';

// Randevu İptali
if (isset($_GET['iptal']) && is_numeric($_GET['iptal'])) {
    $randevuID = (int)$_GET['iptal'];
    $kullaniciID = $_SESSION['kullanici_id'];
    
    $iptal = $conn->prepare("UPDATE randevular SET durum = 'iptal' WHERE randevuID = ? AND kullaniciID = ?");
    $iptal->bind_param("ii", $randevuID, $kullaniciID);
    if ($iptal->execute()) {
        $basari = 'Randevu iptal edildi.';
    }
    $iptal->close();
}

// İşletmeleri Getir
$where = $filtre_tip ? "WHERE i.isletmetipi = '$filtre_tip'" : "";
$isletmeler_sorgu = "SELECT i.*, COUNT(c.calisanID) as calisan_sayisi 
                     FROM isletmeler i 
                     LEFT JOIN calisanlar c ON i.isletmeID = c.isletmeID AND c.randevualinabilirmi = 1
                     $where
                     GROUP BY i.isletmeID";
$isletmeler = $conn->query($isletmeler_sorgu);

// Kullanıcının Randevuları
$kullaniciID = $_SESSION['kullanici_id'];
$randevular_sorgu = $conn->prepare("
    SELECT r.*, i.isletmead, i.isletmetipi, c.adsoyad as calisan
    FROM randevular r
    JOIN isletmeler i ON r.isletmeID = i.isletmeID
    JOIN calisanlar c ON r.calisanID = c.calisanID
    WHERE r.kullaniciID = ?
    ORDER BY r.randevuBaslangic DESC
");
$randevular_sorgu->bind_param("i", $kullaniciID);
$randevular_sorgu->execute();
$randevular = $randevular_sorgu->get_result();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Paneli - Randevu Platformu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <a href="#main-content" class="skip-link">Ana içeriğe geç</a>
    
    <header>
        <nav aria-label="Ana navigasyon">
            <div class="container">
                <h1>Randevu Platformu</h1>
                <ul class="nav-menu">
                    <li><a href="kullanici_panel.php" aria-current="page">Panel</a></li>
                    <li><span style="color: white;">Hoş geldin, <?php echo htmlspecialchars($_SESSION['kullanici_ad']); ?></span></li>
                    <li><a href="cikis.php">Çıkış</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main id="main-content" class="container">
        <h2>Müşteri Paneli</h2>
        
        <?php 
        if ($hata) echo hataGoster($hata);
        if ($basari) echo basariGoster($basari);
        ?>
        
        <!-- Filtreleme -->
        <section>
            <h3>İşletme Ara</h3>
            <form method="GET" action="" class="form-group">
                <label for="tip">İşletme Tipi:</label>
                <select id="tip" name="tip" onchange="this.form.submit()">
                    <option value="">Tüm İşletmeler</option>
                    <?php foreach ($isletme_tipleri as $tip): ?>
                        <option value="<?php echo $tip; ?>" <?php echo $filtre_tip == $tip ? 'selected' : ''; ?>>
                            <?php echo $tip; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </section>

        <!-- İşletmeler Listesi -->
        <section>
            <h3>Mevcut İşletmeler</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">İşletme Adı</th>
                            <th scope="col">Tip</th>
                            <th scope="col">Adres</th>
                            <th scope="col">Telefon</th>
                            <th scope="col">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($isletmeler->num_rows > 0): ?>
                            <?php while ($isletme = $isletmeler->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($isletme['isletmead']); ?></td>
                                    <td><?php echo htmlspecialchars($isletme['isletmetipi']); ?></td>
                                    <td><?php echo htmlspecialchars($isletme['adres']); ?></td>
                                    <td><?php echo htmlspecialchars($isletme['isletmetelno']); ?></td>
                                    <td>
                                        <a href="randevu_al.php?isletme=<?php echo $isletme['isletmeID']; ?>" 
                                           class="btn btn-primary">Randevu Al</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">Henüz kayıtlı işletme bulunmamaktadır.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Randevularım -->
        <section style="margin-top: 3rem;">
            <h3>Randevularım</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">İşletme</th>
                            <th scope="col">Çalışan</th>
                            <th scope="col">Tarih</th>
                            <th scope="col">Saat</th>
                            <th scope="col">Durum</th>
                            <th scope="col">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($randevular->num_rows > 0): ?>
                            <?php while ($randevu = $randevular->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($randevu['isletmead']); ?></td>
                                    <td><?php echo htmlspecialchars($randevu['calisan']); ?></td>
                                    <td><?php echo date('d.m.Y', strtotime($randevu['randevuBaslangic'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($randevu['randevuBaslangic'])); ?> - 
                                        <?php echo date('H:i', strtotime($randevu['randevuBitis'])); ?></td>
                                    <td>
                                        <?php
                                        $durum_class = [
                                            'beklemede' => 'alert-info',
                                            'onaylandi' => 'alert-success',
                                            'iptal' => 'alert-danger',
                                            'tamamlandi' => 'alert-success'
                                        ];
                                        $class = $durum_class[$randevu['durum']] ?? 'alert-info';
                                        echo "<span class='alert $class' style='display:inline-block;padding:0.25rem 0.5rem;'>";
                                        echo ucfirst($randevu['durum']);
                                        echo "</span>";
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($randevu['durum'] == 'beklemede' || $randevu['durum'] == 'onaylandi'): ?>
                                            <a href="?iptal=<?php echo $randevu['randevuID']; ?>" 
                                               class="btn btn-danger"
                                               onclick="return confirm('Randevuyu iptal etmek istediğinizden emin misiniz?');">
                                                İptal Et
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">Henüz randevunuz bulunmamaktadır.</td>
                            </tr>
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