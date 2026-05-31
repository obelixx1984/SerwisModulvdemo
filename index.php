<?php

// ============================================================
// index.php — Front Controller z autoloaderem PSR-4
// ============================================================

declare(strict_types=1);

// Bazowa ścieżka projektu — katalog, w którym leży index.php
define('BASE_PATH', __DIR__);

// ── 1. Ładujemy klasę Autoloadera ────────────────────────────────────────────
// To jedyny ręczny require w całym projekcie.
// Autoloader.php musi być dostępny bez autoloadingu — dlatego ładujemy go wprost.
require BASE_PATH . '/app/Core/Autoloader.php';

// ── 2. Konfigurujemy autoloader PSR-4 ────────────────────────────────────────
$loader = new \App\Core\Autoloader();

// Mapowanie: przestrzeń 'App\' → katalog /app/
// PSR-4 zamieni np. 'App\Controllers\AuthController'
//   na: /app/Controllers/AuthController.php
$loader->addPrefix('App\\', BASE_PATH . '/app');

// Rejestrujemy autoloader w stosie PHP
$loader->register();

// ── 3. Konfiguracja aplikacji ─────────────────────────────────────────────────
// Pliki konfiguracyjne nie są klasami — pozostają jako zwykłe require
require BASE_PATH . '/config/database.php';
require BASE_PATH . '/config/app.php';

// ── 4. Start sesji i autentykacja ────────────────────────────────────────────
// Auth::start() załaduje się automatycznie przez autoloader (App\Helpers\Auth)
\App\Helpers\Auth::start();

// ── 5. Router ────────────────────────────────────────────────────────────────
// Domyślna trasa: formularz logowania
$route = $_GET['route'] ?? 'login';

