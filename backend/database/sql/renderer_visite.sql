-- ============================================================================
-- Renderer v2.1 e visite senza cookie (fasi 2-3 domini) — schema database
--
-- Equivalente SQL della migration create_tracking_tables.php.
-- Da eseguire UNA sola volta (es. phpMyAdmin di Plesk), PRIMA di caricare i file
-- del backend. Fare prima un backup (Esporta) del database.
--
-- Prerequisito: `pages` deve essere InnoDB (verificato il 2026-10-26).
--
-- Rollback (registro accessi e visite vanno persi):
--   DROP TABLE `tracking_salts`;
--   DROP TABLE `page_view_visitors`;
--   DROP TABLE `page_views_daily`;
--   DROP TABLE `renderer_hits`;
--
-- @created 2026-10-26
-- ============================================================================

-- 1. Registro dei renderer che scaricano le pagine ---------------------------
--    host/renderer vuoti = renderer non aggiornato (riconosciuto da IP e user agent)
CREATE TABLE `renderer_hits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `page_id` bigint unsigned NOT NULL,
  `host` varchar(253) NOT NULL DEFAULT '',
  `renderer` varchar(20) NOT NULL DEFAULT '',
  `version` varchar(20) NULL,
  `ip` varchar(45) NOT NULL DEFAULT '',
  `user_agent` varchar(255) NULL,
  `last_url` varchar(500) NULL,
  `hits` int unsigned NOT NULL DEFAULT 0,
  `first_seen_at` timestamp NULL,
  `last_seen_at` timestamp NULL,
  UNIQUE KEY `renderer_hits_unique` (`page_id`, `host`, `renderer`, `ip`),
  KEY `renderer_hits_host_index` (`host`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `renderer_hits` ADD CONSTRAINT `renderer_hits_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE;

-- 2. Visite aggregate per giorno (conservate 16 mesi) ------------------------
CREATE TABLE `page_views_daily` (
  `page_id` bigint unsigned NOT NULL,
  `host` varchar(253) NOT NULL DEFAULT '',
  `day` date NOT NULL,
  `views` int unsigned NOT NULL DEFAULT 0,
  `visitors` int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`page_id`, `host`, `day`),
  KEY `page_views_daily_day_index` (`day`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `page_views_daily` ADD CONSTRAINT `page_views_daily_page_id_foreign` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE;

-- 3. Visitatori del giorno corrente (hash anonimo, svuotata ogni giorno) ------
CREATE TABLE `page_view_visitors` (
  `day` date NOT NULL,
  `visitor_hash` char(64) NOT NULL,
  PRIMARY KEY (`day`, `visitor_hash`)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 4. Sale giornaliero dell'hash (quello vecchio viene cancellato) ------------
CREATE TABLE `tracking_salts` (
  `day` date NOT NULL PRIMARY KEY,
  `salt` char(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
