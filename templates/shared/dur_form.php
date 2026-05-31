<?php

use App\Helpers\Helpers;

$pageTitle = 'Nowy raport DUR';
require BASE_PATH . '/templates/shared/header.php';

$subsystemsJs = [];

$scheduleNotes = $scheduleNotes ?? [];
$preSchedule   = $preSchedule   ?? null;

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
?>
<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
  <a href="<?= BASE_URL ?>/index.php?route=dur" class="btn btn-sm">← Przeglądy DUR</a>
  <h1 style="font-size:16px;font-weight:700;">Nowy raport z przeglądu DUR</h1>
</div>

<div class="card" style="max-width:820px;">
  <div class="card-body">
    <form method="POST" action="<?= BASE_URL ?>/index.php?route=dur_add_post">
      <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
      <div class="g2">
        <div class="fg">
          <label class="flbl">Linia produkcyjna <span class="req">*</span></label>
          <select name="production_line_id" class="fc" required id="durLineSel" onchange="updateDurSubs(this.value); reloadForNotes()">
            <option value="">— Wybierz linię —</option>
            <?php foreach ($lines as $l): ?>
              <option value="<?= $l['id'] ?>"
                <?= (int)$l['id'] === (int)($_GET['line_id'] ?? 0) ? 'selected' : '' ?>>
                <?= Helpers::e($l['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label class="flbl">Podzespół</label>
          <select name="subsystem_id" class="fc" id="durSubSel">
            <option value="">— brak / nie dotyczy —</option>
          </select>
        </div>
        <div class="fg">
          <label class="flbl">Typ przeglądu <span class="req">*</span></label>
          <?php
          $allTypes = [
            'weekly'    => 'Tygodniowy',
            'monthly'   => 'Miesięczny',
            'quarterly' => 'Kwartalny',
            'biannual'  => 'Półroczny',
            'annual'    => 'Roczny',
            'ad_hoc'    => 'Doraźny',
            'periodic'  => 'Okresowy',
          ];
          // Wczytaj niestandardowe nazwy typów z ustawień admina
          try {
            $tl = (new \App\Models\SettingsModel())->get('dur_type_labels');
            if ($tl) {
              foreach (json_decode($tl, true) ?? [] as $k => $v) {
                if (isset($allTypes[$k])) $allTypes[$k] = $v;
              }
            }
          } catch (\Throwable $e) {
          }
          ?>
          <select name="review_type" class="fc" required id="reviewTypeSel" onchange="reloadForNotes()">
            <?php foreach ($allTypes as $key => $label): ?>
              <?php if (in_array($key, $activeTypes)): ?>
                <option value="<?= $key ?>"
                  <?= ($key === ($_GET['review_type'] ?? 'monthly')) ? 'selected' : '' ?>>
                  <?= $label ?>
                </option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if ($preSchedule && !empty($scheduleNotes)): ?>
          <div class="alert" style="background:#f5f3ff;border:1px solid #c4b5fd;
                             border-radius:8px;padding:12px 14px;margin-bottom:12px;">
            <div class="fw6 fs-sm" style="color:#4c1d95;margin-bottom:8px;">
              📝 Uwagi do tego przeglądu (<?= count($scheduleNotes) ?>):
            </div>
            <?php foreach ($scheduleNotes as $sn): ?>
              <div style="padding:6px 0;border-bottom:1px solid #e9d5ff;font-size:13px;">
                <span class="fw6"><?= \App\Helpers\Helpers::e($sn['user_name']) ?>:</span>
                <span style="margin-left:6px;"><?= nl2br(\App\Helpers\Helpers::e($sn['note'])) ?></span>
                <span class="muted" style="font-size:11px;margin-left:8px;">
                  <?= substr($sn['created_at'], 0, 16) ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php elseif ($preSchedule): ?>
          <div class="muted fs-sm" style="margin-bottom:10px;">📝 Brak uwag do wybranego przeglądu.</div>
        <?php endif; ?>

        <div class="fg">
          <label class="flbl">Data przeglądu <span class="req">*</span></label>
          <input name="review_date" type="date" class="fc" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="fg">
          <label class="flbl">Czas trwania (minuty)</label>
          <input name="duration_minutes" type="number" class="fc" placeholder="np. 90" min="1">
        </div>
        <div class="fg">
          <label class="flbl">Szablon</label>
          <select name="template_id" class="fc" id="durTemplate" onchange="fillDurTemplate()">
            <option value="">— Bez szablonu —</option>
            <?php foreach ($templates as $t): ?>
              <option value="<?= $t['id'] ?>" data-checklist="<?= Helpers::e($t['checklist'] ?? '') ?>">
                <?= Helpers::e($t['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="fg">
        <label class="flbl">Wykonane czynności <span class="req">*</span></label>
        <textarea name="activities" class="fc" rows="6" id="durActivities" required
          placeholder="- Kontrola wizualna maszyny&#10;- Smarowanie prowadnic..."></textarea>
      </div>
      <div class="fg">
        <label class="flbl">Uwagi i zalecenia</label>
        <textarea name="notes" class="fc" rows="2" placeholder="np. Zalecana wymiana łożyska..."></textarea>
      </div>
      <div class="g2">
        <div class="fg">
          <label class="flbl">Data następnego przeglądu</label>
          <input name="next_review_date" type="date" class="fc">
        </div>
        <div class="fg">
          <label class="flbl">Status przeglądu</label>
          <select name="status" class="fc">
            <?php
            $durSC = [];
            try {
              $durSCraw = (new \App\Models\SettingsModel())->get('dur_review_statuses');
              if ($durSCraw) $durSC = json_decode($durSCraw, true) ?? [];
            } catch (\Throwable $e) {
            }
            $durSC += [
              'completed'   => ['label' => 'Zakończony'],
              'partial'     => ['label' => 'Częściowy — do dokończenia'],
              'interrupted' => ['label' => 'Przerwany — brak części'],
            ];
            foreach (['completed', 'partial', 'interrupted'] as $sKey):
            ?>
              <option value="<?= $sKey ?>"><?= \App\Helpers\Helpers::e($durSC[$sKey]['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

      </div>
      <div class="sep"></div>
      <div style="display:flex;gap:8px;">
        <button type="submit" class="btn btn-v">Zapisz raport DUR</button>
        <a href="<?= BASE_URL ?>/index.php?route=dur" class="btn">Anuluj</a>
      </div>
      <p class="muted fs-sm" style="margin-top:10px;">
        💡 Części zamienne będziesz mógł dodać po zapisaniu raportu!!!
      </p>
    </form>
  </div>
</div>

<script>
  var SUBSYSTEMS = <?= json_encode($subsystemsJs, JSON_HEX_TAG) ?>;

  function updateDurSubs(lineId) {
    var sel = document.getElementById('durSubSel');
    var subs = SUBSYSTEMS[lineId] || [];
    sel.innerHTML = '<option value="">— brak / nie dotyczy —</option>';
    subs.forEach(function(s) {
      var o = document.createElement('option');
      o.value = s.id;
      o.text = s.name;
      sel.appendChild(o);
    });
  }

  function fillDurTemplate() {
    var sel = document.getElementById('durTemplate');
    var opt = sel.selectedOptions[0];
    var cl = opt ? opt.dataset.checklist : '';
    var ta = document.getElementById('durActivities');
    if (cl) ta.value = cl;
  }

  function reloadForNotes() {
    var lineId = document.getElementById('durLineSel') ?
      document.getElementById('durLineSel').value : '';
    var typeSel = document.getElementById('reviewTypeSel');
    var type = typeSel ? typeSel.value : '';
    if (lineId && type) {
      window.location.href = '<?= BASE_URL ?>/index.php?route=dur_add' +
        '&line_id=' + encodeURIComponent(lineId) +
        '&review_type=' + encodeURIComponent(type);
    }
  }

  // Po załadowaniu strony — jeśli linia jest preselected, wypełnij podzespoły
  document.addEventListener('DOMContentLoaded', function() {
    var lineId = document.getElementById('durLineSel').value;
    if (lineId) {
      updateDurSubs(lineId);
    }
  });
</script>
<?php require BASE_PATH . '/templates/shared/footer.php'; ?>