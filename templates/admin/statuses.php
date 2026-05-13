<?php
use App\Helpers\Helpers;
$pageTitle = 'Statusy zgłoszeń';
require BASE_PATH . '/templates/shared/header.php';
?>

<div class="atabs mb2">
  <a href="<?= BASE_URL ?>/index.php?route=admin_users"      class="atab">Użytkownicy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_employees"  class="atab">Pracownicy / Akronimy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_lines"      class="atab">Linie i podzespoły</a>
  <button class="atab active" data-tab="statuses">Statusy</button>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dictionary" class="atab">Słownik awarii</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dur_tmpl"   class="atab v">Szablony DUR</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dur_sched"  class="atab v">Harmonogram DUR</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_settings"   class="atab">Ustawienia</a>
</div>

<div style="display:grid;grid-template-columns:1fr 360px;gap:16px;align-items:start;">
  <!-- Tabela statusów -->
  <div class="card">
    <div class="card-head">
      <span class="card-title">Statusy zgłoszeń</span>
      <span class="muted fs-sm">Edytuj lub dodaj nowe statusy</span>
    </div>
    <div class="twrap">
      <table>
        <thead><tr>
          <th>Etykieta</th>
          <th>Kolor / podgląd</th>
          <th>Kolejność</th>
          <th>Startowy</th>
          <th>Końcowy</th>
          <th>Aktywny</th>
          <th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($statuses as $s): ?>
        <tr>
          <td class="fw6"><?= Helpers::e($s['label']) ?></td>
          <td>
            <span class="badge" style="background:<?= Helpers::e($s['color']) ?>;color:#fff;">
              <?= Helpers::e($s['label']) ?>
            </span>
            <span class="muted fs-sm" style="margin-left:6px;"><?= Helpers::e($s['color']) ?></span>
          </td>
          <td class="muted fs-sm"><?= $s['sort_order'] ?></td>
          <td><?= $s['is_initial'] ? '✓' : '—' ?></td>
          <td><?= $s['is_final']   ? '✓' : '—' ?></td>
          <td><?= Helpers::statusBadge($s['is_active'] ? 'Tak' : 'Nie', $s['is_active'] ? '#16a34a' : '#6b7280') ?></td>
          <td>
            <button class="btn btn-sm" onclick="editStatus(
              <?= $s['id'] ?>,
              '<?= Helpers::e(addslashes($s['label'])) ?>',
              '<?= Helpers::e($s['color']) ?>',
              <?= $s['sort_order'] ?>,
              <?= $s['is_active'] ?>,
              <?= $s['is_initial'] ?>,
              <?= $s['is_final'] ?>
            )">Edytuj</button>
            <?php if (!$s['is_initial'] && !$s['is_final']): ?>
            <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_status_delete" style="display:inline;" onsubmit="return confirm('Usunąć status <?= Helpers::e(addslashes($s['label'])) ?>?');">
              <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
              <input type="hidden" name="status_id" value="<?= $s['id'] ?>">
              <button type="submit" class="btn btn-sm" style="border-color:#fca5a5;color:#dc2626;">Usuń</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- POPRAWKA 6: Formularz dodawania nowego statusu -->
  <div class="card">
    <div class="card-head"><span class="card-title" id="statusFormTitle">Dodaj nowy status</span></div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_status_save">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
        <input type="hidden" name="status_id" id="statusId" value="0">

        <div class="fg">
          <label class="flbl">Etykieta statusu <span class="req">*</span></label>
          <input class="fc" name="label" id="nsLabel" placeholder="np. W weryfikacji">
        </div>

        <div class="fg">
          <label class="flbl">Kolor</label>
          <div style="display:flex;align-items:center;gap:10px;">
            <input type="color" name="color" id="nsColor" value="#6b7280"
                   style="width:40px;height:34px;padding:2px;border:1px solid #e5e7eb;border-radius:6px;cursor:pointer;">
            <span id="nsPreview" class="badge" style="background:#6b7280;color:#fff;">Podgląd statusu</span>
          </div>
        </div>

        <div class="fg">
          <label class="flbl">Kolejność (sortowanie)</label>
          <input class="fc" type="number" name="sort_order" id="nsOrder" value="10" style="width:80px;">
        </div>

        <div class="fg">
          <label class="flbl">Status startowy <span class="fhint" style="display:inline;">(dla nowych zgłoszeń)</span></label>
          <select class="fc" name="is_initial" id="nsInitial">
            <option value="0">Nie</option>
            <option value="1">Tak</option>
          </select>
        </div>

        <div class="fg">
          <label class="flbl">Status końcowy <span class="fhint" style="display:inline;">(zamknięcie zgłoszenia)</span></label>
          <select class="fc" name="is_final" id="nsFinal">
            <option value="0">Nie</option>
            <option value="1">Tak</option>
          </select>
        </div>

        <div class="fg">
          <label class="flbl">Aktywny</label>
          <select class="fc" name="is_active" id="nsActive">
            <option value="1">Tak</option>
            <option value="0">Nie</option>
          </select>
        </div>

        <div style="display:flex;gap:8px;">
          <button type="submit" class="btn btn-p btn-sm">Zapisz status</button>
          <button type="button" class="btn btn-sm" onclick="resetStatusForm()">Nowy</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('nsColor').addEventListener('input', function () {
  document.getElementById('nsPreview').style.background = this.value;
  document.getElementById('nsPreview').textContent = this.value;
});

function editStatus(id, label, color, order, active, isInitial, isFinal) {
  document.getElementById('statusId').value     = id;
  document.getElementById('nsLabel').value      = label;
  document.getElementById('nsColor').value      = color;
  document.getElementById('nsOrder').value      = order;
  document.getElementById('nsActive').value     = active;
  document.getElementById('nsInitial').value    = isInitial || 0;
  document.getElementById('nsFinal').value      = isFinal   || 0;
  document.getElementById('nsPreview').style.background = color;
  document.getElementById('nsPreview').textContent = label;
  document.getElementById('statusFormTitle').textContent = 'Edytuj status';
}

function resetStatusForm() {
  document.getElementById('statusId').value    = '0';
  document.getElementById('nsLabel').value     = '';
  document.getElementById('nsColor').value     = '#6b7280';
  document.getElementById('nsOrder').value     = '10';
  document.getElementById('nsActive').value    = '1';
  document.getElementById('nsInitial').value   = '0';
  document.getElementById('nsFinal').value     = '0';
  document.getElementById('nsPreview').style.background = '#6b7280';
  document.getElementById('nsPreview').textContent = 'Podgląd statusu';
  document.getElementById('statusFormTitle').textContent = 'Dodaj nowy status';
}
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
