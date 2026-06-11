<?php

declare(strict_types=1);

namespace App\Controllers;

use App\DTOs\CreateFailureDTO;
use App\Helpers\Auth;
use App\Helpers\Helpers;
use App\Models\{
    ProductionLineModel,
    StatusModel,
    FailureModel,
    MaintenanceModel,
    SettingsModel,
    SymptomModel
};
use App\Validators\FailureValidator;

class PublicController
{
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
                if ((int)$l['id'] === $selectedLineId) { $currentLine = $l; break; }
            }
            $fm          = new FailureModel();
            $mm          = new MaintenanceModel();
            $lineHistory = $fm->getLineHistory($selectedLineId, 30);
            $lineStats   = $fm->getLineStats($selectedLineId, 30);
            $lineDur     = $mm->getReviewsByLine($selectedLineId, 3);

            if (!empty($_GET['symptom_id'])) {
                $duplicate = $fm->findOpenDuplicate($selectedLineId, (int)$_GET['symptom_id']);
            }
        }

        $newFail = null;
        if (!empty($_GET['new_fail_id'])) {
            $newFail = (new FailureModel())->getById((int)$_GET['new_fail_id']);
        }

        require BASE_PATH . '/templates/public/report_form.php';
    }

    public function reportPost(): void
    {
        Auth::requireLogin();

        // ── 1. CSRF ───────────────────────────────────────────────────────────
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Helpers::flash('error', 'Błąd bezpieczeństwa. Spróbuj ponownie.');
            Helpers::redirect('report');
        }

        // ── 2. Uzupełnij dane zalogowanego użytkownika w POST i zbuduj DTO ───
        $currentUser  = Auth::user();
        $postWithUser = array_merge($_POST, [
            'reporter_user_id' => $currentUser['id'],
            'reporter_name'    => $currentUser['name'],
            'reporter_login'   => $currentUser['login'],
        ]);
        $dto = CreateFailureDTO::fromPost($postWithUser);

        // ── 3. Sprawdź czy wybrana linia ma podzespoły (walidacja serwer-side) ─
        $lm            = new ProductionLineModel();
        $line          = $lm->getById($dto->productionLineId);
        $lineHasSubs   = $line !== null && !empty($line['subsystems']);

        // ── 4. Walidacja ─────────────────────────────────────────────────────
        $validator = new FailureValidator();
        $errors    = $validator->validateCreate($dto, $lineHasSubs);

        if ($errors) {
            Helpers::flash('error', implode(' ', $errors));
            Helpers::redirect('report', ['line_id' => $dto->productionLineId ?: null]);
        }

        // ── 5. Status startowy ────────────────────────────────────────────────
        if (!$line) {
            Helpers::flash('error', 'Wybrana linia nie istnieje.');
            Helpers::redirect('report');
        }
        $initStatus = (new StatusModel())->getInitial();
        if (!$initStatus) {
            Helpers::flash('error', 'Błąd konfiguracji: brak statusu początkowego.');
            Helpers::redirect('report');
        }

        // ── 6. Zapis zgłoszenia ───────────────────────────────────────────────
        $ticket = Helpers::generateTicketNumber($dto->productionLineId, $line['prefix']);
        $fm     = new FailureModel();
        $failId = $fm->create(array_merge($dto->toArray(), [
            'ticket_number' => $ticket,
            'status_id'     => $initStatus['id'],
        ]));

        $fm->addHistory(
            $failId,
            $currentUser['id'],
            'created',
            null,
            $initStatus['id'],
            $currentUser['login'] . ' – ' . $currentUser['name'],
            'Zgłoszenie awarii utworzone'
        );

        Helpers::flash('success_dur', 'Zgłoszenie wysłane pomyślnie. Numer: <strong>' . Helpers::e($ticket) . '</strong>');
        Helpers::redirect('report', ['line_id' => $dto->productionLineId, 'new_fail_id' => $failId]);
    }

    /** Historia linii — dostępna publicznie */
    public function lineHistory(): void
    {
        $lines   = (new ProductionLineModel())->getAll(true);
        $lineId  = (int)($_GET['line_id'] ?? 0);
        $rawDays = (int)($_GET['days'] ?? 30);
        $days    = in_array($rawDays, [7, 30, 90, 365]) ? $rawDays : 30;
        $page    = max(1, (int)($_GET['page'] ?? 1));

        $fm          = new FailureModel();
        $mm          = new MaintenanceModel();
        $failures    = [];
        $stats       = ['total' => 0, 'open_count' => 0, 'closed_count' => 0, 'avg_repair_str' => '—'];
        $durList     = [];
        $currentLine = null;
        $pager       = null;

        if ($lineId > 0) {
            foreach ($lines as $l) {
                if ((int)$l['id'] === $lineId) { $currentLine = $l; break; }
            }

            $perPage = max(5, (int)(
                (new SettingsModel())->get('records_per_page') ?? RECORDS_PER_PAGE
            ));

            $total    = $fm->countLineHistory($lineId, $days);
            $pager    = Helpers::paginate($total, $page, $perPage);
            $failures = $fm->getLineHistory($lineId, $days, $pager['per_page'], $pager['offset']);

            $rawStats = $fm->getLineStats($lineId, $days);
            if ($rawStats !== null) $stats = $rawStats;
            $durList  = $mm->getReviewsByLine($lineId, 5);
        }

        require BASE_PATH . '/templates/public/line_history.php';
    }
}

/*
 * ============================================================
 * DOKUMENTACJA PLIKU: PublicController.php
 * ============================================================
 * Plik:        app/Controllers/PublicController.php
 * Opis:        Kontroler formularza zgłoszenia awarii i historii linii
 * Zależności:  CreateFailureDTO, FailureValidator, modele
 * Zmiany:      reportPost() używa CreateFailureDTO::fromPost() i FailureValidator.
 *              Dodana walidacja podzespołu po stronie serwera (punkt 2).
 * ============================================================
 */
