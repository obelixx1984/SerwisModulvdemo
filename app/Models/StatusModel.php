<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class StatusModel extends BaseModel
{
    public function getAll(bool $activeOnly = false): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        return $this->fetchAll("SELECT * FROM failure_statuses $where ORDER BY sort_order");
    }

    public function getInitial(): ?array
    {
        return $this->fetchOne("SELECT * FROM failure_statuses WHERE is_initial = 1 AND is_active = 1 LIMIT 1");
    }

    public function getFinal(): array
    {
        return $this->fetchAll("SELECT * FROM failure_statuses WHERE is_final = 1 AND is_active = 1");
    }

    public function getObserved(): ?array
    {
        return $this->fetchOne("SELECT * FROM failure_statuses WHERE is_observed = 1 AND is_active = 1 LIMIT 1");
    }

    public function getById(int $id): ?array
    {
        return $this->fetchOne("SELECT * FROM failure_statuses WHERE id = ?", [$id]);
    }

    public function create(array $d): int
    {
        return $this->execute(
            "INSERT INTO failure_statuses (label, color, sort_order, is_initial, is_final, is_observed, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$d['label'], $d['color'] ?? '#6c757d', $d['sort_order'] ?? 99, 0, 0, 0, 1]
        );
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            "UPDATE failure_statuses SET label = ?, color = ?, sort_order = ?, is_initial = ?, is_final = ?, is_observed = ?, is_active = ? WHERE id = ?",
            [
                $d['label'],
                $d['color'],
                $d['sort_order'],
                $d['is_initial'] ?? 0,
                $d['is_final'] ?? 0,
                $d['is_observed'] ?? 0,
                $d['is_active'],
                $id
            ]
        );
    }

    public function countUsages(int $id): int
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM failures WHERE status_id = ?");
        $st->execute([$id]);
        return (int) $st->fetchColumn();
    }

    public function delete(int $id): void
    {
        $this->execute("DELETE FROM failure_statuses WHERE id = ?", [$id]);
    }
}

// ────────────────────────────────────────────────────────────
