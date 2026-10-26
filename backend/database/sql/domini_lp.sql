-- ============================================================================
-- Domini associati alle landing page (fase 1) — schema database
--
-- Equivalente SQL della migration create_domains_tables.php.
-- Da eseguire UNA sola volta (es. phpMyAdmin di Plesk), PRIMA di caricare i file
-- del backend. Fare prima un backup (Esporta) del database.
--
-- Prerequisito: `companies`, `users`, `pages` e `leads` devono essere InnoDB.
-- Controllo:  SHOW TABLE STATUS WHERE Name IN ('companies', 'users', 'pages', 'leads');
--
-- Rollback (i dati di origine dei lead vanno persi):
--   ALTER TABLE `leads` DROP FOREIGN KEY `leads_domain_id_foreign`;
--   ALTER TABLE `leads` DROP COLUMN `origin_inferred`, DROP COLUMN `domain_id`, DROP COLUMN `origin_host`;
--   DROP TABLE `domain_page`;
--   DROP TABLE `domains`;
--
-- @created 2026-10-26
-- ============================================================================

-- 1. Anagrafica domini --------------------------------------------------------
CREATE TABLE `domains` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `company_id` bigint unsigned NULL,
  `user_id` bigint unsigned NULL,
  `hostname` varchar(253) NOT NULL,
  `label` varchar(100) NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `domains` ADD CONSTRAINT `domains_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;
ALTER TABLE `domains` ADD CONSTRAINT `domains_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
ALTER TABLE `domains` ADD INDEX `domains_company_id_hostname_index` (`company_id`, `hostname`);
ALTER TABLE `domains` ADD INDEX `domains_hostname_index` (`hostname`);

-- 2. Associazione pagine ↔ domini ---------------------------------------------
CREATE TABLE `domain_page` (
  `page_id` bigint unsigned NOT NULL,
  `domain_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`page_id`, `domain_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `domain_page` ADD CONSTRAINT `domain_page_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE;
ALTER TABLE `domain_page` ADD CONSTRAINT `domain_page_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE CASCADE;
ALTER TABLE `domain_page` ADD INDEX `domain_page_domain_id_index` (`domain_id`);

-- 3. Origine dei lead ---------------------------------------------------------
ALTER TABLE `leads` ADD `origin_host` varchar(253) NULL AFTER `metadata`;
ALTER TABLE `leads` ADD `domain_id` bigint unsigned NULL AFTER `origin_host`;
ALTER TABLE `leads` ADD `origin_inferred` tinyint(1) NOT NULL DEFAULT '0' AFTER `domain_id`;
ALTER TABLE `leads` ADD CONSTRAINT `leads_domain_id_foreign` FOREIGN KEY (`domain_id`) REFERENCES `domains` (`id`) ON DELETE SET NULL;
