-- ============================================================
-- Migracja: dodanie kolumny other_symptom do tabeli failures
-- Uruchom JEDNORAZOWO na istniejącej bazie danych
-- ============================================================

ALTER TABLE `failures`
  ADD COLUMN `other_symptom` TINYINT(1) NOT NULL DEFAULT 0
  COMMENT '1 = zgłaszający wybrał "Inne objawy" — opis w polu description'
  AFTER `symptom_id`;

-- Sprawdzenie (opcjonalne):
-- SELECT id, ticket_number, symptom_id, other_symptom, description FROM failures LIMIT 5;
