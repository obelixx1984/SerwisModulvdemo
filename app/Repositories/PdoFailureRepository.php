<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * PdoFailureRepository — warstwa dostępu do danych dla zgłoszeń awarii.
 *
 * Przenosi zapytania SQL z FailureModel i FailureController do jednego miejsca.
 * Każda metoda wykonuje dokładnie jeden, optymalny SELECT z JOIN zamiast N+1.
 * Zwraca tablice asocjacyjne (kompatybilność z szablonami) lub bool/void.
 */
class PdoFailureRepository
{
    public function __construct(private readonly \PDO $db) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Prywatny helper — wspólny SELECT z wszystkimi JOIN
    // Identyczny z FailureModel::baseSelect() — jeden punkt zmiany
    // ─────────────────────────────────────────────────────────────────────────

    private function baseSelect(): string
    {
        return "SELECT f.*,
            pl.name   AS line_name,   pl.prefix AS line_prefix,
            ls.name   AS subsystem_name,
            fc.label  AS cat_label,   fc.color  AS cat_color,
            fs.label  AS status_label, fs.color AS status_color,
                fs.is_final    AS status_is_final,
                fs.is_observed AS status_is_observed,
                fs.is_initial  AS status_is_initial,
            fd.title  AS dict_title,
            fsym.name AS symptom_name
         FROM failures f
         JOIN production_lines pl   ON pl.id   = f.production_line_id
         LEFT JOIN line_subsystems ls ON ls.id = f.subsystem_id
         LEFT JOIN failure_categories fc ON fc.id = f.category_id
         JOIN failure_statuses fs   ON fs.id   = f.status_id
         LEFT JOIN failure_dictionary fd ON fd.id = f.dictionary_item_id
         LEFT JOIN failure_symptoms fsym ON fsym.id = f.symptom_id";
    }

