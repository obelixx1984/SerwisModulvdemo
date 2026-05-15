<?php

use App\Helpers\Helpers;
use App\Helpers\Auth;

$pageTitle = 'Zgłoszenie Awarii';
require BASE_PATH . '/templates/shared/header.php';

$subsystemsJs = [];
foreach ($lines as $l) {
  $subs = [];
  if (!empty($l['subsystems_str'])) {
    $ids   = explode(',', $l['subsystem_ids'] ?? '');
    $names = explode('|||', $l['subsystems_str']);
    foreach ($names as $i => $n) {
      $subs[] = ['id' => trim($ids[$i] ?? ''), 'name' => trim($n)];
    }
  }
  $subsystemsJs[$l['id']] = $subs;
}
?>
<div class="pub-layout">
  <div>
    <div class="card">
      <div class="card-body">
        <div class="pub-header">
          <div class="pub-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
              <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
              <line x1="12" y1="9" x2="12" y2="13" />
              <line x1="12" y1="17" x2="12.01" y2="17" />
            </svg>
          </div>
          <div class="pub-title">Zgłoszenie Awarii</div>
          <div class="pub-sub">Wybierz linię aby sprawdzić historię →</div>
        </div>

        <?php if ($duplicate): ?>
          <div class="dup-warn" style="display:block;">
            ⚠ <strong>Uwaga:</strong> Zgłoszenie <strong><?= Helpers::e($duplicate['ticket_number']) ?></strong>
            z tą samą usterką jest już otwarte na tej linii!
          </div>
        <?php else: ?>
          <div class="dup-warn" id="dupWarn">
            ⚠ <strong>Uwaga:</strong> Zgłoszenie <strong id="dupTicket"></strong>
            z tą samą usterką jest już otwarte na tej linii!
          </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/index.php?route=report_post">
          <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">

          <div class="fg">
            <label class="flbl">Osoba zgłaszająca</label>
            <?php $currentUser = Auth::user(); ?>
            <div class="fc" style="background:#f3f4f6;color:#374151;cursor:default;">
              <?= Helpers::e($currentUser['name']) ?>
            </div>
            <input type="hidden" name="reporter_user_id" value="<?= (int)$currentUser['id'] ?>">
          </div>

          <div class="fg">
            <label class="flbl">Linia produkcyjna <span class="req">*</span></label>
            <select name="production_line_id" id="pubLine" class="fc" required>
              <option value="">— Wybierz linię —</option>
              <?php foreach ($lines as $l): ?>
                <option value="<?= $l['id'] ?>"
                  <?= $currentLine && (int)$currentLine['id'] === (int)$l['id'] ? 'selected' : '' ?>>
                  <?= Helpers::e($l['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <?php
          $hasSubs = $currentLine && !empty($currentLine['subsystems_str']);
          $subIds  = $hasSubs ? explode(',', $currentLine['subsystem_ids'] ?? '') : [];
          $subNames = $hasSubs ? explode('|||', $currentLine['subsystems_str']) : [];
          ?>
          <div class="fg" id="subGrp" style="display:<?= $hasSubs ? 'block' : 'none' ?>;">
            <label class="flbl">Podzespół <span class="req">*</span></label>
            <select name="subsystem_id" id="subsystemSelect" class="fc">
              <option value="">— Wybierz podzespół —</option>
              <?php foreach ($subNames as $i => $sname): $sid = trim($subIds[$i] ?? ''); ?>
                <option value="<?= Helpers::e($sid) ?>"
                  <?= ($_POST['subsystem_id'] ?? '') == $sid ? 'selected' : '' ?>>
                  <?= Helpers::e(trim($sname)) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="fg">
            <label class="flbl">Rodzaj awarii <span class="req">*</span></label>
            <select name="category_id" id="pubCat" class="fc" required>
              <option value="">— Wybierz rodzaj —</option>
              <?php
              $selectedCat = $_POST['category_id'] ?? $_GET['cat_id'] ?? '';
              ?>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>"
                  <?= $selectedCat == $cat['id'] ? 'selected' : '' ?>>
                  <?= Helpers::e($cat['label']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="fg">
            <label class="flbl">Usterka ze słownika</label>
            <select name="dictionary_item_id" id="pubDict" class="fc">
              <option value="">— Wybierz typową usterkę —</option>
              <?php foreach ($dictionary as $d): ?>
                <option value="<?= $d['id'] ?>" data-cat="<?= $d['category_id'] ?>"
                  <?= ($_POST['dictionary_item_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                  <?= Helpers::e($d['title']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="fg">
            <label class="flbl">Własny opis usterki</label>
            <textarea name="description" class="fc" rows="3"
              placeholder="Opisz dokładnie objawy awarii..."><?= Helpers::e($_POST['description'] ?? '') ?></textarea>
          </div>

          <button type="submit" class="pub-btn">🚨 Wyślij zgłoszenie awarii</button>
        </form>
      </div>
    </div>
  </div>

  <div>
    <?php if (!$currentLine): ?>
      <div class="card" style="border:2px dashed #e5e7eb;">
        <div class="card-body" style="text-align:center;padding:40px 20px;">
          <div style="font-size:3rem;margin-bottom:10px;">🏭</div>
          <div class="fw6 mb1">Wybierz linię produkcyjną</div>
          <div class="muted fs-sm">Po wybraniu linii zobaczysz historię awarii z ostatnich 30 dni i ostatnie przeglądy DUR — dzięki temu unikniesz duplikowania zgłoszeń przy 3-zmianowej pracy.</div>
        </div>
      </div>
    <?php else: ?>

      <div class="g3 mb2" style="gap:8px;">
        <div class="stat-card">
          <div class="stat-val sv-r" style="font-size:22px;"><?= (int)($lineStats['total'] ?? 0) ?></div>
          <div class="stat-lbl">Awarii / 30 dni</div>
        </div>
        <div class="stat-card">
          <div class="stat-val <?= (int)($lineStats['open_count'] ?? 0) > 0 ? 'sv-r' : 'sv-g' ?>" style="font-size:22px;">
            <?= (int)($lineStats['open_count'] ?? 0) ?>
          </div>
          <div class="stat-lbl">Otwarte</div>
        </div>
        <div class="stat-card">
          <div class="stat-val sv-g" style="font-size:22px;"><?= Helpers::e($lineStats['avg_repair_str'] ?? '—') ?></div>
          <div class="stat-lbl">Śr. czas naprawy</div>
        </div>
      </div>

      <div class="card mb2">
        <div class="card-head">
          <span class="card-title">Historia awarii — <?= Helpers::e($currentLine['name']) ?></span>
          <span class="muted fs-sm">ostatnie 30 dni</span>
        </div>
        <?php if ($lineHistory): ?>
          <div class="twrap">
            <table>
              <thead>
                <tr>
                  <th>Numer</th>
                  <th>Data</th>
                  <?php if (!empty($currentLine['subsystems_str'])): ?><th>Podzespół</th><?php endif; ?>
                  <th>Usterka</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($lineHistory as $f): ?>
                  <tr<?= empty($f['closed_at']) ? ' style="background:#fffbeb;"' : '' ?>>
                    <td class="mono fw6 fs-sm" style="color:#0a2463;"><?= Helpers::e($f['ticket_number']) ?></td>
                    <td class="muted fs-sm"><?= Helpers::formatDateOnly($f['created_at']) ?></td>
                    <?php if (!empty($currentLine['subsystems_str'])): ?>
                      <td class="fs-sm"><?= Helpers::e($f['subsystem_name'] ?? '—') ?></td>
                    <?php endif; ?>
                    <td class="fs-sm"><?= Helpers::e($f['dict_title'] ?? mb_substr($f['description'] ?? '', 0, 40)) ?></td>
                    <td><?= Helpers::statusBadge($f['status_label'], $f['status_color']) ?></td>
                    </tr>
                  <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="card-body muted" style="text-align:center;">Brak awarii w ostatnich 30 dniach.</div>
        <?php endif; ?>
      </div>

      <?php if ($lineDur): ?>
        <div class="card">
          <div class="card-head">
            <span class="card-title">Przeglądy DUR — <?= Helpers::e($currentLine['name']) ?></span>
          </div>
          <div class="card-body" style="padding:8px;">
            <?php foreach ($lineDur as $r): ?>
              <div class="dur-card">
                <div class="dur-title">
                  <?= Helpers::reviewTypeLabel($r['review_type']) ?> — <?= Helpers::e($r['review_date']) ?>
                  <?= $r['subsystem_name'] ? ' · ' . Helpers::e($r['subsystem_name']) : '' ?>
                </div>
                <div class="dur-meta"><?= Helpers::e($r['performer_name']) ?> · <?= (int)$r['duration_minutes'] ?> min</div>
                <?php foreach (array_slice(explode("\n", $r['activities']), 0, 3) as $a): if (trim($a)): ?>
                    <div class="dur-item"><span class="ck">✓</span><span><?= Helpers::e(ltrim(trim($a), '-')) ?></span></div>
                <?php endif;
                endforeach; ?>
                <?php if ($r['next_review_date']): ?>
                  <div class="dur-next"><span style="color:#7c3aed;">▶</span> Następny: <strong><?= Helpers::e($r['next_review_date']) ?></strong></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<script>
  window.SUBSYSTEMS = <?= json_encode($subsystemsJs, JSON_HEX_TAG) ?>;
  document.getElementById('pubLine').addEventListener('change', function() {
    var lineId = this.value;
    var reporter = document.querySelector('select[name="reporter_acronym"]') ?
      document.querySelector('select[name="reporter_acronym"]').value : '';
    var cat = document.getElementById('pubCat') ? document.getElementById('pubCat').value : '';
    var url = '<?= BASE_URL ?>/index.php?route=report';
    if (lineId) url += '&line_id=' + encodeURIComponent(lineId);
    if (reporter) url += '&reporter=' + encodeURIComponent(reporter);
    if (cat) url += '&cat_id=' + encodeURIComponent(cat);
    window.location.href = url;
  });

  // Sprawdź duplikaty po wyborze usterki (błąd 4)
  (function() {
    var dictSel = document.getElementById('pubDict');
    var lineSel = document.getElementById('pubLine');
    if (!dictSel || !lineSel) return;
    dictSel.addEventListener('change', function() {
      var dictId = this.value;
      var lineId = lineSel.value;
      var dw = document.getElementById('dupWarn');
      var dt = document.getElementById('dupTicket');
      if (!dw || !dt) return;
      if (!dictId || !lineId) {
        dw.style.display = 'none';
        return;
      }
      fetch('<?= BASE_URL ?>/index.php?route=check_duplicate&line_id=' + lineId + '&dict_id=' + dictId)
        .then(function(r) {
          return r.json();
        })
        .then(function(data) {
          if (data && data.ticket) {
            dt.textContent = data.ticket;
            dw.style.display = 'block';
          } else {
            dw.style.display = 'none';
          }
        }).catch(function() {
          dw.style.display = 'none';
        });
    });
  })();
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>