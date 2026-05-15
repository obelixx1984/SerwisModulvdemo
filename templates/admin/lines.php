<?php
use App\Helpers\Helpers;
$pageTitle = 'Linie i podzespoły';
require BASE_PATH . '/templates/shared/header.php';
$year = date('Y');
?>

<div class="atabs mb2">
  <a href="<?= BASE_URL ?>/index.php?route=admin_users"      class="atab">Użytkownicy</a>
  <button class="atab active" data-tab="lines">Linie i podzespoły</button>
  <a href="<?= BASE_URL ?>/index.php?route=admin_statuses"   class="atab">Statusy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dictionary" class="atab">Słownik awarii</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_symptoms"   class="atab">Objawy awarii</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dur_tmpl"   class="atab v">Szablony DUR</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dur_sched"  class="atab v">Harmonogram DUR</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_settings"   class="atab">Ustawienia</a>
</div>

<div style="display:grid;grid-template-columns:1fr 420px;gap:16px;align-items:start;">
  <!-- Tabela linii -->
  <div>
    <div class="card">
      <div class="card-head"><span class="card-title">Skonfigurowane linie produkcyjne</span></div>
      <div class="twrap">
        <table>
          <thead><tr>
            <th>Nazwa linii</th>
            <th>Prefix / przykład numeru</th>
            <th>Podzespoły</th>
            <th>Aktywna</th>
            <th></th>
          </tr></thead>
          <tbody>
          <?php foreach ($lines as $l):
            $subsForJs = str_replace('|||', "
", $l['subsystems_str'] ?? '');
          ?>
          <tr>
            <td class="fw6"><?= Helpers::e($l['name']) ?></td>
            <td>
              <span class="badge" style="background:#0a2463;color:#fff;"><?= Helpers::e($l['prefix']) ?></span>
              <span class="muted fs-sm" style="margin-left:6px;">→ np. 0001/<?= Helpers::e($l['prefix']) ?>/<?= $year ?></span>
            </td>
            <td class="fs-sm muted">
              <?php if ($l['subsystems_str']): ?>
                <?= Helpers::e(str_replace('|||', ', ', $l['subsystems_str'])) ?>
              <?php else: ?>
                <span class="badge" style="background:#f3f4f6;color:#6b7280;">brak</span>
              <?php endif; ?>
            </td>
            <td><?= Helpers::statusBadge($l['is_active'] ? 'Tak' : 'Nie', $l['is_active'] ? '#16a34a' : '#6b7280') ?></td>
            <td>
              <button class="btn btn-sm edit-line-btn"
                data-id="<?= $l['id'] ?>"
                data-name="<?= Helpers::e($l['name']) ?>"
                data-prefix="<?= Helpers::e($l['prefix']) ?>"
                data-desc="<?= Helpers::e($l['description'] ?? '') ?>"
                data-subs="<?= Helpers::e($subsForJs) ?>"
                data-active="<?= $l['is_active'] ?>">Edytuj</button>
              <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_line_delete" style="display:inline;" onsubmit="return confirm('Usunąć linię <?= Helpers::e($l['name']) ?>? Usunięcie jest niemożliwe jeśli linia ma zgłoszenia.');">
                <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
                <input type="hidden" name="line_id" value="<?= $l['id'] ?>">
                <button type="submit" class="btn btn-sm" style="border-color:#fca5a5;color:#dc2626;">Usuń</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Formularz linii — POPRAWKA 1: pole Prefix -->
  <div class="card">
    <div class="card-head"><span class="card-title" id="lineFormTitle">Dodaj linię produkcyjną</span></div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_line_save" id="lineForm">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
        <input type="hidden" name="line_id" id="lineId" value="0">

        <div class="fg">
          <label class="flbl">Nazwa linii <span class="req">*</span></label>
          <input class="fc" name="name" id="lName" placeholder="np. Linia C3">
        </div>

        <div class="fg">
          <label class="flbl">Prefix numerów zgłoszeń <span class="req">*</span></label>
          <input class="fc" name="prefix" id="lPrefix" placeholder="np. C3" maxlength="6"
                 style="text-transform:uppercase;" oninput="updatePreview()">
          <span class="fhint">Format numeru zgłoszenia: <strong id="prefixExample">0001/??/<?= $year ?></strong></span>
        </div>

        <div class="fg">
          <label class="flbl">Podzespoły (jeden per linia, pozostaw puste jeśli brak)</label>
          <textarea class="fc" name="subsystems" id="lSubsystems" rows="4"
                    placeholder="Rozwijak&#10;Nawijak&#10;Pakowaczka"></textarea>
          <span class="fhint">Każda linia tekstu = jeden podzespół</span>
        </div>

        <div class="fg">
          <label class="flbl">Opis (opcjonalnie)</label>
          <input class="fc" name="description" id="lDesc" placeholder="Krótki opis linii">
        </div>

        <div class="fg">
          <label class="flbl">Aktywna</label>
          <select class="fc" name="is_active" id="lActive">
            <option value="1">Tak</option>
            <option value="0">Nie</option>
          </select>
        </div>

        <!-- Podgląd numeru -->
        <div class="preview-box mb2" id="linePreview" style="display:none;">
          <div class="lbl">✓ PODGLĄD KONFIGURACJI</div>
          <div style="display:grid;grid-template-columns:130px 1fr;gap:4px 10px;font-size:13px;" id="linePreviewContent"></div>
        </div>

        <div style="display:flex;gap:8px;">
          <button type="submit" class="btn btn-p btn-sm">Zapisz linię</button>
          <button type="button" class="btn btn-sm" onclick="resetLineForm()">Nowa</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var CURRENT_YEAR = <?= $year ?>;

function updatePreview() {
  var prefix  = document.getElementById('lPrefix').value.toUpperCase() || '??';
  var example = '0001/' + prefix + '/' + CURRENT_YEAR;
  document.getElementById('prefixExample').textContent = example;

  var name = document.getElementById('lName').value;
  var subs = document.getElementById('lSubsystems').value.trim();
  if (name && prefix !== '??') {
    document.getElementById('linePreview').style.display = 'block';
    document.getElementById('linePreviewContent').innerHTML =
      '<div style="color:#6b7280;font-weight:600;">Nazwa:</div><div>' + name + '</div>' +
      '<div style="color:#6b7280;font-weight:600;">Prefix:</div><div><strong>' + prefix + '</strong></div>' +
      '<div style="color:#6b7280;font-weight:600;">Format numeru:</div><div><strong>' + example + '</strong></div>' +
      '<div style="color:#6b7280;font-weight:600;">Podzespoły:</div><div>' + (subs ? subs.split('\n').filter(Boolean).join(', ') : 'brak') + '</div>';
  }
}

function editLine(btn) {
  document.getElementById('lineId').value      = btn.dataset.id;
  document.getElementById('lName').value       = btn.dataset.name;
  document.getElementById('lPrefix').value     = btn.dataset.prefix;
  document.getElementById('lDesc').value       = btn.dataset.desc;
  document.getElementById('lSubsystems').value = btn.dataset.subs;
  document.getElementById('lActive').value     = btn.dataset.active;
  document.getElementById('lineFormTitle').textContent = 'Edytuj linię';
  updatePreview();
  window.scrollTo({top:0,behavior:'smooth'});
}
document.querySelectorAll('.edit-line-btn').forEach(function(btn){
  btn.addEventListener('click', function(){ editLine(this); });
});

function resetLineForm() {
  document.getElementById('lineId').value = '0';
  ['lName','lPrefix','lDesc','lSubsystems'].forEach(function(id){ document.getElementById(id).value=''; });
  document.getElementById('lActive').value = '1';
  document.getElementById('lineFormTitle').textContent = 'Dodaj linię produkcyjną';
  document.getElementById('linePreview').style.display = 'none';
  document.getElementById('prefixExample').textContent = '0001/??/' + CURRENT_YEAR;
}

document.getElementById('lName').addEventListener('input', updatePreview);
document.getElementById('lSubsystems').addEventListener('input', updatePreview);
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
