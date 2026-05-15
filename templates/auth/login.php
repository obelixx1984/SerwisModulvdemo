<?php

use App\Helpers\Auth;
use App\Helpers\Helpers;

if (Auth::check()) {
  Helpers::redirect('dashboard');
}
$flash = Helpers::getFlash();

// Czytaj nazwę i wersję systemu z bazy (tak samo jak header.php)
$appName    = 'Moduł Serwis';
$appVersion = '0.1-dev';
try {
  $db  = \App\Helpers\Database::get();
  $st  = $db->prepare("SELECT skey, svalue FROM settings WHERE skey IN ('app_name','app_version')");
  $st->execute();
  $cfg = $st->fetchAll(\PDO::FETCH_KEY_PAIR);
  if (!empty($cfg['app_name']))    $appName    = $cfg['app_name'];
  if (!empty($cfg['app_version'])) $appVersion = $cfg['app_version'];
} catch (\Throwable $e) { /* baza może jeszcze nie być gotowa */
}
?>
<!DOCTYPE html>
<html lang="pl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logowanie — <?= htmlspecialchars($appName) ?></title>
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: Arial, Helvetica, sans-serif;
      background: #f0f2f7;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-wrap {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 24px;
      width: 100%;
    }

    .login-card {
      background: #fff;
      border: 1px solid #dde1ec;
      border-radius: 12px;
      padding: 40px 44px;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 4px 24px rgba(10, 36, 99, .08);
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .login-logo-icon {
      width: 52px;
      height: 52px;
      background: #0a2463;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 12px;
    }

    .login-title {
      font-size: 16px;
      font-weight: 700;
      color: #1e293b;
      text-align: center;
      margin-bottom: 4px;
    }

    .login-sub {
      font-size: 13px;
      color: #9ca3af;
      text-align: center;
      margin-bottom: 20px;
    }

    .lbl {
      width: 100%;
      font-size: 12px;
      font-weight: 600;
      color: #6b7280;
      margin-bottom: 4px;
      display: block;
    }

    .lfc {
      width: 100%;
      padding: 10px 14px;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      font-size: 14px;
      color: #1e293b;
      background: #f9fafb;
      outline: none;
      font-family: Arial, Helvetica, sans-serif;
      margin-bottom: 10px;
    }

    .lfc:focus {
      border-color: #0a2463;
      background: #fff;
    }

    .login-btn {
      width: 100%;
      padding: 10px;
      background: #0a2463;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      font-family: Arial, Helvetica, sans-serif;
    }

    .login-btn:hover {
      background: #0d2d7a;
    }

    .alert-e {
      background: #fef2f2;
      border: 1px solid #fca5a5;
      color: #7f1d1d;
      border-radius: 7px;
      padding: 8px 12px;
      font-size: 13px;
      width: 100%;
      margin-bottom: 10px;
    }

    .alert-s {
      background: #ecfdf5;
      border: 1px solid #a7f3d0;
      color: #065f46;
      border-radius: 7px;
      padding: 8px 12px;
      font-size: 13px;
      width: 100%;
      margin-bottom: 10px;
    }
  </style>
</head>

<body>
  <div class="login-wrap">
    <div class="login-card">
      <div class="login-logo-icon">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
        </svg>
      </div>
      <div class="login-title"><?= htmlspecialchars($appName) ?></div>
      <div class="login-sub">System zarządzania awariami CMMS</div>

      <?php if ($flash): ?>
        <div class="<?= $flash['type'] === 'error' ? 'alert-e' : 'alert-s' ?>">
          <?= $flash['message'] ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>/index.php?route=login_post" style="width:100%;">
        <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
        <label class="lbl">Login</label>
        <input class="lfc" type="text" name="nickname" placeholder="Wpisz login"
          value="<?= Helpers::e($_POST['nickname'] ?? '') ?>" autocomplete="username" required autofocus>
        <label class="lbl">Hasło</label>
        <input class="lfc" type="password" name="password" placeholder="Wpisz hasło"
          autocomplete="current-password" required>
        <button type="submit" class="login-btn">Zaloguj się</button>
      </form>
    </div>
  </div>
</body>

</html>