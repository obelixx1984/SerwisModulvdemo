<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Database;

class UserModel extends BaseModel
{
    public function findByNickname(string $nickname): ?array
    {
        return $this->fetchOne(
            "SELECT u.*, r.name AS role_name
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.login = ? AND u.is_active = 1",
            [strtolower($nickname)]
        );
    }

    public function changePassword(int $userId, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->execute(
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
            [$hash, $userId]
        );
    }

    public function getAll(): array
    {
        return $this->fetchAll(
            "SELECT u.*, r.name AS role_name, r.label AS role_label
             FROM users u JOIN roles r ON r.id = u.role_id
             ORDER BY r.id, u.name"
        );
    }

    public function create(array $d): int
    {
        return $this->execute(
            "INSERT INTO users (role_id, name, login, email, password_hash, is_active)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $d['role_id'],
                $d['name'],
                strtolower($d['nickname']),
                $d['email'],
                password_hash($d['password'], PASSWORD_BCRYPT, ['cost' => 10]),
                $d['is_active']
            ]
        );
    }

    public function update(int $id, array $d): void
    {
        $sets   = ['name = ?', 'login = ?', 'email = ?', 'role_id = ?', 'is_active = ?'];
        $params = [$d['name'], strtolower($d['nickname']), $d['email'], $d['role_id'], $d['is_active']];
        if (!empty($d['password'])) {
            $sets[]   = 'password_hash = ?';
            $params[] = password_hash($d['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }
        $params[] = $id;
        $this->execute("UPDATE users SET " . implode(', ', $sets) . " WHERE id = ?", $params);
    }

    public function updateLastLogin(int $id): void
    {
        $this->execute("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$id]);
    }

    public function nicknameExists(string $nickname, int $excludeId = 0): bool
    {
        return (bool)$this->fetchOne(
            "SELECT id FROM users WHERE login = ? AND id != ?",
            [strtolower($nickname), $excludeId]
        );
    }

    public function delete(int $id): void
    {
        // Znajdź innego aktywnego użytkownika do przejęcia raportów DUR
        // (pierwszego admina innego niż usuwany, lub pierwszego dowolnego innego)
        $fallback = $this->fetchOne(
            "SELECT id FROM users WHERE id != ? AND is_active = 1 AND role_id = 1 LIMIT 1",
            [$id]
        );
        if (!$fallback) {
            $fallback = $this->fetchOne(
                "SELECT id FROM users WHERE id != ? AND is_active = 1 LIMIT 1",
                [$id]
            );
        }
        $fallbackId = $fallback ? $fallback['id'] : null;

        // Przenieś raporty DUR na innego użytkownika (lub zostaw jeśli brak innych)
        if ($fallbackId) {
            $this->execute(
                "UPDATE maintenance_reviews SET performed_by = ? WHERE performed_by = ?",
                [$fallbackId, $id]
            );
        }

        // Wyzeruj user_id w komentarzach i historii (FK ustawione ON DELETE SET NULL)
        $this->execute("UPDATE failure_comments SET user_id = NULL WHERE user_id = ?", [$id]);
        $this->execute("UPDATE failure_history  SET user_id = NULL WHERE user_id = ?", [$id]);

        // Usuń użytkownika
        $this->execute("DELETE FROM users WHERE id = ?", [$id]);
    }

    public function getMechanics(): array
    {
        return $this->fetchAll(
            "SELECT u.id, u.name, u.login
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE r.name = 'mechanic' AND u.is_active = 1
             ORDER BY u.name"
        );
    }

    public function getById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT u.*, r.name AS role_name, r.label AS role_label
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = ?",
            [$id]
        );
    }
}

// ────────────────────────────────────────────────────────────
