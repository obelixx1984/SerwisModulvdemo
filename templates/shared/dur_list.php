<?php
// templates/shared/dur_list.php
// ZMIANA: dodana paginacja (limit 18 na stronę)

use App\Helpers\Helpers;

$pageTitle = 'Przeglądy DUR';
require BASE_PATH . '/templates/shared/header.php';


$typeLabels = [];
try {
  $tl = (new \App\Models\SettingsModel())->get('dur_type_labels');
  if ($tl) $typeLabels = json_decode($tl, true) ?? [];
} catch (\Throwable $e) {
}

// ZMIANA 2: odczyt konfiguracji statusów z settings
$durStatusConfig = [];
try {
  $saved = (new \App\Models\SettingsModel())->get('dur_review_statuses');
  if ($saved) $durStatusConfig = json_decode($saved, true) ?? [];
} catch (\Throwable $e) {
}
$durStatusConfig += [
  'completed'   => ['label' => 'Zakończony', 'color' => '#16a34a'],
  'partial'     => ['label' => 'Częściowy',  'color' => '#d97706'],
  'interrupted' => ['label' => 'Przerwany',  'color' => '#dc2626'],
];

$noteCounts    = $noteCounts ?? [];
$user          = \App\Helpers\Auth::user();
$currentUserId = (int)($user['id'] ?? 0);
?>

<div class="sh mb2">
  <?php if (\App\Helpers\Auth::isMechanic()): ?>
    <a href="<?= BASE_URL ?>/index.php?route=dur_add" class="btn btn-v btn-sm">+ Dodaj raport DUR</a>
  <?php endif; ?>
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

