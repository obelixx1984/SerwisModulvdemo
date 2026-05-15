<?php
use App\Helpers\Helpers;
use App\Helpers\Auth;

$pageTitle = 'Objawy awarii';
require BASE_PATH . '/templates/shared/header.php';
?>

<div class="atabs mb2">
  <a href="<?= BASE_URL ?>/index.php?route=admin_users"      class="atab">Użytkownicy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_lines"      class="atab">Linie i podzespoły</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_statuses"   class="atab">Statusy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dictionary" class="atab">Słownik awarii</a>
  <button class="atab active">Objawy awarii</button>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dur_tmpl"   class="atab v">Szablony DUR</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dur_sched"  class="atab v">Harmonogram DUR</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_settings"   class="atab">Ustawienia</a>
</div>

<div class="g2">

  <!-- LISTA OBJAWÓW -->
  <div>
    <div class="card">
      <div class="card-head">
        <span class="card-title">Objawy awarii (<?= count($symptoms) ?>)</span>
        <span class="muted fs-sm">Wybierane przez zgłaszającego przy nowym zgłoszeniu</span>
      </div>
      <div class="twrap">
        <table>
          <thead>
            <tr>
              <th style="width:40px;">#</th>
              <th>Nazwa objawu</th>
              <th style="width:60px;text-align:center;">Kol.</th>
              <th style="width:70px;text-align:center;">Użyć</th>
              <th style="width:70px;text-align:center;">Aktywny</th>
              <th style="width:100px;"></th>
            </tr>
          </thead>
          <tbody>
            <?php if ($symptoms): ?>
              <?php foreach ($symptoms as $s): ?>
              <tr>
                <td class="muted fs-sm mono"><?= (int)$s['sort_order'] ?></td>
                <td class="fw6"><?= Helpers::e($s['name']) ?></td>
                <td style="text-align:center;">
                  <span class="dot" style="background:<?= $s['is_active'] ? '#16a34a' : '#d1d5db' ?>;width:10px;height:10px;"></span>
                </td>
                <td style="text-align:center;">
                  <?php if ($s['usage_count'] > 0): ?>
                    <span class="badge" style="background:#e0e7ff;color:#3730a3;"><?= (int)$s['usage_count'] ?></span>
                  <?php else: ?>
                    <span class="muted fs-sm">0</span>
                  <?php endif; ?>
                </td>
                <td style="text-align:center;">
                  <?php if ($s['is_active']): ?>
                    <span class="badge" style="background:#ecfdf5;color:#065f46;">Tak</span>
                  <?php else: ?>
                    <span class="badge" style="background:#f3f4f6;color:#6b7280;">Nie</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="display:flex;gap:4px;">
                    <button class="btn btn-sm"
                      onclick="editSymptom(<?= $s['id'] ?>, <?= htmlspecialchars(json_encode($s['name']), ENT_QUOTES) ?>, <?= (int)$s['sort_order'] ?>, <?= $s['is_active'] ? 1 : 0 ?>)">
                      Edytuj
                    </button>
                    <?php if ((int)$s['usage_count'] === 0): ?>
                    <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_symptom_delete"
                          style="display:inline;"
                          onsubmit="return confirm('Usunąć objaw &quot;<?= Helpers::e(addslashes($s['name'])) ?>&quot;?');">
                      <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
                      <input type="hidden" name="symptom_id" value="<?= $s['id'] ?>">
                      <button type="submit" class="btn btn-sm" style="color:#dc2626;border-color:#fca5a5;">Usuń</button>
                    </form>
                    <?php else: ?>
                    <span class="muted fs-sm" title="Objaw jest używany w zgłoszeniach — dezaktywuj zamiast usuwać" style="cursor:help;">🔒</span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" style="text-align:center;padding:24px;" class="muted">
                  Brak objawów. Dodaj pierwszy objaw →
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- FORMULARZ DODAJ / EDYTUJ -->
  <div>
    <div class="card">
      <div class="card-head">
        <span class="card-title" id="formTitle">Dodaj nowy objaw</span>
        <button class="btn btn-sm" onclick="resetForm()" id="resetBtn" style="display:none;">✕ Anuluj edycję</button>
      </div>
      <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_symptom_save" id="symptomForm">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
          <input type="hidden" name="symptom_id" id="symptomId" value="0">

          <div class="fg">
            <label class="flbl" for="symptomName">Nazwa objawu <span class="req">*</span></label>
            <input type="text" name="name" id="symptomName" class="fc" required
              placeholder="np. Maszyna nie reaguje, Brak komunikacji..."
              maxlength="200">
            <span class="fhint">Widoczna dla pracownika na formularzu zgłoszenia awarii.</span>
          </div>

          <div class="fg">
            <label class="flbl" for="symptomOrder">Kolejność wyświetlania</label>
            <input type="number" name="sort_order" id="symptomOrder" class="fc"
              value="0" min="0" max="999" style="max-width:120px;">
            <span class="fhint">Niższy numer = wyżej na liście. Używaj co 10 (10, 20, 30...) by ułatwić późniejsze wstawianie.</span>
          </div>

          <div class="fg" id="activeGrp" style="display:none;">
            <label class="flbl">Aktywny</label>
            <div style="display:flex;gap:12px;margin-top:4px;">
              <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                <input type="radio" name="is_active" value="1" id="activeYes" checked>
                <span>Tak</span>
              </label>
              <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                <input type="radio" name="is_active" value="0" id="activeNo">
                <span>Nie (dezaktywowany — nie pojawi się w formularzu)</span>
              </label>
            </div>
          </div>

          <button type="submit" class="btn btn-p btn-block" id="submitBtn">+ Dodaj objaw</button>
        </form>
      </div>
    </div>

    <div class="card mt2" style="margin-top:12px;">
      <div class="card-head"><span class="card-title">ℹ Jak to działa?</span></div>
      <div class="card-body fs-sm" style="color:#374151;line-height:1.7;">
        <p class="mb1"><strong>Objawy</strong> to lista opcji widoczna dla pracownika (operatora, producji) gdy wypełnia formularz zgłoszenia awarii.</p>
        <p class="mb1">Pracownik wybiera <strong>jeden objaw</strong> zamiast kategorii technicznej — np. "Maszyna nie reaguje" zamiast "Elektryczna / Brak zasilania maszyny".</p>
        <p class="mb1"><strong>Mechanik</strong> następnie uzupełnia kategorię i rodzaj usterki z poziomu szczegółów zgłoszenia.</p>
        <p><strong>Duplikaty</strong> są wykrywane na podstawie tego samego objawu na tej samej linii.</p>
      </div>
    </div>
  </div>

