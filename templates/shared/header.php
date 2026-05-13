<?php
use App\Helpers\Auth;
use App\Helpers\Helpers;

$user  = Auth::check() ? Auth::user() : null;
$flash = Helpers::getFlash();
$route = $_GET['route'] ?? 'report';

$navPub = [
    ['label'=>'Zgłoś awarię',   'route'=>'report',      'icon'=>'M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM12 9v4M12 17h.01'],
    ['label'=>'Historia linii', 'route'=>'line_history', 'icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
    ['label'=>'Przeglądy DUR',  'route'=>'dur',          'icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
];
$navMech = [
    ['label'=>'Pulpit',         'route'=>'dashboard',    'icon'=>'M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z'],
    ['label'=>'Zgłoszenia',     'route'=>'failures',     'icon'=>'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'],
    ['label'=>'Historia linii', 'route'=>'line_history', 'icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
    ['label'=>'Przeglądy DUR',  'route'=>'dur',          'icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
];
$navAdmin = [
    ['label'=>'Pulpit',         'route'=>'dashboard',    'icon'=>'M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z'],
    ['label'=>'Zgłoszenia',     'route'=>'failures',     'icon'=>'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'],
    ['label'=>'Historia linii', 'route'=>'line_history', 'icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
    ['label'=>'Przeglądy DUR',  'route'=>'dur',          'icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
    ['sep'=>true],
    ['label'=>'Administrator',  'route'=>'admin_users',  'icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
];

// Pobierz ustawienia i uprawnienia z bazy
$appName    = 'Moduł Serwis';
$appVersion = '0.1-dev';
$userPerms  = [];
try {
    $smConn = \App\Helpers\Database::get();
    // Nazwa i wersja systemu
    $smRow = $smConn->prepare("SELECT skey, svalue FROM settings WHERE skey IN ('app_name','app_version')");
    $smRow->execute();
    $smSettings = $smRow->fetchAll(\PDO::FETCH_KEY_PAIR);
    if (!empty($smSettings['app_name'])) $appName = $smSettings['app_name'];
    if (!empty($smSettings['app_version'])) $appVersion = $smSettings['app_version'];
    // Uprawnienia użytkownika na podstawie roli
    if ($user) {
        $pRow = $smConn->prepare("SELECT svalue FROM settings WHERE skey=? LIMIT 1");
        $pRow->execute(['role_perms_'.$user['role']]);
        $pVal = $pRow->fetchColumn();
        if ($pVal) $userPerms = json_decode($pVal, true) ?? [];
    }
} catch (\Throwable $e) { /* baza może nie być gotowa */ }

// Domyślne uprawnienia gdy brak w bazie
if ($user && empty($userPerms)) {
    if ($user['role'] === 'admin')    $userPerms = ['report'=>1,'dashboard'=>1,'failures'=>1,'dur'=>1,'statuses'=>1,'admin'=>1];
    elseif ($user['role'] === 'mechanic') $userPerms = ['dashboard'=>1,'failures'=>1,'dur'=>1,'statuses'=>1];
    else $userPerms = ['report'=>1,'dur'=>1];
}

// Buduj nawigację dynamicznie na podstawie uprawnień
$navItems = $navPub;
if ($user) {
    $navItems = [];
    // Zawsze: zgłoś awarię dla tych co mogą
    if (!empty($userPerms['report']) && $user['role'] !== 'admin' && $user['role'] !== 'mechanic') {
        $navItems[] = $navPub[0]; // Zgłoś awarię
    }
    // Pulpit — dla mechanika i admina
    if (!empty($userPerms['dashboard'])) {
        $navItems[] = ['label'=>'Pulpit','route'=>'dashboard','icon'=>'M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z'];
    }
    // Zgłoszenia
    if (!empty($userPerms['failures'])) {
        $navItems[] = ['label'=>'Zgłoszenia','route'=>'failures','icon'=>'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01'];
    }
    // Historia linii — zawsze widoczna
    $navItems[] = ['label'=>'Historia linii','route'=>'line_history','icon'=>'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'];
    // DUR
    if (!empty($userPerms['dur'])) {
        $navItems[] = ['label'=>'Przeglądy DUR','route'=>'dur','icon'=>'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'];
    }
    // Administrator
    if (!empty($userPerms['admin'])) {
        $navItems[] = ['sep'=>true];
        $navItems[] = ['label'=>'Administrator','route'=>'admin_users','icon'=>'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'];
    }
    // Fallback: jeśli brak jakichkolwiek pozycji
    if (empty($navItems)) {
        $navItems = $navPub;
    }
}

$adminRoutes = ['admin_users','admin_employees','admin_lines','admin_statuses',
                'admin_dictionary','admin_dur_tmpl','admin_dur_sched','admin_settings'];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= Helpers::e($pageTitle ?? 'Moduł Serwis') ?></title>
<style>
/* ── Reset & Base ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html,body{height:100%;}
body{font-family:Arial,Helvetica,sans-serif;font-size:14px;background:#f0f2f7;color:#1e293b;line-height:1.55;}
a{color:#0a2463;text-decoration:none;}

/* ── Layout ── */
.app-shell{display:flex;min-height:100vh;}

/* ── Sidebar ── */
.sidebar{width:220px;min-width:220px;background:#0a2463;display:flex;flex-direction:column;padding:0;flex-shrink:0;position:fixed;top:0;left:0;height:100vh;overflow-y:auto;z-index:50;}
.sidebar-logo{padding:20px 20px 18px;color:#fff;font-size:15px;font-weight:700;border-bottom:1px solid rgba(255,255,255,.1);margin-bottom:6px;display:flex;align-items:center;gap:9px;flex-shrink:0;}
.sidebar-logo-icon{width:30px;height:30px;background:rgba(255,255,255,.12);border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.sidebar-logo-text{display:flex;flex-direction:column;line-height:1.3;}
.sidebar-logo-text small{font-size:10px;color:rgba(255,255,255,.4);font-weight:400;}
.nav-item{display:flex;align-items:center;gap:10px;padding:11px 20px;color:rgba(255,255,255,.65);font-size:14px;cursor:pointer;border-left:3px solid transparent;transition:background .12s;text-decoration:none;width:100%;}
.nav-item:hover{color:#fff;background:rgba(255,255,255,.07);}
.nav-item.active{color:#fff;background:rgba(255,255,255,.12);border-left-color:#5b9cf6;}
.nav-icon{width:18px;text-align:center;opacity:.75;flex-shrink:0;}
.nav-item.active .nav-icon{opacity:1;}
.nav-sep{height:1px;background:rgba(255,255,255,.08);margin:6px 20px;}

/* ── Main ── */
.main-wrap{margin-left:220px;width:calc(100% - 220px);min-width:0;display:flex;flex-direction:column;min-height:100vh;}

/* ── Topbar ── */
.topbar{background:#fff;border-bottom:1px solid #e5e7eb;padding:14px 24px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:40;}
.topbar-title{font-size:16px;font-weight:700;color:#1e293b;}
.topbar-right{display:flex;align-items:center;gap:12px;}
.topbar-user{font-size:13px;color:#6b7280;}
.role-chip{padding:3px 9px;border-radius:4px;font-size:11px;font-weight:700;}
.chip-admin{background:#e8eeff;color:#0a2463;border:1px solid #c7d2fe;}
.chip-mech{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;}
.chip-pub{background:#fef9c3;color:#713f12;border:1px solid #fde047;}
.btn-logout{background:#fff;color:#6b7280;border:1px solid #d1d5db;padding:6px 13px;border-radius:6px;font-size:13px;cursor:pointer;font-family:Arial,Helvetica,sans-serif;text-decoration:none;}
.btn-logout:hover{background:#f3f4f6;}

/* ── Page ── */
.page{padding:20px 24px;}

/* ── Cards ── */
.card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;}
.card-head{padding:12px 16px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;background:#fafafa;}
.card-title{font-size:14px;font-weight:700;color:#1e293b;}
.card-body{padding:14px 16px;}

/* ── Buttons ── */
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 15px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;border:1px solid #d1d5db;background:#fff;color:#374151;transition:background .12s;font-family:Arial,Helvetica,sans-serif;white-space:nowrap;text-decoration:none;}
.btn:hover{background:#f3f4f6;}
.btn-p{background:#0a2463;color:#fff;border-color:#0a2463;}
.btn-p:hover{background:#0d2d7a;}
.btn-g{background:#16a34a;color:#fff;border-color:#16a34a;}
.btn-v{background:#7c3aed;color:#fff;border-color:#7c3aed;}
.btn-v:hover{background:#6d28d9;}
.btn-sm{padding:5px 11px;font-size:12px;}
.btn-block{width:100%;justify-content:center;}

/* ── Forms ── */
.fg{margin-bottom:12px;}
.flbl{display:block;font-size:12px;font-weight:600;color:#6b7280;margin-bottom:4px;}
.req{color:#dc2626;}
.fc{display:block;width:100%;padding:8px 11px;border:1px solid #d1d5db;border-radius:7px;font-size:13px;color:#1e293b;background:#f9fafb;font-family:Arial,Helvetica,sans-serif;transition:border-color .12s;outline:none;}
.fc:focus{border-color:#0a2463;background:#fff;}
.fc:disabled{background:#f3f4f6;color:#9ca3af;cursor:not-allowed;}
textarea.fc{resize:vertical;min-height:72px;}
.fhint{font-size:11px;color:#9ca3af;margin-top:3px;}

/* ── Tables ── */
.twrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:13px;}
thead th{background:#0a2463;color:#fff;font-weight:600;padding:10px 12px;text-align:left;white-space:nowrap;border:none;}
td{padding:9px 12px;border-bottom:1px solid #f3f4f6;color:#1e293b;white-space:nowrap;vertical-align:middle;}
tbody tr:nth-child(even) td{background:#f9fafb;}
tbody tr:hover td{background:#eff2ff!important;}
tbody tr:last-child td{border-bottom:none;}

/* ── Badges ── */
.badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;white-space:nowrap;}
.bo{display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;border:1px solid;white-space:nowrap;}

/* ── KPI / Stats ── */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px;}
.stat-card{background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px 18px;}
.stat-val{font-size:26px;font-weight:700;line-height:1.1;color:#1e293b;}
.stat-lbl{font-size:12px;color:#6b7280;margin-top:4px;text-transform:uppercase;letter-spacing:.04em;}
.sv-r{color:#dc2626;}.sv-a{color:#d97706;}.sv-b{color:#0a2463;}.sv-g{color:#16a34a;}.sv-v{color:#7c3aed;}

/* ── Grid helpers ── */
.g2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;}
.g4{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;}

/* ── Alerts ── */
.alert{padding:9px 13px;border-radius:7px;margin-bottom:12px;border:1px solid transparent;font-size:13px;}
.alert-s{background:#ecfdf5;border-color:#a7f3d0;color:#065f46;}
.alert-e{background:#fef2f2;border-color:#fca5a5;color:#7f1d1d;}
.alert-w{background:#fffbeb;border-color:#fcd34d;color:#78350f;}
.alert-i{background:#eff6ff;border-color:#93c5fd;color:#1e3a8a;}
.alert-v{background:#faf5ff;border-color:#e9d5ff;color:#4c1d95;}

/* ── Timeline ── */
.tl{list-style:none;}
.tl-i{position:relative;padding:0 0 12px 20px;border-left:2px solid #e5e7eb;}
.tl-i:last-child{border-color:transparent;padding-bottom:0;}
.tl-dot{position:absolute;left:-5px;top:4px;width:8px;height:8px;border-radius:50%;background:#fff;border:2px solid #0a2463;}
.tl-dot.g{border-color:#16a34a;}.tl-dot.a{border-color:#d97706;}.tl-dot.v{border-color:#7c3aed;}
.tl-time{font-size:11px;color:#9ca3af;}
.tl-txt{font-size:13px;margin-top:2px;}

/* ── DUR cards ── */
.dur-card{border-left:3px solid #7c3aed;background:#fff;border-radius:0 8px 8px 0;border-top:1px solid #e5e7eb;border-right:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb;padding:11px 14px;margin-bottom:10px;}
.dur-title{font-size:13px;font-weight:700;}
.dur-meta{font-size:12px;color:#6b7280;margin-top:2px;}
.dur-item{display:flex;gap:5px;align-items:flex-start;padding:2px 0;font-size:13px;}
.ck{color:#16a34a;flex-shrink:0;}
.dur-next{margin-top:8px;padding-top:8px;border-top:1px solid #f3f4f6;font-size:12px;display:flex;align-items:center;gap:6px;}

/* ── Public form layout ── */
.pub-layout{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.pub-header{text-align:center;margin-bottom:10px;}
.pub-icon{width:44px;height:44px;background:#0a2463;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:6px;}
.pub-title{font-size:18px;font-weight:700;}
.pub-sub{font-size:13px;color:#6b7280;}
.pub-btn{width:100%;background:#0a2463;color:#fff;border:none;border-radius:7px;padding:10px;font-size:14px;font-weight:600;cursor:pointer;font-family:Arial,Helvetica,sans-serif;margin-top:6px;}
.pub-btn:hover{background:#0d2d7a;}
.dup-warn{background:#fffbeb;border:1px solid #fcd34d;border-radius:7px;padding:8px 11px;font-size:13px;color:#78350f;margin-bottom:10px;display:none;}

/* ── DUR Notice ── */
#durNotice{display:none;position:fixed;top:0;left:220px;right:0;z-index:999;background:#b91c1c;color:#fff;padding:14px 24px;font-size:15px;font-weight:700;text-align:center;letter-spacing:.01em;box-shadow:0 3px 12px rgba(185,28,28,.4);pointer-events:none;}

/* ── Line history selector ── */
.line-sel-bar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 16px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;margin-bottom:16px;}
.line-sel-bar label{font-size:13px;font-weight:600;color:#374151;white-space:nowrap;}

/* ── Section header ── */
.sh{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.sh-title{font-size:16px;font-weight:700;color:#1e293b;}

/* ── DUR upcoming ── */
.dur-up-item{display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid #e9d5ff;font-size:13px;}
.dur-up-item:last-child{border-bottom:none;}

/* ── Admin tabs ── */
.atabs{display:flex;gap:4px;flex-wrap:wrap;margin-bottom:16px;border-bottom:2px solid #e5e7eb;}
.atab{padding:8px 14px;font-size:13px;font-weight:600;border:none;border-bottom:2px solid transparent;background:none;color:#6b7280;cursor:pointer;font-family:Arial,Helvetica,sans-serif;margin-bottom:-2px;text-decoration:none;display:inline-block;}
.atab:hover{color:#0a2463;}
.atab.active{color:#0a2463;border-bottom-color:#0a2463;}
.atab.v{color:#7c3aed;}
.atab.v.active{border-bottom-color:#7c3aed;}

/* ── Preview box ── */
.preview-box{background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;padding:12px 16px;}
.preview-box .lbl{font-size:11px;font-weight:700;color:#065f46;margin-bottom:8px;letter-spacing:.04em;}

/* ── Misc ── */
.sep{border:none;border-top:1px solid #e5e7eb;margin:12px 0;}
.mb1{margin-bottom:6px;}.mb2{margin-bottom:12px;}.mb3{margin-bottom:18px;}
.mt1{margin-top:6px;}.mt2{margin-top:12px;}
.muted{color:#6b7280;}.fs-sm{font-size:12px;}.fw6{font-weight:600;}.mono{font-family:monospace;}
.dot{width:8px;height:8px;border-radius:50%;display:inline-block;flex-shrink:0;}

/* ── Login (osobna strona, nie używa sidebar) ── */
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f0f2f7;padding:24px;}
.login-card{background:#fff;border:1px solid #dde1ec;border-radius:12px;padding:40px 44px;width:100%;max-width:400px;box-shadow:0 4px 24px rgba(10,36,99,.08);display:flex;flex-direction:column;align-items:center;}
.login-logo-icon{width:52px;height:52px;background:#0a2463;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:12px;}
.login-title{font-size:16px;font-weight:700;color:#1e293b;text-align:center;margin-bottom:4px;}
.login-sub{font-size:13px;color:#9ca3af;text-align:center;margin-bottom:20px;}
.login-lbl{width:100%;font-size:12px;font-weight:600;color:#6b7280;margin-bottom:4px;display:block;}
.lfc{width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;color:#1e293b;background:#f9fafb;outline:none;font-family:Arial,Helvetica,sans-serif;margin-bottom:10px;}
.lfc:focus{border-color:#0a2463;background:#fff;}
.login-btn{width:100%;padding:10px;background:#0a2463;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;font-family:Arial,Helvetica,sans-serif;}
.login-btn:hover{background:#0d2d7a;}

/* ── Responsive ── */
@media(max-width:900px){.pub-layout{grid-template-columns:1fr;}.stats{grid-template-columns:repeat(2,1fr);}.g2{grid-template-columns:1fr;}#durNotice{left:0;}}
@media(max-width:700px){.sidebar{width:52px;min-width:52px;}.main-wrap{margin-left:52px;width:calc(100% - 52px);}.sidebar-logo,.nav-item .nav-label{display:none;}.nav-item{justify-content:center;padding:14px;}#durNotice{left:52px;}}
</style>
</head>
<body>

<div id="durNotice">⚠&nbsp;&nbsp;Pamiętaj!!! Poinformuj dział DUR o zgłoszeniu.</div>

<div class="app-shell">
<nav class="sidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-icon">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
    </div>
    <div class="sidebar-logo-text"><?= htmlspecialchars($appName) ?><small><?= htmlspecialchars($appVersion) ?></small></div>
  </div>

  <?php foreach ($navItems as $item): ?>
  <?php if (!empty($item['sep'])): ?>
    <div class="nav-sep"></div>
  <?php else:
    $active = $route === $item['route']
           || (in_array($route, $adminRoutes) && $item['route'] === 'admin_users');
  ?>
    <a href="<?= BASE_URL ?>/index.php?route=<?= $item['route'] ?>"
       class="nav-item<?= $active ? ' active' : '' ?>">
      <span class="nav-icon">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="<?= $item['icon'] ?>"/></svg>
      </span>
      <span class="nav-label"><?= Helpers::e($item['label']) ?></span>
    </a>
  <?php endif; ?>
  <?php endforeach; ?>

  <?php if (!$user): ?>
  <div style="margin-top:auto;padding:14px 16px;border-top:1px solid rgba(255,255,255,.08);">
    <a href="<?= BASE_URL ?>/index.php?route=login" style="display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.5);font-size:12px;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
      <span class="nav-label">Zaloguj się</span>
    </a>
  </div>
  <?php endif; ?>
</nav>

<div class="main-wrap">
  <div class="topbar">
    <div class="topbar-title"><?= Helpers::e($pageTitle ?? 'Moduł Serwis') ?></div>
    <div class="topbar-right">
      <?php if ($user): ?>
        <span class="topbar-user"><?= Helpers::e($user['name']) ?></span>
        <a href="<?= BASE_URL ?>/index.php?route=logout" class="btn-logout">Wyloguj</a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/index.php?route=login" class="btn-logout">Zaloguj się</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="page">

<?php if ($flash): ?>
<?php if ($flash['type'] === 'success_dur'): ?>
<script>window.SHOW_DUR_NOTICE=true;</script>
<div class="alert alert-s mb2">✓ <?= $flash['message'] ?></div>
<?php elseif ($flash['type'] === 'success'): ?>
<div class="alert alert-s mb2">✓ <?= $flash['message'] ?></div>
<?php elseif ($flash['type'] === 'error'): ?>
<div class="alert alert-e mb2">✗ <?= $flash['message'] ?></div>
<?php elseif ($flash['type'] === 'warning'): ?>
<div class="alert alert-w mb2">⚠ <?= $flash['message'] ?></div>
<?php endif; ?>
<?php endif; ?>
