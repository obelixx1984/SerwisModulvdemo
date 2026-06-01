-- ============================================================
-- Moduł Serwis — Dane startowe (seed) v2.7
-- ============================================================
-- Dane słownikowe zgodne z bazą cmms2.sql:
--   - Role, Statusy, Objawy, Kategorie awarii, Słownik awarii
--   - Linie produkcyjne i podzespoły (6 linii)
--   - Użytkownicy (admin + 3 mechanicy + 2 operatorzy)
--   - Szablony DUR i Harmonogramy DUR
--   - Ustawienia systemowe
--   - Kategorie części zamiennych (bez listy części!)
--   - 2 zgłoszenia awarii na linię (status: Nowa awaria)
--     operator1 i operator2 na przemian
--   - 2 przeglądy DUR na linię (mechanicy rotacyjnie)
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ────────────────────────────────────────────────────────────
-- Role systemowe
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `roles` (`id`, `name`, `label`) VALUES
(1, 'admin',    'Administrator'),
(2, 'mechanic', 'Mechanik'),
(3, 'operator', 'Operator (tylko odczyt)');

-- ────────────────────────────────────────────────────────────
-- Użytkownicy — zgodne z cmms2.sql
-- Hasło dla wszystkich: password
-- Hash BCrypt cost=12
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `users`
    (`id`, `role_id`, `name`, `login`, `email`, `password_hash`, `is_active`) VALUES
(1,  1, 'Administrator', 'admin',    'admin@serwis.local',    '$2y$12$X9NozN1mS1hStF40MosqNeTM6JPiJu8oZfwKkL1WhG4T5hoIG1B..', 1),
(11, 2, 'Tomasz Matyjas', 'tomek',   'tomek@serwis.local',    '$2y$12$l2L57NBPPUTjKcs37o4loergdiMsjhKMQBrBY8MGCdKjlFbXQF36K', 1),
(12, 2, 'Roman Lusztak', 'roman',   'roman@serwis.local',    '$2y$12$zSYiS3HB8y7NZZq9DhMee.V1zNbkJTwVxYAZRDfNjQSC/4I1qskqK', 1),
(13, 2, 'Andrii Kisak',  'andrii',  'andrii@serwis.local',   '$2y$12$p32vewUCqyz39VOWaUHM4eymzLrTdI0kuKMcIrM8M4lG9454MfrAu', 1),
(14, 3, 'Adam Kowalski', 'operator1','operator1@serwis.local','$2y$12$Ftl08bX7ntHCMLBSDuaeIOyLeiLTcsB6qfka6riOHHKLIDmdX/veu', 1),
(15, 3, 'Paweł Nowak',   'operator2','operator2@serwis.local','$2y$12$6zwDqYncUXy/GQ4HhNygPe1lbdGXsyZsTPf7LnpH1Z4/QuVm.scM6', 1);

-- ────────────────────────────────────────────────────────────
-- Linie produkcyjne — zgodne z cmms2.sql
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `production_lines`
    (`id`, `name`, `prefix`, `description`, `is_active`) VALUES
(1, 'A1 / Linia arkuszy', 'A1', 'Linia arkuszy', 1),
(2, 'A2 / Linia arkuszy', 'A2', 'Linia arkuszy', 1),
(3, 'A3 / Linia arkuszy', 'A3', 'Linia arkuszy', 1),
(4, 'W1 / Linia taśm',    'W1', 'Linia spawania MIG/MAG', 1),
(5, 'A5 / Linia arkuszy', 'A5', 'Linia arkuszy', 1),
(6, 'W2 / Linia taśm',    'W2', 'Linia taśm', 1);

-- ────────────────────────────────────────────────────────────
-- Podzespoły linii — zgodne z cmms2.sql
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `line_subsystems`
    (`id`, `production_line_id`, `name`, `sort_order`, `is_active`) VALUES
