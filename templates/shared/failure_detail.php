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
$symptoms = $symptoms ?? []; // fallback gdy kontroler nie przekazał
$assignments = $assignments ?? [];  // ← dodaj tę linię
$isLeader    = $isLeader    ?? false;
$hasLeader   = $hasLeader   ?? false;
$mechanics   = $mechanics   ?? [];
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
document.addEventListener('DOMContentLoaded', function () {

  // ── Filtrowanie słownika po kategorii ──────────────────────
  var catSel  = document.getElementById('mechCat');
  var dictSel = document.getElementById('mechDict');
  if (catSel && dictSel) {
    function filterDict(catId) {
      dictSel.querySelectorAll('option[data-cat]').forEach(function (o) {
        o.style.display = (!catId || o.dataset.cat == catId) ? '' : 'none';
      });
    }
    catSel.addEventListener('change', function () { filterDict(this.value); });
    filterDict(catSel.value);
  }

  // ── Inna usterka — toggle textarea ─────────────────────────
  var otherChk = document.getElementById('otherFailureChk');
  var noteWrap = document.getElementById('mechanicNoteWrap');
  var dictWrap = document.getElementById('dictWrap');
  if (otherChk) {
    // WAŻNE: zmienna (var), NIE deklaracja funkcji wewnątrz bloku if
    var toggleOther = function () {
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
    var hasCategory  = <?= !empty($failure['category_id']) ? 'true' : 'false' ?>;
    var hasDict      = <?= (!empty($failure['dictionary_item_id']) || !empty($failure['other_failure'])) ? 'true' : 'false' ?>;
    var crewCount    = <?= count($assignments) ?>;
    var catNotice    = document.getElementById('catNotice');
    var crewNotice   = document.getElementById('crewNotice');

    sel.addEventListener('change', function () {
      var opt     = this.options[this.selectedIndex];
      var isFinal = opt && opt.dataset.final === '1';

      if (catNotice)  catNotice.style.display  = 'none';
      if (crewNotice) crewNotice.style.display = 'none';
      if (!isFinal) return;

      if (!hasCategory || !hasDict) {
        if (catNotice) catNotice.style.display = 'block';
      }
      if (crewCount === 0) {
        if (crewNotice) crewNotice.style.display = 'block';
      }
    });
  }

}); // koniec DOMContentLoaded

// ── Funkcje globalne (muszą być poza DOMContentLoaded ─────────
//    bo są wywoływane przez onclick="..." w HTML) ──────────────

function toggleOtherFailure(checked) {
  var dictGrp = document.getElementById('dictGrp');
  var dictSel = document.getElementById('mechDict');
  var grp     = document.getElementById('mechanicNoteGrp');
  var note    = document.getElementById('mechanicNote');

  if (checked) {
    if (dictGrp) { dictGrp.style.opacity = '.4'; dictGrp.style.pointerEvents = 'none'; }
    if (dictSel) { dictSel.disabled = true; dictSel.value = ''; }
    if (grp)  grp.style.display  = 'block';
    if (note) note.required      = true;
  } else {
    if (dictGrp) { dictGrp.style.opacity = ''; dictGrp.style.pointerEvents = ''; }
    if (dictSel)   dictSel.disabled = false;
    if (grp)  grp.style.display  = 'none';
    if (note) note.required      = false;
  }
}

function openEditModal(failureId, ticket, lineName, subsystemName, currentSymptomId, isOtherSymptom, currentDesc) {
  var idEl    = document.getElementById('editFailureId');
  var tkEl    = document.getElementById('editModalTicket');
  var lnEl    = document.getElementById('editModalLine');
  var subsRow = document.getElementById('editModalSubsystemRow');
  var subEl   = document.getElementById('editModalSubsystem');
  var cb      = document.getElementById('editOtherSymptomCb');

  if (!idEl || !cb) { console.error('Modal edycji nie znaleziony w DOM'); return; }

  idEl.value       = failureId;
  tkEl.textContent = ticket;
  lnEl.textContent = lineName;

  if (subsystemName && subsystemName.trim() !== '') {
    subEl.textContent   = subsystemName;
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
  var descGrp    = document.getElementById('editDescGrp');
  var descArea   = document.getElementById('editDescArea');

  if (checked) {
    if (symptomGrp) symptomGrp.style.display = 'none';
    if (symptomSel) { symptomSel.disabled = true; symptomSel.removeAttribute('required'); symptomSel.value = ''; }
    if (descGrp)    descGrp.style.display = '';
    if (descArea)   descArea.required = true;
  } else {
    if (symptomGrp) symptomGrp.style.display = '';
    if (symptomSel) { symptomSel.disabled = false; symptomSel.required = true; }
    if (descGrp)    descGrp.style.display = 'none';
    if (descArea)   { descArea.required = false; descArea.value = ''; }
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

document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closeEditModal();
});
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>