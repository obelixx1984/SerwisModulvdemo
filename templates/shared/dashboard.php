<?php

use App\Helpers\Helpers;

$pageTitle = 'Pulpit';

$typeLabels = [];

$noteCounts    = $noteCounts ?? [];
$user          = \App\Helpers\Auth::user();
$currentUserId = (int)($user['id'] ?? 0);

try {
  $tl = (new \App\Models\SettingsModel())->get('dur_type_labels');
  if ($tl) $typeLabels = json_decode($tl, true) ?? [];
} catch (\Throwable $e) {
}

require BASE_PATH . '/templates/shared/header.php';
?>
<div class="sh">
  <a href="<?= BASE_URL ?>/index.php?route=report" class="btn btn-p btn-sm">+ Nowe zgłoszenie</a>
</div>

<?php if ($upcoming): ?>
  <div class="alert alert-v mb2">
    <div class="fw6 mb1" style="color:#4c1d95;">⏰ Nadchodzące przeglądy DUR:</div>
    <?php foreach ($upcoming as $u):
      $dl        = (int)$u['days_left'];
      $bc        = $dl <= 0 ? '#dc2626' : ($dl <= 3 ? '#d97706' : '#7c3aed');
      $bl        = $dl <= 0 ? 'zaległy!' : 'za ' . $dl . ' dni';
      $noteCount = (int)($noteCounts[$u['id']] ?? 0);
    ?>
      <div class="dur-up-item" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;">
        <span><?= Helpers::e($u['line_name']) ?> — <?= Helpers::reviewTypeLabel($u['review_type'], $typeLabels) ?></span>
        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
          <span class="badge" style="background:<?= $bc ?>;color:#fff;"><?= $bl ?></span>
          <button
            type="button"
            class="btn btn-sm"
            style="position:relative;<?= $noteCount > 0 ? 'padding-right:22px;' : '' ?>"
            onclick="openNotesModal(
          <?= (int)$u['id'] ?>,
          '<?= Helpers::e(addslashes($u['line_name'])) ?>',
          '<?= Helpers::e(addslashes(Helpers::reviewTypeLabel($u['review_type'], $typeLabels))) ?>'
        )">
            📝 Uwagi
            <?php if ($noteCount > 0): ?>
              <span style="position:absolute;top:-6px;right:-6px;
                       background:#dc2626;color:#fff;border-radius:50%;
                       width:18px;height:18px;font-size:10px;font-weight:700;
                       display:flex;align-items:center;justify-content:center;">
                <?= $noteCount ?>
              </span>
            <?php endif; ?>
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="stats mb2">
  <div class="stat-card">
    <div class="stat-val sv-r"><?= (int)($stats['new_count'] ?? 0) ?></div>
    <div class="stat-lbl">Nowe awarie</div>
  </div>
  <div class="stat-card">
    <div class="stat-val sv-a"><?= (int)($stats['progress_count'] ?? 0) ?></div>
    <div class="stat-lbl">W trakcie</div>
  </div>
  <div class="stat-card">
    <div class="stat-val sv-b"><?= (int)($stats['open_count'] ?? 0) ?></div>
    <div class="stat-lbl">Otwarte łącznie</div>
  </div>
  <?php /* ZMIANA 3: karta "Ilość awarii w [miesiąc]" zamiast "Śr. czas naprawy" */ ?>
  <div class="stat-card">
    <div class="stat-val sv-v"><?= (int)$last30Count ?></div>
    <div class="stat-lbl">Awarii / ostatnie 30 dni</div>
  </div>
</div>

