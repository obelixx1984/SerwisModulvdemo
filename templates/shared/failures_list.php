<?php
use App\Helpers\Helpers;
$pageTitle = 'Zgłoszenia awarii';
require BASE_PATH . '/templates/shared/header.php';
?>
<div class="sh">
  <a href="<?= BASE_URL ?>/index.php?route=report" class="btn btn-p btn-sm">+ Nowe zgłoszenie</a>
</div>

<div class="card mb2" style="padding:10px 16px;display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
  <form method="GET" action="<?= BASE_URL ?>/index.php" style="display:contents;">
    <input type="hidden" name="route" value="failures">
    <div class="fg" style="margin:0;flex:1;min-width:120px;">
      <label class="flbl">Status</label>
      <select name="status_id" class="fc">
        <option value="">Wszystkie</option>
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
        <option value="">Wszystkie linie</option>
        <?php foreach ($lines as $l): ?>
        <option value="<?= $l['id'] ?>" <?= ($_GET['line_id'] ?? '') == $l['id'] ? 'selected' : '' ?>>
          <?= Helpers::e($l['name']) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="fg" style="margin:0;flex:1;min-width:100px;">
      <label class="flbl">Rodzaj</label>
      <select name="category_id" class="fc">
        <option value="">Wszystkie</option>
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
          <?php /* Zmiana 4: imię i nazwisko zamiast loginu */ ?>
          <td class="fs-sm"><?= Helpers::e($f['reporter_name'] ?? $f['reporter_acronym'] ?? '—') ?></td>
          <td class="fs-sm"><?= Helpers::e($f['line_name']) ?></td>
          <td class="fs-sm"><?= Helpers::e($f['subsystem_name'] ?? '—') ?></td>
          <td><?= $f['category_id'] ? Helpers::catBadge($f['cat_label'], $f['cat_color']) : '<span class="muted fs-sm">—</span>' ?></td>
          <?php /* Zmiana 1: symptom_name jako fallback do dict_title */ ?>
          <td class="fs-sm fw6"><?= Helpers::e($f['symptom_name'] ?? $f['dict_title'] ?? mb_substr($f['description'] ?? '', 0, 40)) ?></td>
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
        <tr><td colspan="9" style="text-align:center;padding:24px;" class="muted">Brak zgłoszeń spełniających kryteria.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pager['total_pages'] > 1): ?>
  <div style="padding:12px 16px;display:flex;gap:6px;align-items:center;border-top:1px solid #f3f4f6;flex-wrap:wrap;">
    <?php
    $qbase = BASE_URL.'/index.php?route=failures'
      .(!empty($_GET['status_id'])   ? '&status_id='.(int)$_GET['status_id'] : '')
      .(!empty($_GET['line_id'])     ? '&line_id='.(int)$_GET['line_id'] : '')
      .(!empty($_GET['category_id']) ? '&category_id='.urlencode($_GET['category_id']) : '')
      .(!empty($_GET['search'])      ? '&search='.urlencode($_GET['search']) : '');
    ?>
    <?php if ($pager['has_prev']): ?>
    <a href="<?= $qbase ?>&page=<?= $pager['current_page']-1 ?>" class="btn btn-sm">← Poprzednia</a>
    <?php endif; ?>
    <span class="muted fs-sm">Strona <?= $pager['current_page'] ?> / <?= $pager['total_pages'] ?> (<?= $pager['total'] ?> zgłoszeń)</span>
    <?php if ($pager['has_next']): ?>
    <a href="<?= $qbase ?>&page=<?= $pager['current_page']+1 ?>" class="btn btn-sm">Następna →</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
