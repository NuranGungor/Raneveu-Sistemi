<?php
require_once 'config.php';

$hata = '';
$basari = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['giris'])) {
        // Giriş İşlemi
        $email = guvenliVeri($_POST['email']);
        $sifre = $_POST['sifre'];
        
        $sorgu = $conn->prepare("SELECT isletmeID, isletmead, isletmesifre FROM isletmeler WHERE email = ?");
        $sorgu->bind_param("s", $email);
        $sorgu->execute();
        $sonuc = $sorgu->get_result();
        
        if ($sonuc->num_rows == 1) {
            $isletme = $sonuc->fetch_assoc();
            if (sifreDogrula($sifre, $isletme['isletmesifre'])) {
                $_SESSION['isletme_id'] = $isletme['isletmeID'];
                $_SESSION['isletme_ad'] = $isletme['isletmead'];
                yonlendir('isletme_panel.php');
            } else {
                $hata = 'E-posta veya şifre hatalı!';
            }
        } else {
            $hata = 'E-posta veya şifre hatalı!';
        }
        $sorgu->close();
        
    } elseif (isset($_POST['kayit'])) {
        // Kayıt İşlemi
        $isletmead = guvenliVeri($_POST['isletmead']);
        $isletmetipi = guvenliVeri($_POST['isletmetipi']);
        $adres = guvenliVeri($_POST['adres']);
        $isletmetelno = guvenliVeri($_POST['isletmetelno']);
        $email = guvenliVeri($_POST['email']);
        $sifre = sifreHashle($_POST['sifre']);
        
        // E-posta kontrolü
        $kontrol = $conn->prepare("SELECT isletmeID FROM isletmeler WHERE email = ?");
        $kontrol->bind_param("s", $email);
        $kontrol->execute();
        
        if ($kontrol->get_result()->num_rows > 0) {
            $hata = 'Bu e-posta adresi zaten kayıtlı!';
        } else {
            $sorgu = $conn->prepare("INSERT INTO isletmeler (isletmead, isletmetipi, adres, isletmetelno, email, isletmesifre) VALUES (?, ?, ?, ?, ?, ?)");
            $sorgu->bind_param("ssssss", $isletmead, $isletmetipi, $adres, $isletmetelno, $email, $sifre);
            
            if ($sorgu->execute()) {
                $basari = 'İşletme kaydı başarılı! Şimdi giriş yapabilirsiniz.';
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
    <title>İşletme Girişi - Randevu Platformu</title>
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
                    <li><a href="kullanici_giris.php">Müşteri Girişi</a></li>
                    <li><a href="isletme_giris.php" aria-current="page">İşletme Girişi</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main id="main-content" class="container">
        <div class="form-container">
            <h2>İşletme Girişi</h2>
            
            <?php 
            if ($hata) echo hataGoster($hata);
            if ($basari) echo basariGoster($basari);
            ?>
            
            <form method="POST" action="" novalidate>
                <fieldset>
                    <legend>Giriş Bilgileri</legend>
                    
                    <div class="form-group">
                        <label for="email">E-posta Adresi *</label>
                        <input type="email" id="email" name="email" required
                               placeholder="ornek@email.com"
                               aria-required="true">
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
            
            <h2>Yeni İşletme Kaydı</h2>
            <form method="POST" action="" novalidate>
                <fieldset>
                    <legend>İşletme Bilgileri</legend>
                    
                    <div class="form-group">
                        <label for="kayit_isletmead">İşletme Adı *</label>
                        <input type="text" id="kayit_isletmead" name="isletmead" required
                               placeholder="Örn: Ayşe'nin Kuaförü"
                               aria-required="true">
                    </div>
                    
                    <div class="form-group">
                        <label for="kayit_isletmetipi">İşletme Tipi *</label>
                        <select id="kayit_isletmetipi" name="isletmetipi" required
                                aria-required="true">
                            <option value="">Seçiniz...</option>
                            <?php foreach ($isletme_tipleri as $tip): ?>
                                <option value="<?php echo $tip; ?>"><?php echo $tip; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="kayit_adres">Adres *</label>
                        <textarea id="kayit_adres" name="adres" rows="3" required
                                  placeholder="İşletmenizin tam adresi"
                                  aria-required="true"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="kayit_isletmetelno">Telefon Numarası *</label>
                        <input type="tel" id="kayit_isletmetelno" name="isletmetelno" required
                               placeholder="0352 123 4567"
                               aria-required="true">
                    </div>
                    
                    <div class="form-group">
                        <label for="kayit_email">E-posta Adresi *</label>
                        <input type="email" id="kayit_email" name="email" required
                               placeholder="isletme@email.com"
                               aria-required="true"
                               aria-describedby="email-help">
                        <small id="email-help">Giriş için kullanılacaktır</small>
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
            
            <div style="margin-top: 2rem; padding: 1rem; background: var(--light-bg); border-radius: 8px;">
                <h3>Demo Giriş Bilgileri</h3>
                <p><strong>E-posta:</strong> ayse@kuafor.com<br>
                   <strong>Şifre:</strong> password</p>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Randevu Platformu. Tüm hakları saklıdır.</p>
        </div>
    </footer>
</body>
</html>