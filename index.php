<?php
// Moduł Serwis — Front Controller

define('BASE_PATH', __DIR__);

require BASE_PATH . '/config/database.php';
require BASE_PATH . '/config/app.php';

spl_autoload_register(function (string $class): void {
    static $map = null;
    if ($map === null) {
        $ctrl = BASE_PATH . '/app/Controllers/Controllers.php';
        $mdl  = BASE_PATH . '/app/Models/Models.php';
        $map  = [
            'App\\Helpers\\Database'            => BASE_PATH . '/app/Helpers/Database.php',
            'App\\Helpers\\Auth'                => BASE_PATH . '/app/Helpers/Auth.php',
            'App\\Helpers\\Helpers'             => BASE_PATH . '/app/Helpers/Helpers.php',
            'App\\Models\\BaseModel'            => $mdl,
            'App\\Models\\UserModel'            => $mdl,
            'App\\Models\\EmployeeModel'        => $mdl,
            'App\\Models\\ProductionLineModel'  => $mdl,
            'App\\Models\\CategoryModel'        => $mdl,
            'App\\Models\\DictionaryModel'      => $mdl,
            'App\\Models\\SymptomModel'         => $mdl,
            'App\\Models\\StatusModel'          => $mdl,
            'App\\Models\\FailureModel'         => $mdl,
            'App\\Models\\MaintenanceModel'     => $mdl,
            'App\\Models\\SettingsModel'        => $mdl,
            'App\\Models\\RoleModel'            => $mdl,
            'App\\Models\\ScheduleNoteModel'    => $mdl,
            'App\\Models\\AssignmentModel'      => $mdl,
            'App\\Models\\PhotoTokenModel'      => $mdl,
            'App\\Models\\BridgeModel'          => $mdl,
            'App\\Models\\SparePartCategoryModel' => $mdl,
            'App\\Models\\SparePartModel'         => $mdl,
            'App\\Controllers\\AuthController'    => $ctrl,
            'App\\Controllers\\PublicController'  => $ctrl,
            'App\\Controllers\\FailureController' => $ctrl,
            'App\\Controllers\\DurController'     => $ctrl,
            'App\\Controllers\\AdminController'   => $ctrl,
            'App\\Controllers\\AjaxController'    => $ctrl,
            'App\\Controllers\\UserController'    => $ctrl,
        ];
    }
    if (isset($map[$class]) && !class_exists($class, false)) {
        require_once $map[$class];
    }
});

\App\Helpers\Auth::start();

// Domyślna trasa: logowanie
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
    'failure_delete'      => ['App\\Controllers\\FailureController', 'deleteFailure'],
    'dur_note_add'        => ['App\\Controllers\\DurController', 'scheduleNoteAdd'],
    'dur_note_edit'       => ['App\\Controllers\\DurController', 'scheduleNoteEdit'],
    'dur_note_delete'     => ['App\\Controllers\\DurController', 'scheduleNoteDelete'],
    'dur'                 => ['App\\Controllers\\DurController',     'list'],
    'dur_add'             => ['App\\Controllers\\DurController',     'addForm'],
    'dur_add_post'        => ['App\\Controllers\\DurController',     'addPost'],
    'dur_edit'            => ['App\\Controllers\\DurController',     'editForm'],
    'dur_edit_post'       => ['App\\Controllers\\DurController',     'editPost'],
    // ── Nowe trasy — użytkownik ──────────────────────────────
    'change_password'     => ['App\\Controllers\\UserController',   'changePassword'],
    'my_failures'         => ['App\\Controllers\\UserController',   'myFailures'],
    'my_failure_edit'     => ['App\\Controllers\\UserController',   'myFailureEdit'],
    // ─────────────────────────────────────────────────────────
    // Obsada zgłoszenia
    'assignment_add'    => ['App\\Controllers\\FailureController', 'addAssignment'],
    'assignment_remove' => ['App\\Controllers\\FailureController', 'removeAssignment'],

    // Moje naprawy
    'my_repairs'        => ['App\\Controllers\\UserController',   'myRepairs'],
    // ─────────────────────────────────────────────────────────
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
    'admin_symptom_delete' => ['App\\Controllers\\AdminController',   'symptomDelete'],
    'admin_dur_tmpl'       => ['App\\Controllers\\AdminController',   'durTemplates'],
    'admin_dur_types_save' => ['App\\Controllers\\AdminController',  'durTypesSave'],
    'admin_dur_tmpl_save'  => ['App\\Controllers\\AdminController', 'tmplSave'],
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

    // ── Zdjęcia ──────────────────────────────────────────
    'photo_upload'        => ['App\\Controllers\\FailureController', 'photoUpload'],
    'photo_delete'        => ['App\\Controllers\\FailureController', 'photoDelete'],

    'photo_bridge_qr'  => ['App\\Controllers\\FailureController', 'photoBridgeQr'],
    'photo_check_new'  => ['App\\Controllers\\FailureController', 'photoCheckNew'],

    // ── Części zamienne ──────────────────────────────────────────
    'admin_spare_parts'         => ['App\\Controllers\\AdminController', 'spareParts'],
    'admin_spc_cat_save'        => ['App\\Controllers\\AdminController', 'sparePartCatSave'],
    'admin_spc_cat_delete'      => ['App\\Controllers\\AdminController', 'sparePartCatDelete'],
    'spare_part_add'            => ['App\\Controllers\\FailureController', 'sparePartAdd'],
    'spare_part_delete'         => ['App\\Controllers\\FailureController', 'sparePartDelete'],

    'ajax_note_add'    => ['App\\Controllers\\AjaxController', 'noteAdd'],
    'ajax_note_edit'   => ['App\\Controllers\\AjaxController', 'noteEdit'],
    'ajax_note_delete' => ['App\\Controllers\\AjaxController', 'noteDelete'],
    'ajax_notes_get'   => ['App\\Controllers\\AjaxController', 'notesGet'],

    'admin_user_delete'   => ['App\\Controllers\\AdminController',   'deleteUser'],
    'admin_line_delete'   => ['App\\Controllers\\AdminController',   'deleteLine'],
    'admin_status_delete' => ['App\\Controllers\\AdminController',   'deleteStatus'],
    'admin_tmpl_delete'   => ['App\\Controllers\\AdminController',   'deleteTmpl'],
    'admin_sched_delete'  => ['App\\Controllers\\AdminController',   'deleteSched'],
];

if (isset($routes[$route])) {
    [$class, $action] = $routes[$route];
    (new $class())->$action();
} else {
    http_response_code(404);
    require BASE_PATH . '/templates/shared/404.php';
}
