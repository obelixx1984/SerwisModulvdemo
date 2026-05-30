<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class DictionaryModel extends BaseModel
{
    public function getActive(?int $categoryId = null): array
    {
        $where  = 'WHERE fd.is_active = 1';
        $params = [];
        if ($categoryId) {
            $where .= ' AND fd.category_id = ?';
            $params[] = $categoryId;
        }
        return $this->fetchAll(
            "SELECT fd.*, fc.label AS cat_label, fc.color AS cat_color
             FROM failure_dictionary fd
             JOIN failure_categories fc ON fc.id = fd.category_id
             $where
             ORDER BY fc.sort_order, fd.title",
            $params
        );
    }

    public function getAll(): array
    {
        return $this->fetchAll(
            "SELECT fd.*, fc.label AS cat_label, fc.color AS cat_color
             FROM failure_dictionary fd
             JOIN failure_categories fc ON fc.id = fd.category_id
             ORDER BY fc.sort_order, fd.title"
        );
    }

    public function create(array $d): int
    {
        return $this->execute(
            "INSERT INTO failure_dictionary (category_id, title, description, is_active) VALUES (?, ?, ?, ?)",
            [$d['category_id'], $d['title'], $d['description'] ?? null, 1]
        );
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            "UPDATE failure_dictionary SET title = ?, category_id = ?, description = ?, is_active = ? WHERE id = ?",
            [$d['title'], $d['category_id'], $d['description'] ?? null, $d['is_active'] ?? 1, $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute("DELETE FROM failure_dictionary WHERE id = ?", [$id]);
    }

    public function countUsages(int $id): int
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM failures WHERE dictionary_item_id = ?");
        $st->execute([$id]);
        return (int) $st->fetchColumn();
    }
}

// ────────────────────────────────────────────────────────────
// Zmiana 1: model objawów awarii — wybieranych przez zgłaszającego
// ────────────────────────────────────────────────────────────
