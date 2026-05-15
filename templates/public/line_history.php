<?php

use App\Helpers\Helpers;

$pageTitle = 'Historia linii';
require BASE_PATH . '/templates/shared/header.php';
?>

<div class="line-sel-bar">
  <form method="GET" action="<?= BASE_URL ?>/index.php" style="display:contents;">
    <input type="hidden" name="route" value="line_history">
    <label>Linia produkcyjna:</label>
    <select name="line_id" class="fc" style="max-width:280px;" onchange="this.form.submit()">
      <option value="">— Wybierz linię —</option>
      <?php foreach ($lines as $l): ?>
        <option value="<?= $l['id'] ?>" <?= $currentLine && $currentLine['id'] == $l['id'] ? 'selected' : '' ?>>
          <?= Helpers::e($l['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="days" class="fc" style="width:auto;font-size:12px;" onchange="this.form.submit()">
      <option value="7" <?= $days == 7  ? 'selected' : '' ?>>7 dni</option>
      <option value="30" <?= $days == 30 ? 'selected' : '' ?>>30 dni</option>
      <option value="90" <?= $days == 90 ? 'selected' : '' ?>>90 dni</option>
      <option value="365" <?= $days == 365 ? 'selected' : '' ?>>365 dni</option>
    </select>
  </form>
</div>

<?php if (!$currentLine): ?>
  <div class="card" style="border:2px dashed #e5e7eb;">
    <div class="card-body" style="text-align:center;padding:40px 20px;">
      <div class="muted">Wybierz linię produkcyjną powyżej, aby zobaczyć historię awarii.</div>
    </div>
  </div>
<?php else: ?>

  <div class="g4 mb2">
    <div class="stat-card">
      <div class="stat-val sv-r" style="font-size:22px;"><?= (int)($stats['total'] ?? 0) ?></div>
      <div class="stat-lbl">Awarii łącznie</div>
    </div>
    <div class="stat-card">
      <div class="stat-val sv-a" style="font-size:22px;"><?= (int)($stats['open_count'] ?? 0) ?></div>
      <div class="stat-lbl">Otwarte</div>
    </div>
    <div class="stat-card">
      <div class="stat-val sv-g" style="font-size:22px;"><?= (int)($stats['closed_count'] ?? 0) ?></div>
      <div class="stat-lbl">Zamknięte</div>
    </div>
    <div class="stat-card">
      <div class="stat-val sv-v" style="font-size:22px;"><?= Helpers::e($stats['avg_repair_str'] ?? '—') ?></div>
      <div class="stat-lbl">Śr. czas naprawy</div>
    </div>
  </div>

  <div class="g2">
    <div class="card mb2">
      <div class="card-head"><span class="card-title">Zgłoszenia — <?= Helpers::e($currentLine['name']) ?></span></div>
      <?php if ($failures): ?>
        <div class="twrap">
          <table>
            <thead>
              <tr>
                <th>Numer</th>
                <th>Data</th>
                <?php if (!empty($currentLine['subsystems_str'])): ?><th>Podzespół</th><?php endif; ?>
                <th>Objaw / Usterka</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($failures as $f): ?>
                <tr<?= empty($f['closed_at']) ? ' style="background:#fffbeb;"' : '' ?>>
                  <td class="mono fw6 fs-sm" style="color:#0a2463;">
                    <?php if (\App\Helpers\Auth::isMechanic()): ?>
                      <a href="<?= BASE_URL ?>/index.php?route=failure_detail&id=<?= $f['id'] ?>"><?= Helpers::e($f['ticket_number']) ?></a>
                      <?php else: ?><?= Helpers::e($f['ticket_number']) ?><?php endif; ?>
                  </td>
                  <td class="muted fs-sm"><?= Helpers::formatDateOnly($f['created_at']) ?></td>
                  <?php if (!empty($currentLine['subsystems_str'])): ?>
                    <td class="fs-sm"><?= Helpers::e($f['subsystem_name'] ?? '—') ?></td>
                  <?php endif; ?>
                  <?php /* Zmiana 1: symptom_name jako fallback */ ?>
                  <td class="fs-sm">
                    <?php if (!empty($f['other_symptom'])): ?>
                      <?php $d = trim($f['description'] ?? ''); ?>
                      <span style="font-style:italic;color:#6b7280;" title="<?= Helpers::e($d) ?>">
                        <?= $d !== '' ? Helpers::e(mb_strlen($d) > 42 ? mb_substr($d, 0, 40) . '…' : $d) : 'Inne objawy' ?>
                      </span>
                    <?php else: ?>
                      <?= Helpers::e($f['symptom_name'] ?? $f['dict_title'] ?? mb_substr($f['description'] ?? '', 0, 42) ?: '—') ?>
                    <?php endif; ?>
                  </td>
                  <td><?= Helpers::statusBadge($f['status_label'], $f['status_color']) ?></td>
                  </tr>
                <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="card-body muted" style="text-align:center;">Brak awarii w wybranym okresie.</div>
      <?php endif; ?>
    </div>

    <div>
      <?php if ($durList): ?>
        <div class="card">
          <div class="card-head"><span class="card-title">Przeglądy DUR</span>
            <a href="<?= BASE_URL ?>/index.php?route=dur" class="btn btn-sm">Wszystkie</a>
          </div>
          <div class="card-body" style="padding:8px;">
            <?php foreach ($durList as $r): ?>
              <div class="dur-card">
                <div class="dur-title"><?= Helpers::reviewTypeLabel($r['review_type']) ?> — <?= Helpers::e($r['review_date']) ?>
                  <?= $r['subsystem_name'] ? ' · ' . Helpers::e($r['subsystem_name']) : '' ?></div>
                <div class="dur-meta"><?= Helpers::e($r['performer_name']) ?> · <?= (int)$r['duration_minutes'] ?> min</div>
                <?php foreach (array_slice(explode("\n", $r['activities']), 0, 2) as $a): if (trim($a)): ?>
                    <div class="dur-item"><span class="ck">✓</span><span class="fs-sm"><?= Helpers::e(ltrim(trim($a), '-')) ?></span></div>
                <?php endif;
                endforeach; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="card">
          <div class="card-body" style="text-align:center;padding:20px;">
            <div class="muted">Brak przeglądów DUR dla tej linii.</div>
            <?php if (\App\Helpers\Auth::isMechanic()): ?>
              <a href="<?= BASE_URL ?>/index.php?route=dur_add" class="btn btn-v btn-sm mt1">+ Dodaj raport DUR</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>