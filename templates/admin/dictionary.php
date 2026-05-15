<?php
use App\Helpers\Helpers;
$pageTitle = 'Słownik awarii';
require BASE_PATH . '/templates/shared/header.php';
?>

<div class="atabs mb2">
  <a href="<?= BASE_URL ?>/index.php?route=admin_users"      class="atab">Użytkownicy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_lines"      class="atab">Linie i podzespoły</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_statuses"   class="atab">Statusy</a>
  <button class="atab active" data-tab="dict">Słownik awarii</button>
  <a href="<?= BASE_URL ?>/index.php?route=admin_symptoms"   class="atab">Objawy awarii</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dur_tmpl"   class="atab v">Szablony DUR</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dur_sched"  class="atab v">Harmonogram DUR</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_settings"   class="atab">Ustawienia</a>
</div>

<!-- POPRAWKA 11: Sekcja kategorii z kolorami -->
<div class="card mb2">
  <div class="card-head">
    <span class="card-title">Kategorie awarii</span>
    <span class="muted fs-sm">Zarządzaj kategoriami i ich kolorami</span>
  </div>
  <div style="display:grid;grid-template-columns:1fr 380px;gap:16px;padding:14px 16px;align-items:start;">
    <!-- Tabela kategorii -->
    <div class="twrap">
      <table>
        <thead><tr><th>Kategoria</th><th>Kolor</th><th>Kolejność</th><th>Aktywna</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($categories as $cat): ?>
        <tr>
          <td><?= Helpers::catBadge($cat['label'], $cat['color']) ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:6px;">
              <span style="display:inline-block;width:16px;height:16px;border-radius:3px;background:<?= Helpers::e($cat['color']) ?>;"></span>
              <span class="muted fs-sm"><?= Helpers::e($cat['color']) ?></span>
            </div>
          </td>
          <td class="muted fs-sm"><?= $cat['sort_order'] ?></td>
          <td><?= Helpers::statusBadge($cat['is_active'] ? 'Tak' : 'Nie', $cat['is_active'] ? '#16a34a' : '#6b7280') ?></td>
          <td>
            <button class="btn btn-sm" onclick="editCat(
              <?= $cat['id'] ?>,
              '<?= Helpers::e(addslashes($cat['label'])) ?>',
              '<?= Helpers::e($cat['color']) ?>',
              <?= $cat['sort_order'] ?>,
              <?= $cat['is_active'] ?>
            )">Edytuj</button>
            <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_cat_save"
                  style="display:inline;"
                  onsubmit="return confirm('Usunąć kategorię? Upewnij się że nie ma przypisanych usterek.');">
              <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
              <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
              <input type="hidden" name="delete_cat" value="1">
              <button type="submit" class="btn btn-sm" style="border-color:#fca5a5;color:#dc2626;">Usuń</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Formularz kategorii -->
    <div>
      <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_cat_save">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
        <input type="hidden" name="cat_id" id="catId" value="0">

        <div class="fg">
          <label class="flbl">Nazwa kategorii <span class="req">*</span></label>
          <input class="fc" name="label" id="catLabel" placeholder="np. Pneumatyczna">
        </div>

        <div class="fg">
          <label class="flbl">Kolor</label>
          <div style="display:flex;align-items:center;gap:10px;">
            <input type="color" name="color" id="katKolor" value="#0891b2"
                   style="width:40px;height:34px;padding:2px;border:1px solid #e5e7eb;border-radius:6px;cursor:pointer;">
            <span id="katKolorPrev" class="badge" style="background:#0891b2;color:#fff;">Podgląd</span>
          </div>
        </div>

        <div class="fg">
          <label class="flbl">Kolejność</label>
          <input class="fc" type="number" name="sort_order" id="catOrder" value="0" style="width:80px;">
        </div>

        <div class="fg">
          <label class="flbl">Aktywna</label>
          <select class="fc" name="is_active" id="catActive">
            <option value="1">Tak</option>
            <option value="0">Nie</option>
          </select>
        </div>

        <div style="display:flex;gap:8px;">
          <button type="submit" class="btn btn-p btn-sm">Zapisz kategorię</button>
          <button type="button" class="btn btn-sm" onclick="resetCatForm()">Nowa</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Słownik usterek -->
