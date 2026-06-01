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

class DurController
{
    public function list(): void
    {
        Auth::requireLogin();
        if (!Auth::hasPermission('dur') && !Auth::isMechanic()) {
            Helpers::flash('error', 'Brak uprawnień do przeglądów DUR.');
            Helpers::redirect('dashboard');
            return;
        }

        $mm      = new MaintenanceModel();
        $filters = [
            'line_id' => (int)($_GET['line_id'] ?? 0) ?: null,
            'status'  => $_GET['status'] ?? null,
            'type'    => $_GET['type'] ?? null,
        ];
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 18;

        $activeFilters = array_filter($filters);
        $total   = $mm->countAllReviews($activeFilters);
        $pager   = Helpers::paginate($total, $page, $perPage);
        $reviews = $mm->getAllReviews($activeFilters, $pager['per_page'], $pager['offset']);

        $durWarnDays = max(1, (int)((new SettingsModel())->get('dur_warning_days') ?? DUR_WARNING_DAYS));
        $upcoming    = $mm->getUpcomingSchedules($durWarnDays);
        $lines       = (new ProductionLineModel())->getAll(true);
        $templates   = $mm->getTemplates();

        // Liczba uwag per harmonogram
        $noteCounts = [];
        if (!empty($upcoming)) {
            $scheduleIds = array_column($upcoming, 'id');
            $noteCounts  = (new ScheduleNoteModel())->countActiveGrouped($scheduleIds);
        }

        require BASE_PATH . '/templates/shared/dur_list.php';
    }

    public function addForm(): void
    {
        Auth::requireMechanic();
        $lines     = (new ProductionLineModel())->getAll(true);
        $templates = (new MaintenanceModel())->getTemplates();

        $activeTypes = ['weekly', 'monthly', 'quarterly', 'biannual', 'annual', 'ad_hoc'];
        $saved = (new SettingsModel())->get('dur_active_review_types');
        if ($saved) {
            $decoded = json_decode($saved, true);
            if (is_array($decoded) && $decoded) $activeTypes = $decoded;
        }

        // Uwagi dla preselected linii i typu
        $scheduleNotes = [];
        $preSchedule   = null;
        $preLineId     = (int)($_GET['line_id'] ?? 0);
        $preType       = $_GET['review_type'] ?? '';

        if ($preLineId && $preType) {
            $mm          = new MaintenanceModel();
            $preSchedule = $mm->findScheduleByLineAndType($preLineId, $preType);
            if ($preSchedule) {
                $scheduleNotes = (new ScheduleNoteModel())->getActiveBySchedule($preSchedule['id']);
            }
        }

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
        $nextDate    = trim($_POST['next_review_date'] ?? '');
        $parts       = trim($_POST['parts_used'] ?? '');
        $notes       = trim($_POST['notes'] ?? '');

        if (!$lineId || !$activities || !$reviewDate) {
            Helpers::flash('error', 'Wypełnij wymagane pola: linia, data, czynności.');
            Helpers::redirect('dur_add');
        }

        $user = Auth::user();
        $mm   = new MaintenanceModel();

        $id = $mm->create([
            'production_line_id' => $lineId,
            'subsystem_id'       => $subsysId,
            'template_id'        => $templateId,
            'schedule_id'        => null,
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

        // ── ZMIANA 2: zaktualizuj next_due_date w harmonogramie ──
        $schedule = $mm->findScheduleByLineAndType($lineId, $reviewType);
        if ($schedule) {
            if ($nextDate) {
                // Użytkownik podał datę następnego przeglądu
                $updatedNextDate = $nextDate;
            } else {
                // Oblicz na podstawie interval_days harmonogramu
                $updatedNextDate = date(
                    'Y-m-d',
                    strtotime($reviewDate . ' + ' . (int)$schedule['interval_days'] . ' days')
                );
            }
            $mm->updateScheduleNextDate($schedule['id'], $updatedNextDate);

            // Archiwizuj uwagi — przypisz do właśnie zapisanego raportu
            (new ScheduleNoteModel())->archiveForReview($schedule['id'], $id);
        }
        // ─────────────────...
        Helpers::flash('success', 'Raport DUR zapisany pomyślnie.');
        $actionAfter = $_POST['action_after'] ?? 'list';
        if ($actionAfter === 'parts') {
            Helpers::redirect('dur_edit', ['id' => $id, 'parts' => '1']);
        } else {
            Helpers::redirect('dur');
        }
    }

    public function scheduleNoteAdd(): void
    {
        Auth::requireLogin();
        if (!Auth::hasPermission('dur') && !Auth::isMechanic()) {
            Helpers::flash('error', 'Brak uprawnień.');
            Helpers::redirect('dur');
            return;
        }
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('dur');
            return;
        }

        $scheduleId = (int)($_POST['schedule_id'] ?? 0);
        $note       = trim($_POST['note'] ?? '');

        if (!$scheduleId || !$note) {
            Helpers::flash('error', 'Wpisz treść uwagi.');
            Helpers::redirect('dur');
            return;
        }

        $schedule = (new MaintenanceModel())->getScheduleById($scheduleId);
        if (!$schedule) {
            Helpers::flash('error', 'Harmonogram nie istnieje.');
            Helpers::redirect('dur');
            return;
        }

        $user = Auth::user();
        (new ScheduleNoteModel())->add($scheduleId, (int)$user['id'], $user['name'], $note);

        Helpers::flash('success', 'Uwaga została dodana.');
        $returnTo = $_POST['return_to'] ?? 'dur';
        Helpers::redirect(in_array($returnTo, ['dur', 'dashboard']) ? $returnTo : 'dur');
    }

    public function scheduleNoteEdit(): void
    {
        Auth::requireLogin();
        if (!Auth::hasPermission('dur') && !Auth::isMechanic()) {
            Helpers::flash('error', 'Brak uprawnień.');
            Helpers::redirect('dur');
            return;
        }
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('dur');
            return;
        }

        $noteId = (int)($_POST['note_id'] ?? 0);
        $note   = trim($_POST['note'] ?? '');

        if (!$noteId || !$note) {
            Helpers::flash('error', 'Nieprawidłowe dane.');
            Helpers::redirect('dur');
            return;
        }

        $nm       = new ScheduleNoteModel();
        $existing = $nm->getById($noteId);

        if (!$existing) {
            Helpers::flash('error', 'Uwaga nie istnieje.');
            Helpers::redirect('dur');
            return;
        }

        $user = Auth::user();
        if ((int)$existing['user_id'] !== (int)$user['id']) {
            Helpers::flash('error', 'Możesz edytować tylko swoje uwagi.');
            Helpers::redirect('dur');
            return;
        }

        if (!empty($existing['is_archived'])) {
            Helpers::flash('error', 'Nie można edytować zarchiwizowanej uwagi.');
            Helpers::redirect('dur');
            return;
        }

        $nm->update($noteId, $note);
        Helpers::flash('success', 'Uwaga zaktualizowana.');
        $returnTo = $_POST['return_to'] ?? 'dur';
        Helpers::redirect(in_array($returnTo, ['dur', 'dashboard']) ? $returnTo : 'dur');
    }

