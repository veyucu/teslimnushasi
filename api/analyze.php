<?php
/**
 * Dosya Analiz API
 * Google Document AI ile belge analizi - Python'daki gibi çoklu belge desteği
 */
header('Content-Type: application/json');

require_once '../includes/auth.php';
require_once '../includes/config.php';

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
$allowedTypes = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];
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

// Gelen dosya adını kullan (UUID formatında)
$originalName = pathinfo($file['name'], PATHINFO_FILENAME);
$fileName = $originalName . '.' . $fileExt;
$userId = $user['id'];
$uploadDir = __DIR__ . '/../uploads/temp/' . $userId . '/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filePath = $uploadDir . $fileName;
$relativePath = 'uploads/temp/' . $userId . '/' . $fileName;

if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    echo json_encode(['success' => false, 'message' => 'Dosya kaydedilemedi']);
    exit;
}


// Thumbnail path (resimler için)
$thumbnailPath = '';
if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'webp'])) {
    $thumbnailPath = $relativePath;
}

// Google Document AI ile analiz yap
$result = analyzeWithDocumentAI($filePath, $relativePath, $thumbnailPath);

// Sonuç döndür - ocr_text ile birlikte (header kontrolü için)
echo json_encode([
    'success' => true,
    'documents' => $result['documents'],
    'ocr_text' => $result['ocr_text'] ?? ''
]);

/**
 * Google Document AI ile belge analizi
 * Python'daki gibi çoklu belge ayrıştırma
 */
