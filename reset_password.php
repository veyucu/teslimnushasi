<?php
/**
 * Şifre Sıfırlama Sayfası
 */
require_once 'includes/auth.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = false;
$validToken = false;

if (empty($token)) {
    $error = 'Geçersiz şifre sıfırlama linki.';
} else {
    // Token geçerli mi kontrol et
    $user = db()->fetch(
        "SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()",
        [$token]
    );

    if (!$user) {
        $error = 'Geçersiz veya süresi dolmuş şifre sıfırlama linki.';
    } else {
        $validToken = true;

        // Form gönderildiğinde
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($password) || strlen($password) < 6) {
                $error = 'Şifre en az 6 karakter olmalıdır.';
            } else if ($password !== $confirmPassword) {
                $error = 'Şifreler eşleşmiyor.';
            } else {
                // Şifreyi güncelle
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                db()->query(
                    "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?",
                    [$hashedPassword, $user['id']]
                );
                $success = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Şifre Sıfırla - Teslim Nüshası</title>
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
                <h1>Yeni Şifre Belirle</h1>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ Şifreniz başarıyla değiştirildi!
                </div>
                <a href="login.php?reset=1" class="btn btn-primary btn-lg" style="width:100%;text-align:center;">Giriş
                    Yap</a>
            <?php elseif ($validToken): ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form class="auth-form" method="POST">
                    <div class="form-group">
                        <label for="password">Yeni Şifre</label>
                        <input type="password" id="password" name="password" class="form-control" minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Şifre Tekrar</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                            minlength="6" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">Şifremi Değiştir</button>
                </form>
            <?php else: ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
                <a href="forgot_password.php" class="btn btn-outline btn-lg" style="width:100%;text-align:center;">Yeni Link
                    Talep Et</a>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>