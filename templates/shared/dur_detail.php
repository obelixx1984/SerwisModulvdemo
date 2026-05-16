<?php
// templates/shared/dur_detail.php
// ZMIANA: przycisk "Edytuj" widoczny tylko dla autora raportu z uprawnieniem 'dur'

use App\Helpers\Helpers;
$pageTitle = 'Szczegóły DUR';
require BASE_PATH . '/templates/shared/header.php';

// ZMIANA 2: odczyt konfiguracji statusów z settings
$durStatusConfig = [];
try {
    $saved = (new \App\Models\SettingsModel())->get('dur_review_statuses');
    if ($saved) $durStatusConfig = json_decode($saved, true) ?? [];
} catch (\Throwable $e) {}
$durStatusConfig += [
    'completed'   => ['label' => 'Zakończony', 'color' => '#16a34a'],
    'partial'     => ['label' => 'Częściowy',  'color' => '#d97706'],
    'interrupted' => ['label' => 'Przerwany',  'color' => '#dc2626'],
];

$sc = $durStatusConfig[$review['status']]['color'] ?? '#374151';
$sl = $durStatusConfig[$review['status']]['label'] ?? $review['status'];

$currentUser  = \App\Helpers\Auth::user();
$isAuthor     = (int)($review['performed_by'] ?? 0) === (int)$currentUser['id'];
$canEditDur   = \App\Helpers\Auth::hasPermission('dur') && $isAuthor;
?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
  <a href="<?= BASE_URL ?>/index.php?route=dur" class="btn btn-sm">← Lista DUR</a>
  <h1 style="font-size:16px;font-weight:700;margin:0;">
    <?= Helpers::reviewTypeLabel($review['review_type']) ?> — <?= Helpers::e($review['line_name']) ?>
  </h1>
  <span class="badge" style="background:<?= $sc ?>;color:#fff;"><?= $sl ?></span>

  <?php if ($canEditDur): ?>
    <a href="<?= BASE_URL ?>/index.php?route=dur_edit&id=<?= $review['id'] ?>"
       class="btn btn-sm btn-v" style="margin-left:auto;">
      Edytuj raport
    </a>
  <?php endif; ?>
</div>

<div class="g2">
  <div class="card mb2">
    <div class="card-head"><span class="card-title">Informacje ogólne</span></div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 20px;">
        <div><div class="flbl">Linia</div><div class="fw6"><?= Helpers::e($review['line_name']) ?></div></div>
        <div><div class="flbl">Podzespół</div><div class="fw6"><?= Helpers::e($review['subsystem_name'] ?? '—') ?></div></div>
        <div><div class="flbl">Typ przeglądu</div><div><?= Helpers::reviewTypeLabel($review['review_type']) ?></div></div>
        <div><div class="flbl">Data wykonania</div><div><?= Helpers::e($review['review_date']) ?></div></div>
        <div><div class="flbl">Mechanik</div><div><?= Helpers::e($review['performer_name']) ?></div></div>
        <div><div class="flbl">Czas trwania</div><div><?= $review['duration_minutes'] ? (int)$review['duration_minutes'] . ' min' : '—' ?></div></div>
        <?php if ($review['next_review_date']): ?>
          <div><div class="flbl">Następny przegląd</div><div><?= Helpers::e($review['next_review_date']) ?></div></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php if ($review['notes']): ?>
    <div class="alert alert-w" style="align-self:start;">⚠ <strong>Uwagi:</strong> <?= Helpers::e($review['notes']) ?></div>
  <?php endif; ?>
</div>

<div class="g2">
  <div class="card">
    <div class="card-head"><span class="card-title">Wykonane czynności</span></div>
    <div class="card-body">
      <?php foreach (explode("\n", $review['activities']) as $a): if (trim($a)): ?>
        <div class="dur-item"><span class="ck">✓</span><span><?= Helpers::e(ltrim(trim($a), '-')) ?></span></div>
      <?php endif; endforeach; ?>
    </div>
  </div>
  <?php if ($review['parts_used']): ?>
    <div class="card">
      <div class="card-head"><span class="card-title">Wymienione części i materiały</span></div>
      <div class="card-body">
        <?php foreach (explode("\n", $review['parts_used']) as $p): if (trim($p)): ?>
          <div style="padding:4px 0;border-bottom:1px solid #f3f4f6;font-size:13px;">- <?= Helpers::e(trim($p)) ?></div>
        <?php endif; endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
