<?php
/**
 * İletişim Formu API
 */
header('Content-Type: application/json');
require_once '../includes/settings.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$message = trim($input['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Lütfen tüm alanları doldurun.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Geçerli bir e-posta adresi girin.']);
    exit;
}

// Admin'e email gönder
$toEmail = 'info@teslimnushasi.com';
$subject = 'Yeni İletişim Formu Mesajı - ' . $name;

$body = "
<html>
<body style='font-family: Arial, sans-serif; background: #0a0a0f; color: #ffffff; padding: 20px;'>
    <div style='max-width: 600px; margin: 0 auto; background: #1a1a24; border-radius: 12px; padding: 30px;'>
        <h2 style='color: #6366f1; margin-top: 0;'>📬 Yeni İletişim Mesajı</h2>
        <p><strong>Gönderen:</strong> " . htmlspecialchars($name) . "</p>
        <p><strong>E-posta:</strong> " . htmlspecialchars($email) . "</p>
        <hr style='border-color: #333;'>
        <p><strong>Mesaj:</strong></p>
        <p style='background: #0a0a0f; padding: 15px; border-radius: 8px;'>" . nl2br(htmlspecialchars($message)) . "</p>
    </div>
</body>
</html>
";

if (sendEmail($toEmail, $subject, $body)) {
    echo json_encode(['success' => true, 'message' => 'Mesajınız başarıyla gönderildi!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Mesaj gönderilemedi. Lütfen daha sonra tekrar deneyin.']);
}
