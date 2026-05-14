-- ============================================================
-- MIGRATION 064: Sports seed expansion — synchronizacja katalogu sportow
-- z folderami modulow w app/Sports/.
-- ------------------------------------------------------------
-- PR #119 (audyt) ujawnil, ze seed `sports` mial tylko ~23 wpisy
-- (schema.sql + 002_niche_sports), podczas gdy w app/Sports/ jest
-- 50+ folderow. Brak rekordow w `sports` blokuje:
--   * UI wyboru sportu (lista pobierana z `sports`)
--   * Pelna aktywacja modulow per sport
--   * Federation linking
-- ------------------------------------------------------------
-- Migracja:
--   1. Doseedowuje brakujace federacje (INSERT IGNORE)
--   2. Doseedowuje brakujace sporty (INSERT IGNORE)
--   3. Dodaje nowe sporty bootstrapowane w tym PR (Strongman,
--      Curling, Esport, Yoga, Pilates, Fitness, Dance)
-- ============================================================

SET foreign_key_checks = 0;

-- ------------------------------------------------------------
-- 1. Brakujace federacje
-- ------------------------------------------------------------
INSERT IGNORE INTO `federations` (`code`, `name`, `website`) VALUES
  ('PZAI',     'Polski Zwiazek Aikido',                            'https://www.aikido.org.pl'),
  ('PZN',      'Polski Zwiazek Narciarski',                        'https://www.pzn.pl'),
  ('PZBiath',  'Polski Zwiazek Biathlonu',                         'https://www.pzbiathlon.pl'),
  ('PZBJJ',    'Polski Zwiazek Brazylijskiego Jiu-Jitsu',          'https://www.pzbjj.pl'),
  ('PZBrydz',  'Polski Zwiazek Brydza Sportowego',                 'https://www.pzbs.pl'),
  ('PZKaj',    'Polski Zwiazek Kajakowy',                          'https://www.pzkaj.pl'),
  ('PZUnih',   'Polski Zwiazek Unihokeja',                         'https://www.polskiunihokej.pl'),
  ('PZG',      'Polski Zwiazek Golfa',                             'https://www.pzgolf.pl'),
  ('PZGim',    'Polski Zwiazek Gimnastyczny',                      'https://www.pzg.pl'),
  ('PZKB',     'Polski Zwiazek Kickboxingu',                       'https://www.pzkickboxing.pl'),
  ('PZMMA',    'Polska Liga MMA',                                  'https://www.plmma.pl'),
  ('PZPadel',  'Polski Zwiazek Padla',                             'https://www.padel.pl'),
  ('PZPow',    'Polski Zwiazek Trojboju Silowego',                 'https://www.powerlifting.pl'),
  ('PZR',      'Polski Zwiazek Rugby',                             'https://www.pzrugby.pl'),
  ('PZHT',     'Polski Zwiazek Hokeja na Trawie',                  'https://www.pzht.pl'),
  ('PZZ',      'Polski Zwiazek Zeglarski',                         'https://www.pya.org.pl'),
  ('PZS',      'Polski Zwiazek Squasha',                           'https://www.pzs.org.pl'),
  ('PZTri',    'Polski Zwiazek Triathlonu',                        'https://www.pzta.eu'),
  ('PZPW',     'Polski Zwiazek Plywania (waterpolo)',              'https://www.polswim.pl'),
  ('PZPC',     'Polski Zwiazek Podnoszenia Ciezarow',              'https://www.pzpc.pl'),
  ('PZZap',    'Polski Zwiazek Zapasniczy',                        'https://www.zapasy.org.pl'),
  ('PZSamb',   'Polski Zwiazek Sambo',                             'https://www.sambo.pl'),
  ('PZCurl',   'Polski Zwiazek Curlingu',                          'https://www.pzcurling.pl'),
  ('PZEsp',    'Polski Zwiazek Sportow Elektronicznych',           'https://www.pzse.pl'),
  ('PZTan',    'Polski Zwiazek Tanca',                             'https://www.pztaniec.pl');