$routes = [
    'login'               => ['App\\Controllers\\AuthController',    'loginForm'],
    'login_post'          => ['App\\Controllers\\AuthController',    'loginPost'],
    'logout'              => ['App\\Controllers\\AuthController',    'logout'],
    'report'              => ['App\\Controllers\\PublicController',  'reportForm'],
    'report_post'         => ['App\\Controllers\\PublicController',  'reportPost'],
    'line_history'        => ['App\\Controllers\\PublicController',  'lineHistory'],
    'dashboard'           => ['App\\Controllers\\FailureController', 'dashboard'],
    'failures'            => ['App\\Controllers\\FailureController', 'list'],
    'failure_detail'      => ['App\\Controllers\\FailureController', 'detail'],
    'status_change'       => ['App\\Controllers\\FailureController', 'changeStatus'],
    'set_category'        => ['App\\Controllers\\FailureController', 'setCategory'],
    'add_comment'         => ['App\\Controllers\\FailureController', 'addComment'],
    'add_observation_note' => ['App\\Controllers\\FailureController', 'addObservationNote'],
    'delete_observation_note' => ['App\\Controllers\\FailureController', 'deleteObservationNote'],
    'failure_delete'      => ['App\\Controllers\\FailureController', 'deleteFailure'],
    'dur_note_add'        => ['App\\Controllers\\DurController',     'scheduleNoteAdd'],
    'dur_note_edit'       => ['App\\Controllers\\DurController',     'scheduleNoteEdit'],
    'dur_note_delete'     => ['App\\Controllers\\DurController',     'scheduleNoteDelete'],
    'dur'                 => ['App\\Controllers\\DurController',     'list'],
    'dur_add'             => ['App\\Controllers\\DurController',     'addForm'],
    'dur_add_post'        => ['App\\Controllers\\DurController',     'addPost'],
    'dur_edit'            => ['App\\Controllers\\DurController',     'editForm'],
    'dur_edit_post'       => ['App\\Controllers\\DurController',     'editPost'],
    'change_password'     => ['App\\Controllers\\UserController',    'changePassword'],
    'my_failures'         => ['App\\Controllers\\UserController',    'myFailures'],
    'my_failure_edit'     => ['App\\Controllers\\UserController',    'myFailureEdit'],
    'assignment_add'      => ['App\\Controllers\\FailureController', 'addAssignment'],
    'assignment_remove'   => ['App\\Controllers\\FailureController', 'removeAssignment'],
    'my_repairs'          => ['App\\Controllers\\UserController',    'myRepairs'],
    'dur_detail'          => ['App\\Controllers\\DurController',     'detail'],
    'admin_users'         => ['App\\Controllers\\AdminController',   'users'],
    'admin_user_save'     => ['App\\Controllers\\AdminController',   'userSave'],
    'admin_lines'         => ['App\\Controllers\\AdminController',   'lines'],
    'admin_line_save'     => ['App\\Controllers\\AdminController',   'lineSave'],
    'admin_statuses'      => ['App\\Controllers\\AdminController',   'statuses'],
    'admin_status_save'   => ['App\\Controllers\\AdminController',   'statusSave'],
    'admin_dictionary'    => ['App\\Controllers\\AdminController',   'dictionary'],
    'admin_cat_save'      => ['App\\Controllers\\AdminController',   'categorySave'],
    'admin_dict_save'     => ['App\\Controllers\\AdminController',   'dictItemSave'],
    'admin_symptoms'      => ['App\\Controllers\\AdminController',   'symptoms'],
    'admin_symptom_save'  => ['App\\Controllers\\AdminController',   'symptomSave'],
    'admin_symptom_delete'=> ['App\\Controllers\\AdminController',   'symptomDelete'],
    'admin_dur_tmpl'      => ['App\\Controllers\\AdminController',   'durTemplates'],
    'admin_dur_types_save'=> ['App\\Controllers\\AdminController',   'durTypesSave'],
    'admin_dur_tmpl_save' => ['App\\Controllers\\AdminController',   'tmplSave'],
    'admin_dur_statuses_save' => ['App\\Controllers\\AdminController', 'durStatusesSave'],
    'admin_dur_sched'     => ['App\\Controllers\\AdminController',   'durSchedules'],
    'admin_settings'      => ['App\\Controllers\\AdminController',   'settings'],
    'admin_settings_save' => ['App\\Controllers\\AdminController',   'settingsSave'],
    'admin_role_save'     => ['App\\Controllers\\AdminController',   'roleSave'],
    'admin_role_add'      => ['App\\Controllers\\AdminController',   'roleAdd'],
    'admin_role_delete'   => ['App\\Controllers\\AdminController',   'roleDelete'],
    'admin_dict_delete'   => ['App\\Controllers\\AdminController',   'dictItemDelete'],
    'admin_tmpl_save'     => ['App\\Controllers\\AdminController',   'tmplSave'],
    'admin_sched_save'    => ['App\\Controllers\\AdminController',   'schedSave'],
    'check_duplicate'     => ['App\\Controllers\\AjaxController',    'checkDuplicate'],
    'photo_upload'        => ['App\\Controllers\\FailureController', 'photoUpload'],
    'photo_delete'        => ['App\\Controllers\\FailureController', 'photoDelete'],
    'photo_bridge_qr'     => ['App\\Controllers\\FailureController', 'photoBridgeQr'],
    'photo_check_new'     => ['App\\Controllers\\FailureController', 'photoCheckNew'],
    'admin_spare_parts'   => ['App\\Controllers\\AdminController',   'spareParts'],
    'admin_spc_cat_save'  => ['App\\Controllers\\AdminController',   'sparePartCatSave'],
    'admin_spc_cat_delete'=> ['App\\Controllers\\AdminController',   'sparePartCatDelete'],
    'spare_part_add'      => ['App\\Controllers\\FailureController', 'sparePartAdd'],
    'spare_part_delete'   => ['App\\Controllers\\FailureController', 'sparePartDelete'],
    'ajax_note_add'       => ['App\\Controllers\\AjaxController',    'noteAdd'],
    'ajax_note_edit'      => ['App\\Controllers\\AjaxController',    'noteEdit'],
    'ajax_note_delete'    => ['App\\Controllers\\AjaxController',    'noteDelete'],
    'ajax_notes_get'      => ['App\\Controllers\\AjaxController',    'notesGet'],
    'admin_user_delete'   => ['App\\Controllers\\AdminController',   'deleteUser'],
    'admin_line_delete'   => ['App\\Controllers\\AdminController',   'deleteLine'],
    'admin_status_delete' => ['App\\Controllers\\AdminController',   'deleteStatus'],
    'admin_tmpl_delete'   => ['App\\Controllers\\AdminController',   'deleteTmpl'],
    'admin_sched_delete'  => ['App\\Controllers\\AdminController',   'deleteSched'],
];

// ── 6. Dispatcher ─────────────────────────────────────────────────────────────
if (isset($routes[$route])) {
    [$class, $action] = $routes[$route];

    // Klasa zostanie załadowana automatycznie przez autoloader PSR-4
    (new $class())->$action();
} else {
    http_response_code(404);
    require BASE_PATH . '/templates/shared/404.php';
}
