<?php
// templates/shared/failures_list.php
// ZMIANA 1: $pager['page'] → $pager['current_page'] (fix Warning)
// ZMIANA 2: usunięto <div class="sh-title">📋 Zgłoszenia awarii</div>

use App\Helpers\Helpers;

$pageTitle = 'Zgłoszenia awarii';
require BASE_PATH . '/templates/shared/header.php';
?>

<!-- Filtry -->
<div class="card mb2">
  <div class="card-body" style="padding:10px 14px;">
    <form method="GET" action="<?= BASE_URL ?>/index.php" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
      <input type="hidden" name="route" value="failures">
      <div class="fg" style="margin:0;flex:1;min-width:120px;">
        <label class="flbl">Status</label>
        <select name="status_id" class="fc">
          <option value="">— Wszystkie —</option>
          <?php foreach ($statuses as $s): ?>
          <option value="<?= $s['id'] ?>" <?= ($_GET['status_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
            <?= Helpers::e($s['label']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fg" style="margin:0;flex:1;min-width:120px;">
        <label class="flbl">Linia</label>
        <select name="line_id" class="fc">
          <option value="">— Wszystkie —</option>
          <?php foreach ($lines as $l): ?>
          <option value="<?= $l['id'] ?>" <?= ($_GET['line_id'] ?? '') == $l['id'] ? 'selected' : '' ?>>
            <?= Helpers::e($l['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fg" style="margin:0;flex:1;min-width:120px;">
        <label class="flbl">Rodzaj</label>
        <select name="category_id" class="fc">
          <option value="">— Wszystkie —</option>
          <option value="none" <?= ($_GET['category_id'] ?? '') === 'none' ? 'selected' : '' ?>>— Bez kategorii —</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= ($_GET['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
            <?= Helpers::e($cat['label']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="fg" style="margin:0;flex:2;min-width:140px;">
        <label class="flbl">Szukaj</label>
        <input type="text" name="search" class="fc" placeholder="Numer, opis lub objaw..."
               value="<?= Helpers::e($_GET['search'] ?? '') ?>">
      </div>
      <button type="submit" class="btn btn-p btn-sm" style="margin-bottom:0;">Szukaj</button>
      <a href="<?= BASE_URL ?>/index.php?route=failures" class="btn btn-sm" style="margin-bottom:0;">Reset</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="twrap">
    <table>
      <thead>
        <tr>
          <th>Numer</th><th>Data</th><th>Zgłaszający</th><th>Linia</th>
          <th>Podzespół</th><th>Rodzaj</th><th>Objaw / Usterka</th><th>Status</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php if ($items): ?>
        <?php foreach ($items as $f): ?>
        <tr>
          <td class="mono fw6 fs-sm" style="color:#0a2463;">
            <a href="<?= BASE_URL ?>/index.php?route=failure_detail&id=<?= $f['id'] ?>"><?= Helpers::e($f['ticket_number']) ?></a>
          </td>
          <td class="muted fs-sm"><?= Helpers::formatDate($f['created_at']) ?></td>
          <td class="fs-sm"><?= Helpers::e($f['reporter_name'] ?? $f['reporter_acronym'] ?? '—') ?></td>
          <td class="fs-sm"><?= Helpers::e($f['line_name']) ?></td>
          <td class="fs-sm"><?= Helpers::e($f['subsystem_name'] ?? '—') ?></td>
          <td><?= $f['category_id'] ? Helpers::catBadge($f['cat_label'], $f['cat_color']) : '<span class="muted fs-sm">—</span>' ?></td>

          <td class="fs-sm fw6">
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
            <?php else: ?>
              <?= Helpers::e($f['symptom_name'] ?? $f['dict_title'] ?? mb_substr($f['description'] ?? '', 0, 44)) ?>
            <?php endif; ?>
          </td>

          <td><?= Helpers::statusBadge($f['status_label'], $f['status_color']) ?></td>
          <td style="display:flex;gap:4px;align-items:center;">
            <a href="<?= BASE_URL ?>/index.php?route=failure_detail&id=<?= $f['id'] ?>" class="btn btn-sm">Szczegóły</a>
            <?php if (\App\Helpers\Auth::isAdmin() || \App\Helpers\Auth::hasAdminPermission()): ?>
            <form method="POST" action="<?= BASE_URL ?>/index.php?route=failure_delete"
                  style="display:inline;"
                  onsubmit="return confirm('Usunąć zgłoszenie <?= Helpers::e($f['ticket_number']) ?>? Operacja jest nieodwracalna.');">
              <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
              <input type="hidden" name="failure_id" value="<?= $f['id'] ?>">
              <button type="submit" class="btn btn-sm" style="border-color:#fca5a5;color:#dc2626;">Usuń</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <tr><td colspan="9" class="muted" style="text-align:center;padding:20px;">Brak zgłoszeń spełniających kryteria.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php /* ZMIANA 1: $pager['page'] → $pager['current_page'] (fix Undefined array key) */ ?>
  <?php if (!empty($pager) && $pager['total_pages'] > 1): ?>
  <div class="card-body" style="padding:10px 16px;border-top:1px solid #f3f4f6;">
    <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
      <?php if ($pager['current_page'] > 1): ?>
        <a href="?route=failures&page=<?= $pager['current_page'] - 1 ?>&<?= http_build_query(array_filter([
          'status_id'   => $_GET['status_id']   ?? '',
          'line_id'     => $_GET['line_id']     ?? '',
          'category_id' => $_GET['category_id'] ?? '',
          'search'      => $_GET['search']      ?? '',
        ])) ?>" class="btn btn-sm">← Poprzednia</a>
      <?php endif; ?>
      <span class="muted fs-sm">Strona <?= $pager['current_page'] ?> / <?= $pager['total_pages'] ?></span>
      <?php if ($pager['current_page'] < $pager['total_pages']): ?>
        <a href="?route=failures&page=<?= $pager['current_page'] + 1 ?>&<?= http_build_query(array_filter([
          'status_id'   => $_GET['status_id']   ?? '',
          'line_id'     => $_GET['line_id']     ?? '',
          'category_id' => $_GET['category_id'] ?? '',
          'search'      => $_GET['search']      ?? '',
        ])) ?>" class="btn btn-sm">Następna →</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