-- A1 (line 1)
(24, 1, 'Odwijak z wózkiem', 1, 1),
(25, 1, 'Rolki wprowadzające z pozycjonowaniem', 2, 1),
(26, 1, 'Foliowarka', 3, 1),
(27, 1, 'Prostowarka 1', 4, 1),
(28, 1, 'Prostowarka 2', 5, 1),
(29, 1, 'Gilotyna', 6, 1),
(30, 1, 'Stół odbierający', 7, 1),
(31, 1, 'Stół złomowy', 8, 1),
(32, 1, 'Karuzela złomowa', 9, 1),
(33, 1, 'Rolki podające', 10, 1),
(34, 1, 'Układarka arkuszy ze stołem podnoszonym', 11, 1),
(35, 1, 'Magazyn palet z podajnikiem', 12, 1),
(36, 1, 'Stół wagi', 13, 1),
(37, 1, 'Stoły pakowania', 14, 1),
(38, 1, 'Spinarka palet', 15, 1),
(39, 1, 'Układ hydrauliczny', 16, 1),
(40, 1, 'Układ elektryczny', 17, 1),
(41, 1, 'Szafy elektryczne i pulpity operatora', 18, 1),
-- A3 (line 3)
(42, 3, 'Odwijak z wózkiem', 1, 1),
(43, 3, 'Rolki wprowadzające z pozycjonowaniem', 2, 1),
(44, 3, 'Prostowarka', 3, 1),
(45, 3, 'Gilotyna', 4, 1),
(46, 3, 'Stół odbierający', 5, 1),
(47, 3, 'Rolki podające', 6, 1),
(48, 3, 'Układarka arkuszy ze stołem podnoszonym', 7, 1),
(49, 3, 'Stoły złomowe z wózkami', 8, 1),
(50, 3, 'Stół wagi', 9, 1),
(51, 3, 'Stół pakowania', 10, 1),
(52, 3, 'Układ hydrauliczny', 11, 1),
(53, 3, 'Układ elektryczny', 12, 1),
(54, 3, 'Szafy elektryczne i pulpity operatora', 13, 1),
-- A5 (line 5)
(71, 5, 'Odwijak z wózkiem', 1, 1),
(72, 5, 'Rolki wprowadzające z pozycjonowaniem', 2, 1),
(73, 5, 'Prostowarka', 3, 1),
(74, 5, 'Gilotyna', 4, 1),
(75, 5, 'Stół odbierający', 5, 1),
(76, 5, 'Rolki podające', 6, 1),
(77, 5, 'Układarka arkuszy ze stołem podnoszonym', 7, 1),
(78, 5, 'Stoły złomowe z wózkami', 8, 1),
(79, 5, 'Stół wagi i pakowania', 9, 1),
(80, 5, 'Układ hydrauliczny', 10, 1),
(81, 5, 'Układ elektryczny', 11, 1),
(82, 5, 'Szafy elektryczne i pulpity operatora', 12, 1),
-- W2 (line 6)
(105, 6, 'Odwijak z wózkiem', 1, 1),
(106, 6, 'Rolki wprowadzające z pozycjonowaniem', 2, 1),
(107, 6, 'Gilotyna', 3, 1),
(108, 6, 'Nożyca krążkowa', 4, 1),
(109, 6, 'Stoły nad kanałem', 5, 1),
(110, 6, 'Nawijak złomu', 6, 1),
(111, 6, 'Zespół przejezdny hamulca pneumat. z rolką pomiarową', 7, 1),
(112, 6, 'Nawijak z wózkiem', 8, 1),
(113, 6, 'Ramiona obrotowe z ostrogą', 9, 1),
(114, 6, 'Bramka pakowania', 10, 1),
(115, 6, 'Transportery pakowania', 11, 1),
(116, 6, 'Układ hydrauliczny', 12, 1),
(117, 6, 'Układ elektryczny', 13, 1),
(118, 6, 'Szafy elektryczne i pulpity operatora', 14, 1),
-- W1 (line 4)
(119, 4, 'Odwijak z wózkiem', 1, 1),
(120, 4, 'Rolki wprowadzające z pozycjonowaniem', 2, 1),
(121, 4, 'Gilotyna', 3, 1),
(122, 4, 'Nożyca krążkowa', 4, 1),
(123, 4, 'Stoły nad kanałem', 5, 1),
(124, 4, 'Nawijaki złomu', 6, 1),
(125, 4, 'Hamulec pneumatyczny z rolką pomiarową', 7, 1),
(126, 4, 'Nawijak z wózkiem', 8, 1),
(127, 4, 'Ramiona obrotowe z ostrogą', 9, 1),
(128, 4, 'Bramka pakowania', 10, 1),
(129, 4, 'Transportery pakowania', 11, 1),
(130, 4, 'Układ hydrauliczny', 12, 1),
(131, 4, 'Układ elektryczny', 13, 1),
(132, 4, 'Szafy elektryczne i pulpity operatora', 14, 1),
-- A2 (line 2)
(133, 2, 'Odwijak z wózkiem', 1, 1),
(134, 2, 'Rolki wprowadzające z pozycjonowaniem', 2, 1),
(135, 2, 'Foliowarka', 3, 1),
(136, 2, 'Prostowarka', 4, 1),
(137, 2, 'Gilotyna', 5, 1),
(138, 2, 'Stół odbierający', 6, 1),
(139, 2, 'Stół złomowy', 7, 1),
(140, 2, 'Karuzela złomowa', 8, 1),
(141, 2, 'Rolki podające', 9, 1),
(142, 2, 'Układarka arkuszy ze stołem podnoszonym', 10, 1),
(143, 2, 'Magazyn palet', 11, 1),
(144, 2, 'Stół wagi', 12, 1),
(145, 2, 'Stoły pakowania', 13, 1),
(146, 2, 'Spinarka palet', 14, 1),
(147, 2, 'Układ hydrauliczny', 15, 1),
(148, 2, 'Układ elektryczny', 16, 1),
(149, 2, 'Szafy elektryczne i pulpity operatora', 17, 1);

-- ────────────────────────────────────────────────────────────
-- Liczniki zgłoszeń — 2 zgłoszenia na linię
-- ────────────────────────────────────────────────────────────
INSERT INTO `ticket_counters` (`production_line_id`, `year`, `counter`) VALUES
(1, 2026, 2),
(2, 2026, 2),
(3, 2026, 2),
(4, 2026, 2),
(5, 2026, 2),
(6, 2026, 2)
ON DUPLICATE KEY UPDATE `counter` = VALUES(`counter`);

-- ────────────────────────────────────────────────────────────
-- Kategorie awarii — zgodne z cmms2.sql
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `failure_categories`
    (`id`, `name`, `label`, `color`, `sort_order`, `is_active`) VALUES
