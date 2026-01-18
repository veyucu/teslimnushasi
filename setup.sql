-- Teslim Nüshası - Veritabanı Kurulum
-- MySQL'de çalıştırın

-- Veritabanı oluştur
CREATE DATABASE IF NOT EXISTS teslimnushasi CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
USE teslimnushasi;

-- Kullanıcılar tablosu
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    company VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Belgeler tablosu (ana belge bilgileri)
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type ENUM('fatura', 'irsaliye') NOT NULL,
    document_no VARCHAR(50) NOT NULL,
    document_date DATE NOT NULL,
    customer_name VARCHAR(255) DEFAULT NULL,
    customer_vkn VARCHAR(15) DEFAULT NULL,
    ettn VARCHAR(50) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    page_count INT DEFAULT 1,
    ocr_text TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_document_no (document_no),
    INDEX idx_document_date (document_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Belge sayfaları tablosu (her sayfa ayrı WebP dosyası)
CREATE TABLE IF NOT EXISTS document_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    page_number INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    INDEX idx_document_id (document_id),
    UNIQUE KEY unique_doc_page (document_id, page_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Demo kullanıcı (şifre: 123456)
INSERT INTO users (name, email, password, company) VALUES 
('Demo Kullanıcı', 'demo@teslimnushasi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Demo Şirket')
ON DUPLICATE KEY UPDATE name=name;

-- Mevcut tabloya sayfa tablosu eklemek için (ALTER)
-- ALTER TABLE documents ADD COLUMN page_count INT DEFAULT 1 AFTER notes;
-- ALTER TABLE documents ADD COLUMN ettn VARCHAR(50) DEFAULT NULL AFTER customer_name;

