-- =============================================================
-- AI Choice Database Schema
-- Database: ai_choice
-- =============================================================

CREATE DATABASE IF NOT EXISTS `ai_choice`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `ai_choice`;

-- -------------------------------------------------------------
-- Table: users
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `full_name`  VARCHAR(150)    NOT NULL,
  `email`      VARCHAR(200)    NOT NULL UNIQUE,
  `password`   VARCHAR(255)    NOT NULL,
  `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- Table: ai_assistants  (alternatives for SAW)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ai_assistants` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100)    NOT NULL,
  `company`     VARCHAR(100)    NOT NULL,
  `model`       VARCHAR(100)    NOT NULL,
  `description` TEXT            DEFAULT NULL,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- Table: criteria  (SAW criteria with weight & type)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `criteria` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(150)    NOT NULL,
  `weight`      DECIMAL(5,4)    NOT NULL COMMENT 'decimal fraction 0.0-1.0, sum should equal 1',
  `type`        ENUM('Benefit','Cost') NOT NULL DEFAULT 'Benefit',
  `description` TEXT            DEFAULT NULL,
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- Table: assessments  (scores per assistant per criterion)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `assessments` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `assistant_id` INT UNSIGNED  NOT NULL,
  `criteria_id`  INT UNSIGNED  NOT NULL,
  `value`        DECIMAL(10,4) NOT NULL COMMENT 'raw assessment value',
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_assistant_criteria` (`assistant_id`, `criteria_id`),
  CONSTRAINT `fk_assess_assistant` FOREIGN KEY (`assistant_id`)
    REFERENCES `ai_assistants`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_assess_criteria`  FOREIGN KEY (`criteria_id`)
    REFERENCES `criteria`(`id`)      ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================
-- Seed Data
-- =============================================================

-- Users (password = "password123" hashed with bcrypt)
INSERT INTO `users` (`full_name`, `email`, `password`) VALUES
('Riezky Admin', 'admin@aichoice.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- AI Assistants
INSERT INTO `ai_assistants` (`name`, `company`, `model`, `description`) VALUES
('ChatGPT',    'OpenAI',      'GPT-5',        'Large language model oleh OpenAI berbasis GPT architecture.'),
('Gemini',     'Google',      'Gemini 2.5',   'AI generatif Google dengan kemampuan multimodal.'),
('Claude',     'Anthropic',   'Claude 4',     'AI assistant dengan fokus keamanan dari Anthropic.'),
('Copilot',    'Microsoft',   'Copilot AI',   'Asisten AI Microsoft yang terintegrasi dengan produk Office.'),
('Perplexity', 'Perplexity',  'Pro Search',   'AI dengan kemampuan pencarian web real-time.'),
('Grok',       'xAI',         'Grok 4',       'AI assistant dari xAI buatan Elon Musk.');

-- Criteria
INSERT INTO `criteria` (`name`, `weight`, `type`, `description`) VALUES
('Harga Berlangganan',    0.2500, 'Cost',    'Biaya berlangganan per bulan dalam USD.'),
('Akurasi Jawaban',       0.2000, 'Benefit', 'Tingkat keakuratan jawaban yang diberikan.'),
('Kecepatan Respons',     0.1500, 'Benefit', 'Kecepatan dalam memberikan respons.'),
('Kemudahan Penggunaan',  0.1500, 'Benefit', 'Tingkat kemudahan penggunaan antarmuka.'),
('Fitur Pendukung',       0.1500, 'Benefit', 'Kelengkapan fitur tambahan yang tersedia.'),
('Keamanan Data',         0.1000, 'Benefit', 'Tingkat keamanan dan privasi data pengguna.');

-- Assessments (raw values per assistant per criterion)
-- Criteria order: 1=Harga(Cost), 2=Akurasi, 3=Kecepatan, 4=Kemudahan, 5=Fitur, 6=Keamanan
INSERT INTO `assessments` (`assistant_id`, `criteria_id`, `value`) VALUES
-- ChatGPT
(1, 1, 20.00), (1, 2, 95.00), (1, 3, 90.00), (1, 4, 92.00), (1, 5, 95.00), (1, 6, 88.00),
-- Gemini
(2, 1, 18.00), (2, 2, 93.00), (2, 3, 88.00), (2, 4, 90.00), (2, 5, 92.00), (2, 6, 85.00),
-- Claude
(3, 1, 22.00), (3, 2, 94.00), (3, 3, 86.00), (3, 4, 95.00), (3, 5, 90.00), (3, 6, 92.00),
-- Copilot
(4, 1, 10.00), (4, 2, 88.00), (4, 3, 85.00), (4, 4, 89.00), (4, 5, 87.00), (4, 6, 83.00),
-- Perplexity
(5, 1, 8.00),  (5, 2, 90.00), (5, 3, 92.00), (5, 4, 88.00), (5, 5, 91.00), (5, 6, 80.00),
-- Grok
(6, 1, 15.00), (6, 2, 87.00), (6, 3, 84.00), (6, 4, 86.00), (6, 5, 88.00), (6, 6, 82.00);
