<?php
/**
 * Toplu Belge Yükleme Sayfası
 * PDF.js ile sayfa bölme + Document AI ile OCR + Otomatik belge gruplandırma
 */
require_once 'includes/auth.php';
require_once 'includes/documents.php';

requireLogin();

$user = currentUser();
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Belge Yükle - Teslim Nüshası</title>
    <link rel="icon" type="image/png" href="/favicon.png"><link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="icon" type="image/png" href="/favicon.png"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        /* Navbar Styles - Same as Documents */
        .dashboard-nav {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 8px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .dashboard-nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 5px;
        }

        .navbar-brand .brand-text br {
            display: none;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .btn-sm {
            padding: 8px 14px;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            .dashboard-nav .container {
                flex-wrap: nowrap;
                justify-content: space-between;
                padding: 0 6px;
            }

            .navbar-brand {
                font-size: 0.8rem;
                gap: 6px;
                line-height: 1.15;
            }

            .navbar-brand .brand-text br {
                display: inline;
            }

            .navbar-brand svg {
                width: 24px;
                height: 24px;
            }

            .nav-actions {
                gap: 8px;
                margin-left: auto;
            }

            .nav-actions .btn {
                padding: 6px 10px;
                font-size: 0.75rem;
            }

            .nav-actions span {
                display: none;
            }
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: var(--bg-card);
            border-radius: 12px;
            width: calc(100% - 40px);
            max-width: none;
            height: 85vh;
            min-width: 600px;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
        }

        .modal-header h3 {
            margin: 0;
            color: var(--text-primary);
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 28px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
        }

        .modal-close:hover {
            color: var(--text-primary);
        }

        .modal-body {
            padding: 20px;
        }

        /* Results Cards Styles */
        .results-cards {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .result-card {
            display: flex;
            gap: 16px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px;
        }

        .result-card-left {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .result-card-left .thumbnail {
            width: 70px;
            height: 90px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid var(--border);
            cursor: pointer;
        }

        .result-card-left .page-count {
            font-size: 11px;
            color: var(--text-muted);
        }

        .result-card-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .result-row {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .result-field {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .result-field.flex-grow {
            flex: 1;
        }

        .result-field label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .result-field input,
        .result-field select {
            padding: 6px 8px;
            border: 1px solid var(--border);
            border-radius: 4px;
            background: var(--bg-card);
            color: var(--text-primary);
            font-size: 13px;
        }

        .result-field input:focus,
        .result-field select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn-remove {
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 6px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-remove:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        /* Error ve Hover Stilleri */
        .result-card.error {
            border-color: #ef4444;
            background: rgba(239, 68, 68, 0.05);
        }

        .result-card.error .error-message {
            display: block;
        }

        .result-card:hover {
            border-color: var(--primary);
            background: var(--bg-card);
        }

        .error-message {
            display: none;
            color: #ef4444;
            font-size: 11px;
            margin-top: 4px;
            padding: 4px 8px;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 4px;
        }

        /* Checkbox Stili */
        .save-checkbox {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-left: auto;
        }

        .save-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .save-checkbox label {
            font-size: 12px;
            color: var(--text-secondary);
            cursor: pointer;
        }

        .upload-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2rem;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .page-header p {
            color: var(--text-secondary);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            margin-bottom: 20px;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: var(--primary-light);
        }

        .upload-zone {
            background: var(--bg-card);
            border: 2px dashed var(--border);
            border-radius: var(--radius-lg);
            padding: 60px 40px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-bottom: 30px;
        }

        .upload-zone:hover,
        .upload-zone.dragover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }

        .upload-zone svg {
            width: 64px;
            height: 64px;
            color: var(--primary-light);
            margin-bottom: 20px;
        }

        .upload-zone h3 {
            font-size: 1.5rem;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .processing-status {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
        }

        .processing-status h3 {
            color: var(--text-primary);
            margin-bottom: 10px;
        }

        .processing-status p {
            color: var(--text-secondary);
            margin-bottom: 20px;
        }

        .progress-container {
            background: var(--border);
            border-radius: 10px;
            height: 8px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .progress-bar {
            height: 100%;
            background: var(--gradient-primary);
            transition: width 0.3s ease;
        }

        .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid var(--border);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .documents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .document-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .document-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }

        .document-preview {
            display: flex;
            gap: 4px;
            padding: 12px;
            background: #f8f9fa;
            min-height: 120px;
            overflow-x: auto;
        }

        .document-preview img {
            height: 100px;
            width: auto;
            border-radius: 4px;
            border: 1px solid var(--border);
        }

        .document-info {
            padding: 16px;
        }

        .document-info .doc-title {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .document-info .doc-meta {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .results-section {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 30px;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
        }

        .results-table th,
        .results-table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .results-table th {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .results-table input,
        .results-table select {
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 8px 12px;
            color: var(--text-primary);
            font-size: 0.9rem;
            width: 100%;
        }

        .results-table input:focus,
        .results-table select:focus {
            border-color: var(--primary);
            outline: none;
        }

        .thumbnail {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid var(--border);
        }

        .action-bar {
            display: flex;
            gap: 16px;
            justify-content: flex-end;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            margin-top: 20px;
        }

        .file-remove {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 8px;
        }

        .file-remove:hover {
            color: var(--danger);
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <nav class="dashboard-nav">
            <div class="container">
                <a href="documents" class="navbar-brand">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="brand-text">Teslim <br>Nüshası</span>
                </a>
                <div class="nav-actions">
                    <span style="color: var(--text-muted);"><?= htmlspecialchars($user['name']) ?></span>
                    <a href="logout" class="btn btn-outline btn-sm">Çıkış</a>
                </div>
            </div>
        </nav>

        <div class="dashboard-content">
            <div class="container">
                <div class="upload-container">
                    <a href="documents" class="back-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Belgelere Dön
                    </a>

                    <div class="page-header">
                        <h1>Toplu Belge Yükle</h1>
                        <p>PDF dosyalarınızı yükleyin, Yapay Zeka ile analiz edilecek ve belgeler otomatik ayrılacak</p>
                    </div>

                    <!-- Step 1: Upload -->
                    <div id="step1">
                        <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fileInput').click()">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <h3>PDF dosyalarınızı buraya sürükleyin</h3>
                            <p>veya tıklayarak seçin</p>
                            <button type="button" class="btn btn-secondary">Dosya Seç</button>
                            <input type="file" id="fileInput" accept=".pdf,.jpg,.jpeg,.png" multiple
                                style="display:none">
                        </div>

                        <!-- Seçilen Dosyalar Listesi -->
                        <div id="selectedFilesContainer" style="display:none; margin-top:20px;">
                            <div class="selected-files-header"
                                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                                <h3 style="color:var(--text-primary); margin:0;">Seçilen Dosyalar</h3>
                                <button type="button" class="btn btn-outline btn-sm"
                                    onclick="clearSelectedFiles()">Temizle</button>
                            </div>
                            <div id="selectedFilesList"
                                style="background:var(--bg-secondary); border:1px solid var(--border); border-radius:8px; max-height:200px; overflow-y:auto;">
                            </div>
                            <div class="action-bar" style="margin-top:16px;">
                                <button type="button" class="btn btn-outline"
                                    onclick="clearSelectedFiles()">İptal</button>
                                <button type="button" class="btn btn-primary" id="analyzeBtn" onclick="startAnalysis()">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    Analiz Et
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Processing -->
                    <div id="step2" style="display: none;">
                        <div class="processing-status">
                            <div class="spinner"></div>
                            <h3 id="processingTitle">İşleniyor...</h3>
                            <p id="processingText">Lütfen bekleyin</p>
                            <div class="progress-container">
                                <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                            </div>
                            <small id="progressDetail" style="color: var(--text-muted);"></small>
                        </div>
                    </div>

                    <!-- Step 3: Results -->
                    <div class="results-section" id="step3" style="display: none;">
                        <div class="results-header">
                            <h3 style="color: var(--text-primary);">Tespit Edilen Belgeler</h3>
                            <span id="resultsCount" style="color: var(--text-secondary);"></span>
                        </div>
                        <div id="resultsBody" class="results-cards"></div>
                        <div class="action-bar">
                            <button type="button" class="btn btn-outline" onclick="location.reload()">İptal</button>
                            <button type="button" class="btn btn-primary" id="saveBtn" onclick="saveDocuments()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Tümünü Kaydet
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Belge Önizleme Modal -->
    <div id="previewModal" class="modal-overlay" style="display:none">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="previewModalTitle">Belge Önizleme</h3>
                <button type="button" class="modal-close" onclick="closePreviewModal()">&times;</button>
            </div>
            <div class="modal-body" id="previewModalBody">
            </div>
        </div>
    </div>

    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        // UUID Generator
        function generateUUID() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }

        let allPages = [];      // Tüm sayfalar [{uuid, blob, dataUrl, webpBlob, webpDataUrl}]
        let documents = [];     // Gruplandırılmış belgeler
        let processedResults = [];
        let selectedFiles = []; // Seçilen dosyalar

        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');

        // Drag & Drop
        uploadZone.addEventListener('dragover', (e) => { e.preventDefault(); uploadZone.classList.add('dragover'); });
        uploadZone.addEventListener('dragleave', () => { uploadZone.classList.remove('dragover'); });
        uploadZone.addEventListener('drop', (e) => { e.preventDefault(); uploadZone.classList.remove('dragover'); handleFiles(e.dataTransfer.files); });
        fileInput.addEventListener('change', () => { handleFiles(fileInput.files); });

        // Dosyaları seçim listesine ekle (işleme başlatma)
        function handleFiles(files) {
            for (let file of files) {
                if (file.type === 'application/pdf' || file.type.startsWith('image/')) {
                    // Aynı dosya eklenmiş mi kontrol et
                    if (!selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                        selectedFiles.push(file);
                    }
                }
            }
            showSelectedFiles();
        }

        // Seçilen dosyaları göster
        function showSelectedFiles() {
            if (selectedFiles.length === 0) {
                document.getElementById('selectedFilesContainer').style.display = 'none';
                return;
            }

            document.getElementById('selectedFilesContainer').style.display = 'block';
            const list = document.getElementById('selectedFilesList');
            list.innerHTML = selectedFiles.map((file, i) => `
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; border-bottom:1px solid var(--border);">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="var(--text-muted)">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span style="color:var(--text-primary);">${file.name}</span>
                        <span style="color:var(--text-muted); font-size:12px;">(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                    </div>
                    <button type="button" onclick="removeFile(${i})" style="background:none; border:none; color:var(--text-muted); cursor:pointer; padding:4px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            `).join('');
        }

        // Tek dosya kaldır
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            showSelectedFiles();
        }

        // Tüm dosyaları temizle
        function clearSelectedFiles() {
            selectedFiles = [];
            showSelectedFiles();
            fileInput.value = '';
        }

        // Analiz Et butonuna basılınca işleme başla
        async function startAnalysis() {
            if (selectedFiles.length === 0) return;

            allPages = [];
            documents = [];
            processedResults = [];

            // Step geçişleri
            document.getElementById('step1').style.display = 'none';
            document.getElementById('step2').style.display = 'block';

            // 1. PDF sayfalarını çıkar
            updateProgress(5, 'PDF sayfaları çıkarılıyor...');
            for (let file of selectedFiles) {
                if (file.type === 'application/pdf') {
                    const pages = await extractPdfPages(file);
                    allPages.push(...pages);
                } else if (file.type.startsWith('image/')) {
                    const dataUrl = await readFileAsDataUrl(file);
                    const webpDataUrl = await convertToWebP(dataUrl);
                    const uuid = generateUUID();
                    allPages.push({
                        uuid,
                        dataUrl,
                        webpDataUrl,
                        sourceFile: file.name,
                        pageNum: 1
                    });
                }
            }

            // 1.5. Belge limiti kontrolü (API'ye göndermeden ÖNCE)
            updateProgress(10, 'Belge limiti kontrol ediliyor...');
            try {
                const limitRes = await fetch(`api/check_limit.php?count=${allPages.length}`);
                const limitData = await limitRes.json();
                if (!limitData.can_add) {
                    alert(limitData.message);
                    // Geri dön
                    document.getElementById('step2').style.display = 'none';
                    document.getElementById('step1').style.display = 'block';
                    return;
                }
            } catch (e) {
                console.error('Limit kontrol hatası:', e);
            }

            // 2. Her sayfayı Document AI'ya gönder (PARALLEL - 4 aynı anda)
            updateProgress(20, 'Yapay Zeka ile analiz ediliyor...');
            const BATCH_SIZE = 4; // Aynı anda işlenecek sayfa sayısı

            for (let i = 0; i < allPages.length; i += BATCH_SIZE) {
                const batch = allPages.slice(i, i + BATCH_SIZE);
                const batchNum = Math.floor(i / BATCH_SIZE) + 1;
                const totalBatches = Math.ceil(allPages.length / BATCH_SIZE);

                updateProgress(20 + Math.round((i / allPages.length) * 60),
                    `Grup ${batchNum}/${totalBatches} analiz ediliyor (${batch.length} sayfa)...`);

                // Batch içindeki tüm sayfaları paralel olarak işle
                const results = await Promise.all(
                    batch.map(page => analyzeWithDocumentAI(page.webpDataUrl, page.uuid))
                );

                // Sonuçları sayfalara ata
                batch.forEach((page, idx) => {
                    page.ocrText = results[idx].text || '';
                    page.extractedData = results[idx].data || {};
                });
            }

            // 3. Header kontrolü ile belgeleri grupla
            updateProgress(85, 'Belgeler gruplandırılıyor...');
            documents = groupDocumentsByHeader(allPages);

            // 4. Sonuçları göster
            updateProgress(100, 'Tamamlandı!');
            showResults();
        }

        async function extractPdfPages(file) {
            const arrayBuffer = await file.arrayBuffer();
            const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
            const numPages = pdf.numPages;

            // Sayfa işleme fonksiyonu
            async function processPage(pageNum) {
                const page = await pdf.getPage(pageNum);
                const scale = 2.0;
                const viewport = page.getViewport({ scale });

                const canvas = document.createElement('canvas');
                canvas.width = viewport.width;
                canvas.height = viewport.height;
                const ctx = canvas.getContext('2d');

                await page.render({ canvasContext: ctx, viewport }).promise;

                // Direkt WebP olarak al (PNG ara adımını atla)
                const webpDataUrl = canvas.toDataURL('image/webp', 0.85);
                // Önizleme için düşük kalite PNG (daha hızlı)
                const dataUrl = canvas.toDataURL('image/jpeg', 0.7);
                const uuid = generateUUID();

                return { uuid, dataUrl, webpDataUrl, sourceFile: file.name, pageNum };
            }

            // Paralel işleme - 4'lük batch'ler halinde
            const pages = [];
            const PARALLEL_PAGES = 4;

            for (let i = 1; i <= numPages; i += PARALLEL_PAGES) {
                const batchPromises = [];
                for (let j = i; j < Math.min(i + PARALLEL_PAGES, numPages + 1); j++) {
                    batchPromises.push(processPage(j));
                }

                const batchResults = await Promise.all(batchPromises);
                pages.push(...batchResults);

                updateProgress(5 + Math.round((Math.min(i + PARALLEL_PAGES - 1, numPages) / numPages) * 15),
                    `${file.name} - Sayfa ${Math.min(i + PARALLEL_PAGES - 1, numPages)}/${numPages}`);
            }

            return pages;
        }

        async function analyzeWithDocumentAI(webpDataUrl, uuid) {
            try {
                const blob = dataUrlToBlob(webpDataUrl);
                const formData = new FormData();
                formData.append('file', blob, `${uuid}.webp`);

                const response = await fetch('api/analyze.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success && result.documents && result.documents.length > 0) {
                    const doc = result.documents[0];
                    return {
                        text: result.ocr_text || '',
                        data: {
                            belge_tipi: doc.belge_tipi || '',
                            belge_no: doc.belge_no || '',
                            tarih: doc.tarih || '',
                            musteri: doc.musteri || '',
                            ettn: doc.ettn || '',
                            vkn: doc.vkn || ''
                        }
                    };
                }
            } catch (e) {
                console.error('Document AI error:', e);
            }
            return { text: '', data: {} };
        }

        /**
         * Türkçe karakterleri normalize et
         */
        function normalizeTurkish(text) {
            return text
                .replace(/İ/g, 'I').replace(/ı/g, 'i')
                .replace(/Ş/g, 'S').replace(/ş/g, 's')
                .replace(/Ğ/g, 'G').replace(/ğ/g, 'g')
                .replace(/Ü/g, 'U').replace(/ü/g, 'u')
                .replace(/Ö/g, 'O').replace(/ö/g, 'o')
                .replace(/Ç/g, 'C').replace(/ç/g, 'c');
        }

        /**
         * Header sayfası mı kontrol et
         */
        function isHeaderPage(text) {
            const textNorm = normalizeTurkish(text.toUpperCase());

            const hasEttn = textNorm.includes('ETTN');
            const hasSayin = textNorm.includes('SAYIN');
            const hasBelgeTipi = ['E-FATURA', 'E FATURA', 'EFATURA', 'E-ARSIV', 'E ARSIV', 'E-IRSALIYE', 'E IRSALIYE', 'EIRSALIYE']
                .some(x => textNorm.includes(x));

            const score = [hasEttn, hasSayin, hasBelgeTipi].filter(x => x).length;
            console.log(`Header check: score=${score}`, { hasEttn, hasSayin, hasBelgeTipi });
            return score >= 2;
        }

        /**
         * Belgeleri header'a göre grupla
         * Aynı sourceFile'dan gelen sayfalar sırayla kontrol edilir
         */
        function groupDocumentsByHeader(pages) {
            const docs = [];
            let currentDoc = null;
            let currentSourceFile = null;

            for (let i = 0; i < pages.length; i++) {
                const page = pages[i];
                const isHeader = isHeaderPage(page.ocrText);
                const isDifferentFile = currentSourceFile !== page.sourceFile;

                // Yeni dosya veya header sayfası = yeni belge başlat
                if (isDifferentFile || isHeader) {
                    if (currentDoc) {
                        docs.push(currentDoc);
                    }
                    currentDoc = {
                        pages: [page],
                        data: page.extractedData,
                        sourceFile: page.sourceFile
                    };
                    currentSourceFile = page.sourceFile;
                } else {
                    // Mevcut belgeye ekle (aynı dosyadan ve header değil)
                    currentDoc.pages.push(page);
                }
            }

            if (currentDoc) {
                docs.push(currentDoc);
            }

            console.log(`${docs.length} belge tespit edildi`);
            return docs;
        }

        function showResults() {
            document.getElementById('step2').style.display = 'none';
            document.getElementById('step3').style.display = 'block';
            document.getElementById('resultsCount').textContent = `${documents.length} belge tespit edildi`;

            processedResults = documents.map((doc, index) => ({
                ...doc.data,
                pages: doc.pages,
                preview: doc.pages[0].dataUrl
            }));

            const container = document.getElementById('resultsBody');
            container.innerHTML = processedResults.map((result, index) => `
                <div class="result-card" id="card_${index}" data-index="${index}">
                    <div class="result-card-left">
                        <img src="${result.preview}" class="thumbnail" alt="Önizleme" onclick="previewDocument(${index})" title="Tıklayın önizleme için">
                        <span class="page-count">${result.pages.length} sayfa</span>
                    </div>
                    <div class="result-card-right">
                        <div class="result-row">
                            <div class="result-field">
                                <label>Belge Tipi</label>
                                <select id="type_${index}" onchange="validateCard(${index})">
                                    <option value="fatura" ${result.belge_tipi === 'fatura' ? 'selected' : ''}>Fatura</option>
                                    <option value="irsaliye" ${result.belge_tipi === 'irsaliye' ? 'selected' : ''}>İrsaliye</option>
                                </select>
                            </div>
                            <div class="result-field">
                                <label>Belge No</label>
                                <input type="text" id="no_${index}" value="${result.belge_no || ''}" placeholder="Belge No" onchange="validateCard(${index})">
                            </div>
                            <div class="result-field">
                                <label>Tarih</label>
                                <input type="date" id="date_${index}" value="${result.tarih || ''}" min="1900-01-01" max="2099-12-31" onchange="validateCard(${index})">
                            </div>
                            <div class="result-field flex-grow">
                                <label>Müşteri</label>
                                <input type="text" id="customer_${index}" value="${result.musteri || ''}" placeholder="Müşteri Adı" onchange="validateCard(${index})">
                            </div>
                        </div>
                        <div class="result-row">
                            <div class="result-field">
                                <label>VKN/TCKN</label>
                                <input type="text" id="vkn_${index}" value="${result.vkn || ''}" placeholder="VKN/TCKN" onchange="validateCard(${index})">
                            </div>
                            <div class="result-field flex-grow">
                                <label>ETTN</label>
                                <input type="text" id="ettn_${index}" value="${result.ettn || ''}" placeholder="ETTN" onchange="validateCard(${index})">
                            </div>
                            <button type="button" class="btn-remove" onclick="removeResult(${index})" title="Kaldır">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                        <div class="error-message" id="error_${index}"></div>
                    </div>
                </div>
            `).join('');
            // Tüm kartları validate et ve duplikat kontrolü yap
            checkDuplicatesAndValidate();
        }

        // Tek kart için validasyon
        function validateCard(index) {
            const card = document.getElementById(`card_${index}`);
            const errorDiv = document.getElementById(`error_${index}`);
            const saveCheckbox = document.getElementById(`save_${index}`);

            const belgeNo = document.getElementById(`no_${index}`).value.trim();
            const tarih = document.getElementById(`date_${index}`).value;
            const musteri = document.getElementById(`customer_${index}`).value.trim();

            const errors = [];

            // Eksik veri kontrolü
            if (!belgeNo) errors.push('Belge No eksik');
            if (!tarih) {
                errors.push('Tarih eksik');
            } else {
                // Tarih geçerlilik kontrolü
                const year = parseInt(tarih.split('-')[0]);
                if (isNaN(year) || year < 1900 || year > 2099) {
                    errors.push('Geçersiz tarih (yıl 1900-2099 arası olmalı)');
                    document.getElementById(`date_${index}`).value = '';
                }
            }
            if (!musteri) errors.push('Müşteri eksik');

            if (errors.length > 0) {
                card.classList.add('error');
                errorDiv.textContent = errors.join(', ');
            } else {
                // Sadece eksik veri hatalarını temizle, duplikat hatalarını korumak için kontrol et
                const currentError = errorDiv.textContent;
                if (!currentError.includes('veritabanında') && !currentError.includes('batch içinde')) {
                    card.classList.remove('error');
                    errorDiv.textContent = '';
                }
            }

            // Kaydet butonunu güncelle
            updateSaveButton();
        }

        // Tüm kartlar için duplikat ve validasyon kontrolü
        async function checkDuplicatesAndValidate() {
            // Önce eksik veri kontrolü
            for (let i = 0; i < processedResults.length; i++) {
                validateCard(i);
            }

            // Duplikat kontrolü için API'ye gönderilecek veri
            const docsForCheck = processedResults.map((result, i) => ({
                document_type: document.getElementById(`type_${i}`).value,
                document_no: document.getElementById(`no_${i}`).value,
                ettn: document.getElementById(`ettn_${i}`).value
            }));

            try {
                const response = await fetch('api/check_duplicates.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ documents: docsForCheck })
                });

                const result = await response.json();

                if (result.success && result.duplicates) {
                    for (const [index, reasons] of Object.entries(result.duplicates)) {
                        const card = document.getElementById(`card_${index}`);
                        const errorDiv = document.getElementById(`error_${index}`);

                        card.classList.add('error');
                        errorDiv.textContent = reasons.join('; ');
                    }
                }
            } catch (e) {
                console.error('Duplikat kontrolü hatası:', e);
            }

            // Kaydet butonunu güncelle
            updateSaveButton();
        }

        // Kaydet butonunu güncelle - kırmızı belge varsa devre dışı
        function updateSaveButton() {
            const hasError = document.querySelectorAll('.result-card.error').length > 0;
            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = hasError;
            if (hasError) {
                saveBtn.title = 'Tüm hataları düzeltmeniz gerekiyor';
            } else {
                saveBtn.title = '';
            }
        }

        function removeResult(index) {
            processedResults.splice(index, 1);
            documents.splice(index, 1);
            showResults();
        }

        function previewDocument(index) {
            const result = processedResults[index];
            if (!result || !result.pages) return;

            const modal = document.getElementById('previewModal');
            const body = document.getElementById('previewModalBody');
            const title = document.getElementById('previewModalTitle');

            title.textContent = `Belge Önizleme - ${result.belge_no || 'Belge ' + (index + 1)}`;

            // Sol tarafta resimler (geniş), sağ tarafta form alanları
            body.innerHTML = `
                <div style="display:flex;gap:20px;height:calc(85vh - 80px);overflow:hidden">
                    <div style="width:calc(100% - 300px);overflow-y:auto;padding-right:10px">
                        ${result.pages.map((page, i) =>
                `<img src="${page.dataUrl}" onclick="zoomImage(this)" style="width:100%;margin-bottom:10px;border:1px solid #333;border-radius:4px;cursor:zoom-in" alt="Sayfa ${i + 1}" title="Büyütmek için tıklayın">`
            ).join('')}
                    </div>
                    <div style="width:280px;display:flex;flex-direction:column;gap:10px;flex-shrink:0">
                        <div class="result-field">
                            <label style="color:var(--text-muted);font-size:12px">Belge Tipi</label>
                            <select id="modal_type" style="width:100%;padding:8px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:4px;color:var(--text-primary)">
                                <option value="fatura" ${document.getElementById('type_' + index).value === 'fatura' ? 'selected' : ''}>Fatura</option>
                                <option value="irsaliye" ${document.getElementById('type_' + index).value === 'irsaliye' ? 'selected' : ''}>İrsaliye</option>
                            </select>
                        </div>
                        <div class="result-field">
                            <label style="color:var(--text-muted);font-size:12px">Belge No</label>
                            <input type="text" id="modal_no" value="${document.getElementById('no_' + index).value}" style="width:100%;padding:8px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:4px;color:var(--text-primary)">
                        </div>
                        <div class="result-field">
                            <label style="color:var(--text-muted);font-size:12px">Tarih</label>
                            <input type="date" id="modal_date" value="${document.getElementById('date_' + index).value}" min="1900-01-01" max="2099-12-31" style="width:100%;padding:8px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:4px;color:var(--text-primary)">
                        </div>
                        <div class="result-field">
                            <label style="color:var(--text-muted);font-size:12px">Müşteri</label>
                            <input type="text" id="modal_customer" value="${document.getElementById('customer_' + index).value}" style="width:100%;padding:8px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:4px;color:var(--text-primary)">
                        </div>
                        <div class="result-field">
                            <label style="color:var(--text-muted);font-size:12px">VKN/TCKN</label>
                            <input type="text" id="modal_vkn" value="${document.getElementById('vkn_' + index).value}" style="width:100%;padding:8px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:4px;color:var(--text-primary)">
                        </div>
                        <div class="result-field">
                            <label style="color:var(--text-muted);font-size:12px">ETTN</label>
                            <input type="text" id="modal_ettn" value="${document.getElementById('ettn_' + index).value}" style="width:100%;padding:8px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:4px;color:var(--text-primary);font-size:11px">
                        </div>
                        <button type="button" onclick="applyModalChanges(${index})" style="margin-top:auto;padding:10px;background:var(--primary);color:white;border:none;border-radius:4px;cursor:pointer">Değişiklikleri Uygula</button>
                    </div>
                </div>
            `;

            modal.style.display = 'flex';
        }

        function applyModalChanges(index) {
            document.getElementById('type_' + index).value = document.getElementById('modal_type').value;
            document.getElementById('no_' + index).value = document.getElementById('modal_no').value;
            document.getElementById('date_' + index).value = document.getElementById('modal_date').value;
            document.getElementById('customer_' + index).value = document.getElementById('modal_customer').value;
            document.getElementById('vkn_' + index).value = document.getElementById('modal_vkn').value;
            document.getElementById('ettn_' + index).value = document.getElementById('modal_ettn').value;

            validateCard(index);
            closePreviewModal();
        }

        function closePreviewModal() {
            document.getElementById('previewModal').style.display = 'none';
        }

        // Resmi kutu içinde büyütme/küçültme fonksiyonu
        function zoomImage(img) {
            if (img.style.transform === 'scale(2)') {
                // Küçült
                img.style.transform = 'scale(1)';
                img.style.cursor = 'zoom-in';
                img.style.transformOrigin = 'center';
            } else {
                // Büyüt
                img.style.transform = 'scale(2)';
                img.style.cursor = 'zoom-out';
                img.style.transformOrigin = 'top left';
            }
        }

        // Yardımcı fonksiyonlar
        function updateProgress(percent, detail) {
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressDetail').textContent = detail;
        }

        function readFileAsDataUrl(file) {
            return new Promise((resolve) => {
                const reader = new FileReader();
                reader.onload = (e) => resolve(e.target.result);
                reader.readAsDataURL(file);
            });
        }

        function loadImage(dataUrl) {
            return new Promise((resolve) => {
                const img = new Image();
                img.onload = () => resolve(img);
                img.src = dataUrl;
            });
        }

        async function convertToWebP(dataUrl) {
            const img = await loadImage(dataUrl);
            const canvas = document.createElement('canvas');
            canvas.width = img.width;
            canvas.height = img.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0);
            return canvas.toDataURL('image/webp', 0.75);
        }

        function dataUrlToBlob(dataUrl) {
            const parts = dataUrl.split(',');
            const mime = parts[0].match(/:(.*?);/)[1];
            const bstr = atob(parts[1]);
            let n = bstr.length;
            const u8arr = new Uint8Array(n);
            while (n--) {
                u8arr[n] = bstr.charCodeAt(n);
            }
            return new Blob([u8arr], { type: mime });
        }

        async function saveDocuments() {
            if (processedResults.length === 0) return;

            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = true;
            saveBtn.textContent = 'Kaydediliyor...';

            const documentsToSave = [];

            for (let i = 0; i < processedResults.length; i++) {
                const result = processedResults[i];
                const uploadedPages = [];

                // Her sayfayı ayrı ayrı WebP olarak yükle
                for (let p = 0; p < result.pages.length; p++) {
                    const page = result.pages[p];
                    const webpBlob = dataUrlToBlob(page.webpDataUrl);

                    const formData = new FormData();
                    formData.append('file', webpBlob, `${page.uuid}.webp`);

                    try {
                        const uploadResponse = await fetch('api/upload_image.php', {
                            method: 'POST',
                            body: formData
                        });
                        const uploadResult = await uploadResponse.json();

                        if (uploadResult.success) {
                            uploadedPages.push({
                                file_path: uploadResult.file_path,
                                page_number: p + 1
                            });
                        }
                    } catch (e) {
                        console.error(e);
                    }
                }

                if (uploadedPages.length > 0) {
                    documentsToSave.push({
                        document_type: document.getElementById(`type_${i}`).value,
                        document_no: document.getElementById(`no_${i}`).value,
                        document_date: document.getElementById(`date_${i}`).value,
                        customer_name: document.getElementById(`customer_${i}`).value,
                        customer_vkn: document.getElementById(`vkn_${i}`).value,
                        ettn: document.getElementById(`ettn_${i}`).value,
                        pages: uploadedPages
                    });
                }
            }

            try {
                const response = await fetch('api/save_documents.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ documents: documentsToSave })
                });

                const result = await response.json();

                if (result.success) {
                    alert(`${result.saved} belge başarıyla kaydedildi!`);
                    window.location.href = 'documents.php';
                } else {
                    alert('Kayıt sırasında hata oluştu: ' + result.message);
                }
            } catch (error) {
                alert('Bir hata oluştu');
                console.error(error);
            }

            saveBtn.disabled = false;
            saveBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Tümünü Kaydet';
        }
    </script>
</body>

</html>