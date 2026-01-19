-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 18 Oca 2026, 21:33:33
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `teslimnushasi`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `public_id` varchar(12) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `document_type` enum('fatura','irsaliye') NOT NULL,
  `document_no` varchar(50) NOT NULL,
  `document_date` date NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_vkn` varchar(15) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `page_count` int(11) DEFAULT 1,
  `ettn` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `document_pages`
--

CREATE TABLE `document_pages` (
  `id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `page_number` int(11) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'default_document_limit', '100', '2026-01-18 23:33:20'),
(2, 'require_email_verification', '1', '2026-01-18 23:33:20'),
(3, 'smtp_host', 'bendis.alastyr.com', '2026-01-18 23:33:20'),
(4, 'smtp_port', '465', '2026-01-18 23:33:20'),
(5, 'smtp_username', 'info@teslimnushasi.com', '2026-01-18 23:33:20'),
(6, 'smtp_password', 't49U0IgkwNG+D3GyquD/wUp1K2RLZWJnb3lIdEUzM0dmd2JyN3c9PQ==', '2026-01-18 23:33:20'),
(7, 'smtp_from_email', 'info@teslimnushasi.com', '2026-01-18 23:33:20'),
(8, 'smtp_from_name', 'Teslim Nüshası', '2026-01-18 23:33:20');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `document_limit` int(11) DEFAULT 100,
  `company` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

--
-- Tablo döküm verisi `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `document_limit`, `company`, `phone`, `created_at`, `last_login`, `is_active`, `email_verified`, `verification_token`, `reset_token`, `reset_token_expires`) VALUES
(2, 'Teslim Nüshası', 'info@teslimnushasi.com', '$2y$10$eQIykgPCbNpyPsqjNb8E3eCgG45y16flujPcQ6lXZraR39lA4woA6', 'admin', 999999, NULL, NULL, '2026-01-17 16:45:17', '2026-01-18 23:33:05', 1, 1, NULL, '00f03155cc196ce1d93f2d4974c3f08285c3b56eb273c5f345e0b516d118d817', '2026-01-18 23:48:26'),
(6, 'Veysel KORUYUCU', 'veysel@atakod.com.tr', '$2y$10$ceHrIBHmcpCDI/R3Fhn.JO3KNtCDou9SuA04T1EfvTRAQC9EnpbdO', 'user', 100, NULL, NULL, '2026-01-18 23:20:20', '2026-01-18 23:20:51', 1, 1, NULL, NULL, NULL);

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_public_id` (`public_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_document_no` (`document_no`),
  ADD KEY `idx_document_date` (`document_date`);

--
-- Tablo için indeksler `document_pages`
--
ALTER TABLE `document_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_doc_page` (`document_id`,`page_number`),
  ADD KEY `idx_document_id` (`document_id`);

--
-- Tablo için indeksler `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Tablo için indeksler `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=146;

--
-- Tablo için AUTO_INCREMENT değeri `document_pages`
--
ALTER TABLE `document_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=160;

--
-- Tablo için AUTO_INCREMENT değeri `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Tablo için AUTO_INCREMENT değeri `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `document_pages`
--
ALTER TABLE `document_pages`
  ADD CONSTRAINT `document_pages_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
