<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class SparePartModel extends BaseModel
{
    /** Pobiera części dla danego zgłoszenia */
    public function getByFailure(int $failureId): array
    {
        return $this->fetchAll(
            'SELECT fsp.*, spc.name AS category_name, spc.color AS category_color,
                    u.name AS added_by_name
             FROM failure_spare_parts fsp
             JOIN spare_part_categories spc ON spc.id = fsp.category_id
             LEFT JOIN users u ON u.id = fsp.added_by
             WHERE fsp.failure_id = ?
             ORDER BY fsp.created_at',
            [$failureId]
        );
    }

    /** Dodaje nową część do zgłoszenia, zwraca ID */
    public function create(array $d): int
    {
        return $this->execute(
            'INSERT INTO failure_spare_parts (failure_id, category_id, part_name, quantity, added_by)
             VALUES (?,?,?,?,?)',
            [$d['failure_id'], $d['category_id'], $d['part_name'], $d['quantity'] ?? 1, $d['added_by'] ?? null]
        );
    }

    /** Usuwa konkretną część */
    public function delete(int $id): void
    {
        $this->execute('DELETE FROM failure_spare_parts WHERE id=?', [$id]);
    }

    /**
     * Pobiera wszystkie części z filtrami dla admina.
     * @param int|null $categoryId  Filtr według kategorii (null = wszystkie)
     */
    public function getAll(?int $categoryId = null): array
    {
        $sql    = 'SELECT fsp.*, spc.name AS category_name, spc.color AS category_color,
                          f.ticket_number, u.name AS added_by_name
                   FROM failure_spare_parts fsp
                   JOIN spare_part_categories spc ON spc.id = fsp.category_id
                   JOIN failures f ON f.id = fsp.failure_id
                   LEFT JOIN users u ON u.id = fsp.added_by';
        $params = [];
        if ($categoryId !== null) {
            $sql   .= ' WHERE fsp.category_id = ?';
            $params[] = $categoryId;
        }
        $sql .= ' ORDER BY fsp.created_at DESC';
        return $this->fetchAll($sql, $params);
    }
}
