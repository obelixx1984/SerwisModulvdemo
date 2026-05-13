<?php
use App\Helpers\Helpers;
$pageTitle = 'Użytkownicy systemu';
require BASE_PATH . '/templates/shared/header.php';
?>

<!-- Admin tabs -->
<div class="atabs mb2">
  <button class="atab active" onclick="showUsersTab('users',this)">Użytkownicy</button>
  <button class="atab" onclick="showUsersTab('roles',this)">Role i uprawnienia</button>
  <a href="<?= BASE_URL ?>/index.php?route=admin_employees" class="atab">Pracownicy / Akronimy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_lines"     class="atab">Linie i podzespoły</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_statuses"  class="atab">Statusy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dictionary" class="atab">Słownik awarii</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dur_tmpl"  class="atab v">Szablony DUR</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dur_sched" class="atab v">Harmonogram DUR</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_settings"  class="atab">Ustawienia</a>
</div>

<div id="panel-users">
<div class="g2">
  <!-- Tabela użytkowników -->
  <div class="card">
    <div class="card-head"><span class="card-title">Użytkownicy systemu</span></div>
    <div class="twrap">
      <table>
        <thead><tr>
          <th>Imię i nazwisko</th>
          <th>Nickname</th>
          <th>Email</th>
          <th>Rola</th>
          <th>Aktywny</th>
          <th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td class="fw6"><?= Helpers::e($u['name']) ?></td>
          <td class="mono fs-sm" style="color:#0a2463;font-weight:700;"><?= Helpers::e($u['nickname']) ?></td>
          <td class="muted fs-sm"><?= Helpers::e($u['email']) ?></td>
          <td><?= Helpers::statusBadge($u['role_label'], $u['role_name'] === 'admin' ? '#0a2463' : '#1e3a8a') ?></td>
          <td><?= Helpers::statusBadge($u['is_active'] ? 'Tak' : 'Nie', $u['is_active'] ? '#16a34a' : '#6b7280') ?></td>
          <td>
            <button class="btn btn-sm" onclick="editUser(<?= $u['id'] ?>,'<?= Helpers::e($u['name']) ?>','<?= Helpers::e($u['nickname']) ?>','<?= Helpers::e($u['email']) ?>','<?= $u['role_name'] ?>',<?= $u['is_active'] ?>)">
              Edytuj
            </button>
            <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_user_delete" style="display:inline;" onsubmit="return confirm('Usunąć użytkownika <?= Helpers::e($u['name']) ?>?');">
              <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button type="submit" class="btn btn-sm" style="border-color:#fca5a5;color:#dc2626;">Usuń</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Formularz nowego / edycji -->
  <div class="card">
    <div class="card-head"><span class="card-title" id="userFormTitle">Nowy użytkownik</span></div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_user_save">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
        <input type="hidden" name="user_id" id="userId" value="0">
        <div class="fg"><label class="flbl">Imię i nazwisko</label><input class="fc" name="name" id="uName" placeholder="Jan Kowalski"></div>
        <div class="fg">
          <label class="flbl">Nickname <span class="req">*</span></label>
          <input class="fc" name="nickname" id="uNick" placeholder="np. jkowalski">
          <span class="fhint">Używany do logowania zamiast e-mail</span>
        </div>
        <div class="fg"><label class="flbl">Email</label><input class="fc" name="email" id="uEmail" type="email" placeholder="jan@firma.pl"></div>
        <div class="fg">
          <label class="flbl">Rola</label>
          <select class="fc" name="role" id="uRole">
            <?php foreach ($roles as $r): ?>
            <option value="<?= $r['name'] ?>"><?= Helpers::e($r['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg"><label class="flbl">Hasło <span class="req" id="passReq">*</span></label>
          <input class="fc" name="password" id="uPass" type="password" placeholder="••••••••">
          <span class="fhint" id="passHint">Wymagane przy dodawaniu nowego użytkownika</span>
        </div>
        <div class="fg">
          <label class="flbl">Aktywny</label>
          <select class="fc" name="is_active" id="uActive"><option value="1">Tak</option><option value="0">Nie</option></select>
        </div>
        <div style="display:flex;gap:8px;">
          <button type="submit" class="btn btn-p btn-sm">Zapisz użytkownika</button>
          <button type="button" class="btn btn-sm" onclick="resetUserForm()">Nowy</button>
        </div>
      </form>
    </div>
  </div>
</div>
</div><!-- /#panel-users -->

<!-- Sekcja: Role i uprawnienia -->
<div id="panel-roles" style="display:none;">
  <div class="g2">
    <div class="card">
      <div class="card-head"><span class="card-title">Zdefiniowane role</span></div>
      <div class="twrap">
        <table>
          <thead><tr><th>Rola</th><th>Etykieta</th><th>Uprawnienia</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($roles as $r):
            $perms = $rolePerms[$r['name']] ?? [];
          ?>
          <tr>
            <td class="mono fw6 fs-sm"><?= Helpers::e($r['name']) ?></td>
            <td><?= Helpers::e($r['label']) ?></td>
            <td class="fs-sm muted">
              <?php
              $labels = [];
              if (!empty($perms['report']))    $labels[] = 'Zgłaszanie awarii';
              if (!empty($perms['dashboard'])) $labels[] = 'Pulpit';
              if (!empty($perms['failures']))  $labels[] = 'Lista zgłoszeń';
              if (!empty($perms['dur']))       $labels[] = 'Przeglądy DUR';
              if (!empty($perms['statuses']))  $labels[] = 'Zarządzanie statusami';
              if (!empty($perms['admin']))     $labels[] = 'Panel administratora';
              echo $labels ? implode(', ', $labels) : '—';
              ?>
            </td>
            <td>
              <button class="btn btn-sm" onclick="editRole('<?= Helpers::e($r['name']) ?>','<?= Helpers::e($r['label']) ?>')">Edytuj</button>
              <?php if (!in_array($r['name'], ['admin','mechanic','operator'])): ?>
              <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_role_delete"
                    style="display:inline;"
                    onsubmit="return confirm('Usunąć rolę &quot;<?= Helpers::e($r['label']) ?>&quot;? Użytkownicy z tą rolą zostaną przeniesieni do roli &quot;operator&quot;.');">
                <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
                <input type="hidden" name="role_name" value="<?= Helpers::e($r['name']) ?>">
                <button type="submit" class="btn btn-sm" style="border-color:#fca5a5;color:#dc2626;">Usuń</button>
              </form>
              <?php else: ?>
              <span class="muted fs-sm" style="margin-left:4px;" title="Rola wbudowana — nie można usunąć">🔒</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><span class="card-title" id="roleFormTitle">Edytuj rolę</span></div>
      <div class="card-body">
        <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_role_save">
          <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
          <input type="hidden" name="role_name" id="roleName" value="">

          <div class="fg">
            <label class="flbl">Nazwa roli (techniczna)</label>
            <input class="fc" id="roleNameDisplay" disabled style="background:#f3f4f6;color:#6b7280;">
            <span class="fhint">Nie można zmieniać nazwy technicznej</span>
          </div>
          <div class="fg">
            <label class="flbl">Etykieta (wyświetlana)</label>
            <input class="fc" name="role_label" id="roleLabel" placeholder="np. Kierownik">
          </div>

          <div class="fg">
            <label class="flbl" style="margin-bottom:8px;">Uprawnienia</label>
            <div style="display:flex;flex-direction:column;gap:8px;">
              <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:400;cursor:pointer;">
                <input type="checkbox" name="perm_report" id="pReport" value="1">
                Zgłaszanie awarii
              </label>
              <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:400;cursor:pointer;">
                <input type="checkbox" name="perm_dashboard" id="pDash" value="1">
                Pulpit (dashboard)
              </label>
              <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:400;cursor:pointer;">
                <input type="checkbox" name="perm_failures" id="pFail" value="1">
                Lista i szczegóły zgłoszeń
              </label>
              <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:400;cursor:pointer;">
                <input type="checkbox" name="perm_dur" id="pDur" value="1">
                Przeglądy DUR
              </label>
              <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:400;cursor:pointer;">
                <input type="checkbox" name="perm_statuses" id="pStatus" value="1">
                Zmiana statusów zgłoszeń
              </label>
              <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:400;cursor:pointer;">
                <input type="checkbox" name="perm_admin" id="pAdmin" value="1">
                Pełny dostęp do panelu administratora
              </label>
            </div>
          </div>

          <div class="sep"></div>
          <button type="submit" class="btn btn-p btn-sm">Zapisz uprawnienia</button>
        </form>

        <div style="margin-top:16px;padding-top:14px;border-top:1px solid #f3f4f6;">
          <div class="fw6 fs-sm mb1">Dodaj nową rolę</div>
          <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_role_add">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
            <div style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;">
              <div class="fg" style="margin:0;flex:1;min-width:120px;">
                <label class="flbl">Nazwa techniczna</label>
                <input class="fc" name="new_role_name" placeholder="np. supervisor" pattern="[a-z_]+" title="Tylko małe litery i _">
              </div>
              <div class="fg" style="margin:0;flex:1;min-width:120px;">
                <label class="flbl">Etykieta</label>
                <input class="fc" name="new_role_label" placeholder="np. Kierownik">
              </div>
              <button type="submit" class="btn btn-p btn-sm" style="margin-bottom:0;">Dodaj</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function showUsersTab(tab, btn) {
  document.getElementById('panel-users').style.display = tab === 'users' ? '' : 'none';
  document.getElementById('panel-roles').style.display = tab === 'roles' ? '' : 'none';
  document.querySelectorAll('.atabs .atab').forEach(function(b){ b.classList.remove('active'); });
  btn.classList.add('active');
}
<?php
$rolePermsJs = json_encode($rolePerms);
?>
var ROLE_PERMS = <?= $rolePermsJs ?>;
function editRole(name, label) {
  document.getElementById('roleName').value        = name;
  document.getElementById('roleNameDisplay').value = name;
  document.getElementById('roleLabel').value       = label;
  var p = ROLE_PERMS[name] || {};
  document.getElementById('pReport').checked  = !!p.report;
  document.getElementById('pDash').checked    = !!p.dashboard;
  document.getElementById('pFail').checked    = !!p.failures;
  document.getElementById('pDur').checked     = !!p.dur;
  document.getElementById('pStatus').checked  = !!p.statuses;
  document.getElementById('pAdmin').checked   = !!p.admin;
  document.getElementById('roleFormTitle').textContent = 'Edytuj rolę: ' + label;
}
</script>

<script>
function editUser(id, name, nick, email, role, active) {
  document.getElementById('userId').value = id;
  document.getElementById('uName').value  = name;
  document.getElementById('uNick').value  = nick;
  document.getElementById('uEmail').value = email;
  document.getElementById('uRole').value  = role;
  document.getElementById('uActive').value = active;
  document.getElementById('userFormTitle').textContent = 'Edytuj użytkownika';
  document.getElementById('passReq').style.display = 'none';
  document.getElementById('passHint').textContent = 'Pozostaw puste aby nie zmieniać hasła';
}
function resetUserForm() {
  document.getElementById('userId').value = '0';
  document.getElementById('uName').value  = '';
  document.getElementById('uNick').value  = '';
  document.getElementById('uEmail').value = '';
  document.getElementById('uPass').value  = '';
  document.getElementById('userFormTitle').textContent = 'Nowy użytkownik';
  document.getElementById('passReq').style.display = '';
  document.getElementById('passHint').textContent = 'Wymagane przy dodawaniu nowego użytkownika';
}
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
