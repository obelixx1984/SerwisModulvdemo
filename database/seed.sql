-- ============================================================
-- Moduł Serwis — Dane startowe (seed)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ────────────────────────────────────────────────────────────
-- Role systemowe
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `roles` (`id`, `name`, `label`) VALUES
(1, 'admin',    'Administrator'),
(2, 'mechanic', 'Mechanik'),
(3, 'operator', 'Operator (tylko odczyt)'),
(4, 'dyrektor', 'Dyrektor'),
(5, 'planista', 'Planista');

-- ────────────────────────────────────────────────────────────
-- Użytkownicy
-- Hasło dla wszystkich: password
-- Hash BCrypt cost=12
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `users`
    (`id`, `role_id`, `name`, `login`, `email`, `password_hash`, `is_active`) VALUES
(1, 1, 'Administrator', 'admin', 'admin@serwis.local', '$2y$12$X9NozN1mS1hStF40MosqNeTM6JPiJu8oZfwKkL1WhG4T5hoIG1B..', 1),
(2, 3, 'Marcin Biedrzycki', 'mbied', 'mbied@serwis.local', '$2y$12$YBTd/xh9R3jt0h2RVGcyIu.3LZIlCLUsf3FrXlIGh88kJ0v7mAUrC', 1),
(3, 3, 'Piotr Bobrowski', 'pbobr', 'pbobr@serwis.local', '$2y$12$BTV.s81HnpuNOexgKyXwWOUpvnA3zuhcOYM.ALRnJLPUKUhWm/GWq', 1),
(4, 3, 'Michał Boniecki', 'mboni', 'mboni@serwis.local', '$2y$12$MMD4OhlEphx9Tq/gcgWUt.chPlgVDN6R7QmmZJBQglUdXNKgEC6xe', 1),
(5, 3, 'Marcin Brzózka', 'mbrzo', 'mbrzo@serwis.local', '$2y$12$5QHM6cSLrGToI64AlCyfI..oarYznlYqd0oDuv/Ijq6wChSO2D0Oa', 1),
(6, 3, 'Bartosz Grzybowski', 'bgrzy', 'bgrzy@serwis.local', '$2y$12$Fo5JRW64CRrKIUU5AURy6uGKU1zxuoXt7.acNsxp7av5wo8GoSumy', 1),
(7, 3, 'Artur Jakubiak', 'ajaku', 'ajaku@serwis.local', '$2y$12$MDm.LZgbL/H3hAO/OlbI3uu.1YhF8zm89fgsjOIRpJBWU5ZNAppb.', 1),
(8, 3, 'Tomasz Kaczorowski', 'tkacz', 'tkacz@serwis.local', '$2y$12$Icy3qkOoJcWyeC3DV4YWle7omfDue83GeBPVGbx/hdbEY.xVRlOA.', 1),
(9, 3, 'Paweł Kamiński', 'pkami', 'pkami@serwis.local', '$2y$12$N/mS1Ej1jQU4AFPQxX2pRe/f32Yeb.6h9w/G589mINS85oARFmZZ6', 1),
(10, 3, 'Radosław Karalus', 'rkara', 'rkara@serwis.local', '$2y$12$BMN7HZgJyCSgupr0EtSY0.cdnDxxRRi1yXzPsDA7hN06.0aLHqdna', 1),
(11, 3, 'Artur Kierlańczyk', 'akier', 'akier@serwis.local', '$2y$12$Dd4VW.2OZewSQVzrEeBhPOeRiiA1hW0FwmMZRPVoweMiWMa/JHW26', 1),
(12, 3, 'Łukasz Kierlańczyk', 'lkier', 'lkier@serwis.local', '$2y$12$fQMiast2hiv5NcigA3meberuUo8Ilx356u/pBpK65PSdjVsWwCET6', 1),
(13, 3, 'Piotr Kotyński', 'pkoty', 'pkoty@serwis.local', '$2y$12$Ba3kkcYYmnkBj59xW9cwYOtOx7wQ0vrkh1jagqn6YTeV7IbKoHX..', 1),
(14, 3, 'Piotr Krysiński', 'pkrys', 'pkrys@serwis.local', '$2y$12$/6KJeT5OeoYgsUX3X0TJUuGVvIPLPm5rsMbthSWU1Jr1yEfDCFqly', 1),
(15, 3, 'Tomasz Langa', 'tlang', 'tlang@serwis.local', '$2y$12$hegMXsTIQPGitLtVyDR3Aemsn4vPqfNL3CYKF96bAbMscu.HtCJzO', 1),
(16, 3, 'Piotr Leśniak', 'plesn', 'plesn@serwis.local', '$2y$12$.o4r8CSJcDm3L7n7Ouv9guCEWabq9Wot2TO0qiySNCqNrlyv0HJ5G', 1),
(17, 3, 'Piotr Mońka', 'pmonk', 'pmonk@serwis.local', '$2y$12$OAVMGbhsg3syvUoyz0k7g.aJ5wi50HBpGCLJiBtjYRHl.WW74WiDm', 1),
(18, 3, 'Mirosław Myszka', 'mmysz', 'mmysz@serwis.local', '$2y$12$5czuJxORzKXSnj2UKICiluK7nuukhPWYN3TD6tqx51J8VSrjlO2/a', 1),
(19, 3, 'Kamil Olczak', 'kolcz', 'kolcz@serwis.local', '$2y$12$925AIiw1XFLdkEGzw..LqOCYdzfWt1FEgYiAxVQ8MlF6h85t4CywO', 1),
(20, 3, 'Mateusz Orłowski', 'morlo', 'morlo@serwis.local', '$2y$12$o7cAqzHhEOU7R.b3Mg6AM.o7BtSfRlMwadRhD7/syf6cGJpmzMNsi', 1),
(21, 3, 'Paweł Papuga', 'ppapu', 'ppapu@serwis.local', '$2y$12$aqg8z3YfYvVaEpdH5X99teUtHs4l/sKXxAIpOGGV3aCTF3ZMi8fIC', 1),
(22, 3, 'Paweł Petryka', 'ppetr', 'ppetr@serwis.local', '$2y$12$NyT.VPjoTGY.Xw7cfDN/lemiWXWdPn3AnEwyombpBsSIsyT5XaAGy', 1),
(23, 3, 'Piotr Popiński', 'ppopi', 'ppopi@serwis.local', '$2y$12$P.9mCuIzBrQ89.5roq9K.O6DPn9sgJpwSmHoPL6NKXzClIg0lYOZW', 1),
(24, 3, 'Artur Puchniak', 'apuch', 'apuch@serwis.local', '$2y$12$IIjF5v88Ctw6uJx2.mRTx.Quf.X.Pd3wej0C9ci7jhfGxR0QpUafS', 1),
(25, 3, 'Piotr Radomski', 'prado', 'prado@serwis.local', '$2y$12$BVhXsHG89ZAYlrz4nh2fGOECYl.WWuQHCnvvHeW8WXcsEkdL1OJuW', 1),
(26, 3, 'Łukasz Rychlewski', 'lrych', 'lrych@serwis.local', '$2y$12$FgldenhaxjzoSfkx0y.SZer10GnMGLWKwqY5D7DODrAK4aSP8a.MW', 1),
(27, 3, 'Jacek Siekierski', 'jsiek', 'jsiek@serwis.local', '$2y$12$RiSVsP2usGaA.Z01m5aDxe5oG8v2dZ7HV05ETtKRC/jzgs0R7QtKm', 1),
(28, 3, 'Grzegorz Szleszyński', 'gszle', 'gszle@serwis.local', '$2y$12$Wm9VqsNkcgmyMs703C9pUednaiWwOhaPwCqz2RWz1Kpsz9F.7Ozs6', 1),
(29, 3, 'Jarosław Szyszka', 'jszys', 'jszys@serwis.local', '$2y$12$bFWHFur943xw0W4JUNHdO.PJRmq0JHbTftfobow0Cdvtcknl9N4rq', 1),
(30, 3, 'Piotr Walczak', 'pwalc', 'pwalc@serwis.local', '$2y$12$nbESTePxmlDGN3JiVsn1O.H9cSNJVG9VMKjlvz2IPM5ULa2vysnU2', 1),
(31, 3, 'Radosław Wiśniewski', 'rwisn', 'rwisn@serwis.local', '$2y$12$rQqAP8fSgR74cE5MyYepnO2fFNcmjkFN1saYDXeTu216JiGl8qkrK', 1),
(32, 3, 'Dariusz Zieliński', 'dziel', 'dziel@serwis.local', '$2y$12$9Js9zRxkf16lx1BJsSi.W.EJHFBl3DIfMSbHn5zDOs/0WLvMEFbvG', 1),
(33, 3, 'Kamil Zwierzchowski', 'kzwie', 'kzwie@serwis.local', '$2y$12$hYuD/0AllsjyZvg82lUUme7qsfjYhjQoUayUS95NqNDx8/r.rADAe', 1),
(34, 2, 'Andrii Kisak', 'akisa', 'akisa@serwis.local', '$2y$12$VR2lGiT7RxD5PkmUNse6v.sk11AKjvzylRLApaS5qRvuuwVHz68/2', 1),
(35, 2, 'Tomasz Matyjas', 'tmaty', 'tmaty@serwis.local', '$2y$12$/oINSaM7WCcU0DHocgaOVucC8yJGTTePqaqWjcDIfeD0Ev7A/4kA2', 1),
(36, 2, 'Roman Lusztak', 'rlusz', 'rlusz@serwis.local', '$2y$12$eUxQVR3Q5kx5rQ8k2fvyLOsI22BDQ8vr6Fp54/ZnArIZK2SHZLXwa', 1),
(37, 4, 'Adam Figielski', 'afigi', 'afigi@serwis.local', '$2y$12$Xq5/.6y0Cr10QabyuRuGG.jm4rxFhnKn4owGGF2L4UOnUWe5FVkaW', 1),
(38, 4, 'Andrzej Trzeciak', 'atrze', 'atrze@serwis.local', '$2y$12$LdyYBSc6sjTMnhtLhiWWFe5KSPQk/gWRLio0i.NJSM4NkbOxypOdi', 1),
(39, 5, 'Kamil Karbowiak', 'kkarb', 'kkarb@serwis.local', '$2y$12$0dcvuJB1duP07tL40I9NFOm6skfYOhhJS6/C9U8m8zsSPtmlZe2Mq', 1),
(40, 5, 'Zbigniew Spaliński', 'zspal', 'zspal@serwis.local', '$2y$12$eENGXtBbFkAc1td.HXgqO.IS8HQHphGVUl8rmEtQFfh/QxOGn.nrO', 1),
(41, 5, 'Paweł Górkowski', 'pgork', 'pgork@serwis.local', '$2y$12$7Ylgalce/HBpVA0pB9QwPeIsz8/V2DvzAiw8NxNr3OjGdcPvNkIA6', 1);

