<?php

use App\Helpers\Helpers;

$pageTitle = 'Szczegóły — ' . $failure['ticket_number'];
require BASE_PATH . '/templates/shared/header.php';
?>
<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
  <a href="<?= BASE_URL ?>/index.php?route=failures" class="btn btn-sm">← Lista</a>
  <h1 style="font-size:20px;font-weight:700;font-family:monospace;"><?= Helpers::e($failure['ticket_number']) ?></h1>
  <?= Helpers::statusBadge($failure['status_label'], $failure['status_color']) ?>
</div>

<div class="g2">
  <div>
    <div class="card mb2">
      <div class="card-head">
        <span class="card-title">Szczegóły zgłoszenia</span>
        <a href="<?= BASE_URL ?>/index.php?route=line_history&line_id=<?= $failure['production_line_id'] ?>" class="btn btn-sm">Historia linii</a>
      </div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 20px;">
          <div>
            <div class="flbl">Linia</div>
            <div class="fw6"><?= Helpers::e($failure['line_name']) ?></div>
          </div>
          <div>
            <div class="flbl">Podzespół</div>
            <div class="fw6"><?= Helpers::e($failure['subsystem_name'] ?? '—') ?></div>
          </div>
          <?php /* Zmiana 1: wyświetl objaw zgłaszającego */ ?>
          <div>
            <div class="flbl">Objaw zgłoszony</div>
            <div class="fw6"><?= Helpers::e($failure['symptom_name'] ?? '—') ?></div>
          </div>
          <div>
            <div class="flbl">Zgłaszający</div>
            <div><?= Helpers::e($failure['reporter_name'] ?? $failure['reporter_acronym'] ?? '—') ?></div>
          </div>
          <div>
            <div class="flbl">Rodzaj awarii</div>
            <div><?= $failure['category_id'] ? Helpers::catBadge($failure['cat_label'], $failure['cat_color']) : '<span class="muted fs-sm">Brak — uzupełni mechanik</span>' ?></div>
          </div>
          <div>
            <div class="flbl">Data zgłoszenia</div>
            <div><?= Helpers::formatDate($failure['created_at']) ?></div>
          </div>
          <div>
            <div class="flbl">Numer</div>
            <div class="mono fw6"><?= Helpers::e($failure['ticket_number']) ?></div>
          </div>
          <?php if ($failure['closed_at']): ?>
            <div>
              <div class="flbl">Data zamknięcia</div>
              <div><?= Helpers::formatDate($failure['closed_at']) ?></div>
            </div>
          <?php endif; ?>
          <div style="grid-column:1/-1;">
            <div class="flbl">Usterka</div>
            <div class="fw6">
              <?php if ($failure['other_failure']): ?>
                <em>Inna usterka</em><?= $failure['mechanic_note'] ? ' — ' . Helpers::e(mb_substr($failure['mechanic_note'], 0, 80)) : '' ?>
              <?php else: ?>
                <?= Helpers::e($failure['dict_title'] ?? '—') ?>
              <?php endif; ?>
            </div>
          </div>
          <?php if ($failure['description']): ?>
            <div style="grid-column:1/-1;">
              <div class="flbl">Opis dodatkowy</div>
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

        <div class="sep"></div>
        <?php if (\App\Helpers\Auth::isMechanic()): ?>
          <form method="POST" action="<?= BASE_URL ?>/index.php?route=add_comment">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
            <input type="hidden" name="failure_id" value="<?= $failure['id'] ?>">
            <div class="fg mb1"><label class="flbl">Dodaj komentarz</label>
              <textarea name="comment" class="fc" rows="3" placeholder="Opisz wykonane czynności..." required></textarea>
            </div>
            <button type="submit" class="btn btn-p btn-sm">Dodaj komentarz</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div>
    <?php if (\App\Helpers\Auth::isMechanic()): ?>

      <?php /* Zmiana 2: sekcja kategorii i usterki — tylko dla mechanika */ ?>
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

              <div class="fg" id="dictGrp">
                <label class="flbl">Usterka ze słownika</label>
                <select name="dictionary_item_id" id="mechDict" class="fc">
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
      <?php /* koniec Zmiana 2 */ ?>

      <div class="card mb2">
        <div class="card-head"><span class="card-title">Zmień status</span></div>
        <div class="card-body">

          <?php if (!empty($failure['status_is_final'])): ?>
            <!-- BŁĄD 3: status końcowy — zablokuj zmianę -->
            <div class="alert alert-w">
              <strong>Zgłoszenie zamknięte.</strong><br>
              Status <strong><?= Helpers::e($failure['status_label']) ?></strong> jest statusem końcowym — nie można dalej zmieniać statusu tego zgłoszenia.
            </div>

          <?php else: ?>
            <form method="POST" action="<?= BASE_URL ?>/index.php?route=status_change">
              <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
              <input type="hidden" name="failure_id" value="<?= $failure['id'] ?>">
              <div class="fg"><label class="flbl">Nowy status</label>
                <select name="status_id" id="statusSelect" class="fc" required>
                  <option value="">— Wybierz nowy status —</option>
                  <?php foreach ($statuses as $s): ?>
                    <?php
                    // BŁĄD 4: wyklucz status startowy (nigdy nie można go przypisać ręcznie)
                    if (!empty($s['is_initial'])) continue;
                    // BŁĄD 3+aktualny: wyklucz aktualny status
                    if ($s['id'] == $failure['status_id']) continue;
                    ?>
                    <option value="<?= $s['id'] ?>" data-final="<?= $s['is_final'] ? '1' : '0' ?>">
                      <?= Helpers::e($s['label']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <span class="fhint">Aktualny: <?= Helpers::statusBadge($failure['status_label'], $failure['status_color']) ?></span>
              </div>
              <div class="fg"><label class="flbl">Uwaga (opcjonalnie)</label>
                <textarea name="note" class="fc" rows="2" placeholder="Powód zmiany..."></textarea>
              </div>
              <button type="submit" id="statusSubmitBtn" class="btn btn-p btn-block">Zapisz status</button>
            </form>
          <?php endif; ?>

        </div>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-head"><span class="card-title">Historia zdarzeń</span></div>
      <div class="card-body">
        <ul class="tl">
          <?php foreach (array_reverse($history) as $h): ?>
            <li class="tl-i">
              <div class="tl-dot<?= $h['action'] === 'status_changed' ? ' g' : ($h['action'] === 'comment_added' ? ' v' : ($h['action'] === 'edited' ? ' a' : '')) ?>"></div>
              <div class="tl-time"><?= Helpers::formatDate($h['created_at']) ?> · <?= Helpers::e($h['actor_name']) ?></div>
              <div class="tl-txt">
                <?php if ($h['action'] === 'created'): ?>
                  <strong>Zgłoszenie utworzone</strong>
                <?php elseif ($h['action'] === 'status_changed'): ?>
                  <strong>Zmiana statusu</strong>
                  <?php if ($h['old_status_label']): ?>
                    <span class="badge" style="background:<?= Helpers::e($h['old_status_color']) ?>;color:#fff;"><?= Helpers::e($h['old_status_label']) ?></span> →
                  <?php endif; ?>
                  <span class="badge" style="background:<?= Helpers::e($h['new_status_color']) ?>;color:#fff;"><?= Helpers::e($h['new_status_label']) ?></span>
                <?php elseif ($h['action'] === 'comment_added'): ?>
                  <strong>Dodano komentarz</strong>
                <?php elseif ($h['action'] === 'edited'): ?>
                  <strong>Zaktualizowano dane zgłoszenia</strong>
                <?php else: ?>
                  <strong><?= Helpers::e($h['action']) ?></strong>
                <?php endif; ?>
                <?php if ($h['note']): ?>
                  <div class="muted fs-sm mt1"><?= Helpers::e($h['note']) ?></div>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
          <?php if (!$history): ?>
            <li class="muted fs-sm">Brak historii.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
  /* Zmiana 2: toggle pola notatki mechanika i widoczność słownika */
  function toggleOtherFailure(checked) {
    var grp = document.getElementById('mechanicNoteGrp');
    var note = document.getElementById('mechanicNote');
    var dict = document.getElementById('dictGrp');
    if (checked) {
      grp.style.display = 'block';
      note.required = true;
      if (dict) dict.style.opacity = '0.4';
    } else {
      grp.style.display = 'none';
      note.required = false;
      if (dict) dict.style.opacity = '1';
    }
  }

  /* Zmiana 2: filtruj słownik po kategorii mechanika */
  (function() {
    var catSel = document.getElementById('mechCat');
    var dictSel = document.getElementById('mechDict');
    if (!catSel || !dictSel) return;

    function filterDict() {
      var catId = catSel.value;
      var opts = dictSel.querySelectorAll('option[data-cat]');
      opts.forEach(function(o) {
        o.style.display = (!catId || o.getAttribute('data-cat') === catId) ? '' : 'none';
      });
      // Wyczyść wybór jeśli aktualnie wybrany element nie pasuje do kategorii
      var selected = dictSel.querySelector('option:checked');
      if (selected && selected.getAttribute('data-cat') && catId && selected.getAttribute('data-cat') !== catId) {
        dictSel.value = '';
      }
    }

    catSel.addEventListener('change', filterDict);
    // Inicjalne filtrowanie przy ładowaniu strony
    filterDict();
  })();

  /* Zmiana 3: blokada statusu końcowego — trwały banner catNotice */
  (function() {
    var isComplete = <?= ($failure['category_id'] && ($failure['dictionary_item_id'] || ($failure['other_failure'] && $failure['mechanic_note']))) ? 'true' : 'false' ?>;
    var statusSel = document.getElementById('statusSelect');
    var catNotice = document.getElementById('catNotice');
    var submitBtn = document.getElementById('statusSubmitBtn');

    if (!statusSel || !catNotice) return;

    statusSel.addEventListener('change', function() {
      var opt = this.options[this.selectedIndex];
      var isFinal = opt && opt.getAttribute('data-final') === '1';

      if (isFinal && !isComplete) {
        catNotice.style.display = 'block';
        if (submitBtn) submitBtn.disabled = true;
      } else {
        catNotice.style.display = 'none';
        if (submitBtn) submitBtn.disabled = false;
      }
    });
  })();
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>