(1, 'electrical',              'Elektryczna',  '#dc2626', 1, 1),
(2, 'automation',              'Automatyka',   '#d97706', 2, 1),
(3, 'mechanical',              'Mechaniczna',  '#0a2463', 3, 1),
(4, 'pneumatyczna_1778916781', 'Pneumatyczna', '#0891b2', 4, 1),
(5, 'hydrauliczna_1779179017', 'Hydrauliczna', '#33cc5e', 5, 1);

-- ────────────────────────────────────────────────────────────
-- Słownik usterek — zgodny z cmms2.sql
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `failure_dictionary` (`id`, `category_id`, `title`, `is_active`) VALUES
-- Elektryczna
( 1, 1, 'Brak zasilania maszyny', 1),
( 2, 1, 'Przepalony bezpiecznik', 1),
( 3, 1, 'Uszkodzony falownik',    1),
( 4, 1, 'Zwarcie w sterowniku',   1),
-- Automatyka
( 5, 2, 'Błąd sterownika PLC',         1),
( 6, 2, 'Awaria czujnika indukcyjnego', 1),
( 7, 2, 'Błąd komunikacji sieciowej',   1),
( 8, 2, 'Awaria panelu HMI',            1),
-- Mechaniczna
( 9, 3, 'Pęknięty pas transmisyjny', 1),
(10, 3, 'Awaria łożyska',            1),
(11, 3, 'Zacięcie mechanizmu',       1),
(12, 3, 'Wyciek oleju / smaru',      1),
(13, 3, 'Pęknięta sprężyna',         1),
-- Pneumatyczna
(14, 4, 'Pęknięty wąż', 1);

-- ────────────────────────────────────────────────────────────
-- Statusy awarii — zgodne z cmms2.sql
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `failure_statuses`
    (`id`, `label`, `color`, `sort_order`, `is_initial`, `is_final`, `is_observed`, `is_active`) VALUES
(1, 'Nowa awaria',           '#dc2626', 1, 1, 0, 0, 1),
(3, 'W trakcie naprawy',     '#0e7c07', 2, 0, 0, 0, 1),
(4, 'Oczekuje na części',    '#7c3aed', 3, 0, 0, 0, 1),
(5, 'W trakcie obserwacji',  '#b45309', 4, 0, 0, 1, 1),
(6, 'Naprawiona',            '#374151', 5, 0, 1, 0, 1);

-- ────────────────────────────────────────────────────────────
-- Objawy awarii — zgodne z cmms2.sql
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `failure_symptoms` (`id`, `name`, `sort_order`, `is_active`) VALUES
(1,  'Maszyna nie reaguje',         1,  1),
(2,  'Spadki napięcia',             2,  1),
(3,  'Brak komunikacji',            3,  1),
(4,  'Zatrzymanie linii',           4,  1),
(5,  'Nietypowy dźwięk / wibracje', 5,  1),
(6,  'Wyciek płynu',                6,  1),
(7,  'Błąd na panelu operatorskim', 7,  1),
(8,  'Przegrzewanie się urządzenia',8,  1),
(9,  'Brak ruchu / zacięcie',       9,  1),
(10, 'Inne objawy',                 10, 1);

-- ────────────────────────────────────────────────────────────
-- Szablony DUR — zgodne z cmms2.sql
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `maintenance_templates`
    (`id`, `name`, `review_type`, `checklist`, `is_active`, `created_by`) VALUES
(1, 'Linia W1', 'monthly',
 '- Likwidacja wycieków\r\n- Wymiana łańcucha na nawijaku\r\n- Przegląd elektryki\r\n- Przedmuch szaf\r\n- Wymiana włókniny filtrującej',
 1, 1),
(2, 'Linia A3', 'monthly',
 '- Wymiana elastomerów na układarce arkuszy\r\n- Naprawa siłownika rolki wprowadzającej\r\n- Przedmuch szaf elektrycznych, silników\r\n- Wymiana włókniny filtrującej',
 1, 1),
(3, 'Linia A2', 'monthly',
 '- Smarowanie gilotyny\r\n- Smarowanie prostowarki\r\n- Przedmuch szaf elektrycznych i silników\r\n- Przegląd elektryki',
 1, 1),
(4, 'Linia A5', 'monthly',
 '- Wymiana elastomerów na układarce arkuszy\r\n- Naprawa siłownika rolki wprowadzającej\r\n- Przedmuch szaf elektrycznych\r\n- Wymiana włókniny filtrującej',
 1, 1),
(5, 'Linia A1', 'monthly',
 '- Smarowanie prostowarek\r\n- Przedmuch elektryki\r\n- Wymiana włókniny filtrującej w szafach elektrycznych\r\n- Regulacja układarki arkuszy, naprawa dwóch siłowników zrzutu arkusza',
 1, 1),
(6, 'Linia W2', 'monthly',
 '- Wymiana uszczelnień tłoka na siłowniku ramienia separatora na nawijaku\r\n- Likwidacja wycieku na siłowniku wypychu zespołu hamulca\r\n- Przedmuch chłodnicy oleju na agregacie hydraulicznym\r\n- Uzupełnienie poziomu oleju hydraulicznego w agregacie\r\n- Kontrola i dokręcenie mocowania wału jazdy zespołem hamulca',
 1, 1);

-- ────────────────────────────────────────────────────────────
-- Harmonogramy DUR — zgodne z cmms2.sql
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `maintenance_schedules`
    (`id`, `production_line_id`, `template_id`, `review_type`, `interval_days`, `next_due_date`, `is_active`) VALUES
