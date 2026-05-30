<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class SettingsModel extends BaseModel
{
    public function getAll(): array
    {
        $rows = $this->fetchAll("SELECT * FROM settings ORDER BY id");
        $out  = [];
        foreach ($rows as $r) $out[$r['skey']] = $r;
        return $out;
    }

    public function get(string $key): ?string
    {
        $r = $this->fetchOne("SELECT svalue FROM settings WHERE skey = ?", [$key]);
        return $r ? $r['svalue'] : null;
    }

    public function set(string $key, string $value): void
    {
        $this->execute(
            "INSERT INTO settings (skey, svalue, label) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE svalue = ?",
            [$key, $value, $key, $value]
        );
    }
}

// ────────────────────────────────────────────────────────────
