<?php
use App\Helpers\Helpers;
$pageTitle = 'Pulpit';
require BASE_PATH . '/templates/shared/header.php';
?>
<div class="sh">
  <a href="<?= BASE_URL ?>/index.php?route=report" class="btn btn-p btn-sm">+ Nowe zgłoszenie</a>
</div>

<?php if ($upcoming): ?>
<div class="alert alert-v mb2">
  <div class="fw6 mb1" style="color:#4c1d95;">⏰ Nadchodzące przeglądy DUR:</div>
  <?php foreach ($upcoming as $u):
    $dl = (int)$u['days_left'];
    $bc = $dl <= 0 ? '#dc2626' : ($dl <= 3 ? '#d97706' : '#7c3aed');
    $bl = $dl <= 0 ? 'zaległy!' : 'za '.$dl.' dni';
  ?>
  <div class="dur-up-item">
    <span><?= Helpers::e($u['line_name']) ?> — <?= Helpers::reviewTypeLabel($u['review_type']) ?></span>
    <span class="badge" style="background:<?= $bc ?>;color:#fff;"><?= $bl ?></span>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="stats mb2">
  <div class="stat-card"><div class="stat-val sv-r"><?= (int)($stats['new_count'] ?? 0) ?></div><div class="stat-lbl">Nowe awarie</div></div>
  <div class="stat-card"><div class="stat-val sv-a"><?= (int)($stats['progress_count'] ?? 0) ?></div><div class="stat-lbl">W trakcie</div></div>
  <div class="stat-card"><div class="stat-val sv-b"><?= (int)($stats['open_count'] ?? 0) ?></div><div class="stat-lbl">Otwarte łącznie</div></div>
  <?php /* ZMIANA 3: karta "Ilość awarii w [miesiąc]" zamiast "Śr. czas naprawy" */ ?>
  <div class="stat-card"><div class="stat-val sv-v"><?= (int)$monthlyCount ?></div><div class="stat-lbl">Awarii w <?= Helpers::e($currentMonthName) ?></div></div>
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
          <svg width="12" height="12" fill="none" stroke="#9ca3af" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
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

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
