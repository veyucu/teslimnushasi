<?php
/**
 * Belgeleri Kaydet API
 * Her sayfa ayrı WebP olarak, aynı belgeye bağlı
 */
header('Content-Type: application/json');

require_once '../includes/auth.php';
require_once '../includes/db.php';

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

// Benzersiz public ID üret (10 karakter, alfanumerik)
function generatePublicId($length = 10)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $id = '';
    for ($i = 0; $i < $length; $i++) {
        $id .= $chars[random_int(0, 61)];
    }
    return $id;
}

// Benzersiz public_id üret (çakışma kontrolü ile)
function getUniquePublicId()
{
    $maxAttempts = 10;
    for ($i = 0; $i < $maxAttempts; $i++) {
        $publicId = generatePublicId(10);
        $existing = db()->query("SELECT id FROM documents WHERE public_id = ? LIMIT 1", [$publicId])->fetch();
        if (!$existing) {
            return $publicId;
        }
    }
    // Son çare: 12 karakter dene
    return generatePublicId(12);
}

// JSON verisini al
$input = json_decode(file_get_contents('php://input'), true);
$documents = $input['documents'] ?? [];

if (empty($documents)) {
    echo json_encode(['success' => false, 'message' => 'Kayıt edilecek belge yok']);
    exit;
}

// Belge limiti kontrolü
$documentCount = count($documents);
if (!canAddDocument($user['id'], $documentCount)) {
    $remaining = getRemainingDocuments($user['id']);
    echo json_encode([
        'success' => false,
        'message' => "Belge limitiniz aşıldı. Kalan hak: $remaining belge. Eklemek istediğiniz: $documentCount belge."
    ]);
    exit;
}

$saved = 0;
$errors = 0;

foreach ($documents as $doc) {
    $documentType = $doc['document_type'] ?? '';
    $documentNo = $doc['document_no'] ?? '';
    $documentDate = $doc['document_date'] ?? date('Y-m-d');
    $customerName = $doc['customer_name'] ?? '';
    $customerVkn = $doc['customer_vkn'] ?? '';
    $ettn = $doc['ettn'] ?? '';
    $pages = $doc['pages'] ?? []; // [{file_path: "...", page_number: 1}, ...]
    $publicId = getUniquePublicId();

    if (empty($pages)) {
        $errors++;
        continue;
    }

    try {
        // Ana belge kaydı
        db()->query(
            "INSERT INTO documents (user_id, public_id, document_type, document_no, document_date, customer_name, customer_vkn, ettn, page_count, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [$user['id'], $publicId, $documentType, $documentNo, $documentDate, $customerName, $customerVkn, $ettn, count($pages)]
        );

        $documentId = db()->lastInsertId();

        // Sayfaları kaydet
        foreach ($pages as $page) {
            $filePath = $page['file_path'] ?? '';
            $pageNumber = $page['page_number'] ?? 1;
            $fileSize = 0;

            // Dosya boyutunu al
            $fullPath = __DIR__ . '/../' . $filePath;
            if (file_exists($fullPath)) {
                $fileSize = filesize($fullPath);
            }

            db()->query(
                "INSERT INTO document_pages (document_id, page_number, file_path, file_size, created_at)
                 VALUES (?, ?, ?, ?, NOW())",
                [$documentId, $pageNumber, $filePath, $fileSize]
            );
        }

        $saved++;
    } catch (Exception $e) {
        error_log("Document save error: " . $e->getMessage());
        $errors++;
    }
}

// Sadece işlenen belgelerin temp dosyalarını sil
foreach ($documents as $doc) {
    foreach ($doc['pages'] ?? [] as $page) {
        $filePath = $page['file_path'] ?? '';
        // UUID'yi al ve temp klasöründeki dosyayı sil
        $uuid = pathinfo($filePath, PATHINFO_FILENAME);
        $tempFilePath = __DIR__ . '/../uploads/temp/' . $uuid . '.webp';
        if (file_exists($tempFilePath)) {
            unlink($tempFilePath);
        }
    }
}

echo json_encode([
    'success' => true,
    'saved' => $saved,
    'errors' => $errors,
    'message' => "$saved belge kaydedildi" . ($errors > 0 ? ", $errors hata" : '')
]);
