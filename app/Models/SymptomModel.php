<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class SymptomModel extends BaseModel
{
    public function getActive(): array
    {
        return $this->fetchAll(
            "SELECT * FROM failure_symptoms WHERE is_active = 1 ORDER BY sort_order, name"
        );
    }

    public function getAll(): array
    {
        return $this->fetchAll(
            "SELECT * FROM failure_symptoms ORDER BY sort_order, name"
        );
    }

    public function create(array $d): int
    {
        return $this->execute(
            "INSERT INTO failure_symptoms (name, sort_order, is_active) VALUES (?, ?, ?)",
            [trim($d['name']), (int)($d['sort_order'] ?? 0), 1]
        );
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            "UPDATE failure_symptoms SET name = ?, sort_order = ?, is_active = ? WHERE id = ?",
            [trim($d['name']), (int)($d['sort_order'] ?? 0), (int)$d['is_active'], $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute("DELETE FROM failure_symptoms WHERE id = ?", [$id]);
    }

    public function countUsages(int $id): int
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM failures WHERE symptom_id = ?");
        $st->execute([$id]);
        return (int) $st->fetchColumn();
    }
}

// ────────────────────────────────────────────────────────────