(10, 1, NULL, 'monthly',  30, '2026-06-26', 1),
(11, 2, NULL, 'monthly',  30, '2026-07-01', 1),
(13, 3, NULL, 'monthly',  30, '2026-06-30', 1),
(14, 2, NULL, 'periodic', 30, '2026-06-17', 1),
(15, 4, NULL, 'periodic', 30, '2026-06-24', 1);

-- ────────────────────────────────────────────────────────────
-- Ustawienia systemowe — zgodne z cmms2.sql
-- ────────────────────────────────────────────────────────────
INSERT INTO `settings` (`id`, `skey`, `svalue`, `label`) VALUES
( 1, 'app_name',             'Moduł Serwis',       'Nazwa aplikacji'),
( 2, 'app_version',          '0.1-dev',            'Wersja systemu'),
( 3, 'company_name',         'FINCO Stal Serwis',  'Nazwa firmy'),
( 4, 'dur_warning_days',     '7',                  'Ostrzeżenie DUR (dni przed terminem)'),
( 5, 'records_per_page',     '15',                 'Liczba rekordów na stronę'),
( 6, 'role_perms_admin',
    '{"report":1,"dashboard":1,"failures":1,"dur":1,"statuses":1,"admin":1}',
    'Uprawnienia roli: Administrator'),
( 7, 'role_perms_mechanic',
    '{"report":0,"dashboard":1,"failures":1,"dur":1,"statuses":1,"admin":0}',
    'Uprawnienia roli: Mechanik'),
( 8, 'role_perms_operator',
    '{"report":1,"dashboard":0,"failures":0,"dur":1,"statuses":0,"admin":0}',
    'Uprawnienia roli: Operator'),
(27, 'dur_type_labels',      '[]',                 'dur_type_labels'),
(29, 'dur_active_review_types',
    '["weekly","monthly","ad_hoc","periodic"]',
    'dur_active_review_types'),
(40, 'dur_review_statuses',
    '{"completed":{"label":"Zakończony","color":"#008000"},"partial":{"label":"Częściowy","color":"#d97706"},"interrupted":{"label":"Przerwany","color":"#dc2626"}}',
    'dur_review_statuses'),
(94,  'session_idle_timeout',     '5',  'Czas bezczynności przed wylogowaniem (minuty, 0 = wyłączone)'),
(123, 'observation_window_hours', '48', 'Czas okna obserwacji awarii (godziny)')
ON DUPLICATE KEY UPDATE `svalue` = VALUES(`svalue`);

-- ────────────────────────────────────────────────────────────
-- Kategorie części zamiennych — zgodne z cmms2.sql
-- (bez listy części — failure_spare_parts i dur_spare_parts puste)
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `spare_part_categories`
    (`id`, `name`, `color`, `sort_order`, `is_active`) VALUES
(1, 'Elektryka',  '#0891b2', 1, 1),
(2, 'Mechanika',  '#7c3aed', 2, 1),
(3, 'Hydraulika', '#d97706', 3, 1),
(4, 'Pneumatyka', '#dc2626', 4, 1),
(5, 'Inne',       '#6c757d', 5, 1);

-- ============================================================
-- ZGŁOSZENIA AWARII — 2 na każdą linię, status: Nowa awaria (id=1)
-- operator1 (id=14) i operator2 (id=15) na przemian
-- Format numeru: 000N/PREFIX/ROK
-- ============================================================

-- ── Linia A1 (production_line_id=1, prefix='A1') ───────────
-- Zgłoszenie 1/2 → operator1
INSERT IGNORE INTO `failures`
    (`id`, `ticket_number`, `production_line_id`, `subsystem_id`,
     `symptom_id`, `other_symptom`, `category_id`, `status_id`,
     `dictionary_item_id`, `other_failure`, `mechanic_note`,
     `reporter_acronym`, `reporter_name`, `reporter_user_id`,
     `description`, `closed_at`, `observation_started_at`,
     `created_at`, `updated_at`) VALUES
(101, '0001/A1/2026', 1, 27, 5, 0, NULL, 1, NULL, 0, NULL,
 'operator1', 'Adam Kowalski', 14,
 'Prostowarka 1 wydaje głośny, rytmiczny dźwięk podczas pracy.',
 NULL, NULL, '2026-06-01 06:00:00', '2026-06-01 06:00:00');

-- Zgłoszenie 2/2 → operator2
INSERT IGNORE INTO `failures`
    (`id`, `ticket_number`, `production_line_id`, `subsystem_id`,
     `symptom_id`, `other_symptom`, `category_id`, `status_id`,
     `dictionary_item_id`, `other_failure`, `mechanic_note`,
     `reporter_acronym`, `reporter_name`, `reporter_user_id`,
     `description`, `closed_at`, `observation_started_at`,
     `created_at`, `updated_at`) VALUES
(102, '0002/A1/2026', 1, 40, 2, 0, NULL, 1, NULL, 0, NULL,
 'operator2', 'Paweł Nowak', 15,
 'Odnotowano krótkotrwałe spadki napięcia przy starcie linii.',
 NULL, NULL, '2026-06-01 07:30:00', '2026-06-01 07:30:00');

-- ── Linia A2 (production_line_id=2, prefix='A2') ───────────
INSERT IGNORE INTO `failures`
    (`id`, `ticket_number`, `production_line_id`, `subsystem_id`,
     `symptom_id`, `other_symptom`, `category_id`, `status_id`,
     `dictionary_item_id`, `other_failure`, `mechanic_note`,
     `reporter_acronym`, `reporter_name`, `reporter_user_id`,
     `description`, `closed_at`, `observation_started_at`,
     `created_at`, `updated_at`) VALUES
