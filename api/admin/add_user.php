<?php
/**
 * Yeni Kullanıcı Ekle API
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

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$role = $input['role'] ?? 'user';
$documentLimit = (int) ($input['document_limit'] ?? 100);

if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Ad, e-posta ve şifre zorunludur']);
    exit;
}

// Email kontrolü
$existing = db()->fetch("SELECT id FROM users WHERE email = ?", [$email]);
if ($existing) {
    echo json_encode(['success' => false, 'message' => 'Bu e-posta adresi zaten kayıtlı']);
    exit;
}

// Rol kontrolü
if (!in_array($role, ['admin', 'user'])) {
    $role = 'user';
}

try {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    db()->query(
        "INSERT INTO users (name, email, password, role, document_limit, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
        [$name, $email, $hashedPassword, $role, $documentLimit]
    );

    echo json_encode(['success' => true, 'message' => 'Kullanıcı başarıyla eklendi']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı eklenirken hata oluştu']);
}
