<?php

declare(strict_types=1);

namespace App\Entities;

/**
 * Encja Failure — reprezentuje pojedyncze zgłoszenie awarii.
 *
 * Nie jest modelem ORM — to czysta klasa danych (Value Object / Entity).
 * Tworzona przez PdoFailureRepository::fromRow() na podstawie wiersza z bazy.
 * W szablonach nadal można używać tablic — fromRow() zwraca instancję
 * tylko tam, gdzie potrzebna jest logika obiektu.
 */
class Failure
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $ticketNumber,
        public readonly int     $productionLineId,
        public readonly string  $lineName,
        public readonly string  $linePrefix,
        public readonly ?string $subsystemName,
        public readonly ?int    $categoryId,
        public readonly ?string $catLabel,
        public readonly ?string $catColor,
        public readonly int     $statusId,
        public readonly string  $statusLabel,
        public readonly string  $statusColor,
        public readonly bool    $statusIsFinal,
        public readonly bool    $statusIsObserved,
        public readonly ?int    $symptomId,
        public readonly ?string $symptomName,
        public readonly ?int    $dictionaryItemId,
        public readonly ?string $dictTitle,
        public readonly ?int    $reporterUserId,
        public readonly ?string $reporterName,
        public readonly ?string $reporterAcronym,
        public readonly ?string $description,
        public readonly ?string $mechanicNote,
        public readonly bool    $otherFailure,
        public readonly bool    $otherSymptom,
        public readonly ?string $closedAt,
        public readonly ?string $observationStartedAt,
        public readonly string  $createdAt,
        public readonly string  $updatedAt,
    ) {}

    /**
     * Tworzy instancję Failure z wiersza bazy danych (asoc. tablica).
     * Metoda fabryczna używana przez Repository.
     *
     * @param array<string, mixed> $row Wiersz z PDO::FETCH_ASSOC
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:                   (int)$row['id'],
            ticketNumber:         (string)$row['ticket_number'],
            productionLineId:     (int)$row['production_line_id'],
            lineName:             (string)($row['line_name'] ?? ''),
            linePrefix:           (string)($row['line_prefix'] ?? ''),
            subsystemName:        $row['subsystem_name'] ?? null,
            categoryId:           isset($row['category_id']) ? (int)$row['category_id'] : null,
            catLabel:             $row['cat_label'] ?? null,
            catColor:             $row['cat_color'] ?? null,
            statusId:             (int)$row['status_id'],
            statusLabel:          (string)($row['status_label'] ?? ''),
            statusColor:          (string)($row['status_color'] ?? ''),
            statusIsFinal:        !empty($row['status_is_final']),
            statusIsObserved:     !empty($row['status_is_observed']),
            symptomId:            isset($row['symptom_id']) ? (int)$row['symptom_id'] : null,
            symptomName:          $row['symptom_name'] ?? null,
            dictionaryItemId:     isset($row['dictionary_item_id']) ? (int)$row['dictionary_item_id'] : null,
            dictTitle:            $row['dict_title'] ?? null,
            reporterUserId:       isset($row['reporter_user_id']) ? (int)$row['reporter_user_id'] : null,
            reporterName:         $row['reporter_name'] ?? null,
            reporterAcronym:      $row['reporter_acronym'] ?? null,
            description:          $row['description'] ?? null,
            mechanicNote:         $row['mechanic_note'] ?? null,
            otherFailure:         !empty($row['other_failure']),
            otherSymptom:         !empty($row['other_symptom']),
            closedAt:             $row['closed_at'] ?? null,
            observationStartedAt: $row['observation_started_at'] ?? null,
            createdAt:            (string)($row['created_at'] ?? ''),
            updatedAt:            (string)($row['updated_at'] ?? ''),
        );
    }

    /**
     * Konwertuje encję z powrotem do tablicy asocjacyjnej.
     * Przydatne gdy szablon oczekuje tablicy (kompatybilność wsteczna).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'                     => $this->id,
            'ticket_number'          => $this->ticketNumber,
            'production_line_id'     => $this->productionLineId,
            'line_name'              => $this->lineName,
            'line_prefix'            => $this->linePrefix,
            'subsystem_name'         => $this->subsystemName,
            'category_id'            => $this->categoryId,
            'cat_label'              => $this->catLabel,
            'cat_color'              => $this->catColor,
            'status_id'              => $this->statusId,
            'status_label'           => $this->statusLabel,
            'status_color'           => $this->statusColor,
            'status_is_final'        => $this->statusIsFinal ? 1 : 0,
            'status_is_observed'     => $this->statusIsObserved ? 1 : 0,
            'symptom_id'             => $this->symptomId,
            'symptom_name'           => $this->symptomName,
            'dictionary_item_id'     => $this->dictionaryItemId,
            'dict_title'             => $this->dictTitle,
            'reporter_user_id'       => $this->reporterUserId,
            'reporter_name'          => $this->reporterName,
            'reporter_acronym'       => $this->reporterAcronym,
            'description'            => $this->description,
            'mechanic_note'          => $this->mechanicNote,
            'other_failure'          => $this->otherFailure ? 1 : 0,
            'other_symptom'          => $this->otherSymptom ? 1 : 0,
            'closed_at'              => $this->closedAt,
            'observation_started_at' => $this->observationStartedAt,
            'created_at'             => $this->createdAt,
            'updated_at'             => $this->updatedAt,
        ];
    }

    /**
     * Sprawdza czy zgłoszenie jest w trakcie aktywnego okna obserwacji.
     *
     * @param int $windowHours Długość okna obserwacji w godzinach
     */
    public function isObservationActive(int $windowHours): bool
    {
        if (!$this->statusIsObserved || $this->observationStartedAt === null) {
            return false;
        }
        // Czas wygaśnięcia = czas startu + liczba godzin okna
        $expiresAt = strtotime($this->observationStartedAt) + ($windowHours * 3600);
        return time() < $expiresAt;
    }

    /**
     * Zwraca liczbę sekund pozostałą do końca okna obserwacji (lub 0).
     *
     * @param int $windowHours Długość okna w godzinach
     */
    public function observationSecondsLeft(int $windowHours): int
    {
        if (!$this->statusIsObserved || $this->observationStartedAt === null) {
            return 0;
        }
        $expiresAt = strtotime($this->observationStartedAt) + ($windowHours * 3600);
        return max(0, $expiresAt - time());
    }
}

/*
 * ============================================================
 * DOKUMENTACJA PLIKU: Failure.php
 * ============================================================
 * Plik:        app/Entities/Failure.php
 * Opis:        Encja zgłoszenia awarii z metodą fromRow() i toArray()
 * Zależności:  brak
 * Uwagi:       Wymaga PHP 8.0+ (constructor property promotion).
 *              Dla PHP 7.4 — przenieś właściwości do ciała klasy
 *              i przypisuj je ręcznie w konstruktorze.
 * ============================================================
 */
