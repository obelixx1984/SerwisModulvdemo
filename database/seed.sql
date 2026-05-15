-- ============================================================
-- Moduł Serwis v2 — Dane startowe (seed)
-- ZMIANA: INSERT IGNORE dla failure_symptoms i failure_dictionary
--         zapobiega duplikatom przy wielokrotnym uruchomieniu.
--         Pozostałe tabele używają INSERT z ON DUPLICATE KEY UPDATE
--         lub INSERT IGNORE tam gdzie to stosowne.
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
-- Użytkownicy
-- Hash poniżej odpowiada hasłu: password
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `users` (`id`, `role_id`, `name`, `login`, `email`, `password_hash`, `is_active`) VALUES
(1, 1, 'Administrator',   'admin',    'admin@serwis.local',           '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B3Do9B2', 1),
(2, 2, 'Jan Kowalski',    'mechanik', 'jan.kowalski@serwis.local',    '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B3Do9B2', 1),
(3, 2, 'Kamil Karbowiak', 'kkarb',    'kamil.karbowiak@serwis.local', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B3Do9B2', 1);

-- ────────────────────────────────────────────────────────────
-- Linie produkcyjne
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `production_lines` (`id`, `name`, `prefix`, `description`, `is_active`) VALUES
(1, 'Linia 1 - Montaż',   'A1', 'Linia montażu główna',    1),
(2, 'Linia 2 - Lakiernia', 'A2', 'Linia lakierowania',      1),
(3, 'Linia 3 - Pakowanie', 'A3', 'Linia pakowania wyrobów', 1),
(4, 'Linia 4 - Spawalnia', 'W4', 'Linia spawania MIG/MAG',  1),
(5, 'Linia 5 - CNC',       'C5', 'Obrabiarki CNC',          1);

-- ────────────────────────────────────────────────────────────
-- Podzespoły linii
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `line_subsystems` (`id`, `production_line_id`, `name`, `sort_order`) VALUES
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
INSERT IGNORE INTO `ticket_counters` (`production_line_id`, `year`, `counter`) VALUES
(1, 2026, 0),
(2, 2026, 0),
(3, 2026, 0),
(4, 2026, 0),
(5, 2026, 0);

-- ────────────────────────────────────────────────────────────
-- Kategorie awarii
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `failure_categories` (`id`, `name`, `label`, `color`, `sort_order`) VALUES
(1, 'electrical', 'Elektryczna', '#dc2626', 1),
(2, 'automation', 'Automatyka',  '#d97706', 2),
(3, 'mechanical', 'Mechaniczna', '#0a2463', 3);

-- ────────────────────────────────────────────────────────────
-- Słownik usterek
-- ZMIANA: INSERT IGNORE — zapobiega duplikatom przy ponownym uruchomieniu
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `failure_dictionary` (`id`, `category_id`, `title`) VALUES
-- Elektryczne
( 1, 1, 'Brak zasilania maszyny'),
( 2, 1, 'Przepalony bezpiecznik'),
( 3, 1, 'Uszkodzony falownik'),
( 4, 1, 'Zwarcie w sterowniku'),
-- Automatyka
( 5, 2, 'Błąd sterownika PLC'),
( 6, 2, 'Awaria czujnika indukcyjnego'),
( 7, 2, 'Błąd komunikacji sieciowej'),
( 8, 2, 'Awaria panelu HMI'),
-- Mechaniczne
( 9, 3, 'Pęknięty pas transmisyjny'),
(10, 3, 'Awaria łożyska'),
(11, 3, 'Zacięcie mechanizmu'),
(12, 3, 'Wyciek oleju / smaru'),
(13, 3, 'Pęknięta sprężyna');

-- ────────────────────────────────────────────────────────────
-- Statusy zgłoszeń
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `failure_statuses` (`id`, `label`, `color`, `sort_order`, `is_initial`, `is_final`, `is_active`) VALUES
(1, 'Nowa awaria',        '#dc2626', 1, 1, 0, 1),
(2, 'Przyjęta',           '#d97706', 2, 0, 0, 1),
(3, 'W trakcie naprawy',  '#0a2463', 3, 0, 0, 1),
(4, 'Oczekuje na części', '#7c3aed', 4, 0, 0, 1),
(5, 'Naprawiona',         '#16a34a', 5, 0, 0, 1),
(6, 'Zamknięta',          '#374151', 6, 0, 1, 1);

-- ────────────────────────────────────────────────────────────
-- Objawy awarii — wybierane przez zgłaszającego
-- ZMIANA: INSERT IGNORE — zapobiega duplikatom przy ponownym uruchomieniu
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `failure_symptoms` (`id`, `name`, `sort_order`, `is_active`) VALUES
( 1, 'Maszyna nie reaguje',          1, 1),
( 2, 'Spadki napięcia',              2, 1),
( 3, 'Brak komunikacji',             3, 1),
( 4, 'Zatrzymanie linii',            4, 1),
( 5, 'Nietypowy dźwięk / wibracje',  5, 1),
( 6, 'Wyciek płynu',                 6, 1),
( 7, 'Błąd na panelu operatorskim',  7, 1),
( 8, 'Przegrzewanie się urządzenia', 8, 1),
( 9, 'Brak ruchu / zacięcie',        9, 1),
(10, 'Inne objawy',                 10, 1);

-- ────────────────────────────────────────────────────────────
-- Szablony DUR
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `maintenance_templates` (`id`, `name`, `review_type`, `checklist`, `is_active`, `created_by`) VALUES
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
- Czyszczenie uchwytu spawalniczego',
1, 1);

-- ────────────────────────────────────────────────────────────
-- Ustawienia systemowe
-- ────────────────────────────────────────────────────────────
INSERT INTO `settings` (`skey`, `svalue`, `label`) VALUES
('app_name',         'Moduł Serwis',      'Nazwa aplikacji'),
('app_version',      '0.1-dev',           'Wersja systemu'),
('company_name',     'FINCO Stal Serwis', 'Nazwa firmy'),
('dur_warning_days', '7',                 'Ostrzeżenie DUR (dni przed terminem)'),
('records_per_page', '25',                'Liczba rekordów na stronę')
ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);

-- ────────────────────────────────────────────────────────────
-- Uprawnienia ról
-- ────────────────────────────────────────────────────────────
INSERT INTO `settings` (`skey`, `svalue`, `label`) VALUES
('role_perms_admin',    '{"report":1,"dashboard":1,"failures":1,"dur":1,"statuses":1,"admin":1}', 'Uprawnienia roli: Administrator'),
('role_perms_mechanic', '{"report":0,"dashboard":1,"failures":1,"dur":1,"statuses":1,"admin":0}', 'Uprawnienia roli: Mechanik'),
('role_perms_operator', '{"report":1,"dashboard":0,"failures":0,"dur":1,"statuses":0,"admin":0}', 'Uprawnienia roli: Operator')
ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);

SET FOREIGN_KEY_CHECKS = 1;
