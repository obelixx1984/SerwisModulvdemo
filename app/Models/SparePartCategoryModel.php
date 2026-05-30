<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class SparePartCategoryModel extends BaseModel
{
    /** Pobiera wszystkie kategorie (opcjonalnie tylko aktywne) */
    public function getAll(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM spare_part_categories';
        if ($activeOnly) $sql .= ' WHERE is_active = 1';
        $sql .= ' ORDER BY sort_order, name';
        return $this->fetchAll($sql);
    }

    /** Tworzy nową kategorię i zwraca jej ID */
    public function create(array $d): int
    {
        return $this->execute(
            'INSERT INTO spare_part_categories (name, color, sort_order, is_active) VALUES (?,?,?,?)',
            [$d['name'], $d['color'] ?? '#6c757d', $d['sort_order'] ?? 0, $d['is_active'] ?? 1]
        );
    }

    /** Aktualizuje istniejącą kategorię */
    public function update(int $id, array $d): void
    {
        $this->execute(
            'UPDATE spare_part_categories SET name=?, color=?, sort_order=?, is_active=? WHERE id=?',
            [$d['name'], $d['color'] ?? '#6c757d', $d['sort_order'] ?? 0, $d['is_active'] ?? 1, $id]
        );
    }

    /** Usuwa kategorię (tylko jeśli nie ma przypisanych części) */
    public function delete(int $id): void
    {
        $this->execute('DELETE FROM spare_part_categories WHERE id=?', [$id]);
    }

    /** Sprawdza ile części jest przypisanych do kategorii */
    public function countUsages(int $id): int
    {
        $pdo = \App\Helpers\Database::get();
        $st  = $pdo->prepare('SELECT COUNT(*) FROM failure_spare_parts WHERE category_id=?');
        $st->execute([$id]);
        return (int)$st->fetchColumn();
    }
}

// ────────────────────────────────────────────────────────────
