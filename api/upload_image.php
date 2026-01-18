<?php
/**
 * Resim Yükleme API
 * Sadece dosyayı kaydeder (OCR yapmaz)
 */
header('Content-Type: application/json');

require_once '../includes/auth.php';

// Giriş kontrolü
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit;
}

$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Dosya yüklenemedi']);
    exit;
}

$file = $_FILES['file'];
$allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
$fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($fileExt, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz dosya türü']);
    exit;
}

// Dosya boyutu kontrolü (max 10MB)
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Dosya boyutu çok büyük']);
    exit;
}

// Kullanıcı klasörü
$userDir = __DIR__ . '/../uploads/' . $user['id'] . '/';
if (!is_dir($userDir)) {
    mkdir($userDir, 0755, true);
}

// Gelen dosya adını kullan (UUID formatında)
$originalName = pathinfo($file['name'], PATHINFO_FILENAME);
$fileName = $originalName . '.' . $fileExt;
$filePath = $userDir . $fileName;
$relativePath = 'uploads/' . $user['id'] . '/' . $fileName;

if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    echo json_encode(['success' => false, 'message' => 'Dosya kaydedilemedi']);
    exit;
}

echo json_encode([
    'success' => true,
    'file_path' => $relativePath,
    'file_name' => $fileName
]);
