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

class UserController
{
    /**
     * Zmiana hasła — obsługuje POST z modala w topbarze.
     * Weryfikuje obecne hasło, sprawdza zgodność nowych, zapisuje hash.
     */
    public function changePassword(): void
    {
        Auth::requireLogin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            $this->redirectBack();
        }

        $currentPass = $_POST['current_password'] ?? '';
        $newPass     = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';
        $returnRoute = $_POST['return_route'] ?? 'dashboard';

        if (!$currentPass || !$newPass || !$confirmPass) {
            Helpers::flash('error', 'Wypełnij wszystkie pola formularza.');
            Helpers::redirect($returnRoute);
            return;
        }
        if (strlen($newPass) < 6) {
            Helpers::flash('error', 'Nowe hasło musi mieć co najmniej 6 znaków.');
            Helpers::redirect($returnRoute);
            return;
        }
        if ($newPass !== $confirmPass) {
            Helpers::flash('error', 'Nowe hasło i jego potwierdzenie nie są identyczne.');
            Helpers::redirect($returnRoute);
            return;
        }

        $user   = Auth::user();
        $um     = new UserModel();
        $dbUser = $um->findByNickname($user['login']);

        if (!$dbUser || !password_verify($currentPass, $dbUser['password_hash'])) {
            Helpers::flash('error', 'Obecne hasło jest nieprawidłowe.');
            Helpers::redirect($returnRoute);
            return;
        }

        $um->changePassword((int)$user['id'], $newPass);
        Helpers::flash('success', 'Hasło zostało zmienione pomyślnie.');
        Helpers::redirect($returnRoute);
    }

    /**
     * Moje zgłoszenia — lista awarii zgłoszonych przez bieżącego użytkownika.
     * Jeśli zgłoszenie ma status startowy (is_initial=1), użytkownik może je edytować.
     */
    public function myFailures(): void
    {
        Auth::requireLogin();
        $user       = Auth::user();
        $fm         = new FailureModel();
        $statuses   = (new StatusModel())->getAll(true);
        $symptoms   = (new SymptomModel())->getActive();   // ← NOWE: lista objawów dla modala

        $myFailures = $fm->getByReporterUserId((int)$user['id'], $user['name']);

        $pageTitle  = 'Moje zgłoszenia';
        require BASE_PATH . '/templates/shared/my_failures.php';
    }

    /**
     * Obsługuje POST z modala edycji objawu w "Moje zgłoszenia".
     * Weryfikuje własność zgłoszenia i status startowy.
     * Nowa metoda — Poprawka błąd 1.
     */
    public function myFailureEdit(): void
    {
        Auth::requireLogin();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('my_failures');
        }

        $user         = Auth::user();
        $id           = (int)($_POST['failure_id'] ?? 0);
        $otherSymptom = !empty($_POST['other_symptom']) ? 1 : 0;
        $symptomId    = (!$otherSymptom && !empty($_POST['symptom_id'])) ? (int)$_POST['symptom_id'] : null;
        $description  = trim($_POST['description'] ?? '');

        if (!$id) {
            Helpers::flash('error', 'Nieprawidłowe dane formularza.');
            Helpers::redirect('my_failures');
            return;
        }
        if (!$otherSymptom && !$symptomId) {
            Helpers::flash('error', 'Wybierz objaw awarii.');
            Helpers::redirect('my_failures');
            return;
        }
        if ($otherSymptom && !$description) {
            Helpers::flash('error', 'Wpisz opis przy "Inne objawy".');
            Helpers::redirect('my_failures');
            return;
        }

        $fm      = new FailureModel();
        $failure = $fm->getById($id);

        if (!$failure) {
            Helpers::flash('error', 'Zgłoszenie nie istnieje.');
            Helpers::redirect('my_failures');
            return;
        }

        // Sprawdź czy zgłoszenie należy do tego użytkownika
        if ((int)($failure['reporter_user_id'] ?? 0) !== (int)$user['id']) {
            Helpers::flash('error', 'Brak uprawnień do edycji tego zgłoszenia.');
            Helpers::redirect('my_failures');
            return;
        }

        // Sprawdź czy status jest nadal startowy
        $statuses  = (new StatusModel())->getAll(true);
        $isInitial = false;
        foreach ($statuses as $s) {
            if ($s['id'] == $failure['status_id'] && !empty($s['is_initial'])) {
                $isInitial = true;
                break;
            }
        }

        if (!$isInitial) {
            Helpers::flash('error', 'Zgłoszenie nie ma już statusu startowego — edycja niemożliwa.');
            Helpers::redirect('my_failures');
            return;
        }

        // Zapisz zmianę objawu
        $fm->updateSymptom($id, $symptomId, $otherSymptom, $description ?: null);
        $fm->addHistory(
            $id,
            (int)$user['id'],
            'edited',
            null,
            null,
            $user['name'],
            $otherSymptom
                ? 'Zaktualizowano objaw (Inne objawy) przez zgłaszającego'
                : 'Zaktualizowano objaw awarii przez zgłaszającego'
        );

        Helpers::flash('success', 'Objaw awarii zaktualizowany pomyślnie.');
        if (($_POST['return_to'] ?? '') === 'failure_detail') {
            Helpers::redirect('failure_detail', ['id' => $id]);
        } else {
            Helpers::redirect('my_failures');
        }
    }

    private function redirectBack(): void
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref) {
            header('Location: ' . $ref);
            exit;
        }
        Helpers::redirect('dashboard');
    }

    public function myRepairs(): void
    {
        Auth::requireLogin();

        if (!Auth::isMechanic() && !Auth::hasPermission('statuses')) {
            Helpers::flash('error', 'Brak uprawnień do tej sekcji.');
            Helpers::redirect('dashboard');
            return;
        }

        $user = Auth::user();
        $am   = new AssignmentModel();

        $catRaw  = trim($_GET['category_id'] ?? '');
        $filters = array_filter([
            'status_id'   => (int)($_GET['status_id'] ?? 0) > 0 ? (int)$_GET['status_id'] : null,
            'line_id'     => (int)($_GET['line_id'] ?? 0) > 0 ? (int)$_GET['line_id'] : null,
            'category_id' => $catRaw === 'none' ? 'none' : ((int)$catRaw > 0 ? (int)$catRaw : null),
            'role'        => in_array($_GET['role'] ?? '', ['leader', 'crew'], true) ? $_GET['role'] : null,
        ], fn($v) => $v !== null);

        $myRepairs  = $am->getByUserId((int)$user['id'], $filters);
        $statuses   = (new StatusModel())->getAll(true);
        $lines      = (new ProductionLineModel())->getAll(true);
        $categories = (new CategoryModel())->getAll(true);
        $pageTitle  = 'Moje naprawy';

        require BASE_PATH . '/templates/shared/my_repairs.php';
    }
}
