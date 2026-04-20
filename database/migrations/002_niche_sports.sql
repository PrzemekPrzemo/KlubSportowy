-- ============================================================
-- MIGRATION 002: Niszowe sporty — nowe federacje, sporty, dyscypliny
-- ============================================================

SET foreign_key_checks = 0;

-- ── Nowe federacje ────────────────────────────────────────────
INSERT IGNORE INTO `federations` (`code`, `name`, `website`) VALUES
  ('PZŁucz',  'Polski Związek Łuczniczy',                        'https://www.archery.pl'),
  ('PZSzerm', 'Polski Związek Szermierki',                       'https://www.szermierkakluby.pl'),
  ('PZT',     'Polski Związek Taekwondo',                        'https://www.pzt.pl'),
  ('PZTTS',   'Polski Związek Tańca Towarzyskiego i Sportowego', 'https://www.pztts.pl'),
  ('PZJez',   'Polski Związek Jeździecki',                       'https://www.pzj.pl'),
  ('PZTW',    'Polski Związek Towarzystw Wioślarskich',          'https://www.wioslarstwo.pl'),
  ('PZKol',   'Polski Związek Kolarski',                         'https://www.pzkol.pl'),
  ('PZA',     'Polski Związek Alpinizmu',                        'https://www.pza.org.pl'),
  ('PZTS',    'Polski Związek Tenisa Stołowego',                 'https://www.pzts.pl'),
  ('PZBoks',  'Polski Związek Boksu',                            'https://www.pzboks.pl'),
  ('PZBad',   'Polski Związek Badmintona',                       'https://www.badminton.pl'),
  ('PZŁF',    'Polski Związek Łyżwiarstwa Figurowego',           'https://www.pzlf.pl'),
  ('PZSzach', 'Polski Związek Szachowy',                         'https://www.pzszach.pl'),
  ('PZKS',    'Polski Związek Karate Shinkyokushin',             'https://www.pzks.pl');

-- ── Nowe sporty ───────────────────────────────────────────────
INSERT IGNORE INTO `sports` (`key`, `name`, `federation_id`, `icon`, `color`, `team_sport`, `sort_order`) VALUES
  ('archery',       'Łucznictwo',           (SELECT id FROM federations WHERE code='PZŁucz'  LIMIT 1), 'bi-bullseye',        '#8B4513', 0, 130),
  ('fencing',       'Szermierka',           (SELECT id FROM federations WHERE code='PZSzerm' LIMIT 1), 'bi-slash-lg',        '#C0C0C0', 0, 140),
  ('taekwondo',     'Taekwondo',            (SELECT id FROM federations WHERE code='PZT'     LIMIT 1), 'bi-shield-fill',     '#d63384', 0, 150),
  ('dance_sport',   'Taniec sportowy',      (SELECT id FROM federations WHERE code='PZTTS'   LIMIT 1), 'bi-music-note-beamed','#e83e8c', 0, 160),
  ('equestrian',    'Jeździectwo',          (SELECT id FROM federations WHERE code='PZJez'   LIMIT 1), 'bi-compass',         '#8B6914', 0, 170),
  ('rowing',        'Wioślarstwo',          (SELECT id FROM federations WHERE code='PZTW'    LIMIT 1), 'bi-moisture',        '#0DCAF0', 0, 180),
  ('cycling',       'Kolarstwo',            (SELECT id FROM federations WHERE code='PZKol'   LIMIT 1), 'bi-bicycle',         '#FF8C00', 0, 190),
  ('climbing',      'Wspinaczka sportowa',  (SELECT id FROM federations WHERE code='PZA'     LIMIT 1), 'bi-triangle',        '#6f42c1', 0, 200),
  ('table_tennis',  'Tenis stołowy',        (SELECT id FROM federations WHERE code='PZTS'    LIMIT 1), 'bi-circle-half',     '#198754', 0, 210),
  ('boxing',        'Boks',                 (SELECT id FROM federations WHERE code='PZBoks'  LIMIT 1), 'bi-hand-thumbs-up',  '#dc3545', 0, 220),
  ('badminton',     'Badminton',            (SELECT id FROM federations WHERE code='PZBad'   LIMIT 1), 'bi-feather',         '#20c997', 0, 230),
  ('figure_skating','Łyżwiarstwo figurowe', (SELECT id FROM federations WHERE code='PZŁF'    LIMIT 1), 'bi-snow2',           '#ADD8E6', 0, 240);

-- ── Dyscypliny: Łucznictwo ─────────────────────────────────────
INSERT IGNORE INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='archery'), '18m halowa',      'A18'),
  ((SELECT id FROM sports WHERE `key`='archery'), '25m halowa',      'A25'),
  ((SELECT id FROM sports WHERE `key`='archery'), '50m zewnętrzna',  'A50'),
  ((SELECT id FROM sports WHERE `key`='archery'), '70m zewnętrzna',  'A70'),
  ((SELECT id FROM sports WHERE `key`='archery'), 'Łucznictwo 3D',   'A3D'),
  ((SELECT id FROM sports WHERE `key`='archery'), 'Polowe',          'APF');

