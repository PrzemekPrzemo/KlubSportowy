-- 070: Rozszerzenie fee_rates o kategorie wiekowe, rabaty i auto-assign.
-- Repo nie ma tabeli `fees_definitions` — odpowiednikiem jest `fee_rates`.
-- ALTER-y owiniete w guard (sprawdzenie information_schema) by byly idempotentne.

SET foreign_key_checks = 0;

-- Dynamiczne ALTER-y idempotentne (dodaje kolumny tylko jesli nie istnieja)
DROP PROCEDURE IF EXISTS `__add_col_if_missing`;
DELIMITER //
CREATE PROCEDURE `__add_col_if_missing`(
    IN `p_table` VARCHAR(64),
    IN `p_column` VARCHAR(64),
    IN `p_definition` TEXT
)
BEGIN
    DECLARE col_count INT DEFAULT 0;
    SELECT COUNT(*) INTO col_count
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = p_table
      AND column_name = p_column;
    IF col_count = 0 THEN
        SET @ddl = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//
DELIMITER ;

CALL `__add_col_if_missing`('fee_rates', 'age_category', 'VARCHAR(40) NULL COMMENT ''np. junior/senior/dziecko''');
CALL `__add_col_if_missing`('fee_rates', 'min_age_years', 'TINYINT UNSIGNED NULL');
CALL `__add_col_if_missing`('fee_rates', 'max_age_years', 'TINYINT UNSIGNED NULL');
CALL `__add_col_if_missing`('fee_rates', 'discount_pct', 'DECIMAL(5,2) NOT NULL DEFAULT 0 COMMENT ''Procent rabatu''');
CALL `__add_col_if_missing`('fee_rates', 'auto_assign_for_new_members', 'TINYINT(1) NOT NULL DEFAULT 0');

DROP PROCEDURE IF EXISTS `__add_col_if_missing`;

SET foreign_key_checks = 1;
