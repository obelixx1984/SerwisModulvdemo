<?php
use App\Helpers\Helpers;
$pageTitle = 'Harmonogram DUR';
require BASE_PATH . '/templates/shared/header.php';
?>

<div class="atabs mb2">
  <a href="<?= BASE_URL ?>/index.php?route=admin_users"      class="atab">Użytkownicy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_employees"  class="atab">Pracownicy / Akronimy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_lines"      class="atab">Linie i podzespoły</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_statuses"   class="atab">Statusy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dictionary" class="atab">Słownik awarii</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dur_tmpl"   class="atab v">Szablony DUR</a>
  <button class="atab v active">Harmonogram DUR</button>
  <a href="<?= BASE_URL ?>/index.php?route=admin_settings"   class="atab">Ustawienia</a>
</div>

<div class="g2">
  <div class="card">
    <div class="card-head"><span class="card-title">Harmonogram przeglądów DUR</span></div>
    <div class="twrap">
      <table>
        <thead><tr><th>Linia</th><th>Szablon</th><th>Typ</th><th>Co ile dni</th><th>Następny</th><th>Aktywny</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($schedules as $s):
          $dl    = (int)$s['days_left'];
          $color = $dl <= 0 ? '#dc2626' : ($dl <= 7 ? '#d97706' : '#16a34a');
          $lbl   = $dl <= 0 ? 'zaległy!' : 'za '.$dl.' dni';
        ?>
        <tr>
          <td class="fw6"><?= Helpers::e($s['line_name']) ?></td>
          <td class="fs-sm muted"><?= Helpers::e($s['template_name'] ?? '—') ?></td>
          <td><?= Helpers::statusBadge(Helpers::reviewTypeLabel($s['review_type']), '#7c3aed') ?></td>
          <td class="muted fs-sm"><?= $s['interval_days'] ?></td>
          <td>
            <span class="fw6" style="color:<?= $color ?>;"><?= Helpers::e($s['next_due_date'] ?? '—') ?></span>
            <?php if ($s['next_due_date']): ?>
            <span class="bo fs-sm" style="border-color:<?= $color ?>;color:<?= $color ?>;margin-left:4px;"><?= $lbl ?></span>
            <?php endif; ?>
          </td>
          <td><?= Helpers::statusBadge($s['is_active'] ? 'Tak' : 'Nie', $s['is_active'] ? '#16a34a' : '#6b7280') ?></td>
          <td>
            <button class="btn btn-sm edit-sched-btn"
              data-id="<?= $s['id'] ?>"
              data-line="<?= $s['production_line_id'] ?>"
              data-tmpl="<?= $s['template_id'] ?? '' ?>"
              data-type="<?= Helpers::e($s['review_type']) ?>"
              data-days="<?= (int)$s['interval_days'] ?>"
              data-next="<?= Helpers::e($s['next_due_date'] ?? '') ?>"
              data-active="<?= (int)$s['is_active'] ?>">Edytuj</button>
            <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_sched_delete" style="display:inline;" onsubmit="return confirm('Usunąć tę pozycję harmonogramu?');">
              <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
              <input type="hidden" name="sched_id" value="<?= $s['id'] ?>">
              <button type="submit" class="btn btn-sm" style="border-color:#fca5a5;color:#dc2626;">Usuń</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$schedules): ?>
        <tr><td colspan="7" class="muted" style="text-align:center;padding:16px;">Brak harmonogramów.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-head"><span class="card-title" id="schedFormTitle" style="color:#7c3aed;">Nowa pozycja harmonogramu</span></div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_sched_save">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
        <input type="hidden" name="sched_id" id="schedId" value="0">

        <div class="fg">
          <label class="flbl">Linia produkcyjna <span class="req">*</span></label>
          <select class="fc" name="production_line_id" id="schedLine" required>
            <option value="">— Wybierz linię —</option>
            <?php foreach ($lines as $l): ?>
            <option value="<?= $l['id'] ?>"><?= Helpers::e($l['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="fg">
          <label class="flbl">Szablon DUR</label>
          <select class="fc" name="template_id" id="schedTmpl">
            <option value="">— Bez szablonu —</option>
            <?php foreach ($templates as $t): ?>
            <option value="<?= $t['id'] ?>"><?= Helpers::e($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="fg">
          <label class="flbl">Typ przeglądu</label>
          <select class="fc" name="review_type" id="schedType">
            <option value="weekly">Tygodniowy</option>
            <option value="monthly" selected>Miesięczny</option>
            <option value="quarterly">Kwartalny</option>
            <option value="biannual">Półroczny</option>
            <option value="annual">Roczny</option>
            <option value="ad_hoc">Doraźny</option>
          </select>
        </div>

        <div class="fg">
          <label class="flbl">Co ile dni <span class="req">*</span></label>
          <input class="fc" name="interval_days" id="schedDays" type="number" value="30" min="1" style="width:120px;" required>
        </div>

        <div class="fg">
          <label class="flbl">Data następnego przeglądu <span class="req">*</span></label>
          <input class="fc" name="next_due_date" id="schedNext" type="date" required>
        </div>

        <div class="fg">
          <label class="flbl">Aktywny</label>
          <select class="fc" name="is_active" id="schedActive">
            <option value="1">Tak</option>
            <option value="0">Nie</option>
          </select>
        </div>

        <div style="display:flex;gap:8px;">
          <button type="submit" class="btn btn-v btn-sm">Zapisz harmonogram</button>
          <button type="button" class="btn btn-sm" onclick="resetSchedForm()">Nowy</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.edit-sched-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    document.getElementById('schedId').value     = this.dataset.id;
    document.getElementById('schedLine').value   = this.dataset.line;
    document.getElementById('schedTmpl').value   = this.dataset.tmpl;
    document.getElementById('schedType').value   = this.dataset.type;
    document.getElementById('schedDays').value   = this.dataset.days;
    document.getElementById('schedNext').value   = this.dataset.next;
    document.getElementById('schedActive').value = this.dataset.active;
    document.getElementById('schedFormTitle').textContent = 'Edytuj pozycję harmonogramu';
    window.scrollTo({top:0,behavior:'smooth'});
  });
});
function resetSchedForm(){
  document.getElementById('schedId').value     = '0';
  document.getElementById('schedLine').value   = '';
  document.getElementById('schedTmpl').value   = '';
  document.getElementById('schedType').value   = 'monthly';
  document.getElementById('schedDays').value   = '30';
  document.getElementById('schedNext').value   = '';
  document.getElementById('schedActive').value = '1';
  document.getElementById('schedFormTitle').textContent = 'Nowa pozycja harmonogramu';
}
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
