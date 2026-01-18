<?php
/**
 * Belge Limiti Kontrol API
 * Kullanıcının kalan belge hakkını döner
 */
header('Content-Type: application/json');

require_once '../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit;
}

$user = currentUser();
$requestedCount = (int) ($_GET['count'] ?? 1);

$remaining = getRemainingDocuments($user['id']);
$canAdd = canAddDocument($user['id'], $requestedCount);

echo json_encode([
    'success' => true,
    'can_add' => $canAdd,
    'remaining' => $remaining,
    'requested' => $requestedCount,
    'message' => $canAdd ? 'Belge eklenebilir' : "Belge limitiniz aşılıyor. Kalan hak: $remaining belge."
]);