function analyzeWithDocumentAI($filePath, $relativePath, $thumbnailPath)
{
    // Google Cloud credentials
    $credentialsPath = __DIR__ . '/../rare-chiller-210007-cb726fce6c22.json';

    if (!file_exists($credentialsPath)) {
        return ['documents' => [createEmptyDocument($relativePath, $thumbnailPath)], 'ocr_text' => ''];
    }

    try {
        $credentials = json_decode(file_get_contents($credentialsPath), true);
        $accessToken = getAccessToken($credentials);

        if (!$accessToken) {
            return ['documents' => [createEmptyDocument($relativePath, $thumbnailPath)], 'ocr_text' => ''];
        }

        // Document AI Form Parser endpoint
        $projectId = '459032931285';
        $location = 'eu';
        $processorId = 'bf9a12db7d3759fe';

        $endpoint = "https://{$location}-documentai.googleapis.com/v1/projects/{$projectId}/locations/{$location}/processors/{$processorId}:process";

        // Dosya içeriğini base64 encode
        $fileContent = file_get_contents($filePath);
        $base64Content = base64_encode($fileContent);
        $mimeType = getMimeType($filePath);

        $requestBody = [
            'rawDocument' => [
                'content' => $base64Content,
                'mimeType' => $mimeType
            ]
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Debug log - sadece DEBUG_MODE aktifken kaydet
        if (DEBUG_MODE) {
            $logDir = __DIR__ . '/../documentai_logs/';
            if (!is_dir($logDir))
                mkdir($logDir, 0755, true);
            $logFileName = pathinfo($filePath, PATHINFO_FILENAME);
            file_put_contents($logDir . "{$logFileName}.json", $response);
            file_put_contents($logDir . 'last_response.json', $response);
        }

        if ($httpCode === 200) {
            $result = json_decode($response, true);
            $ocrText = $result['document']['text'] ?? '';
            $parseResult = parseDocumentAIResponse($result, $relativePath, $thumbnailPath);
            return ['documents' => $parseResult, 'ocr_text' => $ocrText];
        }

        return ['documents' => [createEmptyDocument($relativePath, $thumbnailPath)], 'ocr_text' => ''];

    } catch (Exception $e) {
        error_log("Document AI Error: " . $e->getMessage());
        return ['documents' => [createEmptyDocument($relativePath, $thumbnailPath)], 'ocr_text' => ''];
    }
}

/**
 * Document AI yanıtını parse et - formFields ve text'ten veri çıkar
 */
function parseDocumentAIResponse($response, $relativePath, $thumbnailPath)
{
    $document = $response['document'] ?? [];
    $text = $document['text'] ?? '';
    $pages = $document['pages'] ?? [];

    if (empty($text)) {
        return [createEmptyDocument($relativePath, $thumbnailPath)];
    }

    // Text'ten önce belge tipini belirle
    $docData = extractDataFromText($text);
    $belgeTipi = $docData['belge_tipi'];

    // FormFields'tan veri çıkar (belge tipine göre)
    $formFieldsData = extractFromFormFields($pages, $belgeTipi);

    // FormFields verilerini öncelikle kullan
    if (!empty($formFieldsData['belge_no'])) {
        $docData['belge_no'] = $formFieldsData['belge_no'];
    }
    if (!empty($formFieldsData['tarih'])) {
        $docData['tarih'] = $formFieldsData['tarih'];
    }
    if (!empty($formFieldsData['musteri'])) {
        $docData['musteri'] = $formFieldsData['musteri'];
    }

    $docData['file_path'] = $relativePath;
    $docData['thumbnail'] = $thumbnailPath;
    $docData['doc_index'] = 1;

    return [$docData];
}

/**
 * Document AI formFields'tan veri çıkar (belge tipine göre)
 */
function extractFromFormFields($pages, $belgeTipi)
{
    $data = ['belge_no' => '', 'tarih' => '', 'musteri' => ''];

    // Belge tipine göre alan adları
    if ($belgeTipi === 'irsaliye') {
        $belgeNoFields = ['İrsaliye No', 'Irsaliye No', 'rsaliye No'];
        $tarihFields = ['İrsaliye Tarihi', 'Irsaliye Tarihi', 'rsaliye Tarihi'];
    } else {
        $belgeNoFields = ['Fatura No', 'Belge No'];
        $tarihFields = ['Fatura Tarihi', 'Belge Tarihi'];
    }

    foreach ($pages as $page) {
        $formFields = $page['formFields'] ?? [];
        foreach ($formFields as $field) {
            $fieldName = trim($field['fieldName']['textAnchor']['content'] ?? '');
            $fieldValue = trim($field['fieldValue']['textAnchor']['content'] ?? '');

            // Belge No
            if (empty($data['belge_no'])) {
                foreach ($belgeNoFields as $searchField) {
                    if (stripos($fieldName, $searchField) !== false) {
                        $cleanValue = trim(preg_replace('/\s+/', '', $fieldValue));
                        if (mb_strlen($cleanValue) >= 10) {
                            $data['belge_no'] = mb_substr($cleanValue, 0, 16);
                        }
                        break;
                    }
                }
            }

            // Tarih
            if (empty($data['tarih'])) {
                foreach ($tarihFields as $searchField) {
                    if (stripos($fieldName, $searchField) !== false) {
                        // GG-AA-YYYY formatını YYYY-MM-DD'ye çevir
                        if (preg_match('/(\d{2})[-.\/ ](\d{2})[-.\/ ](\d{4})/', $fieldValue, $match)) {
                            $data['tarih'] = $match[3] . '-' . $match[2] . '-' . $match[1];
                        }
                        break;
                    }
                }
            }
        }
    }

    return $data;
}

/**
 * Text'ten veri çıkar - Python'daki extract_data_from_image fonksiyonunun PHP versiyonu
 */
function extractDataFromText($text)
{
    $data = [
        'belge_tipi' => '',
        'belge_no' => '',
        'tarih' => '',
        'musteri' => '',
        'ettn' => '',
        'vkn' => ''
    ];

    $textLower = turkishLower($text);
    $textUpper = mb_strtoupper($text, 'UTF-8');

    // ============ 1. BELGE TİPİ ============
    // Python app.py mantığı: ÖNCE FATURA KONTROL ET (çünkü faturada irsaliye bilgisi olabiliyor)

    // Fatura pattern'leri (Python: fatura_patterns - satır 207)
    $faturaPatterns = ['e-fatura', 'efatura', 'e fatura', 'e-arşiv', 'e-arsiv', 'earşiv', 'earsiv', 'e arşiv', 'e arsiv'];
    $isFatura = false;
    foreach ($faturaPatterns as $pattern) {
        if (stripos($textLower, $pattern) !== false) {
            $isFatura = true;
            break;
        }
    }

    // İrsaliye pattern'leri (Python: irsaliye_patterns - satır 208)
    $irsaliyePatterns = ['e-irsaliye', 'eirsaliye', 'e irsaliye'];
    $isIrsaliye = false;
    foreach ($irsaliyePatterns as $pattern) {
        if (stripos($textLower, $pattern) !== false) {
            $isIrsaliye = true;
            break;
        }
    }

    // Belge tipi belirleme - Python mantığı (satır 210-215)
    // ÖNCE FATURA, SONRA İRSALİYE
    if ($isFatura) {
        $data['belge_tipi'] = 'fatura';
    } else if ($isIrsaliye) {
        $data['belge_tipi'] = 'irsaliye';
    } else {
        // Genel kontrol (Python satır 357-362)
        if (stripos($text, 'FATURA') !== false) {
            $data['belge_tipi'] = 'fatura';
        } else if (stripos($text, 'İRSALİYE') !== false || stripos($text, 'IRSALIYE') !== false) {
            $data['belge_tipi'] = 'irsaliye';
        } else {
            $data['belge_tipi'] = 'fatura'; // Varsayılan
        }
    }

    // ============ 2. BELGE NO ============
    // Label tabanlı arama - İrsaliye No: veya Fatura No: bul, sonraki 50 satırda değer ara
    $belgeTipi = $data['belge_tipi'];
    $lines = explode("\n", $text);

    // Belge tipine göre label'lar (hem Türkçe İ hem ASCII I)
    $belgeNoLabels = ($belgeTipi === 'irsaliye')
        ? ['irsaliye no', 'ırsaliye no']
        : ['fatura no', 'belge no'];

    foreach ($lines as $i => $line) {
        $lineLower = turkishLower($line);
        foreach ($belgeNoLabels as $label) {
            if (stripos($lineLower, $label) !== false) {
                // Label bulundu, sonraki 50 satırda 16 haneli alfanumerik ara
                for ($j = $i + 1; $j < min($i + 50, count($lines)); $j++) {
                    $nextLine = trim($lines[$j]);
                    $cleanValue = preg_replace('/\s+/', '', $nextLine);
                    // 14-20 haneli alfanumerik değer, harf+rakam içermeli
                    if (
                        mb_strlen($cleanValue) >= 14 && mb_strlen($cleanValue) <= 20
                        && ctype_alnum($cleanValue)
                        && preg_match('/[A-Za-z]/', $cleanValue)
                        && preg_match('/\d/', $cleanValue)
                    ) {
                        $data['belge_no'] = mb_substr($cleanValue, 0, 16);
                        break 3;
                    }
                }
            }
        }
    }

    // ============ 3. TARİH ============
    // Tüm satırlarda GG-AA-YYYY formatı ara (Irsaliye/İrsaliye tarihi yakınında)
    // Önce label'ı bul (hem İ hem I ile)
    $tarihLabels = ($belgeTipi === 'irsaliye')
        ? ['irsaliye tarihi', 'ırsaliye tarihi']
        : ['fatura tarihi'];

    $labelFound = false;
    foreach ($lines as $i => $line) {
        $lineLower = turkishLower($line);
        foreach ($tarihLabels as $label) {
            if (stripos($lineLower, $label) !== false) {
                $labelFound = true;
                // Sonraki 50 satıra bak (OCR'da değer uzakta olabilir)
                for ($j = $i + 1; $j < min($i + 50, count($lines)); $j++) {
                    if (preg_match('/(\d{2})[-.\/ ](\d{2})[-.\/ ](\d{4})/', $lines[$j], $match)) {
                        $data['tarih'] = $match[3] . '-' . $match[2] . '-' . $match[1];
                        break 3;
                    }
                }
            }
        }
    }

    // Tarih bulunamadıysa, ilk tarih pattern'ini al
    if (empty($data['tarih'])) {
        foreach ($lines as $line) {
            if (preg_match('/(\d{2})[-.\/ ](\d{2})[-.\/ ](\d{4})/', $line, $match)) {
                $data['tarih'] = $match[3] . '-' . $match[2] . '-' . $match[1];
                break;
            }
        }
    }

    // ============ 4. ETTN ============
    // UUID formatında ETTN: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
    if (preg_match('/ETTN[:\s]*([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/i', $text, $match)) {
        $data['ettn'] = $match[1];
    }

    // ============ 5. MÜŞTERİ ============
    // SAYIN'dan sonraki satırlarda müşteri adını ara (50 satır ileri bak)
    foreach ($lines as $i => $line) {
        if (stripos(mb_strtoupper($line, 'UTF-8'), 'SAYIN') !== false) {
            for ($j = $i + 1; $j < min($i + 50, count($lines)); $j++) {
                $nextLine = trim($lines[$j]);
                if (!empty($nextLine) && mb_strlen($nextLine) > 15) {
                    // Label satırlarını atla (: ile biten)
                    if (substr($nextLine, -1) === ':') {
                        continue;
                    }
                    // Geçersiz satırları atla
                    $skipWords = ['senaryo', 'temel', 'tipi', 'fatura no', 'irsaliye no', 'belge no', 'özelleştirme', 'ozellestirme', 'sevk', 'vkn:', 'tckn:', 'vergi', 'tarih'];
                    $skip = false;
                    foreach ($skipWords as $sw) {
                        if (stripos(mb_strtolower($nextLine, 'UTF-8'), $sw) !== false) {
                            $skip = true;
                            break;
                        }
                    }
                    // Müşteri adı Türkçe/İngilizce harf içermeli, büyük harfle başlamalı
                    if (!$skip && preg_match('/^[A-ZİĞÜŞÖÇ][A-ZİĞÜŞÖÇa-zığüşöç\s\.]+/', $nextLine)) {
                        $data['musteri'] = mb_substr($nextLine, 0, 120, 'UTF-8');
                        break 2;
                    }
                }
            }
            break;
        }
    }

    // ============ 6. VKN/TCKN ============
    // Müşteri VKN - SAYIN bölümünden sonra ara (gönderen VKN değil müşteri VKN)
    $foundSayin = false;
    foreach ($lines as $i => $line) {
        // SAYIN satırını bul
        if (stripos(mb_strtoupper($line, 'UTF-8'), 'SAYIN') !== false) {
            $foundSayin = true;
            continue;
        }
        // SAYIN'dan sonra VKN ara
        if ($foundSayin && empty($data['vkn'])) {
            if (preg_match('/VKN[:\s]*(\d{10,11})/i', $line, $vknMatch)) {
                $data['vkn'] = $vknMatch[1];
                break;
            } else if (preg_match('/TCKN[:\s]*(\d{11})/i', $line, $tcknMatch)) {
                $data['vkn'] = $tcknMatch[1];
                break;
            }
        }
    }

    return $data;
}

/**
 * Türkçe lowercase - Python'daki turkish_lower fonksiyonu
 */
function turkishLower($text)
{
    $trMap = [
        'İ' => 'i',
        'I' => 'ı',
        'Ş' => 'ş',
        'Ğ' => 'ğ',
        'Ü' => 'ü',
        'Ö' => 'ö',
        'Ç' => 'ç'
    ];
    foreach ($trMap as $upper => $lower) {
        $text = str_replace($upper, $lower, $text);
    }
    return mb_strtolower($text, 'UTF-8');
}

/**
 * Boş belge oluştur
 */
function createEmptyDocument($relativePath, $thumbnailPath)
{
    return [
        'file_path' => $relativePath,
        'thumbnail' => $thumbnailPath,
        'belge_tipi' => 'fatura',
        'belge_no' => '',
        'tarih' => date('Y-m-d'),
        'musteri' => '',
        'doc_index' => 1
    ];
}

/**
 * Google OAuth2 access token al
 */
function getAccessToken($credentials)
{
    $tokenUri = 'https://oauth2.googleapis.com/token';
    $now = time();

    $header = base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $claim = base64UrlEncode(json_encode([
        'iss' => $credentials['client_email'],
        'scope' => 'https://www.googleapis.com/auth/cloud-platform',
        'aud' => $tokenUri,
        'exp' => $now + 3600,
        'iat' => $now
    ]));

    $signatureInput = $header . '.' . $claim;
    $privateKey = openssl_pkey_get_private($credentials['private_key']);

    if (!$privateKey) {
        return null;
    }

    openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $jwt = $signatureInput . '.' . base64UrlEncode($signature);

    $ch = curl_init($tokenUri);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

/**
 * URL-safe Base64 encoding
 */
function base64UrlEncode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * MIME type belirle
 */
function getMimeType($filePath)
{
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $types = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp'
    ];
    return $types[$ext] ?? 'application/octet-stream';
}
