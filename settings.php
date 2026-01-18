<?php
/**
 * Admin Ayarları Sayfası
 */
require_once 'includes/auth.php';
requireAdmin();

$user = currentUser();

// Mevcut ayarları al
$settings = [
    'default_document_limit' => getSetting('default_document_limit', '100'),
    'require_email_verification' => getSetting('require_email_verification', '1'),
    'smtp_host' => getSetting('smtp_host', ''),
    'smtp_port' => getSetting('smtp_port', '587'),
    'smtp_username' => getSetting('smtp_username', ''),
    'smtp_password' => getSetting('smtp_password', ''),
    'smtp_from_email' => getSetting('smtp_from_email', ''),
    'smtp_from_name' => getSetting('smtp_from_name', 'Teslim Nüshası'),
];

$success = '';
$error = '';

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        setSetting('default_document_limit', $_POST['default_document_limit'] ?? '100');
        setSetting('require_email_verification', isset($_POST['require_email_verification']) ? '1' : '0');
        setSetting('smtp_host', $_POST['smtp_host'] ?? '');
        setSetting('smtp_port', $_POST['smtp_port'] ?? '587');
        setSetting('smtp_username', $_POST['smtp_username'] ?? '');
        if (!empty($_POST['smtp_password'])) {
            setSetting('smtp_password', $_POST['smtp_password']);
        }
        setSetting('smtp_from_email', $_POST['smtp_from_email'] ?? '');
        setSetting('smtp_from_name', $_POST['smtp_from_name'] ?? 'Teslim Nüshası');

        $success = 'Ayarlar başarıyla kaydedildi!';

        // Ayarları yeniden yükle
        $settings = [
            'default_document_limit' => getSetting('default_document_limit', '100'),
            'require_email_verification' => getSetting('require_email_verification', '1'),
            'smtp_host' => getSetting('smtp_host', ''),
            'smtp_port' => getSetting('smtp_port', '587'),
            'smtp_username' => getSetting('smtp_username', ''),
            'smtp_password' => getSetting('smtp_password', ''),
            'smtp_from_email' => getSetting('smtp_from_email', ''),
            'smtp_from_name' => getSetting('smtp_from_name', 'Teslim Nüshası'),
        ];
    } catch (Exception $e) {
        $error = 'Ayarlar kaydedilirken hata oluştu.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ayarlar - Admin Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .settings-section {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .settings-section h2 {
            color: var(--text-primary);
            font-size: 1.25rem;
            margin: 0 0 20px 0;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
        }

        .form-group.checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group.checkbox input {
            width: auto;
        }

        .form-group.checkbox label {
            margin: 0;
            color: var(--text-primary);
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .admin-header h1 {
            margin: 0;
            color: var(--text-primary);
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard">
        <nav class="dashboard-nav">
            <div class="container">
                <a href="documents.php" class="navbar-brand">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="brand-text">Teslim <br>Nüshası</span>
                </a>
                <div class="nav-actions">
                    <a href="admin.php" class="btn btn-outline btn-sm">Kullanıcılar</a>
                    <a href="documents.php" class="btn btn-outline btn-sm">Belgeler</a>
                    <a href="logout.php" class="btn btn-outline btn-sm">Çıkış</a>
                </div>
            </div>
        </nav>

        <div class="dashboard-content">
            <div class="container">
                <div class="settings-container">
                    <div class="admin-header">
                        <h1>⚙️ Sistem Ayarları</h1>
                    </div>

                    <?php if ($success): ?>
                        <div class="alert alert-success" style="margin-bottom:20px;">
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger" style="margin-bottom:20px;">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="settings-section">
                            <h2>📄 Genel Ayarlar</h2>
                            <div class="form-group">
                                <label>Varsayılan Belge Limiti</label>
                                <input type="number" name="default_document_limit"
                                    value="<?= htmlspecialchars($settings['default_document_limit']) ?>" min="1"
                                    required>
                            </div>
                            <div class="form-group checkbox">
                                <input type="checkbox" name="require_email_verification" id="require_email_verification"
                                    <?= $settings['require_email_verification'] === '1' ? 'checked' : '' ?>>
                                <label for="require_email_verification">Yeni kullanıcılar için email doğrulama
                                    zorunlu</label>
                            </div>
                        </div>

                        <div class="settings-section">
                            <h2>📧 SMTP Email Ayarları</h2>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>SMTP Sunucu</label>
                                    <input type="text" name="smtp_host"
                                        value="<?= htmlspecialchars($settings['smtp_host']) ?>"
                                        placeholder="smtp.gmail.com">
                                </div>
                                <div class="form-group">
                                    <label>Port</label>
                                    <input type="number" name="smtp_port"
                                        value="<?= htmlspecialchars($settings['smtp_port']) ?>" placeholder="587">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Kullanıcı Adı</label>
                                    <input type="text" name="smtp_username"
                                        value="<?= htmlspecialchars($settings['smtp_username']) ?>"
                                        placeholder="email@gmail.com">
                                </div>
                                <div class="form-group">
                                    <label>Şifre (değiştirmek için girin)</label>
                                    <input type="password" name="smtp_password" placeholder="••••••••">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Gönderen Email</label>
                                    <input type="email" name="smtp_from_email"
                                        value="<?= htmlspecialchars($settings['smtp_from_email']) ?>"
                                        placeholder="noreply@example.com">
                                </div>
                                <div class="form-group">
                                    <label>Gönderen Adı</label>
                                    <input type="text" name="smtp_from_name"
                                        value="<?= htmlspecialchars($settings['smtp_from_name']) ?>"
                                        placeholder="Teslim Nüshası">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">Ayarları Kaydet</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>