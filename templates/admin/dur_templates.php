<?php
// templates/admin/dur_templates.php
// ZMIANA 3: sekcja "Typy przeglądów DUR" zmieniona z edycji etykiet
//           na checkboxy aktywności (które typy mają być dostępne)

use App\Helpers\Helpers;

$pageTitle = 'Szablony DUR';
require BASE_PATH . '/templates/shared/header.php';

// Pobierz aktywne typy z ustawień
$activeTypes = ['weekly', 'monthly', 'quarterly', 'biannual', 'annual', 'ad_hoc']; // domyślnie wszystkie
try {
  $saved = (new \App\Models\SettingsModel())->get('dur_active_review_types');
  if ($saved) {
    $decoded = json_decode($saved, true);
    if (is_array($decoded)) $activeTypes = $decoded;
  }
} catch (\Throwable $e) {
}

$allTypes = [
  'weekly'    => 'Tygodniowy',
  'monthly'   => 'Miesięczny',
  'quarterly' => 'Kwartalny',
  'biannual'  => 'Półroczny',
  'annual'    => 'Roczny',
  'ad_hoc'    => 'Doraźny',
  'periodic'  => 'Okresowy',   // ← NOWE
];

// Etykiety niestandardowe (z kontrolera lub bazy)
$typeLabels = $typeLabels ?? [];

// Nadpisz nazwy niestandardowymi etykietami admina
foreach ($typeLabels as $k => $v) {
  if (isset($allTypes[$k]) && $v !== '') $allTypes[$k] = $v;
}

?>

<div class="atabs mb2">
  <a href="<?= BASE_URL ?>/index.php?route=admin_users" class="atab">Użytkownicy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_lines" class="atab">Linie i podzespoły</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_statuses" class="atab">Statusy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dictionary" class="atab">Słownik awarii</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_symptoms" class="atab">Objawy awarii</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_spare_parts" class="atab">Części zamienne</a>
  <button class="atab v active">Szablony DUR</button>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dur_sched" class="atab v">Harmonogram DUR</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_settings" class="atab">Ustawienia</a>
</div>

<div class="g2">
  <div class="card">
    <div class="card-head"><span class="card-title">Szablony przeglądów DUR (<?= count($templates) ?>)</span></div>
    <div class="twrap">
      <table>
        <thead>
          <tr>
            <th>Nazwa</th>
            <th>Czynności</th>
            <th>Aktywny</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($templates as $t): ?>
            <tr>
              <td class="fw6"><?= Helpers::e($t['name']) ?></td>
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
  document.querySelectorAll('.edit-tmpl-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      document.getElementById('tmplId').value = this.dataset.id;
      document.getElementById('tmplName').value = this.dataset.name;
      document.getElementById('tmplChecklist').value = this.dataset.checklist;
      document.getElementById('tmplActive').value = this.dataset.active;
      document.getElementById('tmplFormTitle').textContent = 'Edytuj szablon DUR';
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  });

  function resetTmplForm() {
    document.getElementById('tmplId').value = '0';
    document.getElementById('tmplName').value = '';
    document.getElementById('tmplChecklist').value = '';
    document.getElementById('tmplActive').value = '1';
    document.getElementById('tmplFormTitle').textContent = 'Nowy szablon DUR';
  }
</script>

<?php /* ZMIANA 3: checkboxy aktywności typów przeglądów DUR */ ?>
<div class="card" style="margin-top:16px;">
  <div class="card-head"><span class="card-title">Typy przeglądów DUR — aktywność</span></div>
  <div class="card" style="align-self:start;">
    <div class="card-head"><span class="card-title">Typy przeglądów DUR</span></div>
    <div class="card-body">
      <p class="muted fs-sm mb1">
        Zaznacz które typy mają być dostępne w formularzach DUR.<br>
        Możesz też zmienić nazwę wyświetlaną każdego typu.
      </p>
      <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_dur_tmpl_save">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
        <input type="hidden" name="save_types" value="1">

        <?php
        /*
       * $allTypes i $typeLabels muszą być dostępne w tym szablonie.
       * $allTypes    — klucz => domyślna etykieta
       * $typeLabels  — klucz => niestandardowa etykieta z bazy
       * $activeTypes — tablica aktywnych kluczy
       */
        ?>

        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:14px;">
          <?php foreach ($allTypes as $key => $defaultLabel):
            $isActive    = in_array($key, $activeTypes);
            $customLabel = $typeLabels[$key] ?? '';
            $placeholder = $defaultLabel;
          ?>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 10px;
                    background:<?= $isActive ? '#f0f4ff' : '#f9fafb' ?>;
                    border:1px solid <?= $isActive ? '#c7d2fe' : '#e5e7eb' ?>;
                    border-radius:7px;">

              <!-- Checkbox aktywności -->
              <label style="display:flex;align-items:center;gap:6px;cursor:pointer;flex-shrink:0;min-width:22px;">
                <input
                  type="checkbox"
                  name="type_active[<?= $key ?>]"
                  value="1"
                  <?= $isActive ? 'checked' : '' ?>
                  style="width:15px;height:15px;cursor:pointer;accent-color:#0a2463;">
              </label>

              <!-- Klucz systemowy -->
              <span class="mono muted fs-sm" style="min-width:72px;flex-shrink:0;"><?= $key ?></span>

              <!-- Pole edycji nazwy -->
              <input
                type="text"
                name="type_label[<?= $key ?>]"
                class="fc"
                style="margin:0;flex:1;font-size:13px;padding:5px 9px;"
                value="<?= \App\Helpers\Helpers::e($customLabel) ?>"
                placeholder="<?= \App\Helpers\Helpers::e($placeholder) ?>">

              <!-- Podgląd obecnej etykiety -->
              <span class="badge" style="background:#7c3aed;color:#fff;flex-shrink:0;font-size:11px;">
                <?= \App\Helpers\Helpers::e($customLabel ?: $defaultLabel) ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>

        <button type="submit" class="btn btn-p btn-sm">Zapisz typy i nazwy</button>
      </form>
    </div>
  </div>
</div>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>