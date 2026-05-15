<?php
// templates/shared/my_failures.php
// ZMIANA: obsługa other_symptom w kolumnie "Objaw" i modalu edycji

use App\Helpers\Helpers;
use App\Helpers\Auth;

$pageTitle = 'Moje zgłoszenia';
require BASE_PATH . '/templates/shared/header.php';
?>

<style>
  .edit-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 3000;
    align-items: center;
    justify-content: center;
  }
  .edit-modal-overlay.open { display: flex; }
  .edit-modal-box {
    background: #fff;
    border-radius: 12px;
    width: 100%;
    max-width: 460px;
    box-shadow: 0 20px 60px rgba(0,0,0,.20);
    overflow: hidden;
    animation: editModalIn .15s ease;
  }
  @keyframes editModalIn {
    from { opacity:0; transform:scale(.96); }
    to   { opacity:1; transform:scale(1); }
  }
  .edit-modal-head {
    background: #0a2463;
    color: #fff;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 700;
    font-size: 15px;
  }
  .edit-modal-close {
    background: none;
    border: none;
    color: rgba(255,255,255,.7);
    font-size: 20px;
    cursor: pointer;
    line-height: 1;
    padding: 0;
  }
  .edit-modal-close:hover { color: #fff; }
  .edit-modal-body { padding: 20px; }
  .edit-modal-meta {
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 14px;
    font-size: 13px;
    color: #374151;
  }
  .other-cb-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
    border-radius: 7px;
    margin-bottom: 10px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
  }
</style>

<div class="mb2" style="display:flex;justify-content:flex-end;">
  <a href="<?= BASE_URL ?>/index.php?route=report" class="btn btn-p btn-sm">+ Nowe zgłoszenie</a>
</div>

<?php if (empty($myFailures)): ?>
  <div class="card">
    <div class="card-body" style="text-align:center;padding:32px 16px;">
      <div style="font-size:32px;margin-bottom:8px;">📭</div>
      <div class="fw6" style="margin-bottom:4px;">Brak zgłoszeń</div>
      <div class="muted fs-sm">Nie masz jeszcze żadnych zgłoszeń awarii.</div>
      <div style="margin-top:14px;">
        <a href="<?= BASE_URL ?>/index.php?route=report" class="btn btn-p btn-sm">Zgłoś awarię</a>
      </div>
    </div>
  </div>
<?php else: ?>
  <div class="card">
    <div class="twrap">
      <table>
        <thead>
          <tr>
            <th>Numer</th>
            <th>Linia / Podzespół</th>
            <th>Objaw</th>
            <th>Status</th>
            <th>Data zgłoszenia</th>
            <th style="text-align:center;">Akcje</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($myFailures as $f):
            $statusIsInitial = false;
            foreach ($statuses as $s) {
              if ($s['id'] == $f['status_id'] && !empty($s['is_initial'])) {
                $statusIsInitial = true;
                break;
              }
            }
            $isFinal = !empty($f['status_is_final']);
          ?>
          <tr>
            <td>
              <a href="<?= BASE_URL ?>/index.php?route=failure_detail&id=<?= (int)$f['id'] ?>"
                 class="mono fw6" style="color:#0a2463;text-decoration:none;"
                 title="Kliknij aby zobaczyć szczegóły zgłoszenia">
                <?= Helpers::e($f['ticket_number']) ?>
              </a>
            </td>
            <td>
              <?= Helpers::e($f['line_name']) ?>
              <?php if ($f['subsystem_name']): ?>
                <div class="muted fs-sm"><?= Helpers::e($f['subsystem_name']) ?></div>
              <?php endif; ?>
            </td>

            <?php /* ZMIANA: kolumna Objaw uwzględnia other_symptom */ ?>
            <td class="fs-sm">
              <?php if (!empty($f['other_symptom'])): ?>
                <?php
                  $d = trim($f['description'] ?? '');
                  $display = $d !== ''
                    ? (mb_strlen($d) > 44 ? mb_substr($d, 0, 42) . '…' : $d)
                    : 'Inne objawy';
                ?>
                <span style="font-style:italic;color:#6b7280;" title="<?= Helpers::e($d) ?>">
                  <?= Helpers::e($display) ?>
                </span>
              <?php elseif ($f['symptom_name']): ?>
                <?= Helpers::e($f['symptom_name']) ?>
              <?php else: ?>
                <span class="muted">—</span>
              <?php endif; ?>
            </td>

            <td><?php echo Helpers::statusBadge($f['status_label'] ?? '—', $f['status_color'] ?? '#6b7280'); ?></td>
            <td class="muted fs-sm"><?= date('d.m.Y H:i', strtotime($f['created_at'])) ?></td>
            <td style="text-align:center;white-space:nowrap;">
              <?php if ($statusIsInitial && !$isFinal): ?>
                <button
                  type="button"
                  class="btn btn-p btn-sm"
                  title="Edytuj objaw zgłoszenia"
                  onclick="openEditModal(
                    <?= (int)$f['id'] ?>,
                    '<?= Helpers::e(addslashes($f['ticket_number'])) ?>',
                    '<?= Helpers::e(addslashes($f['line_name'])) ?>',
                    '<?= Helpers::e(addslashes($f['subsystem_name'] ?? '')) ?>',
                    <?= (int)($f['symptom_id'] ?? 0) ?>,
                    <?= !empty($f['other_symptom']) ? 'true' : 'false' ?>,
                    <?= Helpers::e(json_encode($f['description'] ?? '')) ?>
                  )">
                  Edytuj
                </button>
              <?php else: ?>
                <span class="muted fs-sm">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-body" style="padding:8px 16px;background:#fafafa;border-top:1px solid #f3f4f6;">
      <span class="muted fs-sm">Łącznie: <strong><?= count($myFailures) ?></strong> zgłoszeń</span>
    </div>
  </div>
