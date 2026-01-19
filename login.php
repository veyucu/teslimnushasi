<?php
/**
 * Giriş Sayfası
 */
require_once 'includes/auth.php';

// Zaten giriş yapmışsa yönlendir
if (isLoggedIn()) {
    header('Location: documents');
    exit;
}

$error = '';
$info = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Lütfen tüm alanları doldurun.';
    } else {
        $result = login($email, $password);
        if ($result['success']) {
            header('Location: documents');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Teslim Nüshası</title>
    <link rel="icon" type="image/png" href="/favicon.png"><link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="icon" type="image/png" href="/favicon.png"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
                <h1>Hesabınıza Giriş Yapın</h1>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">Kayıt başarılı! Email adresinize gönderilen doğrulama linkine tıklayın.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['verified'])): ?>
                <div class="alert alert-success">Email adresiniz doğrulandı! Şimdi giriş yapabilirsiniz.</div>
            <?php endif; ?>

            <?php if (isset($_GET['reset'])): ?>
                <div class="alert alert-success">Şifreniz başarıyla değiştirildi! Şimdi giriş yapabilirsiniz.</div>
            <?php endif; ?>

            <form class="auth-form" method="POST">
                <div class="form-group">
                    <label for="email">E-posta Adresi</label>
                    <input type="email" id="email" name="email" class="form-control"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">Giriş Yap</button>
            </form>

            <div class="auth-footer">
                <p><a href="forgot_password">Şifremi Unuttum</a></p>
                <p>Hesabınız yok mu? <a href="register">Kayıt Olun</a></p>
            </div>
        </div>
    </div>
</body>

</html>