<div class="g2">
  <div class="card">
    <div class="card-head"><span class="card-title">Zgłoszenia wg statusu</span></div>
    <div class="card-body">
      <?php /* ZMIANA 2: każdy wiersz statusu jest teraz linkiem do listy zgłoszeń z filtrem */ ?>
      <?php foreach ($byStatus as $sid => $s): ?>
        <a href="<?= BASE_URL ?>/index.php?route=failures&status_id=<?= (int)$sid ?>"
          style="display:flex;align-items:center;justify-content:space-between;padding:7px 0;border-bottom:1px solid #f3f4f6;text-decoration:none;color:inherit;border-radius:4px;transition:background .12s,padding-left .12s;cursor:pointer;"
          onmouseover="this.style.background='#eff2ff';this.style.paddingLeft='6px'"
          onmouseout="this.style.background='';this.style.paddingLeft='0'">
          <div style="display:flex;align-items:center;gap:8px;">
            <span class="dot" style="background:<?= Helpers::e($s['color']) ?>;"></span>
            <?= Helpers::e($s['label']) ?>
          </div>
          <div style="display:flex;align-items:center;gap:6px;">
            <strong><?= (int)$s['count'] ?></strong>
            <svg width="12" height="12" fill="none" stroke="#9ca3af" stroke-width="2.5" viewBox="0 0 24 24">
              <path d="M9 18l6-6-6-6" />
            </svg>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-head">
      <span class="card-title">Ostatnie zgłoszenia</span>
      <a href="<?= BASE_URL ?>/index.php?route=failures" class="btn btn-sm">Wszystkie</a>
    </div>
    <?php foreach ($recent as $f): ?>
      <div style="padding:7px 16px;border-bottom:1px solid #f3f4f6;">
        <div style="display:flex;align-items:center;justify-content:space-between;">
          <a href="<?= BASE_URL ?>/index.php?route=failure_detail&id=<?= $f['id'] ?>" class="fw6 fs-sm mono">
            <?= Helpers::e($f['ticket_number']) ?>
          </a>
          <span class="badge" style="background:<?= Helpers::e($f['status_color']) ?>;color:#fff;">
            <?= Helpers::e($f['status_label']) ?>
          </span>
        </div>
        <div class="muted fs-sm">
          <?= Helpers::e($f['line_name']) ?>
          <?= $f['subsystem_name'] ? ' · ' . Helpers::e($f['subsystem_name']) : '' ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<style>
  .notes-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .45);
    z-index: 3000;
    align-items: center;
    justify-content: center;
  }

  .notes-modal-overlay.open {
    display: flex;
  }

  .notes-modal-box {
    background: #fff;
    border-radius: 12px;
    width: 100%;
    max-width: 520px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
    overflow: hidden;
  }

  .notes-modal-head {
    background: #4c1d95;
    color: #fff;
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
  }

  .notes-modal-title {
    font-weight: 700;
    font-size: 14px;
  }

  .notes-modal-close {
    background: none;
    border: none;
    color: rgba(255, 255, 255, .7);
    font-size: 20px;
    cursor: pointer;
    line-height: 1;
    padding: 0;
  }

  .notes-modal-close:hover {
    color: #fff;
  }

  .notes-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 14px;
  }

  .note-item {
    padding: 10px 12px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 8px;
    font-size: 13px;
  }

  .notes-modal-foot {
    border-top: 1px solid #f3f4f6;
    padding: 12px 14px;
    flex-shrink: 0;
    background: #fafafa;
  }
</style>

<!-- Modal: lista i dodawanie uwag -->
<div class="notes-modal-overlay" id="notesModal" onclick="closeNotesModalOutside(event)">
  <div class="notes-modal-box" role="dialog" aria-modal="true">
    <div class="notes-modal-head">
      <span class="notes-modal-title" id="notesModalTitle">Uwagi do przeglądu</span>
      <button class="notes-modal-close" onclick="closeNotesModal()" type="button">×</button>
    </div>
    <div class="notes-modal-body" id="notesModalBody"></div>
    <div class="notes-modal-foot">
      <form method="POST" action="<?= BASE_URL ?>/index.php?route=dur_note_add">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
        <input type="hidden" name="schedule_id" id="noteScheduleId" value="">
        <input type="hidden" name="return_to" value="dashboard">
        <div style="display:flex;gap:8px;align-items:flex-end;">
          <div class="fg" style="margin:0;flex:1;">
            <textarea name="note" id="noteText" class="fc" rows="2"
              placeholder="Wpisz uwagę..." style="resize:none;font-size:13px;" required></textarea>
          </div>
          <button type="submit" class="btn btn-p btn-sm" style="margin-bottom:0;">Dodaj</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: edycja uwagi -->
