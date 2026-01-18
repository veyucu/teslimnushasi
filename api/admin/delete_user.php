<?php
/**
 * Kullanıcı Sil API
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

// Kendini silemesin
$currentUser = currentUser();
if ($currentUser['id'] == $id) {
    echo json_encode(['success' => false, 'message' => 'Kendinizi silemezsiniz']);
    exit;
}

try {
    // Önce kullanıcının belgelerini ve sayfa dosyalarını sil
    $documents = db()->query("SELECT id FROM documents WHERE user_id = ?", [$id])->fetchAll();

    foreach ($documents as $doc) {
        // Sayfa dosyalarını sil
        $pages = db()->query("SELECT file_path FROM document_pages WHERE document_id = ?", [$doc['id']])->fetchAll();
        foreach ($pages as $page) {
            $filePath = __DIR__ . '/../../' . $page['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        // Sayfaları sil
        db()->query("DELETE FROM document_pages WHERE document_id = ?", [$doc['id']]);
    }

    // Belgeleri sil
    db()->query("DELETE FROM documents WHERE user_id = ?", [$id]);

    // Kullanıcıyı sil
    db()->query("DELETE FROM users WHERE id = ?", [$id]);

    echo json_encode(['success' => true, 'message' => 'Kullanıcı ve tüm belgeleri silindi']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Silme işlemi sırasında hata oluştu: ' . $e->getMessage()]);
}
