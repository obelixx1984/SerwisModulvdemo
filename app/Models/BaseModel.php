<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

abstract class BaseModel
{
    protected \PDO $db;
    protected string $table;

    public function __construct()
    {
        $this->db = Database::get();
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $st = $this->db->prepare($sql);
        $st->execute($params);
        $r = $st->fetch();
        return $r ?: null;
    }

    protected function execute(string $sql, array $params = []): int
    {
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return (int) $this->db->lastInsertId();
    }
}

// ────────────────────────────────────────────────────────────
