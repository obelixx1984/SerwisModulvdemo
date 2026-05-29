<?php

use App\Helpers\Helpers;

$pageTitle = 'Części zamienne';
require BASE_PATH . '/templates/shared/header.php';
?>

<div class="atabs mb2">
    <a href="<?= BASE_URL ?>/index.php?route=admin_users" class="atab">Użytkownicy</a>
    <a href="<?= BASE_URL ?>/index.php?route=admin_lines" class="atab">Linie i podzespoły</a>
    <a href="<?= BASE_URL ?>/index.php?route=admin_statuses" class="atab">Statusy</a>
    <a href="<?= BASE_URL ?>/index.php?route=admin_dictionary" class="atab">Słownik awarii</a>
    <a href="<?= BASE_URL ?>/index.php?route=admin_symptoms" class="atab">Objawy awarii</a>
    <button class="atab active">Części zamienne</button>
    <a href="<?= BASE_URL ?>/index.php?route=admin_dur_tmpl" class="atab v">Szablony DUR</a>
    <a href="<?= BASE_URL ?>/index.php?route=admin_dur_sched" class="atab v">Harmonogram DUR</a>
    <a href="<?= BASE_URL ?>/index.php?route=admin_settings" class="atab">Ustawienia</a>
</div>

<!-- Sekcja kategorii (identyczna stylistycznie jak kategorie awarii w dictionary.php) -->
<div class="card mb2">
    <div class="card-head">
        <span class="card-title">Kategorie części zamiennych</span>
        <span class="muted fs-sm">Zarządzaj kategoriami i ich kolorami</span>
    </div>
    <div style="display:grid;grid-template-columns:1fr 380px;gap:16px;padding:14px 16px;align-items:start;">
        <!-- Tabela kategorii -->
        <div class="twrap">
            <table>
                <thead>
                    <tr>
                        <th>Kategoria</th>
                        <th>Kolor</th>
                        <th>Kolejność</th>
                        <th>Aktywna</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= Helpers::catBadge($cat['name'], $cat['color']) ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <span style="display:inline-block;width:16px;height:16px;border-radius:3px;background:<?= Helpers::e($cat['color']) ?>;"></span>
                                    <span class="muted fs-sm"><?= Helpers::e($cat['color']) ?></span>
                                </div>
                            </td>
                            <td class="muted fs-sm"><?= $cat['sort_order'] ?></td>
                            <td><?= Helpers::statusBadge($cat['is_active'] ? 'Tak' : 'Nie', $cat['is_active'] ? '#16a34a' : '#6b7280') ?></td>
                            <td>
                                <button class="btn btn-sm" onclick="editSpc(
              <?= $cat['id'] ?>,
              '<?= Helpers::e(addslashes($cat['name'])) ?>',
              '<?= Helpers::e($cat['color']) ?>',
              <?= $cat['sort_order'] ?>,
              <?= $cat['is_active'] ?>
            )">Edytuj</button>
                                <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_spc_cat_delete"
                                    style="display:inline;"
                                    onsubmit="return confirm('Usunąć kategorię? Upewnij się że nie ma przypisanych części.');">
                                    <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
                                    <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                                    <button type="submit" class="btn btn-sm" style="border-color:#fca5a5;color:#dc2626;">Usuń</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$categories): ?>
                        <tr>
                            <td colspan="5" class="muted" style="text-align:center;padding:16px;">Brak kategorii. Dodaj pierwszą →</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Formularz kategorii -->
        <div>
            <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_spc_cat_save">
                <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
                <input type="hidden" name="cat_id" id="spcCatId" value="0">
                <div class="fg">
                    <label class="flbl">Nazwa kategorii <span class="req">*</span></label>
                    <input class="fc" name="name" id="spcCatName" placeholder="np. Łożyska i wałki">
                </div>
                <div class="fg">
                    <label class="flbl">Kolor</label>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input type="color" name="color" id="spcKolor" value="#0891b2"
                            style="width:40px;height:34px;padding:2px;border:1px solid #e5e7eb;border-radius:6px;cursor:pointer;">
                        <span id="spcKolorPrev" class="badge" style="background:#0891b2;color:#fff;">Podgląd</span>
                    </div>
                </div>
                <div class="fg">
                    <label class="flbl">Kolejność</label>
                    <input class="fc" type="number" name="sort_order" id="spcCatOrder" value="0" style="width:80px;">
                </div>
                <div class="fg">
                    <label class="flbl">Aktywna</label>
                    <select class="fc" name="is_active" id="spcCatActive">
                        <option value="1">Tak</option>
                        <option value="0">Nie</option>
                    </select>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-p btn-sm">Zapisz kategorię</button>
                    <button type="button" class="btn btn-sm" onclick="resetSpcForm()">Nowa</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Lista wszystkich użytych części z filtrem -->
<div class="card">
    <div class="card-head">
        <span class="card-title">Lista użytych części zamiennych</span>
        <!-- Filtr po kategorii -->
        <form method="GET" action="<?= BASE_URL ?>/index.php" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="route" value="admin_spare_parts">
            <select name="cat_id" class="fc" style="min-width:180px;" onchange="this.form.submit()">
                <option value="">— Wszystkie kategorie —</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($filterCatId == $cat['id']) ? 'selected' : '' ?>>
                        <?= Helpers::e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="twrap">
        <table>
            <thead>
                <tr>
                    <th>Część</th>
                    <th>Ilość</th>
                    <th>Kategoria</th>
                    <th>Zgłoszenie</th>
                    <th>Dodał</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($spareParts as $sp): ?>
                    <tr>
                        <td class="fw6"><?= Helpers::e($sp['part_name']) ?></td>
                        <td><?= (int)$sp['quantity'] ?></td>
                        <td><?= Helpers::catBadge($sp['category_name'], $sp['category_color']) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/index.php?route=failure_detail&id=<?= $sp['failure_id'] ?>">
                                <?= Helpers::e($sp['ticket_number']) ?>
                            </a>
                        </td>
                        <td class="muted fs-sm"><?= Helpers::e($sp['added_by_name'] ?? '—') ?></td>
                        <td class="muted fs-sm"><?= date('d.m.Y H:i', strtotime($sp['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$spareParts): ?>
                    <tr>
                        <td colspan="6" class="muted" style="text-align:center;padding:16px;">Brak zapisanych części zamiennych.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    document.getElementById('spcKolor').addEventListener('input', function() {
        document.getElementById('spcKolorPrev').style.background = this.value;
        document.getElementById('spcKolorPrev').textContent = this.value;
    });

    function editSpc(id, name, color, order, active) {
        document.getElementById('spcCatId').value = id;
        document.getElementById('spcCatName').value = name;
        document.getElementById('spcKolor').value = color;
        document.getElementById('spcCatOrder').value = order;
        document.getElementById('spcCatActive').value = active;
        document.getElementById('spcKolorPrev').style.background = color;
        document.getElementById('spcKolorPrev').textContent = name;
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    function resetSpcForm() {
        document.getElementById('spcCatId').value = '0';
        document.getElementById('spcCatName').value = '';
        document.getElementById('spcKolor').value = '#0891b2';
        document.getElementById('spcCatOrder').value = '0';
        document.getElementById('spcCatActive').value = '1';
        document.getElementById('spcKolorPrev').style.background = '#0891b2';
        document.getElementById('spcKolorPrev').textContent = 'Podgląd';
    }
</script>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>