<?php
/**
 * Public Belge Görüntüleme
 * Login gerektirmeden belge görüntüleme ve PDF indirme
 */
require_once 'includes/db.php';

$publicId = $_GET['id'] ?? '';

if (empty($publicId)) {
    http_response_code(404);
    die('Belge bulunamadı');
}

// Belgeyi bul
try {
    $document = db()->query(
        "SELECT d.*, 
                (SELECT GROUP_CONCAT(file_path ORDER BY page_number SEPARATOR '|') FROM document_pages WHERE document_id = d.id) as page_paths
         FROM documents d 
         WHERE d.public_id = ? 
         LIMIT 1",
        [$publicId]
    )->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        http_response_code(404);
        die('Belge bulunamadı');
    }

    $pages = $document['page_paths'] ? explode('|', $document['page_paths']) : [];

} catch (Exception $e) {
    http_response_code(500);
    die('Bir hata oluştu');
}

// Tarih formatla
$tarih = $document['document_date'] ? date('d.m.Y', strtotime($document['document_date'])) : '';
$belgeBaslik = $document['document_no'] ?: 'Belge';
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars($belgeBaslik) ?> - Belge Görüntüleme
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        :root {
            --bg-primary: #0a0a0f;
            --bg-secondary: #12121a;
            --bg-card: #1a1a24;
            --border: #2a2a3a;
            --text-primary: #ffffff;
            --text-secondary: #a0a0b0;
            --text-muted: #6a6a7a;
            --primary: #6366f1;
            --primary-hover: #5558e8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            padding: 20px;
            margin: 0;
        }

        .view-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .view-header {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
            font-size: 1.25rem;
            font-weight: 600;
        }

        .brand svg {
            color: var(--primary);
        }

        .view-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .pages-container {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .page-item {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        .page-item img {
            width: 100%;
            display: block;
        }

        .page-number {
            text-align: center;
            padding: 8px;
            color: var(--text-muted);
            font-size: 0.85rem;
            border-top: 1px solid var(--border);
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            .view-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .view-actions {
                width: 100%;
            }

            .btn {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <div class="view-container">
        <div class="view-header">
            <a href="documents.php" class="brand" style="text-decoration:none;">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Teslim Nüshası</span>
            </a>
            <div class="view-actions">
                <button class="btn btn-primary" onclick="downloadAsPdf()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    PDF İndir
                </button>
            </div>
        </div>

        <div class="pages-container" id="pagesContainer">
            <?php foreach ($pages as $index => $pagePath): ?>
                <div class="page-item">
                    <img src="<?= htmlspecialchars($pagePath) ?>" alt="Sayfa <?= $index + 1 ?>" class="page-image">
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        const { jsPDF } = window.jspdf;
        const documentNo = <?= json_encode($document['document_no'] ?: 'Belge') ?>;
        const customerName = <?= json_encode($document['customer_name'] ?: '') ?>;
        const documentDate = <?= json_encode($document['document_date'] ?: '') ?>;

        async function downloadAsPdf() {
            const btn = document.querySelector('.btn-primary');
            btn.disabled = true;
            btn.innerHTML = '<span>Hazırlanıyor...</span>';

            try {
                const images = document.querySelectorAll('.page-image');
                const pdf = new jsPDF('p', 'mm', 'a4');
                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();

                for (let i = 0; i < images.length; i++) {
                    if (i > 0) pdf.addPage();

                    const img = images[i];
                    const canvas = document.createElement('canvas');
                    canvas.width = img.naturalWidth;
                    canvas.height = img.naturalHeight;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0);

                    const imgData = canvas.toDataURL('image/jpeg', 0.85);
                    const imgRatio = img.naturalHeight / img.naturalWidth;
                    const pdfRatio = pageHeight / pageWidth;

                    let imgWidth = pageWidth;
                    let imgHeight = pageWidth * imgRatio;

                    if (imgHeight > pageHeight) {
                        imgHeight = pageHeight;
                        imgWidth = pageHeight / imgRatio;
                    }

                    const x = (pageWidth - imgWidth) / 2;
                    const y = (pageHeight - imgHeight) / 2;

                    pdf.addImage(imgData, 'JPEG', x, y, imgWidth, imgHeight);
                }

                // Dosya adı oluştur - Türkçe karakter dönüşümü
                function turkishToAscii(str) {
                    const map = { 'ğ': 'g', 'ü': 'u', 'ş': 's', 'ö': 'o', 'ç': 'c', 'ı': 'i', 'İ': 'I', 'Ğ': 'G', 'Ü': 'U', 'Ş': 'S', 'Ö': 'O', 'Ç': 'C' };
                    return str.replace(/[ğüşöçıİĞÜŞÖÇ]/g, c => map[c] || c);
                }
                const dateStr = documentDate ? documentDate.replace(/-/g, '') : '';
                const safeCustomer = turkishToAscii(customerName).replace(/[^a-zA-Z0-9 ]/g, '').trim();
                const safeDocNo = turkishToAscii(documentNo).replace(/[^a-zA-Z0-9-]/g, '');
                const fileName = [safeDocNo, dateStr, safeCustomer].filter(x => x).join('_') + '.pdf';

                pdf.save(fileName);
            } catch (e) {
                console.error('PDF oluşturma hatası:', e);
                alert('PDF oluşturulamadı');
            }

            btn.disabled = false;
            btn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                PDF İndir
            `;
        }
    </script>
</body>

</html>