-- Rollerskating: add skating style and discipline detail
ALTER TABLE `rollerskating_times`
  ADD COLUMN IF NOT EXISTS `skating_style`
    ENUM('short_track','long_track','inline_speed','inline_freestyle','artistic','hockey','other')
    NULL AFTER `distance`,
  ADD COLUMN IF NOT EXISTS `discipline_detail`
    VARCHAR(50) NULL AFTER `skating_style`;
