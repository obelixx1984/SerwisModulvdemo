<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class AssignmentModel extends BaseModel
{
    /**
     * Pobierz pełną obsadę zgłoszenia posortowaną od najstarszego.
     */
    public function getByFailure(int $failureId): array
    {
        return $this->fetchAll(
            "SELECT fa.*, u.login AS user_login,
                    ab.name AS added_by_name
             FROM failure_assignments fa
             JOIN users u ON u.id = fa.user_id
             LEFT JOIN users ab ON ab.id = fa.added_by
             WHERE fa.failure_id = ?
             ORDER BY fa.is_first DESC, fa.created_at ASC",
            [$failureId]
        );
    }

    /**
     * Pobierz pojedynczy wpis obsady po ID.
     */
    public function getById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM failure_assignments WHERE id = ?",
            [$id]
        );
    }

    /**
     * Sprawdź czy użytkownik już jest w obsadzie danego zgłoszenia.
     */
    public function isInCrew(int $failureId, int $userId): bool
    {
        $r = $this->fetchOne(
            "SELECT id FROM failure_assignments WHERE failure_id = ? AND user_id = ?",
            [$failureId, $userId]
        );
        return $r !== null;
    }

    /**
     * Dodaj użytkownika do obsady.
     * Jeśli już istnieje (UNIQUE KEY) — ignoruj (INSERT IGNORE).
     *
     * @param int    $failureId  ID zgłoszenia
     * @param int    $userId     ID użytkownika
     * @param string $userName   Snapshot nazwy
     * @param bool   $isFirst    Czy to pierwsza osoba (zmiana statusu ze startowego)?
     * @param int|null $addedBy  Kto dodał (NULL = auto przy zmianie statusu)
     */
    public function addMember(
        int $failureId,
        int $userId,
        string $userName,
        bool $isFirst = false,
        ?int $addedBy = null
    ): void {
        $this->execute(
            "INSERT IGNORE INTO failure_assignments
             (failure_id, user_id, user_name, is_first, added_by)
             VALUES (?, ?, ?, ?, ?)",
            [$failureId, $userId, $userName, $isFirst ? 1 : 0, $addedBy]
        );
    }

    /**
     * Usuń wpis obsady po ID rekordu (nie user_id!).
     */
    public function removeMember(int $id): void
    {
        $this->execute(
            "DELETE FROM failure_assignments WHERE id = ?",
            [$id]
        );
    }

    /**
     * Pobierz wszystkie zgłoszenia w których dany użytkownik jest w obsadzie.
     * Używane przez "Moje naprawy".
     */
    public function getByUserId(int $userId, array $filters = []): array
    {
        $where  = ['fa.user_id = ?'];
        $params = [$userId];

        if (!empty($filters['status_id'])) {
            $where[]  = 'f.status_id = ?';
            $params[] = $filters['status_id'];
        }
        if (!empty($filters['line_id'])) {
            $where[]  = 'f.production_line_id = ?';
            $params[] = $filters['line_id'];
        }
        if (isset($filters['category_id'])) {
            if ($filters['category_id'] === 'none') {
                $where[] = 'f.category_id IS NULL';
            } elseif ((int)$filters['category_id'] > 0) {
                $where[]  = 'f.category_id = ?';
                $params[] = (int)$filters['category_id'];
            }
        }
        if (!empty($filters['role'])) {
            $where[] = $filters['role'] === 'leader' ? 'fa.is_first = 1' : 'fa.is_first = 0';
        }

        return $this->fetchAll(
            "SELECT f.*,
                pl.name AS line_name,
                ls.name AS subsystem_name,
                fs.label AS status_label, fs.color AS status_color,
                fs.is_final AS status_is_final,
                fsym.name AS symptom_name,
                fc.label AS cat_label, fc.color AS cat_color,
                fa.is_first, fa.created_at AS assigned_at
             FROM failure_assignments fa
             JOIN failures f ON f.id = fa.failure_id
             JOIN production_lines pl ON pl.id = f.production_line_id
             LEFT JOIN line_subsystems ls ON ls.id = f.subsystem_id
             JOIN failure_statuses fs ON fs.id = f.status_id
             LEFT JOIN failure_symptoms fsym ON fsym.id = f.symptom_id
             LEFT JOIN failure_categories fc ON fc.id = f.category_id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY f.created_at DESC
             LIMIT 300",
            $params
        );
    }

    public function isLeader(int $failureId, int $userId): bool
    {
        $r = $this->fetchOne(
            "SELECT id FROM failure_assignments
         WHERE failure_id = ? AND user_id = ? AND is_first = 1",
            [$failureId, $userId]
        );
        return $r !== null;
    }
}
