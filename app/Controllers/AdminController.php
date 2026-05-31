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

class AdminController
{
    public function users(): void
    {
        Auth::requireAdmin();
        $users     = (new UserModel())->getAll();
        $roleModel = new \App\Models\RoleModel();
        $roles     = $roleModel->getAll();
        $rolePerms = $roleModel->getAllPermissions();
        require BASE_PATH . '/templates/admin/users.php';
    }

    public function roleSave(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_users');
        }
        $name  = trim($_POST['role_name'] ?? '');
        $label = trim($_POST['role_label'] ?? '');
        if (!$name) {
            Helpers::flash('error', 'Brak nazwy roli.');
            Helpers::redirect('admin_users');
        }

        $perms = [
            'report'    => !empty($_POST['perm_report'])    ? 1 : 0,
            'dashboard' => !empty($_POST['perm_dashboard']) ? 1 : 0,
            'failures'  => !empty($_POST['perm_failures'])  ? 1 : 0,
            'dur'       => !empty($_POST['perm_dur'])        ? 1 : 0,
            'statuses'  => !empty($_POST['perm_statuses'])  ? 1 : 0,
            'admin'     => !empty($_POST['perm_admin'])      ? 1 : 0,
        ];

        $rm = new \App\Models\RoleModel();
        $rm->updatePermissions($name, $label, $perms);
        Helpers::flash('success', 'Uprawnienia roli "' . Helpers::e($name) . '" zapisane.');
        Helpers::redirect('admin_users');
    }

    public function roleDelete(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_users');
        }
        $name = trim($_POST['role_name'] ?? '');
        // Chroń role wbudowane
        if (in_array($name, ['admin', 'mechanic', 'operator'], true)) {
            Helpers::flash('error', 'Nie można usunąć roli wbudowanej: "' . Helpers::e($name) . '".');
            Helpers::redirect('admin_users');
        }
        if (!$name) {
            Helpers::redirect('admin_users');
        }
        $rm = new \App\Models\RoleModel();
        $rm->deleteRole($name);
        Helpers::flash('success', 'Rola "' . Helpers::e($name) . '" usunięta. Użytkownicy przeniesieni do roli "operator".');
        Helpers::redirect('admin_users');
    }

    public function roleAdd(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_users');
        }
        $name  = strtolower(preg_replace('/[^a-z_]/', '', trim($_POST['new_role_name'] ?? '')));
        $label = trim($_POST['new_role_label'] ?? '');
        if (!$name || !$label) {
            Helpers::flash('error', 'Podaj nazwę techniczną i etykietę roli.');
            Helpers::redirect('admin_users');
        }
        $rm = new \App\Models\RoleModel();
        if ($rm->nameExists($name)) {
            Helpers::flash('error', 'Rola "' . Helpers::e($name) . '" już istnieje.');
            Helpers::redirect('admin_users');
        }
        $rm->create($name, $label);
        Helpers::flash('success', 'Rola "' . Helpers::e($label) . '" dodana.');
        Helpers::redirect('admin_users');
    }

    public function userSave(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_users');
        }
        $id   = (int)($_POST['user_id'] ?? 0);
        $nick = strtolower(trim($_POST['nickname'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'mechanic';
        $pass = $_POST['password'] ?? '';
        $active = (int)($_POST['is_active'] ?? 1);

        // Jeśli e-mail pusty — wygeneruj unikalny placeholder żeby nie złamać UNIQUE KEY
        if ($email === '') {
            $email = $nick . '@serwis.local';
        }

        if (!$nick || !$name) {
            Helpers::flash('error', 'Podaj login i imię/nazwisko.');
            Helpers::redirect('admin_users');
        }
        $um = new UserModel();
        if ($um->nicknameExists($nick, $id)) {
            Helpers::flash('error', 'Login "' . Helpers::e($nick) . '" jest już zajęty.');
            Helpers::redirect('admin_users');
        }

        // Znajdź role_id dynamicznie z bazy — obsługuje niestandardowe role jak 'kierownik'
        $roleRow = (new \App\Models\RoleModel())->findByName($role);
        if (!$roleRow) {
            Helpers::flash('error', 'Nieznana rola: "' . Helpers::e($role) . '".');
            Helpers::redirect('admin_users');
        }
        $roleId = $roleRow['id'];

        $data = [
            'name' => $name,
            'nickname' => $nick,
            'email' => $email,
            'role_id' => $roleId,
            'is_active' => $active,
            'password' => $pass
        ];
        if ($id > 0) {
            $um->update($id, $data);
            Helpers::flash('success', 'Użytkownik zaktualizowany.');
        } else {
            if (!$pass) {
                Helpers::flash('error', 'Podaj hasło dla nowego użytkownika.');
                Helpers::redirect('admin_users');
            }
            $um->create($data);
            Helpers::flash('success', 'Użytkownik "' . Helpers::e($nick) . '" dodany.');
        }
        Helpers::redirect('admin_users');
    }

    /** POPRAWKA 1: Linie z prefixem + podzespoły */
    public function lines(): void
    {
        Auth::requireAdmin();
        $lines = (new ProductionLineModel())->getAll();
        require BASE_PATH . '/templates/admin/lines.php';
    }

    public function lineSave(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_lines');
        }
        $id          = (int)($_POST['line_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $prefix      = strtoupper(trim($_POST['prefix'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $active      = (int)($_POST['is_active'] ?? 1);
        $subsystems  = array_filter(array_map('trim', explode("\n", $_POST['subsystems'] ?? '')));

        if (!$name || !$prefix) {
            Helpers::flash('error', 'Podaj nazwę linii i prefix.');
            Helpers::redirect('admin_lines');
        }

        $lm = new ProductionLineModel();
        if ($lm->prefixExists($prefix, $id)) {
            Helpers::flash('error', 'Prefix "' . Helpers::e($prefix) . '" jest już zajęty przez inną linię.');
            Helpers::redirect('admin_lines');
        }

        $data = ['name' => $name, 'prefix' => $prefix, 'description' => $description, 'is_active' => $active];
        if ($id > 0) {
            $lm->update($id, $data);
            $lm->deleteSubsystemsForLine($id);
        } else {
            $id = $lm->create($data);
        }
        foreach (array_values($subsystems) as $i => $sub) {
            $lm->addSubsystem($id, $sub, $i + 1);
        }

        Helpers::flash('success', 'Linia "' . Helpers::e($name) . '" zapisana. Format numeru: 0001/' . Helpers::e($prefix) . '/' . date('Y'));
        Helpers::redirect('admin_lines');
    }

    /** POPRAWKA 6: Statusy — dodawanie nowych */
    public function statuses(): void
    {
        Auth::requireAdmin();
        $statuses = (new StatusModel())->getAll();
        require BASE_PATH . '/templates/admin/statuses.php';
    }

    public function statusSave(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_statuses');
        }
        $id      = (int)($_POST['status_id'] ?? 0);
        $label   = trim($_POST['label'] ?? '');
        $color   = trim($_POST['color'] ?? '#6c757d');
        $order   = (int)($_POST['sort_order'] ?? 99);
        $active  = (int)($_POST['is_active'] ?? 1);
        $initial = (int)($_POST['is_initial'] ?? 0);
        $final    = (int)($_POST['is_final']    ?? 0);
        $observed = (int)($_POST['is_observed'] ?? 0);

        if (!$label) {
            Helpers::flash('error', 'Podaj etykietę statusu.');
            Helpers::redirect('admin_statuses');
        }

        $sm   = new StatusModel();
        $data = [
            'label'       => $label,
            'color'       => $color,
            'sort_order'  => $order,
            'is_active'   => $active,
            'is_initial'  => $initial,
            'is_final'    => $final,
            'is_observed' => $observed,
        ];
        if ($id > 0) {
            $sm->update($id, $data);
            Helpers::flash('success', 'Status zaktualizowany.');
        } else {
            $sm->create($data);
            Helpers::flash('success', 'Status "' . Helpers::e($label) . '" dodany.');
        }
        Helpers::redirect('admin_statuses');
    }

    /** POPRAWKA 11: Kategorie awarii i słownik */
    public function dictionary(): void
    {
        Auth::requireAdmin();
        $categories = (new CategoryModel())->getAll();
        $dictionary = (new DictionaryModel())->getAll();
        require BASE_PATH . '/templates/admin/dictionary.php';
    }

    public function categorySave(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_dictionary');
        }

        // Obsługa usunięcia kategorii
        if (!empty($_POST['delete_cat'])) {
            $delId = (int)($_POST['cat_id'] ?? 0);
            if ($delId > 0) {
                $cm = new CategoryModel();
                $cm->delete($delId);
                Helpers::flash('success', 'Kategoria usunięta.');
            }
            Helpers::redirect('admin_dictionary');
        }

        $id     = (int)($_POST['cat_id'] ?? 0);
        $label  = trim($_POST['label'] ?? '');
        $color  = trim($_POST['color'] ?? '#6c757d');
        $order  = (int)($_POST['sort_order'] ?? 0);
        $active = (int)($_POST['is_active'] ?? 1);

        if (!$label) {
            Helpers::flash('error', 'Podaj nazwę kategorii.');
            Helpers::redirect('admin_dictionary');
        }

        $cm   = new CategoryModel();
        $data = ['label' => $label, 'color' => $color, 'sort_order' => $order, 'is_active' => $active];
        if ($id > 0) {
            $cm->update($id, $data);
            Helpers::flash('success', 'Kategoria zaktualizowana.');
        } else {
            $cm->create($data);
            Helpers::flash('success', 'Kategoria "' . Helpers::e($label) . '" dodana.');
        }
        Helpers::redirect('admin_dictionary');
    }

    public function dictItemDelete(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_dictionary');
        }
        $id = (int)($_POST['dict_id'] ?? 0);
        if ($id > 0) {
            // Błąd 3: sprawdź czy usterka jest używana w zgłoszeniach
            $usedCount = (new DictionaryModel())->countUsages($id);
            if ($usedCount > 0) {
                Helpers::flash('error', 'Nie można usunąć tej usterki — jest używana w <strong>' . $usedCount . ' zgłoszeniu/zgłoszeniach</strong>. Możesz ją tylko dezaktywować (edycja → Aktywna: Nie).');
                Helpers::redirect('admin_dictionary');
            }
            (new DictionaryModel())->delete($id);
            Helpers::flash('success', 'Pozycja słownika usunięta.');
        }
        Helpers::redirect('admin_dictionary');
    }

    public function dictItemSave(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_dictionary');
        }
        $dictId = (int)($_POST['dict_id'] ?? 0);
        $title  = trim($_POST['title'] ?? '');
        $catId  = (int)($_POST['category_id'] ?? 0);
        $desc   = trim($_POST['description'] ?? '');
        $active = (int)($_POST['dict_active'] ?? 1);
        if (!$title || !$catId) {
            Helpers::flash('error', 'Podaj tytuł usterki i kategorię.');
            Helpers::redirect('admin_dictionary');
        }
        $dm = new DictionaryModel();
        if ($dictId > 0) {
            $dm->update($dictId, ['title' => $title, 'category_id' => $catId, 'description' => $desc, 'is_active' => $active]);
            Helpers::flash('success', 'Pozycja "' . Helpers::e($title) . '" zaktualizowana.');
        } else {
            $dm->create(['title' => $title, 'category_id' => $catId, 'description' => $desc]);
            Helpers::flash('success', 'Pozycja "' . Helpers::e($title) . '" dodana do słownika.');
        }
        Helpers::redirect('admin_dictionary');
    }

    // Zmiana 1: zarządzanie objawami awarii
    public function symptoms(): void
    {
        Auth::requireAdmin();
        $db = \App\Helpers\Database::get();
        $st = $db->query(
            "SELECT s.*, COUNT(f.id) AS usage_count
             FROM failure_symptoms s
             LEFT JOIN failures f ON f.symptom_id = s.id
             GROUP BY s.id
             ORDER BY s.sort_order, s.name"
        );
        $symptoms = $st->fetchAll();
        require BASE_PATH . '/templates/admin/symptoms.php';
    }

    public function symptomSave(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_symptoms');
        }
        $id     = (int)($_POST['symptom_id'] ?? 0);
        $name   = trim($_POST['name'] ?? '');
        $order  = (int)($_POST['sort_order'] ?? 0);
        $active = (int)($_POST['is_active'] ?? 1);

        if (!$name) {
            Helpers::flash('error', 'Podaj nazwę objawu.');
            Helpers::redirect('admin_symptoms');
        }

        $sm = new SymptomModel();
        if ($id > 0) {
            $sm->update($id, ['name' => $name, 'sort_order' => $order, 'is_active' => $active]);
            Helpers::flash('success', 'Objaw "' . Helpers::e($name) . '" zaktualizowany.');
        } else {
            $sm->create(['name' => $name, 'sort_order' => $order]);
            Helpers::flash('success', 'Objaw "' . Helpers::e($name) . '" dodany.');
        }
        Helpers::redirect('admin_symptoms');
    }

    public function symptomDelete(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_symptoms');
        }
        $id = (int)($_POST['symptom_id'] ?? 0);
        if ($id > 0) {
            $sm    = new SymptomModel();
            $usage = $sm->countUsages($id);
            if ($usage > 0) {
                Helpers::flash('error', 'Nie można usunąć objawu — jest używany w ' . $usage . ' zgłoszeniu/zgłoszeniach. Możesz go dezaktywować.');
                Helpers::redirect('admin_symptoms');
            }
            $sm->delete($id);
            Helpers::flash('success', 'Objaw usunięty.');
        }
        Helpers::redirect('admin_symptoms');
    }

    public function durTemplates(): void
    {
        Auth::requireAdmin();
        $templates   = (new MaintenanceModel())->getTemplates(false);
        $sm          = new SettingsModel();

        // Aktywne typy
        $activeTypes = ['weekly', 'monthly', 'quarterly', 'biannual', 'annual', 'ad_hoc', 'periodic'];
        $saved = $sm->get('dur_active_review_types');
        if ($saved) {
            $decoded = json_decode($saved, true);
            if (is_array($decoded) && $decoded) $activeTypes = $decoded;
        }

        // Niestandardowe etykiety typów     ← NOWE
        $typeLabels = [];
        $savedLabels = $sm->get('dur_type_labels');
        if ($savedLabels) {
            $decoded = json_decode($savedLabels, true);
            if (is_array($decoded)) $typeLabels = $decoded;
        }

        require BASE_PATH . '/templates/admin/dur_templates.php';
    }

    public function tmplSave(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_dur_tmpl');
        }

        // ── NOWE: zapis aktywnych typów i etykiet ────────────
        if (isset($_POST['save_types'])) {
            $sm          = new SettingsModel();
            $allTypeKeys = ['weekly', 'monthly', 'quarterly', 'biannual', 'annual', 'ad_hoc', 'periodic'];

            // Aktywne typy (checkboxy)
            $activeTypes = [];
            foreach ($allTypeKeys as $key) {
                if (!empty($_POST['type_active'][$key])) {
                    $activeTypes[] = $key;
                }
            }
            if (empty($activeTypes)) $activeTypes = ['monthly'];
            $sm->set('dur_active_review_types', json_encode($activeTypes));

            // Etykiety typów (pola tekstowe)
            $typeLabels = [];
            foreach ($allTypeKeys as $key) {
                $label = trim($_POST['type_label'][$key] ?? '');
                if ($label !== '') {
                    $typeLabels[$key] = $label;
                }
            }
            $sm->set('dur_type_labels', json_encode($typeLabels));

            Helpers::flash('success', 'Typy przeglądów DUR zaktualizowane.');
            Helpers::redirect('admin_dur_tmpl');
            return;
        }

        $id          = (int)($_POST['tmpl_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $reviewType  = 'monthly';
        $checklist   = trim($_POST['checklist'] ?? '');
        $isActive    = (int)($_POST['is_active'] ?? 1);

        if (!$name) {
            Helpers::flash('error', 'Podaj nazwę szablonu.');
            Helpers::redirect('admin_dur_tmpl');
        }
        $mm   = new MaintenanceModel();
        $user = Auth::user();
        $data = [
            'name' => $name,
            'review_type' => $reviewType,
            'checklist' => $checklist,
            'is_active' => $isActive,
            'created_by' => $user['id']
        ];
        if ($id > 0) {
            $mm->updateTemplate($id, $data);
            Helpers::flash('success', 'Szablon "' . Helpers::e($name) . '" zaktualizowany.');
        } else {
            $mm->createTemplate($data);
            Helpers::flash('success', 'Szablon "' . Helpers::e($name) . '" dodany.');
        }
        Helpers::redirect('admin_dur_tmpl');
    }

    public function durSchedules(): void
    {
        Auth::requireAdmin();
        $schedules = (new MaintenanceModel())->getSchedules();
        $lines     = (new ProductionLineModel())->getAll(true);
        $templates = (new MaintenanceModel())->getTemplates();
        $sm        = new SettingsModel();

        // Problem 1: aktywne typy przeglądów
        $activeTypes = ['weekly', 'monthly', 'quarterly', 'biannual', 'annual', 'ad_hoc'];
        $saved = $sm->get('dur_active_review_types');
        if ($saved) {
            $decoded = json_decode($saved, true);
            if (is_array($decoded) && $decoded) $activeTypes = $decoded;
        }

        // Problem 2: konfiguracja statusów DUR
        $durStatusConfig = [
            'completed'   => ['label' => 'Zakończony', 'color' => '#16a34a'],
            'partial'     => ['label' => 'Częściowy',  'color' => '#d97706'],
            'interrupted' => ['label' => 'Przerwany',  'color' => '#dc2626'],
        ];
        $savedStatuses = $sm->get('dur_review_statuses');
        if ($savedStatuses) {
            $decodedStatuses = json_decode($savedStatuses, true);
            if (is_array($decodedStatuses)) {
                $durStatusConfig = array_merge($durStatusConfig, $decodedStatuses);
            }
        }

        $typeLabels = [];
        $savedLabels = $sm->get('dur_type_labels');
        if ($savedLabels) {
            $decoded = json_decode($savedLabels, true);
            if (is_array($decoded)) $typeLabels = $decoded;
        }

        require BASE_PATH . '/templates/admin/dur_schedules.php';
    }

    public function schedSave(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_dur_sched');
        }
        $id         = (int)($_POST['sched_id'] ?? 0);
        $lineId     = (int)($_POST['production_line_id'] ?? 0);
        $reviewType = $_POST['review_type'] ?? 'monthly';
        $days       = max(1, (int)($_POST['interval_days'] ?? 30));
        $nextDate   = trim($_POST['next_due_date'] ?? '');
        $isActive   = (int)($_POST['is_active'] ?? 1);

        if (!$lineId || !$nextDate) {
            Helpers::flash('error', 'Wybierz linię i podaj datę następnego przeglądu.');
            Helpers::redirect('admin_dur_sched');
        }
        $mm   = new MaintenanceModel();
        $data = [
            'production_line_id' => $lineId,
            'review_type' => $reviewType,
            'interval_days' => $days,
            'next_due_date' => $nextDate,
            'is_active' => $isActive
        ];
        if ($id > 0) {
            $mm->updateSchedule($id, $data);
            Helpers::flash('success', 'Harmonogram zaktualizowany.');
        } else {
            $mm->createSchedule($data);
            Helpers::flash('success', 'Pozycja harmonogramu dodana.');
        }
        Helpers::redirect('admin_dur_sched');
    }

    public function settings(): void
    {
        Auth::requireAdmin();
        $settings = (new SettingsModel())->getAll();
        require BASE_PATH . '/templates/admin/settings.php';
    }

    public function settingsSave(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_settings');
        }
        $sm = new SettingsModel();
        $sm->set('app_name',         trim($_POST['app_name'] ?? 'Moduł Serwis'));
        $sm->set('app_version',      trim($_POST['app_version'] ?? '0.1-dev'));
        $sm->set('company_name',     trim($_POST['company_name'] ?? ''));
        $sm->set('dur_warning_days', (string)max(1, (int)($_POST['dur_warning_days'] ?? 7)));
        $sm->set('records_per_page', (string)max(5, (int)($_POST['records_per_page'] ?? 25)));
        $idleMinutes = max(0, (int)($_POST['session_idle_timeout'] ?? 5));
        $sm->set('session_idle_timeout', (string)$idleMinutes);
        $obsHours = max(1, min(168, (int)($_POST['observation_window_hours'] ?? 8)));
        $sm->set('observation_window_hours', (string)$obsHours);
        Helpers::flash('success', 'Ustawienia zapisane.');
        Helpers::redirect('admin_settings');
    }

    public function deleteUser(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd CSRF.');
            Helpers::redirect('admin_users');
        }
        $id = (int)($_POST['user_id'] ?? 0);
        $me = Auth::user();
        if ($id === (int)($me['id'] ?? 0)) {
            Helpers::flash('error', 'Nie możesz usunąć własnego konta.');
            Helpers::redirect('admin_users');
        }
        if ($id > 0) {
            (new UserModel())->delete($id);
            Helpers::flash('success', 'Użytkownik usunięty.');
        }
        Helpers::redirect('admin_users');
    }

    public function deleteLine(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd CSRF.');
            Helpers::redirect('admin_lines');
        }
        $id = (int)($_POST['line_id'] ?? 0);
        if ($id > 0) {
            $used = (new ProductionLineModel())->countFailures($id);
            if ($used > 0) {
                Helpers::flash('error', 'Nie można usunąć linii — ma przypisanych ' . $used . ' zgłoszeń.');
                Helpers::redirect('admin_lines');
            }
            (new ProductionLineModel())->delete($id);
            Helpers::flash('success', 'Linia usunięta.');
        }
        Helpers::redirect('admin_lines');
    }

    public function deleteStatus(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd CSRF.');
            Helpers::redirect('admin_statuses');
        }
        $id = (int)($_POST['status_id'] ?? 0);
        if ($id > 0) {
            $sm = new StatusModel();
            $s  = $sm->getById($id);
            if ($s && ($s['is_initial'] || $s['is_final'])) {
                Helpers::flash('error', 'Nie można usunąć statusu startowego ani końcowego.');
                Helpers::redirect('admin_statuses');
            }
            $used = $sm->countUsages($id);
            if ($used > 0) {
                Helpers::flash('error', 'Status jest używany w ' . $used . ' zgłoszeniach — nie można go usunąć. Możesz go dezaktywować.');
                Helpers::redirect('admin_statuses');
            }
            $sm->delete($id);
            Helpers::flash('success', 'Status usunięty.');
        }
        Helpers::redirect('admin_statuses');
    }

    public function durTypesSave(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_dur_tmpl');
            return;
        }
        $validKeys   = ['weekly', 'monthly', 'quarterly', 'biannual', 'annual', 'ad_hoc'];
        $activeTypes = [];
        foreach ($validKeys as $k) {
            if (!empty($_POST['active_types']) && in_array($k, (array)$_POST['active_types'])) {
                $activeTypes[] = $k;
            }
        }
        if (empty($activeTypes)) {
            Helpers::flash('error', 'Zaznacz co najmniej jeden typ przeglądu.');
            Helpers::redirect('admin_dur_tmpl');
            return;
        }
        (new \App\Models\SettingsModel())->set('dur_active_review_types', json_encode($activeTypes));
        Helpers::flash('success', 'Aktywne typy przeglądów DUR zapisane.');
        Helpers::redirect('admin_dur_tmpl');
    }

    public function durStatusesSave(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_dur_sched');
            return;
        }

        $validKeys = ['completed', 'partial', 'interrupted'];
        $config    = [];

        foreach ($validKeys as $key) {
            $label    = trim($_POST['status'][$key]['label'] ?? '');
            // Priorytet: color picker (hex_text wpisany ręcznie), fallback: input[type=color]
            $colorHex = trim($_POST['status'][$key]['color_hex'] ?? '');
            $colorPicker = trim($_POST['status'][$key]['color'] ?? '');
            $color = preg_match('/^#[0-9a-fA-F]{6}$/', $colorHex)
                ? $colorHex
                : (preg_match('/^#[0-9a-fA-F]{6}$/', $colorPicker) ? $colorPicker : '#6b7280');

            if ($label) {
                $config[$key] = ['label' => $label, 'color' => $color];
            }
        }

        (new SettingsModel())->set('dur_review_statuses', json_encode($config));
        Helpers::flash('success', 'Nazwy i kolory statusów DUR zapisane.');
        Helpers::redirect('admin_dur_sched');
    }

    public function deleteTmpl(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd CSRF.');
            Helpers::redirect('admin_dur_tmpl');
        }
        $id = (int)($_POST['tmpl_id'] ?? 0);
        if ($id > 0) {
            (new MaintenanceModel())->deleteTemplate($id);
            Helpers::flash('success', 'Szablon DUR usunięty.');
        }
        Helpers::redirect('admin_dur_tmpl');
    }

    public function deleteSched(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd CSRF.');
            Helpers::redirect('admin_dur_sched');
        }
        $id = (int)($_POST['sched_id'] ?? 0);
        if ($id > 0) {
            (new MaintenanceModel())->deleteSchedule($id);
            Helpers::flash('success', 'Pozycja harmonogramu usunięta.');
        }
        Helpers::redirect('admin_dur_sched');
    }

    /**
     * Widok panelu: Części zamienne — kategorie i lista wszystkich użytych części.
     */
    public function spareParts(): void
    {
        Auth::requireAdmin();
        $categories   = (new SparePartCategoryModel())->getAll();
        $filterCatId  = !empty($_GET['cat_id'])   ? (int)$_GET['cat_id']  : null;
        $filterLineId = !empty($_GET['line_id'])  ? (int)$_GET['line_id'] : null;
        $filterFrom   = !empty($_GET['date_from']) ? $_GET['date_from']    : null;
        $filterTo     = !empty($_GET['date_to'])   ? $_GET['date_to']      : null;
        $spareParts   = (new SparePartModel())->getAll($filterCatId);
        $durSpareParts = (new SparePartModel())->getAllFromReviews($filterCatId, $filterLineId, $filterFrom, $filterTo);
        $lines        = (new ProductionLineModel())->getAll(true);
        require BASE_PATH . '/templates/admin/spare_parts.php';
    }

    /**
     * Zapis (dodaj / edytuj) kategorii części zamiennej.
     */
    public function sparePartCatSave(): void
    {
        Auth::requireAdmin();

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_spare_parts');
        }

        $id     = (int)($_POST['cat_id']     ?? 0);
        $name   = trim($_POST['name']        ?? '');
        $color  = trim($_POST['color']       ?? '#6c757d');
        $order  = (int)($_POST['sort_order'] ?? 0);
        $active = (int)($_POST['is_active']  ?? 1);

        if (!$name) {
            Helpers::flash('error', 'Podaj nazwę kategorii.');
            Helpers::redirect('admin_spare_parts');
        }

        $m    = new SparePartCategoryModel();
        $data = ['name' => $name, 'color' => $color, 'sort_order' => $order, 'is_active' => $active];

        if ($id > 0) {
            $m->update($id, $data);
            Helpers::flash('success', 'Kategoria zaktualizowana.');
        } else {
            $m->create($data);
            Helpers::flash('success', 'Kategoria "' . Helpers::e($name) . '" dodana.');
        }
        Helpers::redirect('admin_spare_parts');
    }

    /**
     * Usuwa kategorię części zamiennej (blokada gdy ma przypisane części).
     */
    public function sparePartCatDelete(): void
    {
        Auth::requireAdmin();

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_spare_parts');
        }

        $id = (int)($_POST['cat_id'] ?? 0);
        if ($id > 0) {
            $m     = new SparePartCategoryModel();
            $count = $m->countUsages($id);
            if ($count > 0) {
                Helpers::flash('error', 'Nie można usunąć kategorii — ma przypisane ' . $count . ' część/części. Dezaktywuj ją zamiast usuwać.');
                Helpers::redirect('admin_spare_parts');
            }
            $m->delete($id);
            Helpers::flash('success', 'Kategoria usunięta.');
        }
        Helpers::redirect('admin_spare_parts');
    }

}

// ── Endpoint AJAX: sprawdź duplikat usterki ───────────────────
