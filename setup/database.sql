-- ============================================================
-- KAPTA — Schema da Base de Dados
-- setup/database.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- Base de dados
CREATE DATABASE IF NOT EXISTS `kapta_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `kapta_db`;

-- -----------------------------------------------
-- Tabela: users
-- -----------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(120) NOT NULL,
  `email`      VARCHAR(180) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `role`       ENUM('brand','creator','admin') NOT NULL DEFAULT 'creator',
  `avatar`     VARCHAR(255) DEFAULT NULL,
  `status`     ENUM('active','suspended') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_role`  (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- Tabela: brand_profiles
-- -----------------------------------------------
DROP TABLE IF EXISTS `brand_profiles`;
CREATE TABLE `brand_profiles` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED NOT NULL UNIQUE,
  `company_name` VARCHAR(180) NOT NULL,
  `website`      VARCHAR(255) DEFAULT NULL,
  `description`  TEXT DEFAULT NULL,
  `logo`         VARCHAR(255) DEFAULT NULL,
  `verified`     TINYINT(1) NOT NULL DEFAULT 0,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_brand_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- Tabela: creator_profiles
-- -----------------------------------------------
DROP TABLE IF EXISTS `creator_profiles`;
CREATE TABLE `creator_profiles` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`               INT UNSIGNED NOT NULL UNIQUE,
  `bio`                   TEXT DEFAULT NULL,
  `tiktok_handle`         VARCHAR(100) DEFAULT NULL,
  `instagram_handle`      VARCHAR(100) DEFAULT NULL,
  `youtube_channel`       VARCHAR(255) DEFAULT NULL,
  `total_views`           BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `total_earned`          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `instagram_access_token` VARCHAR(500) DEFAULT NULL,
  `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_creator_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- Tabela: campaigns
-- -----------------------------------------------
DROP TABLE IF EXISTS `campaigns`;
CREATE TABLE `campaigns` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `brand_id`       INT UNSIGNED NOT NULL,
  `title`          VARCHAR(255) NOT NULL,
  `description`    TEXT DEFAULT NULL,
  `requirements`   TEXT DEFAULT NULL,
  `category`       VARCHAR(80) NOT NULL DEFAULT 'Entretenimento',
  `platforms`      JSON DEFAULT NULL,
  `cpm_rate`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `budget`         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `spent`          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `status`         ENUM('draft','active','paused','completed') NOT NULL DEFAULT 'draft',
  `min_followers`  INT UNSIGNED NOT NULL DEFAULT 0,
  `deadline`       DATE DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_brand`  (`brand_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_campaign_brand` FOREIGN KEY (`brand_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- Tabela: campaign_submissions
-- -----------------------------------------------
DROP TABLE IF EXISTS `campaign_submissions`;
CREATE TABLE `campaign_submissions` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `campaign_id`    INT UNSIGNED NOT NULL,
  `creator_id`     INT UNSIGNED NOT NULL,
  `platform`       ENUM('youtube','tiktok','instagram') NOT NULL,
  `video_url`      VARCHAR(500) NOT NULL,
  `video_id`       VARCHAR(255) DEFAULT NULL,
  `title`          VARCHAR(500) DEFAULT NULL,
  `thumbnail`      VARCHAR(500) DEFAULT NULL,
  `views`          BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `last_synced_at` DATETIME DEFAULT NULL,
  `status`         ENUM('pending','approved','rejected','active') NOT NULL DEFAULT 'pending',
  `earnings`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `submitted_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_at`    DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_campaign`  (`campaign_id`),
  KEY `idx_creator`   (`creator_id`),
  KEY `idx_status`    (`status`),
  CONSTRAINT `fk_sub_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sub_creator`  FOREIGN KEY (`creator_id`)  REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- Tabela: wallets
-- -----------------------------------------------
DROP TABLE IF EXISTS `wallets`;
CREATE TABLE `wallets` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED NOT NULL UNIQUE,
  `balance`          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_deposited`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_withdrawn`  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_wallet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- Tabela: transactions
-- -----------------------------------------------
DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `wallet_id`   INT UNSIGNED NOT NULL,
  `user_id`     INT UNSIGNED NOT NULL,
  `type`        ENUM('deposit','withdrawal','campaign_fund','earning','platform_fee') NOT NULL,
  `amount`      DECIMAL(15,2) NOT NULL,
  `description` VARCHAR(500) DEFAULT NULL,
  `reference`   VARCHAR(255) DEFAULT NULL,
  `status`      ENUM('pending','completed','failed') NOT NULL DEFAULT 'completed',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wallet`  (`wallet_id`),
  KEY `idx_user`    (`user_id`),
  KEY `idx_type`    (`type`),
  CONSTRAINT `fk_tx_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tx_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`   (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Admin: admin@kapta.ao / admin123
INSERT INTO `users` (`id`,`name`,`email`,`password`,`role`,`status`) VALUES
(1, 'Administrador Kapta', 'admin@kapta.ao',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
 'admin', 'active');

-- Brand: marca@demo.ao / demo123
INSERT INTO `users` (`id`,`name`,`email`,`password`,`role`,`status`) VALUES
(2, 'Demo Marca', 'marca@demo.ao',
 '$2y$12$TefkRqMZ.K5GXt1GaGSzEe.WcQYxlMC/k2G.u1E7q.oLbgfUh/zKm', -- demo123
 'brand', 'active');

-- Creator: creator@demo.ao / demo123
INSERT INTO `users` (`id`,`name`,`email`,`password`,`role`,`status`) VALUES
(3, 'João Creator', 'creator@demo.ao',
 '$2y$12$TefkRqMZ.K5GXt1GaGSzEe.WcQYxlMC/k2G.u1E7q.oLbgfUh/zKm', -- demo123
 'creator', 'active');

-- Brand profile
INSERT INTO `brand_profiles` (`user_id`,`company_name`,`website`,`description`,`verified`) VALUES
(2, 'Demo Marca Lda', 'https://demomarca.ao',
 'Empresa de demonstração na plataforma Kapta.', 1);

-- Creator profile
INSERT INTO `creator_profiles` (`user_id`,`bio`,`tiktok_handle`,`instagram_handle`,`youtube_channel`,`total_views`,`total_earned`) VALUES
(3, 'Creator apaixonado por conteúdo digital em Angola.',
 '@joaocreator', 'joaocreator', 'UCjOaNcreatorDemo', 120000, 3500.00);

-- Wallets
INSERT INTO `wallets` (`user_id`,`balance`,`total_deposited`,`total_withdrawn`) VALUES
(1, 0.00,       0.00,    0.00),
(2, 50000.00,   50000.00, 0.00),
(3, 3500.00,    0.00,    0.00);

-- Sample campaign
INSERT INTO `campaigns`
  (`id`,`brand_id`,`title`,`description`,`requirements`,`category`,`platforms`,`cpm_rate`,`budget`,`spent`,`status`,`min_followers`,`deadline`)
VALUES
(1, 2,
 'Lançamento Produto X — Angola 2025',
 'Precisamos de creators angolanos para promover o nosso novo produto. Conteúdo autêntico, em português.',
 'Mencionar o produto nos primeiros 30 segundos. Usar a hashtag #ProdutoX. Vídeo mínimo de 60 segundos.',
 'Lifestyle',
 '["youtube","tiktok","instagram"]',
 5000.00,
 30000.00,
 3500.00,
 'active',
 1000,
 '2025-12-31');

-- Sample submission
INSERT INTO `campaign_submissions`
  (`id`,`campaign_id`,`creator_id`,`platform`,`video_url`,`video_id`,`title`,`views`,`status`,`earnings`)
VALUES
(1, 1, 3, 'youtube',
 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
 'dQw4w9WgXcQ',
 'Produto X — Será que Vale a Pena? | Teste Honesto',
 700000,
 'active',
 3500.00);

-- Sample transactions
INSERT INTO `transactions` (`wallet_id`,`user_id`,`type`,`amount`,`description`,`status`) VALUES
(2, 2, 'deposit',       55555.56, 'Depósito inicial de créditos', 'completed'),
(2, 2, 'platform_fee',  -5555.56, 'Taxa da plataforma (10%)',     'completed'),
(2, 2, 'campaign_fund', -30000.00,'Campanha: Lançamento Produto X', 'completed'),
(3, 3, 'earning',       3500.00,  'Ganhos: Produto X — 700k views', 'completed');

SET FOREIGN_KEY_CHECKS = 1;
