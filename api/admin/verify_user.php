<?php
/**
 * Manuel Email Doğrulama API
 */
header('Content-Type: application/json');

require_once '../../includes/auth.php';

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = (int) ($input['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı ID']);
    exit;
}

try {
    db()->query("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?", [$id]);
    echo json_encode(['success' => true, 'message' => 'Kullanıcı emaili doğrulandı']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Doğrulama sırasında hata oluştu']);
}
