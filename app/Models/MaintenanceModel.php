<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class MaintenanceModel extends BaseModel
{
    public function getAllReviews(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $where  = ['1=1'];
        $params = [];
        if (!empty($filters['line_id'])) {
            $where[] = 'mr.production_line_id = ?';
            $params[] = $filters['line_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'mr.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['type'])) {
            $where[] = 'mr.review_type = ?';
            $params[] = $filters['type'];
        }

        return $this->fetchAll(
            "SELECT mr.*,
            pl.name AS line_name,
            ls.name AS subsystem_name,
            u.name  AS performer_name, u.login AS performer_nick
            FROM maintenance_reviews mr
            JOIN production_lines pl ON pl.id = mr.production_line_id
            LEFT JOIN line_subsystems ls ON ls.id = mr.subsystem_id
            JOIN users u ON u.id = mr.performed_by
            WHERE " . implode(' AND ', $where) . "
            ORDER BY mr.review_date DESC, mr.id DESC
            LIMIT $limit OFFSET $offset",
            $params
        );
    }

    /**
     * Zlicza wszystkie raporty DUR spełniające filtry.
     * Używane do paginacji na stronie /route=dur.
     */
    public function countAllReviews(array $filters = []): int
    {
        $where  = ['1=1'];
        $params = [];
        if (!empty($filters['line_id'])) {
            $where[] = 'mr.production_line_id = ?';
            $params[] = $filters['line_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'mr.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['type'])) {
            $where[] = 'mr.review_type = ?';
            $params[] = $filters['type'];
        }
        $st = $this->db->prepare(
            "SELECT COUNT(*)
             FROM maintenance_reviews mr
             WHERE " . implode(' AND ', $where)
        );
        $st->execute($params);
        return (int) $st->fetchColumn();
    }

    public function findScheduleByLineAndType(int $lineId, string $reviewType): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM maintenance_schedules
             WHERE production_line_id = ?
               AND review_type = ?
               AND is_active = 1
             ORDER BY next_due_date ASC
             LIMIT 1",
            [$lineId, $reviewType]
        );
    }

    public function updateScheduleNextDate(int $id, string $nextDate): void
    {
        $this->execute(
            "UPDATE maintenance_schedules SET next_due_date = ?, updated_at = NOW() WHERE id = ?",
            [$nextDate, $id]
        );
    }

    public function getReviewsByLine(int $lineId, int $limit = 5): array
    {
        return $this->fetchAll(
            "SELECT mr.*, pl.name AS line_name, ls.name AS subsystem_name,
                    u.name AS performer_name
             FROM maintenance_reviews mr
             JOIN production_lines pl ON pl.id = mr.production_line_id
             LEFT JOIN line_subsystems ls ON ls.id = mr.subsystem_id
             JOIN users u ON u.id = mr.performed_by
             WHERE mr.production_line_id = ?
             ORDER BY mr.review_date DESC LIMIT ?",
            [$lineId, $limit]
        );
    }

    public function getById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT mr.*, pl.name AS line_name, ls.name AS subsystem_name,
                    u.name AS performer_name, mt.name AS template_name
             FROM maintenance_reviews mr
             JOIN production_lines pl ON pl.id = mr.production_line_id
             LEFT JOIN line_subsystems ls ON ls.id = mr.subsystem_id
             JOIN users u ON u.id = mr.performed_by
             LEFT JOIN maintenance_templates mt ON mt.id = mr.template_id
             WHERE mr.id = ?",
            [$id]
        );
    }

    public function create(array $d): int
    {
        return $this->execute(
            "INSERT INTO maintenance_reviews
             (production_line_id, subsystem_id, template_id, schedule_id, performed_by,
              review_type, review_date, duration_minutes, activities, parts_used, notes,
              status, next_review_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $d['production_line_id'],
                $d['subsystem_id'] ?? null,
                $d['template_id'] ?? null,
                $d['schedule_id'] ?? null,
                $d['performed_by'],
                $d['review_type'],
                $d['review_date'],
                $d['duration_minutes'] ?? null,
                $d['activities'],
                $d['parts_used'] ?? null,
                $d['notes'] ?? null,
                $d['status'] ?? 'completed',
                $d['next_review_date'] ?? null,
            ]
        );
    }

    /**
     * Aktualizuje istniejący raport DUR.
     * Używane przez DurController::editPost().
     */
    public function update(int $id, array $d): void
    {
        $this->execute(
            "UPDATE maintenance_reviews
             SET review_date      = ?,
                 duration_minutes = ?,
                 activities       = ?,
                 parts_used       = ?,
                 notes            = ?,
                 status           = ?,
                 next_review_date = ?,
                 updated_at       = NOW()
             WHERE id = ?",
            [
                $d['review_date'],
                $d['duration_minutes'] ?? null,
                $d['activities'],
                $d['parts_used'] ?? null,
                $d['notes'] ?? null,
                $d['status'] ?? 'completed',
                $d['next_review_date'] ?? null,
                $id,
            ]
        );
    }

    public function getUpcomingSchedules(int $days = 14): array
    {
        return $this->fetchAll(
            "SELECT ms.*, pl.name AS line_name,
                    DATEDIFF(ms.next_due_date, CURDATE()) AS days_left
             FROM maintenance_schedules ms
             JOIN production_lines pl ON pl.id = ms.production_line_id
             WHERE ms.is_active = 1
               AND ms.next_due_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY ms.next_due_date ASC",
            [$days]
        );
    }

    public function getTemplates(bool $activeOnly = true): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        return $this->fetchAll("SELECT * FROM maintenance_templates $where ORDER BY name");
    }

    public function getSchedules(): array
    {
        return $this->fetchAll(
            "SELECT ms.*, pl.name AS line_name,
                DATEDIFF(ms.next_due_date, CURDATE()) AS days_left
         FROM maintenance_schedules ms
         JOIN production_lines pl ON pl.id = ms.production_line_id
         ORDER BY ms.next_due_date ASC"
        );
    }

    public function createTemplate(array $d): int
    {
        return $this->execute(
            "INSERT INTO maintenance_templates
             (name, review_type, checklist, is_active, created_by)
             VALUES (?, ?, ?, ?, ?)",
            [
                $d['name'],
                $d['review_type'],
                $d['checklist'] ?? null,
                $d['is_active'] ?? 1,
                $d['created_by'] ?? null
            ]
        );
    }

    public function updateTemplate(int $id, array $d): void
    {
        $this->execute(
            "UPDATE maintenance_templates
             SET name=?, review_type=?, checklist=?, is_active=?
             WHERE id=?",
            [
                $d['name'],
                $d['review_type'],
                $d['checklist'] ?? null,
                $d['is_active'] ?? 1,
                $id
            ]
        );
    }

    public function createSchedule(array $d): int
    {
        return $this->execute(
            "INSERT INTO maintenance_schedules
           (production_line_id, review_type, interval_days, next_due_date, is_active)
           VALUES (?, ?, ?, ?, ?)",
            [
                $d['production_line_id'],
                $d['review_type'],
                $d['interval_days'],
                $d['next_due_date'],
                $d['is_active'] ?? 1
            ]
        );
    }

    public function updateSchedule(int $id, array $d): void
    {
        $this->execute(
            "UPDATE maintenance_schedules
           SET production_line_id=?, review_type=?,
               interval_days=?, next_due_date=?, is_active=?
           WHERE id=?",
            [
                $d['production_line_id'],
                $d['review_type'],
                $d['interval_days'],
                $d['next_due_date'],
                $d['is_active'] ?? 1,
                $id
            ]
        );
    }

    public function deleteTemplate(int $id): void
    {
        $this->execute("DELETE FROM maintenance_templates WHERE id = ?", [$id]);
    }

    public function deleteSchedule(int $id): void
    {
        $this->execute("DELETE FROM maintenance_schedules WHERE id = ?", [$id]);
    }

    public function getScheduleById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT ms.*, pl.name AS line_name
         FROM maintenance_schedules ms
         JOIN production_lines pl ON pl.id = ms.production_line_id
         WHERE ms.id = ?",
            [$id]
        );
    }
}

// ────────────────────────────────────────────────────────────