-- ------------------------------------------------------------
-- 2. Brakujace sporty (folder istnieje, seed brakuje)
-- Klucz `key` musi byc zgodny z manifest['key'] w app/Sports/*/manifest.php.
-- ------------------------------------------------------------
INSERT IGNORE INTO `sports` (`key`, `name`, `federation_id`, `icon`, `color`, `team_sport`, `sort_order`) VALUES
  ('aikido',         'Aikido',              (SELECT id FROM federations WHERE code='PZAI'    LIMIT 1), 'bi-arrows-angle-expand', '#6c757d', 0, 250),
  ('alpineski',      'Narciarstwo alpejskie',(SELECT id FROM federations WHERE code='PZN'    LIMIT 1), 'bi-snow',               '#0dcaf0', 0, 260),
  ('biathlon',       'Biathlon',            (SELECT id FROM federations WHERE code='PZBiath' LIMIT 1), 'bi-bullseye',           '#198754', 0, 270),
  ('bjj',            'BJJ',                 (SELECT id FROM federations WHERE code='PZBJJ'   LIMIT 1), 'bi-shield-shaded',      '#0d6efd', 0, 280),
  ('bridge',         'Brydz sportowy',      (SELECT id FROM federations WHERE code='PZBrydz' LIMIT 1), 'bi-suit-spade',         '#343a40', 0, 290),
  ('canoeing',       'Kajakarstwo',         (SELECT id FROM federations WHERE code='PZKaj'   LIMIT 1), 'bi-water',              '#20c997', 0, 300),
  ('chess',          'Szachy',              (SELECT id FROM federations WHERE code='PZSzach' LIMIT 1), 'bi-grid-3x3',           '#343a40', 0, 310),
  ('crossfit',       'CrossFit',            NULL,                                                       'bi-lightning-charge',   '#dc3545', 0, 320),
  ('fieldhockey',    'Hokej na trawie',     (SELECT id FROM federations WHERE code='PZHT'    LIMIT 1), 'bi-flag',               '#198754', 1, 330),
  ('figureskating',  'Lyzwiarstwo figurowe',(SELECT id FROM federations WHERE code='PZŁF'    LIMIT 1), 'bi-snow2',              '#ADD8E6', 0, 340),
  ('floorball',      'Unihokej (Floorball)',(SELECT id FROM federations WHERE code='PZUnih'  LIMIT 1), 'bi-flag-fill',          '#fd7e14', 1, 350),
  ('golf',           'Golf',                (SELECT id FROM federations WHERE code='PZG'     LIMIT 1), 'bi-flag-fill',          '#198754', 0, 360),
  ('gymnastics',     'Gimnastyka',          (SELECT id FROM federations WHERE code='PZGim'   LIMIT 1), 'bi-stars',              '#e83e8c', 0, 370),
  ('kayaking',       'Kajakarstwo gorskie', (SELECT id FROM federations WHERE code='PZKaj'   LIMIT 1), 'bi-moisture',           '#0dcaf0', 0, 380),
  ('kickboxing',     'Kickboxing',          (SELECT id FROM federations WHERE code='PZKB'    LIMIT 1), 'bi-hand-thumbs-up',     '#dc3545', 0, 390),
  ('mma',            'MMA',                 (SELECT id FROM federations WHERE code='PZMMA'   LIMIT 1), 'bi-shield-fill-x',      '#343a40', 0, 400),
  ('padel',          'Padel',               (SELECT id FROM federations WHERE code='PZPadel' LIMIT 1), 'bi-cursor',             '#20c997', 0, 410),
  ('powerlifting',   'Trojboj silowy',      (SELECT id FROM federations WHERE code='PZPow'   LIMIT 1), 'bi-chevron-bar-up',     '#dc3545', 0, 420),
  ('rugby',          'Rugby',               (SELECT id FROM federations WHERE code='PZR'     LIMIT 1), 'bi-rugby',              '#6f42c1', 1, 430),
  ('sailing',        'Zeglarstwo',          (SELECT id FROM federations WHERE code='PZZ'     LIMIT 1), 'bi-tsunami',            '#0d6efd', 0, 440),
  ('sambo',          'Sambo',               (SELECT id FROM federations WHERE code='PZSamb'  LIMIT 1), 'bi-shield',             '#dc3545', 0, 450),
  ('skijump',        'Skoki narciarskie',   (SELECT id FROM federations WHERE code='PZN'     LIMIT 1), 'bi-arrow-up-right',     '#0dcaf0', 0, 460),
  ('snowboard',      'Snowboard',           (SELECT id FROM federations WHERE code='PZN'     LIMIT 1), 'bi-snow2',              '#0dcaf0', 0, 470),
  ('squash',         'Squash',              (SELECT id FROM federations WHERE code='PZS'     LIMIT 1), 'bi-cursor-fill',        '#198754', 0, 480),
  ('triathlon',      'Triathlon',           (SELECT id FROM federations WHERE code='PZTri'   LIMIT 1), 'bi-trophy',             '#fd7e14', 0, 490),
  ('water_polo',     'Pilka wodna',         (SELECT id FROM federations WHERE code='PZPW'    LIMIT 1), 'bi-water',              '#0d6efd', 1, 500),
  ('weightlifting',  'Podnoszenie ciezarow',(SELECT id FROM federations WHERE code='PZPC'    LIMIT 1), 'bi-chevron-double-up',  '#dc3545', 0, 510),
  ('wrestling',      'Zapasy',              (SELECT id FROM federations WHERE code='PZZap'   LIMIT 1), 'bi-people-fill',        '#6f42c1', 0, 520),
  ('xcski',          'Narciarstwo biegowe', (SELECT id FROM federations WHERE code='PZN'     LIMIT 1), 'bi-snow3',              '#0dcaf0', 0, 530);

-- ------------------------------------------------------------
-- 3. Nowo bootstrapowane sporty (foldery dodane w tym PR)
-- ------------------------------------------------------------
INSERT IGNORE INTO `sports` (`key`, `name`, `federation_id`, `icon`, `color`, `team_sport`, `sort_order`) VALUES
  ('strongman', 'Strongman',         NULL,                                                       'bi-chevron-double-up', '#dc3545', 0, 540),
  ('curling',   'Curling',           (SELECT id FROM federations WHERE code='PZCurl' LIMIT 1), 'bi-circle-half',        '#0dcaf0', 1, 550),
  ('esport',    'E-sport',           (SELECT id FROM federations WHERE code='PZEsp'  LIMIT 1), 'bi-controller',         '#6f42c1', 1, 560),
  ('yoga',      'Joga',              NULL,                                                       'bi-flower1',            '#e83e8c', 0, 570),
  ('pilates',   'Pilates',           NULL,                                                       'bi-heart-pulse',        '#e83e8c', 0, 580),
  ('fitness',   'Fitness',           NULL,                                                       'bi-activity',           '#fd7e14', 0, 590),
  ('dance',     'Taniec',            (SELECT id FROM federations WHERE code='PZTan'  LIMIT 1), 'bi-music-note-beamed',  '#e83e8c', 0, 600),
  ('futsal',    'Futsal',            (SELECT id FROM federations WHERE code='PZPN'   LIMIT 1), 'bi-dribbble',           '#28a745', 1, 610);

SET foreign_key_checks = 1;
