<?php
/**
 * Kimlik Doğrulama Fonksiyonları
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings.php';

/**
 * Kullanıcı girişi
 */
function login($email, $password)
{
    $user = db()->fetch("SELECT * FROM users WHERE email = ?", [$email]);

    if ($user && password_verify($password, $user['password'])) {
        // Aktif kullanıcı kontrolü
        if (isset($user['is_active']) && !$user['is_active']) {
            return ['success' => false, 'message' => 'Hesabınız pasif durumda. Lütfen yöneticiyle iletişime geçin.'];
        }

        // Email doğrulama kontrolü
        if (isEmailVerificationRequired() && empty($user['email_verified'])) {
            return ['success' => false, 'message' => 'Email adresiniz doğrulanmamış. Lütfen email kutunuzu kontrol edin.', 'need_verification' => true];
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'] ?? 'user';
        $_SESSION['document_limit'] = $user['document_limit'] ?? 100;
        $_SESSION['logged_in'] = true;

        // Son giriş zamanını güncelle
        db()->query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);

        return ['success' => true];
    }
    return ['success' => false, 'message' => 'Email veya şifre hatalı.'];
}

/**
 * Kullanıcı kaydı
 */
function register($name, $email, $password)
{
    // E-posta kontrolü
    $existing = db()->fetch("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existing) {
        return ['success' => false, 'message' => 'Bu e-posta adresi zaten kayıtlı.'];
    }

    // Şifreyi hashle
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Varsayılan limit
    $defaultLimit = getDefaultDocumentLimit();

    // Email doğrulama gerekli mi?
    $requireVerification = isEmailVerificationRequired();
    $verificationToken = $requireVerification ? generateToken() : null;
    $emailVerified = $requireVerification ? 0 : 1;

    // Kullanıcıyı ekle
    db()->query(
        "INSERT INTO users (name, email, password, role, document_limit, email_verified, verification_token, created_at) VALUES (?, ?, ?, 'user', ?, ?, ?, NOW())",
        [$name, $email, $hashedPassword, $defaultLimit, $emailVerified, $verificationToken]
    );

    // Doğrulama emaili gönder
    if ($requireVerification && $verificationToken) {
        sendVerificationEmail($email, $name, $verificationToken);
        return ['success' => true, 'message' => 'Kayıt başarılı! Email adresinize gönderilen doğrulama linkine tıklayın.'];
    }

    return ['success' => true, 'message' => 'Kayıt başarılı! Giriş yapabilirsiniz.'];
}

/**
 * Çıkış yap
 */
function logout()
{
    session_destroy();
    session_start();
}

/**
 * Giriş yapılmış mı kontrol et
 */
function isLoggedIn()
{
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Admin mi kontrol et
 */
function isAdmin()
{
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Giriş gerektiren sayfalarda kullan
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login');
        exit;
    }
}

/**
 * Admin gerektiren sayfalarda kullan
 */
function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        header('Location: documents');
        exit;
    }
}

/**
 * Mevcut kullanıcı bilgilerini al
 */
function currentUser()
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'] ?? 'user',
        'document_limit' => $_SESSION['document_limit'] ?? 100
    ];
}

/**
 * Kullanıcının belge sayısını al
 */
function getDocumentCount($userId)
{
    $result = db()->fetch("SELECT COUNT(*) as count FROM documents WHERE user_id = ?", [$userId]);
    return $result ? (int) $result['count'] : 0;
}

/**
 * Kullanıcı belge ekleyebilir mi kontrol et
 */
function canAddDocument($userId, $count = 1)
{
    $user = db()->fetch("SELECT document_limit FROM users WHERE id = ?", [$userId]);
    $limit = $user ? (int) $user['document_limit'] : 100;
    $current = getDocumentCount($userId);
    return ($current + $count) <= $limit;
}

/**
 * Kullanıcının kalan belge hakkını al
 */
function getRemainingDocuments($userId)
{
    $user = db()->fetch("SELECT document_limit FROM users WHERE id = ?", [$userId]);
    $limit = $user ? (int) $user['document_limit'] : 100;
    $current = getDocumentCount($userId);
    return max(0, $limit - $current);
}
