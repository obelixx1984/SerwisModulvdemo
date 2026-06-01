<?php
// templates/shared/dur_edit.php
// Formularz edycji istniejącego raportu DUR
// Dostępny tylko dla autora raportu z uprawnieniem 'dur'
// Karta części zamiennych po prawej + modal do dodawania/usuwania części

use App\Helpers\Helpers;
use App\Helpers\Auth;

$pageTitle = 'Edytuj raport DUR';
require BASE_PATH . '/templates/shared/header.php';

// Wczytanie niestandardowych etykiet typów przeglądów z ustawień
$typeLabels = [];
try {
    $tl = (new \App\Models\SettingsModel())->get('dur_type_labels');
    if ($tl) $typeLabels = json_decode($tl, true) ?? [];
} catch (\Throwable $e) {
}

// Wczytanie konfiguracji statusów przeglądów z ustawień admina
$durStatusConfig = [];
try {
    $saved = (new \App\Models\SettingsModel())->get('dur_review_statuses');
    if ($saved) $durStatusConfig = json_decode($saved, true) ?? [];
} catch (\Throwable $e) {
}
// Wartości domyślne statusów
$durStatusConfig += [
    'completed'   => ['label' => 'Zakończony', 'color' => '#16a34a'],
    'partial'     => ['label' => 'Częściowy',  'color' => '#d97706'],
    'interrupted' => ['label' => 'Przerwany',  'color' => '#dc2626'],
];

// Czy modal ma się otworzyć automatycznie po przekierowaniu z dur_form lub po akcji na częściach?
$openPartsModal = !empty($_GET['parts']) && $_GET['parts'] === '1';
?>

<!-- Pasek nawigacji powrotu -->
<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;">
    <a href="<?= BASE_URL ?>/index.php?route=dur_detail&id=<?= $review['id'] ?>" class="btn btn-sm">← Szczegóły raportu</a>
    <h1 style="font-size:16px;font-weight:700;margin:0;">Edytuj raport DUR</h1>
</div>

