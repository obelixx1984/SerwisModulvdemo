<?php
// ============================================================
// app/Controllers/Controllers.php — wszystkie kontrolery
// ============================================================
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
    MaintenanceModel,
    SettingsModel,
    SymptomModel
};

// ────────────────────────────────────────────────────────────
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
        Helpers::redirect('dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        Helpers::redirect('login');
    }
}

// ────────────────────────────────────────────────────────────
class PublicController
{
    // Zmiana 1: załaduj objawy zamiast kategorii i słownika
    public function reportForm(): void
    {
        Auth::requireLogin();

        $lines    = (new ProductionLineModel())->getAll(true);
        $symptoms = (new SymptomModel())->getActive();

        $selectedLineId = (int)($_GET['line_id'] ?? 0);
        $lineHistory    = [];
        $lineStats      = [];
        $lineDur        = [];
        $duplicate      = null;
        $currentLine    = null;

        if ($selectedLineId) {
            foreach ($lines as $l) {
                if ((int)$l['id'] === $selectedLineId) {
                    $currentLine = $l;
                    break;
                }
            }
            $fm          = new FailureModel();
            $mm          = new MaintenanceModel();
            $lineHistory = $fm->getLineHistory($selectedLineId, 30);
            $lineStats   = $fm->getLineStats($selectedLineId, 30);
            $lineDur     = $mm->getReviewsByLine($selectedLineId, 3);

            // Zmiana 1+5: sprawdź duplikat po symptom_id
            if (!empty($_GET['symptom_id'])) {
                $duplicate = $fm->findOpenDuplicate($selectedLineId, (int)$_GET['symptom_id']);
            }
        }

        require BASE_PATH . '/templates/public/report_form.php';
    }

    public function reportPost(): void
    {
        Auth::requireLogin();

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa. Spróbuj ponownie.');
            Helpers::redirect('report');
        }

        $lineId      = (int)($_POST['production_line_id'] ?? 0);
        $subsysId    = !empty($_POST['subsystem_id']) ? (int)$_POST['subsystem_id'] : null;
        // Zmiana 1: symptom_id zamiast category_id + dictionary_item_id
        $symptomId   = !empty($_POST['symptom_id']) ? (int)$_POST['symptom_id'] : null;
        $description = trim($_POST['description'] ?? '');
        $currentUser  = Auth::user();
        $reporterName = $currentUser['name'];
        $reporterLogin = $currentUser['login'];

        $errors = [];
        if (!$lineId)    $errors[] = 'Wybierz linię produkcyjną.';
        if (!$symptomId) $errors[] = 'Wybierz objaw awarii.';

        if ($errors) {
            Helpers::flash('error', implode(' ', $errors));
            Helpers::redirect('report');
        }

        $initStatus = (new StatusModel())->getInitial();
        if (!$initStatus) {
            Helpers::flash('error', 'Błąd konfiguracji: brak statusu początkowego.');
            Helpers::redirect('report');
        }

        // Pobierz dane linii do generowania numeru
        $lm    = new ProductionLineModel();
        $line  = $lm->getById($lineId);
        if (!$line) {
            Helpers::flash('error', 'Wybrana linia nie istnieje.');
            Helpers::redirect('report');
        }

        // POPRAWKA 1: Generuj numer w formacie 0001/PREFIX/ROK
        $ticket = Helpers::generateTicketNumber($lineId, $line['prefix']);

        $fm     = new FailureModel();
        $failId = $fm->create([
            'ticket_number'      => $ticket,
            'production_line_id' => $lineId,
            'subsystem_id'       => $subsysId,
            'symptom_id'         => $symptomId,
            'status_id'          => $initStatus['id'],
            'reporter_acronym'   => $reporterLogin,
            'reporter_name'      => $reporterName,
            'description'        => $description ?: null,
        ]);

        $fm->addHistory(
            $failId,
            $currentUser['id'],
            'created',
            null,
            $initStatus['id'],
            $reporterLogin . ' – ' . $reporterName,
            'Zgłoszenie awarii utworzone'
        );

