<?php
// ============================================================
// templates/shared/my_repairs.php — Moje naprawy
// NOWY PLIK — skopiuj do templates/shared/
// ============================================================
use App\Helpers\Helpers;

$pageTitle = 'Moje naprawy';
require BASE_PATH . '/templates/shared/header.php';
?>

<div class="sh mb2">
  <div>
    <div class="sh-title">🔧 Moje naprawy</div>
    <div class="muted fs-sm" style="margin-top:2px;">
      Zgłoszenia awarii, przy których jesteś w obsadzie.
    </div>
  </div>
</div>

<?php if (empty($myRepairs)): ?>
  <div class="card">
    <div class="card-body" style="text-align:center;padding:32px 16px;">
      <div style="font-size:32px;margin-bottom:8px;">🔩</div>
      <div class="fw6" style="margin-bottom:4px;">Brak napraw</div>
      <div class="muted fs-sm">Nie figurujesz jeszcze w obsadzie żadnego zgłoszenia.</div>
    </div>
  </div>
<?php else: ?>

  <!-- Podsumowanie statusów -->
  <?php
    $openCount   = 0;
    $closedCount = 0;
    foreach ($myRepairs as $r) {
      if (!empty($r['status_is_final'])) $closedCount++;
      else $openCount++;
    }
  ?>
  <div class="stats mb2" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-card">
      <div class="stat-val sv-b"><?= count($myRepairs) ?></div>
      <div class="stat-lbl">Łącznie napraw</div>
    </div>
    <div class="stat-card">
      <div class="stat-val sv-r"><?= $openCount ?></div>
      <div class="stat-lbl">Otwarte</div>
    </div>
    <div class="stat-card">
      <div class="stat-val sv-g"><?= $closedCount ?></div>
      <div class="stat-lbl">Zamknięte</div>
    </div>
  </div>

  <div class="card">
    <div class="twrap">
      <table>
        <thead>
          <tr>
            <th>Numer</th>
            <th>Linia</th>
            <th>Objaw / Usterka</th>
            <th>Kategoria</th>
            <th>Status</th>
            <th>Rola w obsadzie</th>
            <th>Data zgłoszenia</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($myRepairs as $r): ?>
          <tr<?= !empty($r['status_is_final']) ? ' style="opacity:.75;"' : '' ?>>
            <td>
              <a href="<?= BASE_URL ?>/index.php?route=failure_detail&id=<?= $r['id'] ?>"
                 class="mono fw6 fs-sm" style="color:#0a2463;">
                <?= Helpers::e($r['ticket_number']) ?>
              </a>
            </td>
            <td class="fs-sm">
              <?= Helpers::e($r['line_name']) ?>
              <?php if ($r['subsystem_name']): ?>
                <div class="muted" style="font-size:11px;"><?= Helpers::e($r['subsystem_name']) ?></div>
              <?php endif; ?>
            </td>
            <td class="fs-sm">
              <?= $r['symptom_name'] ? Helpers::e($r['symptom_name']) : '<span class="muted">—</span>' ?>
            </td>
            <td>
              <?= $r['category_id']
                ? Helpers::catBadge($r['cat_label'], $r['cat_color'])
                : '<span class="muted fs-sm">—</span>' ?>
            </td>
            <td>
              <?= Helpers::statusBadge($r['status_label'], $r['status_color']) ?>
            </td>
            <td>
              <?php if (!empty($r['is_first'])): ?>
                <span class="badge" style="background:#e8eeff;color:#0a2463;border:1px solid #c7d2fe;">
                  👷 Prowadzący
                </span>
              <?php else: ?>
                <span class="badge" style="background:#f3f4f6;color:#6b7280;">
                  Obsada
                </span>
              <?php endif; ?>
            </td>
            <td class="muted fs-sm">
              <?= Helpers::formatDateOnly($r['created_at']) ?>
            </td>
            <td>
              <a href="<?= BASE_URL ?>/index.php?route=failure_detail&id=<?= $r['id'] ?>"
                 class="btn btn-sm btn-p" style="font-size:11px;padding:3px 9px;">
                Otwórz
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-body" style="padding:8px 16px;background:#fafafa;border-top:1px solid #f3f4f6;">
      <span class="muted fs-sm">Łącznie: <strong><?= count($myRepairs) ?></strong> napraw</span>
    </div>
  </div>
<?php endif; ?>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
