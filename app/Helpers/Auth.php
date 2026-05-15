<?php

namespace App\Helpers;

class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => false,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']       = $user['id'];
        $_SESSION['user_name']     = $user['name'];
        $_SESSION['user_login']    = $user['login'];
        $_SESSION['user_role']     = $user['role_name'];
        $_SESSION['logged_in']     = true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $p['path'],
                $p['domain'],
                $p['secure'],
                $p['httponly']
            );
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['logged_in']);
    }

    public static function user(): array
    {
        return [
            'id'       => $_SESSION['user_id']       ?? null,
            'name'     => $_SESSION['user_name']     ?? '',
            'login'    => $_SESSION['user_login']    ?? '',
            'role'     => $_SESSION['user_role']     ?? '',
        ];
    }

    public static function isAdmin(): bool
    {
        return ($_SESSION['user_role'] ?? '') === 'admin';
    }

    public static function isMechanic(): bool
    {
        return in_array($_SESSION['user_role'] ?? '', ['admin', 'mechanic'], true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            Helpers::redirect('login');
        }
    }

    public static function requireMechanic(): void
    {
        self::requireLogin();
        if (!self::isMechanic()) {
            Helpers::redirect('dashboard');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        // Sprawdź uprawnienia admin z bazy (obsługuje role niestandardowe)
        if (self::hasAdminPermission()) return;
        Helpers::flash('error', 'Brak uprawnień do tej sekcji.');
        Helpers::redirect('dashboard');
    }

    /** Zwraca true jeśli użytkownik ma uprawnienie 'admin' (z bazy lub wbudowane) */
    public static function hasAdminPermission(): bool
    {
        $role = $_SESSION['user_role'] ?? '';
        // Rola 'admin' zawsze ma dostęp
        if ($role === 'admin') return true;
        // Inne role — sprawdź w settings
        try {
            $pdo = \App\Helpers\Database::get();
            $st  = $pdo->prepare("SELECT svalue FROM settings WHERE skey=? LIMIT 1");
            $st->execute(['role_perms_' . $role]);
            $val = $st->fetchColumn();
            if ($val) {
                $perms = json_decode($val, true) ?? [];
                return !empty($perms['admin']);
            }
        } catch (\Throwable $e) {
        }
        return false;
    }

    // CSRF
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(string $token): bool
    {
        return !empty($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}
