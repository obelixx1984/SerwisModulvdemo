<?php
// templates/shared/dur_list.php
// ZMIANA: dodana paginacja (limit 18 na stronę)

use App\Helpers\Helpers;
$pageTitle = 'Przeglądy DUR';
require BASE_PATH . '/templates/shared/header.php';
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
    $dl = (int)$u['days_left'];
    $bc = $dl <= 0 ? '#dc2626' : ($dl <= 3 ? '#d97706' : '#7c3aed');
    $bl = $dl <= 0 ? 'zaległy!' : 'za ' . $dl . ' dni';
  ?>
  <div class="dur-up-item">
    <span><?= Helpers::e($u['line_name']) ?> — <?= Helpers::reviewTypeLabel($u['review_type']) ?></span>
    <span class="badge" style="background:<?= $bc ?>;color:#fff;"><?= $bl ?></span>
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
        <option value="completed"   <?= ($_GET['status'] ?? '') === 'completed'   ? 'selected' : '' ?>>Zakończony</option>
        <option value="partial"     <?= ($_GET['status'] ?? '') === 'partial'     ? 'selected' : '' ?>>Częściowy</option>
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
    $sc = ['completed' => '#16a34a', 'partial' => '#d97706', 'interrupted' => '#dc2626'][$r['status']] ?? '#374151';
    $sl = ['completed' => 'Zakończony', 'partial' => 'Częściowy', 'interrupted' => 'Przerwany'][$r['status']] ?? $r['status'];
  ?>
  <div class="dur-card">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:6px;">
      <div>
        <div class="dur-title">
          <?= Helpers::reviewTypeLabel($r['review_type']) ?> — <?= Helpers::e($r['line_name']) ?>
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
    <?php endif; endforeach; ?>
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

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