</div>

<script>
function editSymptom(id, name, sortOrder, isActive) {
  document.getElementById('symptomId').value    = id;
  document.getElementById('symptomName').value  = name;
  document.getElementById('symptomOrder').value = sortOrder;

  if (isActive) {
    document.getElementById('activeYes').checked = true;
  } else {
    document.getElementById('activeNo').checked = true;
  }

  document.getElementById('activeGrp').style.display = 'block';
  document.getElementById('formTitle').textContent    = 'Edytuj objaw';
  document.getElementById('submitBtn').textContent    = 'Zapisz zmiany';
  document.getElementById('resetBtn').style.display  = 'inline-flex';

  document.getElementById('symptomName').focus();
  document.getElementById('symptomForm').scrollIntoView({behavior:'smooth', block:'start'});
}

function resetForm() {
  document.getElementById('symptomId').value    = '0';
  document.getElementById('symptomName').value  = '';
  document.getElementById('symptomOrder').value = '0';
  document.getElementById('activeYes').checked  = true;
  document.getElementById('activeGrp').style.display = 'none';
  document.getElementById('formTitle').textContent   = 'Dodaj nowy objaw';
  document.getElementById('submitBtn').textContent   = '+ Dodaj objaw';
  document.getElementById('resetBtn').style.display  = 'none';
}
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
