<?php
/**
 * Ayarlar ve Email Fonksiyonları
 */

require_once __DIR__ . '/db.php';

/**
 * Ayar değerini al
 */
function getSetting($key, $default = null)
{
    try {
        $result = db()->fetch("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Ayar kaydet
 */
function setSetting($key, $value)
{
    try {
        $existing = db()->fetch("SELECT id FROM settings WHERE setting_key = ?", [$key]);
        if ($existing) {
            db()->query("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?", [$value, $key]);
        } else {
            db()->query("INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW())", [$key, $value]);
        }
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Varsayılan belge limitini al
 */
function getDefaultDocumentLimit()
{
    return (int) getSetting('default_document_limit', 100);
}

/**
 * Email doğrulama gerekli mi?
 */
function isEmailVerificationRequired()
{
    return getSetting('require_email_verification', '1') === '1';
}

/**
 * Multi-line SMTP response okuma
 */
function smtpReadResponse($socket)
{
    $response = '';
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (isset($line[3]) && $line[3] == ' ')
            break;
        if (strlen($line) < 4)
            break;
    }
    return $response;
}

/**
 * SMTP ile email gönder (SSL/TLS destekli)
 */
function sendEmail($to, $subject, $body)
{
    $host = getSetting('smtp_host', '');
    $port = (int) getSetting('smtp_port', '465');
    $username = getSetting('smtp_username', '');
    $password = getSetting('smtp_password', '');
    $fromEmail = getSetting('smtp_from_email', '');
    $fromName = getSetting('smtp_from_name', 'Teslim Nüshası');

    // SMTP ayarları yapılmamışsa
    if (empty($host) || empty($username) || empty($password) || empty($fromEmail)) {
        error_log("SMTP ayarları yapılmamış");
        return false;
    }

    try {
        // SSL bağlantısı için prefix
        $smtpHost = ($port == 465) ? "ssl://" . $host : $host;

        // Bağlantı aç
        $socket = @fsockopen($smtpHost, $port, $errno, $errstr, 30);
        if (!$socket) {
            error_log("SMTP bağlantı hatası: $errstr ($errno)");
            return false;
        }

        // Stream timeout ayarla
        stream_set_timeout($socket, 30);

        // Sunucu karşılama (multi-line)
        $response = smtpReadResponse($socket);
        if (substr($response, 0, 3) != '220') {
            error_log("SMTP sunucu hatası: $response");
            fclose($socket);
            return false;
        }

        // EHLO (multi-line response)
        fputs($socket, "EHLO localhost\r\n");
        $response = smtpReadResponse($socket);

        // AUTH LOGIN
        fputs($socket, "AUTH LOGIN\r\n");
        $response = smtpReadResponse($socket);
        if (substr($response, 0, 3) != '334') {
            error_log("SMTP AUTH hatası: $response");
            fclose($socket);
            return false;
        }

        // Username
        fputs($socket, base64_encode($username) . "\r\n");
        $response = smtpReadResponse($socket);
        if (substr($response, 0, 3) != '334') {
            error_log("SMTP username hatası: $response");
            fclose($socket);
            return false;
        }

        // Password
        fputs($socket, base64_encode($password) . "\r\n");
        $response = smtpReadResponse($socket);
        if (substr($response, 0, 3) != '235') {
            error_log("SMTP password hatası: $response");
            fclose($socket);
            return false;
        }

        // MAIL FROM
        fputs($socket, "MAIL FROM: <$fromEmail>\r\n");
        $response = smtpReadResponse($socket);
        if (substr($response, 0, 3) != '250') {
            error_log("SMTP MAIL FROM hatası: $response");
            fclose($socket);
            return false;
        }

        // RCPT TO
        fputs($socket, "RCPT TO: <$to>\r\n");
        $response = smtpReadResponse($socket);
        if (substr($response, 0, 3) != '250') {
            error_log("SMTP RCPT TO hatası: $response");
            fclose($socket);
            return false;
        }

        // DATA
        fputs($socket, "DATA\r\n");
        $response = smtpReadResponse($socket);
        if (substr($response, 0, 3) != '354') {
            error_log("SMTP DATA hatası: $response");
            fclose($socket);
            return false;
        }

        // Email içeriği
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromEmail>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "Date: " . date("r") . "\r\n";

        fputs($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
        $response = smtpReadResponse($socket);
        if (substr($response, 0, 3) != '250') {
            error_log("SMTP gönderim hatası: $response");
            fclose($socket);
            return false;
        }

        // QUIT
        fputs($socket, "QUIT\r\n");
        fclose($socket);

        error_log("Email başarıyla gönderildi: $to");
        return true;

    } catch (Exception $e) {
        error_log("Email hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Doğrulama emaili gönder
 */
function sendVerificationEmail($email, $name, $token)
{
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    $verifyUrl = $baseUrl . "/teslimnushasi/verify.php?token=" . urlencode($token);

    $subject = "Email Doğrulama - Teslim Nüshası";
    $body = "
    <html>
    <body style='font-family: Arial, sans-serif; background: #0a0a0f; color: #ffffff; padding: 20px;'>
        <div style='max-width: 500px; margin: 0 auto; background: #1a1a24; border-radius: 12px; padding: 30px;'>
            <h2 style='color: #6366f1; margin-top: 0;'>📧 Email Doğrulama</h2>
            <p>Merhaba <strong>$name</strong>,</p>
            <p>Teslim Nüshası'na kayıt olduğunuz için teşekkürler. Hesabınızı aktifleştirmek için aşağıdaki butona tıklayın:</p>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='$verifyUrl' style='background: #6366f1; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Email Adresimi Doğrula</a>
            </p>
            <p style='color: #6a6a7a; font-size: 12px;'>Bu linke tıklayamıyorsanız, aşağıdaki adresi tarayıcınıza yapıştırın:<br>$verifyUrl</p>
        </div>
    </body>
    </html>
    ";

    return sendEmail($email, $subject, $body);
}

/**
 * Şifre sıfırlama emaili gönder
 */
function sendPasswordResetEmail($email, $name, $token)
{
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    $resetUrl = $baseUrl . "/teslimnushasi/reset_password.php?token=" . urlencode($token);

    $subject = "Şifre Sıfırlama - Teslim Nüshası";
    $body = "
    <html>
    <body style='font-family: Arial, sans-serif; background: #0a0a0f; color: #ffffff; padding: 20px;'>
        <div style='max-width: 500px; margin: 0 auto; background: #1a1a24; border-radius: 12px; padding: 30px;'>
            <h2 style='color: #6366f1; margin-top: 0;'>🔐 Şifre Sıfırlama</h2>
            <p>Merhaba <strong>$name</strong>,</p>
            <p>Şifrenizi sıfırlamak için bir talep aldık. Yeni şifre belirlemek için aşağıdaki butona tıklayın:</p>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='$resetUrl' style='background: #6366f1; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Şifremi Sıfırla</a>
            </p>
            <p style='color: #6a6a7a; font-size: 12px;'>Bu link 1 saat geçerlidir.</p>
            <p style='color: #6a6a7a; font-size: 12px;'>Bu talebi siz yapmadıysanız, bu emaili görmezden gelin.</p>
        </div>
    </body>
    </html>
    ";

    return sendEmail($email, $subject, $body);
}

/**
 * Benzersiz token oluştur
 */
function generateToken()
{
    return bin2hex(random_bytes(32));
}
