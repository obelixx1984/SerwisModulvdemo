<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class RoleModel extends BaseModel
{
    public function getAll(): array
    {
        return $this->fetchAll("SELECT * FROM roles ORDER BY id");
    }

    public function nameExists(string $name): bool
    {
        return (bool) $this->fetchOne("SELECT id FROM roles WHERE name = ?", [$name]);
    }

    public function findByName(string $name): ?array
    {
        return $this->fetchOne("SELECT * FROM roles WHERE name = ?", [$name]);
    }

    public function deleteRole(string $name): void
    {
        // Przenieś użytkowników tej roli do roli 'operator' (id=3)
        $this->execute(
            "UPDATE users SET role_id = (SELECT id FROM roles WHERE name = 'operator' LIMIT 1)
             WHERE role_id = (SELECT id FROM roles WHERE name = ? LIMIT 1)",
            [$name]
        );
        // Usuń ustawienia uprawnień
        $this->execute("DELETE FROM settings WHERE skey = ?", ['role_perms_' . $name]);
        // Usuń rolę
        $this->execute("DELETE FROM roles WHERE name = ?", [$name]);
    }

    public function create(string $name, string $label): void
    {
        $this->execute("INSERT INTO roles (name, label) VALUES (?, ?)", [$name, $label]);
        // Domyślne uprawnienia dla nowej roli (tylko zgłaszanie)
        $this->execute(
            "INSERT IGNORE INTO settings (skey, svalue, label) VALUES (?, ?, ?)",
            ['role_perms_' . $name, json_encode(['report' => 1]), 'Uprawnienia roli: ' . $label]
        );
    }

    public function getAllPermissions(): array
    {
        $rows   = $this->fetchAll("SELECT skey, svalue FROM settings WHERE skey LIKE 'role_perms_%'");
        $result = [];
        foreach ($rows as $r) {
            $roleName = substr($r['skey'], strlen('role_perms_'));
            $result[$roleName] = json_decode($r['svalue'], true) ?? [];
        }
        // Domyślne uprawnienia dla wbudowanych ról jeśli brak w bazie
        if (!isset($result['admin']))    $result['admin']    = ['report' => 1, 'dashboard' => 1, 'failures' => 1, 'dur' => 1, 'statuses' => 1, 'admin' => 1];
        if (!isset($result['mechanic'])) $result['mechanic'] = ['dashboard' => 1, 'failures' => 1, 'dur' => 1, 'statuses' => 1];
        if (!isset($result['operator'])) $result['operator'] = ['report' => 1, 'dur' => 1];
        return $result;
    }

    public function updatePermissions(string $name, string $label, array $perms): void
    {
        // Aktualizuj etykietę roli
        $this->execute("UPDATE roles SET label = ? WHERE name = ?", [$label, $name]);
        // Zapisz uprawnienia w settings
        $this->execute(
            "INSERT INTO settings (skey, svalue, label) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)",
            ['role_perms_' . $name, json_encode($perms), 'Uprawnienia roli: ' . $label]
        );
    }
}