-- ────────────────────────────────────────────────────────────
-- Linie produkcyjne 
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
-- Podzespoły linii
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `line_subsystems`
    (`id`, `production_line_id`, `name`, `sort_order`, `is_active`) VALUES
-- A1 (line 1)
(1, 1, 'Odwijak z wózkiem', 1, 1),
(2, 1, 'Rolki wprowadzające z pozycjonowaniem', 2, 1),
(3, 1, 'Foliowarka', 3, 1),
(4, 1, 'Prostowarka 1', 4, 1),
(5, 1, 'Prostowarka 2', 5, 1),
(6, 1, 'Gilotyna', 6, 1),
(7, 1, 'Stół odbierający', 7, 1),
(8, 1, 'Stół złomowy', 8, 1),
(9, 1, 'Karuzela złomowa', 9, 1),
(10, 1, 'Rolki podające', 10, 1),
(11, 1, 'Układarka arkuszy ze stołem podnoszonym', 11, 1),
(12, 1, 'Magazyn palet z podajnikiem', 12, 1),
(13, 1, 'Stół wagi', 13, 1),
(14, 1, 'Stoły pakowania', 14, 1),
(15, 1, 'Spinarka palet', 15, 1),
(16, 1, 'Układ hydrauliczny', 16, 1),
(17, 1, 'Układ elektryczny', 17, 1),
(18, 1, 'Szafy elektryczne i pulpity operatora', 18, 1),
-- A3 (line 3)
(19, 3, 'Odwijak z wózkiem', 1, 1),
(20, 3, 'Rolki wprowadzające z pozycjonowaniem', 2, 1),
(21, 3, 'Prostowarka', 3, 1),
(22, 3, 'Gilotyna', 4, 1),
(23, 3, 'Stół odbierający', 5, 1),
(24, 3, 'Rolki podające', 6, 1),
(25, 3, 'Układarka arkuszy ze stołem podnoszonym', 7, 1),
(26, 3, 'Stoły złomowe z wózkami', 8, 1),
(27, 3, 'Stół wagi', 9, 1),
(28, 3, 'Stół pakowania', 10, 1),
(29, 3, 'Układ hydrauliczny', 11, 1),
(30, 3, 'Układ elektryczny', 12, 1),
(31, 3, 'Szafy elektryczne i pulpity operatora', 13, 1),
-- A5 (line 5)
(32, 5, 'Odwijak z wózkiem', 1, 1),
(33, 5, 'Rolki wprowadzające z pozycjonowaniem', 2, 1),
(34, 5, 'Prostowarka', 3, 1),
(35, 5, 'Gilotyna', 4, 1),
(36, 5, 'Stół odbierający', 5, 1),
(37, 5, 'Rolki podające', 6, 1),
(38, 5, 'Układarka arkuszy ze stołem podnoszonym', 7, 1),
(39, 5, 'Stoły złomowe z wózkami', 8, 1),
(40, 5, 'Stół wagi i pakowania', 9, 1),
(41, 5, 'Układ hydrauliczny', 10, 1),
(42, 5, 'Układ elektryczny', 11, 1),
(43, 5, 'Szafy elektryczne i pulpity operatora', 12, 1),
-- W2 (line 6)
(44, 6, 'Odwijak z wózkiem', 1, 1),
(45, 6, 'Rolki wprowadzające z pozycjonowaniem', 2, 1),
(46, 6, 'Gilotyna', 3, 1),
(47, 6, 'Nożyca krążkowa', 4, 1),
(48, 6, 'Stoły nad kanałem', 5, 1),
(49, 6, 'Nawijak złomu', 6, 1),
(50, 6, 'Zespół przejezdny hamulca pneumat. z rolką pomiarową', 7, 1),
(51, 6, 'Nawijak z wózkiem', 8, 1),
(52, 6, 'Ramiona obrotowe z ostrogą', 9, 1),
(53, 6, 'Bramka pakowania', 10, 1),
(54, 6, 'Transportery pakowania', 11, 1),
(55, 6, 'Układ hydrauliczny', 12, 1),
(56, 6, 'Układ elektryczny', 13, 1),
(57, 6, 'Szafy elektryczne i pulpity operatora', 14, 1),
-- W1 (line 4)
(58, 4, 'Odwijak z wózkiem', 1, 1),
(59, 4, 'Rolki wprowadzające z pozycjonowaniem', 2, 1),
(60, 4, 'Gilotyna', 3, 1),
(61, 4, 'Nożyca krążkowa', 4, 1),
(62, 4, 'Stoły nad kanałem', 5, 1),
(63, 4, 'Nawijaki złomu', 6, 1),
(64, 4, 'Hamulec pneumatyczny z rolką pomiarową', 7, 1),
(65, 4, 'Nawijak z wózkiem', 8, 1),
(66, 4, 'Ramiona obrotowe z ostrogą', 9, 1),
(67, 4, 'Bramka pakowania', 10, 1),
(68, 4, 'Transportery pakowania', 11, 1),
(69, 4, 'Układ hydrauliczny', 12, 1),
(70, 4, 'Układ elektryczny', 13, 1),
(71, 4, 'Szafy elektryczne i pulpity operatora', 14, 1),
-- A2 (line 2)
(72, 2, 'Odwijak z wózkiem', 1, 1),
(73, 2, 'Rolki wprowadzające z pozycjonowaniem', 2, 1),
(74, 2, 'Foliowarka', 3, 1),
(75, 2, 'Prostowarka', 4, 1),
(76, 2, 'Gilotyna', 5, 1),
(77, 2, 'Stół odbierający', 6, 1),
(78, 2, 'Stół złomowy', 7, 1),
(79, 2, 'Karuzela złomowa', 8, 1),
(80, 2, 'Rolki podające', 9, 1),
(81, 2, 'Układarka arkuszy ze stołem podnoszonym', 10, 1),
(82, 2, 'Magazyn palet', 11, 1),
(83, 2, 'Stół wagi', 12, 1),
(84, 2, 'Stoły pakowania', 13, 1),
(85, 2, 'Spinarka palet', 14, 1),
(86, 2, 'Układ hydrauliczny', 15, 1),
(87, 2, 'Układ elektryczny', 16, 1),
(88, 2, 'Szafy elektryczne i pulpity operatora', 17, 1);

