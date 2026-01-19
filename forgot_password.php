<?php
/**
 * Şifremi Unuttum Sayfası
 */
require_once 'includes/auth.php';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Lütfen email adresinizi girin.';
    } else {
        $user = db()->fetch("SELECT id, name, email FROM users WHERE email = ?", [$email]);

        if ($user) {
            // Reset token oluştur
            $token = generateToken();
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            db()->query(
                "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?",
                [$token, $expires, $user['id']]
            );

            // Email gönder
            sendPasswordResetEmail($user['email'], $user['name'], $token);
        }

        // Güvenlik için her durumda aynı mesajı göster
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifremi Unuttum - Teslim Nüshası</title>
    <link rel="icon" type="image/png" href="/favicon.png"><link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="icon" type="image/png" href="/favicon.png"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-header">
                <a href="/" class="logo">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Teslim Nüshası
                </a>
                <h1>Şifremi Unuttum</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    📧 Eğer bu email adresi kayıtlıysa, şifre sıfırlama linki gönderildi.
                </div>
                <a href="login" class="btn btn-outline btn-lg" style="width:100%;text-align:center;">Giriş Sayfasına
                    Dön</a>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form class="auth-form" method="POST">
                    <div class="form-group">
                        <label for="email">E-posta Adresi</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">Şifre Sıfırlama Linki Gönder</button>
                </form>

                <div class="auth-footer">
                    <p><a href="login">Giriş Sayfasına Dön</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>