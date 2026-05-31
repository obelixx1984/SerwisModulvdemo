<?php
// templates/shared/failure_detail.php
// ZMIANA: formularze akcji (status, kategoria, komentarz) ukryte gdy $canEdit === false
// $canEdit pochodzi z FailureController::detail() — false gdy user jest tylko zgłaszającym

use App\Helpers\Helpers;

$pageTitle = 'Szczegóły zgłoszenia';
require BASE_PATH . '/templates/shared/header.php';

$sc = $failure['status_color'] ?? '#6b7280';
$sl = $failure['status_label'] ?? '—';

// Czy bieżący użytkownik może edytować (mechanik / uprawnienie 'failures')
// Zmienna pochodzi z kontrolera — jeśli nie istnieje (stary kod), domyślnie true
$canEdit = $canEdit ?? \App\Helpers\Auth::isMechanic();

// ── NOWE ─────────────────────────────────────────────────────
$isReporter      = $isReporter ?? false;
$statusIsInitial = false;
if (!empty($statuses)) {
  foreach ($statuses as $_s) {
    if ($_s['id'] == $failure['status_id'] && !empty($_s['is_initial'])) {
      $statusIsInitial = true;
      break;
    }
  }
}
// ─────────────────────────────────────────────────────────────
$symptoms           = $symptoms           ?? [];
$assignments        = $assignments        ?? [];
$isLeader           = $isLeader           ?? false;
$hasLeader          = $hasLeader          ?? false;
$mechanics          = $mechanics          ?? [];
$observationNotes       = $observationNotes       ?? [];
$isObservationActive    = $isObservationActive    ?? false;
$observationSecondsLeft = $observationSecondsLeft ?? 0;
$hasAnyObservationNotes = $hasAnyObservationNotes ?? false;
?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
  <a href="#" onclick="history.back(); return false;" class="btn btn-sm">← Wróć</a>
  <h1 style="font-size:16px;font-weight:700;margin:0;">
    <?= Helpers::e($failure['ticket_number']) ?>
  </h1>
  <?= Helpers::statusBadge($sl, $sc) ?>
  <?php if (!$canEdit): ?>
    <span class="badge" style="background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;">Tylko podgląd</span>
  <?php endif; ?>
  <?php if ($isReporter && $statusIsInitial): ?>
    <button
      type="button"
      class="btn btn-p btn-sm"
      title="Edytuj objaw zgłoszenia"
      onclick="openEditModal(
        <?= (int)$failure['id'] ?>,
        '<?= Helpers::e(addslashes($failure['ticket_number'])) ?>',
        '<?= Helpers::e(addslashes($failure['line_name'] ?? '')) ?>',
        '<?= Helpers::e(addslashes($failure['subsystem_name'] ?? '')) ?>',
        <?= (int)($failure['symptom_id'] ?? 0) ?>,
        <?= !empty($failure['other_symptom']) ? 'true' : 'false' ?>,
        '<?= Helpers::e(addslashes($failure['description'] ?? '')) ?>'
      )">
      ✏ Edytuj
    </button>
  <?php endif; ?>
</div>

