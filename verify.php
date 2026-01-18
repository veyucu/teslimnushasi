<?php
/**
 * Email Doğrulama Sayfası
 */
require_once 'includes/auth.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = false;

if (empty($token)) {
    $error = 'Geçersiz doğrulama linki.';
} else {
    // Token ile kullanıcıyı bul
    $user = db()->fetch("SELECT id, email_verified FROM users WHERE verification_token = ?", [$token]);

    if (!$user) {
        $error = 'Geçersiz veya süresi dolmuş doğrulama linki.';
    } else if ($user['email_verified']) {
        $error = 'Bu email adresi zaten doğrulanmış.';
    } else {
        // Email'i doğrula
        db()->query("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?", [$user['id']]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Doğrulama - Teslim Nüshası</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-header">
                <a href="index.php" class="logo">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Teslim Nüshası
                </a>
                <h1>Email Doğrulama</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ Email adresiniz başarıyla doğrulandı!
                </div>
                <a href="login.php?verified=1" class="btn btn-primary btn-lg" style="width:100%;text-align:center;">Giriş
                    Yap</a>
            <?php else: ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
                <a href="login.php" class="btn btn-outline btn-lg" style="width:100%;text-align:center;">Giriş Sayfasına
                    Dön</a>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>