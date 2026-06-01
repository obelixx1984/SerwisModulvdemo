<?php
// ============================================================
// install.php — Skrypt instalacyjny Moduł Serwis
// Po instalacji USUŃ ten plik z serwera!
// ============================================================
declare(strict_types=1);

define('BASE_PATH', __DIR__);
require BASE_PATH . '/config/database.php';

$errors   = [];
$messages = [];
$installed = false;
$pdo = null;

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    $messages[] = '&#10003; Połączono z bazą <strong>' . htmlspecialchars(DB_NAME) . '</strong>';
} catch (PDOException $e) {
    $errors[] = 'Błąd połączenia: ' . htmlspecialchars($e->getMessage());
}

// ── Parsuje SQL na tablicę instrukcji (ignoruje ; w stringach i komentarze) ──
function splitSql(string $sql): array
{
    $stmts = [];
    $buf   = '';
    $inStr = false;
    $strCh = '';
    $len   = strlen($sql);
    $i     = 0;

    while ($i < $len) {
        $ch = $sql[$i];

        // Komentarz liniowy -- (poza stringiem)
        if (!$inStr && $ch === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
            while ($i < $len && $sql[$i] !== "\n") {
                $i++;
            }
            continue;
        }

        // Komentarz blokowy /* */ (poza stringiem)
        if (!$inStr && $ch === '/' && $i + 1 < $len && $sql[$i + 1] === '*') {
            $i += 2;
            while ($i + 1 < $len && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                $i++;
            }
            $i += 2;
            continue;
        }

        // Wejście w string
        if (!$inStr && ($ch === "'" || $ch === '"' || $ch === '`')) {
            $inStr = true;
            $strCh = $ch;
            $buf  .= $ch;
            $i++;
            continue;
        }

        // Wyjście ze stringa (sprawdź escape)
        if ($inStr && $ch === $strCh) {
            $esc = 0;
            for ($j = $i - 1; $j >= 0 && $sql[$j] === '\\'; $j--) {
                $esc++;
            }
            $buf .= $ch;
            $i++;
            if ($esc % 2 === 0) {
                $inStr = false;
            }
            continue;
        }

        // Koniec instrukcji SQL
        if (!$inStr && $ch === ';') {
            $s   = trim($buf);
            $buf = '';
            if ($s !== '') {
                $stmts[] = $s;
            }
            $i++;
            continue;
        }

        $buf .= $ch;
        $i++;
    }

    $s = trim($buf);
    if ($s !== '') {
        $stmts[] = $s;
    }

    return $stmts;
}

