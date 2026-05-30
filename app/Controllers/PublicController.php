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

        // ZMIANA 1: pobierz nowo dodane zgłoszenie jeśli przekierowano po submit
        $newFail = null;
        if (!empty($_GET['new_fail_id'])) {
            $newFail = (new FailureModel())->getById((int)$_GET['new_fail_id']);
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
        $otherSymptom   = !empty($_POST['other_symptom']) ? 1 : 0;
        $symptomId      = (!$otherSymptom && !empty($_POST['symptom_id'])) ? (int)$_POST['symptom_id'] : null;
        $description    = trim($_POST['description'] ?? '');
        $currentUser    = Auth::user();
        $reporterName   = $currentUser['name'];
        $reporterLogin  = $currentUser['login'];
        $reporterUserId = (int)$currentUser['id'];

        $errors = [];
        if (!$lineId) $errors[] = 'Wybierz linię produkcyjną.';
        if (!$otherSymptom && !$symptomId) $errors[] = 'Wybierz objaw awarii.';
        if ($otherSymptom && !$description) $errors[] = 'Wpisz opis — pole "Dodatkowy opis" jest wymagane przy "Inne objawy".';

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
            'other_symptom'      => $otherSymptom,         // ← NOWE
            'status_id'          => $initStatus['id'],
            'reporter_acronym'   => $reporterLogin,
            'reporter_user_id'   => $reporterUserId,
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
        // ZMIANA 1: redirect z line_id i new_fail_id — prawa kolumna zachowuje kontekst linii
        Helpers::redirect('report', ['line_id' => $lineId, 'new_fail_id' => $failId]);
    }

    /** Historia linii — dostepna publicznie */
    public function lineHistory(): void
    {
        $lines   = (new ProductionLineModel())->getAll(true);
        $lineId  = (int)($_GET['line_id'] ?? 0);
        $rawDays = (int)($_GET['days'] ?? 30);
        $days    = in_array($rawDays, [7, 30, 90, 365]) ? $rawDays : 30;
        $page    = max(1, (int)($_GET['page'] ?? 1));   // ← NOWE

        $fm          = new FailureModel();
        $mm          = new MaintenanceModel();
        $failures    = [];
        $stats       = ['total' => 0, 'open_count' => 0, 'closed_count' => 0, 'avg_repair_str' => '—'];
        $durList     = [];
        $currentLine = null;
        $pager       = null;   // ← NOWE

        if ($lineId > 0) {
            foreach ($lines as $l) {
                if ((int)$l['id'] === $lineId) {
                    $currentLine = $l;
                    break;
                }
            }

            // ── NOWE: odczytaj records_per_page z bazy ustawień ──────────
            $perPage = max(5, (int)(
                (new \App\Models\SettingsModel())->get('records_per_page') ?? RECORDS_PER_PAGE
            ));

            // Policz całkowitą liczbę rekordów i wylicz paginację
            $total    = $fm->countLineHistory($lineId, $days);   // ← nowa metoda w Models.php
            $pager    = Helpers::paginate($total, $page, $perPage);

            // Pobierz stronę wyników (LIMIT + OFFSET)
            $failures = $fm->getLineHistory($lineId, $days, $pager['per_page'], $pager['offset']);
            // ─────────────────────────────────────────────────────────────

            $rawStats = $fm->getLineStats($lineId, $days);
            if ($rawStats !== null) $stats = $rawStats;
            $durList  = $mm->getReviewsByLine($lineId, 5);
        }

        require BASE_PATH . '/templates/public/line_history.php';
    }
}

// ────────────────────────────────────────────────────────────
