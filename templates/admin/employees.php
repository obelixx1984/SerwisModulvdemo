<?php
use App\Helpers\Helpers;
$pageTitle = 'Pracownicy / Akronimy';
require BASE_PATH . '/templates/shared/header.php';
?>

<div class="atabs mb2">
  <a href="<?= BASE_URL ?>/index.php?route=admin_users"     class="atab">Użytkownicy</a>
  <button class="atab active" data-tab="akronimy">Pracownicy / Akronimy</button>
  <a href="<?= BASE_URL ?>/index.php?route=admin_lines"     class="atab">Linie i podzespoły</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_statuses"  class="atab">Statusy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dictionary" class="atab">Słownik awarii</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dur_tmpl"  class="atab v">Szablony DUR</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dur_sched" class="atab v">Harmonogram DUR</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_settings"  class="atab">Ustawienia</a>
</div>

<div style="display:grid;grid-template-columns:1fr 360px;gap:16px;align-items:start;">
  <div class="card">
    <div class="card-head">
      <span class="card-title">Lista pracowników z akronimami</span>
      <span class="muted fs-sm">Akronimy używane w formularzu zgłoszenia awarii</span>
    </div>
    <div class="twrap">
      <table>
        <thead><tr>
          <th style="width:90px;">Akronim</th>
          <th>Imię i nazwisko</th>
          <th>Stanowisko</th>
          <th>Aktywny</th>
          <th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($employees as $e): ?>
        <tr>
          <td style="font-weight:700;font-family:monospace;"><?= Helpers::e($e['acronym']) ?></td>
          <td><?= Helpers::e($e['name']) ?></td>
          <td class="muted fs-sm"><?= Helpers::e($e['position'] ?? '—') ?></td>
          <td><?= Helpers::statusBadge($e['is_active'] ? 'Tak' : 'Nie', $e['is_active'] ? '#16a34a' : '#6b7280') ?></td>
          <td>
            <button class="btn btn-sm" onclick="editEmp(<?= $e['id'] ?>,'<?= Helpers::e($e['acronym']) ?>','<?= Helpers::e($e['name']) ?>','<?= Helpers::e($e['position'] ?? '') ?>',<?= $e['is_active'] ?>)">
              Edytuj
            </button>
            <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_emp_delete" style="display:inline;" onsubmit="return confirm('Usunąć pracownika <?= Helpers::e($e['name']) ?>?');">
              <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
              <input type="hidden" name="emp_id" value="<?= $e['id'] ?>">
              <button type="submit" class="btn btn-sm" style="border-color:#fca5a5;color:#dc2626;">Usuń</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><span class="card-title" id="empFormTitle">Dodaj pracownika</span></div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_emp_save">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
        <input type="hidden" name="emp_id" id="empId" value="0">
        <div class="fg">
          <label class="flbl">Akronim (2–5 liter) <span class="req">*</span></label>
          <input class="fc" name="acronym" id="empAk" placeholder="np. JKO" maxlength="5" style="text-transform:uppercase;">
          <span class="fhint">Wyświetlany w liście zgłaszających</span>
        </div>
        <div class="fg">
          <label class="flbl">Imię i nazwisko <span class="req">*</span></label>
          <input class="fc" name="name" id="empName" placeholder="np. Jan Kowalski">
        </div>
        <div class="fg">
          <label class="flbl">Stanowisko</label>
          <input class="fc" name="position" id="empPos" placeholder="np. Operator Zm. I">
        </div>
        <div class="fg">
          <label class="flbl">Aktywny</label>
          <select class="fc" name="is_active" id="empActive">
            <option value="1">Tak</option>
            <option value="0">Nie</option>
          </select>
        </div>
        <div style="display:flex;gap:8px;">
          <button type="submit" class="btn btn-p btn-sm">Zapisz pracownika</button>
          <button type="button" class="btn btn-sm" onclick="resetEmpForm()">Nowy</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editEmp(id, ak, name, pos, active) {
  document.getElementById('empId').value     = id;
  document.getElementById('empAk').value     = ak;
  document.getElementById('empName').value   = name;
  document.getElementById('empPos').value    = pos;
  document.getElementById('empActive').value = active;
  document.getElementById('empFormTitle').textContent = 'Edytuj pracownika';
}
function resetEmpForm() {
  ['empId','empAk','empName','empPos'].forEach(function(id){ document.getElementById(id).value=''; });
  document.getElementById('empActive').value = '1';
  document.getElementById('empFormTitle').textContent = 'Dodaj pracownika';
}
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
