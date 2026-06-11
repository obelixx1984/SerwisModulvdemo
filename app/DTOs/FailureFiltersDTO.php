<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * FailureFiltersDTO — parametry filtrowania listy zgłoszeń.
 *
 * Zastępuje ręczne parsowanie $_GET w FailureController::buildListFilters().
 * Metoda toArray() zwraca format zgodny z PdoFailureRepository::buildWhere().
 */
final class FailureFiltersDTO
{
    public function __construct(
        public readonly ?int    $statusId,
        public readonly ?int    $lineId,
        // Specjalna wartość 'none' oznacza "bez kategorii" — zachowujemy jako string|null
        public readonly string|null $categoryId,
        public readonly ?string $search,
        public readonly int     $page,
        public readonly ?int    $reporterUserId,
    ) {}

    /**
     * Fabryka — tworzy DTO z tablicy $_GET.
     *
     * @param array<string, mixed> $get  Zwykle $_GET
     */
    public static function fromGet(array $get): self
    {
        $catRaw = trim($get['category_id'] ?? '');

        // Obsługa wartości specjalnej 'none' (zgłoszenia bez kategorii)
        if ($catRaw === 'none') {
            $categoryId = 'none';
        } elseif ((int)$catRaw > 0) {
            $categoryId = (string)(int)$catRaw;
        } else {
            $categoryId = null;
        }

        $search = trim($get['search'] ?? '');

        return new self(
            statusId:       (int)($get['status_id'] ?? 0) > 0 ? (int)$get['status_id'] : null,
            lineId:         (int)($get['line_id']   ?? 0) > 0 ? (int)$get['line_id']   : null,
            categoryId:     $categoryId,
            search:         $search !== '' ? $search : null,
            page:           max(1, (int)($get['page'] ?? 1)),
            reporterUserId: (int)($get['reporter_user_id'] ?? 0) > 0 ? (int)$get['reporter_user_id'] : null,
        );
    }

    /**
     * Zwraca tablicę filtrów zgodną z PdoFailureRepository::buildWhere().
     * Null-values są pomijane przez array_filter w kontrolerze.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'status_id'        => $this->statusId,
            'line_id'          => $this->lineId,
            'category_id'      => $this->categoryId,
            'search'           => $this->search,
            'reporter_user_id' => $this->reporterUserId,
        ], fn($v) => $v !== null);
    }

    /**
     * Sprawdza czy jakikolwiek filtr jest aktywny.
     * Przydatne do wyświetlania komunikatu "Aktywne filtry" w szablonie.
     */
    public function hasAnyFilter(): bool
    {
        return $this->statusId !== null
            || $this->lineId !== null
            || $this->categoryId !== null
            || $this->search !== null
            || $this->reporterUserId !== null;
    }
}

/*
 * ============================================================
 * DOKUMENTACJA PLIKU: FailureFiltersDTO.php
 * ============================================================
 * Plik:        app/DTOs/FailureFiltersDTO.php
 * Opis:        DTO parametrów filtrowania listy zgłoszeń (GET)
 * Zależności:  brak
 * Uwagi:       categoryId może być '1', '2', 'none' lub null.
 *              toArray() kompatybilne z PdoFailureRepository::buildWhere().
 * ============================================================
 */
