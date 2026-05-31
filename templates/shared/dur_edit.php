<?php
// templates/shared/dur_edit.php
// Formularz edycji istniejącego raportu DUR
// Dostępny tylko dla autora raportu z uprawnieniem 'dur'

use App\Helpers\Helpers;
use App\Helpers\Auth;

$pageTitle = 'Edytuj raport DUR';
require BASE_PATH . '/templates/shared/header.php';

$typeLabels = [];
try {
  $tl = (new \App\Models\SettingsModel())->get('dur_type_labels');
  if ($tl) $typeLabels = json_decode($tl, true) ?? [];
} catch (\Throwable $e) {
}

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
?>

<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
  <a href="<?= BASE_URL ?>/index.php?route=dur_detail&id=<?= $review['id'] ?>" class="btn btn-sm">← Szczegóły raportu</a>
  <h1 style="font-size:16px;font-weight:700;margin:0;">Edytuj raport DUR</h1>
</div>

<div style="max-width:700px;">
  <div class="card">
    <div class="card-head">
      <span class="card-title">
        <?= Helpers::reviewTypeLabel($review['review_type'], $typeLabels) ?> — <?= Helpers::e($review['line_name']) ?>
      </span>
    </div>
    <div class="card-body">
      <form method="POST" action="<?= BASE_URL ?>/index.php?route=dur_edit_post">
        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
        <input type="hidden" name="review_id" value="<?= (int)$review['id'] ?>">

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
              <?= Helpers::reviewTypeLabel($review['review_type'], $typeLabels) ?>
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
      <!-- ══ Karta: Części zamienne ═══════════════════════════════════ -->
      <div class="card mb2" style="margin-top:24px;">
        <div class="card-head" style="background:#eff6ff;border-bottom:1px solid #bfdbfe;"><span class="card-title" style="color:#1d4ed8;">🔧 Części zamienne</span></div>
        <div class="card-body">

          <?php if (!empty($durSpareParts)): ?>
            <table style="width:100%;border-collapse:collapse;margin-bottom:12px;">
              <thead>
                <tr>
                  <th style="text-align:left;padding:4px 8px;border-bottom:1px solid #e5e7eb;">Część</th>
                  <th style="text-align:left;padding:4px 8px;border-bottom:1px solid #e5e7eb;">Ilość</th>
                  <th style="text-align:left;padding:4px 8px;border-bottom:1px solid #e5e7eb;">Kategoria</th>
                  <th style="padding:4px 8px;border-bottom:1px solid #e5e7eb;"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($durSpareParts as $sp): ?>
                  <tr>
                    <td style="padding:4px 8px;"><?= Helpers::e($sp['part_name']) ?></td>
                    <td style="padding:4px 8px;"><?= (int)$sp['quantity'] ?></td>
                    <td style="padding:4px 8px;"><?= Helpers::catBadge($sp['category_name'], $sp['category_color']) ?></td>
                    <td style="padding:4px 8px;">
                      <form method="POST" action="<?= BASE_URL ?>/index.php?route=dur_spare_part_delete"
                        style="display:inline;"
                        onsubmit="return confirm('Usunąć tę część?');">
                        <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
                        <input type="hidden" name="spare_id" value="<?= $sp['id'] ?>">
                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                        <button type="submit" class="btn btn-sm" style="border-color:#fca5a5;color:#dc2626;">Usuń</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p class="muted fs-sm" style="margin:0 0 12px;">Brak dodanych części zamiennych.</p>
          <?php endif; ?>

          <form method="POST" action="<?= BASE_URL ?>/index.php?route=dur_spare_part_add"
            style="display:grid;grid-template-columns:1fr 80px 200px auto;gap:8px;align-items:end;">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
            <div>
              <label class="flbl" style="font-size:.75rem;">Nazwa części <span class="req">*</span></label>
              <input class="fc" name="part_name" placeholder="np. Uszczelka pompy" required>
            </div>
            <div>
              <label class="flbl" style="font-size:.75rem;">Ilość</label>
              <input class="fc" type="number" name="quantity" value="1" min="1" required>
            </div>
            <div>
              <label class="flbl" style="font-size:.75rem;">Kategoria <span class="req">*</span></label>
              <select class="fc" name="category_id" required>
                <option value="">— Wybierz —</option>
                <?php foreach ($sparePartCategories as $spc): ?>
                  <option value="<?= $spc['id'] ?>"><?= Helpers::e($spc['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <button type="submit" class="btn btn-p btn-sm">Dodaj</button>
            </div>
          </form>

        </div>
      </div>
      <!-- ══ Koniec karty: Części zamienne ══════════════════════════ -->
    </div>
  </div>
</div>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>