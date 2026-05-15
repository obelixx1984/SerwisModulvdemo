<?php
// ============================================================
// templates/shared/my_failures.php — Moje zgłoszenia
// NOWY PLIK
// ============================================================
use App\Helpers\Helpers;
use App\Helpers\Auth;

$pageTitle = 'Moje zgłoszenia';
require BASE_PATH . '/templates/shared/header.php';
?>

<div class="sh mb2">
  <div>
    <div class="sh-title">📋 Moje zgłoszenia</div>
    <div class="muted fs-sm" style="margin-top:2px;">Awarie zgłoszone przez Ciebie — możesz edytować te ze statusem startowym.</div>
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
            $isInitial = !empty($f['status_is_initial'] ?? false);
            // Sprawdź czy status jest startowy (is_initial) na podstawie tablicy statusów
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
                <!-- Status startowy — użytkownik może edytować -->
                <a href="<?= BASE_URL ?>/index.php?route=report&edit_id=<?= $f['id'] ?>"
                   class="btn btn-p btn-sm"
                   title="Edytuj zgłoszenie">
                  ✏ Edytuj
                </a>
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

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
