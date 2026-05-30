<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class ProductionLineModel extends BaseModel
{
    public function getAll(bool $activeOnly = false): array
    {
        $where = $activeOnly ? 'WHERE pl.is_active = 1' : '';
        return $this->fetchAll(
            "SELECT pl.*,
                    GROUP_CONCAT(ls.name ORDER BY ls.sort_order SEPARATOR '|||') AS subsystems_str,
                    GROUP_CONCAT(ls.id   ORDER BY ls.sort_order SEPARATOR ',')   AS subsystem_ids
             FROM production_lines pl
             LEFT JOIN line_subsystems ls ON ls.production_line_id = pl.id AND ls.is_active = 1
             $where
             GROUP BY pl.id
             ORDER BY pl.name"
        );
    }

    public function getById(int $id): ?array
    {
        $line = $this->fetchOne("SELECT * FROM production_lines WHERE id = ?", [$id]);
        if (!$line) return null;
        $line['subsystems'] = $this->fetchAll(
            "SELECT * FROM line_subsystems WHERE production_line_id = ? AND is_active = 1 ORDER BY sort_order",
            [$id]
        );
        return $line;
    }

    public function create(array $d): int
    {
        return $this->execute(
            "INSERT INTO production_lines (name, prefix, description, is_active) VALUES (?, ?, ?, ?)",
            [$d['name'], strtoupper($d['prefix']), $d['description'] ?? null, $d['is_active'] ?? 1]
        );
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            "UPDATE production_lines SET name = ?, prefix = ?, description = ?, is_active = ? WHERE id = ?",
            [$d['name'], strtoupper($d['prefix']), $d['description'] ?? null, $d['is_active'], $id]
        );
    }

    public function addSubsystem(int $lineId, string $name, int $order = 0): int
    {
        return $this->execute(
            "INSERT INTO line_subsystems (production_line_id, name, sort_order) VALUES (?, ?, ?)",
            [$lineId, $name, $order]
        );
    }

    public function deleteSubsystemsForLine(int $lineId): void
    {
        $this->execute("DELETE FROM line_subsystems WHERE production_line_id = ?", [$lineId]);
    }

    public function prefixExists(string $prefix, int $excludeId = 0): bool
    {
        $row = $this->fetchOne(
            "SELECT id FROM production_lines WHERE prefix = ? AND id != ?",
            [strtoupper($prefix), $excludeId]
        );
        return (bool) $row;
    }

    public function countFailures(int $id): int
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM failures WHERE production_line_id = ?");
        $st->execute([$id]);
        return (int) $st->fetchColumn();
    }

    public function delete(int $id): void
    {
        $this->execute("DELETE FROM production_lines WHERE id = ?", [$id]);
    }
}

// ────────────────────────────────────────────────────────────
