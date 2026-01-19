<?php
/**
 * Admin Paneli
 * Kullanıcı yönetimi ve belge limitleri
 */
require_once 'includes/auth.php';
requireAdmin();

$user = currentUser();

// Kullanıcı listesi
$users = db()->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM documents WHERE user_id = u.id) as document_count
    FROM users u 
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Teslim Nüshası</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-community@31.0.1/dist/ag-grid-community.min.js"></script>
    <style>
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .admin-header h1 {
            margin: 0;
            color: var(--text-primary);
        }

        .admin-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            flex: 1;
            min-width: 150px;
        }

        .stat-card h3 {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin: 0 0 8px 0;
            font-weight: 500;
        }

        .stat-card .value {
            color: var(--text-primary);
            font-size: 2rem;
            font-weight: 700;
        }

        .grid-container {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
        }

        #usersGrid {
            height: 500px;
            width: 100%;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: var(--bg-card);
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            padding: 24px;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h3 {
            margin: 0;
            color: var(--text-primary);
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 24px;
            cursor: pointer;
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
            font-size: 0.95rem;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 24px;
        }

        .ag-theme-alpine-dark {
            --ag-background-color: var(--bg-card);
            --ag-header-background-color: var(--bg-secondary);
            --ag-odd-row-background-color: var(--bg-secondary);
            --ag-row-hover-color: rgba(99, 102, 241, 0.1);
            --ag-border-color: var(--border);
            --ag-header-foreground-color: var(--text-muted);
            --ag-foreground-color: var(--text-primary);
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
                    <a href="settings" class="btn btn-outline btn-sm">⚙️ Ayarlar</a>
                    <a href="documents" class="btn btn-outline btn-sm">Belgeler</a>
                    <span style="color: var(--text-muted);">
                        <?= htmlspecialchars($user['name']) ?>
                    </span>
                    <a href="logout" class="btn btn-outline btn-sm">Çıkış</a>
                </div>
            </div>
        </nav>

        <div class="dashboard-content">
            <div class="container">
                <div class="admin-header">
                    <h1>🛡️ Admin Panel</h1>
                    <button class="btn btn-primary" onclick="openAddUserModal()">+ Yeni Kullanıcı</button>
                </div>

                <div class="admin-stats">
                    <div class="stat-card">
                        <h3>Toplam Kullanıcı</h3>
                        <div class="value">
                            <?= count($users) ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <h3>Toplam Belge</h3>
                        <div class="value">
                            <?= array_sum(array_column($users, 'document_count')) ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <h3>Admin Sayısı</h3>
                        <div class="value">
                            <?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?>
                        </div>
                    </div>
                </div>

                <div class="grid-container">
                    <div id="usersGrid" class="ag-theme-alpine-dark"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kullanıcı Düzenleme Modal -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Kullanıcı Düzenle</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="userForm">
                <input type="hidden" id="userId">
                <div class="form-group">
                    <label>Ad Soyad</label>
                    <input type="text" id="userName" required>
                </div>
                <div class="form-group">
                    <label>E-posta</label>
                    <input type="email" id="userEmail" required>
                </div>
                <div class="form-group">
                    <label>Şifre (boş bırakılırsa değişmez)</label>
                    <input type="password" id="userPassword">
                </div>
                <div class="form-group">
                    <label>Rol</label>
                    <select id="userRole">
                        <option value="user">Kullanıcı</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Durum</label>
                    <select id="userActive">
                        <option value="1">Aktif</option>
                        <option value="0">Pasif</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Belge Limiti</label>
                    <input type="number" id="userLimit" min="0" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">İptal</button>
                    <button type="submit" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const rowData = <?= json_encode($users) ?>;

        const columnDefs = [
            { field: 'id', headerName: 'ID', width: 70 },
            { field: 'name', headerName: 'Ad Soyad', flex: 1 },
            { field: 'email', headerName: 'E-posta', flex: 1 },
            {
                field: 'email_verified', headerName: '✉️', width: 60,
                cellRenderer: p => p.value ? '✅' : '❌',
                cellStyle: { textAlign: 'center' }
            },
            {
                field: 'is_active', headerName: 'Durum', width: 80,
                cellRenderer: p => p.value ? '🟢 Aktif' : '🔴 Pasif',
                cellStyle: { textAlign: 'center' }
            },
            {
                field: 'role', headerName: 'Rol', width: 100,
                cellRenderer: p => p.value === 'admin' ? '🛡️ Admin' : '👤 User'
            },
            {
                field: 'document_count', headerName: 'Belge', width: 80,
                cellStyle: { textAlign: 'center' }
            },
            {
                field: 'document_limit', headerName: 'Limit', width: 80,
                cellStyle: { textAlign: 'center' }
            },
            {
                headerName: 'Kullanım',
                width: 100,
                valueGetter: p => {
                    const count = p.data.document_count || 0;
                    const limit = p.data.document_limit || 100;
                    return Math.round((count / limit) * 100) + '%';
                },
                cellStyle: p => {
                    const count = p.data.document_count || 0;
                    const limit = p.data.document_limit || 100;
                    const pct = (count / limit) * 100;
                    return {
                        textAlign: 'center',
                        color: pct >= 90 ? '#ef4444' : pct >= 70 ? '#f59e0b' : '#22c55e'
                    };
                }
            },
            {
                field: 'last_login', headerName: 'Son Giriş', width: 140,
                valueFormatter: p => p.value ? new Date(p.value).toLocaleString('tr-TR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-'
            },
            {
                headerName: 'İşlem',
                width: 120,
                cellRenderer: p => `
                    ${!p.data.email_verified ? `<button onclick="verifyUser(${p.data.id})" style="background:none;border:none;cursor:pointer;font-size:14px" title="Email Doğrula">✅</button>` : ''}
                    <button onclick="editUser(${p.data.id})" style="background:none;border:none;cursor:pointer;font-size:14px" title="Düzenle">✏️</button>
                    <button onclick="deleteUser(${p.data.id}, '${p.data.name}')" style="background:none;border:none;cursor:pointer;font-size:14px" title="Sil">🗑️</button>
                `
            }
        ];

        const gridOptions = {
            columnDefs,
            rowData,
            defaultColDef: { sortable: true, resizable: true },
            pagination: true,
            paginationPageSize: 20
        };

        new agGrid.Grid(document.getElementById('usersGrid'), gridOptions);

        // Modal functions
        let isNewUser = false;

        function openAddUserModal() {
            isNewUser = true;
            document.getElementById('modalTitle').textContent = 'Yeni Kullanıcı';
            document.getElementById('userId').value = '';
            document.getElementById('userName').value = '';
            document.getElementById('userEmail').value = '';
            document.getElementById('userPassword').value = '';
            document.getElementById('userRole').value = 'user';
            document.getElementById('userActive').value = '1';
            document.getElementById('userLimit').value = 100;
            document.getElementById('editModal').style.display = 'flex';
        }

        function editUser(id) {
            const user = rowData.find(u => u.id == id);
            if (!user) return;

            isNewUser = false;
            document.getElementById('modalTitle').textContent = 'Kullanıcı Düzenle';
            document.getElementById('userId').value = user.id;
            document.getElementById('userName').value = user.name;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userPassword').value = '';
            document.getElementById('userRole').value = user.role || 'user';
            document.getElementById('userActive').value = user.is_active !== undefined ? (user.is_active ? '1' : '0') : '1';
            document.getElementById('userLimit').value = user.document_limit || 100;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        document.getElementById('userForm').onsubmit = async (e) => {
            e.preventDefault();

            const data = {
                id: document.getElementById('userId').value,
                name: document.getElementById('userName').value,
                email: document.getElementById('userEmail').value,
                password: document.getElementById('userPassword').value,
                role: document.getElementById('userRole').value,
                is_active: parseInt(document.getElementById('userActive').value),
                document_limit: parseInt(document.getElementById('userLimit').value)
            };

            const url = isNewUser ? 'api/admin/add_user.php' : 'api/admin/update_user.php';

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await res.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.message);
                }
            } catch (err) {
                alert('Bir hata oluştu');
            }
        };

        async function deleteUser(id, name) {
            if (!confirm(`"${name}" kullanıcısını silmek istediğinize emin misiniz? Tüm belgeleri de silinecek!`)) return;

            try {
                const res = await fetch('api/admin/delete_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const result = await res.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.message);
                }
            } catch (err) {
                alert('Bir hata oluştu');
            }
        }

        async function verifyUser(id) {
            if (!confirm('Bu kullanıcının emailini manuel olarak doğrulamak istediğinize emin misiniz?')) return;

            try {
                const res = await fetch('api/admin/verify_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const result = await res.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert(result.message);
                }
            } catch (err) {
                alert('Bir hata oluştu');
            }
        }
    </script>
</body>

</html>