<!-- Filtry -->
<div class="card mb2" style="padding:10px 16px;display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
  <form method="GET" action="<?= BASE_URL ?>/index.php" style="display:contents;">
    <input type="hidden" name="route" value="dur">
    <div class="fg" style="margin:0;flex:1;min-width:140px;">
      <label class="flbl">Linia</label>
      <select name="line_id" class="fc">
        <option value="">Wszystkie linie</option>
        <?php foreach ($lines as $l): ?>
          <option value="<?= $l['id'] ?>" <?= ($_GET['line_id'] ?? '') == $l['id'] ? 'selected' : '' ?>>
            <?= Helpers::e($l['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="fg" style="margin:0;flex:1;min-width:120px;">
      <label class="flbl">Status</label>
      <select name="status" class="fc">
        <option value="">Wszystkie</option>
        <option value="completed" <?= ($_GET['status'] ?? '') === 'completed'   ? 'selected' : '' ?>>Zakończony</option>
        <option value="partial" <?= ($_GET['status'] ?? '') === 'partial'     ? 'selected' : '' ?>>Częściowy</option>
        <option value="interrupted" <?= ($_GET['status'] ?? '') === 'interrupted' ? 'selected' : '' ?>>Przerwany</option>
      </select>
    </div>
    <button type="submit" class="btn btn-p btn-sm" style="margin-bottom:0;">Filtruj</button>
    <a href="<?= BASE_URL ?>/index.php?route=dur" class="btn btn-sm" style="margin-bottom:0;">Reset</a>
    <?php /* ZMIANA: info o łącznej liczbie i aktualnej stronie */ ?>
    <?php if (!empty($pager) && $pager['total'] > 0): ?>
      <span class="muted fs-sm" style="margin-left:4px;align-self:center;">
        <?= $pager['total'] ?> raportów
        <?php if ($pager['total_pages'] > 1): ?>
          &nbsp;·&nbsp; strona <?= $pager['current_page'] ?>/<?= $pager['total_pages'] ?>
        <?php endif; ?>
      </span>
    <?php endif; ?>
  </form>
</div>

<!-- Siatka kart DUR -->
<div class="g2">
  <?php foreach ($reviews as $r):
    $sc = $durStatusConfig[$r['status']]['color'] ?? '#374151';
    $sl = $durStatusConfig[$r['status']]['label'] ?? $r['status'];
  ?>
    <div class="dur-card">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:6px;">
        <div>
          <div class="dur-title">
            <?= Helpers::reviewTypeLabel($r['review_type'], $typeLabels) ?> — <?= Helpers::e($r['line_name']) ?>
            <?= $r['subsystem_name'] ? ' · ' . Helpers::e($r['subsystem_name']) : '' ?>
          </div>
          <div class="dur-meta">
            <?= Helpers::e($r['review_date']) ?> · <?= Helpers::e($r['performer_name']) ?>
            <?= $r['duration_minutes'] ? ' · ' . (int)$r['duration_minutes'] . ' min' : '' ?>
          </div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;">
          <span class="badge" style="background:<?= $sc ?>;color:#fff;"><?= $sl ?></span>
          <a href="<?= BASE_URL ?>/index.php?route=dur_detail&id=<?= $r['id'] ?>" class="btn btn-sm">Szczegóły</a>
        </div>
      </div>
      <?php foreach (array_slice(explode("\n", $r['activities']), 0, 3) as $a): if (trim($a)): ?>
          <div class="dur-item"><span class="ck">✓</span><span><?= Helpers::e(ltrim(trim($a), '-')) ?></span></div>
      <?php endif;
      endforeach; ?>
      <?php if ($r['notes']): ?>
        <div style="margin-top:6px;padding:5px 8px;background:#fffbeb;border-radius:5px;font-size:12px;color:#78350f;">
          ⚠ <?= Helpers::e($r['notes']) ?>
        </div>
      <?php endif; ?>
      <?php if ($r['next_review_date']): ?>
        <div class="dur-next">
          <span style="color:#7c3aed;">▶</span> Następny: <strong><?= Helpers::e($r['next_review_date']) ?></strong>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <?php if (!$reviews): ?>
    <div style="grid-column:1/-1;" class="card">
      <div class="card-body" style="text-align:center;padding:24px;color:#6b7280;">Brak raportów DUR.</div>
    </div>
  <?php endif; ?>
</div>

<?php /* ZMIANA: paginacja pod siatką kart */ ?>
<?php if (!empty($pager) && $pager['total_pages'] > 1): ?>
  <?php
  // Buduj bazowy URL zachowując aktywne filtry
  $baseUrl = BASE_URL . '/index.php?route=dur';
  if (!empty($_GET['line_id'])) $baseUrl .= '&line_id=' . (int)$_GET['line_id'];
  if (!empty($_GET['status']))  $baseUrl .= '&status='  . urlencode($_GET['status']);
  if (!empty($_GET['type']))    $baseUrl .= '&type='    . urlencode($_GET['type']);

  $cp = $pager['current_page'];
  $tp = $pager['total_pages'];
  ?>
  <div style="margin-top:8px;display:flex;gap:6px;align-items:center;flex-wrap:wrap;">

    <?php if ($pager['has_prev']): ?>
      <a href="<?= $baseUrl ?>&page=<?= $cp - 1 ?>" class="btn btn-sm">← Poprzednia</a>
    <?php else: ?>
      <span class="btn btn-sm" style="opacity:.4;cursor:default;">← Poprzednia</span>
    <?php endif; ?>

    <?php
    // Numery stron z elipsą (max 7 widocznych)
    if ($tp <= 7) {
      $pages = range(1, $tp);
    } else {
      $pages = [1];
      if ($cp > 3)       $pages[] = '…';
      for ($i = max(2, $cp - 1); $i <= min($tp - 1, $cp + 1); $i++) $pages[] = $i;
      if ($cp < $tp - 2) $pages[] = '…';
      $pages[] = $tp;
    }
    foreach ($pages as $p):
      if ($p === '…'): ?>
        <span class="muted" style="padding:0 4px;">…</span>
      <?php elseif ($p == $cp): ?>
        <span class="btn btn-sm btn-p" style="cursor:default;"><?= $p ?></span>
      <?php else: ?>
        <a href="<?= $baseUrl ?>&page=<?= $p ?>" class="btn btn-sm"><?= $p ?></a>
    <?php endif;
    endforeach;
    ?>

    <?php if ($pager['has_next']): ?>
      <a href="<?= $baseUrl ?>&page=<?= $cp + 1 ?>" class="btn btn-sm">Następna →</a>
    <?php else: ?>
      <span class="btn btn-sm" style="opacity:.4;cursor:default;">Następna →</span>
    <?php endif; ?>

  </div>
<?php endif; ?>

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
      <form id="noteAddForm">
        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
        <input type="hidden" name="schedule_id" id="noteScheduleId" value="">
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
  var activeScheduleId = null;

  function openNotesModal(scheduleId, lineName, typeName) {
    activeScheduleId = scheduleId;
    document.getElementById('notesModalTitle').textContent = '📝 ' + lineName + ' — ' + typeName;
    document.getElementById('noteScheduleId').value = scheduleId;
    document.getElementById('noteText').value = '';
    renderNotes(SCHEDULE_NOTES_DATA[scheduleId] || []);
    document.getElementById('notesModal').classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function renderNotes(notes) {
    var body = document.getElementById('notesModalBody');
    if (!notes.length) {
      body.innerHTML = '<div class="muted fs-sm" style="text-align:center;padding:20px;">Brak uwag. Dodaj pierwszą poniżej.</div>';
      return;
    }
    var html = '';
    notes.forEach(function(n) {
      var isOwner = (parseInt(n.user_id) === CURRENT_USER_ID);
      var dt = (n.created_at || '').substring(0, 16).replace('T', ' ');
      html += '<div class="note-item" id="note-item-' + n.id + '">';
      html += '<div style="display:flex;justify-content:space-between;margin-bottom:5px;">';
      html += '<span style="font-weight:700;color:#374151;">' + esc(n.user_name) + '</span>';
      html += '<div style="display:flex;align-items:center;gap:6px;">';
      html += '<span style="color:#9ca3af;font-size:11px;">' + dt + '</span>';
      if (isOwner) {
        html += '<button class="btn btn-sm" style="padding:2px 7px;font-size:11px;" data-note-id="' + n.id + '" onclick="openNoteEdit(this)">✏</button>';
        html += '<button class="btn btn-sm" style="padding:2px 7px;font-size:11px;color:#dc2626;border-color:#fca5a5;" onclick="deleteNote(' + n.id + ')">✕</button>';
      }
      html += '</div></div>';
      html += '<div class="note-text" style="color:#374151;white-space:pre-wrap;word-break:break-word;">' + esc(n.note) + '</div>';
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
    activeScheduleId = null;
  }

  function closeNotesModalOutside(e) {
    if (e.target === document.getElementById('notesModal')) closeNotesModal();
  }

  // Dodawanie uwagi — AJAX, modal zostaje otwarty
  document.getElementById('noteAddForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var noteText = document.getElementById('noteText').value.trim();
    if (!noteText) return;

    var fd = new FormData();
    fd.append('csrf_token', CSRF);
    fd.append('schedule_id', activeScheduleId);
    fd.append('note', noteText);

    fetch(BASE_URL_JS + '/index.php?route=ajax_note_add', {
        method: 'POST',
        body: fd
      })
      .then(function(r) {
        return r.json();
      })
      .then(function(data) {
        if (data.ok) {
          SCHEDULE_NOTES_DATA[activeScheduleId] = data.notes;
          renderNotes(data.notes);
          document.getElementById('noteText').value = '';
        } else {
          alert(data.error || 'Błąd zapisu uwagi');
        }
      })
      .catch(function() {
        alert('Błąd połączenia');
      });
  });

  // Edycja inline
  function openNoteEdit(btn) {
    var noteId = parseInt(btn.dataset.noteId);
    var notes = SCHEDULE_NOTES_DATA[activeScheduleId] || [];
    var noteObj = notes.find(function(n) {
      return parseInt(n.id) === noteId;
    });
    if (!noteObj) return;
    var noteText = noteObj.note;

    var noteDiv = document.getElementById('note-item-' + noteId);
    if (!noteDiv || noteDiv.querySelector('textarea')) return;
    var textDiv = noteDiv.querySelector('.note-text');
    if (!textDiv) return;

    var ta = document.createElement('textarea');
    ta.style.cssText = 'width:100%;font-size:13px;padding:5px;border:1px solid #c4b5fd;border-radius:6px;resize:vertical;min-height:60px;';
    ta.value = noteText;

    var btnRow = document.createElement('div');
    btnRow.style.cssText = 'display:flex;gap:6px;margin-top:6px;';

    var btnSave = document.createElement('button');
    btnSave.className = 'btn btn-p btn-sm';
    btnSave.style.cssText = 'font-size:11px;padding:3px 10px;';
    btnSave.textContent = 'Zapisz';
    btnSave.onclick = function() {
      saveNoteEdit(noteId, ta);
    };

    var btnCancel = document.createElement('button');
    btnCancel.className = 'btn btn-sm';
    btnCancel.style.cssText = 'font-size:11px;padding:3px 10px;';
    btnCancel.textContent = 'Anuluj';
    btnCancel.onclick = function() {
      renderNotes(SCHEDULE_NOTES_DATA[activeScheduleId] || []);
    };

    btnRow.appendChild(btnSave);
    btnRow.appendChild(btnCancel);
    textDiv.innerHTML = '';
    textDiv.appendChild(ta);
    textDiv.appendChild(btnRow);
    ta.focus();
  }

  function saveNoteEdit(noteId, ta) {
    var newText = ta.value.trim();
    if (!newText) {
      alert('Treść uwagi nie może być pusta.');
      return;
    }

    var fd = new FormData();
    fd.append('csrf_token', CSRF);
    fd.append('note_id', noteId);
    fd.append('note', newText);

    fetch(BASE_URL_JS + '/index.php?route=ajax_note_edit', {
        method: 'POST',
        body: fd
      })
      .then(function(r) {
        return r.json();
      })
      .then(function(data) {
        if (data.ok) {
          SCHEDULE_NOTES_DATA[activeScheduleId] = data.notes;
          renderNotes(data.notes);
        } else {
          alert(data.error || 'Błąd zapisu');
          renderNotes(SCHEDULE_NOTES_DATA[activeScheduleId] || []);
        }
      })
      .catch(function() {
        alert('Błąd połączenia');
      });
  }

  function deleteNote(noteId) {
    if (!confirm('Usunąć tę uwagę?')) return;
    var fd = new FormData();
    fd.append('csrf_token', CSRF);
    fd.append('note_id', noteId);

    fetch(BASE_URL_JS + '/index.php?route=ajax_note_delete', {
        method: 'POST',
        body: fd
      })
      .then(function(r) {
        return r.json();
      })
      .then(function(data) {
        if (data.ok) {
          SCHEDULE_NOTES_DATA[activeScheduleId] = data.notes;
          renderNotes(data.notes);
        } else {
          alert(data.error || 'Błąd usuwania');
        }
      })
      .catch(function() {
        alert('Błąd połączenia');
      });
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeNotesModal();
  });
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>