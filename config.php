<?php
// Veritabanı Bağlantı Ayarları
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'randevu_sistemi');

// Veritabanı Bağlantısı
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset("utf8mb4");
    
    if ($conn->connect_error) {
        die("Bağlantı hatası: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Oturum Başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Yardımcı Fonksiyonlar
function guvenliVeri($veri) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($veri));
}

function sifreHashle($sifre) {
    return password_hash($sifre, PASSWORD_DEFAULT);
}

function sifreDogrula($sifre, $hash) {
    return password_verify($sifre, $hash);
}

function girisKontrol() {
    return isset($_SESSION['kullanici_id']);
}

function isletmeGirisKontrol() {
    return isset($_SESSION['isletme_id']);
}

function yonlendir($sayfa) {
    header("Location: $sayfa");
    exit();
}

function hataGoster($mesaj) {
    return "<div class='alert alert-danger' role='alert'>$mesaj</div>";
}

function basariGoster($mesaj) {
    return "<div class='alert alert-success' role='alert'>$mesaj</div>";
}

// İşletme Tipleri
$isletme_tipleri = [
    'Kuaför',
    'Berber',
    'Restoran',
    'Kafe',
    'Köpek Gezdiren',
    'Spor Koçu',
    'Bebek Bakıcısı'
];

// Randevu Süreleri (dakika)
define('RANDEVU_SURESI', 60);
?>