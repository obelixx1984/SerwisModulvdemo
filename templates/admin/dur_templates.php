<?php
// templates/admin/dur_templates.php
// ZMIANA 3: sekcja "Typy przeglądów DUR" zmieniona z edycji etykiet
//           na checkboxy aktywności (które typy mają być dostępne)

use App\Helpers\Helpers;
$pageTitle = 'Szablony DUR';
require BASE_PATH . '/templates/shared/header.php';

// Pobierz aktywne typy z ustawień
$activeTypes = ['weekly','monthly','quarterly','biannual','annual','ad_hoc']; // domyślnie wszystkie
try {
    $saved = (new \App\Models\SettingsModel())->get('dur_active_review_types');
    if ($saved) {
        $decoded = json_decode($saved, true);
        if (is_array($decoded)) $activeTypes = $decoded;
    }
} catch (\Throwable $e) {}

$allTypes = [
    'weekly'    => 'Tygodniowy',
    'monthly'   => 'Miesięczny',
    'quarterly' => 'Kwartalny',
    'biannual'  => 'Półroczny',
    'annual'    => 'Roczny',
    'ad_hoc'    => 'Doraźny',
];
?>

<div class="atabs mb2">
  <a href="<?= BASE_URL ?>/index.php?route=admin_users"      class="atab">Użytkownicy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_lines"      class="atab">Linie i podzespoły</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_statuses"   class="atab">Statusy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dictionary" class="atab">Słownik awarii</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_symptoms"   class="atab">Objawy awarii</a>
  <button class="atab v active">Szablony DUR</button>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dur_sched"  class="atab v">Harmonogram DUR</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_settings"   class="atab">Ustawienia</a>
</div>

<div class="g2">
  <div class="card">
    <div class="card-head"><span class="card-title">Szablony przeglądów DUR (<?= count($templates) ?>)</span></div>
    <div class="twrap">
      <table>
        <thead><tr><th>Nazwa</th><th>Typ</th><th>Czynności</th><th>Aktywny</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($templates as $t): ?>
        <tr>
          <td class="fw6"><?= Helpers::e($t['name']) ?></td>
          <td><?= Helpers::statusBadge(Helpers::reviewTypeLabel($t['review_type']), '#7c3aed') ?></td>
          <td class="muted fs-sm"><?= count(array_filter(explode("\n", $t['checklist'] ?? ''), 'trim')) ?> poz.</td>
          <td><?= Helpers::statusBadge($t['is_active'] ? 'Tak' : 'Nie', $t['is_active'] ? '#16a34a' : '#6b7280') ?></td>
          <td>
            <button class="btn btn-sm edit-tmpl-btn"
              data-id="<?= $t['id'] ?>"
              data-name="<?= Helpers::e($t['name']) ?>"
              data-type="<?= Helpers::e($t['review_type']) ?>"
              data-checklist="<?= Helpers::e($t['checklist'] ?? '') ?>"
              data-active="<?= $t['is_active'] ?>">Edytuj</button>
            <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_tmpl_delete" style="display:inline;" onsubmit="return confirm('Usunąć szablon DUR?');">
              <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
              <input type="hidden" name="tmpl_id" value="<?= $t['id'] ?>">
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
    <div class="card-head"><span class="card-title" id="tmplFormTitle" style="color:#7c3aed;">Nowy szablon DUR</span></div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_tmpl_save">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
        <input type="hidden" name="tmpl_id" id="tmplId" value="0">
        <div class="fg">
          <label class="flbl">Nazwa szablonu <span class="req">*</span></label>
          <input class="fc" name="name" id="tmplName" placeholder="np. Przegląd tygodniowy lakierni">
        </div>
        <div class="fg">
          <label class="flbl">Typ przeglądu</label>
          <select class="fc" name="review_type" id="tmplType">
            <?php foreach ($allTypes as $key => $label): ?>
              <?php if (in_array($key, $activeTypes)): ?>
                <option value="<?= $key ?>" <?= $key === 'monthly' ? 'selected' : '' ?>><?= $label ?></option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label class="flbl">Lista czynności <span class="muted" style="font-weight:400;">(jedna per linia)</span></label>
          <textarea class="fc" name="checklist" id="tmplChecklist" rows="8"
                    placeholder="- Kontrola wizualna maszyny&#10;- Sprawdzenie poziomu olejów&#10;- Smarowanie punktów"></textarea>
        </div>
        <div class="fg">
          <label class="flbl">Aktywny</label>
          <select class="fc" name="is_active" id="tmplActive">
            <option value="1">Tak</option>
            <option value="0">Nie</option>
          </select>
        </div>
        <div style="display:flex;gap:8px;">
          <button type="submit" class="btn btn-v btn-sm">Zapisz szablon</button>
          <button type="button" class="btn btn-sm" onclick="resetTmplForm()">Nowy</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.edit-tmpl-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.getElementById('tmplId').value        = this.dataset.id;
    document.getElementById('tmplName').value      = this.dataset.name;
    document.getElementById('tmplType').value      = this.dataset.type;
    document.getElementById('tmplChecklist').value = this.dataset.checklist;
    document.getElementById('tmplActive').value    = this.dataset.active;
    document.getElementById('tmplFormTitle').textContent = 'Edytuj szablon DUR';
    window.scrollTo({top:0,behavior:'smooth'});
  });
});
function resetTmplForm(){
  document.getElementById('tmplId').value        = '0';
  document.getElementById('tmplName').value      = '';
  document.getElementById('tmplType').value      = 'monthly';
  document.getElementById('tmplChecklist').value = '';
  document.getElementById('tmplActive').value    = '1';
  document.getElementById('tmplFormTitle').textContent = 'Nowy szablon DUR';
}
</script>

<?php /* ZMIANA 3: checkboxy aktywności typów przeglądów DUR */ ?>
<div class="card" style="margin-top:16px;">
  <div class="card-head"><span class="card-title">Typy przeglądów DUR — aktywność</span></div>
  <div class="card-body">
    <div class="alert alert-i fs-sm" style="margin-bottom:14px;">
      Zaznacz typy przeglądów które mają być dostępne przy dodawaniu szablonów i harmonogramów.
      Odznaczenie typu nie usuwa istniejących danych — tylko ukrywa go w formularzach.
    </div>
    <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_dur_types_save">
      <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:16px;">
        <?php foreach ($allTypes as $key => $label): ?>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:8px 10px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:7px;">
            <input
              type="checkbox"
              name="active_types[]"
              value="<?= $key ?>"
              <?= in_array($key, $activeTypes) ? 'checked' : '' ?>
              style="width:15px;height:15px;cursor:pointer;flex-shrink:0;">
            <span style="font-size:13px;font-weight:600;color:#374151;"><?= $label ?></span>
          </label>
        <?php endforeach; ?>
      </div>
      <button type="submit" class="btn btn-p btn-sm">Zapisz aktywne typy</button>
    </form>
  </div>
</div>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
