-- ============================================================
-- Moduł Serwis v2 — Plik instalacyjny (schema + dane startowe)
-- Wgraj do phpMyAdmin lub wykonaj: mysql -u root -p modul_serwis < install.sql
-- ============================================================

-- Opcjonalnie: utwórz i wybierz bazę danych
-- CREATE DATABASE IF NOT EXISTS `modul_serwis` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `modul_serwis`;

SOURCE schema.sql;
SOURCE seed.sql;