<?php endif; ?>

<!-- ══ Modal: Edycja objawu awarii ════════════════════════════ -->
<div class="edit-modal-overlay" id="editSymptomModal" onclick="closeEditModalOutside(event)">
  <div class="edit-modal-box" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">

    <div class="edit-modal-head">
      <span id="editModalTitle">Edytuj objaw awarii</span>
      <button class="edit-modal-close" onclick="closeEditModal()" type="button" aria-label="Zamknij">×</button>
    </div>

    <div class="edit-modal-body">
      <div class="edit-modal-meta">
        <div>Zgłoszenie: <strong id="editModalTicket">—</strong></div>
        <div>Linia: <span id="editModalLine">—</span></div>
        <div id="editModalSubsystemRow" style="display:none;">Podzespół: <strong id="editModalSubsystem" style="color:#374151;">—</strong></div>
      </div>

      <form method="POST" action="<?= BASE_URL ?>/index.php?route=my_failure_edit" id="editSymptomForm">
        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="failure_id" id="editFailureId" value="">

        <?php /* ZMIANA: checkbox "Inne objawy" w modalu edycji */ ?>
        <label class="other-cb-row">
          <input
            type="checkbox"
            name="other_symptom"
            id="editOtherSymptomCb"
            value="1"
            style="width:16px;height:16px;cursor:pointer;flex-shrink:0;"
            onchange="toggleEditOtherSymptom(this.checked)">
          Inne objawy
          <span class="muted" style="font-weight:400;">&nbsp;— brak odpowiedniego na liście</span>
        </label>

        <div id="editSymptomGrp">
          <div class="fg">
            <label class="flbl">Objaw awarii <span class="req" id="editSymptomReq">*</span></label>
            <select name="symptom_id" id="editSymptomSelect" class="fc" required>
              <option value="">— Wybierz objaw —</option>
              <?php foreach ($symptoms as $sym): ?>
                <option value="<?= (int)$sym['id'] ?>"><?= Helpers::e($sym['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="fhint">Wybierz objaw który najlepiej opisuje awarię.</span>
          </div>
        </div>

        <div id="editDescGrp" style="display:none;">
          <div class="fg">
            <label class="flbl">Opis objawu <span class="req">*</span></label>
            <textarea name="description" id="editDescArea" class="fc" rows="3"
              placeholder="Opisz dokładnie jaki objaw zaobserwowałeś..."></textarea>
            <span class="fhint">Opis pojawi się na listach zamiast nazwy objawu.</span>
          </div>
        </div>

        <div style="display:flex;gap:8px;margin-top:4px;">
          <button type="submit" class="btn btn-p btn-sm">Zapisz zmianę</button>
          <button type="button" class="btn btn-sm" onclick="closeEditModal()">Anuluj</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openEditModal(failureId, ticket, lineName, subsystemName, currentSymptomId, isOtherSymptom, currentDesc) {
  document.getElementById('editFailureId').value         = failureId;
  document.getElementById('editModalTicket').textContent = ticket;
  document.getElementById('editModalLine').textContent   = lineName;

  var subsRow = document.getElementById('editModalSubsystemRow');
  if (subsystemName && subsystemName.trim() !== '') {
    document.getElementById('editModalSubsystem').textContent = subsystemName;
    subsRow.style.display = '';
  } else {
    subsRow.style.display = 'none';
  }

  var cb = document.getElementById('editOtherSymptomCb');
  cb.checked = isOtherSymptom;
  toggleEditOtherSymptom(isOtherSymptom);

  if (isOtherSymptom) {
    document.getElementById('editDescArea').value = currentDesc || '';
  } else {
    document.getElementById('editSymptomSelect').value = currentSymptomId || '';
  }

  document.getElementById('editSymptomModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function toggleEditOtherSymptom(checked) {
  var symptomGrp = document.getElementById('editSymptomGrp');
  var symptomSel = document.getElementById('editSymptomSelect');
  var descGrp    = document.getElementById('editDescGrp');
  var descArea   = document.getElementById('editDescArea');

  if (checked) {
    symptomGrp.style.display = 'none';
    symptomSel.disabled      = true;
    symptomSel.removeAttribute('required');
    symptomSel.value         = '';
    descGrp.style.display    = '';
    descArea.required        = true;
  } else {
    symptomGrp.style.display = '';
    symptomSel.disabled      = false;
    symptomSel.required      = true;
    descGrp.style.display    = 'none';
    descArea.required        = false;
    descArea.value           = '';
  }
}

function closeEditModal() {
  document.getElementById('editSymptomModal').classList.remove('open');
  document.body.style.overflow = '';
}
function closeEditModalOutside(e) {
  if (e.target === document.getElementById('editSymptomModal')) closeEditModal();
}
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeEditModal();
});
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