-- ────────────────────────────────────────────────────────────
-- Liczniki zgłoszeń
-- ────────────────────────────────────────────────────────────
INSERT INTO `ticket_counters` (`production_line_id`, `year`, `counter`) VALUES
(1, 2026, 0),
(2, 2026, 0),
(3, 2026, 0),
(4, 2026, 0),
(5, 2026, 0),
(6, 2026, 0)
ON DUPLICATE KEY UPDATE `counter` = VALUES(`counter`);

-- ────────────────────────────────────────────────────────────
-- Kategorie awarii
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `failure_categories`
    (`id`, `name`, `label`, `color`, `sort_order`, `is_active`) VALUES
(1, 'electrical',    'Elektryczna',  '#dc2626', 1, 1),
(2, 'automation',    'Automatyka',   '#d97706', 2, 1),
(3, 'mechanical',    'Mechaniczna',  '#0a2463', 3, 1),
(4, 'pneumatic',     'Pneumatyczna', '#0891b2', 4, 1),
(5, 'hydraulic',     'Hydrauliczna', '#33cc5e', 5, 1);

-- ────────────────────────────────────────────────────────────
-- Słownik usterek 
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
(14, 4, 'Pęknięty wąż', 1),
-- Hydrauliczna
(15, 5, 'Wyciek oleju hydraulicznego',       1),
(16, 5, 'Spadek ciśnienia w układzie',       1),
(17, 5, 'Awaria pompy hydraulicznej',        1),
(18, 5, 'Uszkodzony siłownik hydrauliczny',  1),
(19, 5, 'Zapowietrzenie układu hydraulicznego', 1),
(20, 5, 'Przegrzewanie się oleju hydraulicznego', 1);

