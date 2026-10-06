-- ============================================================================
-- Libreria Media (fasi 1-5 + cartelle) — schema database
--
-- Equivalente SQL delle migration:
--   create_media_table.php, add_legacy_to_media.php, create_media_folders_table.php
-- Da eseguire UNA sola volta (es. phpMyAdmin di Plesk), PRIMA di caricare i file
-- del backend. Fare prima un backup (Esporta) del database.
--
-- Prerequisito: `companies` e `users` devono essere InnoDB (chiavi esterne).
-- Controllo:  SHOW TABLE STATUS WHERE Name IN ('companies', 'users');
--
-- Rollback (solo se la libreria non è ancora stata usata):
--   ALTER TABLE `media` DROP FOREIGN KEY `media_folder_id_foreign`;
--   DROP TABLE `media_folders`;
--   DROP TABLE `media`;
--
-- @created 2026-10-06
-- ============================================================================

-- 1. Tabella media ------------------------------------------------------------
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `company_id` bigint unsigned NULL,
  `user_id` bigint unsigned NULL,
  `type` enum('image', 'video') NOT NULL,
  `disk` varchar(20) NOT NULL DEFAULT 'local',
  `path` varchar(500) NOT NULL,
  `thumbnail_path` varchar(500) NULL,
  `original_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `size` bigint unsigned NOT NULL,
  `width` int unsigned NULL,
  `height` int unsigned NULL,
  `duration` int unsigned NULL,
  `alt_text` varchar(255) NULL,
  `parent_id` bigint unsigned NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  `deleted_at` timestamp NULL
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `media` ADD CONSTRAINT `media_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;
ALTER TABLE `media` ADD CONSTRAINT `media_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `media` ADD CONSTRAINT `media_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `media` (`id`) ON DELETE SET NULL;
ALTER TABLE `media` ADD INDEX `media_company_id_type_created_at_index` (`company_id`, `type`, `created_at`);
ALTER TABLE `media` ADD INDEX `media_user_id_type_index` (`user_id`, `type`);

-- 2. Flag legacy (file importati dai vecchi upload) ---------------------------
ALTER TABLE `media` ADD `legacy` tinyint(1) NOT NULL DEFAULT '0' AFTER `parent_id`;

-- 3. Cartelle logiche ---------------------------------------------------------
CREATE TABLE `media_folders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `company_id` bigint unsigned NULL,
  `user_id` bigint unsigned NULL,
  `parent_id` bigint unsigned NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `media_folders` ADD CONSTRAINT `media_folders_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;
ALTER TABLE `media_folders` ADD CONSTRAINT `media_folders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `media_folders` ADD CONSTRAINT `media_folders_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `media_folders` (`id`) ON DELETE CASCADE;
ALTER TABLE `media_folders` ADD INDEX `media_folders_company_id_parent_id_index` (`company_id`, `parent_id`);

ALTER TABLE `media` ADD `folder_id` bigint unsigned NULL AFTER `user_id`;
ALTER TABLE `media` ADD CONSTRAINT `media_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `media_folders` (`id`) ON DELETE SET NULL;