<!-- Układ dwukolumnowy: formularz edycji (lewa) + karta części zamiennych (prawa) -->
<div class="g2" style="align-items:start;">

    <!-- ══ Kolumna lewa: formularz edycji raportu DUR ══════════════ -->
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
                    <!-- Linia produkcyjna — tylko do odczytu -->
                    <div class="fg">
                        <label class="flbl">Linia produkcyjna</label>
                        <div class="fc" style="background:#f3f4f6;color:#374151;cursor:default;">
                            <?= Helpers::e($review['line_name']) ?>
                            <?= $review['subsystem_name'] ? ' · ' . Helpers::e($review['subsystem_name']) : '' ?>
                        </div>
                        <span class="fhint">Linii nie można zmieniać po zapisaniu raportu.</span>
                    </div>
                    <!-- Typ przeglądu — tylko do odczytu -->
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
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <!-- Przycisk główny: zapisz zmiany i wróć do szczegółów -->
                    <button type="submit" class="btn btn-v">
                        <?= !empty($durSpareParts) ? 'Aktualizuj raport' : 'Zapisz zmiany' ?>
                    </button>
                    <!-- Przycisk otwierający modal części -->
                    <button type="button" class="btn"
                        style="background:#0369a1;color:#fff;border-color:#0369a1;"
                        onclick="openPartsModal()">
                        🔧 <?= !empty($durSpareParts) ? 'Edytuj części' : 'Dodaj części' ?>
                    </button>
                    <a href="<?= BASE_URL ?>/index.php?route=dur_detail&id=<?= $review['id'] ?>" class="btn">Anuluj</a>
                </div>
            </form>
        </div>
    </div>
    <!-- ══ Koniec kolumny lewej ════════════════════════════════════ -->

    <!-- ══ Kolumna prawa: podgląd dodanych części zamiennych ══════ -->
    <div class="card" style="border-left:3px solid #0369a1;">
        <div class="card-head" style="background:#0a2463;border-bottom:1px solid #1e3a8a;">
            <span class="card-title" style="color:#fff;">🔧 Części zamienne</span>
            <?php if (!empty($durSpareParts)): ?>
                <?php $cnt = count($durSpareParts); ?>
                <span class="badge" style="background:#0369a1;color:#fff;">
                    <?= $cnt ?> <?= $cnt === 1 ? 'pozycja' : ($cnt < 5 ? 'pozycje' : 'pozycji') ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (!empty($durSpareParts)): ?>
                <!-- Tabela podglądu — tylko odczyt, edycja odbywa się w modalu -->
                <table style="width:100%;border-collapse:collapse;margin-bottom:12px;">
                    <thead>
                        <tr>
                            <th>Część</th>
                            <th style="width:50px;text-align:center;">Ilość</th>
                            <th>Kategoria</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($durSpareParts as $sp): ?>
                            <tr>
                                <td style="padding:5px 8px;"><?= Helpers::e($sp['part_name']) ?></td>
                                <td style="padding:5px 8px;text-align:center;"><?= (int)$sp['quantity'] ?></td>
                                <td style="padding:5px 8px;"><?= Helpers::catBadge($sp['category_name'], $sp['category_color']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="muted fs-sm" style="margin:0 0 12px;">Brak dodanych części zamiennych.</p>
            <?php endif; ?>

            <!-- Przycisk edycji części w karcie podglądu -->
            <button type="button" class="btn btn-sm"
                style="background:#0369a1;color:#fff;border-color:#0369a1;"
                onclick="openPartsModal()">
                🔧 <?= !empty($durSpareParts) ? 'Edytuj części zamienne' : 'Dodaj części zamienne' ?>
            </button>
        </div>
    </div>
    <!-- ══ Koniec kolumny prawej ═══════════════════════════════════ -->

</div>

<!-- ══════════════════════════════════════════════════════════════ -->
<!-- Modal: Zarządzanie częściami zamiennymi                        -->
<!-- ══════════════════════════════════════════════════════════════ -->
<style>
    .parts-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .50);
        z-index: 3000;
        align-items: center;
        justify-content: center;
    }

    .parts-modal-overlay.open {
        display: flex;
    }

    .parts-modal-box {
        background: #fff;
        border-radius: 12px;
        width: 100%;
        max-width: 720px;
        max-height: 85vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
        overflow: hidden;
    }

    .parts-modal-head {
        background: #0a2463;
        color: #fff;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-weight: 700;
        font-size: 15px;
        flex-shrink: 0;
    }

    .parts-modal-close {
        background: none;
        border: none;
        color: rgba(255, 255, 255, .75);
        font-size: 22px;
        cursor: pointer;
        line-height: 1;
        padding: 0;
        transition: color .15s;
    }

    .parts-modal-close:hover {
        color: #fff;
    }

    .parts-modal-body {
        padding: 20px;
        overflow-y: auto;
        flex: 1;
    }

    .parts-modal-foot {
        padding: 14px 20px;
        border-top: 1px solid #e5e7eb;
        background: #f8fafc;
        flex-shrink: 0;
        display: flex;
        justify-content: flex-end;
    }
</style>

