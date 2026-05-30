<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class FailureModel extends BaseModel
{
    // Zmiana 1: LEFT JOIN failure_categories (category_id jest teraz NULL dla nowych zgłoszeń)
    // Zmiana 1: LEFT JOIN failure_symptoms (nowa tabela objawów)
    private function baseSelect(): string
    {
        return "SELECT f.*,
            pl.name AS line_name, pl.prefix AS line_prefix,
            ls.name AS subsystem_name,
            fc.label AS cat_label, fc.color AS cat_color,
            fs.label AS status_label, fs.color AS status_color, fs.is_final AS status_is_final,
            fd.title AS dict_title,
            fsym.name AS symptom_name
         FROM failures f
         JOIN production_lines pl ON pl.id = f.production_line_id
         LEFT JOIN line_subsystems ls ON ls.id = f.subsystem_id
         LEFT JOIN failure_categories fc ON fc.id = f.category_id
         JOIN failure_statuses fs ON fs.id = f.status_id
         LEFT JOIN failure_dictionary fd ON fd.id = f.dictionary_item_id
         LEFT JOIN failure_symptoms fsym ON fsym.id = f.symptom_id";
    }

    public function getList(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $where  = ['1=1'];
        $params = [];
        if (!empty($filters['status_id'])) {
            $where[] = 'f.status_id = ?';
            $params[] = $filters['status_id'];
        }
        if (!empty($filters['line_id'])) {
            $where[] = 'f.production_line_id = ?';
            $params[] = $filters['line_id'];
        }
        if (!empty($filters['category_id'])) {
            if ($filters['category_id'] === 'none') {
                $where[] = 'f.category_id IS NULL';
            } else {
                $where[] = 'f.category_id = ?';
                $params[] = $filters['category_id'];
            }
        }
        if (!empty($filters['search'])) {
            $where[] = '(f.ticket_number LIKE ? OR f.description LIKE ? OR fsym.name LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }
        if (!empty($filters['reporter_user_id'])) {
            $where[]  = 'f.reporter_user_id = ?';
            $params[] = (int)$filters['reporter_user_id'];
        }
        $sql = $this->baseSelect()
            . ' WHERE ' . implode(' AND ', $where)
            . ' ORDER BY f.created_at DESC'
            . " LIMIT $limit OFFSET $offset";
        return $this->fetchAll($sql, $params);
    }

    public function countList(array $filters = []): int
    {
        $where  = ['1=1'];
        $params = [];
        if (!empty($filters['status_id'])) {
            $where[] = 'f.status_id = ?';
            $params[] = $filters['status_id'];
        }
        if (!empty($filters['line_id'])) {
            $where[] = 'f.production_line_id = ?';
            $params[] = $filters['line_id'];
        }
        if (!empty($filters['category_id'])) {
            if ($filters['category_id'] === 'none') {
                $where[] = 'f.category_id IS NULL';
            } else {
                $where[] = 'f.category_id = ?';
                $params[] = $filters['category_id'];
            }
        }
        if (!empty($filters['search'])) {
            $where[] = '(f.ticket_number LIKE ? OR f.description LIKE ? OR fsym.name LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }
        if (!empty($filters['reporter_user_id'])) {
            $where[]  = 'f.reporter_user_id = ?';
            $params[] = (int)$filters['reporter_user_id'];
        }
        // Zmiana 1: LEFT JOIN failure_symptoms potrzebny też w COUNT gdy filtrujemy po symptom_name
        $st = $this->db->prepare(
            "SELECT COUNT(*) FROM failures f
             LEFT JOIN failure_symptoms fsym ON fsym.id = f.symptom_id
             WHERE " . implode(' AND ', $where)
        );
        $st->execute($params);
        return (int) $st->fetchColumn();
    }

    public function getById(int $id): ?array
    {
        return $this->fetchOne($this->baseSelect() . ' WHERE f.id = ?', [$id]);
    }

    public function getByTicket(string $ticket): ?array
    {
        return $this->fetchOne($this->baseSelect() . ' WHERE f.ticket_number = ?', [$ticket]);
    }

    // Zmiana 1: symptom_id zamiast category_id + dictionary_item_id od zgłaszającego
    // category_id i dictionary_item_id ustawia mechanik (Zmiana 2)
    public function create(array $d): int
    {
        return $this->execute(
            "INSERT INTO failures
            (ticket_number, production_line_id, subsystem_id, symptom_id, other_symptom, category_id, status_id,
            dictionary_item_id, reporter_acronym, reporter_name, reporter_user_id, description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $d['ticket_number'],
                $d['production_line_id'],
                $d['subsystem_id'] ?? null,
                $d['symptom_id'] ?? null,
                $d['other_symptom'] ?? 0,      // ← NOWE
                $d['category_id'] ?? null,
                $d['status_id'],
                $d['dictionary_item_id'] ?? null,
                $d['reporter_acronym'] ?? null,
                $d['reporter_name'] ?? null,
                $d['reporter_user_id'] ?? null,
                $d['description'] ?? null,
            ]
        );
    }

    public function getByReporterUserId(int $userId, string $reporterName = ''): array
    {
        if ($userId > 0) {
            // Właściwe filtrowanie po user_id (po migracji)
            return $this->fetchAll(
                $this->baseSelect() .
                    " WHERE f.reporter_user_id = ?
                  ORDER BY f.created_at DESC
                  LIMIT 200",
                [$userId]
            );
        }
        // Fallback: filtrowanie po imieniu i nazwisku (dla starych rekordów)
        return $this->fetchAll(
            $this->baseSelect() .
                " WHERE f.reporter_name = ?
              ORDER BY f.created_at DESC
              LIMIT 200",
            [$reporterName]
        );
    }

    public function changeStatus(int $id, int $newStatusId, bool $isFinal): void
    {
        $closed = $isFinal ? 'closed_at = NOW(),' : '';
        $this->execute(
            "UPDATE failures SET status_id = ?, $closed updated_at = NOW() WHERE id = ?",
            [$newStatusId, $id]
        );
    }

    /**
     * Aktualizuje objaw awarii (symptom_id) zgłoszenia.
     * Używane przez użytkownika z modala edycji w "Moje zgłoszenia".
     * Nowa metoda — Poprawka błąd 1.
     */
    public function updateSymptom(int $id, ?int $symptomId, int $otherSymptom = 0, ?string $description = null): void
    {
        $this->execute(
            "UPDATE failures
             SET symptom_id = ?, other_symptom = ?, description = ?, updated_at = NOW()
             WHERE id = ?",
            [$symptomId, $otherSymptom, $description, $id]
        );
    }

    // Zmiana 2: mechanik ustawia kategorie i usterkę
    public function setCategory(int $id, array $d): void
    {
        $this->execute(
            "UPDATE failures
             SET category_id = ?, dictionary_item_id = ?, other_failure = ?, mechanic_note = ?,
                 updated_at = NOW()
             WHERE id = ?",
            [
                $d['category_id'] ?: null,
                $d['dictionary_item_id'] ?: null,
                $d['other_failure'] ? 1 : 0,
                $d['mechanic_note'] ?: null,
                $id
            ]
        );
    }

    public function addHistory(
        int $failureId,
        ?int $userId,
        string $action,
        ?int $oldStatusId,
        ?int $newStatusId,
        string $actorName,
        ?string $note = null
    ): void {
        $this->execute(
            "INSERT INTO failure_history
             (failure_id, user_id, actor_name, action, old_status_id, new_status_id, note)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$failureId, $userId, $actorName, $action, $oldStatusId, $newStatusId, $note]
        );
    }

    public function getHistory(int $failureId): array
    {
        return $this->fetchAll(
            "SELECT fh.*,
                fs_old.label AS old_status_label, fs_old.color AS old_status_color,
                fs_new.label AS new_status_label, fs_new.color AS new_status_color
             FROM failure_history fh
             LEFT JOIN failure_statuses fs_old ON fs_old.id = fh.old_status_id
             LEFT JOIN failure_statuses fs_new ON fs_new.id = fh.new_status_id
             WHERE fh.failure_id = ?
             ORDER BY fh.created_at ASC",
            [$failureId]
        );
    }

    public function getComments(int $failureId): array
    {
        return $this->fetchAll(
            "SELECT * FROM failure_comments WHERE failure_id = ? ORDER BY created_at ASC",
            [$failureId]
        );
    }

    public function addComment(int $failureId, ?int $userId, string $author, string $comment): int
    {
        return $this->execute(
            "INSERT INTO failure_comments (failure_id, user_id, author, comment) VALUES (?, ?, ?, ?)",
            [$failureId, $userId, $author, $comment]
        );
    }

    /** Historia linii do widoku publicznego i historii linii */
    public function getLineHistory(int $lineId, int $days = 30, int $limit = 9999, int $offset = 0): array
    {
        return $this->fetchAll(
            $this->baseSelect() . "
             WHERE f.production_line_id = ?
               AND f.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             ORDER BY f.created_at DESC
             LIMIT $limit OFFSET $offset",
            [$lineId, $days]
        );
    }

    /**
     * Zlicza zgłoszenia dla linii w danym przedziale czasowym.
     * Używane do paginacji na stronie Historia linii.
     */
    public function countLineHistory(int $lineId, int $days = 30): int
    {
        $st = $this->db->prepare(
            "SELECT COUNT(*)
             FROM failures f
             WHERE f.production_line_id = ?
               AND f.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $st->execute([$lineId, $days]);
        return (int) $st->fetchColumn();
    }

    /** Statystyki linii — POPRAWKA 9: zwraca dane do obliczenia śr. czasu naprawy */
    public function getLineStats(int $lineId, int $days = 30): array
    {
        $empty = [
            'total' => 0,
            'open_count' => 0,
            'closed_count' => 0,
            'avg_repair_minutes' => null,
            'avg_repair_str' => '—'
        ];

        $row = $this->fetchOne(
            "SELECT
               COUNT(*) AS total,
               SUM(CASE WHEN fs.is_final = 0 THEN 1 ELSE 0 END) AS open_count,
               SUM(CASE WHEN fs.is_final = 1 THEN 1 ELSE 0 END) AS closed_count,
               AVG(CASE WHEN f.closed_at IS NOT NULL
                        THEN TIMESTAMPDIFF(MINUTE, f.created_at, f.closed_at)
                   END) AS avg_repair_minutes
             FROM failures f
             JOIN failure_statuses fs ON fs.id = f.status_id
             WHERE f.production_line_id = ?
               AND f.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$lineId, $days]
        );

        if (!is_array($row)) {
            return $empty;
        }

        $row['avg_repair_str'] = '—';
        if (!empty($row['avg_repair_minutes'])) {
            $min = (float) $row['avg_repair_minutes'];
            if ($min < 60)       $row['avg_repair_str'] = round($min) . ' min';
            elseif ($min < 1440) $row['avg_repair_str'] = round($min / 60, 1) . 'h';
            else                 $row['avg_repair_str'] = round($min / 1440, 1) . ' dni';
        }
        return $row;
    }

    /** Usuń zgłoszenie wraz z historią i komentarzami (kaskadowo przez FK) */
    public function deleteFailure(int $id): void
    {
        $this->execute("DELETE FROM failures WHERE id = ?", [$id]);
    }

    // Zmiana 1+5: sprawdza duplikat po symptom_id (nie dictionary_item_id)
    public function findOpenDuplicate(int $lineId, int $symptomId): ?array
    {
        return $this->fetchOne(
            "SELECT f.ticket_number FROM failures f
             JOIN failure_statuses fs ON fs.id = f.status_id
             WHERE f.production_line_id = ? AND f.symptom_id = ? AND fs.is_final = 0
             LIMIT 1",
            [$lineId, $symptomId]
        );
    }

    /** ZMIANA 3: Zlicz awarie zgłoszone w bieżącym miesiącu i roku */
    public function getLast30DaysCount(): int
    {
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS cnt
         FROM failures
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        return (int)($row['cnt'] ?? 0);
    }

    /** Globalny średni czas naprawy (wszystkie linie, zamknięte zgłoszenia) */
    public function getGlobalAvgRepairTime(): string
    {
        $row = $this->fetchOne(
            "SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, closed_at)) AS avg_min
             FROM failures
             WHERE closed_at IS NOT NULL AND created_at IS NOT NULL"
        );
        if (!$row || $row['avg_min'] === null) return '—';
        $min = (float) $row['avg_min'];
        if ($min < 60)       return round($min) . ' min';
        if ($min < 1440)     return round($min / 60, 1) . 'h';
        return round($min / 1440, 1) . ' dni';
    }

    /** Dashboard: statystyki ogólne */
    public function getDashboardStats(): array
    {
        return $this->fetchOne(
            "SELECT
               SUM(CASE WHEN fs.is_initial = 1 THEN 1 ELSE 0 END)        AS new_count,
               SUM(CASE WHEN fs.label = 'W trakcie naprawy' THEN 1 ELSE 0 END) AS progress_count,
               SUM(CASE WHEN fs.is_final = 0 THEN 1 ELSE 0 END)          AS open_count,
               SUM(CASE WHEN fs.is_final = 1 AND DATE(f.updated_at) = CURDATE() THEN 1 ELSE 0 END) AS closed_today
             FROM failures f JOIN failure_statuses fs ON fs.id = f.status_id"
        ) ?? [];
    }

    // ── Zdjęcia zgłoszenia ──────────────────────────────────

    public function getPhotos(int $failureId, bool $onlyPublic = false): array
    {
        $sql = "SELECT * FROM failure_photos WHERE failure_id = ?";
        if ($onlyPublic) {
            $sql .= " AND is_public = 1";
        }
        $sql .= " ORDER BY created_at ASC";
        return $this->fetchAll($sql, [$failureId]);
    }

    public function addPhoto(array $d): int
    {
        $this->execute(
            "INSERT INTO failure_photos
                (failure_id, user_id, username, filename, path, filesize, is_public)
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $d['failure_id'],
                $d['user_id'],
                $d['username'],
                $d['filename'],
                $d['path'],
                $d['filesize'],
                $d['is_public'],
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function getPhotoById(int $photoId): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM failure_photos WHERE id = ?",
            [$photoId]
        );
    }

    public function deletePhoto(int $photoId): void
    {
        $this->execute("DELETE FROM failure_photos WHERE id = ?", [$photoId]);
    }
}

// ────────────────────────────────────────────────────────────
