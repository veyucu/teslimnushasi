<?php
/**
 * Kayıt Sayfası
 */
require_once 'includes/auth.php';
require_once 'includes/settings.php';

// Zaten giriş yapmışsa yönlendir
if (isLoggedIn()) {
    header('Location: documents');
    exit;
}

// Yeni üye alımı kapalıysa
$registrationEnabled = getSetting('registration_enabled', '1') === '1';

$error = '';
$success = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Lütfen tüm alanları doldurun.';
    } else if (strlen($password) < 6) {
        $error = 'Şifre en az 6 karakter olmalıdır.';
    } else if ($password !== $password_confirm) {
        $error = 'Şifreler eşleşmiyor.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin.';
    } else {
        $result = register($name, $email, $password);
        if ($result['success']) {
            header('Location: login?registered=1');
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
    <title>Kayıt Ol - Teslim Nüshası</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
                <h1>Ücretsiz Hesap Oluşturun</h1>
            </div>

            <?php if (!$registrationEnabled): ?>
                <div class="alert alert-danger" style="margin-bottom:20px;">
                    Şu anda yeni üye alımı yapılmamaktadır. Daha sonra tekrar deneyiniz.
                </div>
                <div class="auth-footer">
                    <p><a href="login">Giriş Sayfasına Dön</a></p>
                </div>
            <?php else: ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="POST">
                <div class="form-group">
                    <label for="name">Adınız Soyadınız</label>
                    <input type="text" id="name" name="name" class="form-control"
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">E-posta Adresi</label>
                    <input type="email" id="email" name="email" class="form-control"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Şifre</label>
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="En az 6 karakter" required>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Şifre Tekrar</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">Kayıt Ol</button>
            </form>

            <div class="auth-footer">
                <p>Zaten hesabınız var mı? <a href="login">Giriş Yapın</a></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>