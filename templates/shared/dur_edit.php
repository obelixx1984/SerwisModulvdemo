<?php
// templates/shared/dur_edit.php
// Formularz edycji istniejącego raportu DUR
// Dostępny tylko dla autora raportu z uprawnieniem 'dur'

use App\Helpers\Helpers;
use App\Helpers\Auth;

$pageTitle = 'Edytuj raport DUR';
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
?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
  <a href="<?= BASE_URL ?>/index.php?route=dur_detail&id=<?= $review['id'] ?>" class="btn btn-sm">← Szczegóły raportu</a>
  <h1 style="font-size:16px;font-weight:700;margin:0;">Edytuj raport DUR</h1>
</div>

<div style="max-width:700px;">
  <div class="card">
    <div class="card-head">
      <span class="card-title">
        <?= Helpers::reviewTypeLabel($review['review_type']) ?> — <?= Helpers::e($review['line_name']) ?>
      </span>
    </div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_URL ?>/index.php?route=dur_edit_post">
        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="review_id"  value="<?= (int)$review['id'] ?>">

        <div class="g2">
          <div class="fg">
            <label class="flbl">Linia produkcyjna</label>
            <div class="fc" style="background:#f3f4f6;color:#374151;cursor:default;">
              <?= Helpers::e($review['line_name']) ?>
              <?= $review['subsystem_name'] ? ' · ' . Helpers::e($review['subsystem_name']) : '' ?>
            </div>
            <span class="fhint">Linii nie można zmieniać po zapisaniu raportu.</span>
          </div>
          <div class="fg">
            <label class="flbl">Typ przeglądu</label>
            <div class="fc" style="background:#f3f4f6;color:#374151;cursor:default;">
              <?= Helpers::reviewTypeLabel($review['review_type']) ?>
            </div>
            <span class="fhint">Typ przeglądu nie może być zmieniany.</span>
          </div>
        </div>

        <div class="g2">
          <div class="fg">
            <label class="flbl">Data wykonania <span class="req">*</span></label>
            <input name="review_date" type="date" class="fc" required
                   value="<?= Helpers::e($review['review_date']) ?>">
          </div>
          <div class="fg">
            <label class="flbl">Czas trwania (minuty)</label>
            <input name="duration_minutes" type="number" class="fc" min="1" max="480"
                   value="<?= (int)($review['duration_minutes'] ?? 0) ?: '' ?>">
          </div>
        </div>

        <div class="fg">
          <label class="flbl">Wykonane czynności <span class="req">*</span></label>
          <textarea name="activities" class="fc" rows="7" required
            placeholder="- Kontrola wizualna maszyny&#10;- Smarowanie prowadnic..."><?= Helpers::e($review['activities']) ?></textarea>
        </div>

        <div class="fg">
          <label class="flbl">Wymienione części i materiały</label>
          <textarea name="parts_used" class="fc" rows="3"
            placeholder="np. Smar litowy 200g"><?= Helpers::e($review['parts_used'] ?? '') ?></textarea>
        </div>

        <div class="fg">
          <label class="flbl">Uwagi i zalecenia</label>
          <textarea name="notes" class="fc" rows="2"
            placeholder="np. Zalecana wymiana łożyska..."><?= Helpers::e($review['notes'] ?? '') ?></textarea>
        </div>

        <div class="g2">
          <div class="fg">
            <label class="flbl">Data następnego przeglądu</label>
            <input name="next_review_date" type="date" class="fc"
                   value="<?= Helpers::e($review['next_review_date'] ?? '') ?>">
          </div>
          <div class="fg">
            <label class="flbl">Status przeglądu</label>
            <select name="status" class="fc">
              <?php foreach (['completed', 'partial', 'interrupted'] as $sKey): ?>
                <?php $sLabel = $durStatusConfig[$sKey]['label'] ?? $sKey; ?>
                <option value="<?= $sKey ?>" <?= $review['status'] === $sKey ? 'selected' : '' ?>>
                  <?= Helpers::e($sLabel) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="sep"></div>
        <div style="display:flex;gap:8px;">
          <button type="submit" class="btn btn-v">Zapisz zmiany</button>
          <a href="<?= BASE_URL ?>/index.php?route=dur_detail&id=<?= $review['id'] ?>" class="btn">Anuluj</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
