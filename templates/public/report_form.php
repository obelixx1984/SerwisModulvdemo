<?php
// templates/public/report_form.php
// ZMIANA: dodano checkbox "Inne objawy" z logiką JS

use App\Helpers\Helpers;
use App\Helpers\Auth;

$pageTitle = 'Zgłoszenie Awarii';
require BASE_PATH . '/templates/shared/header.php';

$subsystemsJs = [];
foreach ($lines as $l) {
    $subs = [];
    if (!empty($l['subsystems_str'])) {
        $ids   = explode(',', $l['subsystem_ids'] ?? '');
        $names = explode('|||', $l['subsystems_str']);
        foreach ($names as $i => $n) {
            $subs[] = ['id' => trim($ids[$i] ?? ''), 'name' => trim($n)];
        }
    }
    $subsystemsJs[$l['id']] = $subs;
}

// Czy po powrocie z POST checkbox był zaznaczony?
$otherSymptomChecked = !empty($_POST['other_symptom']);
?>
<div class="pub-layout">
  <div>
    <div class="card">
      <div class="card-body">
        <div class="pub-header">
          <div class="pub-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
              <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
              <line x1="12" y1="9" x2="12" y2="13" />
              <line x1="12" y1="17" x2="12.01" y2="17" />
            </svg>
          </div>
          <div class="pub-title">Zgłoszenie Awarii</div>
          <div class="pub-sub">Wybierz linię aby sprawdzić historię →</div>
        </div>

        <?php if ($duplicate): ?>
          <div class="dup-warn" style="display:block;">
            ⚠ <strong>Uwaga:</strong> Zgłoszenie <strong><?= Helpers::e($duplicate['ticket_number']) ?></strong>
            z tym samym objawem jest już otwarte na tej linii!
          </div>
        <?php else: ?>
          <div class="dup-warn" id="dupWarn">
            ⚠ <strong>Uwaga:</strong> Zgłoszenie <strong id="dupTicket"></strong>
            z tym samym objawem jest już otwarte na tej linii!
          </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/index.php?route=report_post">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

          <div class="fg">
            <label class="flbl">Osoba zgłaszająca</label>
            <?php $currentUser = Auth::user(); ?>
            <div class="fc" style="background:#f3f4f6;color:#374151;cursor:default;">
              <?= Helpers::e($currentUser['name']) ?>
            </div>
            <input type="hidden" name="reporter_user_id" value="<?= (int)$currentUser['id'] ?>">
          </div>

          <div class="fg">
            <label class="flbl">Linia produkcyjna <span class="req">*</span></label>
            <select name="production_line_id" id="pubLine" class="fc" required>
              <option value="">— Wybierz linię —</option>
              <?php foreach ($lines as $l): ?>
                <option value="<?= $l['id'] ?>"
                  <?= $currentLine && (int)$currentLine['id'] === (int)$l['id'] ? 'selected' : '' ?>>
                  <?= Helpers::e($l['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php
          $hasSubs  = $currentLine && !empty($currentLine['subsystems_str']);
          $subIds   = $hasSubs ? explode(',', $currentLine['subsystem_ids'] ?? '') : [];
          $subNames = $hasSubs ? explode('|||', $currentLine['subsystems_str']) : [];
          ?>
          <div class="fg" id="subGrp" style="display:<?= $hasSubs ? 'block' : 'none' ?>;">
            <label class="flbl">Podzespół <span class="req">*</span></label>
            <select name="subsystem_id" id="subsystemSelect" class="fc">
              <option value="">— Wybierz podzespół —</option>
              <?php foreach ($subNames as $i => $sname): $sid = trim($subIds[$i] ?? ''); ?>
                <option value="<?= Helpers::e($sid) ?>"
                  <?= ($_POST['subsystem_id'] ?? '') == $sid ? 'selected' : '' ?>>
                  <?= Helpers::e(trim($sname)) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php /* ── ZMIANA: checkbox "Inne objawy" ── */ ?>
          <div class="fg" style="margin-bottom:6px;">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:8px 10px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:7px;">
              <input
                type="checkbox"
                name="other_symptom"
                id="otherSymptomCb"
                value="1"
                <?= $otherSymptomChecked ? 'checked' : '' ?>
                onchange="toggleOtherSymptom(this.checked)"
                style="width:16px;height:16px;cursor:pointer;flex-shrink:0;">
              <span style="font-size:13px;font-weight:600;color:#374151;">
                Inne objawy
                <span class="muted" style="font-weight:400;">&nbsp;— brak odpowiedniego objawu na liście</span>
              </span>
            </label>
          </div>

          <?php /* Wybór objawu — wyłączony gdy "Inne objawy" zaznaczone */ ?>
          <div class="fg" id="symptomGrp" style="<?= $otherSymptomChecked ? 'opacity:.4;pointer-events:none;' : '' ?>">
            <label class="flbl">
              Objaw awarii
              <span class="req" id="symptomReq" style="<?= $otherSymptomChecked ? 'display:none' : '' ?>">*</span>
            </label>
            <select
              name="symptom_id"
              id="pubSymptom"
              class="fc"
              <?= $otherSymptomChecked ? 'disabled' : 'required' ?>>
              <option value="">— Wybierz objaw —</option>
              <?php
              $selectedSymptom = $_POST['symptom_id'] ?? $_GET['symptom_id'] ?? '';
              ?>
              <?php foreach ($symptoms as $sym): ?>
                <option value="<?= $sym['id'] ?>"
                  <?= $selectedSymptom == $sym['id'] ? 'selected' : '' ?>>
                  <?= Helpers::e($sym['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php /* Opis — opcjonalny normalnie, obowiązkowy gdy "Inne objawy" */ ?>
          <div class="fg">
            <label class="flbl">
              Dodatkowy opis
              <span class="req" id="descReq" style="<?= $otherSymptomChecked ? '' : 'display:none' ?>">*</span>
              <span id="descOpt" class="muted fs-sm" style="<?= $otherSymptomChecked ? 'display:none' : '' ?>">&nbsp;(opcjonalnie)</span>
            </label>
            <textarea
              name="description"
              id="descArea"
              class="fc"
              rows="3"
              <?= $otherSymptomChecked ? 'required' : '' ?>
              placeholder="<?= $otherSymptomChecked ? 'Opisz dokładnie jaki objaw zaobserwowałeś...' : 'Opisz dokładnie co zaobserwowałeś...' ?>"><?= Helpers::e($_POST['description'] ?? '') ?></textarea>
            <span class="fhint" id="descHint" style="<?= $otherSymptomChecked ? '' : 'display:none' ?>">
              ⚠ Opis jest wymagany gdy wybrano "Inne objawy" — pojawi się w miejscu objawu na listach.
            </span>
          </div>

          <button type="submit" class="pub-btn">🚨 Wyślij zgłoszenie awarii</button>
        </form>
      </div>
    </div>
  </div>

  <div>

    <?php /* ZMIANA 1: karta potwierdzenia nowo dodanego zgłoszenia */ ?>
    <?php if (!empty($newFail)): ?>
    <div class="card mb2" style="border:2px solid #16a34a;background:#f0fdf4;">
      <div class="card-head" style="background:#dcfce7;border-bottom:1px solid #bbf7d0;border-radius:10px 10px 0 0;">
        <span class="card-title" style="color:#15803d;">✅ Zgłoszenie dodane pomyślnie</span>
        <a href="<?= BASE_URL ?>/index.php?route=report&line_id=<?= (int)($currentLine['id'] ?? 0) ?>" class="btn btn-sm" style="background:#16a34a;color:#fff;border-color:#16a34a;">+ Nowe</a>
      </div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 16px;font-size:13px;">
          <div><div class="flbl">Numer</div><div class="mono fw6"><?= Helpers::e($newFail['ticket_number']) ?></div></div>
          <div><div class="flbl">Status</div><div><?= Helpers::statusBadge($newFail['status_label'] ?? '—', $newFail['status_color'] ?? '#6b7280') ?></div></div>
          <div><div class="flbl">Linia</div><div><?= Helpers::e($newFail['line_name'] ?? '—') ?></div></div>
          <div>
            <div class="flbl">Objaw</div>
            <div>
              <?php if (!empty($newFail['other_symptom'])): ?>
                <em class="muted">Inne objawy</em>
              <?php else: ?>
                <?= Helpers::e($newFail['symptom_name'] ?? '—') ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($currentLine): ?>
      <div class="stats mb2" style="grid-template-columns:repeat(3,1fr);">
        <div class="stat-card">
          <div class="stat-val sv-b" style="font-size:22px;"><?= (int)($lineStats['total'] ?? 0) ?></div>
          <div class="stat-lbl">Awarii / 30 dni</div>
        </div>
        <div class="stat-card">
          <div class="stat-val <?= (int)($lineStats['open_count'] ?? 0) > 0 ? 'sv-r' : 'sv-g' ?>" style="font-size:22px;">
            <?= (int)($lineStats['open_count'] ?? 0) ?>
          </div>
          <div class="stat-lbl">Otwarte</div>
        </div>
        <div class="stat-card">
          <div class="stat-val sv-g" style="font-size:22px;"><?= Helpers::e($lineStats['avg_repair_str'] ?? '—') ?></div>
          <div class="stat-lbl">Śr. czas naprawy</div>
        </div>
      </div>

      <div class="card mb2">
        <div class="card-head">
          <span class="card-title">Historia awarii — <?= Helpers::e($currentLine['name']) ?></span>
          <span class="muted fs-sm">ostatnie 30 dni</span>
        </div>
        <?php if ($lineHistory): ?>
          <div class="twrap">
            <table>
              <thead>
                <tr>
                  <th>Numer</th>
                  <th>Data</th>
                  <?php if (!empty($currentLine['subsystems_str'])): ?><th>Podzespół</th><?php endif; ?>
                  <th>Objaw / Usterka</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($lineHistory as $f): ?>
                  <tr<?= empty($f['closed_at']) ? ' style="background:#fffbeb;"' : '' ?>>
                    <td class="mono fw6 fs-sm" style="color:#0a2463;"><?= Helpers::e($f['ticket_number']) ?></td>
                    <td class="muted fs-sm"><?= Helpers::formatDateOnly($f['created_at']) ?></td>
                    <?php if (!empty($currentLine['subsystems_str'])): ?>
                      <td class="fs-sm"><?= Helpers::e($f['subsystem_name'] ?? '—') ?></td>
                    <?php endif; ?>
                    <?php /* ZMIANA: uwzględnij other_symptom */ ?>
                    <td class="fs-sm">
                      <?php if (!empty($f['other_symptom'])): ?>
                        <?php $d = trim($f['description'] ?? ''); ?>
                        <span style="font-style:italic;" title="<?= Helpers::e($d) ?>">
                          <?= $d !== '' ? Helpers::e(mb_strlen($d) > 42 ? mb_substr($d, 0, 40) . '…' : $d) : '<span class="muted">Inne objawy</span>' ?>
                        </span>
                      <?php else: ?>
                        <?= Helpers::e($f['symptom_name'] ?? $f['dict_title'] ?? mb_substr($f['description'] ?? '', 0, 42)) ?>
                      <?php endif; ?>
                    </td>
                    <td><?= Helpers::statusBadge($f['status_label'], $f['status_color']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="card-body muted" style="text-align:center;">Brak awarii w ostatnich 30 dniach.</div>
        <?php endif; ?>
      </div>

      <?php if ($lineDur): ?>
        <div class="card">
          <div class="card-head">
            <span class="card-title">Przeglądy DUR — <?= Helpers::e($currentLine['name']) ?></span>
          </div>
          <div class="card-body" style="padding:8px;">
            <?php foreach ($lineDur as $r): ?>
              <div class="dur-card">
                <div class="dur-title">
                  <?= Helpers::reviewTypeLabel($r['review_type']) ?> — <?= Helpers::e($r['review_date']) ?>
                  <?= $r['subsystem_name'] ? ' · ' . Helpers::e($r['subsystem_name']) : '' ?>
                </div>
                <div class="dur-meta"><?= Helpers::e($r['performer_name']) ?> · <?= (int)$r['duration_minutes'] ?> min</div>
                <?php foreach (array_slice(explode("\n", $r['activities']), 0, 3) as $a): if (trim($a)): ?>
                    <div class="dur-item"><span class="ck">✓</span><span><?= Helpers::e(ltrim(trim($a), '-')) ?></span></div>
                <?php endif; endforeach; ?>
                <?php if ($r['next_review_date']): ?>
                  <div class="dur-next"><span style="color:#7c3aed;">▶</span> Następny: <strong><?= Helpers::e($r['next_review_date']) ?></strong></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<script>
  window.SUBSYSTEMS = <?= json_encode($subsystemsJs, JSON_HEX_TAG) ?>;

  // ── ZMIANA: toggle "Inne objawy" ──────────────────────────
  function toggleOtherSymptom(checked) {
    var symptomGrp = document.getElementById('symptomGrp');
    var symptomSel = document.getElementById('pubSymptom');
    var symptomReq = document.getElementById('symptomReq');
    var descArea   = document.getElementById('descArea');
    var descReq    = document.getElementById('descReq');
    var descOpt    = document.getElementById('descOpt');
    var descHint   = document.getElementById('descHint');
    var dupWarn    = document.getElementById('dupWarn');

    if (checked) {
      // Inne objawy zaznaczone — wyłącz select objawu, wymagaj opisu
      symptomGrp.style.opacity         = '.4';
      symptomGrp.style.pointerEvents   = 'none';
      symptomSel.disabled              = true;
      symptomSel.removeAttribute('required');
      symptomSel.value                 = '';
      symptomReq.style.display         = 'none';
      descArea.required                = true;
      descArea.placeholder             = 'Opisz dokładnie jaki objaw zaobserwowałeś...';
      descReq.style.display            = 'inline';
      descOpt.style.display            = 'none';
      if (descHint) descHint.style.display = '';
      // Ukryj ostrzeżenie o duplikacie
      if (dupWarn) dupWarn.style.display = 'none';
    } else {
      // Normalny tryb — aktywuj select objawu, opis opcjonalny
      symptomGrp.style.opacity         = '';
      symptomGrp.style.pointerEvents   = '';
      symptomSel.disabled              = false;
      symptomSel.required              = true;
      symptomReq.style.display         = 'inline';
      descArea.required                = false;
      descArea.placeholder             = 'Opisz dokładnie co zaobserwowałeś...';
      descReq.style.display            = 'none';
      descOpt.style.display            = 'inline';
      if (descHint) descHint.style.display = 'none';
    }
  }
  // ──────────────────────────────────────────────────────────

  // Zmiana linii → przeładuj stronę
  document.getElementById('pubLine').addEventListener('change', function() {
    var lineId     = this.value;
    var symptomSel = document.getElementById('pubSymptom');
    var symptomVal = (symptomSel && !symptomSel.disabled) ? symptomSel.value : '';
    var url = '<?= BASE_URL ?>/index.php?route=report';
    if (lineId)     url += '&line_id='    + encodeURIComponent(lineId);
    if (symptomVal) url += '&symptom_id=' + encodeURIComponent(symptomVal);
    window.location.href = url;
  });

  // Sprawdź duplikaty po wyborze objawu
  (function() {
    var symptomSel = document.getElementById('pubSymptom');
    var lineSel    = document.getElementById('pubLine');
    if (!symptomSel || !lineSel) return;
    symptomSel.addEventListener('change', function() {
      var symptomId = this.value;
      var lineId    = lineSel.value;
      var dw = document.getElementById('dupWarn');
      var dt = document.getElementById('dupTicket');
      if (!dw || !dt) return;
      if (!symptomId || !lineId) { dw.style.display = 'none'; return; }
      fetch('<?= BASE_URL ?>/index.php?route=check_duplicate&line_id=' + lineId + '&symptom_id=' + symptomId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data && data.ticket) {
            dt.textContent    = data.ticket;
            dw.style.display  = 'block';
          } else {
            dw.style.display  = 'none';
          }
        }).catch(function() { dw.style.display = 'none'; });
    });
  })();
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
