-- ============================================================
-- Migracja 051_rbac_nullable_club_id.sql
--
-- Naprawa schematu RBAC: club_id w role_permissions miało NOT NULL,
-- ale komentarz + kod aplikacji oczekuje "NULL = globalny domyślny"
-- (RolePermissionModel::modulesForRole z fallbackiem na club_id IS NULL).
--
-- Skutek bug-a: wszystkie świeże klub na produkcji miały pusty sidebar
-- bo brak per-klub i brak global-nych permissions.
--
-- Plan:
--   1. Przebudowa schematu — surrogate id PK + UNIQUE na (club_id,role,module)
--      (PK nie może zawierać NULL, więc trzeba surrogate)
--   2. Seed default global permissions dla 6 ról
--      (zarzad, trener, instruktor, lekarz, ksiegowy + zawodnik portal-only)
--
-- Migracja idempotentna — wszystkie operacje schema-y mają guards przez
-- INFORMATION_SCHEMA, INSERT-y z ON DUPLICATE KEY UPDATE.
-- ============================================================

SET foreign_key_checks = 0;

-- ── 1. SCHEMA FIX ──────────────────────────────────────────────

-- 1.1 Drop legacy FK (jeśli istnieje pod starą nazwą)
SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'role_permissions'
      AND CONSTRAINT_NAME = 'role_permissions_ibfk_1'
);
SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE role_permissions DROP FOREIGN KEY role_permissions_ibfk_1',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1.2 Drop kompozycyjne PK (jeśli wciąż na 3 kolumnach)
SET @has_old_pk = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'role_permissions'
      AND COLUMN_KEY = 'PRI'
      AND COLUMN_NAME = 'club_id'
);
SET @sql = IF(@has_old_pk > 0,
    'ALTER TABLE role_permissions DROP PRIMARY KEY',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1.3 Dodaj surrogate `id` jako nowy PK (jeśli kolumna nie istnieje)
SET @has_id = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'role_permissions'
      AND COLUMN_NAME = 'id'
);
SET @sql = IF(@has_id = 0,
    'ALTER TABLE role_permissions
        ADD COLUMN id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY FIRST',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1.4 Zmień club_id na NULL (jeśli wciąż NOT NULL)
SET @club_nullable = (
    SELECT IS_NULLABLE FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'role_permissions'
      AND COLUMN_NAME = 'club_id'
);
SET @sql = IF(@club_nullable = 'NO',
    "ALTER TABLE role_permissions
        MODIFY COLUMN club_id INT UNSIGNED NULL COMMENT 'NULL = globalny domyślny'",
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1.5 Dodaj UNIQUE KEY (jeśli brak)
SET @has_unique = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'role_permissions'
      AND INDEX_NAME = 'uq_role_perm'
);
SET @sql = IF(@has_unique = 0,
    'ALTER TABLE role_permissions
        ADD UNIQUE KEY uq_role_perm (club_id, role, module)',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1.6 Recreate FK z nową nazwą (jeśli brak)
SET @fk_new_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'role_permissions'
      AND CONSTRAINT_NAME = 'fk_rp_club'
);
SET @sql = IF(@fk_new_exists = 0,
    'ALTER TABLE role_permissions
        ADD CONSTRAINT fk_rp_club
        FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── 2. SEED DEFAULT GLOBAL PERMISSIONS ─────────────────────────

INSERT INTO role_permissions (club_id, role, module, can_view, can_edit) VALUES
-- ZARZAD: pełne uprawnienia do wszystkich modułów
(NULL, 'zarzad', 'members',       1, 1),
(NULL, 'zarzad', 'sports',        1, 1),
(NULL, 'zarzad', 'calendar',      1, 1),
(NULL, 'zarzad', 'events',        1, 1),
(NULL, 'zarzad', 'trainings',     1, 1),
(NULL, 'zarzad', 'fees',          1, 1),
(NULL, 'zarzad', 'medical',       1, 1),
(NULL, 'zarzad', 'announcements', 1, 1),
(NULL, 'zarzad', 'gallery',       1, 1),
(NULL, 'zarzad', 'messages',      1, 1),
(NULL, 'zarzad', 'analytics',     1, 1),
(NULL, 'zarzad', 'bookings',      1, 1),
(NULL, 'zarzad', 'reports',       1, 1),
(NULL, 'zarzad', 'club',          1, 1),
(NULL, 'zarzad', 'shop',          1, 1),
(NULL, 'zarzad', 'livestream',    1, 1),

-- TRENER: zawodnicy + sekcje read, kalendarz/treningi RW, medical read
(NULL, 'trener', 'members',       1, 0),
(NULL, 'trener', 'sports',        1, 0),
(NULL, 'trener', 'calendar',      1, 1),
(NULL, 'trener', 'events',        1, 1),
(NULL, 'trener', 'trainings',     1, 1),
(NULL, 'trener', 'medical',       1, 0),
(NULL, 'trener', 'announcements', 1, 0),
(NULL, 'trener', 'gallery',       1, 1),
(NULL, 'trener', 'messages',      1, 1),
(NULL, 'trener', 'reports',       1, 0),
(NULL, 'trener', 'fees',          1, 0),

-- INSTRUKTOR: jak trener, mniej uprawnień edycji
(NULL, 'instruktor', 'members',   1, 0),
(NULL, 'instruktor', 'sports',    1, 0),
(NULL, 'instruktor', 'calendar',  1, 1),
(NULL, 'instruktor', 'events',    1, 0),
(NULL, 'instruktor', 'trainings', 1, 1),
(NULL, 'instruktor', 'gallery',   1, 1),
(NULL, 'instruktor', 'messages',  1, 1),

-- LEKARZ: tylko medical (RW) + members (read)
(NULL, 'lekarz', 'members',       1, 0),
(NULL, 'lekarz', 'medical',       1, 1),

-- KSIEGOWY: opłaty (RW) + zawodnicy (read) + raporty
(NULL, 'ksiegowy', 'fees',        1, 1),
(NULL, 'ksiegowy', 'members',     1, 0),
(NULL, 'ksiegowy', 'reports',     1, 1)
ON DUPLICATE KEY UPDATE
    can_view = VALUES(can_view),
    can_edit = VALUES(can_edit);

SET foreign_key_checks = 1;
