<?php
/**
 * Teslim Nüshası - Konfigürasyon Dosyası (ÖRNEK)
 * Bu dosyayı config.php olarak kopyalayın ve değerleri güncelleyin
 */

// Veritabanı Ayarları
define('DB_HOST', 'localhost');
define('DB_NAME', 'teslimnushasi');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site Ayarları
define('SITE_NAME', 'Teslim Nüshası');
define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('SITE_EMAIL', 'info@example.com');

// Oturum Ayarları
define('SESSION_LIFETIME', 3600);

// Hata Ayıklama - PRODUCTION'DA FALSE OLMALI!
define('DEBUG_MODE', false);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

date_default_timezone_set('Europe/Istanbul');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