(103, '0001/A2/2026', 2, 137, 4, 0, NULL, 1, NULL, 0, NULL,
 'operator1', 'Adam Kowalski', 14,
 'Gilotyna zatrzymała się w połowie cyklu cięcia.',
 NULL, NULL, '2026-06-01 06:10:00', '2026-06-01 06:10:00');

INSERT IGNORE INTO `failures`
    (`id`, `ticket_number`, `production_line_id`, `subsystem_id`,
     `symptom_id`, `other_symptom`, `category_id`, `status_id`,
     `dictionary_item_id`, `other_failure`, `mechanic_note`,
     `reporter_acronym`, `reporter_name`, `reporter_user_id`,
     `description`, `closed_at`, `observation_started_at`,
     `created_at`, `updated_at`) VALUES
(104, '0002/A2/2026', 2, 135, 6, 0, NULL, 1, NULL, 0, NULL,
 'operator2', 'Paweł Nowak', 15,
 'Stwierdzono wyciek oleju hydraulicznego z foliowanki.',
 NULL, NULL, '2026-06-01 07:45:00', '2026-06-01 07:45:00');

-- ── Linia A3 (production_line_id=3, prefix='A3') ───────────
INSERT IGNORE INTO `failures`
    (`id`, `ticket_number`, `production_line_id`, `subsystem_id`,
     `symptom_id`, `other_symptom`, `category_id`, `status_id`,
     `dictionary_item_id`, `other_failure`, `mechanic_note`,
     `reporter_acronym`, `reporter_name`, `reporter_user_id`,
     `description`, `closed_at`, `observation_started_at`,
     `created_at`, `updated_at`) VALUES
(105, '0001/A3/2026', 3, 44, 1, 0, NULL, 1, NULL, 0, NULL,
 'operator1', 'Adam Kowalski', 14,
 'Prostowarka przestała reagować na sygnały sterownicze.',
 NULL, NULL, '2026-06-01 06:20:00', '2026-06-01 06:20:00');

INSERT IGNORE INTO `failures`
    (`id`, `ticket_number`, `production_line_id`, `subsystem_id`,
     `symptom_id`, `other_symptom`, `category_id`, `status_id`,
     `dictionary_item_id`, `other_failure`, `mechanic_note`,
     `reporter_acronym`, `reporter_name`, `reporter_user_id`,
     `description`, `closed_at`, `observation_started_at`,
     `created_at`, `updated_at`) VALUES
(106, '0002/A3/2026', 3, 48, 3, 0, NULL, 1, NULL, 0, NULL,
 'operator2', 'Paweł Nowak', 15,
 'Brak komunikacji między panelem HMI a układarką arkuszy.',
 NULL, NULL, '2026-06-01 08:00:00', '2026-06-01 08:00:00');

-- ── Linia W1 (production_line_id=4, prefix='W1') ───────────
INSERT IGNORE INTO `failures`
    (`id`, `ticket_number`, `production_line_id`, `subsystem_id`,
     `symptom_id`, `other_symptom`, `category_id`, `status_id`,
     `dictionary_item_id`, `other_failure`, `mechanic_note`,
     `reporter_acronym`, `reporter_name`, `reporter_user_id`,
     `description`, `closed_at`, `observation_started_at`,
     `created_at`, `updated_at`) VALUES
(107, '0001/W1/2026', 4, 121, 9, 0, NULL, 1, NULL, 0, NULL,
 'operator1', 'Adam Kowalski', 14,
 'Gilotyna nie wykonuje ruchu — zatrzymanie na pozycji zerowej.',
 NULL, NULL, '2026-06-01 06:30:00', '2026-06-01 06:30:00');

INSERT IGNORE INTO `failures`
    (`id`, `ticket_number`, `production_line_id`, `subsystem_id`,
     `symptom_id`, `other_symptom`, `category_id`, `status_id`,
     `dictionary_item_id`, `other_failure`, `mechanic_note`,
     `reporter_acronym`, `reporter_name`, `reporter_user_id`,
     `description`, `closed_at`, `observation_started_at`,
     `created_at`, `updated_at`) VALUES
(108, '0002/W1/2026', 4, 130, 6, 0, NULL, 1, NULL, 0, NULL,
 'operator2', 'Paweł Nowak', 15,
 'Widoczny wyciek na przewodzie hydraulicznym przy nawijaku.',
 NULL, NULL, '2026-06-01 08:15:00', '2026-06-01 08:15:00');

-- ── Linia A5 (production_line_id=5, prefix='A5') ───────────
INSERT IGNORE INTO `failures`
    (`id`, `ticket_number`, `production_line_id`, `subsystem_id`,
     `symptom_id`, `other_symptom`, `category_id`, `status_id`,
     `dictionary_item_id`, `other_failure`, `mechanic_note`,
     `reporter_acronym`, `reporter_name`, `reporter_user_id`,
     `description`, `closed_at`, `observation_started_at`,
     `created_at`, `updated_at`) VALUES
(109, '0001/A5/2026', 5, 73, 7, 0, NULL, 1, NULL, 0, NULL,
 'operator1', 'Adam Kowalski', 14,
 'Na panelu prostowanki wyświetla się kod błędu E04.',
 NULL, NULL, '2026-06-01 06:40:00', '2026-06-01 06:40:00');