<div class="g2">

  <!-- ── Lewa kolumna: informacje + komentarze ── -->
  <div>

    <div class="card mb2">
      <div class="card-head"><span class="card-title">Informacje o zgłoszeniu</span></div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 20px;">

          <div>
            <div class="flbl">Numer zgłoszenia</div>
            <div class="mono fw6"><?= Helpers::e($failure['ticket_number']) ?></div>
          </div>
          <div>
            <div class="flbl">Status</div>
            <div><?= Helpers::statusBadge($sl, $sc) ?></div>
          </div>
          <div>
            <div class="flbl">Linia produkcyjna</div>
            <div class="fw6"><?= Helpers::e($failure['line_name'] ?? '—') ?></div>
          </div>
          <div>
            <div class="flbl">Podzespół</div>
            <div><?= Helpers::e($failure['subsystem_name'] ?? '—') ?></div>
          </div>
          <div>
            <div class="flbl">Zgłaszający</div>
            <div><?= Helpers::e($failure['reporter_name'] ?? $failure['reporter_acronym'] ?? '—') ?></div>
          </div>
          <div>
            <div class="flbl">Data zgłoszenia</div>
            <div><?= Helpers::formatDate($failure['created_at']) ?></div>
          </div>
          <?php if ($failure['closed_at']): ?>
            <div>
              <div class="flbl">Data zamknięcia</div>
              <div><?= Helpers::formatDate($failure['closed_at']) ?></div>
            </div>
          <?php endif; ?>

          <div>
            <div class="flbl">Objaw zgłoszony</div>
            <div class="fw6">
              <?php if (!empty($failure['other_symptom'])): ?>
                <em style="color:#6b7280;">Inne objawy</em>
              <?php else: ?>
                <?= Helpers::e($failure['symptom_name'] ?? '—') ?>
              <?php endif; ?>
            </div>
          </div>

          <div>
            <div class="flbl">Rodzaj awarii</div>
            <div>
              <?= $failure['category_id']
                ? Helpers::catBadge($failure['cat_label'] ?? '—', $failure['cat_color'] ?? '#6b7280')
                : '<span class="muted">—</span>' ?>
            </div>
          </div>

          <div>
            <div class="flbl">Usterka</div>
            <div>
              <?php if ($failure['other_failure']): ?>
                <em>Inna usterka</em>
                <?= $failure['mechanic_note']
                  ? ' — ' . Helpers::e(mb_substr($failure['mechanic_note'], 0, 80))
                  : '' ?>
              <?php else: ?>
                <?= Helpers::e($failure['dict_title'] ?? '—') ?>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($failure['description']): ?>
            <div style="grid-column:1/-1;">
              <div class="flbl">
                <?= !empty($failure['other_symptom']) ? 'Opis objawu (Inne objawy)' : 'Opis dodatkowy' ?>
              </div>
              <div><?= nl2br(Helpers::e($failure['description'])) ?></div>
            </div>
          <?php endif; ?>

          <?php if ($failure['other_failure'] && $failure['mechanic_note']): ?>
            <div style="grid-column:1/-1;">
              <div class="flbl">Notatka mechanika</div>
              <div><?= nl2br(Helpers::e($failure['mechanic_note'])) ?></div>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>

    <?php if (!empty($failure['status_is_observed'])): ?>
      <?php
      // Oblicz datę wygaśnięcia do wyświetlenia
      $obsExpires = '';
      if (!empty($failure['observation_started_at'])) {
        $obsExpires = date('d.m.Y H:i', strtotime($failure['observation_started_at']) + ($observationWindowHours ?? 8) * 3600);
      }
      ?>
      <div class="card mb2" style="border-color:<?= $isObservationActive ? '#FAC775' : '#F09595' ?>;">
        <div class="card-head" style="background:<?= $isObservationActive ? '#FAEEDA' : '#FCEBEB' ?>;">
          <span class="card-title" style="color:<?= $isObservationActive ? '#854F0B' : '#A32D2D' ?>;">
            ⏱ Okno obserwacji
          </span>
          <?php if ($isObservationActive): ?>
            <span class="badge" style="background:#FAC775;color:#854F0B;">Aktywne</span>
          <?php else: ?>
            <span class="badge" style="background:#F09595;color:#A32D2D;">Zakończone</span>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <div style="display:flex;align-items:center;gap:14px;">
            <div style="width:48px;height:48px;border-radius:50%;
                        background:<?= $isObservationActive ? '#FAC775' : '#F09595' ?>;
                        display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:22px;">
              <?= $isObservationActive ? '⏳' : '🔒' ?>
            </div>
            <div style="flex:1;">
              <?php if ($isObservationActive): ?>
                <div style="font-size:11px;color:#BA7517;margin-bottom:2px;">Pozostały czas na uwagi</div>
                <div id="obsCountdown" style="font-size:28px;font-weight:700;color:#854F0B;font-variant-numeric:tabular-nums;line-height:1;">
                  --:--:--
                </div>
                <div style="height:5px;background:#FAC775;border-radius:4px;margin-top:6px;overflow:hidden;">
                  <div id="obsProgressBar" style="height:100%;background:#854F0B;border-radius:4px;transition:width 1s linear;"></div>
                </div>
              <?php else: ?>
                <div style="font-size:11px;color:#A32D2D;margin-bottom:2px;">Czas obserwacji upłynął</div>
                <div style="font-size:22px;font-weight:700;color:#A32D2D;line-height:1;">00:00:00</div>
              <?php endif; ?>
            </div>
            <div style="text-align:right;font-size:11px;color:<?= $isObservationActive ? '#BA7517' : '#A32D2D' ?>;">
              <?php if ($obsExpires): ?>
                <?= $isObservationActive ? 'Upływa:' : 'Wygasło:' ?><br>
                <strong><?= Helpers::e($obsExpires) ?></strong>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php
    $showObservationCard   = !empty($failure['status_is_observed']) || $hasAnyObservationNotes;
    $isObservationArchived = empty($failure['status_is_observed']) && $hasAnyObservationNotes;
    ?>
    <?php if ($showObservationCard): ?>
      <div class="card mb2" style="border-color:<?= $isObservationArchived ? '#d1d5db' : '#F09595' ?>;">

        <div class="card-head" style="background:<?= $isObservationArchived ? '#f3f4f6' : '#FCEBEB' ?>;">
          <span class="card-title" style="color:<?= $isObservationArchived ? '#374151' : '#A32D2D' ?>;">
            <?= $isObservationArchived ? '📋 Uwagi zgłoszone podczas obserwacji' : '💬 Uwagi do obserwacji' ?>
          </span>
          <?php if (!empty($observationNotes)): ?>
            <span class="badge" style="background:<?= $isObservationArchived ? '#e5e7eb' : '#F09595' ?>;color:<?= $isObservationArchived ? '#374151' : '#7F1D1D' ?>;">
              <?= count($observationNotes) ?>
            </span>
          <?php endif; ?>
        </div>

        <?php if ($isObservationArchived): ?>
          <!-- Pasek z przyciskiem rozwijania -->
          <div style="padding:10px 16px;border-bottom:0.5px solid #e5e7eb;">
            <button type="button"
              onclick="toggleArchivedNotes(this)"
              style="width:100%;display:flex;align-items:center;justify-content:space-between;
                     background:none;border:none;cursor:pointer;font-size:13px;color:#6b7280;padding:0;">
              <span id="archivedNotesLabel">Pokaż uwagi (<?= count($observationNotes) ?>)</span>
              <span id="archivedNotesArrow" style="font-size:16px;transition:transform .25s;">▼</span>
            </button>
          </div>
          <div id="archivedNotesBody" style="display:none;">
          <?php endif; ?>

          <div class="card-body" style="background:<?= $isObservationArchived ? '#fafafa' : '#fff8f8' ?>;">

            <?php if (!empty($observationNotes)): ?>
              <?php foreach ($observationNotes as $on): ?>
                <?php
                $canDeleteNote = !$isObservationArchived && $isObservationActive &&
                  ((int)$on['user_id'] === (int)$user['id'] || \App\Helpers\Auth::isAdmin());
                ?>
                <div style="padding:10px 12px;margin-bottom:8px;border-radius:6px;
                          background:<?= $isObservationArchived ? '#f3f4f6' : '#FCEBEB' ?>;
                          border-left:3px solid <?= $isObservationArchived ? '#d1d5db' : '#F09595' ?>;">
                  <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:4px;">
                    <div>
                      <strong style="font-size:12px;color:<?= $isObservationArchived ? '#374151' : '#7F1D1D' ?>;">
                        <?= Helpers::e($on['user_name']) ?>
                      </strong>
                      <span style="font-size:11px;color:<?= $isObservationArchived ? '#6b7280' : '#A32D2D' ?>;margin-left:8px;">
                        <?= Helpers::formatDate($on['created_at']) ?>
                      </span>
                    </div>
                    <?php if ($canDeleteNote): ?>
                      <form method="POST" action="<?= BASE_URL ?>/index.php?route=delete_observation_note"
                        style="margin:0;" onsubmit="return confirm('Usunąć tę uwagę?')">
                        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
                        <input type="hidden" name="note_id" value="<?= (int)$on['id'] ?>">
                        <input type="hidden" name="failure_id" value="<?= (int)$failure['id'] ?>">
                        <button type="submit" class="btn btn-sm"
                          style="padding:2px 8px;font-size:11px;color:#A32D2D;border-color:#F09595;">
                          ✕ Usuń
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                  <p style="font-size:13px;line-height:1.5;color:#374151;margin:0;">
                    <?= nl2br(Helpers::e($on['note'])) ?>
                  </p>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p style="font-size:13px;color:<?= $isObservationArchived ? '#6b7280' : '#A32D2D' ?>;opacity:.7;margin:0 0 8px;">
                Brak uwag do obserwacji.
              </p>
            <?php endif; ?>

            <?php if (!$isObservationArchived): ?>
              <?php if ($isObservationActive): ?>
                <div class="sep" style="border-color:#F7C1C1;"></div>
                <form method="POST" action="<?= BASE_URL ?>/index.php?route=add_observation_note">
                  <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
                  <input type="hidden" name="failure_id" value="<?= $failure['id'] ?>">
                  <div class="fg mb1">
                    <label class="flbl" style="color:#A32D2D;">Dodaj uwagę do obserwacji</label>
                    <textarea name="note" class="fc" rows="3"
                      style="border-color:#F09595;"
                      placeholder="Opisz obserwowane zachowanie maszyny..." required></textarea>
                  </div>
                  <button type="submit" class="btn btn-sm"
                    style="background:#A32D2D;color:#fff;border-color:#A32D2D;">
                    Dodaj uwagę
                  </button>
                </form>
              <?php else: ?>
                <div class="sep" style="border-color:#F7C1C1;"></div>
                <div style="display:flex;align-items:center;gap:8px;padding:8px 10px;
                          background:#F7C1C1;border-radius:6px;">
                  <span style="font-size:14px;">🔒</span>
                  <span style="font-size:12px;color:#7F1D1D;font-weight:500;">
                    Czas obserwacji upłynął — dodawanie uwag zostało zablokowane.
                  </span>
                </div>
              <?php endif; ?>
            <?php endif; ?>

          </div>

          <?php if ($isObservationArchived): ?>
          </div><!-- koniec archivedNotesBody -->
        <?php endif; ?>

      </div>
    <?php endif; ?>

    <!-- Komentarze serwisowe -->
    <div class="card mb2">
      <div class="card-head"><span class="card-title">Komentarze serwisowe</span></div>
      <div class="card-body">
        <?php if ($comments): ?>
          <?php foreach ($comments as $c): ?>
            <div style="padding:8px 0;border-bottom:1px solid #f3f4f6;">
              <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <strong class="fs-sm"><?= Helpers::e($c['author']) ?></strong>
                <span class="muted fs-sm"><?= Helpers::formatDate($c['created_at']) ?></span>
              </div>
              <p style="font-size:13px;line-height:1.5;"><?= nl2br(Helpers::e($c['comment'])) ?></p>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="muted fs-sm mb1">Brak komentarzy.</p>
        <?php endif; ?>

        <?php if ($canEdit): ?>
          <div class="sep"></div>
          <form method="POST" action="<?= BASE_URL ?>/index.php?route=add_comment">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
            <input type="hidden" name="failure_id" value="<?= $failure['id'] ?>">
            <div class="fg mb1">
              <label class="flbl">Dodaj komentarz</label>
              <textarea name="comment" class="fc" rows="3" placeholder="Opisz wykonane czynności..." required></textarea>
            </div>
            <button type="submit" class="btn btn-p btn-sm">Dodaj komentarz</button>
          </form>
        <?php else: ?>
          <div class="sep"></div>
          <p class="muted fs-sm">Komentarze może dodawać tylko osoba z działu DUR.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Obsada zgłoszenia -->
    <?php if ($canEdit || $isReporter || \App\Helpers\Auth::check()): ?>
      <div class="card mb2" id="crewCard">
        <div class="card-head">
          <span class="card-title">👷 Obsada zgłoszenia</span>
          <?php if (empty($assignments) && empty($failure['status_is_final'])): ?>
            <span class="badge" style="background:#dc2626;color:#fff;" id="crewMissingBadge">
              Brak — wymagana do zamknięcia
            </span>
          <?php elseif (!empty($assignments)): ?>
            <span class="badge" style="background:#16a34a;color:#fff;" id="crewOkBadge">
              <?= count($assignments) ?> os.
            </span>
          <?php endif; ?>
        </div>
        <div class="card-body">

          <?php if (!empty($failure['status_is_final'])): ?>
            <!-- Zgłoszenie zamknięte — tylko podgląd obsady -->
            <div class="alert alert-w" style="margin-bottom:10px;">
              <strong>Zgłoszenie zamknięte.</strong> Obsady nie można modyfikować.
            </div>
          <?php endif; ?>

          <!-- Lista obsady -->
          <?php if ($assignments): ?>
            <div style="margin-bottom:12px;">
              <?php foreach ($assignments as $a): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;
                          padding:7px 0;border-bottom:1px solid #f3f4f6;">
                  <div style="display:flex;align-items:center;gap:8px;">
                    <span style="width:28px;height:28px;border-radius:50%;
                                background:<?= $a['is_first'] ? '#0a2463' : '#e5e7eb' ?>;
                                color:<?= $a['is_first'] ? '#fff' : '#374151' ?>;
                                display:inline-flex;align-items:center;justify-content:center;
                                font-size:11px;font-weight:700;flex-shrink:0;">
                      <?= mb_strtoupper(mb_substr($a['user_name'], 0, 1)) ?>
                    </span>
                    <div>
                      <div class="fw6 fs-sm"><?= Helpers::e($a['user_name']) ?>
                        <?php if ($a['is_first']): ?>
                          <span class="badge" style="background:#e8eeff;color:#0a2463;margin-left:4px;font-size:10px;">
                            Prowadzący
                          </span>
                        <?php endif; ?>
                      </div>
                      <div class="muted" style="font-size:11px;">
                        Dodany: <?= Helpers::formatDate($a['created_at']) ?>
                        <?php if ($a['added_by_name']): ?>
                          · przez <?= Helpers::e($a['added_by_name']) ?>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>

                  <?php if ($isLeader && empty($a['is_first']) && empty($failure['status_is_final'])): ?>
                    <!-- Przycisk usunięcia (nie dla pierwszej osoby, nie gdy zamknięte) -->
                    <form method="POST" action="<?= BASE_URL ?>/index.php?route=assignment_remove"
                      onsubmit="return confirm('Usunąć <?= htmlspecialchars(addslashes($a['user_name'])) ?> z obsady?')">
                      <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
                      <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                      <input type="hidden" name="failure_id" value="<?= $failure['id'] ?>">
                      <button type="submit" class="btn btn-sm"
                        style="color:#dc2626;border-color:#fca5a5;padding:3px 9px;"
                        title="Usuń z obsady">
                        ✕
                      </button>
                    </form>
                  <?php elseif ($isLeader && !empty($a['is_first'])): ?>
                    <span class="muted" style="font-size:11px;" title="Pierwszej osoby nie można usunąć">🔒</span>
                  <?php endif; ?>

                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <p class="muted fs-sm mb1">Brak obsady. Obsada jest dodawana automatycznie przy pierwszej zmianie statusu.</p>
          <?php endif; ?>

          <!-- Formularz dodawania obsady (tylko gdy nie zamknięte) -->
          <?php if ($isLeader && $hasLeader && empty($failure['status_is_final'])): ?>
            <div class="sep"></div>
            <form method="POST" action="<?= BASE_URL ?>/index.php?route=assignment_add"
              style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
              <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
              <input type="hidden" name="failure_id" value="<?= $failure['id'] ?>">
              <div class="fg" style="margin:0;flex:1;min-width:160px;">
                <label class="flbl">Dodaj do obsady (Mechanik)</label>
                <select name="user_id" class="fc" required>
                  <option value="">— Wybierz mechanika —</option>
                  <?php
                  // Pomijaj tych którzy już są w obsadzie
                  $inCrew = array_column($assignments, 'user_id');
                  foreach ($mechanics as $m):
                    if (in_array($m['id'], $inCrew)) continue;
                  ?>
                    <option value="<?= $m['id'] ?>"><?= Helpers::e($m['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button type="submit" class="btn btn-p btn-sm" style="margin-bottom:0;">
                + Dodaj
              </button>
            </form>
          <?php endif; ?>

        </div>
      </div>
    <?php endif; /* koniec canEdit — obsada */ ?>

    <!-- ── Zdjęcia — galeria ─────────────────────────────────── -->
    <?php if (!empty($photos)): ?>
      <div class="card mt2" id="photoGalleryCard">
        <div class="card-head">
          <span class="card-title">
            📷 Zdjęcia zgłoszenia
            <span class="badge bg-secondary ms-1"><?= count($photos) ?></span>
          </span>
        </div>
        <div class="card-body">
          <div style="display:flex;flex-wrap:wrap;gap:10px;" id="photoGallery">
            <?php foreach ($photos as $i => $ph): ?>
              <div class="photo-thumb"
                style="position:relative;display:inline-block;cursor:pointer;"
                data-index="<?= $i ?>"
                role="button"
                aria-label="Zdjęcie <?= $i + 1 ?> z <?= count($photos) ?>">
                <img src="<?= BASE_URL . '/' . \App\Helpers\Helpers::e($ph['path']) ?>"
                  alt="Zdjęcie zgłoszenia <?= $i + 1 ?>"
                  loading="lazy"
                  style="width:110px;height:82px;object-fit:cover;border-radius:6px;border:0.5px solid #ddd;display:block;transition:opacity .15s,transform .15s;"
                  onmouseover="this.style.opacity='.82';this.style.transform='scale(1.03)'"
                  onmouseout="this.style.opacity='1';this.style.transform='scale(1)'">
                <?php if (!$ph['is_public']): ?>
                  <span title="Widoczne tylko dla uprawnionych"
                    style="position:absolute;top:3px;left:3px;background:rgba(0,0,0,.52);color:#fff;font-size:9px;padding:1px 5px;border-radius:3px;">
                    🔒 uprawnieni
                  </span>
                <?php endif; ?>
                <div style="font-size:10px;color:#888;text-align:center;margin-top:3px;">
                  <?= round($ph['filesize'] / 1024) ?> kB
                </div>
                <?php if ($canEdit && (\App\Helpers\Auth::isAdmin() || (int)$ph['user_id'] === (int)$user['id'])): ?>
                  <button class="deletePhotoBtn"
                    data-id="<?= (int)$ph['id'] ?>"
                    title="Usuń zdjęcie"
                    style="position:absolute;bottom:3px;right:3px;background:rgba(163,45,45,.85);border:0;color:#fff;border-radius:4px;cursor:pointer;font-size:10px;padding:2px 5px;line-height:1;display:none;">✕</button>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
          <p style="font-size:12px;color:#888;margin-top:10px;">
            Kliknij miniaturę aby powiększyć
          </p>
        </div>
      </div>

      <!-- Lightbox -->
      <div id="lbBackdrop" role="dialog" aria-modal="true" aria-label="Podgląd zdjęcia"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.82);z-index:9999;align-items:center;justify-content:center;">
        <div id="lbBox"
          style="background:#fff;border-radius:12px;border:0.5px solid #ddd;max-width:640px;width:calc(100% - 32px);overflow:hidden;">
          <div style="padding:10px 14px;display:flex;align-items:center;justify-content:space-between;border-bottom:0.5px solid #ddd;">
            <div id="lbTitle" style="font-size:13px;font-weight:500;"></div>
            <button id="lbClose"
              style="background:none;border:0.5px solid #ddd;border-radius:6px;cursor:pointer;padding:4px 10px;font-size:13px;">
              ✕ Zamknij
            </button>
          </div>
          <div style="position:relative;background:#000;line-height:0;">
            <img id="lbImg" src="" alt="Powiększone zdjęcie zgłoszenia"
              style="width:100%;max-height:420px;object-fit:contain;display:block;">
            <button id="lbPrev"
              style="position:absolute;top:50%;left:0;transform:translateY(-50%);background:rgba(0,0,0,.45);border:0;color:#fff;cursor:pointer;padding:14px 10px;font-size:22px;border-radius:0 6px 6px 0;">
              ‹
            </button>
            <button id="lbNext"
              style="position:absolute;top:50%;right:0;transform:translateY(-50%);background:rgba(0,0,0,.45);border:0;color:#fff;cursor:pointer;padding:14px 10px;font-size:22px;border-radius:6px 0 0 6px;">
              ›
            </button>
          </div>
          <div style="padding:10px 14px;display:flex;align-items:center;justify-content:space-between;border-top:0.5px solid #ddd;">
            <div id="lbMeta" style="font-size:12px;color:#666;display:flex;gap:10px;flex-wrap:wrap;"></div>
            <div style="display:flex;align-items:center;gap:8px;">
              <div id="lbDots" style="display:flex;gap:5px;"></div>
              <span id="lbCounter" style="font-size:12px;color:#999;min-width:36px;text-align:right;"></span>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

  </div>

  <!-- ── Prawa kolumna: historia (zawsze) + akcje (canEdit) ── -->
  <div>

    <!-- Historia zdarzeń — ZAWSZE po prawej stronie -->
    <div class="card mb2">
      <div class="card-head"><span class="card-title">Historia zdarzeń</span></div>
      <div class="card-body">
        <?php if ($history): ?>
          <ul class="tl">
            <?php foreach (array_reverse($history) as $h):
              $dot = 'tl-dot';
              if ($h['action'] === 'created')        $dot .= ' g';
              if ($h['action'] === 'status_changed') $dot .= ' a';
              if ($h['action'] === 'comment_added')  $dot .= ' v';
            ?>
              <li class="tl-i">
                <div class="<?= $dot ?>"></div>
                <div class="tl-time"><?= Helpers::formatDate($h['created_at']) ?> · <?= Helpers::e($h['actor_name']) ?></div>
                <div class="tl-txt"><?= Helpers::e($h['note'] ?? $h['action']) ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="muted fs-sm">Brak historii.</p>
        <?php endif; ?>
      </div>
    </div>

    <?php /* ZMIANA: sekcja kategorii i usterki — tylko gdy canEdit */ ?>
    <?php if ($canEdit): ?>
      <div class="card mb2">
        <div class="card-head">
          <span class="card-title">Kategoria i usterka</span>
          <?php if ($failure['category_id'] && ($failure['dictionary_item_id'] || $failure['other_failure'])): ?>
            <span class="badge" style="background:#16a34a;color:#fff;">Uzupełnione</span>
          <?php elseif (empty($failure['status_is_final'])): ?>
            <span class="badge" style="background:#dc2626;color:#fff;">Brak — wymagane do zamknięcia</span>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <?php if (!empty($failure['status_is_final'])): ?>
            <div class="alert alert-w">
              <strong>Zgłoszenie zamknięte.</strong><br>
              Kategoria i usterka nie mogą być zmieniane po nadaniu statusu końcowego.
            </div>
          <?php else: ?>
            <form method="POST" action="<?= BASE_URL ?>/index.php?route=set_category" id="catForm">
              <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
              <input type="hidden" name="failure_id" value="<?= $failure['id'] ?>">

              <div class="fg">
                <label class="flbl">Rodzaj awarii</label>
                <select name="category_id" id="mechCat" class="fc">
                  <option value="">— Wybierz rodzaj —</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"
                      <?= $failure['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                      <?= Helpers::e($cat['label']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="fg" id="dictGrp" style="<?= $failure['other_failure'] ? 'opacity:.4;pointer-events:none;' : '' ?>">
                <label class="flbl">Usterka ze słownika</label>
                <select name="dictionary_item_id" id="mechDict" class="fc"
                  <?= $failure['other_failure'] ? 'disabled' : '' ?>>
                  <option value="">— Wybierz usterkę —</option>
                  <?php foreach ($dictionary as $d): ?>
                    <option value="<?= $d['id'] ?>"
                      data-cat="<?= $d['category_id'] ?>"
                      style="display:none;"
                      <?= $failure['dictionary_item_id'] == $d['id'] ? 'selected' : '' ?>>
                      <?= Helpers::e($d['title']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="fg">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                  <input type="checkbox" name="other_failure" id="otherFailureCb" value="1"
                    <?= $failure['other_failure'] ? 'checked' : '' ?>
                    onchange="toggleOtherFailure(this.checked)">
                  <span class="flbl" style="margin:0;">Inna usterka (brak w słowniku)</span>
                </label>
              </div>

              <div class="fg" id="mechanicNoteGrp" style="display:<?= $failure['other_failure'] ? 'block' : 'none' ?>;">
                <label class="flbl">Notatka mechanika <span class="req">*</span></label>
                <textarea name="mechanic_note" id="mechanicNote" class="fc" rows="3"
                  placeholder="Opisz usterkę której nie ma w słowniku..."
                  <?= $failure['other_failure'] ? 'required' : '' ?>><?= Helpers::e($failure['mechanic_note'] ?? '') ?></textarea>
              </div>

              <button type="submit" class="btn btn-p btn-sm">Zapisz kategorię i usterkę</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; /* koniec canEdit — kategoria */ ?>

    <!-- ══ Karta: Części zamienne ═══════════════════════════════════ -->
    <div class="card mb2">
      <div class="card-head"><span class="card-title">🔧 Części zamienne</span></div>
      <div class="card-body">

        <?php if (!empty($spareParts)): ?>
          <table style="width:100%;border-collapse:collapse;margin-bottom:12px;">
            <thead>
              <tr>
                <th style="text-align:left;padding:4px 8px;border-bottom:1px solid #e5e7eb;">Część</th>
                <th style="text-align:left;padding:4px 8px;border-bottom:1px solid #e5e7eb;">Ilość</th>
                <th style="text-align:left;padding:4px 8px;border-bottom:1px solid #e5e7eb;">Kategoria</th>
                <?php if ($canEdit && empty($failure['status_is_final'])): ?>
                  <th style="padding:4px 8px;border-bottom:1px solid #e5e7eb;"></th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($spareParts as $sp): ?>
                <tr>
                  <td style="padding:4px 8px;"><?= Helpers::e($sp['part_name']) ?></td>
                  <td style="padding:4px 8px;"><?= (int)$sp['quantity'] ?></td>
                  <td style="padding:4px 8px;"><?= Helpers::catBadge($sp['category_name'], $sp['category_color']) ?></td>
                  <?php if ($canEdit && empty($failure['status_is_final'])): ?>
                    <td style="padding:4px 8px;">
                      <form method="POST" action="<?= BASE_URL ?>/index.php?route=spare_part_delete"
                        style="display:inline;"
                        onsubmit="return confirm('Usunąć tę część?');">
                        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
                        <input type="hidden" name="spare_id" value="<?= $sp['id'] ?>">
                        <input type="hidden" name="failure_id" value="<?= $failure['id'] ?>">
                        <button type="submit" class="btn btn-sm" style="border-color:#fca5a5;color:#dc2626;">Usuń</button>
                      </form>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="muted fs-sm" style="margin:0 0 12px;">Brak dodanych części zamiennych.</p>
        <?php endif; ?>

        <!-- Formularz dodawania — tylko gdy canEdit i status nie jest końcowy -->
        <?php if ($canEdit && empty($failure['status_is_final'])): ?>
          <form method="POST" action="<?= BASE_URL ?>/index.php?route=spare_part_add"
            style="display:grid;grid-template-columns:1fr 80px 200px auto;gap:8px;align-items:end;">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
            <input type="hidden" name="failure_id" value="<?= $failure['id'] ?>">
            <div>
              <label class="flbl" style="font-size:.75rem;">Nazwa części <span class="req">*</span></label>
              <input class="fc" name="part_name" placeholder="np. Uszczelka pompy" required>
            </div>
            <div>
              <label class="flbl" style="font-size:.75rem;">Ilość</label>
              <input class="fc" type="number" name="quantity" value="1" min="1" required>
            </div>
            <div>
              <label class="flbl" style="font-size:.75rem;">Kategoria <span class="req">*</span></label>
              <select class="fc" name="category_id" required>
                <option value="">— Wybierz —</option>
                <?php foreach ($sparePartCategories as $spc): ?>
                  <option value="<?= $spc['id'] ?>"><?= Helpers::e($spc['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <button type="submit" class="btn btn-p btn-sm">Dodaj</button>
            </div>
          </form>
        <?php elseif (!empty($failure['status_is_final'])): ?>
          <p class="muted fs-sm" style="margin:4px 0 0;">
            Zgłoszenie jest zamknięte — nie można dodawać ani usuwać części.
          </p>
        <?php endif; ?>

      </div>
    </div>
    <!-- ══ Koniec karty: Części zamienne ══════════════════════════ -->

    <?php /* ZMIANA: formularz zmiany statusu — tylko gdy canEdit */ ?>
    <?php if ($canEdit): ?>
      <div class="card mb2">
        <div class="card-head"><span class="card-title">Zmień status</span></div>
        <div class="card-body">

          <?php if (!empty($failure['status_is_final'])): ?>
            <div class="alert alert-w">
              <strong>Zgłoszenie zamknięte.</strong><br>
              Status <strong><?= Helpers::e($failure['status_label']) ?></strong> jest statusem końcowym —
              nie można dalej zmieniać statusu tego zgłoszenia.
            </div>
          <?php else: ?>
            <form method="POST" action="<?= BASE_URL ?>/index.php?route=status_change">
              <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
              <input type="hidden" name="failure_id" value="<?= $failure['id'] ?>">
              <div class="fg">
                <label class="flbl">Nowy status</label>
                <select name="status_id" id="statusSelect" class="fc" required>
                  <option value="">— Wybierz nowy status —</option>
                  <?php foreach ($statuses as $s): ?>
                    <?php
                    if (!empty($s['is_initial'])) continue;
                    if ($s['id'] == $failure['status_id']) continue;
                    ?>
                    <option value="<?= $s['id'] ?>" data-final="<?= $s['is_final'] ?>">
                      <?= Helpers::e($s['label']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="fg">
                <label class="flbl">Notatka (opcjonalnie)</label>
                <textarea name="note" class="fc" rows="2" placeholder="Krótki opis powodu zmiany..."></textarea>
              </div>
              <button type="submit" class="btn btn-p btn-sm">Zmień status</button>
            </form>
          <?php endif; ?>

        </div>
      </div>
    <?php endif; /* koniec canEdit — status */ ?>

    <?php if ($canEdit && empty($failure['status_is_final'])): ?>
      <!-- ── Zdjęcia — upload ─────────────────────────────────── -->
      <div class="card mt2" id="photoUploadCard">
        <div class="card-head"><span class="card-title">📷 Dodaj zdjęcia</span></div>
        <div class="card-body">

          <div id="dropZone" style="border:2px dashed #aaa;border-radius:8px;padding:24px;text-align:center;cursor:pointer;background:#f9f9f9;transition:background .15s;">
            <div>Przeciągnij zdjęcia tutaj lub
              <label for="photoFileInput" style="color:#0d6efd;cursor:pointer;text-decoration:underline;"
                onclick="event.stopPropagation()">wybierz pliki</label>
            </div>
            <div style="font-size:.8rem;color:#888;margin-top:4px;">JPEG, PNG · maks. 6 MB na plik · zmniejszane automatycznie do 1920 px</div>
          </div>

          <div style="margin-top:10px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <button type="button" id="qrBtn"
              style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;font-size:13px;border:0.5px solid #ccc;border-radius:6px;cursor:pointer;background:#fff;">
              📱 Dodaj telefonem (QR)
            </button>
            <span style="font-size:12px;color:#999;">Link ważny 15 minut</span>
          </div>

          <div id="qrPanel" style="display:none;margin-top:14px;padding:16px;border:0.5px solid #ddd;border-radius:8px;background:#fafafa;text-align:center;">
            <p style="font-size:13px;color:#555;margin-bottom:12px;">Zeskanuj aparatem telefonu:</p>
            <canvas id="qrCanvas"></canvas>
            <p style="font-size:11px;color:#aaa;margin-top:10px;" id="qrExpiry"></p>
            <button type="button" id="qrClose"
              style="margin-top:8px;font-size:12px;color:#888;background:none;border:0;cursor:pointer;text-decoration:underline;">
              Zamknij
            </button>
          </div>

          <input type="file" id="photoFileInput" accept="image/jpeg,image/png,image/webp" multiple style="display:none;">

          <div class="mt1" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
            <strong>Widoczność dodawanych zdjęć:</strong>
            <label style="cursor:pointer;"><input type="radio" name="photoVisibility" value="0" checked> 🔒 Tylko uprawnieni</label>
            <label style="cursor:pointer;"><input type="radio" name="photoVisibility" value="1"> 🌐 Wszyscy</label>
          </div>

          <div id="pendingPreviews" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px;min-height:0;"></div>

          <div id="uploadProgress" style="display:none;margin-top:8px;color:#555;font-size:.9rem;"></div>

          <button type="button" id="photoUploadBtn" class="btn btn-primary mt1" style="display:none;">
            Zapisz zdjęcia
          </button>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!$canEdit): ?>
      <div class="card mb2">
        <div class="card-head"><span class="card-title">Status zgłoszenia</span></div>
        <div class="card-body">
          <p class="fs-sm" style="margin:0;">Aktualne: <?= Helpers::statusBadge($sl, $sc) ?></p>
          <p class="muted fs-sm" style="margin-top:8px;">
            Zmiana statusu i kategorii nie jest możliwa — nie masz uprawnień.
          </p>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<!-- ══ Modal: Edycja objawu awarii ════════════════════════════ -->
<style>
  .edit-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .45);
    z-index: 3000;
    align-items: center;
    justify-content: center;
  }

  .edit-modal-overlay.open {
    display: flex;
  }

  .edit-modal-box {
    background: #fff;
    border-radius: 12px;
    width: 100%;
    max-width: 460px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, .20);
    overflow: hidden;
  }

  .edit-modal-head {
    background: #0a2463;
    color: #fff;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 700;
    font-size: 15px;
  }

  .edit-modal-close {
    background: none;
    border: none;
    color: rgba(255, 255, 255, .7);
    font-size: 20px;
    cursor: pointer;
    line-height: 1;
    padding: 0;
  }

  .edit-modal-body {
    padding: 20px;
  }

  .edit-modal-meta {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 14px;
    font-size: 13px;
  }

  .other-cb-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 7px;
    margin-bottom: 10px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
  }
</style>
<div class="edit-modal-overlay" id="editSymptomModal" onclick="closeEditModalOutside(event)">
  <div class="edit-modal-box" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">

    <div class="edit-modal-head">
      <span id="editModalTitle">Edytuj objaw awarii</span>
      <button class="edit-modal-close" onclick="closeEditModal()" type="button" aria-label="Zamknij">×</button>
    </div>

    <div class="edit-modal-body">
      <div class="edit-modal-meta">
        <div>Zgłoszenie: <strong id="editModalTicket">—</strong></div>
        <div>Linia: <span id="editModalLine">—</span></div>
        <div id="editModalSubsystemRow" style="display:none;">Podzespół: <strong id="editModalSubsystem" style="color:#374151;">—</strong></div>
      </div>

      <form method="POST" action="<?= BASE_URL ?>/index.php?route=my_failure_edit" id="editSymptomForm">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
        <input type="hidden" name="failure_id" id="editFailureId" value="">
        <input type="hidden" name="return_to" value="failure_detail">

        <?php /* ZMIANA: checkbox "Inne objawy" w modalu edycji */ ?>
        <label class="other-cb-row">
          <input
            type="checkbox"
            name="other_symptom"
            id="editOtherSymptomCb"
            value="1"
            style="width:16px;height:16px;cursor:pointer;flex-shrink:0;"
            onchange="toggleEditOtherSymptom(this.checked)">
          Inne objawy
          <span class="muted" style="font-weight:400;">&nbsp;— brak odpowiedniego na liście</span>
        </label>

        <div id="editSymptomGrp">
          <div class="fg">
            <label class="flbl">Objaw awarii <span class="req" id="editSymptomReq">*</span></label>
            <select name="symptom_id" id="editSymptomSelect" class="fc" required>
              <option value="">— Wybierz objaw —</option>
              <?php foreach ($symptoms as $sym): ?>
                <option value="<?= (int)$sym['id'] ?>"><?= Helpers::e($sym['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="fhint">Wybierz objaw który najlepiej opisuje awarię.</span>
          </div>
        </div>

        <div id="editDescGrp" style="display:none;">
          <div class="fg">
            <label class="flbl">Opis objawu <span class="req">*</span></label>
            <textarea name="description" id="editDescArea" class="fc" rows="3"
              placeholder="Opisz dokładnie jaki objaw zaobserwowałeś..."></textarea>
            <span class="fhint">Opis pojawi się na listach zamiast nazwy objawu.</span>
          </div>
        </div>

        <div style="display:flex;gap:8px;margin-top:4px;">
          <button type="submit" class="btn btn-p btn-sm">Zapisz zmianę</button>
          <button type="button" class="btn btn-sm" onclick="closeEditModal()">Anuluj</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  /*
   * KLUCZ ZMIANY: cały kod który wymaga DOM-u przeniesiony do
   * DOMContentLoaded. "toggleOther" zmieniony z deklaracji funkcji
   * (wewnątrz bloku if) na zmienną — to był główny błąd JS.
   * Funkcje globalne (openEditModal itp.) zostają poza wrapperem.
   */
  document.addEventListener('DOMContentLoaded', function() {

    // ── Filtrowanie słownika po kategorii ──────────────────────
    var catSel = document.getElementById('mechCat');
    var dictSel = document.getElementById('mechDict');
    if (catSel && dictSel) {
      function filterDict(catId) {
        dictSel.querySelectorAll('option[data-cat]').forEach(function(o) {
          o.style.display = (!catId || o.dataset.cat == catId) ? '' : 'none';
        });
      }
      catSel.addEventListener('change', function() {
        filterDict(this.value);
      });
      filterDict(catSel.value);
    }

    // ── Inna usterka — toggle textarea ─────────────────────────
    var otherChk = document.getElementById('otherFailureChk');
    var noteWrap = document.getElementById('mechanicNoteWrap');
    var dictWrap = document.getElementById('dictWrap');
    if (otherChk) {
      // WAŻNE: zmienna (var), NIE deklaracja funkcji wewnątrz bloku if
      var toggleOther = function() {
        var on = otherChk.checked;
        if (noteWrap) noteWrap.style.display = on ? '' : 'none';
        if (dictWrap) dictWrap.style.display = on ? 'none' : '';
      };
      otherChk.addEventListener('change', toggleOther);
      toggleOther();
    }

    // ── Walidacja przed zmianą na status końcowy ───────────────
    var sel = document.getElementById('statusSelect');
    if (sel) {
      var hasCategory = <?= !empty($failure['category_id']) ? 'true' : 'false' ?>;
      var hasDict = <?= (!empty($failure['dictionary_item_id']) || !empty($failure['other_failure'])) ? 'true' : 'false' ?>;
      var crewCount = <?= count($assignments) ?>;
      var catNotice = document.getElementById('catNotice');
      var crewNotice = document.getElementById('crewNotice');

      sel.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        var isFinal = opt && opt.dataset.final === '1';

        // PO — przy ukrywaniu resetuj też top do 0
        // żeby następnym razem crewNotice startował od góry
        if (catNotice) catNotice.style.display = 'none';
        if (crewNotice) {
          crewNotice.style.display = 'none';
          crewNotice.style.top = '0'; // reset pozycji
        }
        if (!isFinal) return;

        // PO — crewNotice przesuwa się dynamicznie pod catNotice jeśli ten jest widoczny
        if (!hasCategory || !hasDict) {
          if (catNotice) catNotice.style.display = 'block';
        }
        if (crewCount === 0) {
          if (crewNotice) {
            // Zmierz wysokość catNotice — jeśli jest widoczny, przesuń crewNotice pod niego
            // Jeśli catNotice jest ukryty, catHeight = 0 i crewNotice pojawi się od samej góry
            var catHeight = (catNotice && catNotice.style.display === 'block') ?
              catNotice.offsetHeight :
              0;
            crewNotice.style.top = catHeight + 'px';
            crewNotice.style.display = 'block';
          }
        }
      });
    }

  }); // koniec DOMContentLoaded

  // ── Funkcje globalne (muszą być poza DOMContentLoaded ─────────
  //    bo są wywoływane przez onclick="..." w HTML) ──────────────

  function toggleOtherFailure(checked) {
    var dictGrp = document.getElementById('dictGrp');
    var dictSel = document.getElementById('mechDict');
    var grp = document.getElementById('mechanicNoteGrp');
    var note = document.getElementById('mechanicNote');

    if (checked) {
      if (dictGrp) {
        dictGrp.style.opacity = '.4';
        dictGrp.style.pointerEvents = 'none';
      }
      if (dictSel) {
        dictSel.disabled = true;
        dictSel.value = '';
      }
      if (grp) grp.style.display = 'block';
      if (note) note.required = true;
    } else {
      if (dictGrp) {
        dictGrp.style.opacity = '';
        dictGrp.style.pointerEvents = '';
      }
      if (dictSel) dictSel.disabled = false;
      if (grp) grp.style.display = 'none';
      if (note) note.required = false;
    }
  }

  function openEditModal(failureId, ticket, lineName, subsystemName, currentSymptomId, isOtherSymptom, currentDesc) {
    var idEl = document.getElementById('editFailureId');
    var tkEl = document.getElementById('editModalTicket');
    var lnEl = document.getElementById('editModalLine');
    var subsRow = document.getElementById('editModalSubsystemRow');
    var subEl = document.getElementById('editModalSubsystem');
    var cb = document.getElementById('editOtherSymptomCb');

    if (!idEl || !cb) {
      console.error('Modal edycji nie znaleziony w DOM');
      return;
    }

    idEl.value = failureId;
    tkEl.textContent = ticket;
    lnEl.textContent = lineName;

    if (subsystemName && subsystemName.trim() !== '') {
      subEl.textContent = subsystemName;
      subsRow.style.display = '';
    } else {
      subsRow.style.display = 'none';
    }

    cb.checked = isOtherSymptom;
    toggleEditOtherSymptom(isOtherSymptom);

    if (isOtherSymptom) {
      var da = document.getElementById('editDescArea');
      if (da) da.value = currentDesc || '';
    } else {
      var ss = document.getElementById('editSymptomSelect');
      if (ss) ss.value = currentSymptomId || '';
    }

    var modal = document.getElementById('editSymptomModal');
    if (modal) {
      modal.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
  }

  function toggleEditOtherSymptom(checked) {
    var symptomGrp = document.getElementById('editSymptomGrp');
    var symptomSel = document.getElementById('editSymptomSelect');
    var descGrp = document.getElementById('editDescGrp');
    var descArea = document.getElementById('editDescArea');

    if (checked) {
      if (symptomGrp) symptomGrp.style.display = 'none';
      if (symptomSel) {
        symptomSel.disabled = true;
        symptomSel.removeAttribute('required');
        symptomSel.value = '';
      }
      if (descGrp) descGrp.style.display = '';
      if (descArea) descArea.required = true;
    } else {
      if (symptomGrp) symptomGrp.style.display = '';
      if (symptomSel) {
        symptomSel.disabled = false;
        symptomSel.required = true;
      }
      if (descGrp) descGrp.style.display = 'none';
      if (descArea) {
        descArea.required = false;
        descArea.value = '';
      }
    }
  }

  function closeEditModal() {
    var modal = document.getElementById('editSymptomModal');
    if (modal) modal.classList.remove('open');
    document.body.style.overflow = '';
  }

  function closeEditModalOutside(e) {
    if (e.target === document.getElementById('editSymptomModal')) closeEditModal();
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeEditModal();
  });

  /* Start Foto */

  (function() {

    /* ═══════════════════════════════════════════
       Dane zdjęć przekazane z PHP do JS
    ═══════════════════════════════════════════ */
    const PHOTOS = <?= json_encode(array_values(array_map(function ($ph) {
                      return [
                        'id'     => (int)$ph['id'],
                        'url'    => BASE_URL . '/' . $ph['path'],
                        'kb'     => round($ph['filesize'] / 1024),
                        'pub'    => (bool)$ph['is_public'],
                        'author' => $ph['username'],
                        'date'   => $ph['created_at'],
                      ];
                    }, $photos ?? []))) ?>;

    /* ═══════════════════════════════════════════
       Lightbox
    ═══════════════════════════════════════════ */
    const backdrop = document.getElementById('lbBackdrop');
    const lbImg = document.getElementById('lbImg');
    const lbTitle = document.getElementById('lbTitle');
    const lbMeta = document.getElementById('lbMeta');
    const lbDots = document.getElementById('lbDots');
    const lbCounter = document.getElementById('lbCounter');
    const lbPrev = document.getElementById('lbPrev');
    const lbNext = document.getElementById('lbNext');
    const lbClose = document.getElementById('lbClose');

    let current = 0;

    function openLb(idx) {
      current = idx;
      backdrop.style.display = 'flex';
      updateLb();
    }

    function closeLb() {
      backdrop.style.display = 'none';
    }

    function updateLb() {
      const p = PHOTOS[current];
      lbImg.src = p.url;
      lbTitle.textContent = 'Zdjęcie ' + (current + 1) + ' z ' + PHOTOS.length;
      lbPrev.style.display = current === 0 ? 'none' : 'block';
      lbNext.style.display = current === PHOTOS.length - 1 ? 'none' : 'block';
      lbCounter.textContent = (current + 1) + ' / ' + PHOTOS.length;

      lbMeta.innerHTML =
        (p.pub ?
          '<span style="background:#f0f0f0;padding:2px 7px;border-radius:4px;font-size:11px;">🌐 wszyscy</span>' :
          '<span style="background:#f0f0f0;padding:2px 7px;border-radius:4px;font-size:11px;">🔒 tylko uprawnieni</span>') +
        '<span>' + p.kb + ' kB</span>' +
        '<span style="color:#aaa;">' + p.author + ' · ' + p.date + '</span>';

      lbDots.innerHTML = '';
      PHOTOS.forEach(function(_, i) {
        const d = document.createElement('button');
        d.style.cssText = 'width:7px;height:7px;border-radius:50%;border:0;cursor:pointer;padding:0;background:' +
          (i === current ? '#185FA5' : '#ccc') + ';transition:background .15s,transform .15s;' +
          (i === current ? 'transform:scale(1.4);' : '');
        d.setAttribute('aria-label', 'Przejdź do zdjęcia ' + (i + 1));
        d.addEventListener('click', function() {
          current = i;
          updateLb();
        });
        lbDots.appendChild(d);
      });
    }

    if (lbPrev) lbPrev.addEventListener('click', function() {
      if (current > 0) {
        current--;
        updateLb();
      }
    });
    if (lbNext) lbNext.addEventListener('click', function() {
      if (current < PHOTOS.length - 1) {
        current++;
        updateLb();
      }
    });
    if (lbClose) lbClose.addEventListener('click', closeLb);
    if (backdrop) backdrop.addEventListener('click', function(e) {
      if (e.target === backdrop) closeLb();
    });
    document.addEventListener('keydown', function(e) {
      if (!backdrop || backdrop.style.display === 'none') return;
      if (e.key === 'ArrowLeft' && current > 0) {
        current--;
        updateLb();
      }
      if (e.key === 'ArrowRight' && current < PHOTOS.length - 1) {
        current++;
        updateLb();
      }
      if (e.key === 'Escape') closeLb();
    });

    /* Kliknięcie miniatury → otwórz lightbox */
    document.querySelectorAll('#photoGallery .photo-thumb').forEach(function(el) {
      el.addEventListener('click', function(e) {
        if (e.target.classList.contains('deletePhotoBtn')) return;
        openLb(+this.dataset.index);
      });
      /* Pokaż/ukryj przycisk usuwania przy najechaniu */
      el.addEventListener('mouseenter', function() {
        const btn = this.querySelector('.deletePhotoBtn');
        if (btn) btn.style.display = 'block';
      });
      el.addEventListener('mouseleave', function() {
        const btn = this.querySelector('.deletePhotoBtn');
        if (btn) btn.style.display = 'none';
      });
    });

    /* ═══════════════════════════════════════════
     QR kod
    ═══════════════════════════════════════════ */
    const qrBtn = document.getElementById('qrBtn');
    const qrPanel = document.getElementById('qrPanel');
    const qrClose = document.getElementById('qrClose');

    if (qrBtn) {
      qrBtn.addEventListener('click', async function() {
        qrBtn.disabled = true;
        qrBtn.textContent = 'Generowanie…';

        const fd = new FormData();
        fd.append('failure_id', FAILURE_ID);

        try {
          const res = await fetch('<?= BASE_URL ?>/index.php?route=photo_bridge_qr', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
          });
          const data = await res.json();

          if (data.success) {
            qrPanel.style.display = 'block';
            const canvas = document.getElementById('qrCanvas');
            QRCode.toCanvas(canvas, data.qr_token, {
              width: 200,
              margin: 2
            }, function(err) {
              if (err) console.error(err);
            });
            const exp = new Date(Date.now() + 15 * 60 * 1000);
            document.getElementById('qrExpiry').textContent =
              'Link wygasa o ' + exp.getHours() + ':' + String(exp.getMinutes()).padStart(2, '0');
            startPolling();
          } else {
            alert(data.message || 'Błąd generowania QR.');
          }
        } catch (e) {
          alert('Błąd połączenia z mostem.');
        }

        qrBtn.disabled = false;
        qrBtn.innerHTML = '📱 Dodaj telefonem (QR)';
      });

      if (qrClose) {
        qrClose.addEventListener('click', function() {
          qrPanel.style.display = 'none';
        });
      }
    }

    /* ═══════════════════════════════════════════
       Polling — auto-odświeżanie
    ═══════════════════════════════════════════ */
    let pollInterval = null;
    let lastPhotoTime = Math.floor(Date.now() / 1000);

    function startPolling() {
      if (pollInterval) return;
      pollInterval = setInterval(async function() {
        try {
          const res = await fetch(
            '<?= BASE_URL ?>/index.php?route=photo_check_new&failure_id=' + FAILURE_ID + '&since=' + lastPhotoTime, {
              credentials: 'same-origin'
            }
          );
          const data = await res.json();
          if (data.count > 0) {
            lastPhotoTime = Math.floor(Date.now() / 1000);
            window.location.reload();
          }
        } catch (_) {}
      }, 5000);
    }

    /* ═══════════════════════════════════════════
       Upload
    ═══════════════════════════════════════════ */
    const FAILURE_ID = <?= (int)$failure['id'] ?>;

    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('photoFileInput');
    const previews = document.getElementById('pendingPreviews');
    const uploadBtn = document.getElementById('photoUploadBtn');
    const progressEl = document.getElementById('uploadProgress');
    if (!dropZone) return;
    let pendingFiles = [];

    function refreshUploadBtn() {
      uploadBtn.style.display = pendingFiles.some(Boolean) ? 'inline-block' : 'none';
    }

    function addFiles(files) {
      files = Array.isArray(files) ? files : Array.from(files);
      [...files]
      .filter(function(f) {
          return ['image/jpeg', 'image/png', 'image/webp'].includes(f.type);
        })
        .filter(function(f) {
          return f.size <= 6 * 1024 * 1024;
        })
        .forEach(function(file) {
          const idx = pendingFiles.length;
          pendingFiles.push(file);
          const reader = new FileReader();
          reader.onload = function(ev) {
            const wrap = document.createElement('div');
            wrap.style.cssText = 'position:relative;display:inline-block;';
            wrap.dataset.idx = idx;
            wrap.innerHTML =
              '<img src="' + ev.target.result + '" style="width:100px;height:75px;object-fit:cover;border-radius:6px;border:0.5px solid #ccc;display:block;">' +
              '<div style="font-size:10px;color:#888;text-align:center;margin-top:2px;">' + (file.size / 1024).toFixed(0) + ' kB</div>' +
              '<button type="button" style="position:absolute;top:2px;right:2px;background:rgba(163,45,45,.8);border:0;color:#fff;border-radius:4px;cursor:pointer;font-size:11px;padding:1px 5px;">✕</button>';
            wrap.querySelector('button').addEventListener('click', function() {
              pendingFiles[idx] = null;
              wrap.remove();
              refreshUploadBtn();
            });
            previews.appendChild(wrap);
          };
          reader.readAsDataURL(file);
        });
      refreshUploadBtn();
    }

    dropZone.addEventListener('dragover', function(e) {
      e.preventDefault();
      dropZone.style.background = '#e8f0fe';
    });
    dropZone.addEventListener('dragleave', function() {
      dropZone.style.background = '#f9f9f9';
    });
    dropZone.addEventListener('drop', function(e) {
      e.preventDefault();
      dropZone.style.background = '#f9f9f9';
      addFiles(e.dataTransfer.files);
    });
    dropZone.addEventListener('click', function() {
      fileInput.click();
    });
    fileInput.addEventListener('change', function() {
      var files = Array.from(fileInput.files);
      fileInput.value = '';
      addFiles(files);
    });

    uploadBtn.addEventListener('click', async function() {
      const isPublic = document.querySelector('input[name="photoVisibility"]:checked').value;
      const toUpload = pendingFiles.filter(Boolean);
      if (!toUpload.length) return;

      uploadBtn.disabled = true;
      progressEl.style.display = 'block';

      let ok = 0,
        fail = 0;
      for (let i = 0; i < toUpload.length; i++) {
        progressEl.textContent = 'Wysyłanie ' + (i + 1) + ' / ' + toUpload.length + '…';
        const fd = new FormData();
        fd.append('failure_id', FAILURE_ID);
        fd.append('is_public', isPublic);
        fd.append('photo', toUpload[i]);
        try {
          const res = await fetch('<?= BASE_URL ?>/index.php?route=photo_upload', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
          });
          const data = await res.json();
          data.success ? ok++ : fail++;
        } catch (_) {
          fail++;
        }
      }

      progressEl.textContent = 'Zapisano ' + ok + ' zdjęć' + (fail ? ', błąd: ' + fail : '') + '.';
      setTimeout(function() {
        window.location.reload();
      }, 800);
    });

    /* Usuwanie */
    document.querySelectorAll('.deletePhotoBtn').forEach(function(btn) {
      btn.addEventListener('click', async function(e) {
        e.stopPropagation();
        if (!confirm('Usunąć to zdjęcie?')) return;
        const fd = new FormData();
        fd.append('photo_id', this.dataset.id);
        try {
          await fetch('<?= BASE_URL ?>/index.php?route=photo_delete', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
          });
        } finally {
          window.location.reload();
        }
      });
    });

  })();

  /* ══ Licznik obserwacji ══════════════════════════════════════ */
  (function() {
    var countdown = document.getElementById('obsCountdown');
    var progressBar = document.getElementById('obsProgressBar');
    if (!countdown) return; // nie ma licznika na stronie — pomiń

    var secondsLeft = <?= (int)$observationSecondsLeft ?>;
    var totalSeconds = <?= (int)(($observationWindowHours ?? 8) * 3600) ?>;

    function pad(n) {
      return String(n).padStart(2, '0');
    }

    function updateCountdown() {
      if (secondsLeft <= 0) {
        countdown.textContent = '00:00:00';
        if (progressBar) progressBar.style.width = '0%';
        // Przeładuj stronę — licznik wygasł, interfejs musi się zmienić
        window.location.reload();
        return;
      }
      var h = Math.floor(secondsLeft / 3600);
      var m = Math.floor((secondsLeft % 3600) / 60);
      var s = secondsLeft % 60;
      countdown.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
      if (progressBar) {
        var pct = Math.round((secondsLeft / totalSeconds) * 100);
        progressBar.style.width = pct + '%';
      }
      secondsLeft--;
    }

    updateCountdown(); // natychmiastowe pierwsze wywołanie bez czekania 1s
    setInterval(updateCountdown, 1000);
  })();

  /* ══ Rozwijana karta archiwalnych uwag obserwacji ════════════ */
  function toggleArchivedNotes(btn) {
    var body = document.getElementById('archivedNotesBody');
    var arrow = document.getElementById('archivedNotesArrow');
    var label = document.getElementById('archivedNotesLabel');
    if (!body) return;
    var isOpen = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : 'block';
    arrow.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
    label.textContent = isOpen ?
      'Pokaż uwagi (<?= count($observationNotes) ?>)' :
      'Ukryj uwagi (<?= count($observationNotes) ?>)';
  }
</script>

<script src="<?= BASE_URL ?>/assets/js/qrcode.min.js"></script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>