<div class="parts-modal-overlay" id="partsModal" onclick="closePartsModalOutside(event)">
    <div class="parts-modal-box" role="dialog" aria-modal="true" aria-labelledby="partsModalTitle">

        <!-- Nagłówek modala -->
        <div class="parts-modal-head">
            <span id="partsModalTitle">🔧 Zarządzanie częściami zamiennymi</span>
            <button class="parts-modal-close" type="button" onclick="closePartsModal()" aria-label="Zamknij">×</button>
        </div>

        <!-- Treść modala -->
        <div class="parts-modal-body">

            <?php if (!empty($durSpareParts)): ?>
                <!-- Lista dodanych części z przyciskami usuwania -->
                <div style="margin-bottom:20px;">
                    <div class="fw6 fs-sm" style="margin-bottom:8px;color:#374151;">Dodane części:</div>
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr>
                                <th>Część</th>
                                <th style="width:55px;text-align:center;">Ilość</th>
                                <th>Kategoria</th>
                                <th style="width:60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($durSpareParts as $sp): ?>
                                <tr>
                                    <td style="padding:5px 8px;"><?= Helpers::e($sp['part_name']) ?></td>
                                    <td style="padding:5px 8px;text-align:center;"><?= (int)$sp['quantity'] ?></td>
                                    <td style="padding:5px 8px;"><?= Helpers::catBadge($sp['category_name'], $sp['category_color']) ?></td>
                                    <td style="padding:5px 8px;">
                                        <!-- Formularz usuwania — po akcji wraca do edycji z otwartym modalem -->
                                        <form method="POST"
                                            action="<?= BASE_URL ?>/index.php?route=dur_spare_part_delete"
                                            style="display:inline;"
                                            onsubmit="return confirm('Usunąć tę część?');">
                                            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
                                            <input type="hidden" name="spare_id" value="<?= $sp['id'] ?>">
                                            <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                            <button type="submit" class="btn btn-sm"
                                                style="border-color:#fca5a5;color:#dc2626;">Usuń</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="sep"></div>
            <?php endif; ?>

            <!-- Formularz dodawania nowej części -->
            <div style="margin-top:<?= empty($durSpareParts) ? '0' : '16px' ?>;">
                <div class="fw6 fs-sm" style="margin-bottom:10px;color:#374151;">Dodaj część zamienną:</div>
                <form method="POST" action="<?= BASE_URL ?>/index.php?route=dur_spare_part_add">
                    <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
                    <!-- Po dodaniu części wróć do edycji z otwartym modalem -->
                    <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                    <div class="fg">
                        <label class="flbl">Nazwa części <span class="req">*</span></label>
                        <input class="fc" name="part_name" placeholder="np. Uszczelka pompy" required>
                    </div>
                    <div class="g2" style="margin-top:8px;">
                        <div class="fg">
                            <label class="flbl">Ilość</label>
                            <input class="fc" type="number" name="quantity" value="1" min="1" required>
                        </div>
                        <div class="fg">
                            <label class="flbl">Kategoria <span class="req">*</span></label>
                            <select class="fc" name="category_id" required>
                                <option value="">— Wybierz —</option>
                                <?php foreach ($sparePartCategories as $spc): ?>
                                    <option value="<?= $spc['id'] ?>"><?= Helpers::e($spc['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top:12px;">
                        <button type="submit" class="btn btn-p">+ Dodaj część</button>
                    </div>
                </form>
            </div>

        </div>

        <!-- Stopka modala -->
        <div class="parts-modal-foot">
            <button type="button" class="btn btn-v" onclick="closePartsModal()">Gotowe — zamknij</button>
        </div>

    </div>
</div>

<script>
    // Otwiera modal części zamiennych
    function openPartsModal() {
        document.getElementById('partsModal').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    // Zamyka modal
    function closePartsModal() {
        document.getElementById('partsModal').classList.remove('open');
        document.body.style.overflow = '';
    }

    // Zamknij modal po kliknięciu tła (poza oknem modala)
    function closePartsModalOutside(e) {
        if (e.target === document.getElementById('partsModal')) {
            closePartsModal();
        }
    }

    // Zamknij modal klawiszem Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closePartsModal();
    });

    // Automatyczne otwarcie modala gdy w URL jest ?parts=1
    // (przekierowanie z dur_form z przyciskiem "Zapisz i dodaj części"
    //  lub powrót po dodaniu/usunięciu części)
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($openPartsModal): ?>
            openPartsModal();
        <?php endif; ?>
    });
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>

<?php
/*
 * ============================================================
 * DOKUMENTACJA PLIKU: dur_edit.php
 * ============================================================
 * Plik:         templates/shared/dur_edit.php
 * Opis:         Formularz edycji istniejącego raportu DUR.
 *               Układ dwukolumnowy: formularz edycji (lewa) +
 *               karta podglądu części zamiennych (prawa).
 *               Zarządzanie częściami odbywa się przez modal
 *               otwierany przyciskiem "Dodaj/Edytuj części".
 *               Modal otwiera się automatycznie gdy w URL jest ?parts=1.
 * Zależności:   DurController::editForm(), SparePartModel,
 *               SparePartCategoryModel, SettingsModel
 * Zmienne:      $review, $durSpareParts, $sparePartCategories,
 *               $typeLabels, $durStatusConfig, $openPartsModal
 * Uwagi:        Wszystkie formularze są osobnymi elementami <form>.
 *               Przycisk zmienia nazwę dynamicznie w zależności od
 *               tego czy części już istnieją.
 * ============================================================
 */
?>