<?php
// BASE_PATH jest definiowane w index.php - nie definiujemy tutaj ponownie

(function () {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $base   = rtrim(str_replace('/index.php', '', $script), '/');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $scheme . '://' . $host . $base);
})();

define('APP_NAME',         'Moduł Serwis');
define('APP_DEBUG',        true);
define('APP_TIMEZONE',     'Europe/Warsaw');
define('SESSION_NAME',     'modul_serwis_sess');
define('SESSION_LIFETIME', 7200);
define('RECORDS_PER_PAGE', 25);
define('DUR_WARNING_DAYS', 7);

date_default_timezone_set(APP_TIMEZONE);

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}
