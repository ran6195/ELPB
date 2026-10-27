-- ============================================================================
-- Registro attività (audit log) — schema database
--
-- Equivalente SQL della migration create_audit_logs_table.php.
-- Da eseguire UNA sola volta (es. phpMyAdmin di Plesk), PRIMA di caricare i file
-- del backend. Fare prima un backup (Esporta) del database.
--
-- Nessuna foreign key: le voci restano anche se utenti, aziende o pagine
-- vengono eliminati. Conservazione 24 mesi (pulizia automatica dall'applicazione).
--
-- Rollback (il registro va perso):
--   DROP TABLE `audit_logs`;
--
-- @created 2026-10-27
-- ============================================================================

CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `created_at` datetime(3) NOT NULL,
  `request_id` char(26) NULL,
  `channel` varchar(10) NOT NULL,
  `category` varchar(10) NOT NULL,
  `action` varchar(100) NOT NULL,
  `user_id` bigint unsigned NULL,
  `user_name` varchar(255) NULL,
  `user_email` varchar(255) NULL,
  `user_role` varchar(20) NULL,
  `company_id` bigint unsigned NULL,
  `page_id` bigint unsigned NULL,
  `subject_type` varchar(40) NULL,
  `subject_id` bigint unsigned NULL,
  `subject_label` varchar(255) NULL,
  `method` varchar(8) NULL,
  `url` varchar(1000) NULL,
  `status` smallint unsigned NULL,
  `outcome` varchar(12) NULL,
  `duration_ms` int unsigned NULL,
  `ip` varchar(45) NULL,
  `user_agent` varchar(250) NULL,
  `properties` json NULL,
  KEY `audit_logs_created_at_index` (`created_at`),
  KEY `audit_logs_user_id_created_at_index` (`user_id`, `created_at`),
  KEY `audit_logs_company_id_created_at_index` (`company_id`, `created_at`),
  KEY `audit_logs_category_created_at_index` (`category`, `created_at`),
  KEY `audit_logs_request_id_index` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
