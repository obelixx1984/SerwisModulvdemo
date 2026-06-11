<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * CreateFailureDTO — dane formularza zgłoszenia awarii.
 *
 * Zastępuje surowy dostęp do $_POST w PublicController::reportPost().
 * Readonly — po utworzeniu żadne pole nie może być zmienione.
 * Walidacja odbywa się w FailureValidator::validateCreate() — tu tylko parsowanie.
 */
final class CreateFailureDTO
{
    public function __construct(
        public readonly int     $productionLineId,
        public readonly ?int    $subsystemId,
        public readonly ?int    $symptomId,
        public readonly bool    $otherSymptom,
        public readonly string  $description,
        public readonly int     $reporterUserId,
        public readonly string  $reporterName,
        public readonly string  $reporterLogin,
        public readonly string  $csrfToken,
    ) {}

    /**
     * Fabryka — tworzy DTO z tablicy $_POST.
     * Parsuje i rzutuje typy, nie waliduje reguł biznesowych.
     *
     * @param array<string, mixed> $post  Zwykle $_POST
     */
    public static function fromPost(array $post): self
    {
        // Flaga "Inne objawy" — checkbox zwraca '1' lub brak klucza
        $otherSymptom = !empty($post['other_symptom']);

        // symptom_id ignorujemy gdy zaznaczono "Inne objawy"
        $symptomId = (!$otherSymptom && !empty($post['symptom_id']))
            ? (int)$post['symptom_id']
            : null;

        // subsystem_id — null gdy puste (linia bez podzespołów)
        $subsystemId = !empty($post['subsystem_id'])
            ? (int)$post['subsystem_id']
            : null;

        return new self(
            productionLineId: (int)($post['production_line_id'] ?? 0),
            subsystemId:      $subsystemId,
            symptomId:        $symptomId,
            otherSymptom:     $otherSymptom,
            description:      trim($post['description'] ?? ''),
            reporterUserId:   (int)($post['reporter_user_id'] ?? 0),
            reporterName:     trim($post['reporter_name'] ?? ''),
            reporterLogin:    trim($post['reporter_login'] ?? ''),
            csrfToken:        (string)($post['csrf_token'] ?? ''),
        );
    }

    /**
     * Konwertuje DTO do tablicy gotowej do przekazania do FailureModel::create().
     * Status i ticket_number są uzupełniane przez kontroler (zależą od bazy).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'production_line_id' => $this->productionLineId,
            'subsystem_id'       => $this->subsystemId,
            'symptom_id'         => $this->symptomId,
            'other_symptom'      => $this->otherSymptom ? 1 : 0,
            'description'        => $this->description !== '' ? $this->description : null,
            'reporter_user_id'   => $this->reporterUserId,
            'reporter_name'      => $this->reporterName,
            'reporter_acronym'   => $this->reporterLogin,
        ];
    }
}

/*
 * ============================================================
 * DOKUMENTACJA PLIKU: CreateFailureDTO.php
 * ============================================================
 * Plik:        app/DTOs/CreateFailureDTO.php
 * Opis:        DTO danych formularza zgłoszenia awarii (POST)
 * Zależności:  brak
 * Uwagi:       Readonly — PHP 8.1+. Dla PHP 8.0 usuń readonly z konstruktora
 *              i dodaj właściwości jako public readonly w ciele klasy.
 *              Walidacja: FailureValidator::validateCreate()
 * ============================================================
 */
