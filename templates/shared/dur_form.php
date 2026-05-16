<?php

use App\Helpers\Helpers;

$pageTitle = 'Nowy raport DUR';
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
          <select name="production_line_id" class="fc" required id="durLineSel" onchange="updateDurSubs(this.value)">
            <option value="">— Wybierz linię —</option>
            <?php foreach ($lines as $l): ?>
              <option value="<?= $l['id'] ?>"><?= Helpers::e($l['name']) ?></option>
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
          ];
          ?>
          <select name="review_type" class="fc" required>
            <?php foreach ($allTypes as $key => $label): ?>
              <?php if (in_array($key, $activeTypes)): ?>
                <option value="<?= $key ?>" <?= $key === 'monthly' ? 'selected' : '' ?>>
                  <?= $label ?>
                </option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
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
        <label class="flbl">Wymienione części i materiały</label>
        <textarea name="parts_used" class="fc" rows="2" placeholder="np. Smar litowy 200g"></textarea>
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
</script>
<?php require BASE_PATH . '/templates/shared/footer.php'; ?>