    public function scheduleNoteDelete(): void
    {
        Auth::requireLogin();
        if (!Auth::hasPermission('dur') && !Auth::isMechanic()) {
            Helpers::flash('error', 'Brak uprawnień.');
            Helpers::redirect('dur');
            return;
        }
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('dur');
            return;
        }

        $noteId   = (int)($_POST['note_id'] ?? 0);
        $nm       = new ScheduleNoteModel();
        $existing = $nm->getById($noteId);

        if (!$existing) {
            Helpers::redirect('dur');
            return;
        }

        $user = Auth::user();
        if ((int)$existing['user_id'] !== (int)$user['id']) {
            Helpers::flash('error', 'Możesz usuwać tylko swoje uwagi.');
            Helpers::redirect('dur');
            return;
        }

        if (!empty($existing['is_archived'])) {
            Helpers::flash('error', 'Nie można usunąć zarchiwizowanej uwagi.');
            Helpers::redirect('dur');
            return;
        }

        $nm->delete($noteId);
        Helpers::flash('success', 'Uwaga usunięta.');
        $returnTo = $_POST['return_to'] ?? 'dur';
        Helpers::redirect(in_array($returnTo, ['dur', 'dashboard']) ? $returnTo : 'dur');
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
        $durSpareParts = (new SparePartModel())->getByReview($id);
        require BASE_PATH . '/templates/shared/dur_detail.php';
    }

    /**
     * Formularz edycji raportu DUR.
     * Dostępny tylko dla autora raportu z uprawnieniem 'dur'.
     */
    public function editForm(): void
    {
        Auth::requireLogin();
        if (!Auth::hasPermission('dur')) {
            Helpers::flash('error', 'Brak uprawnień do przeglądów DUR.');
            Helpers::redirect('dur');
            return;
        }

        $id     = (int)($_GET['id'] ?? 0);
        $review = (new MaintenanceModel())->getById($id);

        if (!$review) {
            require BASE_PATH . '/templates/shared/404.php';
            return;
        }

        $user = Auth::user();
        if ((int)$review['performed_by'] !== (int)$user['id']) {
            Helpers::flash('error', 'Możesz edytować tylko własne raporty DUR.');
            Helpers::redirect('dur_detail', ['id' => $id]);
            return;
        }

        $durSpareParts       = (new SparePartModel())->getByReview($id);
        $sparePartCategories = (new SparePartCategoryModel())->getAll();
        require BASE_PATH . '/templates/shared/dur_edit.php';
    }

    /**
     * Obsługa POST z formularza edycji raportu DUR.
     */
    public function editPost(): void
    {
        Auth::requireLogin();
        if (!Auth::hasPermission('dur')) {
            Helpers::flash('error', 'Brak uprawnień.');
            Helpers::redirect('dur');
            return;
        }
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('dur');
            return;
        }

        $id         = (int)($_POST['review_id'] ?? 0);
        $activities = trim($_POST['activities'] ?? '');
        $reviewDate = trim($_POST['review_date'] ?? '');
        $duration   = !empty($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : null;
        $status     = in_array($_POST['status'] ?? '', ['completed', 'partial', 'interrupted'])
            ? $_POST['status'] : 'completed';
        $nextDate   = trim($_POST['next_review_date'] ?? '');
        $parts      = trim($_POST['parts_used'] ?? '');
        $notes      = trim($_POST['notes'] ?? '');

        if (!$id || !$activities || !$reviewDate) {
            Helpers::flash('error', 'Wypełnij wymagane pola: data, czynności.');
            Helpers::redirect('dur_edit', ['id' => $id]);
            return;
        }

        $mm     = new MaintenanceModel();
        $review = $mm->getById($id);

        if (!$review) {
            Helpers::flash('error', 'Raport nie istnieje.');
            Helpers::redirect('dur');
            return;
        }

        $user = Auth::user();
        if ((int)$review['performed_by'] !== (int)$user['id']) {
            Helpers::flash('error', 'Możesz edytować tylko własne raporty DUR.');
            Helpers::redirect('dur_detail', ['id' => $id]);
            return;
        }

        $mm->update($id, [
            'review_date'      => $reviewDate,
            'duration_minutes' => $duration,
            'activities'       => $activities,
            'parts_used'       => $parts ?: null,
            'notes'            => $notes ?: null,
            'status'           => $status,
            'next_review_date' => $nextDate ?: null,
        ]);

        // Zaktualizuj harmonogram jeśli podano datę następnego przeglądu
        if ($nextDate) {
            $schedule = $mm->findScheduleByLineAndType(
                (int)$review['production_line_id'],
                $review['review_type']
            );
            if ($schedule) {
                $mm->updateScheduleNextDate($schedule['id'], $nextDate);
            }
        }

        Helpers::flash('success', 'Raport DUR zaktualizowany pomyślnie.');
        Helpers::redirect('dur_detail', ['id' => $id]);
    }

    public function sparePartAdd(): void
    {
        Auth::requireMechanic();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('dur');
        }
        $reviewId   = (int)($_POST['review_id']   ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $partName   = trim($_POST['part_name']    ?? '');
        $quantity   = max(1, (int)($_POST['quantity'] ?? 1));

        if (!$reviewId || !$categoryId || !$partName) {
            Helpers::flash('error', 'Wypełnij wszystkie wymagane pola części zamiennej.');
            Helpers::redirect('dur_edit', ['id' => $reviewId, 'parts' => '1']);
        }
        $user = Auth::user();
        (new SparePartModel())->createForReview([
            'review_id'   => $reviewId,
            'category_id' => $categoryId,
            'part_name'   => $partName,
            'quantity'    => $quantity,
            'added_by'    => $user['id'],
        ]);
        Helpers::flash('success', 'Część dodana.');
        Helpers::redirect('dur_edit', ['id' => $reviewId, 'parts' => '1']);
    }

    public function sparePartDelete(): void
    {
        Auth::requireMechanic();
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa.');
            Helpers::redirect('dur');
        }
        $spareId  = (int)($_POST['spare_id']  ?? 0);
        $reviewId = (int)($_POST['review_id'] ?? 0);

        if ($spareId > 0) {
            (new SparePartModel())->deleteFromReview($spareId);
            Helpers::flash('success', 'Część usunięta.');
        }
        Helpers::redirect('dur_edit', ['id' => $reviewId, 'parts' => '1']);
    }
}

// ────────────────────────────────────────────────────────────