        // POPRAWKA 2: Informacja o DUR przekazana przez flash (pokaże komunikat w szablonie)
        Helpers::flash(
            'success_dur',
            'Zgłoszenie wysłane pomyślnie. Numer: <strong>' . Helpers::e($ticket) . '</strong>'
        );
        Helpers::redirect('report');
    }

    /** Historia linii — dostepna publicznie */
    public function lineHistory(): void
    {
        $lines   = (new ProductionLineModel())->getAll(true);
        $lineId  = (int)($_GET['line_id'] ?? 0);
        $rawDays = (int)($_GET['days'] ?? 30);
        $days    = in_array($rawDays, [7, 30, 90, 365]) ? $rawDays : 30;

        $fm          = new FailureModel();
        $mm          = new MaintenanceModel();
        $failures    = [];
        $stats       = ['total' => 0, 'open_count' => 0, 'closed_count' => 0, 'avg_repair_str' => '—'];
        $durList     = [];
        $currentLine = null;

        if ($lineId > 0) {
            foreach ($lines as $l) {
                if ((int)$l['id'] === $lineId) {
                    $currentLine = $l;
                    break;
                }
            }
            $failures = $fm->getLineHistory($lineId, $days);
            $rawStats = $fm->getLineStats($lineId, $days);
            if ($rawStats !== null) $stats = $rawStats;
            $durList  = $mm->getReviewsByLine($lineId, 5);
        }

        require BASE_PATH . '/templates/public/line_history.php';
    }
}

// ────────────────────────────────────────────────────────────
class FailureController
{
    /** POPRAWKA 7+8: Dashboard → Pulpit (zarówno dla mechanika jak i admina) */
    public function dashboard(): void
    {
        Auth::requireLogin();
        $fm        = new FailureModel();
        $mm        = new MaintenanceModel();
        $stats     = $fm->getDashboardStats();
        $recent    = $fm->getList([], 6, 0);
        $upcoming  = $mm->getUpcomingSchedules(DUR_WARNING_DAYS);
        $statuses  = (new StatusModel())->getAll(true);

        // Średni czas naprawy dla wszystkich linii (błąd 1 pulpit)
        $avgRepairAll = $fm->getGlobalAvgRepairTime();

        // Zlicz per status
        $byStatus = [];
        foreach ($statuses as $s) {
            $byStatus[$s['id']] = ['label' => $s['label'], 'color' => $s['color'], 'count' => 0];
        }
        $allOpen = $fm->getList([], 9999, 0);
        foreach ($allOpen as $f) {
            if (isset($byStatus[$f['status_id']])) $byStatus[$f['status_id']]['count']++;
        }

        require BASE_PATH . '/templates/shared/dashboard.php';
    }

    public function list(): void
    {
        Auth::requireMechanic();
        $catRaw   = $_GET['category_id'] ?? '';
        $filters  = [
            'status_id'   => (int)($_GET['status_id'] ?? 0) ?: null,
            'line_id'     => (int)($_GET['line_id'] ?? 0) ?: null,
            'category_id' => $catRaw === 'none' ? 'none' : ((int)$catRaw ?: null),
            'search'      => trim($_GET['search'] ?? ''),
        ];
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $total  = (new FailureModel())->countList(array_filter($filters));
        $pager  = Helpers::paginate($total, $page, RECORDS_PER_PAGE);
        $fm     = new FailureModel();
        $items  = $fm->getList(array_filter($filters), $pager['per_page'], $pager['offset']);

        $lines      = (new ProductionLineModel())->getAll(true);
        $categories = (new CategoryModel())->getAll(true);
        $statuses   = (new StatusModel())->getAll(true);

        require BASE_PATH . '/templates/shared/failures_list.php';
    }

