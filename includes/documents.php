<?php
/**
 * Belge İşlemleri Fonksiyonları
 */

require_once __DIR__ . '/db.php';

/**
 * Belge yükle
 */
function uploadDocument($userId, $file, $documentType, $documentNo, $documentDate, $customerName, $notes = '')
{
    // Dosya uzantısı kontrolü
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => 'Geçersiz dosya türü. Sadece JPG, PNG, GIF ve PDF dosyaları yüklenebilir.'];
    }

    // Dosya boyutu kontrolü (max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Dosya boyutu çok büyük. Maksimum 10MB yüklenebilir.'];
    }

    // Benzersiz dosya adı oluştur
    $fileName = uniqid('doc_') . '_' . time() . '.' . $fileExt;
    $uploadDir = __DIR__ . '/../uploads/' . $userId . '/';

    // Kullanıcı klasörü yoksa oluştur
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filePath = $uploadDir . $fileName;
    $relativePath = 'uploads/' . $userId . '/' . $fileName;

    // Dosyayı taşı
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => false, 'message' => 'Dosya yüklenirken bir hata oluştu.'];
    }

    // Veritabanına kaydet
    try {
        db()->query(
            "INSERT INTO documents (user_id, document_type, document_no, document_date, customer_name, notes, file_path, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [$userId, $documentType, $documentNo, $documentDate, $customerName, $notes, $relativePath]
        );

        return ['success' => true, 'message' => 'Belge başarıyla yüklendi.', 'id' => db()->lastInsertId()];
    } catch (Exception $e) {
        // Yüklenen dosyayı sil
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        return ['success' => false, 'message' => 'Veritabanına kayıt sırasında hata oluştu.'];
    }
}

/**
 * Kullanıcının belgelerini getir
 */
function getUserDocuments($userId, $search = '', $type = '', $limit = 50, $offset = 0)
{
    $sql = "SELECT * FROM documents WHERE user_id = ?";
    $params = [$userId];

    if (!empty($search)) {
        $sql .= " AND (document_no LIKE ? OR customer_name LIKE ? OR notes LIKE ?)";
        $searchTerm = '%' . $search . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if (!empty($type)) {
        $sql .= " AND document_type = ?";
        $params[] = $type;
    }

    $sql .= " ORDER BY document_date DESC, created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    return db()->fetchAll($sql, $params);
}

/**
 * Belge detayını getir
 */
function getDocument($documentId, $userId)
{
    return db()->fetch(
        "SELECT * FROM documents WHERE id = ? AND user_id = ?",
        [$documentId, $userId]
    );
}

/**
 * Belge sil
 */
function deleteDocument($documentId, $userId)
{
    $document = getDocument($documentId, $userId);

    if (!$document) {
        return ['success' => false, 'message' => 'Belge bulunamadı.'];
    }

    // Dosyayı sil
    $filePath = __DIR__ . '/../' . $document['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Veritabanından sil
    db()->query("DELETE FROM documents WHERE id = ? AND user_id = ?", [$documentId, $userId]);

    return ['success' => true, 'message' => 'Belge başarıyla silindi.'];
}

/**
 * Belge sayısını getir (tip filtresiz)
 */
function getDocumentCountByType($userId, $type = '')
{
    $sql = "SELECT COUNT(*) as count FROM documents WHERE user_id = ?";
    $params = [$userId];

    if (!empty($type)) {
        $sql .= " AND document_type = ?";
        $params[] = $type;
    }

    return db()->fetch($sql, $params)['count'] ?? 0;
}
