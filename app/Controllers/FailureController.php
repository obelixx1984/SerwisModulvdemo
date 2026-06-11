<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Helpers;
use App\Models\{
    CategoryModel,
    DictionaryModel,
    ProductionLineModel,
    StatusModel,
    UserModel,
    SparePartModel,
    SparePartCategoryModel,
    SymptomModel,
    AssignmentModel,
    MaintenanceModel,
    ScheduleNoteModel,
    SettingsModel
};
use App\Services\FailureService;
use App\DTOs\FailureFiltersDTO;

/**
 * FailureController — obsługa żądań HTTP dla modułu zgłoszeń awarii.
 *
 * Każda metoda akcji ma maksymalnie 5 linii logiki:
 *   1. Autoryzacja
 *   2. Pobranie danych wejściowych
 *   3. Wywołanie serwisu
 *   4. Odpowiedź (redirect / render szablonu)
 *
 * Cała logika biznesowa i SQL są w FailureService i PdoFailureRepository.
 */
class FailureController
{
    /**
     * Wszystkie zależności wstrzykiwane przez DI Container.
     * Kontroler nie tworzy obiektów — tylko używa.
     */
    public function __construct(
        private readonly FailureService          $svc,
        private readonly CategoryModel           $categories,
        private readonly DictionaryModel         $dictionary,
        private readonly ProductionLineModel     $lines,
        private readonly StatusModel             $statuses,
        private readonly UserModel               $users,
        private readonly SparePartModel          $spareParts,
        private readonly SparePartCategoryModel  $sparePartCats,
        private readonly SymptomModel            $symptoms,
        private readonly AssignmentModel         $assignments,
        private readonly MaintenanceModel        $maintenance,
        private readonly ScheduleNoteModel       $scheduleNotes,
        private readonly SettingsModel           $settings
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Dashboard
    // ─────────────────────────────────────────────────────────────────────────

    public function dashboard(): void
    {
        Auth::requireLogin();
        $statuses  = $this->statuses->getAll(true);
        $dashboard = $this->svc->getDashboardData($statuses);
        $recent    = $this->svc->getPaginatedList([], 1, 6)['items'];
        $this->renderDashboardExtras($dashboard, $recent, $statuses);
    }

    /**
     * Ładuje dodatkowe dane dla dashboardu i renderuje szablon.
     * Wydzielone żeby dashboard() miał max 5 linii.
     *
     * @param array<string, mixed>   $dashboard
     * @param array<int, mixed>      $recent
     * @param array<int, mixed>      $statuses
     */
    private function renderDashboardExtras(array $dashboard, array $recent, array $statuses): void
    {
        $durWarnDays = max(1, (int)($this->settings->get('dur_warning_days') ?? DUR_WARNING_DAYS));
        $upcoming    = $this->maintenance->getUpcomingSchedules($durWarnDays);
        $scheduleIds = !empty($upcoming) ? array_column($upcoming, 'id') : [];
        $noteCounts  = $scheduleIds ? $this->scheduleNotes->countActiveGrouped($scheduleIds) : [];

        // Wypakuj klucze dashboardu jako zmienne — nazwy muszą odpowiadać temu co używa szablon
        $stats          = $dashboard['stats'];
        $byStatus       = $dashboard['byStatus'];
        $last30Count    = $dashboard['last30Count'];
        $avgRepairAll   = $dashboard['avg_repair_all'];

        require BASE_PATH . '/templates/shared/dashboard.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Lista zgłoszeń
    // ─────────────────────────────────────────────────────────────────────────

    public function list(): void
    {
        Auth::requireLogin();
        $this->guardPermission('failures', 'failures');

        $dto    = FailureFiltersDTO::fromGet($_GET);
        $result = $this->svc->getPaginatedList($dto->toArray(), $dto->page, RECORDS_PER_PAGE);
        $this->renderList($result, $dto->toArray());
    }

    /** @param array<string, mixed> $result */
    private function renderList(array $result, array $filters): void
    {
        ['items' => $items, 'pager' => $pager] = $result;
        $lines      = $this->lines->getAll(true);
        $categories = $this->categories->getAll(true);
        $statuses   = $this->statuses->getAll(true);
        require BASE_PATH . '/templates/shared/failures_list.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Szczegóły zgłoszenia
    // ─────────────────────────────────────────────────────────────────────────

    public function detail(): void
    {
        Auth::requireLogin();
        $failure = $this->svc->getById((int)($_GET['id'] ?? 0));
        if (!$failure) {
            require BASE_PATH . '/templates/shared/404.php';
            return;
        }
        $this->renderDetail($failure);
    }

    /** @param array<string, mixed> $failure */
    private function renderDetail(array $failure): void
    {
        $user       = Auth::user();
        $canEdit    = Auth::isMechanic() || Auth::hasPermission('statuses');
        $isReporter = (int)($failure['reporter_user_id'] ?? 0) === (int)$user['id'];
        $obsWindow  = $this->svc->getObservationWindow($failure);
        [$isObservationActive, $observationSecondsLeft] = [$obsWindow['active'], $obsWindow['seconds_left']];

        $history             = $this->svc->getHistory($failure['id']);
        $comments            = $this->svc->getComments($failure['id']);
        $observationNotes    = $this->svc->getObservationNotes($failure['id']);
        $hasAnyObservationNotes = !empty($observationNotes);
        $photos              = $this->svc->getPhotos($failure['id'], !$canEdit);
        $statuses            = $this->statuses->getAll(true);
        $categories          = $this->categories->getAll(true);
        $dictionary          = $this->dictionary->getActive();
        $spareParts          = $this->spareParts->getByFailure((int)$failure['id']);
        $sparePartCategories = $this->sparePartCats->getAll(true);
        $assignments         = $this->assignments->getByFailure($failure['id']);
        $mechanics           = $this->users->getMechanics();
        $isLeader            = $this->assignments->isLeader($failure['id'], (int)$user['id']);
        $hasLeader           = !empty(array_filter($assignments, fn($a) => !empty($a['is_first'])));
        $symptoms            = $this->symptoms->getActive();

        require BASE_PATH . '/templates/shared/failure_detail.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Zmiana statusu
    // ─────────────────────────────────────────────────────────────────────────

    public function changeStatus(): void
    {
        Auth::requireMechanic();
        $this->verifyCsrf();
        $failure = $this->requireFailure((int)($_POST['failure_id'] ?? 0), 'failures');
        $user    = Auth::user();

        try {
            $this->svc->changeStatus(
                $failure,
                (int)($_POST['status_id'] ?? 0),
                (int)$user['id'],
                $user['name'],
                trim($_POST['note'] ?? '')
            );
            Helpers::flash('success', 'Status zmieniony.');
        } catch (\InvalidArgumentException $e) {
            Helpers::flash('error', $e->getMessage());
        }

        Helpers::redirect('failure_detail', ['id' => $failure['id']]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Ustawianie kategorii i usterki
    // ─────────────────────────────────────────────────────────────────────────

    public function setCategory(): void
    {
        Auth::requireMechanic();
        $this->verifyCsrf();
        $failure = $this->requireFailure((int)($_POST['failure_id'] ?? 0), 'failures');
        $this->guardFinalStatus($failure, 'failure_detail', ['id' => $failure['id']]);

        $otherFailure = !empty($_POST['other_failure']) ? 1 : 0;
        $mechanicNote = trim($_POST['mechanic_note'] ?? '');

        if ($otherFailure && !$mechanicNote) {
            Helpers::flash('error', 'Przy "Inna usterka" musisz wpisać notatkę mechanika.');
            Helpers::redirect('failure_detail', ['id' => $failure['id']]);
        }

        $this->svc->setCategory($failure['id'], [
            'category_id'        => !empty($_POST['category_id'])        ? (int)$_POST['category_id']        : null,
            'dictionary_item_id' => !empty($_POST['dictionary_item_id']) ? (int)$_POST['dictionary_item_id'] : null,
            'other_failure'      => $otherFailure,
            'mechanic_note'      => $mechanicNote,
        ]);
        $user = Auth::user();
        $this->svc->addHistory($failure['id'], $user['id'], 'edited', null, null, $user['name'], 'Ustawiono kategorię i usterkę');
        Helpers::flash('success', 'Kategoria i usterka zapisane.');
        Helpers::redirect('failure_detail', ['id' => $failure['id']]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Usuwanie zgłoszenia
    // ─────────────────────────────────────────────────────────────────────────

    public function deleteFailure(): void
    {
        Auth::requireLogin();
        if (!Auth::hasAdminPermission()) {
            Helpers::flash('error', 'Brak uprawnień.');
            Helpers::redirect('failures');
        }
        $this->verifyCsrf();
        $failure = $this->svc->getById((int)($_POST['failure_id'] ?? 0));
        if ($failure) {
            $this->svc->delete($failure['id']);
            Helpers::flash('success', 'Zgłoszenie usunięte.');
        }
        Helpers::redirect('failures');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Komentarze
    // ─────────────────────────────────────────────────────────────────────────

    public function addComment(): void
    {
        Auth::requireMechanic();
        $this->verifyCsrf();
        $id      = (int)($_POST['failure_id'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        if (!$id || !$comment) {
            Helpers::flash('error', 'Komentarz nie może być pusty.');
            Helpers::redirect('failure_detail', ['id' => $id]);
        }
        $user = Auth::user();
        $this->svc->addComment($id, $user['id'], $user['name'], $comment);
        Helpers::flash('success', 'Komentarz dodany.');
        Helpers::redirect('failure_detail', ['id' => $id]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Uwagi do okna obserwacji
    // ─────────────────────────────────────────────────────────────────────────

    public function addObservationNote(): void
    {
        Auth::requireLogin();
        $this->verifyCsrf();
        $id      = (int)($_POST['failure_id'] ?? 0);
        $note    = trim($_POST['note'] ?? '');
        $failure = $this->requireFailure($id, 'failures');
        $obsWin  = $this->svc->getObservationWindow($failure);
        if (!$obsWin['active']) {
            Helpers::flash('error', 'Czas okna obserwacji upłynął.');
            Helpers::redirect('failure_detail', ['id' => $id]);
        }
        if (!$note) {
            Helpers::flash('error', 'Uwaga nie może być pusta.');
            Helpers::redirect('failure_detail', ['id' => $id]);
        }
        $user = Auth::user();
        $this->svc->addObservationNote($id, (int)$user['id'], $user['name'], $note);
        Helpers::flash('success', 'Uwaga dodana.');
        Helpers::redirect('failure_detail', ['id' => $id]);
    }

    public function deleteObservationNote(): void
    {
        Auth::requireLogin();
        $this->verifyCsrf();
        $noteId    = (int)($_POST['note_id']    ?? 0);
        $failureId = (int)($_POST['failure_id'] ?? 0);
        $note      = $this->svc->getObservationNoteById($noteId);
        if (!$note || (int)$note['failure_id'] !== $failureId) {
            Helpers::flash('error', 'Nie znaleziono uwagi.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
        }
        $user = Auth::user();
        if ((int)$note['user_id'] !== (int)$user['id'] && !Auth::isAdmin()) {
            Helpers::flash('error', 'Możesz usuwać tylko własne uwagi.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
        }
        $failure = $this->requireFailure($failureId, 'failures');
        $obsWin  = $this->svc->getObservationWindow($failure);
        if (!$obsWin['active']) {
            Helpers::flash('error', 'Czas obserwacji upłynął.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
        }
        $this->svc->deleteObservationNote($noteId);
        Helpers::flash('success', 'Uwaga usunięta.');
        Helpers::redirect('failure_detail', ['id' => $failureId]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Części zamienne
    // ─────────────────────────────────────────────────────────────────────────

    public function sparePartAdd(): void
    {
        Auth::requireLogin();
        $this->verifyCsrf();
        $failureId  = (int)($_POST['failure_id']  ?? 0);
        if (!Auth::hasPermission('statuses')) {
            Helpers::flash('error', 'Brak uprawnień.');
            Helpers::redirect('failure_detail&id=' . $failureId);
        }
        $failure    = $this->requireFailure($failureId, 'failures');
        $this->guardFinalStatus($failure, 'failure_detail', ['id' => $failureId]);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $partName   = trim($_POST['part_name']    ?? '');
        $quantity   = max(1, (int)($_POST['quantity'] ?? 1));
        if (!$categoryId || !$partName) {
            Helpers::flash('error', 'Wypełnij wszystkie pola.');
            Helpers::redirect('failure_detail&id=' . $failureId);
        }
        $this->spareParts->create(['failure_id' => $failureId, 'category_id' => $categoryId, 'part_name' => $partName, 'quantity' => $quantity, 'added_by' => (int)Auth::user()['id']]);
        Helpers::flash('success', 'Część zamienna dodana.');
        Helpers::redirect('failure_detail&id=' . $failureId);
    }

    public function sparePartDelete(): void
    {
        Auth::requireLogin();
        $this->verifyCsrf();
        $spareId   = (int)($_POST['spare_id']   ?? 0);
        $failureId = (int)($_POST['failure_id'] ?? 0);
        if (!Auth::hasPermission('statuses')) {
            Helpers::flash('error', 'Brak uprawnień.');
            Helpers::redirect('failure_detail&id=' . $failureId);
        }
        $failure   = $this->requireFailure($failureId, 'failures');
        $this->guardFinalStatus($failure, 'failure_detail', ['id' => $failureId]);
        if ($spareId > 0) {
            $this->spareParts->delete($spareId);
            Helpers::flash('success', 'Część usunięta.');
        }
        Helpers::redirect('failure_detail&id=' . $failureId);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Obsada zgłoszenia
    // ─────────────────────────────────────────────────────────────────────────

    public function addAssignment(): void
    {
        Auth::requireMechanic();
        $this->verifyCsrf();
        $failureId = (int)($_POST['failure_id'] ?? 0);
        $userId    = (int)($_POST['user_id']    ?? 0);
        $failure   = $this->requireFailure($failureId, 'failures');
        $this->guardFinalStatus($failure, 'failure_detail', ['id' => $failureId]);
        if (!$userId) {
            Helpers::flash('error', 'Wybierz osobę z listy.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
        }
        $this->doAddAssignment($failureId, $userId, $failure);
    }

    /** @param array<string, mixed> $failure */
    private function doAddAssignment(int $failureId, int $userId, array $failure): void
    {
        $addUser = $this->users->getById($userId);
        if (!$addUser || $addUser['role_name'] !== 'mechanic') {
            Helpers::flash('error', 'Tylko mechanicy mogą być w obsadzie.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
        }
        if ($this->assignments->isInCrew($failureId, $userId)) {
            Helpers::flash('error', Helpers::e($addUser['name']) . ' już jest w obsadzie.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
        }
        $currentUser = Auth::user();
        if (!$this->assignments->isLeader($failureId, (int)$currentUser['id'])) {
            Helpers::flash('error', 'Tylko prowadzący może modyfikować obsadę.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
        }
        $this->assignments->addMember($failureId, $userId, $addUser['name'], false, (int)$currentUser['id']);
        $this->svc->addHistory($failureId, (int)$currentUser['id'], 'crew_added', null, null, $currentUser['name'], 'Dodano do obsady: ' . $addUser['name']);
        Helpers::flash('success', Helpers::e($addUser['name']) . ' dodany do obsady.');
        Helpers::redirect('failure_detail', ['id' => $failureId]);
    }

    public function removeAssignment(): void
    {
        Auth::requireMechanic();
        $this->verifyCsrf();
        $assignId  = (int)($_POST['assignment_id'] ?? 0);
        $failureId = (int)($_POST['failure_id']    ?? 0);
        $assignment = $this->assignments->getById($assignId);
        if (!$assignment || (int)$assignment['failure_id'] !== $failureId) {
            Helpers::flash('error', 'Nie znaleziono obsady.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
        }
        $this->doRemoveAssignment($failureId, $assignId, $assignment);
    }

    /** @param array<string, mixed> $assignment */
    private function doRemoveAssignment(int $failureId, int $assignId, array $assignment): void
    {
        if (!empty($assignment['is_first'])) {
            Helpers::flash('error', 'Nie można usunąć prowadzącego.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
        }
        $currentUser = Auth::user();
        if (!$this->assignments->isLeader($failureId, (int)$currentUser['id'])) {
            Helpers::flash('error', 'Tylko prowadzący może modyfikować obsadę.');
            Helpers::redirect('failure_detail', ['id' => $failureId]);
        }
        $failure = $this->requireFailure($failureId, 'failures');
        $this->guardFinalStatus($failure, 'failure_detail', ['id' => $failureId]);
        $this->assignments->removeMember($assignId);
        $this->svc->addHistory($failureId, (int)$currentUser['id'], 'crew_removed', null, null, $currentUser['name'], 'Usunięto z obsady: ' . $assignment['user_name']);
        Helpers::flash('success', Helpers::e($assignment['user_name']) . ' usunięty z obsady.');
        Helpers::redirect('failure_detail', ['id' => $failureId]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Zdjęcia — upload i usunięcie (logika procesowania GD pozostaje tu,
    //           bo to infrastruktura HTTP a nie logika biznesowa)
    // ─────────────────────────────────────────────────────────────────────────

    public function photoUpload(): void
    {
        Auth::requireLogin();
        if (!Auth::isMechanic()) {
            $this->jsonError(403, 'Brak uprawnień.');
            return;
        }
        $failureId = (int)($_POST['failure_id'] ?? 0);
        $failure   = $this->svc->getById($failureId);
        if (!$failure) {
            $this->jsonError(404, 'Zgłoszenie nie istnieje.');
            return;
        }
        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $this->jsonError(400, 'Błąd przesyłania pliku.');
            return;
        }
        if ($_FILES['photo']['size'] > 6 * 1024 * 1024) {
            $this->jsonError(400, 'Plik przekracza 6 MB.');
            return;
        }
        $this->processPhotoUpload($failure, (int)($_POST['is_public'] ?? 0) === 1 ? 1 : 0);
    }

    /** @param array<string, mixed> $failure */
    private function processPhotoUpload(array $failure, int $isPublic): void
    {
        $user    = Auth::user();
        $src     = $this->loadImageFromUpload($_FILES['photo']['tmp_name']);
        if (!$src) {
            $this->jsonError(400, 'Nieobsługiwany format obrazu (JPEG, PNG, WEBP).');
            return;
        }
        $src     = $this->resizeIfNeeded($src, 1920);
        $dir     = BASE_PATH . '/foto/' . $user['login'] . '/' . $failure['ticket_number'] . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = uniqid('', true) . '.jpg';
        imagejpeg($src, $dir . $filename, 80);
        imagedestroy($src);
        $dbPath   = 'foto/' . $user['login'] . '/' . $failure['ticket_number'] . '/' . $filename;
        $photoId  = $this->svc->addPhoto(['failure_id' => $failure['id'], 'user_id' => (int)$user['id'], 'username' => $user['login'], 'filename' => $filename, 'path' => $dbPath, 'filesize' => (int)filesize($dir . $filename), 'is_public' => $isPublic]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'id' => $photoId, 'url' => BASE_URL . '/' . $dbPath, 'filesize' => (int)filesize($dir . $filename)]);
    }

    /** Ładuje obrazek GD z pliku tymczasowego. */
    private function loadImageFromUpload(string $tmpPath): mixed
    {
        $mime = mime_content_type($tmpPath);
        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($tmpPath),
            'image/png'  => imagecreatefrompng($tmpPath),
            'image/webp' => imagecreatefromwebp($tmpPath),
            default      => null,
        };
    }

    /** Skaluje obraz GD jeśli przekracza maxDim px. */
    private function resizeIfNeeded(mixed $src, int $maxDim): mixed
    {
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w <= $maxDim && $h <= $maxDim) return $src;
        $ratio = min($maxDim / $w, $maxDim / $h);
        $nw    = (int)round($w * $ratio);
        $nh    = (int)round($h * $ratio);
        $dst   = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);
        return $dst;
    }

    public function photoDelete(): void
    {
        Auth::requireLogin();
        if (!Auth::isMechanic()) {
            $this->jsonError(403, 'Brak uprawnień.');
            return;
        }
        $photoId = (int)($_POST['photo_id'] ?? 0);
        $photo   = $this->svc->getPhotoById($photoId);
        $user    = Auth::user();
        if (!$photo || (!Auth::isAdmin() && (int)$photo['user_id'] !== (int)$user['id'])) {
            $this->jsonError(404, 'Zdjęcie nie istnieje lub brak dostępu.');
            return;
        }
        $fullPath = BASE_PATH . '/' . $photo['path'];
        if (file_exists($fullPath)) unlink($fullPath);
        $this->svc->deletePhoto($photoId);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    public function photoBridgeQr(): void
    {
        Auth::requireLogin();
        if (!Auth::isMechanic()) {
            $this->jsonError(403, 'Brak uprawnień.');
            return;
        }
        $failureId = (int)($_POST['failure_id'] ?? 0);
        $failure   = $this->svc->getById($failureId);
        if (!$failure) {
            $this->jsonError(404, 'Zgłoszenie nie istnieje.');
            return;
        }
        $user  = Auth::user();
        $token = (new \App\Models\BridgeModel())->generateQrToken($user['login'], $failure['ticket_number']);
        if (!$token) {
            $this->jsonError(500, 'Błąd połączenia z mostem.');
            return;
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'qr_token' => $token, 'ticket' => $failure['ticket_number']]);
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
        $this->importBridgePhotos($failureId, $since);
    }

    private function importBridgePhotos(int $failureId, int $since): void
    {
        $bm      = new \App\Models\BridgeModel();
        $failure = $this->svc->getById($failureId);
        $user    = Auth::user();

        if ($bm->login()) {
            foreach ($bm->getPhotos() as $photo) {
                $dl = $bm->downloadPhoto((int)$photo['id']);
                if (empty($dl['body'])) continue;
                $tmp = tempnam(sys_get_temp_dir(), 'bridge_');
                file_put_contents($tmp, $dl['body']);
                $src = $this->loadImageFromUpload($tmp);
                unlink($tmp);
                if (!$src) continue;
                $src      = $this->resizeIfNeeded($src, 1920);
                $dir      = BASE_PATH . '/foto/' . $user['login'] . '/' . $failure['ticket_number'] . '/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $filename = uniqid('mob_', true) . '.jpg';
                imagejpeg($src, $dir . $filename, 80);
                imagedestroy($src);
                $this->svc->addPhoto(['failure_id' => $failureId, 'user_id' => (int)$user['id'], 'username' => $user['login'], 'filename' => $filename, 'path' => 'foto/' . $user['login'] . '/' . $failure['ticket_number'] . '/' . $filename, 'filesize' => (int)filesize($dir . $filename), 'is_public' => (int)$photo['is_public']]);
            }
        }

        $canEdit   = Auth::isMechanic();
        $allPhotos = $this->svc->getPhotos($failureId, !$canEdit);
        $newPhotos = array_filter($allPhotos, fn($p) => strtotime($p['created_at']) > $since);
        header('Content-Type: application/json');
        echo json_encode(['count' => count($newPhotos), 'total' => count($allPhotos)]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Prywatne helpery — eliminacja powtarzającego się kodu
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Weryfikuje CSRF token lub przekierowuje z błędem.
     */
    private function verifyCsrf(): void
    {
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('failures');
        }
    }

    /**
     * Pobiera zgłoszenie po ID lub przekierowuje z błędem 404.
     *
     * @return array<string, mixed>
     */
    private function requireFailure(int $id, string $redirectRoute): array
    {
        $failure = $this->svc->getById($id);
        if (!$failure) {
            Helpers::redirect($redirectRoute);
        }
        return $failure;
    }

    /**
     * Blokuje akcję jeśli zgłoszenie jest zamknięte.
     *
     * @param array<string, mixed> $failure
     * @param array<string, mixed> $params
     */
    private function guardFinalStatus(array $failure, string $route, array $params = []): void
    {
        if (!empty($failure['status_is_final'])) {
            Helpers::flash('error', 'Zgłoszenie jest zamknięte — operacja niedozwolona.');
            Helpers::redirect($route, $params);
        }
    }

    /**
     * Sprawdza uprawnienie lub przekierowuje z błędem.
     */
    private function guardPermission(string $permission, string $redirectRoute): void
    {
        if (!Auth::isMechanic() && !Auth::hasPermission($permission)) {
            Helpers::flash('error', 'Brak uprawnień.');
            Helpers::redirect($redirectRoute);
        }
    }

    /**
     * Wysyła odpowiedź JSON z kodem błędu i kończy skrypt.
     */
    private function jsonError(int $code, string $message): void
    {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode(['success' => false, 'message' => $message]);
    }
}

/*
 * ============================================================
 * DOKUMENTACJA PLIKU: FailureController.php (zrefaktoryzowany)
 * ============================================================
 * Plik:        app/Controllers/FailureController.php
 * Opis:        Kontroler HTTP dla modułu awarii — max 5 linii per metoda akcji
 * Zależności:  FailureService, wszystkie modele wstrzykiwane przez DI
 * Uwagi:       Konstruktor przyjmuje zależności — wymaga DI Container.
 *              Zarejestruj w config/bindings.php jako FailureController::class.
 *              Metody prywatne (renderDetail, doAddAssignment itp.) to
 *              wydzielone fragmenty — nie liczą się do limitu 5 linii akcji.
 * ============================================================
 */