// ── Obsługa formularza ────────────────────────────────────────────────────────
if ($pdo && isset($_POST['install'])) {
    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("SET NAMES utf8mb4");

        // ── KROK 1: Schemat bazy ──────────────────────────────────────────────
        $schemaFile = BASE_PATH . '/database/schema.sql';
        if (!file_exists($schemaFile)) {
            $errors[] = 'Brak pliku database/schema.sql';
        } else {
            foreach (splitSql(file_get_contents($schemaFile)) as $stmt) {
                try {
                    $pdo->exec($stmt);
                } catch (PDOException $e) {
                    // Ignorujemy błąd "already exists" — idempotentne działanie
                    if (stripos($e->getMessage(), 'already exists') === false) {
                        $errors[] = 'Schema: ' . htmlspecialchars($e->getMessage());
                    }
                }
            }
            if (empty($errors)) {
                $messages[] = '&#10003; Schemat bazy danych utworzony';
            }
        }

        // ── KROK 2: Role systemowe ────────────────────────────────────────────
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

        // ── KROK 3: Dane startowe seed.sql (bez sekcji users i roles) ────────
        $seedFile = BASE_PATH . '/database/seed.sql';
        if (file_exists($seedFile)) {
            $seed = file_get_contents($seedFile);
            // Wytnij bloki INSERT INTO users i roles (już wstawione powyżej)
            $seed = preg_replace('/INSERT\s+INTO\s+`?users`?[^;]+;/si', '', $seed);
            $seed = preg_replace('/INSERT\s+INTO\s+`?roles`?[^;]+;/si', '', $seed);

            foreach (splitSql($seed) as $stmt) {
                try {
                    $pdo->exec($stmt);
                } catch (PDOException $e) {
                    $msg = $e->getMessage();
                    // Tolerujemy duplikaty — seed może być uruchamiany wielokrotnie
                    if (
                        stripos($msg, 'already exists')  === false &&
                        stripos($msg, 'Duplicate entry') === false
                    ) {
                        $errors[] = 'Seed: ' . htmlspecialchars($msg);
                    }
                }
            }
            if (empty($errors)) {
                $messages[] = '&#10003; Dane startowe wczytane';
            }
        }

        // ── KROK 4: Edycja wybranych użytkowników z formularza ───────────────
        $editUsers = $_POST['edit_users'] ?? [];

        $stmtUins = $pdo->prepare("
            INSERT INTO `users`
                (role_id, name, login, email, password_hash, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
                role_id=VALUES(role_id),
                name=VALUES(name),
                email=VALUES(email),
                password_hash=VALUES(password_hash),
                is_active=1
        ");
        $stmtUupd = $pdo->prepare("
            UPDATE `users` SET role_id=?, name=?, email=?, is_active=1
            WHERE login=?
        ");

        foreach ($editUsers as $u) {
            $uLogin = strtolower(trim($u['login']    ?? ''));
            $uName  = trim($u['name']       ?? '');
            $uEmail = trim($u['email']      ?? '');
            $uRole  = (int)($u['role_id']   ?? 1);
            $uPass  = trim($u['password']   ?? '');

            if ($uLogin === '' || $uName === '') {
                continue;
            }

            // Sprawdź czy login już istnieje w bazie
            $chk = $pdo->prepare("SELECT id FROM users WHERE login=? LIMIT 1");
            $chk->execute([$uLogin]);
            $exists = $chk->fetchColumn();

            if ($uPass !== '') {
                // Nowe hasło — wstaw lub zaktualizuj z hashem
                $hash = password_hash($uPass, PASSWORD_BCRYPT, ['cost' => 10]);
                $stmtUins->execute([$uRole, $uName, $uLogin, $uEmail, $hash]);
                $messages[] = '&#10003; Konto <strong>' . htmlspecialchars($uLogin)
                    . '</strong> &nbsp;|&nbsp; hasło zapisane';
            } elseif ($exists) {
                // Edycja bez zmiany hasła — aktualizujemy tylko dane
                $stmtUupd->execute([$uRole, $uName, $uEmail, $uLogin]);
                $messages[] = '&#10003; Konto <strong>' . htmlspecialchars($uLogin)
                    . '</strong> zaktualizowane (hasło bez zmian)';
            }
            // Nowe konto bez hasła — pomijamy (nie możemy wstawić pustego hasha)
        }

        // ── KROK 5: Nowe konto (jeśli wypełniono formularz) ──────────────────
        $newLogin = strtolower(trim($_POST['new_user']['login']    ?? ''));
        $newName  = trim($_POST['new_user']['name']       ?? '');
        $newEmail = trim($_POST['new_user']['email']      ?? '');
        $newRole  = (int)($_POST['new_user']['role_id']   ?? 1);
        $newPass  = trim($_POST['new_user']['password']   ?? '');

        if ($newLogin !== '' && $newName !== '' && $newPass !== '') {
            $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 10]);
            $stmtNew = $pdo->prepare("
                INSERT INTO `users` (role_id, name, login, email, password_hash, is_active)
                VALUES (?, ?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE
                    role_id=VALUES(role_id), name=VALUES(name),
                    email=VALUES(email), password_hash=VALUES(password_hash), is_active=1
            ");
            $stmtNew->execute([$newRole, $newName, $newLogin, $newEmail, $hash]);
            $messages[] = '&#10003; Nowe konto <strong>' . htmlspecialchars($newLogin) . '</strong> utworzone';
        }

        // Przywróć sprawdzanie kluczy obcych
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        if (empty($errors)) {
            file_put_contents(BASE_PATH . '/.installed', date('Y-m-d H:i:s'));
            $installed = true;
        }

    } catch (PDOException $e) {
        $errors[] = 'Błąd krytyczny: ' . htmlspecialchars($e->getMessage());
        try {
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        } catch (Exception $x) {
            // ignoruj błąd przywracania FK
        }
    }
}

// URL do aplikacji (po instalacji)
$base   = rtrim(str_replace('/install.php', '', str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/install.php')), '/');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$appUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . $base . '/index.php';

// Pobierz listę użytkowników i ról do formularza (przed renderowaniem HTML)
$formRoles = [];
$formUsers = [];
if ($pdo) {
    try {
        $formRoles = $pdo->query("SELECT id, name, label FROM roles ORDER BY id")->fetchAll();
        $formUsers = $pdo->query("SELECT id, role_id, name, login, email FROM users ORDER BY id")->fetchAll();
    } catch (Exception $ex) {
        // Tabele mogą nie istnieć przed pierwszą instalacją — ignorujemy
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Instalacja — Moduł Serwis</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f0f2f7;
            min-height: 100vh;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 30px 20px;
        }

        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 36px 40px;
            width: 100%;
            max-width: 620px;
            box-shadow: 0 4px 20px rgba(0,0,0,.08);
        }

        /* ── Nagłówek ── */
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 18px;
            border-bottom: 1px solid #f3f4f6;
        }
        .logo-icon {
            width: 44px; height: 44px;
            background: #0a2463;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .title { font-size: 17px; font-weight: 700; color: #1e293b; }
        .sub   { font-size: 12px; color: #9ca3af; margin-top: 2px; }

        /* ── Komunikaty ── */
        .msg { padding: 8px 12px; border-radius: 6px; font-size: 13px; margin-bottom: 5px; line-height: 1.5; }
        .ok  { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .err { background: #fef2f2; border: 1px solid #fca5a5; color: #7f1d1d; }

        /* ── Sekcja ── */
        .box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 14px;
            font-size: 13px;
        }
        .box > strong { display: block; color: #1e293b; margin-bottom: 6px; }
        .dbinfo { color: #6b7280; font-size: 12px; }

        /* ── Formularze ── */
        label {
            display: block;
            font-size: 12px; font-weight: 600;
            color: #6b7280;
            margin: 10px 0 3px;
        }
        .field {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            font-family: Arial;
            background: #f9fafb;
            outline: none;
            transition: border-color .15s;
        }
        .field:focus { border-color: #0a2463; background: #fff; }

        /* Dropdown wyboru użytkownika */
        .user-select-row {
            display: flex;
            gap: 8px;
            align-items: flex-end;
            margin-bottom: 10px;
        }
        .user-select-row select { flex: 1; }
        .btn-select {
            padding: 8px 14px;
            background: #0a2463;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }
        .btn-select:hover { background: #0d2d7a; }

        /* Panel edycji użytkownika */
        .edit-panel {
            background: #fff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12px 14px;
            margin-top: 10px;
            display: none; /* ukryty domyślnie — pokazywany przez JS */
        }
        .edit-panel.visible { display: block; }

        /* Panel nowego konta */
        .new-user-panel {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 12px 14px;
            margin-top: 12px;
            display: none; /* ukryty domyślnie */
        }
        .new-user-panel.visible { display: block; }
        .new-user-panel .panel-title {
            font-size: 13px; font-weight: 700;
            color: #166534;
            margin-bottom: 10px;
        }

        .btn-add-new {
            display: inline-block;
            margin-top: 12px;
            padding: 8px 14px;
            background: #16a34a;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-add-new:hover { background: #15803d; }

        /* ── Przyciski główne ── */
        .btn {
            width: 100%;
            padding: 11px;
            background: #0a2463;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: Arial;
            margin-top: 16px;
        }
        .btn:hover { background: #0d2d7a; }

        /* ── Sukces ── */
        .success { text-align: center; padding: 8px 0; }
        .success h2 { font-size: 19px; color: #065f46; margin-bottom: 8px; }
        .btn-go {
            display: inline-block;
            margin-top: 14px;
            padding: 10px 24px;
            background: #0a2463;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }

        /* ── Ostrzeżenie ── */
        .warn {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            color: #78350f;
            padding: 10px 14px;
            border-radius: 7px;
            font-size: 12px;
            margin-top: 14px;
            line-height: 1.6;
        }
        code {
            background: #f3f4f6;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 12px;
        }

        /* Separator */
        .sep { border: none; border-top: 1px solid #e5e7eb; margin: 12px 0; }

        /* Tag roli obok loginu w dropdownie */
        .role-badge {
            display: inline-block;
            font-size: 10px;
            padding: 1px 5px;
            border-radius: 3px;
            background: #e0e7ff;
            color: #3730a3;
            margin-left: 4px;
        }
    </style>
</head>
<body>
<div class="card">

    <!-- ── Nagłówek ── -->
    <div class="logo">
        <div class="logo-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                 stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77
                         a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91
                         a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
            </svg>
        </div>
        <div>
            <div class="title">Moduł Serwis — Instalacja</div>
            <div class="sub">Konfiguracja bazy danych i kont użytkowników</div>
        </div>
    </div>

    <!-- ── Komunikaty ── -->
    <?php foreach ($errors as $e): ?>
        <div class="msg err">&#10007; <?= $e ?></div>
    <?php endforeach; ?>
    <?php foreach ($messages as $m): ?>
        <div class="msg ok"><?= $m ?></div>
    <?php endforeach; ?>

    <?php if ($installed): ?>
        <!-- ── Ekran sukcesu ── -->
        <div class="success" style="margin-top:16px;">
            <div style="font-size:3rem;margin-bottom:10px;">&#9989;</div>
            <h2>Instalacja zakończona!</h2>
            <p style="font-size:13px;color:#6b7280;margin-top:6px;">
                Baza i konta użytkowników są gotowe do pracy.
            </p>
            <a href="<?= htmlspecialchars($appUrl) ?>" class="btn-go">
                Przejdź do aplikacji &#8594;
            </a>
        </div>
        <div class="warn">
            &#9888; <strong>Usuń plik <code>install.php</code> z serwera</strong>
            — zawiera dane konfiguracyjne i nie powinien być publicznie dostępny.
        </div>

    <?php elseif (!$pdo): ?>
        <!-- ── Brak połączenia ── -->
        <div class="msg err">
            &#10007; Nie można połączyć się z bazą danych.<br>
            Otwórz plik <code>config/database.php</code> i sprawdź ustawienia
            DB_HOST, DB_NAME, DB_USER, DB_PASS.
        </div>

    <?php else: ?>
        <!-- ── Formularz instalacji ── -->

        <!-- Info o połączeniu -->
        <div class="box">
            <strong>Połączenie z bazą danych &#10003;</strong>
            <span class="dbinfo">
                Host: <b><?= htmlspecialchars(DB_HOST) ?></b> &nbsp;|&nbsp;
                Baza: <b><?= htmlspecialchars(DB_NAME) ?></b> &nbsp;|&nbsp;
                User: <b><?= htmlspecialchars(DB_USER) ?></b>
            </span>
        </div>

        <form method="POST" id="installForm">

            <!-- ── Sekcja użytkowników ── -->
            <div class="box">
                <strong>Zarządzanie kontami użytkowników</strong>

                <?php if (!empty($formUsers)): ?>
                    <!-- Dropdown wyboru istniejącego konta -->
                    <p style="font-size:12px;color:#6b7280;margin-bottom:8px;">
                        Wybierz konto z listy, aby je edytować (hasło zostaw puste = bez zmiany).
                    </p>

                    <div class="user-select-row">
                        <div style="flex:1;">
                            <label for="userDropdown" style="margin-top:0;">Wybierz użytkownika</label>
                            <select id="userDropdown" class="field">
                                <option value="">— wybierz konto —</option>
                                <?php
                                // Budujemy słownik ról do wyświetlenia w opcjach
                                $rolesById = [];
                                foreach ($formRoles as $r) {
                                    $rolesById[(int)$r['id']] = $r['label'];
                                }
                                foreach ($formUsers as $u):
                                    $roleLabel = $rolesById[(int)$u['role_id']] ?? '';
                                ?>
                                    <option value="<?= (int)$u['id'] ?>"
                                            data-login="<?= htmlspecialchars($u['login']) ?>"
                                            data-name="<?= htmlspecialchars($u['name']) ?>"
                                            data-email="<?= htmlspecialchars($u['email'] ?? '') ?>"
                                            data-role="<?= (int)$u['role_id'] ?>">
                                        <?= htmlspecialchars($u['login']) ?>
                                        (<?= htmlspecialchars($u['name']) ?>)
                                        — <?= htmlspecialchars($roleLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="button" class="btn-select" id="btnSelectUser">
                            Edytuj wybrane
                        </button>
                    </div>

                    <!-- Panel edycji wybranego użytkownika (ukryty domyślnie) -->
                    <div class="edit-panel" id="editPanel">
                        <div style="font-size:11px;color:#9ca3af;margin-bottom:8px;" id="editPanelTitle">
                            Edycja konta
                        </div>

                        <!-- Ukryte pole identyfikujące login (nie można go zmienić) -->
                        <input type="hidden" name="edit_users[0][login]" id="editLogin">

                        <label>Login <span style="font-weight:400;color:#9ca3af;">(nie można zmienić)</span></label>
                        <input type="text" id="editLoginDisplay" class="field"
                               disabled style="background:#f3f4f6;color:#9ca3af;">

                        <label>Imię i Nazwisko</label>
                        <input type="text" name="edit_users[0][name]" id="editName"
                               class="field" placeholder="Jan Kowalski">

                        <label>E-mail</label>
                        <input type="email" name="edit_users[0][email]" id="editEmail"
                               class="field" placeholder="jan@firma.pl">

                        <label>Rola</label>
                        <select name="edit_users[0][role_id]" id="editRole" class="field">
                            <?php foreach ($formRoles as $r): ?>
                                <option value="<?= (int)$r['id'] ?>">
                                    <?= htmlspecialchars($r['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Hasło <span style="font-weight:400;color:#9ca3af;">(puste = bez zmiany)</span></label>
                        <input type="password" name="edit_users[0][password]"
                               class="field" placeholder="Nowe hasło lub puste">
                    </div>

                    <hr class="sep">

                <?php else: ?>
                    <!-- Brak użytkowników w bazie — przed pierwszą instalacją -->
                    <p style="font-size:12px;color:#9ca3af;margin-bottom:8px;">
                        Baza nie zawiera jeszcze żadnych użytkowników. Dodaj pierwsze konto poniżej.
                    </p>
                <?php endif; ?>

                <!-- Przycisk otwierający formularz nowego konta -->
                <button type="button" class="btn-add-new" id="btnAddNew">
                    &#43; Dodaj nowe konto
                </button>

                <!-- Panel nowego konta (ukryty domyślnie) -->
                <div class="new-user-panel" id="newUserPanel">
                    <div class="panel-title">&#43; Nowe konto użytkownika</div>

                    <label>Login</label>
                    <input type="text" name="new_user[login]" class="field"
                           placeholder="np. jkowalski">

                    <label>Imię i Nazwisko</label>
                    <input type="text" name="new_user[name]" class="field"
                           placeholder="Jan Kowalski">

                    <label>E-mail</label>
                    <input type="email" name="new_user[email]" class="field"
                           placeholder="jan@firma.pl">

                    <label>Rola</label>
                    <select name="new_user[role_id]" class="field">
                        <?php foreach ($formRoles as $r): ?>
                            <option value="<?= (int)$r['id'] ?>">
                                <?= htmlspecialchars($r['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label>Hasło <span style="font-weight:400;color:#dc2626;">*wymagane</span></label>
                    <input type="password" name="new_user[password]" class="field"
                           placeholder="Hasło dla nowego konta">
                </div>
            </div>

            <input type="hidden" name="install" value="1">
            <button type="submit" class="btn">
                &#128640; Zapisz i uruchom instalację
            </button>
        </form>

        <div class="warn">
            &#9888; Skrypt zainstaluje schemat bazy i wstawi dane startowe.
            Po zakończeniu usuń <code>install.php</code> z serwera.
        </div>

    <?php endif; ?>

</div><!-- /.card -->

<script>
/**
 * Obsługa interaktywności formularza instalacyjnego:
 * - Dropdown użytkowników → wypełnia panel edycji
 * - Przycisk "Dodaj nowe konto" → pokazuje/chowa panel nowego konta
 */
(function () {
    'use strict';

    // Elementy DOM
    const dropdown    = document.getElementById('userDropdown');
    const btnSelect   = document.getElementById('btnSelectUser');
    const editPanel   = document.getElementById('editPanel');
    const editTitle   = document.getElementById('editPanelTitle');
    const editLogin   = document.getElementById('editLogin');        // hidden input
    const editLoginD  = document.getElementById('editLoginDisplay'); // disabled text
    const editName    = document.getElementById('editName');
    const editEmail   = document.getElementById('editEmail');
    const editRole    = document.getElementById('editRole');
    const btnAddNew   = document.getElementById('btnAddNew');
    const newPanel    = document.getElementById('newUserPanel');

    // Kliknięcie "Edytuj wybrane" → wypełnij panel edycji danymi z wybranej opcji
    if (btnSelect && dropdown) {
        btnSelect.addEventListener('click', function () {
            const opt = dropdown.options[dropdown.selectedIndex];

            if (!opt || opt.value === '') {
                // Nic nie wybrano — ukryj panel
                editPanel.classList.remove('visible');
                return;
            }

            // Pobierz dane z atrybutów data-* opcji
            const login = opt.dataset.login  || '';
            const name  = opt.dataset.name   || '';
            const email = opt.dataset.email  || '';
            const role  = opt.dataset.role   || '1';

            // Wypełnij formularz edycji
            editLogin.value        = login;
            editLoginD.value       = login;
            editName.value         = name;
            editEmail.value        = email;

            // Ustaw właściwą rolę w select
            if (editRole) {
                for (let i = 0; i < editRole.options.length; i++) {
                    editRole.options[i].selected = (editRole.options[i].value === role);
                }
            }

            editTitle.textContent  = 'Edycja konta: ' + login;
            editPanel.classList.add('visible');
        });
    }

    // Kliknięcie "Dodaj nowe konto" → pokaż/chowaj panel nowego konta
    if (btnAddNew && newPanel) {
        btnAddNew.addEventListener('click', function () {
            const isVisible = newPanel.classList.contains('visible');

            if (isVisible) {
                // Chowamy panel i czyścimy pola
                newPanel.classList.remove('visible');
                btnAddNew.textContent = '+ Dodaj nowe konto';
                newPanel.querySelectorAll('input').forEach(function (el) {
                    el.value = '';
                });
            } else {
                // Pokazujemy panel
                newPanel.classList.add('visible');
                btnAddNew.textContent = '✕ Anuluj dodawanie';
                // Przesuń widok do panelu
                newPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

}());
/*
 * ============================================================
 * DOKUMENTACJA PLIKU: install.php
 * ============================================================
 * Plik:         install.php
 * Opis:         Interaktywny instalator bazy danych Moduł Serwis.
 *               Tworzy schemat, wczytuje dane startowe (seed),
 *               umożliwia edycję kont z dropdownu i dodanie
 *               nowego konta przez ukryty panel.
 * Wersja:       2.0
 * Zależności:   config/database.php, database/schema.sql, database/seed.sql
 * Uwagi:        Usuń plik z serwera po zakończeniu instalacji!
 * ============================================================
 */
</script>
</body>
</html>