    // Zmiana 2: załaduj kategorie i słownik dla sekcji mechanika
    public function detail(): void
    {
        Auth::requireMechanic();
        $id      = (int)($_GET['id'] ?? 0);
        $fm      = new FailureModel();
        $failure = $fm->getById($id);
        if (!$failure) {
            require BASE_PATH . '/templates/shared/404.php';
            return;
        }

        $history    = $fm->getHistory($id);
        $comments   = $fm->getComments($id);
        $statuses   = (new StatusModel())->getAll(true);
        $categories = (new CategoryModel())->getAll(true);
        $dictionary = (new DictionaryModel())->getActive();

        require BASE_PATH . '/templates/shared/failure_detail.php';
    }

    // Zmiana 3: blokada statusu końcowego bez kategorii i usterki
    public function changeStatus(): void
    {
        Auth::requireMechanic();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('failures');
        }
        $id          = (int)($_POST['failure_id'] ?? 0);
        $newStatusId = (int)($_POST['status_id'] ?? 0);
        $note        = trim($_POST['note'] ?? '');

        $fm      = new FailureModel();
        $failure = $fm->getById($id);
        if (!$failure) {
            Helpers::redirect('failures');
        }

        // Blokada: ten sam status
        if ($failure['status_id'] == $newStatusId) {
            Helpers::flash('error', 'Zgłoszenie ma już ten status. Wybierz inny.');
            Helpers::redirect('failure_detail', ['id' => $id]);
        }

        // Blokada: zgłoszenie ze statusem końcowym (błąd 3)
        if (!empty($failure['status_is_final'])) {
            Helpers::flash('error', 'Zgłoszenie jest zamknięte — nie można zmieniać statusu.');
            Helpers::redirect('failure_detail', ['id' => $id]);
        }

        $newStatus = (new StatusModel())->getById($newStatusId);
        if (!$newStatus) {
            Helpers::flash('error', 'Nieprawidłowy status.');
            Helpers::redirect('failure_detail', ['id' => $id]);
        }

        // Blokada: nie można ręcznie nadać statusu startowego (błąd 4)
        if (!empty($newStatus['is_initial'])) {
            Helpers::flash('error', 'Status startowy jest nadawany automatycznie przy tworzeniu zgłoszenia — nie można go przypisać ręcznie.');
            Helpers::redirect('failure_detail', ['id' => $id]);
        }

        // Zmiana 3: blokada statusu końcowego bez kategorii i usterki
        if (!empty($newStatus['is_final'])) {
            $hasCategory = !empty($failure['category_id']);
            $hasDict     = !empty($failure['dictionary_item_id']);
            $hasOther    = !empty($failure['other_failure']);
            $hasNote     = !empty($failure['mechanic_note']);

            if (!$hasCategory || (!$hasDict && !$hasOther) || ($hasOther && !$hasNote)) {
                Helpers::flash('error', 'Nie dodałeś kategorii i rodzaju awarii!!! Uzupełnij to!!!');
                Helpers::redirect('failure_detail', ['id' => $id]);
            }
        }

        $user = Auth::user();
        $fm->changeStatus($id, $newStatusId, (bool)$newStatus['is_final']);
        $fm->addHistory(
            $id,
            $user['id'],
            'status_changed',
            $failure['status_id'],
            $newStatusId,
            $user['name'],
            $note ?: 'Zmiana statusu: ' . $failure['status_label'] . ' → ' . $newStatus['label']
        );

