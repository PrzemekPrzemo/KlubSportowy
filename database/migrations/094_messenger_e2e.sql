-- 094: Messenger E2E — opt-in szyfrowanie wiadomosci (client-side AES-256-GCM).
--
-- MODEL ZAGROZEN:
--   * Server NIE moze odczytac body zaszyfrowanej wiadomosci.
--   * Klucz wywodzony z passphrase czlonka per browser (Web Crypto API: PBKDF2 -> HKDF).
--   * Server przechowuje tylko ciphertext + iv (w body / ciphertext_meta) + key_fingerprint.
--   * passphrase_hash sluzy WYLACZNIE do weryfikacji client-side (czy member zna swoja
--     wlasna passphrase) — nie umozliwia serwerowi rekonstrukcji klucza.
--
-- CAVEAT:
--   * Admin klubu nie ma wgladu w tresc (feature, nie bug).
--   * Zapomniana passphrase = bezpowrotna utrata historii (chyba ze ustawiono recovery).
--   * Push notifications pokaza tylko placeholder "Nowa wiadomosc (zaszyfrowana)".
--
-- Wstecz kompatybilne: is_encrypted = 0 dla starych wiadomosci; thread.e2e_enabled = 0 do opt-in.

SET foreign_key_checks = 0;

ALTER TABLE `chat_messages`
    ADD COLUMN `is_encrypted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `body`,
    ADD COLUMN `encryption_version` VARCHAR(20) NULL AFTER `is_encrypted`,
    ADD COLUMN `ciphertext_meta` JSON NULL AFTER `encryption_version`
        COMMENT 'iv (base64), alg (np. AES-GCM-256), key_fingerprint (sha256(member_id+thread_id))';

ALTER TABLE `message_threads`
    ADD COLUMN `e2e_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `thread_type`,
    ADD COLUMN `e2e_key_fingerprint` CHAR(32) NULL AFTER `e2e_enabled`
        COMMENT 'Pierwsze 16 bajtow (hex) SHA-256 kanonicznego fingerprintu watku — do walidacji po stronie clienta.';

CREATE TABLE IF NOT EXISTS `messenger_member_keys` (
    member_id INT UNSIGNED NOT NULL PRIMARY KEY,
    passphrase_hash VARCHAR(255) NULL COMMENT 'password_hash (PASSWORD_ARGON2ID) passphrase — do client-side verify, NIE do dekrypcji.',
    recovery_phrase_encrypted TEXT NULL COMMENT 'Opcjonalna fraza odzyskiwania zaszyfrowana Encryption::encryptForClub.',
    setup_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_attempt_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Per-member E2E key material (passphrase verification only — server NIE moze deszyfrowac wiadomosci).';

SET foreign_key_checks = 1;
