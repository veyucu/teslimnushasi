<?php
/**
 * Kullanıcı Güncelle API
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
$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$role = $input['role'] ?? 'user';
$isActive = (int) ($input['is_active'] ?? 1);
$documentLimit = (int) ($input['document_limit'] ?? 100);

if ($id <= 0 || empty($name) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'ID, ad ve e-posta zorunludur']);
    exit;
}

// Email kontrolü (kendi e-postası hariç)
$existing = db()->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $id]);
if ($existing) {
    echo json_encode(['success' => false, 'message' => 'Bu e-posta adresi başka bir kullanıcıya ait']);
    exit;
}

// Rol kontrolü
if (!in_array($role, ['admin', 'user'])) {
    $role = 'user';
}

try {
    if (!empty($password)) {
        // Şifre de güncelle
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        db()->query(
            "UPDATE users SET name = ?, email = ?, password = ?, role = ?, is_active = ?, document_limit = ? WHERE id = ?",
            [$name, $email, $hashedPassword, $role, $isActive, $documentLimit, $id]
        );
    } else {
        // Şifre hariç güncelle
        db()->query(
            "UPDATE users SET name = ?, email = ?, role = ?, is_active = ?, document_limit = ? WHERE id = ?",
            [$name, $email, $role, $isActive, $documentLimit, $id]
        );
    }

    echo json_encode(['success' => true, 'message' => 'Kullanıcı başarıyla güncellendi']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Kullanıcı güncellenirken hata oluştu']);
}
