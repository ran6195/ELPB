-- ============================================================================
-- Storico della pubblicazione delle pagine — schema database
--
-- Equivalente SQL della migration create_page_publications_table.php.
-- Da eseguire UNA sola volta (es. phpMyAdmin di Plesk), PRIMA di caricare i file
-- del backend. Fare prima un backup (Esporta) del database.
--
-- Dopo il caricamento dei file: scripts/backfill-publication-history.php
-- (prima senza argomenti per vedere cosa farebbe, poi con --execute) apre il
-- periodo delle pagine già pubblicate.
--
-- Rollback (lo storico va perso):
--   DROP TABLE `page_publications`;
--
-- @created 2026-11-14
-- ============================================================================

-- Un periodo per ogni tratto pubblicato di una pagina per una stessa azienda.
-- ended_at NULL = ancora pubblicata. Nessuna FK: lo storico resta anche dopo
-- l'eliminazione definitiva della pagina.
CREATE TABLE `page_publications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `page_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NULL,
  `started_at` datetime NOT NULL,
  `ended_at` datetime NULL,
  `start_source` varchar(20) NOT NULL,
  `end_reason` varchar(20) NULL,
  `created_at` timestamp NULL,
  `updated_at` timestamp NULL,
  KEY `page_publications_page_id_ended_at_index` (`page_id`, `ended_at`),
  KEY `page_publications_company_id_started_at_index` (`company_id`, `started_at`),
  KEY `page_publications_started_at_ended_at_index` (`started_at`, `ended_at`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
