-- Migration 030: Performance indexes for multi-section clubs (>15 sport sections)
--
-- Findings from audit:
-- - club_sports has KEY (club_id) and KEY (sport_id), but no composite index
--   covering the common filter "active sections of this club, in display order".
-- - trainings has KEY (club_id) and KEY (start_time), but lookups in the
--   member portal join member_sports → club_sports and filter by date+status,
--   which is a full scan on the existing indexes.
--
-- Both indexes are additive (no drops, no schema-shape changes), safe to apply
-- on production with concurrent traffic. MySQL 8 ALGORITHM=INPLACE LOCK=NONE
-- is implicit for index addition on InnoDB.

SET foreign_key_checks = 0;

-- club_sports: covering index for "active sections of this club".
-- Sort order comes from the joined `sports.sort_order` column, so the
-- composite index needs only (club_id, is_active) — the join on
-- club_sports.sport_id then uses the existing idx_club_sports_sport.
ALTER TABLE `club_sports`
  ADD KEY `idx_cs_club_active` (`club_id`, `is_active`);

-- trainings: covering index for "schedule for club between dates"
ALTER TABLE `trainings`
  ADD KEY `idx_tr_club_time_status` (`club_id`, `start_time`, `status`);

-- trainings: covering index for "schedule per active section between dates"
-- (used by MemberPortalController::scheduleForMember after A4 optimization)
ALTER TABLE `trainings`
  ADD KEY `idx_tr_csid_time` (`club_sport_id`, `start_time`);

SET foreign_key_checks = 1;
