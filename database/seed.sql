-- ============================================================
-- Moduł Serwis v2 — Dane startowe (seed)
-- Kompatybilny ze schematem v2.2 (zawiera reporter_user_id)
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
-- Użytkownicy
-- Hash poniżej odpowiada hasłu: password
-- Przy instalacji przez install.php hasła są generowane przez PHP
-- i zastępują te wartości — nie musisz ich zmieniać ręcznie.
-- ────────────────────────────────────────────────────────────
INSERT INTO `users` (`id`, `role_id`, `name`, `login`, `email`, `password_hash`, `is_active`) VALUES
(1, 1, 'Administrator',   'admin',    'admin@serwis.local',        '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B3Do9B2', 1),
(2, 2, 'Jan Kowalski',    'mechanik', 'jan.kowalski@serwis.local', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B3Do9B2', 1),
(3, 2, 'Kamil Karbowiak', 'kkarb',    'kamil.karbowiak@serwis.local', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B3Do9B2', 1);

-- ────────────────────────────────────────────────────────────
-- Linie produkcyjne
-- ────────────────────────────────────────────────────────────
INSERT INTO `production_lines` (`id`, `name`, `prefix`, `description`, `is_active`) VALUES
(1, 'Linia 1 - Montaż',    'A1', 'Linia montażu główna',    1),
(2, 'Linia 2 - Lakiernia',  'A2', 'Linia lakierowania',      1),
(3, 'Linia 3 - Pakowanie',  'A3', 'Linia pakowania wyrobów', 1),
(4, 'Linia 4 - Spawalnia',  'W4', 'Linia spawania MIG/MAG',  1),
(5, 'Linia 5 - CNC',        'C5', 'Obrabiarki CNC',          1);

-- ────────────────────────────────────────────────────────────
-- Podzespoły linii
-- ────────────────────────────────────────────────────────────
INSERT INTO `line_subsystems` (`id`, `production_line_id`, `name`, `sort_order`) VALUES
(1, 1, 'Rozwijak',           1),
(2, 1, 'Nawijak',            2),
(3, 1, 'Pakowaczka',         3),
(4, 2, 'Kabina lakiernicza', 1),
(5, 2, 'Suszarnia',          2),
(6, 4, 'Spawarka MIG 1',     1),
(7, 4, 'Spawarka MIG 2',     2);

-- ────────────────────────────────────────────────────────────
-- Liczniki zgłoszeń (startowe — zerowe)
-- ────────────────────────────────────────────────────────────
INSERT INTO `ticket_counters` (`production_line_id`, `year`, `counter`) VALUES
(1, 2026, 0),
(2, 2026, 0),
(3, 2026, 0),
(4, 2026, 0),
(5, 2026, 0);

-- ────────────────────────────────────────────────────────────
-- Kategorie awarii
-- ────────────────────────────────────────────────────────────
INSERT INTO `failure_categories` (`id`, `name`, `label`, `color`, `sort_order`) VALUES
(1, 'electrical',  'Elektryczna', '#dc2626', 1),
(2, 'automation',  'Automatyka',  '#d97706', 2),
(3, 'mechanical',  'Mechaniczna', '#0a2463', 3);

-- ────────────────────────────────────────────────────────────
-- Słownik usterek
-- ────────────────────────────────────────────────────────────
INSERT INTO `failure_dictionary` (`category_id`, `title`) VALUES
-- Elektryczne
(1, 'Brak zasilania maszyny'),
(1, 'Przepalony bezpiecznik'),
(1, 'Uszkodzony falownik'),
(1, 'Zwarcie w sterowniku'),
-- Automatyka
(2, 'Błąd sterownika PLC'),
(2, 'Awaria czujnika indukcyjnego'),
(2, 'Błąd komunikacji sieciowej'),
(2, 'Awaria panelu HMI'),
-- Mechaniczne
(3, 'Pęknięty pas transmisyjny'),
(3, 'Awaria łożyska'),
(3, 'Zacięcie mechanizmu'),
(3, 'Wyciek oleju / smaru'),
(3, 'Pęknięta sprężyna');

-- ────────────────────────────────────────────────────────────
-- Statusy zgłoszeń
-- ────────────────────────────────────────────────────────────
INSERT INTO `failure_statuses` (`id`, `label`, `color`, `sort_order`, `is_initial`, `is_final`, `is_active`) VALUES
(1, 'Nowa awaria',        '#dc2626', 1, 1, 0, 1),
(2, 'Przyjęta',           '#d97706', 2, 0, 0, 1),
(3, 'W trakcie naprawy',  '#0a2463', 3, 0, 0, 1),
(4, 'Oczekuje na części', '#7c3aed', 4, 0, 0, 1),
(5, 'Naprawiona',         '#16a34a', 5, 0, 0, 1),
(6, 'Zamknięta',          '#374151', 6, 0, 1, 1);

-- ────────────────────────────────────────────────────────────
-- Objawy awarii — wybierane przez zgłaszającego
-- ────────────────────────────────────────────────────────────
INSERT INTO `failure_symptoms` (`name`, `sort_order`, `is_active`) VALUES
('Maszyna nie reaguje',         1, 1),
('Spadki napięcia',             2, 1),
('Brak komunikacji',            3, 1),
('Zatrzymanie linii',           4, 1),
('Nietypowy dźwięk / wibracje', 5, 1),
('Wyciek płynu',                6, 1),
('Błąd na panelu operatorskim', 7, 1),
('Przegrzewanie się urządzenia',8, 1),
('Brak ruchu / zacięcie',       9, 1),
('Inne objawy',                10, 1);

-- ────────────────────────────────────────────────────────────
-- Szablony DUR
-- ────────────────────────────────────────────────────────────
INSERT INTO `maintenance_templates` (`id`, `name`, `review_type`, `checklist`, `is_active`, `created_by`) VALUES
(1, 'Przegląd tygodniowy standardowy', 'weekly',
'- Kontrola wizualna maszyny i otoczenia
- Sprawdzenie poziomu olejów i smarów
- Smarowanie łańcuchów przenośnika
- Kontrola stanu pasów i łańcuchów
- Sprawdzenie czujników bezpieczeństwa',
1, 1),

(2, 'Przegląd miesięczny standardowy', 'monthly',
'- Kontrola wizualna maszyny — stan mechaniczny
- Smarowanie prowadnic liniowych
- Sprawdzenie napięcia pasów — regulacja jeśli konieczna
- Kontrola czujników indukcyjnych i optycznych
- Sprawdzenie złącz elektrycznych
- Pomiar rezystancji izolacji silnika
- Czyszczenie filtrów układu chłodzenia
- Kontrola parametrów sterownika PLC',
1, 1),

(3, 'Przegląd kwartalny CNC', 'quarterly',
'- Kalibracja geometryczna osi X/Y/Z
- Wymiana oleju wrzeciona
- Czyszczenie prowadnic kulkowych
- Kontrola napędu posuwowego
- Sprawdzenie narzędzi i uchwytu',
1, 1),

(4, 'Przegląd spawalniczy', 'monthly',
'- Kontrola i wymiana dysz gazowych
- Sprawdzenie podajnika drutu
- Czyszczenie uchwytu spawalniczego
- Kontrola przewodów spawalniczych
- Sprawdzenie urządzenia chłodzącego',
1, 1);

-- ────────────────────────────────────────────────────────────
-- Harmonogram DUR
-- ────────────────────────────────────────────────────────────
INSERT INTO `maintenance_schedules` (`production_line_id`, `template_id`, `review_type`, `interval_days`, `next_due_date`, `is_active`) VALUES
(1, 2, 'monthly',   30, '2026-06-25', 1),
(2, 2, 'monthly',   30, '2026-06-01', 1),
(3, 1, 'weekly',     7, '2026-05-19', 1),
(5, 3, 'quarterly', 90, '2026-07-15', 1);

-- ────────────────────────────────────────────────────────────
-- Przykładowe raporty DUR (dane demonstracyjne)
-- ────────────────────────────────────────────────────────────
INSERT INTO `maintenance_reviews`
    (`production_line_id`, `subsystem_id`, `template_id`, `performed_by`, `review_type`,
     `review_date`, `duration_minutes`, `activities`, `notes`, `status`, `next_review_date`, `created_at`)
VALUES
(1, 2, 2, 2, 'monthly', '2025-04-25', 200,
'Smarowanie prowadnic liniowych
Wymiana paska klinowego nr 3 (prewencyjnie)
Kontrola napięcia pasów — OK
Sprawdzenie czujników — 2 sztuki wymienione',
NULL, 'completed', '2026-06-25', '2025-04-25 14:00:00'),

(5, NULL, 3, 2, 'quarterly', '2025-04-15', 345,
'Kalibracja geometryczna osi X/Y/Z — odchyłka 0.02mm OK
Wymiana oleju wrzeciona 2L
Czyszczenie prowadnic kulkowych',
'Zalecana wymiana łożysk wrzeciona przy następnym przeglądzie',
'completed', '2026-07-15', '2025-04-15 09:00:00'),

(3, NULL, 1, 2, 'weekly', '2025-04-21', 50,
'Kontrola wizualna maszyny — bez uwag
Smarowanie łańcuchów przenośnika',
NULL, 'completed', '2026-05-19', '2025-04-21 07:00:00'),

(4, 7, 4, 3, 'monthly', '2025-04-10', 130,
'Kontrola i wymiana dysz gazowych — 3 szt.
Sprawdzenie podajnika drutu
Czyszczenie uchwytu spawalniczego',
NULL, 'partial', '2025-05-10', '2025-04-10 13:00:00');

-- ────────────────────────────────────────────────────────────
-- Ustawienia systemowe
-- ────────────────────────────────────────────────────────────
INSERT INTO `settings` (`skey`, `svalue`, `label`) VALUES
('app_name',         'Moduł Serwis',     'Nazwa aplikacji'),
('app_version',      '0.1-dev',          'Wersja systemu'),
('company_name',     'FINCO Stal Serwis','Nazwa firmy'),
('dur_warning_days', '7',                'Ostrzeżenie DUR (dni przed terminem)'),
('records_per_page', '25',              'Liczba rekordów na stronę');

-- ────────────────────────────────────────────────────────────
-- Uprawnienia ról
-- ────────────────────────────────────────────────────────────
INSERT INTO `settings` (`skey`, `svalue`, `label`) VALUES
('role_perms_admin',    '{"report":1,"dashboard":1,"failures":1,"dur":1,"statuses":1,"admin":1}', 'Uprawnienia roli: Administrator'),
('role_perms_mechanic', '{"report":0,"dashboard":1,"failures":1,"dur":1,"statuses":1,"admin":0}', 'Uprawnienia roli: Mechanik'),
('role_perms_operator', '{"report":1,"dashboard":0,"failures":0,"dur":1,"statuses":0,"admin":0}', 'Uprawnienia roli: Operator')
ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);

SET FOREIGN_KEY_CHECKS = 1;
