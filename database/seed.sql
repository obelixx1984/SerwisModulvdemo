-- ============================================================
-- Moduł Serwis v2 — Dane startowe (seed)
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ────────────────────────────────────────────────────────────
-- Role systemowe
-- ────────────────────────────────────────────────────────────
INSERT INTO `roles` (`id`, `name`, `label`) VALUES
(1, 'admin',    'Administrator'),
(2, 'mechanic', 'Mechanik'),
(3, 'operator', 'Operator (tylko odczyt)');

-- ────────────────────────────────────────────────────────────
-- Użytkownicy (hasło: password)
-- POPRAWKA 5: pole nickname
-- ────────────────────────────────────────────────────────────
-- UŻYTKOWNICY są wstawiani przez install.php z prawidłowymi hashami PHP
-- Jeśli używasz seed.sql bezpośrednio (bez install.php), użyj poniższych wierszy:
-- Hasła zostaną wygenerowane przez install.php z password_hash() serwera PHP
-- Uruchom: http://localhost/cmms/install.php
INSERT INTO `users` (`id`, `role_id`, `name`, `nickname`, `email`, `password_hash`, `is_active`) VALUES
(1, 1, 'Administrator',   'admin',    'admin@serwis.local',        '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B3Do9B2', 1),
(2, 2, 'Jan Kowalski',    'mechanik', 'jan.kowalski@serwis.local', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B3Do9B2', 1),
(3, 2, 'Piotr Nowak',     'pnowak',   'piotr.nowak@serwis.local',  '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B3Do9B2', 1);
-- Powyższy hash odpowiada haslu: password

-- ────────────────────────────────────────────────────────────
-- Pracownicy z akronimami
-- ────────────────────────────────────────────────────────────
INSERT INTO `employees` (`id`, `acronym`, `name`, `position`, `is_active`) VALUES
(1, 'JKO', 'Jan Kowalski',       'Operator Zm. I',   1),
(2, 'ANO', 'Anna Nowak',          'Operator Zm. II',  1),
(3, 'PZA', 'Piotr Zając',         'Mechanik',         1),
(4, 'MWI', 'Marcin Wiśniewski',   'Mechanik',         1),
(5, 'EDA', 'Ewa Dąbrowska',       'Mistrz Zm. I',     1),
(6, 'TLE', 'Tomasz Lewandowski',  'Operator Zm. III', 0);

-- ────────────────────────────────────────────────────────────
-- Linie produkcyjne — POPRAWKA 1: pole prefix
-- ────────────────────────────────────────────────────────────
INSERT INTO `production_lines` (`id`, `name`, `prefix`, `description`, `is_active`) VALUES
(1, 'Linia 1 - Montaż',    'A1', 'Linia montażu główna',     1),
(2, 'Linia 2 - Lakiernia', 'L2', 'Linia lakierowania',       1),
(3, 'Linia 3 - Pakowanie', 'P3', 'Linia pakowania wyrobów',  1),
(4, 'Linia 4 - Spawalnia', 'W4', 'Linia spawania MIG/MAG',   1),
(5, 'Linia 5 - CNC',       'C5', 'Obrabiarki CNC',           1);

-- ────────────────────────────────────────────────────────────
-- Podzespoły linii
-- ────────────────────────────────────────────────────────────
INSERT INTO `line_subsystems` (`production_line_id`, `name`, `sort_order`) VALUES
(1, 'Rozwijak',             1),
(1, 'Nawijak',              2),
(1, 'Pakowaczka',           3),
(2, 'Kabina lakiernicza',   1),
(2, 'Suszarnia',            2),
(4, 'Spawarka MIG 1',       1),
(4, 'Spawarka MIG 2',       2);

-- ────────────────────────────────────────────────────────────
-- Liczniki zgłoszeń (startowe wartości)
-- ────────────────────────────────────────────────────────────
INSERT INTO `ticket_counters` (`production_line_id`, `year`, `counter`) VALUES
(1, 2026, 0),
(2, 2026, 0),
(3, 2026, 0),
(4, 2026, 0),
(5, 2026, 0);

-- ────────────────────────────────────────────────────────────
-- Kategorie awarii — POPRAWKA 11: dynamiczne z kolorem
-- ────────────────────────────────────────────────────────────
INSERT INTO `failure_categories` (`id`, `name`, `label`, `color`, `sort_order`) VALUES
(1, 'electrical', 'Elektryczna', '#dc2626', 1),
(2, 'automation',  'Automatyka',  '#d97706', 2),
(3, 'mechanical',  'Mechaniczna', '#0a2463', 3);

-- ────────────────────────────────────────────────────────────
-- Słownik usterek
-- ────────────────────────────────────────────────────────────
INSERT INTO `failure_dictionary` (`category_id`, `title`) VALUES
(1, 'Brak zasilania maszyny'),
(1, 'Przepalony bezpiecznik'),
(1, 'Uszkodzony falownik'),
(1, 'Zwarcie w sterowniku'),
(2, 'Błąd sterownika PLC'),
(2, 'Awaria czujnika indukcyjnego'),
(2, 'Błąd komunikacji sieciowej'),
(2, 'Awaria panelu HMI'),
(3, 'Pęknięty pas transmisyjny'),
(3, 'Awaria łożyska'),
(3, 'Zacięcie mechanizmu'),
(3, 'Wyciek oleju / smaru'),
(3, 'Pęknięta sprężyna');

-- ────────────────────────────────────────────────────────────
-- Statusy zgłoszeń — POPRAWKA 6: dynamiczne
-- ────────────────────────────────────────────────────────────
INSERT INTO `failure_statuses` (`id`, `label`, `color`, `sort_order`, `is_initial`, `is_final`, `is_active`) VALUES
(1, 'Nowa awaria',        '#dc2626', 1, 1, 0, 1),
(2, 'Przyjęta',           '#d97706', 2, 0, 0, 1),
(3, 'W trakcie naprawy',  '#0a2463', 3, 0, 0, 1),
(4, 'Oczekuje na części', '#7c3aed', 4, 0, 0, 1),
(5, 'Naprawiona',         '#16a34a', 5, 0, 0, 1),
(6, 'Zamknięta',          '#374151', 6, 0, 1, 1);

-- ────────────────────────────────────────────────────────────
-- Szablony DUR
-- ────────────────────────────────────────────────────────────
INSERT INTO `maintenance_templates` (`id`, `name`, `review_type`, `checklist`, `created_by`) VALUES
(1, 'Przegląd tygodniowy standardowy', 'weekly',
 '- Kontrola wizualna maszyny i otoczenia\n- Sprawdzenie poziomu olejów i smarów\n- Smarowanie łańcuchów przenośnika\n- Kontrola stanu pasów i łańcuchów\n- Sprawdzenie czujników bezpieczeństwa', 1),
(2, 'Przegląd miesięczny standardowy', 'monthly',
 '- Kontrola wizualna maszyny — stan mechaniczny\n- Smarowanie prowadnic liniowych\n- Sprawdzenie napięcia pasów — regulacja jeśli konieczna\n- Kontrola czujników indukcyjnych i optycznych\n- Sprawdzenie złącz elektrycznych\n- Pomiar rezystancji izolacji silnika\n- Czyszczenie filtrów układu chłodzenia\n- Kontrola parametrów sterownika PLC', 1),
(3, 'Przegląd kwartalny CNC', 'quarterly',
 '- Kalibracja geometryczna osi X/Y/Z\n- Wymiana oleju wrzeciona\n- Czyszczenie prowadnic kulkowych\n- Kontrola napędu posuwowego\n- Sprawdzenie narzędzi i uchwytu', 1),
(4, 'Przegląd spawalniczy', 'monthly',
 '- Kontrola i wymiana dysz gazowych\n- Sprawdzenie podajnika drutu\n- Czyszczenie uchwytu spawalniczego\n- Kontrola przewodów spawalniczych\n- Sprawdzenie urządzenia chłodzącego', 1);

-- ────────────────────────────────────────────────────────────
-- Harmonogram DUR
-- ────────────────────────────────────────────────────────────
INSERT INTO `maintenance_schedules` (`production_line_id`, `template_id`, `review_type`, `interval_days`, `next_due_date`) VALUES
(1, 2, 'monthly',  30, '2026-06-25'),
(2, 2, 'monthly',  30, '2026-06-01'),
(3, 1, 'weekly',    7, '2026-05-12'),
(5, 3, 'quarterly', 90,'2026-07-15');

-- ────────────────────────────────────────────────────────────
-- Raporty DUR (przykładowe)
-- ────────────────────────────────────────────────────────────
INSERT INTO `maintenance_reviews`
  (`production_line_id`, `subsystem_id`, `template_id`, `performed_by`, `review_type`,
   `review_date`, `duration_minutes`, `activities`, `notes`, `status`, `next_review_date`, `created_at`) VALUES
(1, 2, 2, 2, 'monthly',  '2025-04-25', 200,
 'Smarowanie prowadnic liniowych\nWymiana paska klinowego nr 3 (prewencyjnie)\nKontrola napięcia pasów — OK\nSprawdzenie czujników — 2 sztuki wymienione',
 NULL, 'completed', '2026-06-25', '2025-04-25 14:00:00'),
(5, NULL, 3, 4, 'quarterly', '2025-04-15', 345,
 'Kalibracja geometryczna osi X/Y/Z — odchyłka 0.02mm OK\nWymiana oleju wrzeciona 2L\nCzyszczenie prowadnic kulkowych',
 'Zalecana wymiana łożysk wrzeciona przy następnym przeglądzie', 'completed', '2026-07-15', '2025-04-15 09:00:00'),
(3, NULL, 1, 2, 'weekly',   '2025-04-21', 50,
 'Kontrola wizualna maszyny — bez uwag\nSmarowanie łańcuchów przenośnika',
 NULL, 'completed', '2026-05-12', '2025-04-21 07:00:00'),
(4, 7, 4, 3, 'monthly',  '2025-04-10', 130,
 'Kontrola i wymiana dysz gazowych — 3 szt.\nSprawdzenie podajnika drutu\nCzyszczenie uchwytu spawalniczego',
 NULL, 'partial', '2025-05-10', '2025-04-10 13:00:00');

-- ────────────────────────────────────────────────────────────
-- Ustawienia systemowe
-- ────────────────────────────────────────────────────────────
INSERT INTO `settings` (`skey`, `svalue`, `label`) VALUES
('app_name',         'Moduł Serwis',          'Nazwa aplikacji'),
('app_version',      '0.1-dev',               'Wersja systemu'),
('company_name',     'Twoja Firma Sp. z o.o.','Nazwa firmy'),
('dur_warning_days', '7',                     'Ostrzeżenie DUR (dni przed terminem'),
('records_per_page', '25',                    'Liczba rekordów na stronę');

SET FOREIGN_KEY_CHECKS = 1;

-- Domyślne uprawnienia ról
INSERT INTO `settings` (`skey`, `svalue`, `label`) VALUES
('role_perms_admin',    '{"report":1,"dashboard":1,"failures":1,"dur":1,"statuses":1,"admin":1}', 'Uprawnienia roli: Administrator'),
('role_perms_mechanic', '{"report":0,"dashboard":1,"failures":1,"dur":1,"statuses":1,"admin":0}', 'Uprawnienia roli: Mechanik'),
('role_perms_operator', '{"report":1,"dashboard":0,"failures":0,"dur":1,"statuses":0,"admin":0}', 'Uprawnienia roli: Operator')
ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);
