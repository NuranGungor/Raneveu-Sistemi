<?php
require_once 'config.php';

$hata = '';
$basari = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['giris'])) {
        // Giriş İşlemi
        $telno = guvenliVeri($_POST['telno']);
        $sifre = $_POST['sifre'];
        
        $sorgu = $conn->prepare("SELECT kullaniciID, ad, soyad, sifre FROM kullanicilar WHERE telno = ?");
        $sorgu->bind_param("s", $telno);
        $sorgu->execute();
        $sonuc = $sorgu->get_result();
        
        if ($sonuc->num_rows == 1) {
            $kullanici = $sonuc->fetch_assoc();
            if (sifreDogrula($sifre, $kullanici['sifre'])) {
                $_SESSION['kullanici_id'] = $kullanici['kullaniciID'];
                $_SESSION['kullanici_ad'] = $kullanici['ad'] . ' ' . $kullanici['soyad'];
                yonlendir('kullanici_panel.php');
            } else {
                $hata = 'Telefon numarası veya şifre hatalı!';
            }
        } else {
            $hata = 'Telefon numarası veya şifre hatalı!';
        }
        $sorgu->close();
        
    } elseif (isset($_POST['kayit'])) {
        // Kayıt İşlemi
        $ad = guvenliVeri($_POST['ad']);
        $soyad = guvenliVeri($_POST['soyad']);
        $telno = guvenliVeri($_POST['telno']);
        $sifre = sifreHashle($_POST['sifre']);
        
        // Telefon numarası kontrolü
        $kontrol = $conn->prepare("SELECT kullaniciID FROM kullanicilar WHERE telno = ?");
        $kontrol->bind_param("s", $telno);
        $kontrol->execute();
        
        if ($kontrol->get_result()->num_rows > 0) {
            $hata = 'Bu telefon numarası zaten kayıtlı!';
        } else {
            $sorgu = $conn->prepare("INSERT INTO kullanicilar (ad, soyad, telno, sifre) VALUES (?, ?, ?, ?)");
            $sorgu->bind_param("ssss", $ad, $soyad, $telno, $sifre);
            
            if ($sorgu->execute()) {
                $basari = 'Kayıt başarılı! Şimdi giriş yapabilirsiniz.';
            } else {
                $hata = 'Kayıt sırasında bir hata oluştu!';
            }
            $sorgu->close();
        }
        $kontrol->close();
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Girişi - Randevu Platformu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <a href="#main-content" class="skip-link">Ana içeriğe geç</a>
    
    <header>
        <nav aria-label="Ana navigasyon">
            <div class="container">
                <h1>Randevu Platformu</h1>
                <ul class="nav-menu">
                    <li><a href="index.php">Ana Sayfa</a></li>
                    <li><a href="kullanici_giris.php" aria-current="page">Müşteri Girişi</a></li>
                    <li><a href="isletme_giris.php">İşletme Girişi</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main id="main-content" class="container">
        <div class="form-container">
            <h2>Müşteri Girişi</h2>
            
            <?php 
            if ($hata) echo hataGoster($hata);
            if ($basari) echo basariGoster($basari);
            ?>
            
            <form method="POST" action="" novalidate>
                <fieldset>
                    <legend>Giriş Bilgileri</legend>
                    
                    <div class="form-group">
                        <label for="telno">Telefon Numarası *</label>
                        <input type="tel" id="telno" name="telno" required 
                               placeholder="0532 123 4567"
                               aria-required="true"
                               aria-describedby="telno-help">
                        <small id="telno-help">Örnek: 0532 123 4567</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="sifre">Şifre *</label>
                        <input type="password" id="sifre" name="sifre" required
                               aria-required="true">
                    </div>
                    
                    <button type="submit" name="giris" class="btn btn-primary">Giriş Yap</button>
                </fieldset>
            </form>
            
            <hr style="margin: 2rem 0;">
            
            <h2>Yeni Kayıt</h2>
            <form method="POST" action="" novalidate>
                <fieldset>
                    <legend>Kayıt Bilgileri</legend>
                    
                    <div class="form-group">
                        <label for="kayit_ad">Ad *</label>
                        <input type="text" id="kayit_ad" name="ad" required
                               aria-required="true">
                    </div>
                    
                    <div class="form-group">
                        <label for="kayit_soyad">Soyad *</label>
                        <input type="text" id="kayit_soyad" name="soyad" required
                               aria-required="true">
                    </div>
                    
                    <div class="form-group">
                        <label for="kayit_telno">Telefon Numarası *</label>
                        <input type="tel" id="kayit_telno" name="telno" required
                               placeholder="0532 123 4567"
                               aria-required="true"
                               aria-describedby="kayit-telno-help">
                        <small id="kayit-telno-help">Örnek: 0532 123 4567</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="kayit_sifre">Şifre *</label>
                        <input type="password" id="kayit_sifre" name="sifre" required
                               minlength="6"
                               aria-required="true"
                               aria-describedby="sifre-help">
                        <small id="sifre-help">En az 6 karakter olmalıdır</small>
                    </div>
                    
                    <button type="submit" name="kayit" class="btn btn-success">Kayıt Ol</button>
                </fieldset>
            </form>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Randevu Platformu. Tüm hakları saklıdır.</p>
        </div>
    </footer>
</body>
</html>