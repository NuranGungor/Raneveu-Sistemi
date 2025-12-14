<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Randevu Platformu - Ana Sayfa</title>
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
                    <li><a href="isletme_giris.php">İşletme Girişi</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main id="main-content" class="container">
        <section class="hero">
            <h2>Kolayca Randevu Alın</h2>
            <p>İhtiyacınız olan hizmeti seçin, müsait saatleri görün ve hemen randevu oluşturun.</p>
        </section>

        <section class="features">
            <h3>Hizmetlerimiz</h3>
            <div class="feature-grid">
                <?php
                foreach ($isletme_tipleri as $tip) {
                    echo "<div class='feature-card'>";
                    echo "<h4>$tip</h4>";
                    echo "<a href='kullanici_giris.php' class='btn btn-primary'>Randevu Al</a>";
                    echo "</div>";
                }
                ?>
            </div>
        </section>

        <section class="info">
            <h3>Nasıl Çalışır?</h3>
            <ol>
                <li>Telefon numaranız ve şifrenizle giriş yapın veya kayıt olun</li>
                <li>İhtiyacınız olan hizmet tipini seçin</li>
                <li>Müsait işletmeleri ve randevu saatlerini görün</li>
                <li>Size uygun tarih ve saati seçerek randevu oluşturun</li>
            </ol>
        </section>

        <section class="business-info">
            <h3>İşletme Sahibi misiniz?</h3>
            <p>Randevu sisteminizi kolayca yönetin. Müsaitlik takvimi oluşturun ve randevuları takip edin.</p>
            <a href="isletme_giris.php" class="btn btn-secondary">İşletme Paneli</a>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 Randevu Platformu. Tüm hakları saklıdır.</p>
        </div>
    </footer>
</body>
</html>