<?php
/**
 * SMTP Test Scripti
 */
require_once 'includes/db.php';
require_once 'includes/settings.php';

echo "<h2>SMTP Ayarları Test</h2>";
echo "<pre>";

$host = getSetting('smtp_host', '');
$port = getSetting('smtp_port', '465');
$username = getSetting('smtp_username', '');
$password = getSetting('smtp_password', '');
$fromEmail = getSetting('smtp_from_email', '');
$fromName = getSetting('smtp_from_name', 'Teslim Nüshası');

echo "Host: " . ($host ?: '(boş)') . "\n";
echo "Port: " . ($port ?: '(boş)') . "\n";
echo "Username: " . ($username ?: '(boş)') . "\n";
echo "Password: " . ($password ? '****** (şifreli)' : '(boş)') . "\n";
echo "From Email: " . ($fromEmail ?: '(boş)') . "\n";
echo "From Name: " . ($fromName ?: '(boş)') . "\n\n";

if (empty($host) || empty($username) || empty($password) || empty($fromEmail)) {
    echo "<strong style='color:red'>HATA: SMTP ayarları eksik!</strong>\n";
    echo "Lütfen Admin Panel > Ayarlar sayfasından SMTP bilgilerini girin.\n";
} else {
    echo "SMTP ayarları tamam. Test emaili gönderiliyor...\n\n";

    $testEmail = $username; // Kendine test emaili gönder
    $result = sendEmail($testEmail, 'Test Email - Teslim Nüshası', '<h1>Test Email</h1><p>Bu bir test emailidir. SMTP ayarlarınız çalışıyor!</p>');

    if ($result) {
        echo "<strong style='color:green'>Email başarıyla gönderildi!</strong>";
    } else {
        echo "<strong style='color:red'>Email gönderilemedi!</strong>\n";
        echo "PHP error_log dosyasını kontrol edin.";
    }
}

echo "</pre>";
