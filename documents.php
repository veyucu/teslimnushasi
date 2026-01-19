<?php
/**
 * Belgeler - Doküman Listesi (AG-Grid)
 */
require_once 'includes/auth.php';
require_once 'includes/db.php';

requireLogin();

$user = currentUser();
$message = '';
$messageType = '';

// Silme işlemi (tekli veya çoklu)
if (isset($_POST['delete_ids'])) {
    $ids = json_decode($_POST['delete_ids'], true);
    if (is_array($ids) && count($ids) > 0) {
        try {
            foreach ($ids as $id) {
                // Sayfa dosyalarını sil
                $pages = db()->query("SELECT file_path FROM document_pages WHERE document_id = ?", [$id])->fetchAll();
                foreach ($pages as $page) {
                    $filePath = __DIR__ . '/' . $page['file_path'];
                    if (file_exists($filePath))
                        unlink($filePath);
                }
                // DB'den sil
                db()->query("DELETE FROM documents WHERE id = ? AND user_id = ?", [$id, $user['id']]);
            }
            $message = count($ids) . ' belge başarıyla silindi';
            $messageType = 'success';
        } catch (Exception $e) {
            $message = 'Silme işlemi başarısız: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// İstatistikler ve belgeler
$stats = ['total' => 0, 'fatura' => 0, 'irsaliye' => 0, 'fis' => 0];
$documents = [];

try {
    $sql = "SELECT d.id, d.public_id, d.document_type, d.document_no, d.document_date, d.customer_name, d.customer_vkn, d.page_count, d.created_at,
            (SELECT file_path FROM document_pages WHERE document_id = d.id AND page_number = 1 LIMIT 1) as first_page_path
            FROM documents d WHERE d.user_id = ? ORDER BY d.created_at DESC";
    $documents = db()->query($sql, [$user['id']])->fetchAll(PDO::FETCH_ASSOC);

    $stats['total'] = count($documents);
    $stats['fatura'] = count(array_filter($documents, fn($d) => $d['document_type'] === 'fatura'));
    $stats['irsaliye'] = count(array_filter($documents, fn($d) => $d['document_type'] === 'irsaliye'));
    $stats['fis'] = count(array_filter($documents, fn($d) => $d['document_type'] === 'fis'));
} catch (Exception $e) {
}

// Varsayılan tarihler
$defaultEndDate = date('Y-m-d');
$defaultStartDate = date('Y-m-d', strtotime('-7 days'));
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Belgeler - Teslim Nüshası</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.1/styles/ag-grid.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.1/styles/ag-theme-alpine.css">
    <!-- jsPDF for PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        /* Navbar with Stats */
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

        .nav-stats {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .nav-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 6px 16px;
            background: rgba(99, 102, 241, 0.08);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .nav-stat:hover {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateY(-1px);
        }

        .nav-stat .value {
            font-size: 1.1rem;
            font-weight: 700;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .nav-stat .label {
            font-size: 0.65rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Brand text - hide br on desktop */
        .navbar-brand .brand-text br {
            display: none;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        /* Grid Container */
        .grid-container {
            padding: 0;
        }

        #documentsGrid {
            height: calc(100vh - 160px);
            width: 100%;
            border-radius: var(--radius);
        }

        /* AG-Grid Pagination Area Custom */
        .ag-paging-panel {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            gap: 20px !important;
            padding-left: 1px !important;
        }

        .grid-actions-inline {
            display: flex;
            gap: 12px;
            align-items: center;
            order: -1;
            margin: 0;
            margin-right: auto;
        }

        .grid-actions-inline .btn {
            padding: 6px 14px;
            font-size: 0.8rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .grid-actions-inline .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-secondary);
        }

        .grid-actions-inline .btn-outline:hover:not(:disabled) {
            border-color: var(--primary);
            color: var(--primary-light);
            background: rgba(99, 102, 241, 0.1);
        }

        .grid-actions-inline .btn-danger {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }

        .grid-actions-inline .btn-danger:hover:not(:disabled) {
            background: rgba(239, 68, 68, 0.25);
            border-color: var(--danger);
            box-shadow: 0 0 12px rgba(239, 68, 68, 0.2);
        }

        .grid-actions-inline .btn:disabled {
            opacity: 0.35;
            cursor: not-allowed;
        }

        .grid-actions-inline .selection-count {
            color: var(--text-muted);
            font-size: 0.8rem;
            margin-left: 12px;
            font-weight: 500;
        }

        .grid-actions-inline .selection-count span {
            color: var(--primary-light);
            font-weight: 600;
        }

        /* Search Filters */
        .search-filters {
            display: flex;
            gap: 12px;
            margin-top: 12px;
            margin-bottom: 12px;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-group.expand {
            flex: 1;
        }

        .filter-group label {
            display: none;
        }

        .filter-group input {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px 14px;
            color: var(--text-primary);
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .filter-group input::placeholder {
            color: var(--text-muted);
        }

        .filter-group input:hover {
            border-color: var(--border-light);
        }

        .filter-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .filter-group input[type="date"] {
            width: 135px;
        }

        .filter-group input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.7);
            cursor: pointer;
        }

        .filter-group input[type="text"] {
            min-width: 120px;
        }

        .btn-filter {
            padding: 10px 18px;
            height: auto;
            white-space: nowrap;
            font-size: 0.85rem;
        }

        /* AG-Grid Premium Dark Theme */
        .ag-theme-alpine-dark {
            /* Core Colors */
            --ag-background-color: var(--bg-card);
            --ag-header-background-color: var(--bg-elevated);
            --ag-odd-row-background-color: rgba(255, 255, 255, 0.015);
            --ag-row-hover-color: rgba(99, 102, 241, 0.12);
            --ag-selected-row-background-color: rgba(99, 102, 241, 0.25);
            --ag-range-selection-background-color: rgba(99, 102, 241, 0.2);

            /* Borders */
            --ag-border-color: var(--border);
            --ag-row-border-color: var(--border);
            --ag-header-column-separator-color: var(--border-light);

            /* Typography */
            --ag-header-foreground-color: var(--text-secondary);
            --ag-foreground-color: var(--text-primary);
            --ag-secondary-foreground-color: var(--text-muted);

            /* Checkbox & Input */
            --ag-checkbox-checked-color: var(--primary);
            --ag-checkbox-unchecked-color: var(--text-muted);
            --ag-checkbox-background-color: var(--bg-input);
            --ag-input-focus-border-color: var(--primary);

            /* Misc */
            --ag-font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --ag-font-size: 13px;
            --ag-row-height: 42px;
            --ag-header-height: 44px;
            --ag-grid-size: 6px;
            --ag-icon-size: 16px;

            border-radius: var(--radius);
            overflow: hidden;
            border: 1px solid var(--border);
        }

        /* Header Styling */
        .ag-theme-alpine-dark .ag-header {
            background: linear-gradient(180deg, var(--bg-elevated) 0%, rgba(22, 22, 31, 0.95) 100%);
            border-bottom: 1px solid var(--border-light);
        }

        .ag-theme-alpine-dark .ag-header-cell {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            transition: color 0.2s ease;
            overflow: hidden;
            padding: 0 4px !important;
        }

        .ag-theme-alpine-dark .ag-header-cell-text {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 100%;
            text-align: center !important;
        }

        .ag-theme-alpine-dark .ag-cell-label-container {
            display: flex !important;
            justify-content: center !important;
            width: 100% !important;
        }

        .ag-theme-alpine-dark .ag-header-cell-comp-wrapper {
            display: flex !important;
            justify-content: center !important;
            width: 100% !important;
        }

        .ag-theme-alpine-dark .ag-header-cell-label {
            display: flex !important;
            justify-content: center !important;
            width: 100% !important;
        }

        /* Hide sort indicator to not push text off-center */
        .ag-theme-alpine-dark .ag-sort-indicator-container,
        .ag-theme-alpine-dark .ag-header-icon {
            display: none !important;
        }

        .ag-theme-alpine-dark .ag-header-cell:hover {
            color: var(--text-primary);
        }

        .ag-theme-alpine-dark .ag-header-cell-sorted-asc,
        .ag-theme-alpine-dark .ag-header-cell-sorted-desc {
            color: var(--primary-light) !important;
        }

        /* Row Styling */
        .ag-theme-alpine-dark .ag-row {
            border-bottom: 1px solid rgba(42, 42, 58, 0.5);
            transition: background-color 0.15s ease;
        }

        .ag-theme-alpine-dark .ag-row:hover {
            background-color: rgba(99, 102, 241, 0.08) !important;
        }

        .ag-theme-alpine-dark .ag-row-selected {
            background-color: rgba(99, 102, 241, 0.2) !important;
        }

        .ag-theme-alpine-dark .ag-row-selected:hover {
            background-color: rgba(99, 102, 241, 0.28) !important;
        }

        /* Cell Styling */
        .ag-theme-alpine-dark .ag-cell {
            display: flex;
            align-items: center;
            padding: 0 4px;
            overflow: hidden;
        }

        .ag-theme-alpine-dark .ag-cell-value {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 100%;
        }

        .ag-theme-alpine-dark .ag-cell-focus {
            border-color: var(--primary) !important;
        }

        /* Checkbox Styling */
        .ag-theme-alpine-dark .ag-checkbox-input-wrapper {
            width: 18px;
            height: 18px;
        }

        .ag-theme-alpine-dark .ag-checkbox-input-wrapper::after {
            color: var(--primary-light);
        }

        .ag-theme-alpine-dark .ag-checkbox-input-wrapper.ag-checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        /* Center checkbox in pinned column */
        .ag-theme-alpine-dark .ag-pinned-left-cols-container .ag-cell,
        .ag-theme-alpine-dark .ag-pinned-left-cols-container .ag-cell-wrapper {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            padding: 0 !important;
        }

        .ag-theme-alpine-dark .ag-pinned-left-cols-container .ag-selection-checkbox {
            margin: 0 !important;
        }

        .ag-theme-alpine-dark .ag-pinned-left-header .ag-header-cell,
        .ag-theme-alpine-dark .ag-pinned-left-header .ag-header-cell-comp-wrapper,
        .ag-theme-alpine-dark .ag-pinned-left-header .ag-header-select-all {
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }

        .ag-theme-alpine-dark .ag-pinned-left-header .ag-checkbox-input-wrapper {
            margin: 0 auto !important;
        }

        /* Hide empty wrapper that pushes header checkbox to left */
        .ag-theme-alpine-dark .ag-pinned-left-header .ag-header-cell.ag-column-first .ag-header-cell-comp-wrapper {
            display: none !important;
        }

        /* Pagination Panel Styling */
        .ag-theme-alpine-dark .ag-paging-panel {
            background: var(--bg-elevated);
            border-top: 1px solid var(--border);
            padding: 10px 16px;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .ag-theme-alpine-dark .ag-paging-button {
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-secondary);
            border-radius: 6px;
            padding: 4px 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .ag-theme-alpine-dark .ag-paging-button:hover:not(.ag-disabled) {
            border-color: var(--primary);
            color: var(--primary-light);
            background: rgba(99, 102, 241, 0.1);
        }

        .ag-theme-alpine-dark .ag-paging-button.ag-disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .ag-theme-alpine-dark .ag-paging-page-size-select {
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-primary);
            border-radius: 6px;
            padding: 4px 8px;
        }

        .ag-theme-alpine-dark .ag-paging-page-size-select:focus {
            border-color: var(--primary);
            outline: none;
        }

        /* Custom scrollbar for grid */
        .ag-theme-alpine-dark ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .ag-theme-alpine-dark ::-webkit-scrollbar-track {
            background: var(--bg-dark);
        }

        .ag-theme-alpine-dark ::-webkit-scrollbar-thumb {
            background: var(--border-light);
            border-radius: 4px;
        }

        .ag-theme-alpine-dark ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }

        /* Sort icons */
        .ag-theme-alpine-dark .ag-icon-asc,
        .ag-theme-alpine-dark .ag-icon-desc {
            color: var(--primary-light);
        }

        /* Loading overlay */
        .ag-theme-alpine-dark .ag-overlay-loading-center {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--text-primary);
            padding: 20px 30px;
        }

        /* No rows overlay */
        .ag-theme-alpine-dark .ag-overlay-no-rows-center {
            color: var(--text-muted);
        }

        .action-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 4px;
            color: var(--text-secondary);
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: var(--bg-elevated);
        }

        .action-btn.delete:hover {
            color: var(--danger);
        }

        .alert {
            margin-bottom: 16px;
            padding: 12px 16px;
            border-radius: 8px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.2);
            color: var(--danger);
        }

        /* Modal - Full Screen Style */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            width: 95%;
            max-width: 1200px;
            height: 90vh;
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .modal-header h3 {
            color: var(--text-primary);
            font-size: 1rem;
            font-weight: 500;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            font-size: 1.5rem;
            line-height: 1;
        }

        .modal-close:hover {
            background: var(--bg-elevated);
            color: var(--text-primary);
        }

        .modal-header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-sm {
            padding: 8px 14px;
            font-size: 0.8rem;
        }

        .modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #0a0a12;
        }

        .modal-body img {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }

        .page-image {
            max-width: 100%;
            margin-bottom: 10px;
            border-radius: 4px;
            cursor: zoom-in;
            transition: transform 0.3s ease;
        }

        .page-image.zoomed {
            max-width: none;
            width: 200%;
            cursor: zoom-out;
        }

        .modal-footer {
            padding: 12px 24px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .page-buttons {
            display: flex;
            gap: 6px;
        }

        .page-btn {
            width: 36px;
            height: 36px;
            border: 1px solid var(--border);
            background: var(--bg-elevated);
            color: var(--text-secondary);
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .page-btn:hover {
            border-color: var(--primary-light);
            color: var(--primary-light);
        }

        .page-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        @media (max-width: 768px) {
            .dashboard-nav {
                padding: 6px 0;
            }

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

            .nav-stats {
                display: none;
            }

            .nav-actions {
                gap: 8px;
                margin-left: auto;
            }

            .nav-actions .btn {
                padding: 6px 10px;
                font-size: 0.75rem;
            }

            .nav-actions .btn svg {
                width: 14px;
                height: 14px;
            }

            .nav-actions span {
                display: none;
            }

            .modal-body {
                flex-direction: column;
            }

            .modal-details {
                width: 100%;
            }

            /* Mobile Modal - Full Screen */
            .modal-overlay {
                padding: 0;
            }

            .modal-content {
                width: 100%;
                height: 100%;
                max-width: 100%;
                border-radius: 0;
            }

            .modal-header {
                padding: 8px 12px;
            }

            .modal-header h3 {
                font-size: 0.75rem;
                flex: 1;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                margin-right: 8px;
            }

            .modal-header-actions {
                gap: 8px;
            }

            .modal-header-actions .btn-sm {
                padding: 6px 10px;
                font-size: 0.7rem;
            }

            .modal-header-actions .btn-text {
                display: none;
            }

            .modal-close {
                font-size: 1.2rem;
                padding: 4px;
            }

            .modal-body {
                padding: 8px;
            }

            .search-filters {
                flex-wrap: wrap;
                gap: 6px;
                margin-top: 8px;
                margin-bottom: 8px;
            }

            .filter-group {
                flex: unset;
                min-width: unset;
                gap: 2px;
            }

            .filter-group label {
                font-size: 0.55rem;
            }

            .filter-group input {
                padding: 6px 8px;
                font-size: 0.75rem;
            }

            .filter-group input[type="date"] {
                width: 105px;
            }

            .filter-group input[type="text"] {
                min-width: 70px;
                flex: 1;
            }

            .filter-group.expand {
                flex: 1;
                min-width: unset;
            }

            .btn-filter {
                flex: unset;
                padding: 6px 10px;
                height: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .btn-filter .btn-text {
                display: none;
            }

            .btn-filter svg {
                width: 16px;
                height: 16px;
            }

            /* Mobile Grid Adjustments */
            .ag-theme-alpine-dark {
                --ag-header-height: 26px;
                --ag-row-height: 36px;
                --ag-font-size: 0.75rem;
            }

            #documentsGrid {
                height: calc(100vh - 100px);
            }

            .ag-theme-alpine-dark .ag-header {
                min-height: 26px !important;
                height: 26px !important;
            }

            .ag-theme-alpine-dark .ag-header-row {
                height: 26px !important;
            }

            .ag-theme-alpine-dark .ag-cell,
            .ag-theme-alpine-dark .ag-header-cell {
                font-size: 0.75rem !important;
            }

            /* Hide page_count and created_at columns on mobile */
            .ag-theme-alpine-dark [col-id="page_count"],
            .ag-theme-alpine-dark [col-id="created_at"] {
                display: none !important;
            }

            /* Hide page size selector on mobile */
            .ag-theme-alpine-dark .ag-paging-page-size {
                display: none !important;
            }

            /* Smaller pagination font on mobile */
            .ag-theme-alpine-dark .ag-paging-panel {
                font-size: 0.65rem !important;
                gap: 6px !important;
                padding: 6px 4px !important;
            }

            .grid-actions-inline {
                gap: 6px !important;
                margin-right: 8px !important;
            }

            .grid-actions-inline .btn {
                padding: 4px 8px !important;
                font-size: 0.7rem !important;
            }

            .ag-theme-alpine-dark .ag-paging-row-summary-panel,
            .ag-theme-alpine-dark .ag-paging-page-summary-panel {
                gap: 4px !important;
            }

            .ag-theme-alpine-dark .ag-paging-button {
                padding: 2px 4px !important;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <!-- Navbar -->
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
                <div class="nav-stats">
                    <div class="nav-stat">
                        <div class="value"><?= $stats['total'] ?></div>
                        <div class="label">Toplam</div>
                    </div>
                    <div class="nav-stat">
                        <div class="value"><?= $stats['fatura'] ?></div>
                        <div class="label">Fatura</div>
                    </div>
                    <div class="nav-stat">
                        <div class="value"><?= $stats['irsaliye'] ?></div>
                        <div class="label">İrsaliye</div>
                    </div>
                    <div class="nav-stat">
                        <div class="value"><?= $stats['fis'] ?></div>
                        <div class="label">Fiş</div>
                    </div>
                </div>
                <div class="nav-actions">
                    <?php if (isAdmin()): ?>
                        <a href="admin" class="btn btn-outline btn-sm" title="Admin Panel">🛡️</a>
                    <?php endif; ?>
                    <a href="upload" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Belge Yükle
                    </a>
                    <span style="color: var(--text-muted);"><?= htmlspecialchars($user['name']) ?></span>
                    <a href="logout" class="btn btn-outline btn-sm">Çıkış</a>
                </div>
            </div>
        </nav>

        <!-- Grid Content -->
        <div class="container grid-container">
            <?php if (!empty($message)): ?>
                <div id="autoHideAlert" class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
                <script>setTimeout(() => { const el = document.getElementById('autoHideAlert'); if (el) el.style.display = 'none'; }, 3000);</script>
            <?php endif; ?>

            <!-- Search Filters -->
            <div class="search-filters">
                <div class="filter-group">
                    <label>Başlangıç</label>
                    <input type="date" id="filterDateStart" value="<?= $defaultStartDate ?>" min="1900-01-01"
                        max="2099-12-31">
                </div>
                <div class="filter-group">
                    <label>Bitiş</label>
                    <input type="date" id="filterDateEnd" value="<?= $defaultEndDate ?>" min="1900-01-01"
                        max="2099-12-31">
                </div>
                <div class="filter-group expand">
                    <label>Ara</label>
                    <input type="text" id="filterSearch" placeholder="Belge no veya müşteri adı...">
                </div>
                <button class="btn btn-secondary btn-filter" onclick="applyFilters()" title="Filtrele">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <span class="btn-text">Filtrele</span>
                </button>
                <button class="btn btn-outline btn-filter" onclick="clearFilters()" title="Temizle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span class="btn-text">Temizle</span>
                </button>
            </div>

            <div id="documentsGrid" class="ag-theme-alpine-dark"></div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal-overlay" id="previewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Belge Önizleme</h3>
                <div class="modal-header-actions">
                    <button class="btn btn-outline btn-sm" id="copyLinkBtn" title="Paylaşım linkini kopyala">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        <span class="btn-text">Link</span>
                    </button>
                    <button class="btn btn-primary btn-sm" id="downloadBtn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <span class="btn-text">İndir</span>
                    </button>
                    <button class="modal-close" onclick="closeModal()">✕</button>
                </div>
            </div>
            <div class="modal-body" id="modalBody">
                <div id="pagesContainer"></div>
            </div>
        </div>
    </div>

    <!-- Hidden form for delete -->
    <form id="deleteForm" method="POST" style="display:none;">
        <input type="hidden" name="delete_ids" id="deleteIdsInput">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.1/dist/ag-grid-community.min.js"></script>
    <script>
        const { jsPDF } = window.jspdf;
        const rowData = <?= json_encode($documents) ?>;
        let currentDocId = null;
        let currentPages = [];
        let currentDocInfo = {};

        const columnDefs = [
            {
                headerCheckboxSelection: true,
                checkboxSelection: true,
                width: 36,
                pinned: 'left',
                sortable: false,
                filter: false,
                cellStyle: { display: 'flex', justifyContent: 'center', alignItems: 'center' },
                onCellClicked: (params) => {
                    params.node.setSelected(!params.node.isSelected());
                }
            },
            {
                field: 'document_type', headerName: 'Tip', width: 80,
                valueFormatter: p => p.value === 'fatura' ? 'Fatura' : (p.value === 'irsaliye' ? 'İrsaliye' : 'Fiş'),
                cellStyle: { display: 'flex', justifyContent: 'center', alignItems: 'center' }
            },
            { field: 'document_no', headerName: 'Belge No', width: 140 },
            {
                field: 'document_date', headerName: 'Tarih', width: 100,
                valueFormatter: p => p.value ? new Date(p.value).toLocaleDateString('tr-TR') : '-',
                cellStyle: { display: 'flex', justifyContent: 'center', alignItems: 'center' }
            },
            { field: 'customer_name', headerName: 'Müşteri', flex: 1 },
            {
                field: 'customer_vkn',
                headerName: 'VKN',
                width: 110,
                hide: window.innerWidth < 768,
                cellStyle: { display: 'flex', justifyContent: 'center', alignItems: 'center' }
            },
            {
                field: 'page_count',
                headerName: 'Sayfa',
                width: 70,
                cellRenderer: p => p.value || 1,
                cellStyle: { display: 'flex', justifyContent: 'center', alignItems: 'center' }
            },
            {
                field: 'created_at', headerName: 'Yüklenme', width: 140,
                valueFormatter: p => p.value ? new Date(p.value).toLocaleString('tr-TR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-'
            },
            {
                headerName: 'Link',
                width: 60,
                sortable: false,
                filter: false,
                cellRenderer: p => {
                    if (!p.data.public_id) return '-';
                    return `<button onclick="copyLink('${p.data.public_id}')" style="background:none;border:none;cursor:pointer;font-size:16px" title="Paylaşım linkini kopyala">🔗</button>`;
                },
                cellStyle: { display: 'flex', justifyContent: 'center', alignItems: 'center' }
            }
        ];

        const gridOptions = {
            columnDefs,
            rowData,
            defaultColDef: { sortable: true, resizable: true },
            enableCellTextSelection: true,
            ensureDomOrder: true,
            pagination: true,
            localeText: {
                page: 'Sayfa',
                of: '/',
                to: '-',
                pageSizeSelectorLabel: 'Sayfa Boyutu:',
                firstPage: 'İlk Sayfa',
                lastPage: 'Son Sayfa',
                nextPage: 'Sonraki',
                previousPage: 'Önceki'
            },
            paginationPageSize: 100,
            paginationPageSizeSelector: [50, 100, 200, 300],
            rowSelection: 'multiple',
            suppressRowClickSelection: true,
            onRowDoubleClicked: e => openDocument(e.data.id),
            onSelectionChanged: updateSelectionInfo,
            animateRows: true,
            onGridReady: (params) => {
                // Inject action buttons into pagination panel
                setTimeout(() => {
                    const pagingPanel = document.querySelector('.ag-paging-panel');
                    if (pagingPanel) {
                        const actionsDiv = document.createElement('div');
                        actionsDiv.className = 'grid-actions-inline';
                        actionsDiv.innerHTML = `
                            <span id="selectionCount" style="font-size:0.8rem;color:var(--primary);font-weight:600;min-width:20px;">0</span>
                            <button class="btn btn-outline" id="btnCopyLinks" disabled onclick="copySelectedLinks()">🔗 Link</button>
                            <button class="btn btn-outline" id="btnDownload" disabled onclick="downloadSelected()">⬇️ İndir</button>
                            <button class="btn btn-danger" id="btnDelete" disabled onclick="deleteSelected()">🗑️ Sil</button>
                        `;
                        pagingPanel.insertBefore(actionsDiv, pagingPanel.firstChild);
                    }

                    // Apply default date filter on page load
                    applyFilters();

                    // Focus on search field
                    document.getElementById('filterSearch').focus();

                    // Hide columns and adjust widths on mobile (requires page refresh for changes)
                    if (window.innerWidth <= 768) {
                        params.api.setColumnsVisible(['page_count', 'created_at'], false);

                        // Narrow columns on mobile
                        params.api.setColumnWidths([
                            { key: 'document_type', newWidth: 45 },
                            { key: 'document_no', newWidth: 115 },
                            { key: 'document_date', newWidth: 65 }
                        ]);

                        // Let customer column take remaining space
                        params.api.sizeColumnsToFit();
                    }
                }, 100);
            }
        };

        new agGrid.Grid(document.getElementById('documentsGrid'), gridOptions);

        // Sayfa yüklendiğinde kayıtlı filtre değerlerini yükle
        const savedDateStart = localStorage.getItem('tn_filterDateStart');
        const savedDateEnd = localStorage.getItem('tn_filterDateEnd');
        const savedSearch = localStorage.getItem('tn_filterSearch');

        if (savedDateStart) document.getElementById('filterDateStart').value = savedDateStart;
        if (savedDateEnd) document.getElementById('filterDateEnd').value = savedDateEnd;
        if (savedSearch) document.getElementById('filterSearch').value = savedSearch;

        // Filtre değişikliklerini kaydet
        document.getElementById('filterDateStart').addEventListener('change', function () {
            localStorage.setItem('tn_filterDateStart', this.value);
        });
        document.getElementById('filterDateEnd').addEventListener('change', function () {
            localStorage.setItem('tn_filterDateEnd', this.value);
        });
        document.getElementById('filterSearch').addEventListener('input', function () {
            localStorage.setItem('tn_filterSearch', this.value);
        });

        // Sayfa yüklendiğinde filtre uygula
        setTimeout(() => applyFilters(), 100);

        // Custom copy handler for grid cells
        document.getElementById('documentsGrid').addEventListener('keydown', function (e) {
            if (e.ctrlKey && e.key === 'c') {
                const focusedCell = gridOptions.api.getFocusedCell();
                if (focusedCell) {
                    const rowNode = gridOptions.api.getDisplayedRowAtIndex(focusedCell.rowIndex);
                    if (rowNode) {
                        const value = gridOptions.api.getValue(focusedCell.column, rowNode);
                        if (value !== null && value !== undefined) {
                            navigator.clipboard.writeText(String(value)).then(() => {
                            }).catch(err => {
                                // Fallback for older browsers
                                const textarea = document.createElement('textarea');
                                textarea.value = String(value);
                                document.body.appendChild(textarea);
                                textarea.select();
                                document.execCommand('copy');
                                document.body.removeChild(textarea);
                            });
                            e.preventDefault();
                        }
                    }
                }
            }
        });

        function updateSelectionInfo() {
            const selected = gridOptions.api.getSelectedRows();
            const count = selected.length;
            const countEl = document.getElementById('selectionCount');
            const btnDownload = document.getElementById('btnDownload');
            const btnDelete = document.getElementById('btnDelete');
            const btnCopyLinks = document.getElementById('btnCopyLinks');
            if (countEl) countEl.textContent = count;
            if (btnDownload) btnDownload.disabled = count === 0;
            if (btnDelete) btnDelete.disabled = count === 0;
            if (btnCopyLinks) btnCopyLinks.disabled = count === 0;
        }

        // Seçilen belgelerin linklerini kopyala
        function copySelectedLinks() {
            const selected = gridOptions.api.getSelectedRows();
            if (selected.length === 0) return;

            const links = selected
                .filter(doc => doc.public_id)
                .map(doc => window.location.origin + '/view/' + doc.public_id);

            if (links.length === 0) {
                alert('Seçilen belgelerde paylaşım linki yok');
                return;
            }

            copyToClipboard(links.join('\n'), links.length + ' belge linki kopyalandı!');
        }

        async function openDocument(id) {
            currentDocId = id;
            const res = await fetch(`api/get_document.php?id=${id}`);
            const doc = await res.json();
            if (!doc.success) return alert('Belge yüklenemedi');

            // Store doc info for PDF download and link copy
            currentDocInfo = {
                document_no: doc.document_no,
                document_date: doc.document_date,
                customer_name: doc.customer_name,
                public_id: doc.public_id
            };

            // Başlık: BelgeNo - Tarih - Müşteri
            const dateStr = doc.document_date ? new Date(doc.document_date).toLocaleDateString('tr-TR') : '';
            const titleParts = [doc.document_no, dateStr, doc.customer_name].filter(x => x);
            document.getElementById('modalTitle').textContent = titleParts.join(' - ') || 'Belge Önizleme';

            currentPages = doc.pages || [];

            // Show all pages stacked vertically
            const pagesContainer = document.getElementById('pagesContainer');
            pagesContainer.innerHTML = currentPages.map(p =>
                `<img src="${p.file_path}" alt="Sayfa" class="page-image" onclick="toggleZoom(this)">`
            ).join('');

            document.getElementById('previewModal').classList.add('active');

            // Scroll to bottom after modal opens
            setTimeout(() => {
                const modalBody = document.getElementById('modalBody');
                modalBody.scrollTop = modalBody.scrollHeight;
            }, 100);
        }

        function toggleZoom(img) {
            const allImages = document.querySelectorAll('.page-image');
            const isZoomed = img.classList.contains('zoomed');
            allImages.forEach(i => {
                if (isZoomed) {
                    i.classList.remove('zoomed');
                } else {
                    i.classList.add('zoomed');
                }
            });
        }

        function closeModal() { document.getElementById('previewModal').classList.remove('active'); }

        function deleteSingle(id) {
            if (confirm('Bu belgeyi silmek istediğinize emin misiniz?')) {
                document.getElementById('deleteIdsInput').value = JSON.stringify([id]);
                document.getElementById('deleteForm').submit();
            }
        }

        function deleteSelected() {
            const selected = gridOptions.api.getSelectedRows();
            if (selected.length === 0) return;

            if (confirm(`${selected.length} belge silinecektir. Emin misiniz?`)) {
                const ids = selected.map(r => r.id);
                document.getElementById('deleteIdsInput').value = JSON.stringify(ids);
                document.getElementById('deleteForm').submit();
            }
        }

        async function downloadSelected() {
            const selected = gridOptions.api.getSelectedRows();
            if (selected.length === 0) return;

            for (const doc of selected) {
                // Get document pages
                const res = await fetch(`api/get_document.php?id=${doc.id}`);
                const data = await res.json();
                if (!data.success || !data.pages || data.pages.length === 0) continue;

                // Create filename: BelgeNo_YYYYMMDD_Müşteri
                const dateStr = (doc.document_date || '').replace(/-/g, '');
                const customerName = (doc.customer_name || 'Belge').replace(/[^a-zA-Z0-9ğüşöçıİĞÜŞÖÇ ]/g, '').substring(0, 30);
                const docNo = (doc.document_no || 'Belge').replace(/[^a-zA-Z0-9-]/g, '');
                const fileName = `${docNo}_${dateStr}_${customerName}.pdf`;

                // Create PDF
                const pdf = new jsPDF('p', 'mm', 'a4');

                for (let i = 0; i < data.pages.length; i++) {
                    const page = data.pages[i];

                    // Load image
                    const img = await loadImage(page.file_path);

                    // Calculate dimensions to fit A4
                    const pageWidth = 210;
                    const pageHeight = 297;
                    const imgRatio = img.width / img.height;
                    const pageRatio = pageWidth / pageHeight;

                    let imgWidth, imgHeight;
                    if (imgRatio > pageRatio) {
                        imgWidth = pageWidth - 20;
                        imgHeight = imgWidth / imgRatio;
                    } else {
                        imgHeight = pageHeight - 20;
                        imgWidth = imgHeight * imgRatio;
                    }

                    const x = (pageWidth - imgWidth) / 2;
                    const y = (pageHeight - imgHeight) / 2;

                    if (i > 0) pdf.addPage();
                    pdf.addImage(img, 'JPEG', x, y, imgWidth, imgHeight);
                }

                pdf.save(fileName);

                // Small delay between downloads
                await new Promise(r => setTimeout(r, 500));
            }
        }

        function loadImage(src) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => resolve(img);
                img.onerror = reject;
                img.src = src;
            });
        }

        // Filter Functions
        function applyFilters() {
            let dateStart = document.getElementById('filterDateStart').value;
            let dateEnd = document.getElementById('filterDateEnd').value;
            const search = document.getElementById('filterSearch').value.toLowerCase();

            // Tarih validasyonu
            if (dateStart) {
                const yearStart = parseInt(dateStart.split('-')[0]);
                if (isNaN(yearStart) || yearStart < 1900 || yearStart > 2099) {
                    alert('Başlangıç tarihi geçersiz (yıl 1900-2099 arası olmalı)');
                    document.getElementById('filterDateStart').value = '';
                    dateStart = '';
                }
            }
            if (dateEnd) {
                const yearEnd = parseInt(dateEnd.split('-')[0]);
                if (isNaN(yearEnd) || yearEnd < 1900 || yearEnd > 2099) {
                    alert('Bitiş tarihi geçersiz (yıl 1900-2099 arası olmalı)');
                    document.getElementById('filterDateEnd').value = '';
                    dateEnd = '';
                }
            }

            const filteredData = rowData.filter(row => {
                if (dateStart && row.document_date < dateStart) return false;
                if (dateEnd && row.document_date > dateEnd) return false;
                if (search) {
                    const docNo = (row.document_no || '').toLowerCase();
                    const customer = (row.customer_name || '').toLowerCase();
                    if (!docNo.includes(search) && !customer.includes(search)) return false;
                }
                return true;
            });

            gridOptions.api.setRowData(filteredData);
        }

        function clearFilters() {
            // Sadece arama kutusunu temizle, tarihler olduğu gibi kalsın
            document.getElementById('filterSearch').value = '';

            // Mevcut tarih filtreleri ile grid'i güncelle
            applyFilters();
        }

        // Güvenli clipboard fonksiyonu (HTTPS olmadan da çalışır)
        function copyToClipboard(text, successMsg) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    alert(successMsg || 'Kopyalandı!');
                }).catch(() => {
                    fallbackCopy(text, successMsg);
                });
            } else {
                fallbackCopy(text, successMsg);
            }
        }

        function fallbackCopy(text, successMsg) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            try {
                document.execCommand('copy');
                alert(successMsg || 'Kopyalandı!');
            } catch (err) {
                prompt('Linki kopyalayın:', text);
            }
            document.body.removeChild(textarea);
        }

        // Paylaşım linkini kopyala
        function copyLink(publicId) {
            const url = window.location.origin + '/view/' + publicId;
            copyToClipboard(url, 'Link panoya kopyalandı!');
        }

        document.querySelectorAll('.filter-group input').forEach(input => {
            input.addEventListener('keypress', e => { if (e.key === 'Enter') applyFilters(); });
        });

        // Modal copy link button
        document.getElementById('copyLinkBtn').onclick = () => {
            if (!currentDocInfo.public_id) {
                alert('Bu belge için paylaşım linki mevcut değil');
                return;
            }
            copyLink(currentDocInfo.public_id);
        };

        // Modal download button - creates PDF
        document.getElementById('downloadBtn').onclick = async () => {
            if (!currentPages.length) return;

            // Create filename: BelgeNo_YYYYMMDD_Müşteri
            const dateStr = (currentDocInfo.document_date || '').replace(/-/g, '');
            const customerName = (currentDocInfo.customer_name || 'Belge').replace(/[^a-zA-Z0-9ğüşöçıİĞÜŞÖÇ ]/g, '').substring(0, 30);
            const docNo = (currentDocInfo.document_no || 'Belge').replace(/[^a-zA-Z0-9-]/g, '');
            const fileName = `${docNo}_${dateStr}_${customerName}.pdf`;

            // Create PDF
            const pdf = new jsPDF('p', 'mm', 'a4');

            for (let i = 0; i < currentPages.length; i++) {
                const page = currentPages[i];
                const img = await loadImage(page.file_path);

                const pageWidth = 210;
                const pageHeight = 297;
                const imgRatio = img.width / img.height;
                const pageRatio = pageWidth / pageHeight;

                let imgWidth, imgHeight;
                if (imgRatio > pageRatio) {
                    imgWidth = pageWidth - 10;
                    imgHeight = imgWidth / imgRatio;
                } else {
                    imgHeight = pageHeight - 10;
                    imgWidth = imgHeight * imgRatio;
                }

                const x = (pageWidth - imgWidth) / 2;
                const y = (pageHeight - imgHeight) / 2;

                if (i > 0) pdf.addPage();
                pdf.addImage(img, 'JPEG', x, y, imgWidth, imgHeight);
            }

            pdf.save(fileName);
        };

        document.addEventListener('keydown', e => e.key === 'Escape' && closeModal());
        document.getElementById('previewModal').onclick = e => e.target.classList.contains('modal-overlay') && closeModal();
    </script>
</body>

</html>