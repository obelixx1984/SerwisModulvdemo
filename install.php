<?php
// ============================================================
// install.php — Skrypt instalacyjny Moduł Serwis
// Uruchom RAZ: http://localhost/cmms/install.php
// Po instalacji USUŃ ten plik!
// ============================================================
define('BASE_PATH', __DIR__);
require BASE_PATH . '/config/database.php';

$errors   = [];
$messages = [];
$installed = false;
$pdo = null;

try {
    $pdo = new PDO(
        'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $messages[] = '&#10003; Połączono z bazą <strong>'.htmlspecialchars(DB_NAME).'</strong>';
} catch (PDOException $e) {
    $errors[] = 'Błąd połączenia: '.htmlspecialchars($e->getMessage());
}

// Parsuje SQL na tablicę instrukcji, ignorując ; wewnątrz stringów i komentarze
function splitSql(string $sql): array {
    $stmts = [];
    $buf   = '';
    $inStr = false;
    $strCh = '';
    $len   = strlen($sql);
    $i     = 0;
    while ($i < $len) {
        $ch = $sql[$i];
        // Komentarz liniowy -- (tylko poza stringiem)
        if (!$inStr && $ch === '-' && $i+1 < $len && $sql[$i+1] === '-') {
            while ($i < $len && $sql[$i] !== "\n") $i++;
            continue;
        }
        // Komentarz blokowy /* */ (tylko poza stringiem)
        if (!$inStr && $ch === '/' && $i+1 < $len && $sql[$i+1] === '*') {
            $i += 2;
            while ($i+1 < $len && !($sql[$i] === '*' && $sql[$i+1] === '/')) $i++;
            $i += 2;
            continue;
        }
        // Wejście w string
        if (!$inStr && ($ch === "'" || $ch === '"' || $ch === '`')) {
            $inStr = true; $strCh = $ch; $buf .= $ch; $i++; continue;
        }
        // Wyjście ze stringa (sprawdź escape)
        if ($inStr && $ch === $strCh) {
            $esc = 0;
            for ($j = $i-1; $j >= 0 && $sql[$j] === '\\'; $j--) $esc++;
            $buf .= $ch; $i++;
            if ($esc % 2 === 0) $inStr = false;
            continue;
        }
        // Koniec instrukcji
        if (!$inStr && $ch === ';') {
            $s = trim($buf); $buf = '';
            if ($s !== '') $stmts[] = $s;
            $i++; continue;
        }
        $buf .= $ch; $i++;
    }
    $s = trim($buf);
    if ($s !== '') $stmts[] = $s;
    return $stmts;
}

if ($pdo && isset($_POST['install'])) {
    try {
        // Wyłącz FK checks na czas instalacji
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("SET NAMES utf8mb4");

        // ── KROK 1: Schemat bazy ──────────────────────────────
        $schemaFile = BASE_PATH . '/database/schema.sql';
        if (!file_exists($schemaFile)) {
            $errors[] = 'Brak pliku database/schema.sql';
        } else {
            foreach (splitSql(file_get_contents($schemaFile)) as $stmt) {
                try { $pdo->exec($stmt); }
                catch (PDOException $e) {
                    if (stripos($e->getMessage(), 'already exists') === false)
                        $errors[] = 'Schema: '.htmlspecialchars($e->getMessage());
                }
            }
            if (empty($errors)) $messages[] = '&#10003; Schemat bazy danych utworzony';
        }

        // ── KROK 2: Role (muszą istnieć przed users!) ────────
        $pdo->exec("INSERT IGNORE INTO `roles` (id, name, label) VALUES
            (1, 'admin',    'Administrator'),
            (2, 'mechanic', 'Mechanik'),
            (3, 'operator', 'Operator (tylko odczyt)')");
        // Wstaw domyślne uprawnienia ról
        $adminPerms    = '{"report":1,"dashboard":1,"failures":1,"dur":1,"statuses":1,"admin":1}';
        $mechanicPerms = '{"report":0,"dashboard":1,"failures":1,"dur":1,"statuses":1,"admin":0}';
        $operatorPerms = '{"report":1,"dashboard":0,"failures":0,"dur":1,"statuses":0,"admin":0}';
        $stmtPerms = $pdo->prepare(
            'INSERT INTO settings (skey, svalue, label) VALUES (?,?,?),(?,?,?),(?,?,?)
             ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)'
        );
        $stmtPerms->execute([
            'role_perms_admin',    $adminPerms,    'Uprawnienia roli: Administrator',
            'role_perms_mechanic', $mechanicPerms, 'Uprawnienia roli: Mechanik',
            'role_perms_operator', $operatorPerms, 'Uprawnienia roli: Operator',
        ]);
        $messages[] = '&#10003; Role systemowe wczytane';

        // ── KROK 3: Pozostałe dane seed (bez users) ───────────
        $seedFile = BASE_PATH . '/database/seed.sql';
        if (file_exists($seedFile)) {
            $seed = file_get_contents($seedFile);
            // Wytnij wszystkie INSERT INTO users
            $seed = preg_replace('/INSERT\s+INTO\s+`?users`?[^;]+;/si', '', $seed);
            // Wytnij INSERT INTO roles (już wstawione)
            $seed = preg_replace('/INSERT\s+INTO\s+`?roles`?[^;]+;/si', '', $seed);
            foreach (splitSql($seed) as $stmt) {
                try { $pdo->exec($stmt); }
                catch (PDOException $e) {
                    $msg = $e->getMessage();
                    if (stripos($msg, 'already exists') === false &&
                        stripos($msg, 'Duplicate entry') === false)
                        $errors[] = 'Seed: '.htmlspecialchars($msg);
                }
            }
            if (empty($errors)) $messages[] = '&#10003; Dane startowe wczytane';
        }

        // ── KROK 4: Użytkownicy z prawdziwymi hashami ─────────
        $users = [
            [1, 1, 'Administrator', 'admin',    'admin@serwis.local',
                trim($_POST['pass_admin']    ?? '') ?: 'password'],
            [2, 2, 'Jan Kowalski',  'mechanik', 'jan.kowalski@serwis.local',
                trim($_POST['pass_mechanik'] ?? '') ?: 'password'],
            [3, 2, 'Piotr Nowak',   'pnowak',   'piotr.nowak@serwis.local',
                trim($_POST['pass_pnowak']   ?? '') ?: 'password'],
        ];

        $stmtU = $pdo->prepare("
            INSERT INTO `users`
                (id, role_id, name, nickname, email, password_hash, is_active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                role_id=VALUES(role_id),
                name=VALUES(name),
                email=VALUES(email),
                password_hash=VALUES(password_hash),
                is_active=1
        ");

        foreach ($users as $u) {
            $pass = $u[5];
            $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 10]);
            $stmtU->execute([$u[0], $u[1], $u[2], $u[3], $u[4], $hash]);
            $messages[] = '&#10003; Konto <strong>'.htmlspecialchars($u[3])
                        . '</strong> &nbsp;|&nbsp; hasło: <code>'
                        . htmlspecialchars($pass).'</code>';
        }

        // Przywróć FK checks
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        if (empty($errors)) {
            file_put_contents(BASE_PATH . '/.installed', date('Y-m-d H:i:s'));
            $installed = true;
        }

    } catch (PDOException $e) {
        $errors[] = 'Błąd krytyczny: '.htmlspecialchars($e->getMessage());
        try { $pdo->exec("SET FOREIGN_KEY_CHECKS = 1"); } catch (Exception $x) {}
    }
}

// URL do aplikacji
$base   = rtrim(str_replace('/install.php','',str_replace('\\','/',$_SERVER['SCRIPT_NAME']??'/install.php')),'/' );
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$appUrl = $scheme.'://'.$_SERVER['HTTP_HOST'].$base.'/index.php';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Instalacja — Moduł Serwis</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Arial,Helvetica,sans-serif;background:#f0f2f7;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:36px 40px;width:100%;max-width:580px;box-shadow:0 4px 20px rgba(0,0,0,.08);}
.logo{display:flex;align-items:center;gap:12px;margin-bottom:24px;padding-bottom:18px;border-bottom:1px solid #f3f4f6;}
.logo-icon{width:44px;height:44px;background:#0a2463;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.title{font-size:17px;font-weight:700;color:#1e293b;}
.sub{font-size:12px;color:#9ca3af;margin-top:2px;}
.msg{padding:8px 12px;border-radius:6px;font-size:13px;margin-bottom:5px;line-height:1.5;}
.ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;}
.err{background:#fef2f2;border:1px solid #fca5a5;color:#7f1d1d;}
.box{background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px 16px;margin-bottom:14px;font-size:13px;}
.box strong{display:block;color:#1e293b;margin-bottom:6px;}
.dbinfo{color:#6b7280;font-size:12px;}
label{display:block;font-size:12px;font-weight:600;color:#6b7280;margin:10px 0 3px;}
input[type=password]{width:100%;padding:9px 11px;border:1px solid #d1d5db;border-radius:7px;font-size:13px;font-family:Arial;background:#f9fafb;outline:none;}
input[type=password]:focus{border-color:#0a2463;background:#fff;}
.btn{width:100%;padding:11px;background:#0a2463;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;font-family:Arial;margin-top:16px;}
.btn:hover{background:#0d2d7a;}
.success{text-align:center;padding:8px 0;}
.success h2{font-size:19px;color:#065f46;margin-bottom:8px;}
.btn-go{display:inline-block;margin-top:14px;padding:10px 24px;background:#0a2463;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;}
.warn{background:#fffbeb;border:1px solid #fcd34d;color:#78350f;padding:10px 14px;border-radius:7px;font-size:12px;margin-top:14px;line-height:1.6;}
code{background:#f3f4f6;padding:1px 5px;border-radius:3px;font-size:12px;}
</style>
</head>
<body>
<div class="card">

  <div class="logo">
    <div class="logo-icon">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
      </svg>
    </div>
    <div>
      <div class="title">Moduł Serwis — Instalacja</div>
      <div class="sub">Konfiguracja bazy danych i kont użytkowników</div>
    </div>
  </div>

  <?php foreach ($errors as $e): ?>
  <div class="msg err">&#10007; <?= $e ?></div>
  <?php endforeach; ?>

  <?php foreach ($messages as $m): ?>
  <div class="msg ok"><?= $m ?></div>
  <?php endforeach; ?>

  <?php if ($installed): ?>
    <div class="success" style="margin-top:16px;">
      <div style="font-size:3rem;margin-bottom:10px;">&#9989;</div>
      <h2>Instalacja zakończona!</h2>
      <p style="font-size:13px;color:#6b7280;margin-top:6px;">Baza i konta użytkowników są gotowe do pracy.</p>
      <a href="<?= htmlspecialchars($appUrl) ?>" class="btn-go">Przejdź do aplikacji &#8594;</a>
    </div>
    <div class="warn">
      &#9888; <strong>Usuń plik <code>install.php</code> z serwera</strong> — zawiera dane konfiguracyjne i nie powinien być publicznie dostępny.
    </div>

  <?php elseif (!$pdo): ?>
    <div class="msg err">
      &#10007; Nie można połączyć się z bazą danych.<br>
      Otwórz plik <code>config/database.php</code> i sprawdź ustawienia DB_HOST, DB_NAME, DB_USER, DB_PASS.
    </div>

  <?php else: ?>
    <div class="box">
      <strong>Połączenie z bazą danych &#10003;</strong>
      <span class="dbinfo">
        Host: <b><?= htmlspecialchars(DB_HOST) ?></b> &nbsp;|&nbsp;
        Baza: <b><?= htmlspecialchars(DB_NAME) ?></b> &nbsp;|&nbsp;
        User: <b><?= htmlspecialchars(DB_USER) ?></b>
      </span>
    </div>

    <form method="POST">
      <div class="box">
        <strong>Hasła kont startowych</strong>
        Zostaw puste, aby użyć domyślnego hasła <code>password</code>.<br>
        Hasła możesz zmienić po zalogowaniu w panelu Administratora.

        <label>Hasło dla konta <b>admin</b></label>
        <input type="password" name="pass_admin" placeholder="Pozostaw puste = 'password'">

        <label>Hasło dla konta <b>mechanik</b></label>
        <input type="password" name="pass_mechanik" placeholder="Pozostaw puste = 'password'">

        <label>Hasło dla konta <b>pnowak</b></label>
        <input type="password" name="pass_pnowak" placeholder="Pozostaw puste = 'password'">
      </div>

      <input type="hidden" name="install" value="1">
      <button type="submit" class="btn">&#128640; Uruchom instalację</button>
    </form>

    <div class="warn">
      &#9888; Skrypt zainstaluje schemat bazy i wstawi dane startowe. Po zakończeniu usuń <code>install.php</code> z serwera.
    </div>
  <?php endif; ?>

</div>
</body>
</html>