    /**
     * Buduje tablicę WHERE + params na podstawie filtrów.
     * Używane zarówno w getList() jak i countList() — DRY.
     *
     * @param  array<string, mixed> $filters
     * @return array{0: string[], 1: mixed[]}  [$whereClauses, $params]
     */
    private function buildWhere(array $filters): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status_id'])) {
            $where[]  = 'f.status_id = ?';
            $params[] = (int)$filters['status_id'];
        }

        if (!empty($filters['line_id'])) {
            $where[]  = 'f.production_line_id = ?';
            $params[] = (int)$filters['line_id'];
        }

        if (isset($filters['category_id'])) {
            if ($filters['category_id'] === 'none') {
                // Filtr: zgłoszenia bez przypisanej kategorii
                $where[] = 'f.category_id IS NULL';
            } elseif ((int)$filters['category_id'] > 0) {
                $where[]  = 'f.category_id = ?';
                $params[] = (int)$filters['category_id'];
            }
        }

        if (!empty($filters['search'])) {
            // Przeszukuje numer, opis i nazwę objawu w jednym kroku
            $where[]  = '(f.ticket_number LIKE ? OR f.description LIKE ? OR fsym.name LIKE ?)';
            $s        = '%' . $filters['search'] . '%';
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        if (!empty($filters['reporter_user_id'])) {
            $where[]  = 'f.reporter_user_id = ?';
            $params[] = (int)$filters['reporter_user_id'];
        }

        return [$where, $params];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Publiczne metody — API Repository
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Pobiera stronicowaną listę zgłoszeń z filtrowaniem.
     * Jeden SELECT z JOIN — brak problemu N+1.
     *
     * @param  array<string, mixed> $filters  Klucze: status_id, line_id, category_id, search, reporter_user_id
     * @param  int                  $limit    Liczba rekordów na stronę
     * @param  int                  $offset   Przesunięcie (strona * limit)
     * @return array<int, array<string, mixed>>
     */
    public function getList(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        [$where, $params] = $this->buildWhere($filters);

        $sql = $this->baseSelect()
            . ' WHERE ' . implode(' AND ', $where)
            . ' ORDER BY f.created_at DESC'
            . " LIMIT {$limit} OFFSET {$offset}";

        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /**
     * Zlicza rekordy pasujące do filtrów (potrzebne do paginacji).
     * Używa LEFT JOIN z failure_symptoms bo filtr search może go wymagać.
     *
     * @param  array<string, mixed> $filters
     */
    public function countList(array $filters = []): int
    {
        [$where, $params] = $this->buildWhere($filters);

        // LEFT JOIN z failure_symptoms niezbędny gdy filtrujemy po symptom_name
        $sql = "SELECT COUNT(*) FROM failures f
                LEFT JOIN failure_symptoms fsym ON fsym.id = f.symptom_id
                WHERE " . implode(' AND ', $where);

        $st = $this->db->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    /**
     * Pobiera jedno zgłoszenie po ID (z pełnym JOIN).
     *
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array
    {
        $st = $this->db->prepare($this->baseSelect() . ' WHERE f.id = ?');
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /**
     * Pobiera zgłoszenie po numerze biletu.
     *
     * @return array<string, mixed>|null
     */
    public function getByTicket(string $ticket): ?array
    {
        $st = $this->db->prepare($this->baseSelect() . ' WHERE f.ticket_number = ?');
        $st->execute([$ticket]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /**
     * Aktualizuje status zgłoszenia.
     * Ustawia closed_at i observation_started_at w jednym UPDATE.
     *
     * @param int  $id          ID zgłoszenia
     * @param int  $newStatusId Nowy status
     * @param bool $isFinal     Czy status jest końcowy (ustawia closed_at)
     * @param bool $isObserved  Czy status uruchamia okno obserwacji
     */
    public function changeStatus(
        int     $id,
        int     $newStatusId,
        bool    $isFinal,
        bool    $isObserved = false,
        ?string $observationUntil = null
    ): void {
        $closedPart = $isFinal ? 'closed_at = NOW(),' : '';
        $params     = [$newStatusId];

        if ($isObserved) {
            if ($observationUntil !== null) {
                $observationPart = 'observation_started_at = NOW(), observation_until = ?,';
                $params[]        = $observationUntil;
            } else {
                $observationPart = 'observation_started_at = NOW(), observation_until = NULL,';
            }
        } else {
            $observationPart = 'observation_started_at = NULL, observation_until = NULL,';
        }

        $params[] = $id;

        $st = $this->db->prepare(
            "UPDATE failures SET status_id = ?, {$closedPart} {$observationPart} updated_at = NOW()
         WHERE id = ?"
        );
        $st->execute($params);
    }

    /**
     * Zapisuje nowe zgłoszenie awarii do bazy.
     *
     * @param  array<string, mixed> $d Dane zgłoszenia
     * @return int                     ID nowego rekordu
     */
    public function create(array $d): int
    {
        $st = $this->db->prepare(
            "INSERT INTO failures
             (ticket_number, production_line_id, subsystem_id, symptom_id, other_symptom,
              category_id, status_id, dictionary_item_id,
              reporter_acronym, reporter_name, reporter_user_id, description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $st->execute([
            $d['ticket_number'],
            $d['production_line_id'],
            $d['subsystem_id']        ?? null,
            $d['symptom_id']          ?? null,
            $d['other_symptom']       ?? 0,
            $d['category_id']         ?? null,
            $d['status_id'],
            $d['dictionary_item_id']  ?? null,
            $d['reporter_acronym']    ?? null,
            $d['reporter_name']       ?? null,
            $d['reporter_user_id']    ?? null,
            $d['description']         ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Aktualizuje kategorię i usterkę zgłoszenia (ustawiane przez mechanika).
     *
     * @param array<string, mixed> $d
     */
    public function setCategory(int $id, array $d): void
    {
        $st = $this->db->prepare(
            "UPDATE failures
             SET category_id = ?, dictionary_item_id = ?, other_failure = ?,
                 mechanic_note = ?, updated_at = NOW()
             WHERE id = ?"
        );
        $st->execute([
            $d['category_id']        ?: null,
            $d['dictionary_item_id'] ?: null,
            $d['other_failure']      ? 1 : 0,
            $d['mechanic_note']      ?: null,
            $id,
        ]);
    }

    /**
     * Usuwa zgłoszenie (kaskadowo przez FK w bazie).
     */
    public function delete(int $id): void
    {
        $st = $this->db->prepare("DELETE FROM failures WHERE id = ?");
        $st->execute([$id]);
    }

    /**
     * Statystyki dla dashboardu — jeden agregujący SELECT.
     *
     * @return array<string, mixed>
     */
    public function getDashboardStats(): array
    {
        $st = $this->db->query(
            "SELECT
               SUM(CASE WHEN fs.is_initial = 1 THEN 1 ELSE 0 END)                          AS new_count,
               SUM(CASE WHEN fs.is_final = 0 THEN 1 ELSE 0 END)                            AS open_count,
               SUM(CASE WHEN fs.is_final = 1 AND DATE(f.updated_at) = CURDATE() THEN 1 ELSE 0 END) AS closed_today
             FROM failures f
             JOIN failure_statuses fs ON fs.id = f.status_id"
        );
        return $st->fetch() ?: [];
    }

    /**
     * Zlicza zgłoszenia z ostatnich 30 dni.
     */
    public function getLast30DaysCount(): int
    {
        $st = $this->db->query(
            "SELECT COUNT(*) FROM failures WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        return (int)$st->fetchColumn();
    }

    /**
     * Zlicza zgłoszenia per status — używane do wykresu / dashboardu.
     * Zwraca tablicę [status_id => count].
     *
     * @return array<int, int>
     */
    public function countByStatus(): array
    {
        $st = $this->db->query(
            "SELECT status_id, COUNT(*) AS cnt FROM failures GROUP BY status_id"
        );
        $result = [];
        foreach ($st->fetchAll() as $row) {
            $result[(int)$row['status_id']] = (int)$row['cnt'];
        }
        return $result;
    }

    /**
     * Sprawdza duplikat: otwarte zgłoszenie na tej samej linii z tym samym objawem.
     *
     * @return array<string, mixed>|null
     */
    public function findOpenDuplicate(int $lineId, int $symptomId): ?array
    {
        $st = $this->db->prepare(
            "SELECT f.ticket_number FROM failures f
             JOIN failure_statuses fs ON fs.id = f.status_id
             WHERE f.production_line_id = ? AND f.symptom_id = ? AND fs.is_final = 0
             LIMIT 1"
        );
        $st->execute([$lineId, $symptomId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    /**
     * Globalny średni czas naprawy (wszystkie zamknięte zgłoszenia).
     */
    public function getGlobalAvgRepairTime(): string
    {
        $st = $this->db->query(
            "SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, closed_at)) AS avg_min
             FROM failures WHERE closed_at IS NOT NULL AND created_at IS NOT NULL"
        );
        $row = $st->fetch();

        if (!$row || $row['avg_min'] === null) {
            return '—';
        }

        // Formatowanie: minuty → godziny → dni
        $min = (float)$row['avg_min'];
        if ($min < 60)   return round($min) . ' min';
        if ($min < 1440) return round($min / 60, 1) . 'h';
        return round($min / 1440, 1) . ' dni';
    }

    /**
     * Dodaje wpis do historii zgłoszenia.
     */
    public function addHistory(
        int     $failureId,
        ?int    $userId,
        string  $action,
        ?int    $oldStatusId,
        ?int    $newStatusId,
        string  $actorName,
        ?string $note = null
    ): void {
        $st = $this->db->prepare(
            "INSERT INTO failure_history
             (failure_id, user_id, actor_name, action, old_status_id, new_status_id, note)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $st->execute([$failureId, $userId, $actorName, $action, $oldStatusId, $newStatusId, $note]);
    }

    /**
     * Pobiera historię zmian zgłoszenia.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getHistory(int $failureId): array
    {
        $st = $this->db->prepare(
            "SELECT fh.*,
                fs_old.label AS old_status_label, fs_old.color AS old_status_color,
                fs_new.label AS new_status_label, fs_new.color AS new_status_color
             FROM failure_history fh
             LEFT JOIN failure_statuses fs_old ON fs_old.id = fh.old_status_id
             LEFT JOIN failure_statuses fs_new ON fs_new.id = fh.new_status_id
             WHERE fh.failure_id = ?
             ORDER BY fh.created_at ASC"
        );
        $st->execute([$failureId]);
        return $st->fetchAll();
    }

    /**
     * Pobiera komentarze do zgłoszenia.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getComments(int $failureId): array
    {
        $st = $this->db->prepare(
            "SELECT * FROM failure_comments WHERE failure_id = ? ORDER BY created_at ASC"
        );
        $st->execute([$failureId]);
        return $st->fetchAll();
    }

    /**
     * Dodaje komentarz do zgłoszenia.
     */
    public function addComment(int $failureId, ?int $userId, string $author, string $comment): int
    {
        $st = $this->db->prepare(
            "INSERT INTO failure_comments (failure_id, user_id, author, comment) VALUES (?, ?, ?, ?)"
        );
        $st->execute([$failureId, $userId, $author, $comment]);
        return (int)$this->db->lastInsertId();
    }

    public function getCommentById(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM failure_comments WHERE id = ?");
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function updateComment(int $id, string $comment): void
    {
        $st = $this->db->prepare("UPDATE failure_comments SET comment = ? WHERE id = ?");
        $st->execute([$comment, $id]);
    }

    public function deleteComment(int $id): void
    {
        $st = $this->db->prepare("DELETE FROM failure_comments WHERE id = ?");
        $st->execute([$id]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Zdjęcia
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPhotos(int $failureId, bool $onlyPublic = false): array
    {
        $sql = "SELECT * FROM failure_photos WHERE failure_id = ?";
        if ($onlyPublic) {
            $sql .= " AND is_public = 1";
        }
        $sql .= " ORDER BY created_at ASC";

        $st = $this->db->prepare($sql);
        $st->execute([$failureId]);
        return $st->fetchAll();
    }

    /**
     * @param array<string, mixed> $d
     */
    public function addPhoto(array $d): int
    {
        $st = $this->db->prepare(
            "INSERT INTO failure_photos
             (failure_id, user_id, username, filename, path, filesize, is_public)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $st->execute([
            $d['failure_id'],
            $d['user_id'],
            $d['username'],
            $d['filename'],
            $d['path'],
            $d['filesize'],
            $d['is_public'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getPhotoById(int $photoId): ?array
    {
        $st = $this->db->prepare("SELECT * FROM failure_photos WHERE id = ?");
        $st->execute([$photoId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function deletePhoto(int $photoId): void
    {
        $st = $this->db->prepare("DELETE FROM failure_photos WHERE id = ?");
        $st->execute([$photoId]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Uwagi do okna obserwacji
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getObservationNotes(int $failureId): array
    {
        $st = $this->db->prepare(
            "SELECT * FROM failure_observation_notes WHERE failure_id = ? ORDER BY created_at ASC"
        );
        $st->execute([$failureId]);
        return $st->fetchAll();
    }

    public function addObservationNote(int $failureId, int $userId, string $userName, string $note): int
    {
        $st = $this->db->prepare(
            "INSERT INTO failure_observation_notes (failure_id, user_id, user_name, note)
             VALUES (?, ?, ?, ?)"
        );
        $st->execute([$failureId, $userId, $userName, $note]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getObservationNoteById(int $noteId): ?array
    {
        $st = $this->db->prepare(
            "SELECT * FROM failure_observation_notes WHERE id = ?"
        );
        $st->execute([$noteId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function deleteObservationNote(int $noteId): void
    {
        $st = $this->db->prepare("DELETE FROM failure_observation_notes WHERE id = ?");
        $st->execute([$noteId]);
    }

    /**
     * Aktualizuje objaw zgłoszenia.
     */
    public function updateSymptom(int $id, ?int $symptomId, int $otherSymptom, ?string $description): void
    {
        $st = $this->db->prepare(
            "UPDATE failures SET symptom_id = ?, other_symptom = ?, description = ?, updated_at = NOW()
             WHERE id = ?"
        );
        $st->execute([$symptomId, $otherSymptom, $description, $id]);
    }
}

/*
 * ============================================================
 * DOKUMENTACJA PLIKU: PdoFailureRepository.php
 * ============================================================
 * Plik:        app/Repositories/PdoFailureRepository.php
 * Opis:        Warstwa dostępu do danych zgłoszeń awarii (PDO, bez ORM)
 * Zależności:  \PDO (wstrzykiwane przez DI Container)
 * Uwagi:       Jeden SELECT z JOIN zamiast N+1.
 *              Metoda buildWhere() eliminuje duplikację filtrów.
 *              Rejestruj w config/bindings.php jako PdoFailureRepository::class.
 * ============================================================
 */
