<?php
/**
 * İletişim Formu İşleme
 */
require_once 'includes/settings.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Lütfen tüm alanları doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geçerli bir e-posta adresi girin.';
    } else {
        // Admin'e email gönder
        $toEmail = 'info@teslimnushasi.com';
        $subject = 'Yeni İletişim Formu Mesajı - ' . $name;

        $body = "
        <html>
        <body style='font-family: Arial, sans-serif; background: #0a0a0f; color: #ffffff; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: #1a1a24; border-radius: 12px; padding: 30px;'>
                <h2 style='color: #6366f1; margin-top: 0;'>📬 Yeni İletişim Mesajı</h2>
                <p><strong>Gönderen:</strong> $name</p>
                <p><strong>E-posta:</strong> $email</p>
                <hr style='border-color: #333;'>
                <p><strong>Mesaj:</strong></p>
                <p style='background: #0a0a0f; padding: 15px; border-radius: 8px;'>" . nl2br(htmlspecialchars($message)) . "</p>
            </div>
        </body>
        </html>
        ";

        if (sendEmail($toEmail, $subject, $body)) {
            $success = true;
        } else {
            $error = 'Mesaj gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İletişim - Teslim Nüshası</title>
    <link rel="icon" type="image/png" href="/favicon.png"><link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="icon" type="image/png" href="/favicon.png"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <a href="/" class="navbar-brand">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Teslim Nüshası</span>
                </a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <h3>✅ Mesajınız Gönderildi!</h3>
                    <p>En kısa sürede size dönüş yapacağız.</p>
                </div>
                <a href="/" class="btn btn-primary btn-lg" style="width:100%;text-align:center;">Ana Sayfaya Dön</a>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <h2>Mesajınızı Gönderin</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Adınız Soyadınız</label>
                        <input type="text" name="name" class="form-control"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>E-posta Adresiniz</label>
                        <input type="email" name="email" class="form-control"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Mesajınız</label>
                        <textarea name="message" class="form-control" rows="5"
                            required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg" style="width:100%">Mesaj Gönder</button>
                </form>
                <p style="text-align:center;margin-top:20px;"><a href="/">Ana Sayfaya Dön</a></p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>