        Helpers::flash('success', 'Status zmieniony na: <strong>' . Helpers::e($newStatus['label']) . '</strong>');
        Helpers::redirect('failure_detail', ['id' => $id]);
    }

    // Zmiana 2: mechanik ustawia kategorię i usterkę
    public function setCategory(): void
    {
        Auth::requireMechanic();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('failures');
        }
        $id           = (int)($_POST['failure_id'] ?? 0);
        $categoryId   = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $dictItemId   = !empty($_POST['dictionary_item_id']) ? (int)$_POST['dictionary_item_id'] : null;
        $otherFailure = !empty($_POST['other_failure']) ? 1 : 0;
        $mechanicNote = trim($_POST['mechanic_note'] ?? '');

        $fm      = new FailureModel();
        $failure = $fm->getById($id);
        if (!$failure) {
            Helpers::redirect('failures');
        }

        // Walidacja: jeśli Inna usterka, notatka obowiązkowa
        if ($otherFailure && !$mechanicNote) {
            Helpers::flash('error', 'Przy "Inna usterka" musisz wpisać notatkę mechanika.');
            Helpers::redirect('failure_detail', ['id' => $id]);
        }

        // Gdy Inna usterka — wyczyść pozycję słownika (są wzajemnie wykluczające się)
        if ($otherFailure) {
            $dictItemId = null;
        }

        $user = Auth::user();
        $fm->setCategory($id, [
            'category_id'        => $categoryId,
            'dictionary_item_id' => $dictItemId,
            'other_failure'      => $otherFailure,
            'mechanic_note'      => $mechanicNote,
        ]);
        $fm->addHistory(
            $id,
            $user['id'],
            'edited',
            null, null,
            $user['name'],
            'Ustawiono kategorię i usterkę przez mechanika'
        );
        Helpers::flash('success', 'Kategoria i usterka zapisane.');
        Helpers::redirect('failure_detail', ['id' => $id]);
    }

    public function deleteFailure(): void
    {
        Auth::requireLogin();
        if (!Auth::hasAdminPermission()) {
            Helpers::flash('error', 'Brak uprawnień do usuwania zgłoszeń.');
            Helpers::redirect('failures');
        }
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('failures');
        }
        $id = (int)($_POST['failure_id'] ?? 0);
        if ($id > 0) {
            $fm      = new FailureModel();
            $failure = $fm->getById($id);
            if ($failure) {
                $ticket = $failure['ticket_number'];
                $fm->deleteFailure($id);
                Helpers::flash('success', 'Zgłoszenie <strong>' . Helpers::e($ticket) . '</strong> zostało usunięte.');
            }
        }
        Helpers::redirect('failures');
    }

    public function addComment(): void
    {
        Auth::requireMechanic();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('failures');
        }
        $id      = (int)($_POST['failure_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if (!$id || !$comment) {
            Helpers::flash('error', 'Komentarz nie może być pusty.');
            Helpers::redirect('failure_detail', ['id' => $id]);
        }
        $user = Auth::user();
        $fm   = new FailureModel();
        $fm->addComment($id, $user['id'], $user['name'], $comment);
        $fm->addHistory($id, $user['id'], 'comment_added', null, null, $user['name'], 'Dodano komentarz');
        Helpers::flash('success', 'Komentarz dodany.');
        Helpers::redirect('failure_detail', ['id' => $id]);
    }
}

// ────────────────────────────────────────────────────────────
class DurController
{
    public function list(): void
    {
        // DUR dostepny publicznie (tylko odczyt)
        $mm       = new MaintenanceModel();
        $filters  = [
            'line_id' => (int)($_GET['line_id'] ?? 0) ?: null,
            'status'  => $_GET['status'] ?? null,
            'type'    => $_GET['type'] ?? null,
        ];
        $reviews  = $mm->getAllReviews(array_filter($filters), 50, 0);
        $upcoming = $mm->getUpcomingSchedules(DUR_WARNING_DAYS);
        $lines    = (new ProductionLineModel())->getAll(true);
        $templates = $mm->getTemplates();

        require BASE_PATH . '/templates/shared/dur_list.php';
    }

    public function addForm(): void
    {
        Auth::requireMechanic();
        $lines     = (new ProductionLineModel())->getAll(true);
        $templates = (new MaintenanceModel())->getTemplates();
        require BASE_PATH . '/templates/shared/dur_form.php';
    }

