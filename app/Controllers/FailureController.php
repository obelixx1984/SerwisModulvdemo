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
        $durWarnDays = max(1, (int)((new \App\Models\SettingsModel())->get('dur_warning_days') ?? DUR_WARNING_DAYS));
        $upcoming    = $mm->getUpcomingSchedules($durWarnDays);

        $noteCounts = [];
        if (!empty($upcoming)) {
            $scheduleIds = array_column($upcoming, 'id');
            $noteCounts  = (new \App\Models\ScheduleNoteModel())->countActiveGrouped($scheduleIds);
        }

        $statuses  = (new StatusModel())->getAll(true);

        // Średni czas naprawy dla wszystkich linii (zachowany do ewentualnego użycia)
        $avgRepairAll = $fm->getGlobalAvgRepairTime();

        // Awarie z ostatnich 30 dni
        $last30Count = $fm->getLast30DaysCount();

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
        Auth::requireLogin();
        if (!Auth::isMechanic() && !Auth::hasPermission('failures')) {
            Helpers::flash('error', 'Brak uprawnień do listy zgłoszeń.');
            Helpers::redirect('dashboard');
            return;
        }
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
        Auth::requireLogin();

        $id      = (int)($_GET['id'] ?? 0);
        $fm      = new FailureModel();
        $failure = $fm->getById($id);

        if (!$failure) {
            require BASE_PATH . '/templates/shared/404.php';
            return;
        }

        $user       = Auth::user();
        $canView    = Auth::isMechanic() || Auth::hasPermission('failures');
        $isReporter = (int)($failure['reporter_user_id'] ?? 0) === (int)$user['id'];
        $canEdit    = Auth::isMechanic() || Auth::hasPermission('statuses');
        // Każdy zalogowany użytkownik może zobaczyć szczegóły zgłoszenia w trybie podglądu.
        // $canEdit i $isReporter kontrolują co może zrobić na tej stronie.

        $history    = $fm->getHistory($id);
        $comments   = $fm->getComments($id);
        $statuses   = (new StatusModel())->getAll(true);
        $categories = (new CategoryModel())->getAll(true);
        $dictionary = (new DictionaryModel())->getActive();
        $spareParts         = (new SparePartModel())->getByFailure((int)$failure['id']);
        $sparePartCategories = (new SparePartCategoryModel())->getAll(true);

        // ── NOWE: obsada zgłoszenia ──────────────────────────
        $am          = new AssignmentModel();
        $assignments = $am->getByFailure($id);
        // Lista mechaników do wyboru (tylko rola mechanic, aktywni)
        $mechanics   = (new UserModel())->getMechanics();
        // ── NOWE ────────────────────────────────────────────────────
        $isLeader    = $am->isLeader($id, (int)$user['id']);
        $hasLeader   = !empty(array_filter($assignments, fn($a) => !empty($a['is_first'])));
        $symptoms    = (new SymptomModel())->getActive();  // ← DODAJ
        // ────────────────────────────────────────────────────────────

        // Zdjęcia — uprawnieni widzą wszystkie, pozostali tylko publiczne
        $photos = $fm->getPhotos($id, !$canEdit);

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

        if ($failure['status_id'] == $newStatusId) {
            Helpers::flash('error', 'Zgłoszenie ma już ten status. Wybierz inny.');
            Helpers::redirect('failure_detail', ['id' => $id]);
        }

        if (!empty($failure['status_is_final'])) {
            Helpers::flash('error', 'Zgłoszenie jest zamknięte — nie można zmieniać statusu.');
            Helpers::redirect('failure_detail', ['id' => $id]);
        }

        $newStatus = (new StatusModel())->getById($newStatusId);
        if (!$newStatus) {
            Helpers::flash('error', 'Nieprawidłowy status.');
            Helpers::redirect('failure_detail', ['id' => $id]);
        }

        if (!empty($newStatus['is_initial'])) {
            Helpers::flash('error', 'Status startowy nadawany jest automatycznie.');
            Helpers::redirect('failure_detail', ['id' => $id]);
        }

        // ── Walidacja przy statusie końcowym ─────────────────
        if (!empty($newStatus['is_final'])) {
            // Kategoria i usterka
            $hasCategory = !empty($failure['category_id']);
            $hasDict     = !empty($failure['dictionary_item_id']);
            $hasOther    = !empty($failure['other_failure']);
            $hasNote     = !empty($failure['mechanic_note']);

            if (!$hasCategory || (!$hasDict && !$hasOther) || ($hasOther && !$hasNote)) {
                Helpers::flash('error', 'Nie dodałeś kategorii i rodzaju awarii!!! Uzupełnij to!!!');
                Helpers::redirect('failure_detail', ['id' => $id]);
            }

            // ── NOWE: walidacja obsady ────────────────────────
            $am          = new AssignmentModel();
            $assignments = $am->getByFailure($id);
            if (empty($assignments)) {
                Helpers::flash('error', 'Brak obsady zgłoszenia!!! Przed zamknięciem potwierdź kto pracował przy naprawie.');
                Helpers::redirect('failure_detail', ['id' => $id]);
            }
            // ─────────────────────────────────────────────────
        }

        $user = Auth::user();
        $am   = new AssignmentModel();

        // ── NOWE: auto-dodaj bieżącego użytkownika do obsady ─
        // is_first=1 tylko gdy status poprzedni był startowy
        $prevStatusIsInitial = !empty($failure['status_is_initial'] ?? false);
        // Sprawdź przez join — getById daje nam status_is_final, ale nie is_initial
        // Pobieramy aktualny status by sprawdzić is_initial
        $currentStatus = (new StatusModel())->getById($failure['status_id']);
        $isFirst       = !empty($currentStatus['is_initial']) && !$am->isInCrew($id, (int)$user['id']);
        $am->addMember($id, (int)$user['id'], $user['name'], $isFirst);
        // ─────────────────────────────────────────────────────

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

        // Blokada: zgłoszenie zamknięte — nie można edytować kategorii
        if (!empty($failure['status_is_final'])) {
            Helpers::flash('error', 'Zgłoszenie jest zamknięte — nie można edytować kategorii i usterki.');
            Helpers::redirect('failure_detail', ['id' => $id]);
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
            null,
            null,
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

    /**
     * Dodaje część zamienną do zgłoszenia.
     * Dostępne tylko gdy zgłoszenie nie ma statusu końcowego.
     */
    public function sparePartAdd(): void
    {
        Auth::requireLogin();

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('failures');
        }

        $failureId  = (int)($_POST['failure_id']  ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $partName   = trim($_POST['part_name']    ?? '');
        $quantity   = max(1, (int)($_POST['quantity'] ?? 1));

        if (!$failureId || !$categoryId || !$partName) {
            Helpers::flash('error', 'Wypełnij wszystkie wymagane pola części zamiennej.');
            Helpers::redirect('failure_detail&id=' . $failureId);
        }

        // Sprawdź uprawnienia do zmiany statusu (mechanik / admin)
        if (!Auth::hasPermission('statuses')) {
            Helpers::flash('error', 'Brak uprawnień do dodawania części zamiennych.');
            Helpers::redirect('failure_detail&id=' . $failureId);
        }

        $failure = (new FailureModel())->getById($failureId);
        if (!$failure) {
            Helpers::flash('error', 'Zgłoszenie nie istnieje.');
            Helpers::redirect('failures');
        }

        // Blokada po statusie końcowym
        if (!empty($failure['status_is_final'])) {
            Helpers::flash('error', 'Nie można dodawać części po nadaniu statusu końcowego.');
            Helpers::redirect('failure_detail&id=' . $failureId);
        }

        (new SparePartModel())->create([
            'failure_id'  => $failureId,
            'category_id' => $categoryId,
            'part_name'   => $partName,
            'quantity'    => $quantity,
            'added_by'    => (int)Auth::user()['id'],
        ]);

        Helpers::flash('success', 'Część zamienna dodana.');
        Helpers::redirect('failure_detail&id=' . $failureId);
    }

    /**
     * Usuwa część zamienną ze zgłoszenia.
     * Dostępne tylko dla mechanika / admina i gdy status nie jest końcowy.
     */
    public function sparePartDelete(): void
    {
        Auth::requireLogin();

        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('failures');
        }

        $spareId   = (int)($_POST['spare_id']   ?? 0);
        $failureId = (int)($_POST['failure_id'] ?? 0);

        if (!Auth::hasPermission('statuses')) {
            Helpers::flash('error', 'Brak uprawnień.');
            Helpers::redirect('failure_detail&id=' . $failureId);
        }

        $failure = (new FailureModel())->getById($failureId);
        if (!empty($failure['status_is_final'])) {
            Helpers::flash('error', 'Nie można usuwać części po nadaniu statusu końcowego.');
            Helpers::redirect('failure_detail&id=' . $failureId);
        }

        if ($spareId > 0) {
            (new SparePartModel())->delete($spareId);
            Helpers::flash('success', 'Część usunięta.');
        }
        Helpers::redirect('failure_detail&id=' . $failureId);
    }

    // ──────────────────────────────────────────────────────────────────
    // NOWE: addAssignment() — ręczne dodanie osoby do obsady
    // ──────────────────────────────────────────────────────────────────
    public function addAssignment(): void
    {
        Auth::requireMechanic();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('failures');
        }

        $failureId = (int)($_POST['failure_id'] ?? 0);
        $userId    = (int)($_POST['user_id'] ?? 0);

        $fm      = new FailureModel();
        $failure = $fm->getById($failureId);

        if (!$failure) {
            Helpers::redirect('failures');
        }

        if (!empty($failure['status_is_final'])) {
            Helpers::flash('error', 'Zgłoszenie zamknięte — nie można modyfikować obsady.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
            return;
        }

        if (!$userId) {
            Helpers::flash('error', 'Wybierz osobę z listy.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
            return;
        }

        // Sprawdź że dodawany użytkownik jest mechanikiem
        $um      = new UserModel();
        $addUser = $um->getById($userId);
        if (!$addUser || $addUser['role_name'] !== 'mechanic') {
            Helpers::flash('error', 'Do obsady można dodawać tylko osoby z rolą Mechanik.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
            return;
        }

        $am = new AssignmentModel();
        if ($am->isInCrew($failureId, $userId)) {
            Helpers::flash('error', Helpers::e($addUser['name']) . ' już jest w obsadzie tego zgłoszenia.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
            return;
        }

        $currentUser = Auth::user();

        // Tylko prowadzący może dodawać do obsady
        if (!$am->isLeader($failureId, (int)$currentUser['id'])) {
            Helpers::flash('error', 'Tylko prowadzący naprawę może modyfikować obsadę.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
            return;
        }

        $am->addMember($failureId, $userId, $addUser['name'], false, (int)$currentUser['id']);

        $fm->addHistory(
            $failureId,
            (int)$currentUser['id'],
            'crew_added',
            null,
            null,
            $currentUser['name'],
            'Dodano do obsady: ' . $addUser['name']
        );

        Helpers::flash('success', Helpers::e($addUser['name']) . ' dodany do obsady.');
        Helpers::redirect('failure_detail', ['id' => $failureId]);
    }

    // ──────────────────────────────────────────────────────────────────
    // NOWE: removeAssignment() — usunięcie osoby z obsady
    // ──────────────────────────────────────────────────────────────────
    public function removeAssignment(): void
    {
        Auth::requireMechanic();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('failures');
        }

        $assignId  = (int)($_POST['assignment_id'] ?? 0);
        $failureId = (int)($_POST['failure_id'] ?? 0);

        $am         = new AssignmentModel();
        $assignment = $am->getById($assignId);

        if (!$assignment || (int)$assignment['failure_id'] !== $failureId) {
            Helpers::flash('error', 'Nie znaleziono wpisu obsady.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
            return;
        }

        // Blokada: pierwszej osoby nie można usunąć
        if (!empty($assignment['is_first'])) {
            Helpers::flash('error', 'Nie można usunąć prowadzącego z obsady.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
            return;
        }

        // ── NOWE: tylko prowadzący może usuwać z obsady ──────
        $currentUser = Auth::user();
        if (!$am->isLeader($failureId, (int)$currentUser['id'])) {
            Helpers::flash('error', 'Tylko prowadzący naprawę może modyfikować obsadę.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
            return;
        }
        // ────────────────────────────────────────────────────

        $fm      = new FailureModel();
        $failure = $fm->getById($failureId);
        if (!empty($failure['status_is_final'])) {
            Helpers::flash('error', 'Zgłoszenie zamknięte — nie można modyfikować obsady.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
            return;
        }

        $currentUser = Auth::user();
        $removedName = $assignment['user_name'];
        $am->removeMember($assignId);

        $fm->addHistory(
            $failureId,
            (int)$currentUser['id'],
            'crew_removed',
            null,
            null,
            $currentUser['name'],
            'Usunięto z obsady: ' . $removedName
        );

        Helpers::flash('success', Helpers::e($removedName) . ' usunięty z obsady.');
        Helpers::redirect('failure_detail', ['id' => $failureId]);
    }

    // ── Zdjęcia zgłoszenia ──────────────────────────────────

    public function photoUpload(): void
    {
        Auth::requireLogin();

        if (!Auth::isMechanic()) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Brak uprawnień.']);
            return;
        }

        $failureId = (int)($_POST['failure_id'] ?? 0);
        $isPublic  = ($_POST['is_public'] ?? '0') === '1' ? 1 : 0;

        if (!$failureId) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Brak ID zgłoszenia.']);
            return;
        }

        $fm      = new FailureModel();
        $failure = $fm->getById($failureId);

        if (!$failure) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Zgłoszenie nie istnieje.']);
            return;
        }

        if (
            empty($_FILES['photo']) ||
            $_FILES['photo']['error'] !== UPLOAD_ERR_OK
        ) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Błąd przesyłania pliku.']);
            return;
        }

        // Limit 6 MB przed kompresją
        if ($_FILES['photo']['size'] > 6 * 1024 * 1024) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Plik przekracza 6 MB.']);
            return;
        }

        $user     = Auth::user();
        $username = $user['login'];
        $ticket   = $failure['ticket_number'];

        $dir = BASE_PATH . '/foto/' . $username . '/' . $ticket . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tmpPath = $_FILES['photo']['tmp_name'];
        $mime    = mime_content_type($tmpPath);

        if ($mime === 'image/jpeg') {
            $src = imagecreatefromjpeg($tmpPath);
        } elseif ($mime === 'image/png') {
            $src = imagecreatefrompng($tmpPath);
        } elseif ($mime === 'image/webp') {
            $src = imagecreatefromwebp($tmpPath);
        } else {
            $src = null;
        }

        if (!$src) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nieobsługiwany format obrazu (JPEG, PNG, WEBP).']);
            return;
        }

        $w      = imagesx($src);
        $h      = imagesy($src);
        $maxDim = 1920;

        if ($w > $maxDim || $h > $maxDim) {
            $ratio = min($maxDim / $w, $maxDim / $h);
            $nw    = (int) round($w * $ratio);
            $nh    = (int) round($h * $ratio);
            $dst   = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst;
        }

        $filename = uniqid('', true) . '.jpg';
        $fullPath = $dir . $filename;
        imagejpeg($src, $fullPath, 80);
        imagedestroy($src);

        $filesize = (int) filesize($fullPath);
        $dbPath   = 'foto/' . $username . '/' . $ticket . '/' . $filename;

        $photoId = $fm->addPhoto([
            'failure_id' => $failureId,
            'user_id'    => (int) $user['id'],
            'username'   => $username,
            'filename'   => $filename,
            'path'       => $dbPath,
            'filesize'   => $filesize,
            'is_public'  => $isPublic,
        ]);

        header('Content-Type: application/json');
        echo json_encode([
            'success'  => true,
            'id'       => $photoId,
            'url'      => BASE_URL . '/foto/' . $username . '/' . $ticket . '/' . $filename,
            'filesize' => $filesize,
        ]);
    }

    public function photoBridgeQr(): void
    {
        Auth::requireLogin();
        if (!Auth::isMechanic()) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Brak uprawnień.']);
            return;
        }
        $failureId = (int)($_POST['failure_id'] ?? 0);
        if (!$failureId) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Brak ID zgłoszenia.']);
            return;
        }
        $fm      = new FailureModel();
        $failure = $fm->getById($failureId);
        if (!$failure) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Zgłoszenie nie istnieje.']);
            return;
        }
        $user  = Auth::user();
        $bm    = new \App\Models\BridgeModel();
        $token = $bm->generateQrToken($user['login'], $failure['ticket_number']);
        if (!$token) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Błąd połączenia z mostem.']);
            return;
        }
        header('Content-Type: application/json');
        echo json_encode([
            'success'  => true,
            'qr_token' => $token,
            'ticket'   => $failure['ticket_number'],
        ]);
    }

    public function photoCheckNew(): void
    {
        Auth::requireLogin();

        $failureId = (int)($_GET['failure_id'] ?? 0);
        $since     = (int)($_GET['since'] ?? 0);

        if (!$failureId) {
            header('Content-Type: application/json');
            echo json_encode(['count' => 0, 'total' => 0]);
            return;
        }

        // Pobierz nowe zdjęcia z Mostu i zapisz do zgłoszenia
        $bm = new \App\Models\BridgeModel();
        if ($bm->login()) {
            $photos = $bm->getPhotos();
            if (!empty($photos)) {
                $fm   = new FailureModel();
                $fail = $fm->getById($failureId);
                $user = Auth::user();

                foreach ($photos as $photo) {
                    $dl = $bm->downloadPhoto((int)$photo['id']);
                    if (empty($dl['body'])) continue;

                    $ticket   = $fail['ticket_number'];
                    $username = $user['login'];
                    $dir      = BASE_PATH . '/foto/' . $username . '/' . $ticket . '/';

                    if (!\is_dir($dir)) \mkdir($dir, 0755, true);

                    // Kompresja przez GD
                    $tmp = \tempnam(\sys_get_temp_dir(), 'bridge_');
                    \file_put_contents($tmp, $dl['body']);
                    $mime = \mime_content_type($tmp);

                    if ($mime === 'image/jpeg') {
                        $src = \imagecreatefromjpeg($tmp);
                    } elseif ($mime === 'image/png') {
                        $src = \imagecreatefrompng($tmp);
                    } elseif ($mime === 'image/webp') {
                        $src = \imagecreatefromwebp($tmp);
                    } else {
                        $src = null;
                    }

                    \unlink($tmp);
                    if (!$src) continue;

                    $w = \imagesx($src);
                    $h = \imagesy($src);
                    $maxDim = 1920;
                    if ($w > $maxDim || $h > $maxDim) {
                        $ratio = min($maxDim / $w, $maxDim / $h);
                        $nw = (int)round($w * $ratio);
                        $nh = (int)round($h * $ratio);
                        $dst = \imagecreatetruecolor($nw, $nh);
                        \imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                        \imagedestroy($src);
                        $src = $dst;
                    }

                    $filename = \uniqid('mob_', true) . '.jpg';
                    $fullPath = $dir . $filename;
                    \imagejpeg($src, $fullPath, 80);
                    \imagedestroy($src);

                    $fm->addPhoto([
                        'failure_id' => $failureId,
                        'user_id'    => (int)$user['id'],
                        'username'   => $username,
                        'filename'   => $filename,
                        'path'       => 'foto/' . $username . '/' . $ticket . '/' . $filename,
                        'filesize'   => (int)\filesize($fullPath),
                        'is_public'  => (int)$photo['is_public'],
                    ]);
                }
            }
        }

        // Zwróć aktualną liczbę zdjęć
        $canEdit   = Auth::isMechanic();
        $fm        = new FailureModel();
        $allPhotos = $fm->getPhotos($failureId, !$canEdit);
        $newPhotos = \array_filter($allPhotos, fn($p) => \strtotime($p['created_at']) > $since);

        header('Content-Type: application/json');
        echo json_encode(['count' => count($newPhotos), 'total' => count($allPhotos)]);
    }

    public function photoDelete(): void
    {
        Auth::requireLogin();

        if (!Auth::isMechanic()) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Brak uprawnień.']);
            return;
        }

        $photoId = (int)($_POST['photo_id'] ?? 0);
        $user    = Auth::user();
        $fm      = new FailureModel();
        $photo   = $fm->getPhotoById($photoId);

        // Mechanik może usunąć tylko swoje zdjęcia; admin może wszystkie
        if (
            !$photo ||
            (!Auth::isAdmin() && (int)$photo['user_id'] !== (int)$user['id'])
        ) {
            header('Content-Type: application/json');
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Zdjęcie nie istnieje lub brak dostępu.']);
            return;
        }

        $fullPath = BASE_PATH . '/' . $photo['path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $fm->deletePhoto($photoId);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
}

// ────────────────────────────────────────────────────────────
