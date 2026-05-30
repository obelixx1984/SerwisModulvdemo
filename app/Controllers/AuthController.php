<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Helpers;
use App\Models\{
    UserModel,
    ProductionLineModel,
    CategoryModel,
    DictionaryModel,
    StatusModel,
    FailureModel,
    AssignmentModel,
    MaintenanceModel,
    ScheduleNoteModel,
    SparePartCategoryModel,
    SparePartModel,
    SettingsModel,
    SymptomModel
};

class AuthController
{
    public function loginForm(): void
    {
        if (Auth::check()) Helpers::redirect('dashboard');
        require BASE_PATH . '/templates/auth/login.php';
    }

    /** POPRAWKA 5: logowanie przez nickname zamiast email */
    public function loginPost(): void
    {
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa. Spróbuj ponownie.');
            Helpers::redirect('login');
        }
        $nickname = strtolower(trim($_POST['nickname'] ?? ''));
        $pass     = $_POST['password'] ?? '';
        if (!$nickname || !$pass) {
            Helpers::flash('error', 'Podaj login i hasło.');
            Helpers::redirect('login');
        }
        $model = new UserModel();
        $user  = $model->findByNickname($nickname);
        if (!$user || !password_verify($pass, $user['password_hash'])) {
            Helpers::flash('error', 'Nieprawidłowy login lub hasło.');
            Helpers::redirect('login');
        }
        Auth::login($user);
        $model->updateLastLogin($user['id']);

        // ── Wyznacz cel przekierowania na podstawie uprawnień ──
        $role  = $user['role_name'];
        $perms = [];

        // Admin zawsze ma dashboard
        if ($role === 'admin') {
            Helpers::redirect('dashboard');
            return;
        }

        // Wczytaj uprawnienia roli z bazy
        try {
            $pdo = \App\Helpers\Database::get();
            $st  = $pdo->prepare("SELECT svalue FROM settings WHERE skey = ? LIMIT 1");
            $st->execute(['role_perms_' . $role]);
            $val = $st->fetchColumn();
            if ($val) $perms = json_decode($val, true) ?? [];
        } catch (\Throwable $e) {
        }

        // Domyślne uprawnienia gdy brak w bazie
        if (empty($perms)) {
            if ($role === 'mechanic') {
                $perms = ['dashboard' => 1, 'failures' => 1, 'dur' => 1, 'statuses' => 1];
            } else {
                $perms = ['report' => 1, 'dur' => 1];
            }
        }

        // Przekieruj do pierwszej dostępnej sekcji (priorytet jak niżej)
        if (!empty($perms['dashboard'])) {
            Helpers::redirect('dashboard');
            return;
        }
        if (!empty($perms['failures'])) {
            Helpers::redirect('failures');
            return;
        }
        if (!empty($perms['report'])) {   // ← PRZENIESIONE przed dur
            Helpers::redirect('report');
            return;
        }
        if (!empty($perms['dur'])) {      // ← PRZENIESIONE za report
            Helpers::redirect('dur');
            return;
        }
        Helpers::redirect('line_history'); // zawsze dostępna
    }

    public function logout(): void
    {
        Auth::logout();
        Helpers::redirect('login');
    }
}

// ────────────────────────────────────────────────────────────
