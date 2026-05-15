-- 082: Legal documents suite — wersjonowanie regulaminów i log akceptacji.
--
-- Dwie tabele:
--   * legal_documents     — wszystkie wersje dokumentów (ToS, Privacy, Cookies, DPA, SLA, member_clause).
--                           Tylko jedna wersja per (doc_type, locale) ma is_current=1.
--   * legal_acceptances   — audytowy log akceptacji (kto, kiedy, jaki dokument,
--                           jaki kontekst: rejestracja / onboarding / odnowienie / signup członka / wymagane przy logowaniu).
--
-- Po publikacji nowej wersji ToS/Privacy:
--   - poprzednia wersja: is_current=0, nowa: is_current=1
--   - przy następnym logowaniu pokazujemy modal force-reacceptance
--     (jeśli ostatnia akceptacja użytkownika dotyczy starszej wersji).

SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `legal_documents` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    doc_type ENUM('tos','privacy','cookies','dpa','sla','member_clause') NOT NULL,
    locale CHAR(2) NOT NULL DEFAULT 'pl',
    version VARCHAR(20) NOT NULL,
    effective_from DATE NOT NULL,
    title VARCHAR(200) NOT NULL,
    body_md MEDIUMTEXT NOT NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_type_current (doc_type, locale, is_current),
    KEY idx_type_version (doc_type, locale, version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Wersjonowane dokumenty prawne (Sendormeco Holding Sp. z o.o.)';

CREATE TABLE IF NOT EXISTS `legal_acceptances` (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    club_id INT UNSIGNED NULL,
    member_id INT UNSIGNED NULL,
    document_id INT UNSIGNED NOT NULL,
    accepted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    context ENUM('registration','onboarding','renewal','member_signup','login_required') NOT NULL,
    KEY idx_user (user_id),
    KEY idx_club (club_id),
    KEY idx_document (document_id),
    KEY idx_accepted (accepted_at),
    FOREIGN KEY (document_id) REFERENCES legal_documents(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Audytowy log akceptacji dokumentów prawnych (kto/kiedy/skąd)';

SET foreign_key_checks = 1;