-- ── Dyscypliny: Szermierka ─────────────────────────────────────
INSERT IGNORE INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='fencing'), 'Floret',  'FF'),
  ((SELECT id FROM sports WHERE `key`='fencing'), 'Szpada',  'FE'),
  ((SELECT id FROM sports WHERE `key`='fencing'), 'Szabla',  'FS');

-- ── Dyscypliny: Taekwondo ──────────────────────────────────────
INSERT IGNORE INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='taekwondo'), 'Walka (Kyorugi)',      'TKW'),
  ((SELECT id FROM sports WHERE `key`='taekwondo'), 'Poomsae (formy)',      'TKP'),
  ((SELECT id FROM sports WHERE `key`='taekwondo'), 'Breaking',             'TKB');

-- ── Dyscypliny: Taniec sportowy ────────────────────────────────
INSERT IGNORE INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='dance_sport'), 'Standardowy (S)',     'DST'),
  ((SELECT id FROM sports WHERE `key`='dance_sport'), 'Latynoamerykański (L)','DSL'),
  ((SELECT id FROM sports WHERE `key`='dance_sport'), '10 tańców',           'D10'),
  ((SELECT id FROM sports WHERE `key`='dance_sport'), 'Solo/improwizacja',   'DSI');

-- ── Dyscypliny: Jeździectwo ────────────────────────────────────
INSERT IGNORE INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='equestrian'), 'Ujeżdżenie',   'EQ_D'),
  ((SELECT id FROM sports WHERE `key`='equestrian'), 'Skoki',         'EQ_J'),
  ((SELECT id FROM sports WHERE `key`='equestrian'), 'WKKW',          'EQ_E'),
  ((SELECT id FROM sports WHERE `key`='equestrian'), 'Woltyżerka',    'EQ_V'),
  ((SELECT id FROM sports WHERE `key`='equestrian'), 'Zaprzęg',       'EQ_C');

-- ── Dyscypliny: Wioślarstwo ────────────────────────────────────
INSERT IGNORE INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='rowing'), 'Jedynka (1x)',        'ROW1'),
  ((SELECT id FROM sports WHERE `key`='rowing'), 'Dwójka podwójna (2x)','ROW2X'),
  ((SELECT id FROM sports WHERE `key`='rowing'), 'Czwórka (4-)',        'ROW4'),
  ((SELECT id FROM sports WHERE `key`='rowing'), 'Ósemka (8+)',         'ROW8'),
  ((SELECT id FROM sports WHERE `key`='rowing'), 'Ergometr',            'ROWE');

-- ── Dyscypliny: Kolarstwo ──────────────────────────────────────
INSERT IGNORE INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='cycling'), 'Szosa',          'CYC_R'),
  ((SELECT id FROM sports WHERE `key`='cycling'), 'MTB (góra)',      'CYC_M'),
  ((SELECT id FROM sports WHERE `key`='cycling'), 'Tor',             'CYC_T'),
  ((SELECT id FROM sports WHERE `key`='cycling'), 'BMX',             'CYC_B'),
  ((SELECT id FROM sports WHERE `key`='cycling'), 'Przełaj (CX)',    'CYC_X'),
  ((SELECT id FROM sports WHERE `key`='cycling'), 'Gravel',          'CYC_G');

-- ── Dyscypliny: Wspinaczka ──────────────────────────────────────
INSERT IGNORE INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='climbing'), 'Prowadzenie',  'CLB_L'),
  ((SELECT id FROM sports WHERE `key`='climbing'), 'Bouldering',   'CLB_B'),
  ((SELECT id FROM sports WHERE `key`='climbing'), 'Speed',        'CLB_S'),
  ((SELECT id FROM sports WHERE `key`='climbing'), 'Kombinacja',   'CLB_C');

-- ── Dyscypliny: Tenis stołowy ───────────────────────────────────
INSERT IGNORE INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='table_tennis'), 'Singiel',       'TTS'),
  ((SELECT id FROM sports WHERE `key`='table_tennis'), 'Debel',         'TTD'),
  ((SELECT id FROM sports WHERE `key`='table_tennis'), 'Mikst',         'TTM');

-- ── Dyscypliny: Boks ────────────────────────────────────────────
INSERT IGNORE INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='boxing'), 'Olimpijski',       'BOX_O'),
  ((SELECT id FROM sports WHERE `key`='boxing'), 'Kickboxing',       'BOX_K'),
  ((SELECT id FROM sports WHERE `key`='boxing'), 'Tajski (Muay Thai)','BOX_T');

