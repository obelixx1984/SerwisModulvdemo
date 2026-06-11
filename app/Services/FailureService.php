<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Helpers;
use App\Models\StatusModel;
use App\Models\AssignmentModel;
use App\Models\SettingsModel;
use App\Repositories\PdoFailureRepository;

/**
 * FailureService — logika biznesowa dla zgłoszeń awarii.
 *
 * Odpowiada za:
 * - walidację danych przed zapisem lub zmianą statusu,
 * - reguły biznesowe (blokady, obsada, okno obserwacji),
 * - agregację danych na potrzeby dashboardu.
 *
 * Nie zawiera SQL — deleguje do PdoFailureRepository.
 * Nie zawiera logiki HTTP — nie używa $_GET/$_POST ani redirect().
 * Błędy sygnalizuje wyjątkami (ServiceException lub \InvalidArgumentException).
 */
class FailureService
{
    public function __construct(
        private readonly PdoFailureRepository $repo,
        private readonly StatusModel          $statusModel,
        private readonly AssignmentModel      $assignmentModel,
        private readonly SettingsModel        $settingsModel
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Listy i stronicowanie
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Zwraca stronicowaną listę zgłoszeń wraz z metadanymi paginacji.
     *
     * @param  array<string, mixed> $filters  Tablica filtrów (status_id, line_id, ...)
     * @param  int                  $page     Bieżąca strona (min. 1)
     * @param  int                  $perPage  Liczba rekordów na stronę
     * @return array{items: array, pager: array, total: int}
     */
    public function getPaginatedList(array $filters, int $page, int $perPage): array
    {
        $page  = max(1, $page);
        $total = $this->repo->countList($filters);
        $pager = Helpers::paginate($total, $page, $perPage);
        $items = $this->repo->getList($filters, $pager['per_page'], $pager['offset']);

        return compact('items', 'pager', 'total');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Tworzenie zgłoszenia z walidacją
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Tworzy nowe zgłoszenie po walidacji danych wejściowych.
     *
     * @param  array<string, mixed> $data Dane z formularza
     * @return int                        ID nowego zgłoszenia
     * @throws \InvalidArgumentException  Gdy dane są niepoprawne
     */
    public function create(array $data): int
    {
        // Walidacja obowiązkowych pól
        if (empty($data['production_line_id'])) {
            throw new \InvalidArgumentException('Wybierz linię produkcyjną.');
        }
        if (empty($data['status_id'])) {
            throw new \InvalidArgumentException('Brak statusu startowego.');
        }
        if (empty($data['reporter_name'])) {
            throw new \InvalidArgumentException('Podaj imię i nazwisko zgłaszającego.');
        }

        // Walidacja: Inny objaw wymaga opisu
        if (!empty($data['other_symptom']) && empty($data['description'])) {
            throw new \InvalidArgumentException('Przy "Inny objaw" opis jest obowiązkowy.');
        }

        return $this->repo->create($data);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Zmiana statusu z regułami biznesowymi
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Zmienia status zgłoszenia po sprawdzeniu wszystkich reguł biznesowych.
     *
     * @param  array<string, mixed> $failure      Tablica zgłoszenia (z getById)
     * @param  int                  $newStatusId  Nowy status
     * @param  int                  $currentUserId ID aktualnego użytkownika
     * @param  string               $userName     Nazwa użytkownika (do historii)
     * @param  string               $note         Opcjonalna notatka
     * @throws \InvalidArgumentException          Gdy zmiana jest niedozwolona
     */
    public function changeStatus(
        array  $failure,
        int    $newStatusId,
        int    $currentUserId,
        string $userName,
        string $note = ''
    ): void {
        $id = (int)$failure['id'];

        // Reguła: nie można zmieniać na ten sam status
        if ($failure['status_id'] == $newStatusId) {
            throw new \InvalidArgumentException('Zgłoszenie ma już ten status. Wybierz inny.');
        }

        // Reguła: zgłoszenie zamknięte jest niezmieniane
        if (!empty($failure['status_is_final'])) {
            throw new \InvalidArgumentException('Zgłoszenie jest zamknięte — nie można zmieniać statusu.');
        }

        // Weryfikacja istnienia nowego statusu
        $newStatus = $this->statusModel->getById($newStatusId);
        if (!$newStatus) {
            throw new \InvalidArgumentException('Nieprawidłowy status.');
        }

        // Reguła: status startowy jest nadawany tylko automatycznie
        if (!empty($newStatus['is_initial'])) {
            throw new \InvalidArgumentException('Status startowy nadawany jest automatycznie.');
        }

        // Reguła: przed statusem końcowym wymagane: kategoria, usterka, obsada
        if (!empty($newStatus['is_final'])) {
            $this->validateForClosing($failure, $id);
        }

        // Auto-dodanie bieżącego użytkownika do obsady (jako prowadzący jeśli pierwszy)
        $currentStatus = $this->statusModel->getById($failure['status_id']);
        $isFirst       = !empty($currentStatus['is_initial'])
            && !$this->assignmentModel->isInCrew($id, $currentUserId);
        $this->assignmentModel->addMember($id, $currentUserId, $userName, $isFirst);

        // Zmiana statusu w bazie
        $this->repo->changeStatus(
            $id,
            $newStatusId,
            (bool)$newStatus['is_final'],
            (bool)($newStatus['is_observed'] ?? false)
        );

        // Zapis historii zmiany
        $this->repo->addHistory(
            $id,
            $currentUserId,
            'status_changed',
            $failure['status_id'],
            $newStatusId,
            $userName,
            $note ?: 'Zmiana statusu: ' . $failure['status_label'] . ' → ' . $newStatus['label']
        );
    }

    /**
     * Walidacja kompletności danych przed nadaniem statusu końcowego.
     *
     * @param  array<string, mixed> $failure
     * @throws \InvalidArgumentException
     */
    private function validateForClosing(array $failure, int $failureId): void
    {
        $hasCategory = !empty($failure['category_id']);
        $hasDict     = !empty($failure['dictionary_item_id']);
        $hasOther    = !empty($failure['other_failure']);
        $hasNote     = !empty($failure['mechanic_note']);

        // Wymagane: kategoria ORAZ (słownikowa usterka LUB innaUsterka z notatką)
        if (!$hasCategory || (!$hasDict && !$hasOther) || ($hasOther && !$hasNote)) {
            throw new \InvalidArgumentException(
                'Nie dodałeś kategorii i rodzaju awarii!!! Uzupełnij to!!!'
            );
        }

        // Wymagana obsada przed zamknięciem
        $assignments = $this->assignmentModel->getByFailure($failureId);
        if (empty($assignments)) {
            throw new \InvalidArgumentException(
                'Brak obsady zgłoszenia!!! Przed zamknięciem potwierdź kto pracował przy naprawie.'
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Dane dashboardu
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Zwraca zagregowane dane dla widoku dashboardu.
     * Wszystkie dane w jednej metodzie — jeden punkt wejścia dla kontrolera.
     *
     * @param  array<string, mixed>[] $statuses   Lista statusów (z StatusModel::getAll)
     * @return array<string, mixed>
     */
    public function getDashboardData(array $statuses): array
    {
        // Podstawowe statystyki — jeden zapytanie agregatywne
        $stats = $this->repo->getDashboardStats();

        // Zlicz per status (jeden SELECT GROUP BY)
        $countByStatus = $this->repo->countByStatus();
        $byStatus      = [];
        foreach ($statuses as $s) {
            $byStatus[$s['id']] = [
                'label' => $s['label'],
                'color' => $s['color'],
                'count' => $countByStatus[$s['id']] ?? 0,
            ];
        }

        return [
            'stats'          => $stats,
            'byStatus'       => $byStatus,
            'last30Count'    => $this->repo->getLast30DaysCount(),
            'avg_repair_all' => $this->repo->getGlobalAvgRepairTime(),
        ];
    }

    /**
     * Sprawdza czy okno obserwacji jest aktywne i zwraca czas pozostały w sekundach.
     *
     * @param  array<string, mixed> $failure
     * @return array{active: bool, seconds_left: int}
     */
    public function getObservationWindow(array $failure): array
    {
        // Pobierz długość okna z ustawień (domyślnie 8h)
        $windowHours = (int)($this->settingsModel->get('observation_window_hours') ?? 8);

        if (empty($failure['status_is_observed']) || empty($failure['observation_started_at'])) {
            return ['active' => false, 'seconds_left' => 0];
        }

        $expiresAt = strtotime($failure['observation_started_at']) + ($windowHours * 3600);
        $remaining = $expiresAt - time();

        return [
            'active'       => $remaining > 0,
            'seconds_left' => max(0, $remaining),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Delegacje do Repository (metody używane przez kontroler)
    // ─────────────────────────────────────────────────────────────────────────

    /** @return array<string, mixed>|null */
    public function getById(int $id): ?array
    {
        return $this->repo->getById($id);
    }

    /** @return array<string, mixed>|null */
    public function getByTicket(string $ticket): ?array
    {
        return $this->repo->getByTicket($ticket);
    }

    /** @return array<int, array<string, mixed>> */
    public function getHistory(int $failureId): array
    {
        return $this->repo->getHistory($failureId);
    }

    /** @return array<int, array<string, mixed>> */
    public function getComments(int $failureId): array
    {
        return $this->repo->getComments($failureId);
    }

    public function addComment(int $failureId, ?int $userId, string $author, string $comment): void
    {
        $this->repo->addComment($failureId, $userId, $author, $comment);
        $this->repo->addHistory($failureId, $userId, 'comment_added', null, null, $author, 'Dodano komentarz');
    }

    /** @return array<int, array<string, mixed>> */
    public function getPhotos(int $failureId, bool $onlyPublic = false): array
    {
        return $this->repo->getPhotos($failureId, $onlyPublic);
    }

    /** @return array<string, mixed>|null */
    public function getPhotoById(int $photoId): ?array
    {
        return $this->repo->getPhotoById($photoId);
    }

    /** @param array<string, mixed> $d */
    public function addPhoto(array $d): int
    {
        return $this->repo->addPhoto($d);
    }

    public function deletePhoto(int $photoId): void
    {
        $this->repo->deletePhoto($photoId);
    }

    /** @return array<int, array<string, mixed>> */
    public function getObservationNotes(int $failureId): array
    {
        return $this->repo->getObservationNotes($failureId);
    }

    /** @return array<string, mixed>|null */
    public function getObservationNoteById(int $noteId): ?array
    {
        return $this->repo->getObservationNoteById($noteId);
    }

    public function addObservationNote(int $failureId, int $userId, string $userName, string $note): void
    {
        $this->repo->addObservationNote($failureId, $userId, $userName, $note);
    }

    public function deleteObservationNote(int $noteId): void
    {
        $this->repo->deleteObservationNote($noteId);
    }

    public function setCategory(int $id, array $d): void
    {
        $this->repo->setCategory($id, $d);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }

    public function addHistory(
        int $failureId,
        ?int $userId,
        string $action,
        ?int $old,
        ?int $new,
        string $actor,
        ?string $note = null
    ): void {
        $this->repo->addHistory($failureId, $userId, $action, $old, $new, $actor, $note);
    }

    /** @return array<string, mixed>|null */
    public function findOpenDuplicate(int $lineId, int $symptomId): ?array
    {
        return $this->repo->findOpenDuplicate($lineId, $symptomId);
    }

    public function updateSymptom(int $id, ?int $symptomId, int $otherSymptom, ?string $description): void
    {
        $this->repo->updateSymptom($id, $symptomId, $otherSymptom, $description);
    }
}

/*
 * ============================================================
 * DOKUMENTACJA PLIKU: FailureService.php
 * ============================================================
 * Plik:        app/Services/FailureService.php
 * Opis:        Logika biznesowa dla zgłoszeń awarii
 * Zależności:  PdoFailureRepository, StatusModel, AssignmentModel, SettingsModel
 * Uwagi:       Błędy sygnalizuje \InvalidArgumentException.
 *              Kontroler łapie wyjątek i wywołuje Helpers::flash() + redirect().
 *              Rejestruj w config/bindings.php jako FailureService::class.
 * ============================================================
 */
