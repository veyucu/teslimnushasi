<?php
/**
 * Belge Detaylarını Getir API
 * Modal önizleme için sayfa bilgileriyle birlikte
 */
header('Content-Type: application/json');

require_once '../includes/auth.php';
require_once '../includes/db.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit;
}

$user = currentUser();
$id = $_GET['id'] ?? 0;

if (!is_numeric($id)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz belge ID']);
    exit;
}

// Belge bilgilerini getir
$doc = db()->query(
    "SELECT * FROM documents WHERE id = ? AND user_id = ?",
    [$id, $user['id']]
)->fetch();

if (!$doc) {
    echo json_encode(['success' => false, 'message' => 'Belge bulunamadı']);
    exit;
}

// Sayfa bilgilerini getir
$pages = db()->query(
    "SELECT * FROM document_pages WHERE document_id = ? ORDER BY page_number ASC",
    [$id]
)->fetchAll();

echo json_encode([
    'success' => true,
    'id' => $doc['id'],
    'public_id' => $doc['public_id'] ?? null,
    'document_type' => $doc['document_type'],
    'document_no' => $doc['document_no'],
    'document_date' => $doc['document_date'],
    'customer_name' => $doc['customer_name'],
    'page_count' => $doc['page_count'] ?? count($pages),
    'pages' => $pages
]);