<div style="display:grid;grid-template-columns:1fr 380px;gap:16px;align-items:start;">
  <div class="card">
    <div class="card-head"><span class="card-title">Słownik usterek</span></div>
    <div class="twrap">
      <table>
        <thead><tr><th>Usterka</th><th>Kategoria</th><th>Akt.</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($dictionary as $d): ?>
        <tr>
          <td class="fw6"><?= Helpers::e($d['title']) ?></td>
          <td><?= Helpers::catBadge($d['cat_label'], $d['cat_color']) ?></td>
          <td><?= $d['is_active'] ? '✓' : '✗' ?></td>
          <td style="display:flex;gap:4px;align-items:center;">
            <button class="btn btn-sm edit-dict-btn"
              data-id="<?= $d['id'] ?>"
              data-title="<?= Helpers::e($d['title']) ?>"
              data-cat="<?= $d['category_id'] ?>"
              data-desc="<?= Helpers::e($d['description'] ?? '') ?>"
              data-active="<?= $d['is_active'] ?>">Edytuj</button>
            <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_dict_delete"
                  style="display:inline;"
                  onsubmit="return confirm('Usunąć tę usterkę ze słownika?');">
              <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
              <input type="hidden" name="dict_id" value="<?= $d['id'] ?>">
              <button type="submit" class="btn btn-sm" style="border-color:#fca5a5;color:#dc2626;">Usuń</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$dictionary): ?>
        <tr><td colspan="3" class="muted" style="text-align:center;padding:16px;">Brak pozycji. Dodaj pierwszą →</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Formularz nowej usterki -->
  <div class="card">
    <div class="card-head"><span class="card-title" id="dictFormTitle">Dodaj pozycję do słownika</span></div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_dict_save">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
        <input type="hidden" name="dict_id" id="dictId" value="0">
        <div class="fg">
          <label class="flbl">Usterka <span class="req">*</span></label>
          <input class="fc" name="title" id="dictTitle" placeholder="np. Uszkodzony wałek napędowy">
        </div>
        <div class="fg">
          <label class="flbl">Kategoria <span class="req">*</span></label>
          <select class="fc" name="category_id" id="dictCat" required>
            <option value="">— Wybierz kategorię —</option>
            <?php foreach ($categories as $cat): if (!$cat['is_active']) continue; ?>
            <option value="<?= $cat['id'] ?>"><?= Helpers::e($cat['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label class="flbl">Opis (opcjonalnie)</label>
          <textarea class="fc" name="description" id="dictDesc" rows="2" placeholder="Dodatkowy opis usterki..."></textarea>
        </div>
        <div class="fg">
          <label class="flbl">Aktywna</label>
          <select class="fc" name="dict_active" id="dictActive">
            <option value="1">Tak</option>
            <option value="0">Nie</option>
          </select>
        </div>
        <div style="display:flex;gap:8px;">
          <button type="submit" class="btn btn-p btn-sm">Zapisz pozycję</button>
          <button type="button" class="btn btn-sm" onclick="resetDictForm()">Nowa</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.edit-dict-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.getElementById('dictId').value    = this.dataset.id;
    document.getElementById('dictTitle').value = this.dataset.title;
    document.getElementById('dictCat').value   = this.dataset.cat;
    document.getElementById('dictDesc').value  = this.dataset.desc;
    document.getElementById('dictActive').value= this.dataset.active;
    document.getElementById('dictFormTitle').textContent = 'Edytuj pozycję słownika';
    window.scrollTo({top:document.body.scrollHeight,behavior:'smooth'});
  });
});
function resetDictForm(){
  document.getElementById('dictId').value    = '0';
  document.getElementById('dictTitle').value = '';
  document.getElementById('dictCat').value   = '';
  document.getElementById('dictDesc').value  = '';
  document.getElementById('dictActive').value= '1';
  document.getElementById('dictFormTitle').textContent = 'Dodaj pozycję do słownika';
}

document.getElementById('katKolor').addEventListener('input', function () {
  document.getElementById('katKolorPrev').style.background = this.value;
  document.getElementById('katKolorPrev').textContent = this.value;
});

function editCat(id, label, color, order, active) {
  document.getElementById('catId').value     = id;
  document.getElementById('catLabel').value  = label;
  document.getElementById('katKolor').value  = color;
  document.getElementById('catOrder').value  = order;
  document.getElementById('catActive').value = active;
  document.getElementById('katKolorPrev').style.background = color;
  document.getElementById('katKolorPrev').textContent = label;
}

function resetCatForm() {
  document.getElementById('catId').value     = '0';
  document.getElementById('catLabel').value  = '';
  document.getElementById('katKolor').value  = '#0891b2';
  document.getElementById('catOrder').value  = '0';
  document.getElementById('catActive').value = '1';
  document.getElementById('katKolorPrev').style.background = '#0891b2';
  document.getElementById('katKolorPrev').textContent = 'Podgląd';
}
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
