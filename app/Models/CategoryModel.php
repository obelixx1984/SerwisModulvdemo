<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class CategoryModel extends BaseModel
{
    public function getAll(bool $activeOnly = false): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        return $this->fetchAll("SELECT * FROM failure_categories $where ORDER BY sort_order, id");
    }

    public function create(array $d): int
    {
        $name = strtolower(preg_replace('/\s+/', '_', trim($d['label']))) . '_' . time();
        return $this->execute(
            "INSERT INTO failure_categories (name, label, color, sort_order, is_active) VALUES (?, ?, ?, ?, ?)",
            [$name, $d['label'], $d['color'] ?? '#6c757d', $d['sort_order'] ?? 0, 1]
        );
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            "UPDATE failure_categories SET label = ?, color = ?, sort_order = ?, is_active = ? WHERE id = ?",
            [$d['label'], $d['color'], $d['sort_order'] ?? 0, $d['is_active'], $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute("DELETE FROM failure_categories WHERE id = ?", [$id]);
    }
}

// ────────────────────────────────────────────────────────────
