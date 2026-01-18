<?php
/**
 * Duplikat Belge Kontrolü API
 * ETTN ve belge no için veritabanı kontrolü yapar
 */

require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['documents']) || !is_array($input['documents'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Documents array required']);
    exit;
}

$documents = $input['documents'];
$duplicates = [];

try {
    // Her belge için kontrol
    foreach ($documents as $index => $doc) {
        $reasons = [];

        // ETTN kontrolü
        if (!empty($doc['ettn'])) {
            $stmt = db()->query(
                "SELECT id FROM documents WHERE ettn = ? LIMIT 1",
                [$doc['ettn']]
            );
            if ($stmt->fetch()) {
                $reasons[] = 'ETTN veritabanında mevcut';
            }
        }

        // Belge tipi + Belge No kontrolü
        if (!empty($doc['document_type']) && !empty($doc['document_no'])) {
            $stmt = db()->query(
                "SELECT id FROM documents WHERE document_type = ? AND document_no = ? LIMIT 1",
                [$doc['document_type'], $doc['document_no']]
            );
            if ($stmt->fetch()) {
                $reasons[] = 'Aynı tipte belge no veritabanında mevcut';
            }
        }

        // Batch içinde duplikat kontrolü (aynı ETTN)
        if (!empty($doc['ettn'])) {
            foreach ($documents as $j => $otherDoc) {
                if ($j !== $index && !empty($otherDoc['ettn']) && $otherDoc['ettn'] === $doc['ettn']) {
                    $reasons[] = 'Aynı ETTN batch içinde tekrar ediyor';
                    break;
                }
            }
        }

        // Batch içinde duplikat kontrolü (aynı tip + belge no)
        if (!empty($doc['document_type']) && !empty($doc['document_no'])) {
            foreach ($documents as $j => $otherDoc) {
                if (
                    $j !== $index &&
                    !empty($otherDoc['document_type']) && !empty($otherDoc['document_no']) &&
                    $otherDoc['document_type'] === $doc['document_type'] &&
                    $otherDoc['document_no'] === $doc['document_no']
                ) {
                    $reasons[] = 'Aynı belge batch içinde tekrar ediyor';
                    break;
                }
            }
        }

        if (!empty($reasons)) {
            $duplicates[$index] = $reasons;
        }
    }

    echo json_encode([
        'success' => true,
        'duplicates' => $duplicates
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
