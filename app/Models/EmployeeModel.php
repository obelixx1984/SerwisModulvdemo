<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class EmployeeModel extends BaseModel
{
    public function getAll(bool $activeOnly = false): array
    {
        $where = $activeOnly ? 'WHERE is_active = 1' : '';
        return $this->fetchAll("SELECT * FROM employees $where ORDER BY name");
    }

    public function create(array $d): int
    {
        return $this->execute(
            "INSERT INTO employees (acronym, name, position, is_active) VALUES (?, ?, ?, ?)",
            [strtoupper($d['acronym']), $d['name'], $d['position'] ?? null, $d['is_active'] ?? 1]
        );
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            "UPDATE employees SET acronym = ?, name = ?, position = ?, is_active = ? WHERE id = ?",
            [strtoupper($d['acronym']), $d['name'], $d['position'] ?? null, $d['is_active'], $id]
        );
    }

    public function findByAcronym(string $ak): ?array
    {
        return $this->fetchOne("SELECT * FROM employees WHERE acronym = ?", [strtoupper($ak)]);
    }

    public function delete(int $id): void
    {
        $this->execute("DELETE FROM employees WHERE id = ?", [$id]);
    }
}

// ────────────────────────────────────────────────────────────