-- ────────────────────────────────────────────────────────────
-- Statusy awarii
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `failure_statuses`
    (`id`, `label`, `color`, `sort_order`, `is_initial`, `is_final`, `is_observed`, `is_active`) VALUES
(1, 'Nowa awaria',           '#dc2626', 1, 1, 0, 0, 1),
(2, 'W trakcie naprawy',     '#0e7c07', 2, 0, 0, 0, 1),
(3, 'Oczekuje na części',    '#7c3aed', 3, 0, 0, 0, 1),
(4, 'W trakcie obserwacji',  '#b45309', 4, 0, 0, 1, 1),
(5, 'Naprawiona',            '#374151', 5, 0, 1, 0, 1);

-- ────────────────────────────────────────────────────────────
-- Objawy awarii
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
-- Szablony DUR
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
-- Harmonogramy DUR
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `maintenance_schedules`
    (`id`, `production_line_id`, `template_id`, `review_type`, `interval_days`, `next_due_date`, `is_active`) VALUES
(1, 1, NULL, 'monthly',  30, '2026-07-01', 1),
(2, 2, NULL, 'monthly',  30, '2026-07-01', 1),
(3, 3, NULL, 'monthly',  30, '2026-07-30', 1),
(4, 2, NULL, 'periodic', 30, '2026-07-30', 1),
(5, 4, NULL, 'periodic', 30, '2026-07-20', 1);