INSERT IGNORE INTO `failures`
    (`id`, `ticket_number`, `production_line_id`, `subsystem_id`,
     `symptom_id`, `other_symptom`, `category_id`, `status_id`,
     `dictionary_item_id`, `other_failure`, `mechanic_note`,
     `reporter_acronym`, `reporter_name`, `reporter_user_id`,
     `description`, `closed_at`, `observation_started_at`,
     `created_at`, `updated_at`) VALUES
(110, '0002/A5/2026', 5, 77, 5, 0, NULL, 1, NULL, 0, NULL,
 'operator2', 'Paweł Nowak', 15,
 'Układarka arkuszy generuje nietypowe wibracje przy podnoszeniu.',
 NULL, NULL, '2026-06-01 08:30:00', '2026-06-01 08:30:00');

-- ── Linia W2 (production_line_id=6, prefix='W2') ───────────
INSERT IGNORE INTO `failures`
    (`id`, `ticket_number`, `production_line_id`, `subsystem_id`,
     `symptom_id`, `other_symptom`, `category_id`, `status_id`,
     `dictionary_item_id`, `other_failure`, `mechanic_note`,
     `reporter_acronym`, `reporter_name`, `reporter_user_id`,
     `description`, `closed_at`, `observation_started_at`,
     `created_at`, `updated_at`) VALUES
(111, '0001/W2/2026', 6, 107, 4, 0, NULL, 1, NULL, 0, NULL,
 'operator1', 'Adam Kowalski', 14,
 'Gilotyna zatrzymała się podczas cięcia — linia stoi.',
 NULL, NULL, '2026-06-01 06:50:00', '2026-06-01 06:50:00');

INSERT IGNORE INTO `failures`
    (`id`, `ticket_number`, `production_line_id`, `subsystem_id`,
     `symptom_id`, `other_symptom`, `category_id`, `status_id`,
     `dictionary_item_id`, `other_failure`, `mechanic_note`,
     `reporter_acronym`, `reporter_name`, `reporter_user_id`,
     `description`, `closed_at`, `observation_started_at`,
     `created_at`, `updated_at`) VALUES
(112, '0002/W2/2026', 6, 116, 6, 0, NULL, 1, NULL, 0, NULL,
 'operator2', 'Paweł Nowak', 15,
 'Stwierdzono wyciek oleju z agregatu hydraulicznego.',
 NULL, NULL, '2026-06-01 08:45:00', '2026-06-01 08:45:00');

-- ── Historia zgłoszeń (wpisy "created") ────────────────────
INSERT IGNORE INTO `failure_history`
    (`failure_id`, `user_id`, `actor_name`, `action`, `old_status_id`, `new_status_id`, `note`, `created_at`) VALUES
(101, 14, 'operator1 – Adam Kowalski',  'created', NULL, 1, 'Zgłoszenie awarii utworzone', '2026-06-01 06:00:00'),
(102, 15, 'operator2 – Paweł Nowak',    'created', NULL, 1, 'Zgłoszenie awarii utworzone', '2026-06-01 07:30:00'),
(103, 14, 'operator1 – Adam Kowalski',  'created', NULL, 1, 'Zgłoszenie awarii utworzone', '2026-06-01 06:10:00'),
(104, 15, 'operator2 – Paweł Nowak',    'created', NULL, 1, 'Zgłoszenie awarii utworzone', '2026-06-01 07:45:00'),
(105, 14, 'operator1 – Adam Kowalski',  'created', NULL, 1, 'Zgłoszenie awarii utworzone', '2026-06-01 06:20:00'),
(106, 15, 'operator2 – Paweł Nowak',    'created', NULL, 1, 'Zgłoszenie awarii utworzone', '2026-06-01 08:00:00'),
(107, 14, 'operator1 – Adam Kowalski',  'created', NULL, 1, 'Zgłoszenie awarii utworzone', '2026-06-01 06:30:00'),
(108, 15, 'operator2 – Paweł Nowak',    'created', NULL, 1, 'Zgłoszenie awarii utworzone', '2026-06-01 08:15:00'),
(109, 14, 'operator1 – Adam Kowalski',  'created', NULL, 1, 'Zgłoszenie awarii utworzone', '2026-06-01 06:40:00'),
(110, 15, 'operator2 – Paweł Nowak',    'created', NULL, 1, 'Zgłoszenie awarii utworzone', '2026-06-01 08:30:00'),
(111, 14, 'operator1 – Adam Kowalski',  'created', NULL, 1, 'Zgłoszenie awarii utworzone', '2026-06-01 06:50:00'),
(112, 15, 'operator2 – Paweł Nowak',    'created', NULL, 1, 'Zgłoszenie awarii utworzone', '2026-06-01 08:45:00');

-- ============================================================
-- PRZEGLĄDY DUR — 2 na każdą linię
-- Mechanicy: Tomek (11), Roman (12), Andrii (13) — rotacyjnie
-- Szablon: dopasowany do linii. Status: completed.
-- ============================================================

-- ── Linia A1 (line 1) — szablon 5 ─────────────────────────
-- Przegląd 1 → Tomek (id=11)
INSERT IGNORE INTO `maintenance_reviews`
    (`id`, `production_line_id`, `subsystem_id`, `template_id`, `schedule_id`,
     `performed_by`, `review_type`, `review_date`, `duration_minutes`,
     `activities`, `parts_used`, `notes`, `status`, `next_review_date`,
     `created_at`) VALUES
