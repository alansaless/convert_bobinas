-- SQL de criação da base e tabelas
CREATE DATABASE IF NOT EXISTS `leads_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `leads_db`;

-- Tabela principal de leads
CREATE TABLE IF NOT EXISTS `leads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NULL,
  `email` VARCHAR(255) NULL,
  `phone` VARCHAR(50) NULL,
  `whatsapp` VARCHAR(50) NULL,
  `address` VARCHAR(255) NULL,
  `city` VARCHAR(100) NULL,
  `state` VARCHAR(100) NULL,
  `country` VARCHAR(100) NULL,
  `lat` DECIMAL(10,7) NULL,
  `lng` DECIMAL(10,7) NULL,
  `website` VARCHAR(255) NULL,
  `rating` DECIMAL(3,2) NULL,
  `reviews_count` INT NULL,
  `category` VARCHAR(100) NULL,
  `source` VARCHAR(100) NULL,
  `raw_payload` JSON NULL,
  `rd_uuid` VARCHAR(64) NULL,
  `verified` TINYINT(1) NOT NULL DEFAULT 0,
  `rd_attempts` SMALLINT NOT NULL DEFAULT 0,
  `last_rd_error` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_phone` (`phone`),
  UNIQUE KEY `uniq_email` (`email`),
  UNIQUE KEY `uniq_website` (`website`),
  KEY `idx_city` (`city`),
  KEY `idx_verified` (`verified`),
  KEY `idx_source` (`source`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Logs de integração com RD Station
CREATE TABLE IF NOT EXISTS `rd_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lead_id` INT UNSIGNED NULL,
  `action` VARCHAR(50) NOT NULL, -- create | update | resend
  `status_code` INT NULL,
  `response_body` MEDIUMTEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lead_action` (`lead_id`, `action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;