ALTER TABLE boxing_results
    ADD COLUMN IF NOT EXISTS weight_class VARCHAR(15) DEFAULT NULL AFTER age_category;