(201, 1, NULL, 5, 10, 11, 'monthly', '2026-06-01', 60,
 '- Smarowanie prostowarek\r\n- Przedmuch elektryki\r\n- Wymiana włókniny filtrującej w szafach elektrycznych\r\n- Regulacja układarki arkuszy',
 NULL, 'Przegląd wykonany bez uwag.', 'completed', '2026-07-01', '2026-06-01 07:00:00');

-- Przegląd 2 → Roman (id=12)
INSERT IGNORE INTO `maintenance_reviews`
    (`id`, `production_line_id`, `subsystem_id`, `template_id`, `schedule_id`,
     `performed_by`, `review_type`, `review_date`, `duration_minutes`,
     `activities`, `parts_used`, `notes`, `status`, `next_review_date`,
     `created_at`) VALUES
(202, 1, NULL, 5, 10, 12, 'periodic', '2026-06-01', 45,
 '- Smarowanie prostowarek\r\n- Przedmuch elektryki\r\n- Regulacja układarki arkuszy',
 NULL, 'Zalecana wymiana filtra za ok. 2 tygodnie.', 'completed', NULL, '2026-06-01 10:00:00');

-- ── Linia A2 (line 2) — szablon 3 ─────────────────────────
-- Przegląd 1 → Andrii (id=13)
INSERT IGNORE INTO `maintenance_reviews`
    (`id`, `production_line_id`, `subsystem_id`, `template_id`, `schedule_id`,
     `performed_by`, `review_type`, `review_date`, `duration_minutes`,
     `activities`, `parts_used`, `notes`, `status`, `next_review_date`,
     `created_at`) VALUES
(203, 2, NULL, 3, 11, 13, 'monthly', '2026-06-01', 55,
 '- Smarowanie gilotyny\r\n- Smarowanie prostowarki\r\n- Przedmuch szaf elektrycznych i silników\r\n- Przegląd elektryki',
 NULL, 'Wszystko w normie.', 'completed', '2026-07-01', '2026-06-01 07:15:00');

-- Przegląd 2 → Tomek (id=11)
INSERT IGNORE INTO `maintenance_reviews`
    (`id`, `production_line_id`, `subsystem_id`, `template_id`, `schedule_id`,
     `performed_by`, `review_type`, `review_date`, `duration_minutes`,
     `activities`, `parts_used`, `notes`, `status`, `next_review_date`,
     `created_at`) VALUES
(204, 2, NULL, 3, 14, 11, 'periodic', '2026-06-01', 40,
 '- Smarowanie gilotyny\r\n- Przedmuch szaf elektrycznych i silników',
 NULL, 'Drobny wyciek smaru przy gilotynie — uszczelnić.', 'completed', NULL, '2026-06-01 10:15:00');

-- ── Linia A3 (line 3) — szablon 2 ─────────────────────────
-- Przegląd 1 → Roman (id=12)
INSERT IGNORE INTO `maintenance_reviews`
    (`id`, `production_line_id`, `subsystem_id`, `template_id`, `schedule_id`,
     `performed_by`, `review_type`, `review_date`, `duration_minutes`,
     `activities`, `parts_used`, `notes`, `status`, `next_review_date`,
     `created_at`) VALUES
(205, 3, NULL, 2, 13, 12, 'monthly', '2026-06-01', 70,
 '- Wymiana elastomerów na układarce arkuszy\r\n- Naprawa siłownika rolki wprowadzającej\r\n- Przedmuch szaf elektrycznych, silników\r\n- Wymiana włókniny filtrującej',
 NULL, 'Wymieniono dwa elastomery — OK.', 'completed', '2026-07-01', '2026-06-01 07:30:00');

-- Przegląd 2 → Andrii (id=13)
INSERT IGNORE INTO `maintenance_reviews`
    (`id`, `production_line_id`, `subsystem_id`, `template_id`, `schedule_id`,
     `performed_by`, `review_type`, `review_date`, `duration_minutes`,
     `activities`, `parts_used`, `notes`, `status`, `next_review_date`,
     `created_at`) VALUES
(206, 3, NULL, 2, 13, 13, 'ad_hoc', '2026-06-01', 30,
 '- Naprawa siłownika rolki wprowadzającej\r\n- Przedmuch szaf elektrycznych',
 NULL, 'Przegląd doraźny po zgłoszeniu operatora.', 'completed', NULL, '2026-06-01 10:30:00');

-- ── Linia W1 (line 4) — szablon 1 ─────────────────────────
-- Przegląd 1 → Tomek (id=11)
INSERT IGNORE INTO `maintenance_reviews`
    (`id`, `production_line_id`, `subsystem_id`, `template_id`, `schedule_id`,
     `performed_by`, `review_type`, `review_date`, `duration_minutes`,
     `activities`, `parts_used`, `notes`, `status`, `next_review_date`,
     `created_at`) VALUES
(207, 4, NULL, 1, 15, 11, 'periodic', '2026-06-01', 90,
 '- Likwidacja wycieków\r\n- Wymiana łańcucha na nawijaku\r\n- Przegląd elektryki\r\n- Przedmuch szaf\r\n- Wymiana włókniny filtrującej',
 NULL, 'Łańcuch nawijaka wymieniony — poprzedni zużyty.', 'completed', '2026-07-01', '2026-06-01 07:45:00');