    public function addPost(): void
    {
        Auth::requireMechanic();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('dur_add');
        }
        $lineId      = (int)($_POST['production_line_id'] ?? 0);
        $subsysId    = !empty($_POST['subsystem_id']) ? (int)$_POST['subsystem_id'] : null;
        $templateId  = !empty($_POST['template_id']) ? (int)$_POST['template_id'] : null;
        $activities  = trim($_POST['activities'] ?? '');
        $reviewDate  = $_POST['review_date'] ?? '';
        $reviewType  = $_POST['review_type'] ?? 'monthly';
        $duration    = !empty($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : null;
        $status      = in_array($_POST['status'] ?? '', ['completed', 'partial', 'interrupted'])
            ? $_POST['status'] : 'completed';
        $nextDate    = $_POST['next_review_date'] ?? null;
        $parts       = trim($_POST['parts_used'] ?? '');
        $notes       = trim($_POST['notes'] ?? '');

        if (!$lineId || !$activities || !$reviewDate) {
            Helpers::flash('error', 'Uzupełnij wymagane pola.');
            Helpers::redirect('dur_add');
        }

        $user = Auth::user();
        (new MaintenanceModel())->create([
            'production_line_id' => $lineId,
            'subsystem_id'       => $subsysId,
            'template_id'        => $templateId,
            'performed_by'       => $user['id'],
            'review_type'        => $reviewType,
            'review_date'        => $reviewDate,
            'duration_minutes'   => $duration,
            'activities'         => $activities,
            'parts_used'         => $parts ?: null,
            'notes'              => $notes ?: null,
            'status'             => $status,
            'next_review_date'   => $nextDate ?: null,
        ]);

        Helpers::flash('success', 'Raport DUR zapisany pomyślnie.');
        Helpers::redirect('dur');
    }

    public function detail(): void
    {
        // DUR detail dostepny publicznie (tylko odczyt)
        $id     = (int)($_GET['id'] ?? 0);
        $review = (new MaintenanceModel())->getById($id);
        if (!$review) {
            require BASE_PATH . '/templates/shared/404.php';
            return;
        }
        require BASE_PATH . '/templates/shared/dur_detail.php';
    }
}

// ────────────────────────────────────────────────────────────
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
        $final   = (int)($_POST['is_final'] ?? 0);

        if (!$label) {
            Helpers::flash('error', 'Podaj etykietę statusu.');
            Helpers::redirect('admin_statuses');
        }

        $sm   = new StatusModel();
        $data = [
            'label' => $label,
            'color' => $color,
            'sort_order' => $order,
            'is_active' => $active,
            'is_initial' => $initial,
            'is_final' => $final
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
        $templates = (new MaintenanceModel())->getTemplates(false);
        require BASE_PATH . '/templates/admin/dur_templates.php';
    }

    public function tmplSave(): void
    {
        Auth::requireAdmin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('admin_dur_tmpl');
        }
        $id          = (int)($_POST['tmpl_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $reviewType  = $_POST['review_type'] ?? 'monthly';
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
        $tmplId     = !empty($_POST['template_id']) ? (int)$_POST['template_id'] : null;
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
            'template_id' => $tmplId,
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
        }
        $keys   = ['weekly', 'monthly', 'quarterly', 'biannual', 'annual', 'ad_hoc'];
        $labels = [];
        foreach ($keys as $k) {
            $v = trim($_POST['type_' . $k] ?? '');
            if ($v) $labels[$k] = $v;
        }
        (new \App\Models\SettingsModel())->set('dur_type_labels', json_encode($labels));
        Helpers::flash('success', 'Etykiety typów DUR zapisane.');
        Helpers::redirect('admin_dur_tmpl');
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
}

// ── Endpoint AJAX: sprawdź duplikat usterki ───────────────────
class AjaxController
{
    // Zmiana 1+5: sprawdza duplikat po symptom_id (nie dict_id)
    public function checkDuplicate(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $lineId    = (int)($_GET['line_id'] ?? 0);
        $symptomId = (int)($_GET['symptom_id'] ?? 0);
        if (!$lineId || !$symptomId) {
            echo json_encode(['ticket' => null]);
            exit;
        }
        $dup = (new \App\Models\FailureModel())->findOpenDuplicate($lineId, $symptomId);
        echo json_encode(['ticket' => $dup ? $dup['ticket_number'] : null]);
        exit;
    }
}
