<?php
use App\Helpers\Helpers;
$pageTitle = 'Szczegóły — '.$failure['ticket_number'];
require BASE_PATH . '/templates/shared/header.php';
?>
<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap;">
  <a href="<?= BASE_URL ?>/index.php?route=failures" class="btn btn-sm">← Lista</a>
  <h1 style="font-size:20px;font-weight:700;font-family:monospace;"><?= Helpers::e($failure['ticket_number']) ?></h1>
  <?= Helpers::statusBadge($failure['status_label'], $failure['status_color']) ?>
</div>

<div class="g2">
  <div>
    <div class="card mb2">
      <div class="card-head">
        <span class="card-title">Szczegóły zgłoszenia</span>
        <a href="<?= BASE_URL ?>/index.php?route=line_history&line_id=<?= $failure['production_line_id'] ?>" class="btn btn-sm">Historia linii</a>
      </div>
      <div class="card-body">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px 20px;">
          <div><div class="flbl">Linia</div><div class="fw6"><?= Helpers::e($failure['line_name']) ?></div></div>
          <div><div class="flbl">Podzespół</div><div class="fw6"><?= Helpers::e($failure['subsystem_name'] ?? '—') ?></div></div>
          <div><div class="flbl">Rodzaj awarii</div><div><?= Helpers::catBadge($failure['cat_label'], $failure['cat_color']) ?></div></div>
          <div><div class="flbl">Zgłaszający</div><div><?= Helpers::e($failure['reporter_name'] ?? $failure['reporter_acronym'] ?? '—') ?></div></div>
          <div><div class="flbl">Data zgłoszenia</div><div><?= Helpers::formatDate($failure['created_at']) ?></div></div>
          <div><div class="flbl">Numer</div><div class="mono fw6"><?= Helpers::e($failure['ticket_number']) ?></div></div>
          <?php if ($failure['closed_at']): ?>
          <div><div class="flbl">Data zamknięcia</div><div><?= Helpers::formatDate($failure['closed_at']) ?></div></div>
          <?php endif; ?>
          <div style="grid-column:1/-1;"><div class="flbl">Usterka</div><div class="fw6"><?= Helpers::e($failure['dict_title'] ?? '—') ?></div></div>
          <?php if ($failure['description']): ?>
          <div style="grid-column:1/-1;"><div class="flbl">Opis własny</div><div><?= nl2br(Helpers::e($failure['description'])) ?></div></div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card mb2">
      <div class="card-head"><span class="card-title">Komentarze serwisowe</span></div>
      <div class="card-body">
        <?php if ($comments): ?>
        <?php foreach ($comments as $c): ?>
        <div style="padding:8px 0;border-bottom:1px solid #f3f4f6;">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
            <strong class="fs-sm"><?= Helpers::e($c['author']) ?></strong>
            <span class="muted fs-sm"><?= Helpers::formatDate($c['created_at']) ?></span>
          </div>
          <p style="font-size:13px;line-height:1.5;"><?= nl2br(Helpers::e($c['comment'])) ?></p>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <p class="muted fs-sm mb1">Brak komentarzy.</p>
        <?php endif; ?>

        <div class="sep"></div>
        <?php if (\App\Helpers\Auth::isMechanic()): ?>
        <form method="POST" action="<?= BASE_URL ?>/index.php?route=add_comment">
          <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
          <input type="hidden" name="failure_id" value="<?= $failure['id'] ?>">
          <div class="fg mb1"><label class="flbl">Dodaj komentarz</label>
            <textarea name="comment" class="fc" rows="3" placeholder="Opisz wykonane czynności..." required></textarea></div>
          <button type="submit" class="btn btn-p btn-sm">Dodaj komentarz</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div>
    <?php if (\App\Helpers\Auth::isMechanic()): ?>
    <div class="card mb2">
      <div class="card-head"><span class="card-title">Zmień status</span></div>
      <div class="card-body">

        <?php if (!empty($failure['status_is_final'])): ?>
        <!-- BŁĄD 3: status końcowy — zablokuj zmianę -->
        <div class="alert alert-w">
          <strong>Zgłoszenie zamknięte.</strong><br>
          Status <strong><?= Helpers::e($failure['status_label']) ?></strong> jest statusem końcowym — nie można dalej zmieniać statusu tego zgłoszenia.
        </div>

        <?php else: ?>
        <form method="POST" action="<?= BASE_URL ?>/index.php?route=status_change">
          <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
          <input type="hidden" name="failure_id" value="<?= $failure['id'] ?>">
          <div class="fg"><label class="flbl">Nowy status</label>
            <select name="status_id" class="fc" required>
              <option value="">— Wybierz nowy status —</option>
              <?php foreach ($statuses as $s): ?>
              <?php
              // BŁĄD 4: wyklucz status startowy (nigdy nie można go przypisać ręcznie)
              if (!empty($s['is_initial'])) continue;
              // BŁĄD 3+aktualny: wyklucz aktualny status
              if ($s['id'] == $failure['status_id']) continue;
              ?>
              <option value="<?= $s['id'] ?>">
                <?= Helpers::e($s['label']) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <span class="fhint">Aktualny: <?= Helpers::statusBadge($failure['status_label'], $failure['status_color']) ?></span>
          </div>
          <div class="fg"><label class="flbl">Uwaga (opcjonalnie)</label>
            <textarea name="note" class="fc" rows="2" placeholder="Powód zmiany..."></textarea></div>
          <button type="submit" class="btn btn-p btn-block">Zapisz status</button>
        </form>
        <?php endif; ?>

      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-head"><span class="card-title">Historia zdarzeń</span></div>
      <div class="card-body">
        <ul class="tl">
          <?php foreach (array_reverse($history) as $h): ?>
          <li class="tl-i">
            <div class="tl-dot<?= $h['action']==='status_changed' ? ' g' : ($h['action']==='comment_added' ? ' v' : '') ?>"></div>
            <div class="tl-time"><?= Helpers::formatDate($h['created_at']) ?> · <?= Helpers::e($h['actor_name']) ?></div>
            <div class="tl-txt">
              <?php if ($h['action']==='created'): ?>
                <strong>Zgłoszenie utworzone</strong>
              <?php elseif ($h['action']==='status_changed'): ?>
                <strong>Zmiana statusu</strong>
                <?php if ($h['old_status_label']): ?>
                  <span class="badge" style="background:<?= Helpers::e($h['old_status_color']) ?>;color:#fff;"><?= Helpers::e($h['old_status_label']) ?></span> →
                <?php endif; ?>
                <span class="badge" style="background:<?= Helpers::e($h['new_status_color']) ?>;color:#fff;"><?= Helpers::e($h['new_status_label']) ?></span>
              <?php elseif ($h['action']==='comment_added'): ?>
                <strong>Dodano komentarz</strong>
              <?php else: ?>
                <strong><?= Helpers::e($h['action']) ?></strong>
              <?php endif; ?>
              <?php if ($h['note']): ?>
              <div class="muted fs-sm mt1"><?= Helpers::e($h['note']) ?></div>
              <?php endif; ?>
            </div>
          </li>
          <?php endforeach; ?>
          <?php if (!$history): ?>
          <li class="muted fs-sm">Brak historii.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