-- Przegląd 2 → Roman (id=12)
INSERT IGNORE INTO `maintenance_reviews`
    (`id`, `production_line_id`, `subsystem_id`, `template_id`, `schedule_id`,
     `performed_by`, `review_type`, `review_date`, `duration_minutes`,
     `activities`, `parts_used`, `notes`, `status`, `next_review_date`,
     `created_at`) VALUES
(208, 4, NULL, 1, 15, 12, 'monthly', '2026-06-01', 50,
 '- Likwidacja wycieków\r\n- Przegląd elektryki\r\n- Przedmuch szaf',
 NULL, 'Brak nowych wycieków.', 'completed', NULL, '2026-06-01 10:45:00');

-- ── Linia A5 (line 5) — szablon 4 ─────────────────────────
-- Przegląd 1 → Andrii (id=13)
INSERT IGNORE INTO `maintenance_reviews`
    (`id`, `production_line_id`, `subsystem_id`, `template_id`, `schedule_id`,
     `performed_by`, `review_type`, `review_date`, `duration_minutes`,
     `activities`, `parts_used`, `notes`, `status`, `next_review_date`,
     `created_at`) VALUES
(209, 5, NULL, 4, NULL, 13, 'monthly', '2026-06-01', 65,
 '- Wymiana elastomerów na układarce arkuszy\r\n- Naprawa siłownika rolki wprowadzającej\r\n- Przedmuch szaf elektrycznych\r\n- Wymiana włókniny filtrującej',
 NULL, 'Siłownik rolki sprawny — naprawa odroczona.', 'completed', '2026-07-01', '2026-06-01 08:00:00');

-- Przegląd 2 → Tomek (id=11)
INSERT IGNORE INTO `maintenance_reviews`
    (`id`, `production_line_id`, `subsystem_id`, `template_id`, `schedule_id`,
     `performed_by`, `review_type`, `review_date`, `duration_minutes`,
     `activities`, `parts_used`, `notes`, `status`, `next_review_date`,
     `created_at`) VALUES
(210, 5, NULL, 4, NULL, 11, 'ad_hoc', '2026-06-01', 35,
 '- Przedmuch szaf elektrycznych\r\n- Kontrola poziomu smarów',
 NULL, 'Doraźna kontrola po sygnale operatora — OK.', 'completed', NULL, '2026-06-01 11:00:00');

-- ── Linia W2 (line 6) — szablon 6 ─────────────────────────
-- Przegląd 1 → Roman (id=12)
INSERT IGNORE INTO `maintenance_reviews`
    (`id`, `production_line_id`, `subsystem_id`, `template_id`, `schedule_id`,
     `performed_by`, `review_type`, `review_date`, `duration_minutes`,
     `activities`, `parts_used`, `notes`, `status`, `next_review_date`,
     `created_at`) VALUES
(211, 6, NULL, 6, NULL, 12, 'monthly', '2026-06-01', 80,
 '- Wymiana uszczelnień tłoka na siłowniku ramienia separatora na nawijaku\r\n- Likwidacja wycieku na siłowniku wypychu zespołu hamulca\r\n- Przedmuch chłodnicy oleju na agregacie hydraulicznym\r\n- Uzupełnienie poziomu oleju hydraulicznego w agregacie\r\n- Kontrola i dokręcenie mocowania wału jazdy zespołem hamulca',
 NULL, 'Uszczelnienia wymienione, poziom oleju uzupełniony.', 'completed', '2026-07-01', '2026-06-01 08:15:00');

-- Przegląd 2 → Andrii (id=13)
INSERT IGNORE INTO `maintenance_reviews`
    (`id`, `production_line_id`, `subsystem_id`, `template_id`, `schedule_id`,
     `performed_by`, `review_type`, `review_date`, `duration_minutes`,
     `activities`, `parts_used`, `notes`, `status`, `next_review_date`,
     `created_at`) VALUES
(212, 6, NULL, 6, NULL, 13, 'periodic', '2026-06-01', 45,
 '- Przedmuch chłodnicy oleju na agregacie hydraulicznym\r\n- Kontrola i dokręcenie mocowania wału jazdy zespołem hamulca',
 NULL, 'Mocowanie wału wymagało dokręcenia.', 'completed', NULL, '2026-06-01 11:15:00');

SET FOREIGN_KEY_CHECKS = 1;

/*
 * ============================================================
 * DOKUMENTACJA PLIKU: seed.sql
 * ============================================================
 * Plik:         seed.sql
 * Opis:         Dane startowe aplikacji Moduł Serwis v2.7
 * Wersja:       2.7 (zgodna z cmms2.sql)
 * Zależności:   schema.sql (musi być wykonany wcześniej)
 * Zawiera:
 *   - Role, Użytkownicy (admin + 3 mechanicy + 2 operatorzy)
 *   - 6 linii produkcyjnych + podzespoły
 *   - Kategorie awarii (5), Słownik usterek (14), Statusy (5)
 *   - Objawy awarii (10), Szablony DUR (6), Harmonogramy (5)
 *   - Ustawienia systemowe (10 kluczy)
 *   - Kategorie części zamiennych (5) — bez listy części
 *   - 12 zgłoszeń awarii (2 na linię, status: Nowa awaria)
 *     operator1 i operator2 na przemian
 *   - 12 przeglądów DUR (2 na linię, mechanicy rotacyjnie)
 * Uwagi:        failure_spare_parts i dur_spare_parts są puste
 * ============================================================
 */
