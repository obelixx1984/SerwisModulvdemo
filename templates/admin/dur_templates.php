<?php
use App\Helpers\Helpers;
$pageTitle = 'Szablony DUR';
require BASE_PATH . '/templates/shared/header.php';
?>

<div class="atabs mb2">
  <a href="<?= BASE_URL ?>/index.php?route=admin_users"      class="atab">Użytkownicy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_lines"      class="atab">Linie i podzespoły</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_statuses"   class="atab">Statusy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dictionary" class="atab">Słownik awarii</a>
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
            <option value="weekly">Tygodniowy</option>
            <option value="monthly" selected>Miesięczny</option>
            <option value="quarterly">Kwartalny</option>
            <option value="biannual">Półroczny</option>
            <option value="annual">Roczny</option>
            <option value="ad_hoc">Doraźny</option>
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

<!-- BŁĄD 8: Zarządzanie typami przeglądów -->
<div class="card mt2" style="margin-top:16px;">
  <div class="card-head"><span class="card-title">Typy przeglądów DUR</span></div>
  <div class="card-body">
    <div class="alert alert-i fs-sm mb2">
      Dostępne typy są zdefiniowane systemowo. Możesz zmienić ich etykiety wyświetlane w aplikacji.
    </div>
    <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_dur_types_save">
      <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
        <?php
        $typeKeys = ['weekly','monthly','quarterly','biannual','annual','ad_hoc'];
        $defaultLabels = ['weekly'=>'Tygodniowy','monthly'=>'Miesięczny','quarterly'=>'Kwartalny',
                          'biannual'=>'Półroczny','annual'=>'Roczny','ad_hoc'=>'Doraźny'];
        $savedLabels = [];
        try {
            $db = \App\Helpers\Database::get();
            $st = $db->prepare("SELECT svalue FROM settings WHERE skey='dur_type_labels' LIMIT 1");
            $st->execute();
            $val = $st->fetchColumn();
            if ($val) $savedLabels = json_decode($val, true) ?? [];
        } catch(\Throwable $e) {}
        foreach ($typeKeys as $key):
          $label = $savedLabels[$key] ?? $defaultLabels[$key];
        ?>
        <div class="fg">
          <label class="flbl"><?= $key ?></label>
          <input class="fc" name="type_<?= $key ?>" value="<?= Helpers::e($label) ?>">
        </div>
        <?php endforeach; ?>
      </div>
      <button type="submit" class="btn btn-p btn-sm mt1">Zapisz etykiety typów</button>
    </form>
  </div>
</div>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
