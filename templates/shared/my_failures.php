<?php
// ============================================================
// templates/shared/my_failures.php — Moje zgłoszenia
// POPRAWKA błąd 1: edycja otwiera modal zamiast strony /report
// ============================================================
use App\Helpers\Helpers;
use App\Helpers\Auth;

$pageTitle = 'Moje zgłoszenia';
require BASE_PATH . '/templates/shared/header.php';
?>

<style>
  /* Modal edycji objawu */
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
    max-width: 440px;
    box-shadow: 0 20px 60px rgba(0,0,0,.20);
    overflow: hidden;
    animation: editModalIn .15s ease;
  }
  @keyframes editModalIn {
    from { opacity:0; transform:scale(.96); }
    to   { opacity:1; transform:scale(1);   }
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
    transition: color .1s;
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
  .edit-modal-meta strong { color: #0a2463; font-family: monospace; }
</style>

<div class="sh mb2">
  <div>
    <div class="sh-title">📋 Moje zgłoszenia</div>
    <div class="muted fs-sm" style="margin-top:2px;">Awarie zgłoszone przez Ciebie — możesz edytować objaw tych ze statusem startowym.</div>
  </div>
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
            <th>Linia</th>
            <th>Objaw</th>
            <th>Status</th>
            <th>Data zgłoszenia</th>
            <th style="text-align:center;">Akcje</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($myFailures as $f):
            // Ustal czy status jest startowy na podstawie tablicy statusów
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
              <span class="mono fw6" style="color:#0a2463;">
                <?= Helpers::e($f['ticket_number']) ?>
              </span>
            </td>
            <td>
              <?= Helpers::e($f['line_name']) ?>
              <?php if ($f['subsystem_name']): ?>
                <div class="muted fs-sm"><?= Helpers::e($f['subsystem_name']) ?></div>
              <?php endif; ?>
            </td>
            <td class="fs-sm">
              <?= $f['symptom_name'] ? Helpers::e($f['symptom_name']) : '<span class="muted">—</span>' ?>
            </td>
            <td>
              <?php
                $sc = $f['status_color'] ?? '#6b7280';
                $sl = $f['status_label'] ?? '—';
                echo Helpers::statusBadge($sl, $sc);
              ?>
            </td>
            <td class="muted fs-sm">
              <?= date('d.m.Y H:i', strtotime($f['created_at'])) ?>
            </td>
            <td style="text-align:center;white-space:nowrap;">
              <?php if ($statusIsInitial && !$isFinal): ?>
                <!-- POPRAWKA błąd 1: otwiera modal zamiast przejścia do /report -->
                <button
                  type="button"
                  class="btn btn-p btn-sm"
                  title="Edytuj objaw zgłoszenia"
                  onclick="openEditModal(
                    <?= (int)$f['id'] ?>,
                    '<?= Helpers::e(addslashes($f['ticket_number'])) ?>',
                    '<?= Helpers::e(addslashes($f['line_name'])) ?>',
                    <?= (int)($f['symptom_id'] ?? 0) ?>
                  )">
                  ✏ Edytuj
                </button>
              <?php else: ?>
                <span class="muted fs-sm" title="Zgłoszenie jest w trakcie realizacji lub zamknięte">—</span>
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
      <span id="editModalTitle">✏ Edytuj objaw awarii</span>
      <button class="edit-modal-close" onclick="closeEditModal()" type="button" aria-label="Zamknij">×</button>
    </div>

    <div class="edit-modal-body">
      <div class="edit-modal-meta" id="editModalMeta">
        Zgłoszenie: <strong id="editModalTicket">—</strong> &nbsp;|&nbsp; Linia: <span id="editModalLine">—</span>
      </div>

      <form method="POST" action="<?= BASE_URL ?>/index.php?route=my_failure_edit" id="editSymptomForm">
        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="failure_id" id="editFailureId" value="">

        <div class="fg">
          <label class="flbl">Objaw awarii <span class="req">*</span></label>
          <select name="symptom_id" id="editSymptomSelect" class="fc" required>
            <option value="">— Wybierz objaw —</option>
            <?php foreach ($symptoms as $sym): ?>
              <option value="<?= (int)$sym['id'] ?>">
                <?= Helpers::e($sym['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <span class="fhint">Wybierz objaw który najlepiej opisuje awarię.</span>
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
function openEditModal(failureId, ticket, lineName, currentSymptomId) {
  document.getElementById('editFailureId').value       = failureId;
  document.getElementById('editModalTicket').textContent = ticket;
  document.getElementById('editModalLine').textContent   = lineName;

  // Ustaw aktualny objaw w select
  var sel = document.getElementById('editSymptomSelect');
  sel.value = currentSymptomId || '';

  document.getElementById('editSymptomModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeEditModal() {
  document.getElementById('editSymptomModal').classList.remove('open');
  document.body.style.overflow = '';
}

function closeEditModalOutside(e) {
  if (e.target === document.getElementById('editSymptomModal')) {
    closeEditModal();
  }
}

// Zamknij modalem klawiszem Escape
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeEditModal();
});
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
