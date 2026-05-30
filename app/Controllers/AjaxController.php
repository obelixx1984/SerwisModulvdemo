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

    private function jsonOut(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    public function notesGet(): void
    {
        Auth::requireLogin();
        $scheduleId = (int)($_GET['schedule_id'] ?? 0);
        if (!$scheduleId) {
            $this->jsonOut(['ok' => false, 'error' => 'Brak ID harmonogramu']);
        }
        $notes = (new \App\Models\ScheduleNoteModel())->getActiveBySchedule($scheduleId);
        $this->jsonOut(['ok' => true, 'notes' => $notes]);
    }

    public function noteAdd(): void
    {
        Auth::requireLogin();
        if (!Auth::hasPermission('dur') && !Auth::isMechanic()) {
            $this->jsonOut(['ok' => false, 'error' => 'Brak uprawnień']);
        }
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            $this->jsonOut(['ok' => false, 'error' => 'Błąd bezpieczeństwa']);
        }
        $scheduleId = (int)($_POST['schedule_id'] ?? 0);
        $note       = trim($_POST['note'] ?? '');
        if (!$scheduleId || !$note) {
            $this->jsonOut(['ok' => false, 'error' => 'Wpisz treść uwagi']);
        }
        $mm = new \App\Models\MaintenanceModel();
        if (!$mm->getScheduleById($scheduleId)) {
            $this->jsonOut(['ok' => false, 'error' => 'Harmonogram nie istnieje']);
        }
        $user = Auth::user();
        (new \App\Models\ScheduleNoteModel())->add($scheduleId, (int)$user['id'], $user['name'], $note);
        $notes = (new \App\Models\ScheduleNoteModel())->getActiveBySchedule($scheduleId);
        $this->jsonOut(['ok' => true, 'notes' => $notes]);
    }

    public function noteEdit(): void
    {
        Auth::requireLogin();
        if (!Auth::hasPermission('dur') && !Auth::isMechanic()) {
            $this->jsonOut(['ok' => false, 'error' => 'Brak uprawnień']);
        }
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            $this->jsonOut(['ok' => false, 'error' => 'Błąd bezpieczeństwa']);
        }
        $noteId = (int)($_POST['note_id'] ?? 0);
        $note   = trim($_POST['note'] ?? '');
        if (!$noteId || !$note) {
            $this->jsonOut(['ok' => false, 'error' => 'Nieprawidłowe dane']);
        }
        $nm       = new \App\Models\ScheduleNoteModel();
        $existing = $nm->getById($noteId);
        if (!$existing) {
            $this->jsonOut(['ok' => false, 'error' => 'Uwaga nie istnieje']);
        }
        $user = Auth::user();
        if ((int)$existing['user_id'] !== (int)$user['id']) {
            $this->jsonOut(['ok' => false, 'error' => 'Możesz edytować tylko swoje uwagi']);
        }
        if (!empty($existing['is_archived'])) {
            $this->jsonOut(['ok' => false, 'error' => 'Nie można edytować zarchiwizowanej uwagi']);
        }
        $nm->update($noteId, $note);
        $notes = (new \App\Models\ScheduleNoteModel())->getActiveBySchedule((int)$existing['schedule_id']);
        $this->jsonOut(['ok' => true, 'notes' => $notes]);
    }

    public function noteDelete(): void
    {
        Auth::requireLogin();
        if (!Auth::hasPermission('dur') && !Auth::isMechanic()) {
            $this->jsonOut(['ok' => false, 'error' => 'Brak uprawnień']);
        }
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            $this->jsonOut(['ok' => false, 'error' => 'Błąd bezpieczeństwa']);
        }
        $noteId = (int)($_POST['note_id'] ?? 0);
        $nm     = new \App\Models\ScheduleNoteModel();
        $existing = $nm->getById($noteId);
        if (!$existing) {
            $this->jsonOut(['ok' => false, 'error' => 'Uwaga nie istnieje']);
        }
        $user = Auth::user();
        if ((int)$existing['user_id'] !== (int)$user['id']) {
            $this->jsonOut(['ok' => false, 'error' => 'Możesz usuwać tylko swoje uwagi']);
        }
        $scheduleId = (int)$existing['schedule_id'];
        $nm->delete($noteId);
        $notes = (new \App\Models\ScheduleNoteModel())->getActiveBySchedule($scheduleId);
        $this->jsonOut(['ok' => true, 'notes' => $notes]);
    }
}