-- ── Dyscypliny: Badminton ───────────────────────────────────────
INSERT IGNORE INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='badminton'), 'Singiel mężczyzn',  'BAD_MS'),
  ((SELECT id FROM sports WHERE `key`='badminton'), 'Singiel kobiet',    'BAD_WS'),
  ((SELECT id FROM sports WHERE `key`='badminton'), 'Debel',             'BAD_D'),
  ((SELECT id FROM sports WHERE `key`='badminton'), 'Mikst',             'BAD_MX');

-- ── Dyscypliny: Łyżwiarstwo figurowe ───────────────────────────
INSERT IGNORE INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='figure_skating'), 'Soliści',              'FSK_S'),
  ((SELECT id FROM sports WHERE `key`='figure_skating'), 'Pary sportowe',        'FSK_P'),
  ((SELECT id FROM sports WHERE `key`='figure_skating'), 'Taniec na lodzie',     'FSK_D'),
  ((SELECT id FROM sports WHERE `key`='figure_skating'), 'Synchroniczne',        'FSK_SY');

-- ── Dyscypliny: Judo (uzupełnienie) ────────────────────────────
INSERT IGNORE INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='judo'), 'Ogólne',           'JUDO'),
  ((SELECT id FROM sports WHERE `key`='judo'), 'Kata',             'JKAT'),
  ((SELECT id FROM sports WHERE `key`='judo'), 'Para-judo',        'JPAR');

-- ── Dyscypliny: Karate (uzupełnienie) ──────────────────────────
INSERT IGNORE INTO `disciplines` (`sport_id`, `name`, `short_code`) VALUES
  ((SELECT id FROM sports WHERE `key`='karate'), 'Kumite',          'KKUM'),
  ((SELECT id FROM sports WHERE `key`='karate'), 'Kata',            'KKAT'),
  ((SELECT id FROM sports WHERE `key`='karate'), 'Team kata',       'KTKT');

-- ── Klasy sportowe: Łucznictwo ─────────────────────────────────
INSERT IGNORE INTO `member_classes` (`sport_id`, `name`, `short_code`, `sort_order`) VALUES
  ((SELECT id FROM sports WHERE `key`='archery'), 'Uczeń',        'U',   1),
  ((SELECT id FROM sports WHERE `key`='archery'), 'Młodzik',      'Mł',  2),
  ((SELECT id FROM sports WHERE `key`='archery'), 'Junior',       'J',   3),
  ((SELECT id FROM sports WHERE `key`='archery'), 'Senior',       'S',   4),
  ((SELECT id FROM sports WHERE `key`='archery'), 'Mistrz',       'M',   5);

-- ── Klasy sportowe: Szermierka ─────────────────────────────────
INSERT IGNORE INTO `member_classes` (`sport_id`, `name`, `short_code`, `sort_order`) VALUES
  ((SELECT id FROM sports WHERE `key`='fencing'), 'Nowicjusz',    'N',   1),
  ((SELECT id FROM sports WHERE `key`='fencing'), 'II klasa',     'II',  2),
  ((SELECT id FROM sports WHERE `key`='fencing'), 'I klasa',      'I',   3),
  ((SELECT id FROM sports WHERE `key`='fencing'), 'Kandydat MŚ',  'KM',  4),
  ((SELECT id FROM sports WHERE `key`='fencing'), 'Mistrz Sportu','MS',  5);

-- ── Klasy sportowe: Taniec sportowy ───────────────────────────
INSERT IGNORE INTO `member_classes` (`sport_id`, `name`, `short_code`, `sort_order`) VALUES
  ((SELECT id FROM sports WHERE `key`='dance_sport'), 'D (podstawa)', 'D', 1),
  ((SELECT id FROM sports WHERE `key`='dance_sport'), 'C',             'C', 2),
  ((SELECT id FROM sports WHERE `key`='dance_sport'), 'B',             'B', 3),
  ((SELECT id FROM sports WHERE `key`='dance_sport'), 'A',             'A', 4),
  ((SELECT id FROM sports WHERE `key`='dance_sport'), 'S (Special)',   'S', 5),
  ((SELECT id FROM sports WHERE `key`='dance_sport'), 'E (Elite)',     'E', 6),
  ((SELECT id FROM sports WHERE `key`='dance_sport'), 'M (Mistrz)',    'M', 7);

-- ── Klasy sportowe: Boks ────────────────────────────────────────
INSERT IGNORE INTO `member_classes` (`sport_id`, `name`, `short_code`, `sort_order`) VALUES
  ((SELECT id FROM sports WHERE `key`='boxing'), 'Amator',        'AM',  1),
  ((SELECT id FROM sports WHERE `key`='boxing'), 'Elite',         'EL',  2),
  ((SELECT id FROM sports WHERE `key`='boxing'), 'Zawodowy',      'PRO', 3);

SET foreign_key_checks = 1;
