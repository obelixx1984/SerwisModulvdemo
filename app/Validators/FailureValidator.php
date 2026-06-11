<?php

declare(strict_types=1);

namespace App\Validators;

use App\DTOs\CreateFailureDTO;

/**
 * FailureValidator — walidacja danych zgłoszenia awarii.
 *
 * Zwraca tablicę stringów z komunikatami błędów.
 * Pusta tablica = dane poprawne.
 *
 * Zasada: walidator NIE rzuca wyjątków — zwraca błędy.
 * Wyjątki zgłasza serwis dla błędów logiki biznesowej (statusy, obsada itp.)
 */
class FailureValidator
{
    /**
     * Waliduje dane nowego zgłoszenia awarii.
     *
     * @param  CreateFailureDTO       $dto           Dane z formularza
     * @param  bool                   $lineHasSubs   Czy wybrana linia ma podzespoły
     * @return string[]                              Tablica komunikatów błędów (pusta = OK)
     */
    public function validateCreate(CreateFailureDTO $dto, bool $lineHasSubs = false): array
    {
        $errors = [];

        // ── Linia produkcyjna ────────────────────────────────────────────────
        if ($dto->productionLineId <= 0) {
            $errors[] = 'Wybierz linię produkcyjną.';
        }

        // ── Podzespół — wymagany gdy linia ma podzespoły ────────────────────
        // Błąd merytoryczny który zgłosiłeś: brakowało tej walidacji po stronie PHP.
        // Frontend ukrywa select gdy brak podzespołów, ale serwer MUSI weryfikować.
        if ($lineHasSubs && $dto->subsystemId === null) {
            $errors[] = 'Wybierz podzespół — linia posiada zdefiniowane podzespoły.';
        }

        // ── Objaw awarii ─────────────────────────────────────────────────────
        if (!$dto->otherSymptom && $dto->symptomId === null) {
            $errors[] = 'Wybierz objaw awarii lub zaznacz "Inne objawy".';
        }

        // ── Opis przy "Inne objawy" jest obowiązkowy ─────────────────────────
        if ($dto->otherSymptom && $dto->description === '') {
            $errors[] = 'Wpisz opis — pole "Dodatkowy opis" jest wymagane przy "Inne objawy".';
        }

        // ── Zgłaszający ──────────────────────────────────────────────────────
        if ($dto->reporterUserId <= 0) {
            $errors[] = 'Brak danych zalogowanego użytkownika. Zaloguj się ponownie.';
        }

        return $errors;
    }

    /**
     * Waliduje filtry listy zgłoszeń.
     * Filtry są opcjonalne — zwraca błąd tylko przy nieprawidłowych wartościach.
     *
     * @param  array<string, mixed> $raw  Surowe wartości z $_GET
     * @return string[]
     */
    public function validateFilters(array $raw): array
    {
        $errors = [];

        // Strona musi być liczbą całkowitą > 0
        if (isset($raw['page']) && (!is_numeric($raw['page']) || (int)$raw['page'] < 1)) {
            $errors[] = 'Nieprawidłowy numer strony.';
        }

        // category_id: liczba lub 'none'
        if (isset($raw['category_id']) && $raw['category_id'] !== '' && $raw['category_id'] !== 'none') {
            if (!is_numeric($raw['category_id']) || (int)$raw['category_id'] < 1) {
                $errors[] = 'Nieprawidłowa wartość filtra kategorii.';
            }
        }

        // Długość wyszukiwania — ochrona przed bardzo długimi ciągami
        if (isset($raw['search']) && mb_strlen($raw['search']) > 200) {
            $errors[] = 'Fraza wyszukiwania jest za długa (max. 200 znaków).';
        }

        return $errors;
    }
}

/*
 * ============================================================
 * DOKUMENTACJA PLIKU: FailureValidator.php
 * ============================================================
 * Plik:        app/Validators/FailureValidator.php
 * Opis:        Walidacja danych zgłoszenia awarii i filtrów listy
 * Zależności:  CreateFailureDTO
 * Uwagi:       Zwraca string[] — pusta tablica = dane poprawne.
 *              Wstrzyknij przez DI Container lub twórz jako new FailureValidator().
 * ============================================================
 */
