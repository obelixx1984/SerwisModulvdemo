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
?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
  <?php if ($canEdit): ?>
    <a href="<?= BASE_URL ?>/index.php?route=failures" class="btn btn-sm">← Lista zgłoszeń</a>
  <?php else: ?>
    <a href="<?= BASE_URL ?>/index.php?route=my_failures" class="btn btn-sm">← Moje zgłoszenia</a>
  <?php endif; ?>
  <h1 style="font-size:16px;font-weight:700;margin:0;">
    <?= Helpers::e($failure['ticket_number']) ?>
  </h1>
  <?= Helpers::statusBadge($sl, $sc) ?>
  <?php if (!$canEdit): ?>
    <span class="badge" style="background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb;">Tylko podgląd</span>
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

<script>
// Filtrowanie słownika po kategorii
document.addEventListener('DOMContentLoaded', function () {
  var catSel  = document.getElementById('mechCat');
  var dictSel = document.getElementById('mechDict');
  if (!catSel || !dictSel) return;

  function filterDict(catId) {
    var opts = dictSel.querySelectorAll('option[data-cat]');
    opts.forEach(function (o) {
      o.style.display = (!catId || o.dataset.cat == catId) ? '' : 'none';
    });
    if (dictSel.selectedOptions[0] && dictSel.selectedOptions[0].style.display === 'none') {
      dictSel.value = '';
    }
  }

  catSel.addEventListener('change', function () { filterDict(this.value); });
  filterDict(catSel.value);
});

function toggleOtherFailure(checked) {
  var dictGrp = document.getElementById('dictGrp');
  var dictSel = document.getElementById('mechDict');
  var grp     = document.getElementById('mechanicNoteGrp');
  var note    = document.getElementById('mechanicNote');

  if (checked) {
    // Inna usterka — zablokuj słownik, wymagaj notatki
    if (dictGrp) { dictGrp.style.opacity = '.4'; dictGrp.style.pointerEvents = 'none'; }
    if (dictSel) { dictSel.disabled = true; dictSel.value = ''; }
    if (grp)     grp.style.display = 'block';
    if (note)    note.required = true;
  } else {
    // Normalna usterka — odblokuj słownik, ukryj notatkę
    if (dictGrp) { dictGrp.style.opacity = ''; dictGrp.style.pointerEvents = ''; }
    if (dictSel) { dictSel.disabled = false; }
    if (grp)     grp.style.display = 'none';
    if (note)    { note.required = false; }
  }
}
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
