-- 106_scoring_niche_full.sql
-- Promote 5 sports PARTIAL -> FULL: figure_skating, gymnastics, dance_sport (judge scoring)
-- + bridge (cards), climbing, sailing, equestrian, cross_fit (niche full obsługa).
--
-- Wprowadza pelny ISU/judge scoring, bridge boards, climbing routes/attempts,
-- sailing regatta races, equestrian horses, CrossFit WOD library.

SET foreign_key_checks = 0;

-- Figure skating + Gymnastics + Dance sport (judging-based)
CREATE TABLE IF NOT EXISTS sport_judged_performances (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tournament_id INT UNSIGNED NULL,
  club_id INT UNSIGNED NOT NULL,
  member_id INT UNSIGNED NOT NULL,
  sport_key VARCHAR(50) NOT NULL,
  routine_type VARCHAR(100) NULL COMMENT 'short_program/free_skate/floor/balance_beam/standard/latin/...',
  apparatus VARCHAR(50) NULL,
  technical_score DECIMAL(5,2) NULL,
  presentation_score DECIMAL(5,2) NULL,
  difficulty_score DECIMAL(5,2) NULL,
  execution_score DECIMAL(5,2) NULL,
  deductions DECIMAL(4,2) DEFAULT 0,
  total_score DECIMAL(6,2) NULL,
  rank_position INT NULL,
  performed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  notes TEXT NULL,
  KEY idx_judged_tournament_score (tournament_id, total_score),
  KEY idx_judged_member_sport (member_id, sport_key),
  KEY idx_judged_club_sport (club_id, sport_key, performed_at),
  CONSTRAINT fk_judged_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_judged_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sport_judge_scores (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  performance_id INT UNSIGNED NOT NULL,
  judge_name VARCHAR(200) NOT NULL,
  judge_certification VARCHAR(100) NULL,
  score_category VARCHAR(100) NULL COMMENT 'technique/artistry/difficulty/etc.',
  score_value DECIMAL(5,2) NOT NULL,
  notes TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_judge_performance (performance_id),
  CONSTRAINT fk_judge_perf FOREIGN KEY (performance_id) REFERENCES sport_judged_performances(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bridge — pary i karty
CREATE TABLE IF NOT EXISTS sport_bridge_pairs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id INT UNSIGNED NOT NULL,
  member_north_id INT UNSIGNED NOT NULL,
  member_south_id INT UNSIGNED NOT NULL,
  pair_name VARCHAR(150) NULL,
  masterpoints DECIMAL(8,2) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_bp_club (club_id),
  KEY idx_bp_north (member_north_id),
  KEY idx_bp_south (member_south_id),
  CONSTRAINT fk_bp_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_bp_north FOREIGN KEY (member_north_id) REFERENCES members(id) ON DELETE CASCADE,
  CONSTRAINT fk_bp_south FOREIGN KEY (member_south_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sport_bridge_boards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tournament_id INT UNSIGNED NULL,
  club_id INT UNSIGNED NOT NULL,
  board_number INT NOT NULL,
  pair_id INT UNSIGNED NOT NULL,
  contract VARCHAR(20) NULL,
  declarer ENUM('N','S','E','W') NULL,
  result INT NULL COMMENT 'tricks taken vs needed (positive=overtricks, negative=undertricks)',
  imp_score INT NULL,
  mp_score DECIMAL(5,2) NULL,
  played_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_bb_tournament_board (tournament_id, board_number),
  KEY idx_bb_club_date (club_id, played_at),
  KEY idx_bb_pair (pair_id),
  CONSTRAINT fk_bb_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_bb_pair FOREIGN KEY (pair_id) REFERENCES sport_bridge_pairs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Climbing — biblioteka dróg + próby (uzupełnienie istniejących climbing_routes/sends)
CREATE TABLE IF NOT EXISTS sport_climbing_routes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id INT UNSIGNED NOT NULL,
  location_name VARCHAR(200) NULL,
  route_name VARCHAR(200) NOT NULL,
  discipline ENUM('lead','bouldering','speed') NOT NULL,
  grade_yds VARCHAR(20) NULL COMMENT '5.10a, V5, etc.',
  grade_french VARCHAR(20) NULL COMMENT '6a, 7b+, etc.',
  setter VARCHAR(200) NULL,
  set_date DATE NULL,
  retired_date DATE NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_scr_club_discipline (club_id, discipline),
  CONSTRAINT fk_scr_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sport_climbing_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id INT UNSIGNED NOT NULL,
  member_id INT UNSIGNED NOT NULL,
  route_id INT UNSIGNED NOT NULL,
  attempt_date DATE NOT NULL,
  result ENUM('top','zone','failed','flash','onsight') NOT NULL,
  attempts_count INT NOT NULL DEFAULT 1,
  notes TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sca_member_date (member_id, attempt_date),
  KEY idx_sca_club_date (club_id, attempt_date),
  KEY idx_sca_route (route_id),
  CONSTRAINT fk_sca_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_sca_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  CONSTRAINT fk_sca_route FOREIGN KEY (route_id) REFERENCES sport_climbing_routes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sailing — żeglarz + wyścigi regatowe z low-point scoring
CREATE TABLE IF NOT EXISTS sport_sailing_member (
  member_id INT UNSIGNED NOT NULL PRIMARY KEY,
  club_id INT UNSIGNED NOT NULL,
  boat_classes VARCHAR(200) NULL COMMENT 'CSV: optimist,laser,470,...',
  isaf_number VARCHAR(50) NULL,
  national_rank INT NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_ssm_club (club_id),
  CONSTRAINT fk_ssm_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_ssm_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sport_sailing_regatta_races (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tournament_id INT UNSIGNED NULL,
  club_id INT UNSIGNED NOT NULL,
  member_id INT UNSIGNED NOT NULL,
  boat_class VARCHAR(50) NULL,
  race_number INT NOT NULL,
  position INT NULL,
  points DECIMAL(5,2) NULL,
  status ENUM('finished','DNS','DNF','DSQ','OCS','RDG') NOT NULL DEFAULT 'finished',
  weather_wind_knots INT NULL,
  weather_wave_height_cm INT NULL,
  race_date DATE NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ssrr_tournament_member (tournament_id, member_id, race_number),
  KEY idx_ssrr_club_date (club_id, race_date),
  CONSTRAINT fk_ssrr_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_ssrr_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Equestrian — konie + wyniki FEI
CREATE TABLE IF NOT EXISTS sport_equestrian_horses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id INT UNSIGNED NOT NULL,
  name VARCHAR(200) NOT NULL,
  breed VARCHAR(100) NULL,
  birth_year INT NULL,
  fei_id VARCHAR(50) NULL,
  owner_member_id INT UNSIGNED NULL,
  notes TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_seh_club (club_id),
  KEY idx_seh_owner (owner_member_id),
  CONSTRAINT fk_seh_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_seh_owner FOREIGN KEY (owner_member_id) REFERENCES members(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sport_equestrian_results (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tournament_id INT UNSIGNED NULL,
  club_id INT UNSIGNED NOT NULL,
  member_id INT UNSIGNED NOT NULL,
  horse_id INT UNSIGNED NULL,
  discipline ENUM('dressage','jumping','eventing','vaulting','endurance','para') NOT NULL,
  event_name VARCHAR(200) NULL,
  event_date DATE NULL,
  score DECIMAL(6,2) NULL,
  faults_jumping INT NULL,
  time_seconds DECIMAL(7,2) NULL,
  rank_position INT NULL,
  notes TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ser_tournament_score (tournament_id, score),
  KEY idx_ser_club_date (club_id, event_date),
  KEY idx_ser_member (member_id),
  KEY idx_ser_horse (horse_id),
  CONSTRAINT fk_ser_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_ser_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  CONSTRAINT fk_ser_horse FOREIGN KEY (horse_id) REFERENCES sport_equestrian_horses(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CrossFit — WOD library + leaderboard
CREATE TABLE IF NOT EXISTS sport_crossfit_wods (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id INT UNSIGNED NULL COMMENT 'NULL=globalna (Murph, Cindy, Helen, Fran)',
  name VARCHAR(200) NOT NULL,
  description TEXT NULL,
  type ENUM('for_time','amrap','rounds_reps','max_load','strength') NOT NULL,
  time_cap_minutes INT NULL,
  scaling_rules TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_scw_club (club_id),
  CONSTRAINT fk_scw_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sport_crossfit_results (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  club_id INT UNSIGNED NOT NULL,
  member_id INT UNSIGNED NOT NULL,
  wod_id INT UNSIGNED NOT NULL,
  scaled_or_rx ENUM('RX','scaled','foundations') NOT NULL DEFAULT 'RX',
  result_time_seconds INT NULL,
  result_reps INT NULL,
  result_load_kg DECIMAL(6,2) NULL,
  recorded_at DATE NOT NULL,
  verified TINYINT(1) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_scr_member_wod (member_id, wod_id),
  KEY idx_scr_wod_time (wod_id, result_time_seconds),
  KEY idx_scr_club_date (club_id, recorded_at),
  CONSTRAINT fk_scr_club FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
  CONSTRAINT fk_scr_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
  CONSTRAINT fk_scr_wod FOREIGN KEY (wod_id) REFERENCES sport_crossfit_wods(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed benchmark WODs (global, club_id = NULL)
INSERT IGNORE INTO sport_crossfit_wods (id, club_id, name, description, type, time_cap_minutes) VALUES
  (1, NULL, 'Murph', '1 mile run, 100 pull-ups, 200 push-ups, 300 air squats, 1 mile run', 'for_time', 60),
  (2, NULL, 'Cindy', '20 min AMRAP: 5 pull-ups, 10 push-ups, 15 air squats', 'amrap', 20),
  (3, NULL, 'Helen', '3 rounds: 400m run, 21 KB swings, 12 pull-ups', 'for_time', 30),
  (4, NULL, 'Fran', '21-15-9 thrusters + pull-ups', 'for_time', 15),
  (5, NULL, 'Filthy Fifty', '50 reps each: box jumps, jumping pull-ups, KB swings, walking lunges, knees to elbows, push press, back extensions, wall ball, burpees, double-unders', 'for_time', 30);

SET foreign_key_checks = 1;
