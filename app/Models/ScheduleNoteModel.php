<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class ScheduleNoteModel extends BaseModel
{
    public function getActiveBySchedule(int $scheduleId): array
    {
        return $this->fetchAll(
            "SELECT sn.*, u.login AS user_login
             FROM schedule_notes sn
             JOIN users u ON u.id = sn.user_id
             WHERE sn.schedule_id = ? AND sn.is_archived = 0
             ORDER BY sn.created_at ASC",
            [$scheduleId]
        );
    }

    public function getArchivedByReview(int $reviewId): array
    {
        return $this->fetchAll(
            "SELECT sn.*, u.login AS user_login
             FROM schedule_notes sn
             JOIN users u ON u.id = sn.user_id
             WHERE sn.review_id = ? AND sn.is_archived = 1
             ORDER BY sn.created_at ASC",
            [$reviewId]
        );
    }

    public function getById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM schedule_notes WHERE id = ?",
            [$id]
        );
    }

    public function add(int $scheduleId, int $userId, string $userName, string $note): int
    {
        return $this->execute(
            "INSERT INTO schedule_notes (schedule_id, user_id, user_name, note)
             VALUES (?, ?, ?, ?)",
            [$scheduleId, $userId, $userName, $note]
        );
    }

    public function update(int $id, string $note): void
    {
        $this->execute(
            "UPDATE schedule_notes SET note = ?, updated_at = NOW() WHERE id = ?",
            [$note, $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute(
            "DELETE FROM schedule_notes WHERE id = ?",
            [$id]
        );
    }

    public function archiveForReview(int $scheduleId, int $reviewId): void
    {
        $this->execute(
            "UPDATE schedule_notes
             SET is_archived = 1, review_id = ?, updated_at = NOW()
             WHERE schedule_id = ? AND is_archived = 0",
            [$reviewId, $scheduleId]
        );
    }

    public function countActiveGrouped(array $scheduleIds): array
    {
        if (empty($scheduleIds)) return [];
        $placeholders = implode(',', array_fill(0, count($scheduleIds), '?'));
        $rows = $this->fetchAll(
            "SELECT schedule_id, COUNT(*) AS cnt
             FROM schedule_notes
             WHERE schedule_id IN ($placeholders) AND is_archived = 0
             GROUP BY schedule_id",
            $scheduleIds
        );
        $result = [];
        foreach ($rows as $r) {
            $result[(int)$r['schedule_id']] = (int)$r['cnt'];
        }
        return $result;
    }
}

// ────────────────────────────────────────────────────────────