<div class="notes-modal-overlay" id="noteEditModal" onclick="closeNoteEditModalOutside(event)">
  <div class="notes-modal-box" role="dialog" aria-modal="true" style="max-height:auto;">
    <div class="notes-modal-head">
      <span class="notes-modal-title">Edytuj uwagę</span>
      <button class="notes-modal-close" onclick="closeNoteEditModal()" type="button">×</button>
    </div>
    <div style="padding:14px;">
      <form method="POST" action="<?= BASE_URL ?>/index.php?route=dur_note_edit">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
        <input type="hidden" name="note_id" id="editNoteId" value="">
        <input type="hidden" name="return_to" value="dashboard">
        <div class="fg">
          <label class="flbl">Treść uwagi</label>
          <textarea name="note" id="editNoteText" class="fc" rows="3" required></textarea>
        </div>
        <div style="display:flex;gap:8px;">
          <button type="submit" class="btn btn-p btn-sm">Zapisz</button>
          <button type="button" class="btn btn-sm" onclick="closeNoteEditModal()">Anuluj</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  var SCHEDULE_NOTES_DATA = <?php
                            $allNotes = [];
                            if (!empty($upcoming)) {
                              $nm = new \App\Models\ScheduleNoteModel();
                              foreach ($upcoming as $u) {
                                $allNotes[(int)$u['id']] = $nm->getActiveBySchedule((int)$u['id']);
                              }
                            }
                            echo json_encode($allNotes, JSON_HEX_TAG | JSON_HEX_AMP);
                            ?>;

  var CURRENT_USER_ID = <?= $currentUserId ?>;
  var BASE_URL_JS = '<?= BASE_URL ?>';
  var CSRF = '<?= \App\Helpers\Auth::csrfToken() ?>';

  function openNotesModal(scheduleId, lineName, typeName) {
    document.getElementById('notesModalTitle').textContent = '📝 ' + lineName + ' — ' + typeName;
    document.getElementById('noteScheduleId').value = scheduleId;
    document.getElementById('noteText').value = '';
    renderNotes(scheduleId);
    document.getElementById('notesModal').classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function renderNotes(scheduleId) {
    var body = document.getElementById('notesModalBody');
    var notes = SCHEDULE_NOTES_DATA[scheduleId] || [];
    if (!notes.length) {
      body.innerHTML = '<div class="muted fs-sm" style="text-align:center;padding:20px;">Brak uwag. Dodaj pierwszą poniżej.</div>';
      return;
    }
    var html = '';
    notes.forEach(function(n) {
      var isOwner = (parseInt(n.user_id) === CURRENT_USER_ID);
      var dt = (n.created_at || '').substring(0, 16).replace('T', ' ');
      html += '<div class="note-item">';
      html += '<div style="display:flex;justify-content:space-between;margin-bottom:5px;">';
      html += '<span style="font-weight:700;color:#374151;">' + esc(n.user_name) + '</span>';
      html += '<div style="display:flex;align-items:center;gap:6px;">';
      html += '<span style="color:#9ca3af;font-size:11px;">' + dt + '</span>';
      if (isOwner) {
        html += '<button class="btn btn-sm" style="padding:2px 7px;font-size:11px;" onclick="openNoteEdit(' + n.id + ',' + JSON.stringify(n.note) + ')">✏</button>';
        html += '<button class="btn btn-sm" style="padding:2px 7px;font-size:11px;color:#dc2626;border-color:#fca5a5;" onclick="deleteNote(' + n.id + ')">✕</button>';
      }
      html += '</div></div>';
      html += '<div style="color:#374151;white-space:pre-wrap;word-break:break-word;">' + esc(n.note) + '</div>';
      html += '</div>';
    });
    body.innerHTML = html;
  }

  function esc(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function closeNotesModal() {
    document.getElementById('notesModal').classList.remove('open');
    document.body.style.overflow = '';
  }

  function closeNotesModalOutside(e) {
    if (e.target === document.getElementById('notesModal')) closeNotesModal();
  }

  function openNoteEdit(id, text) {
    document.getElementById('editNoteId').value = id;
    document.getElementById('editNoteText').value = text;
    document.getElementById('noteEditModal').classList.add('open');
  }

  function closeNoteEditModal() {
    document.getElementById('noteEditModal').classList.remove('open');
  }

  function closeNoteEditModalOutside(e) {
    if (e.target === document.getElementById('noteEditModal')) closeNoteEditModal();
  }

  function deleteNote(id) {
    if (!confirm('Usunąć tę uwagę?')) return;
    var f = document.createElement('form');
    f.method = 'POST';
    f.action = BASE_URL_JS + '/index.php?route=dur_note_delete';
    f.innerHTML = '<input type="hidden" name="csrf_token" value="' + CSRF + '">' +
      '<input type="hidden" name="note_id" value="' + id + '">' +
      '<input type="hidden" name="return_to" value="dashboard">';
    document.body.appendChild(f);
    f.submit();
  }
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeNoteEditModal();
      closeNotesModal();
    }
  });
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>