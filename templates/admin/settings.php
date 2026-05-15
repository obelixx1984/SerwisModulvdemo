<?php
// templates/admin/settings.php
// ZMIANA: label "Rekordów na stronę" → "Ilość rekordów na stronie z historią linii" + opis

use App\Helpers\Helpers;
$pageTitle = 'Ustawienia systemu';
require BASE_PATH . '/templates/shared/header.php';
?>

<div class="atabs mb2">
  <a href="<?= BASE_URL ?>/index.php?route=admin_users"      class="atab">Użytkownicy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_lines"      class="atab">Linie i podzespoły</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_statuses"   class="atab">Statusy</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dictionary" class="atab">Słownik awarii</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_symptoms"   class="atab">Objawy awarii</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dur_tmpl"   class="atab v">Szablony DUR</a>
  <a href="<?= BASE_URL ?>/index.php?route=admin_dur_sched"  class="atab v">Harmonogram DUR</a>
  <button class="atab active" data-tab="settings">Ustawienia</button>
</div>

<div class="card" style="max-width:560px;">
  <div class="card-head"><span class="card-title">Konfiguracja systemu</span></div>
  <div class="card-body">
    <form method="POST" action="<?= BASE_URL ?>/index.php?route=admin_settings_save">
      <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">

      <div class="fg">
        <label class="flbl">Nazwa systemu</label>
        <input class="fc" name="app_name" value="<?= Helpers::e($settings['app_name']['svalue'] ?? 'Moduł Serwis') ?>">
        <span class="fhint">Wyświetlana w sidebarze i tytule okna przeglądarki</span>
      </div>

      <div class="fg">
        <label class="flbl">Wersja systemu</label>
        <input class="fc" name="app_version" value="<?= Helpers::e($settings['app_version']['svalue'] ?? '0.1-dev') ?>" placeholder="np. 1.0.0">
        <span class="fhint">Wyświetlana pod nazwą w sidebarze</span>
      </div>

      <div class="fg">
        <label class="flbl">Nazwa firmy</label>
        <input class="fc" name="company_name" value="<?= Helpers::e($settings['company_name']['svalue'] ?? '') ?>">
      </div>

      <?php /* ZMIANA: nowy label i hint dla records_per_page */ ?>
      <div class="fg">
        <label class="flbl">Ilość rekordów na stronie z historią linii</label>
        <input class="fc" type="number" name="records_per_page" min="5" max="200" style="width:100px;"
               value="<?= Helpers::e($settings['records_per_page']['svalue'] ?? '25') ?>">
        <span class="fhint">
          Maksymalna liczba zgłoszeń widocznych na jednej stronie w historii linii
          (<a href="<?= BASE_URL ?>/index.php?route=line_history" style="color:#0a2463;">Historia linii</a>).
          Po przekroczeniu tej liczby pojawi się paginacja. Zakres: 5–200.
        </span>
      </div>

      <div class="fg">
        <label class="flbl">Ostrzeżenie DUR (dni przed terminem)</label>
        <input class="fc" type="number" name="dur_warning_days" min="1" max="60" style="width:100px;"
               value="<?= Helpers::e($settings['dur_warning_days']['svalue'] ?? '7') ?>">
        <span class="fhint">Harmonogram DUR z terminem w ciągu tych dni będzie widoczny na Pulpicie</span>
      </div>

      <div class="sep"></div>
      <button type="submit" class="btn btn-p">Zapisz ustawienia</button>
    </form>
  </div>
</div>

<?php require BASE_PATH . '/templates/shared/footer.php'; ?>