-- ────────────────────────────────────────────────────────────
-- Ustawienia systemowe 
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
(94,  'session_idle_timeout',     '20',  'Czas bezczynności przed wylogowaniem (minuty, 0 = wyłączone)'),
(123, 'observation_window_hours', '48', 'Czas okna obserwacji awarii (godziny)')
ON DUPLICATE KEY UPDATE `svalue` = VALUES(`svalue`);

-- ────────────────────────────────────────────────────────────
-- Kategorie części zamiennych 
-- ────────────────────────────────────────────────────────────
INSERT IGNORE INTO `spare_part_categories`
    (`id`, `name`, `color`, `sort_order`, `is_active`) VALUES
(1, 'Elektryka',  '#0891b2', 1, 1),
(2, 'Mechanika',  '#7c3aed', 2, 1),
(3, 'Hydraulika', '#d97706', 3, 1),
(4, 'Pneumatyka', '#dc2626', 4, 1),
(5, 'Inne',       '#6c757d', 5, 1);

SET FOREIGN_KEY_CHECKS = 1;

/*
 * ============================================================
 * DOKUMENTACJA PLIKU: seed.sql
 * ============================================================
 * Plik:         seed.sql
 * Opis:         Dane startowe aplikacji Moduł Serwis 0.1-dev
 * Wersja:       0.1-dev 
 * Zależności:   schema.sql (musi być wykonany wcześniej)
 * Zawiera:
 *   - Role, Użytkownicy
 *   - 6 linii produkcyjnych + podzespoły
 *   - Kategorie awarii, Słownik usterek, Statusy
 *   - Objawy awarii, Szablony DUR, Harmonogramy
 *   - Ustawienia systemowe
 *   - Kategorie części zamiennych — bez listy części
 * ============================================================
 */
