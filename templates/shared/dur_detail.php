<?php
// templates/shared/dur_detail.php
// ZMIANA: przycisk "Edytuj" widoczny tylko dla autora raportu z uprawnieniem 'dur'

use App\Helpers\Helpers;

$pageTitle = 'Szczegóły DUR';
require BASE_PATH . '/templates/shared/header.php';

$archivedNotes = (new \App\Models\ScheduleNoteModel())->getArchivedByReview((int)$review['id']);

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

$typeLabels = [];
try {
  $tl = (new \App\Models\SettingsModel())->get('dur_type_labels');
  if ($tl) $typeLabels = json_decode($tl, true) ?? [];
} catch (\Throwable $e) {
}

$sc = $durStatusConfig[$review['status']]['color'] ?? '#374151';
$sl = $durStatusConfig[$review['status']]['label'] ?? $review['status'];

$currentUser  = \App\Helpers\Auth::user();
$isAuthor     = (int)($review['performed_by'] ?? 0) === (int)$currentUser['id'];
$canEditDur   = \App\Helpers\Auth::hasPermission('dur') && $isAuthor;
?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
  <a href="<?= BASE_URL ?>/index.php?route=dur" class="btn btn-sm">← Lista DUR</a>
  <h1 style="font-size:16px;font-weight:700;margin:0;">
    <?= Helpers::reviewTypeLabel($review['review_type'], $typeLabels) ?> — <?= Helpers::e($review['line_name']) ?>
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
        <div>
          <div class="flbl">Linia</div>
          <div class="fw6"><?= Helpers::e($review['line_name']) ?></div>
        </div>
        <div>
          <div class="flbl">Podzespół</div>
          <div class="fw6"><?= Helpers::e($review['subsystem_name'] ?? '—') ?></div>
        </div>
        <div>
          <div class="flbl">Typ przeglądu</div>
          <div><?= Helpers::reviewTypeLabel($review['review_type'], $typeLabels) ?></div>
        </div>
        <div>
          <div class="flbl">Data wykonania</div>
          <div><?= Helpers::e($review['review_date']) ?></div>
        </div>
        <div>
          <div class="flbl">Mechanik</div>
          <div><?= Helpers::e($review['performer_name']) ?></div>
        </div>
        <div>
          <div class="flbl">Czas trwania</div>
          <div><?= $review['duration_minutes'] ? (int)$review['duration_minutes'] . ' min' : '—' ?></div>
        </div>
        <?php if ($review['next_review_date']): ?>
          <div>
            <div class="flbl">Następny przegląd</div>
            <div><?= Helpers::e($review['next_review_date']) ?></div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php if ($review['notes']): ?>
    <div class="alert alert-w" style="align-self:start;">⚠ <strong>Uwagi:</strong> <?= Helpers::e($review['notes']) ?></div>
  <?php endif; ?>
</div>

<?php if (!empty($archivedNotes)): ?>
  <div class="card mb2" style="border-left:3px solid #7c3aed;">
    <div class="card-head">
      <span class="card-title" style="color:#4c1d95;">
        📝 Uwagi przed przeglądem (<?= count($archivedNotes) ?>)
      </span>
      <span class="badge" style="background:#f5f3ff;color:#4c1d95;border:1px solid #c4b5fd;">
        Archiwum
      </span>
    </div>
    <div class="card-body" style="padding:10px 14px;">
      <div class="muted fs-sm" style="margin-bottom:10px;">
        Uwagi dodane przez użytkowników przed wykonaniem tego przeglądu.
      </div>
      <?php foreach ($archivedNotes as $n): ?>
        <div style="padding:8px 10px;background:#f8fafc;border:1px solid #e5e7eb;
                  border-radius:7px;margin-bottom:8px;font-size:13px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
            <span class="fw6"><?= \App\Helpers\Helpers::e($n['user_name']) ?></span>
            <span class="muted" style="font-size:11px;"><?= substr($n['created_at'], 0, 16) ?></span>
          </div>
          <div style="white-space:pre-wrap;word-break:break-word;">
            <?= nl2br(\App\Helpers\Helpers::e($n['note'])) ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<div class="g2">
  <div class="card">
    <div class="card-head"><span class="card-title">Wykonane czynności</span></div>
    <div class="card-body">
      <?php foreach (explode("\n", $review['activities']) as $a): if (trim($a)): ?>
          <div class="dur-item"><span class="ck">✓</span><span><?= Helpers::e(ltrim(trim($a), '-')) ?></span></div>
      <?php endif;
      endforeach; ?>
    </div>
  </div>
  <!-- ══ Karta: Części zamienne ═══════════════════════════════════ -->
  <div class="card mb2" style="border-left:3px solid #0369a1;">
    <div class="card-head" style="background:#0a2463;border-bottom:1px solid #1e3a8a;">
      <span class="card-title" style="color:#fff;">🔧 Części zamienne</span>
    </div>
    <div class="card-body">
      <?php if (!empty($durSpareParts)): ?>
        <table style="width:100%;border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left;padding:4px 8px;border-bottom:1px solid #e5e7eb;">Część</th>
              <th style="text-align:left;padding:4px 8px;border-bottom:1px solid #e5e7eb;">Ilość</th>
              <th style="text-align:left;padding:4px 8px;border-bottom:1px solid #e5e7eb;">Kategoria</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($durSpareParts as $sp): ?>
              <tr>
                <td style="padding:4px 8px;"><?= Helpers::e($sp['part_name']) ?></td>
                <td style="padding:4px 8px;"><?= (int)$sp['quantity'] ?></td>
                <td style="padding:4px 8px;"><?= Helpers::catBadge($sp['category_name'], $sp['category_color']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="muted fs-sm" style="margin:0;">Brak dodanych części zamiennych.</p>
      <?php endif; ?>
    </div>
  </div>
  <!-- ══ Koniec karty: Części zamienne ══════════════════════════ -->
</div>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>