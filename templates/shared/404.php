<?php use App\Helpers\Helpers; ?>
<!DOCTYPE html><html lang="pl"><head><meta charset="UTF-8"><title>404 — Moduł Serwis</title>
<style>body{font-family:Arial,sans-serif;background:#f0f2f7;min-height:100vh;display:flex;align-items:center;justify-content:center;}
.box{text-align:center;}.btn{display:inline-flex;padding:7px 15px;border-radius:7px;background:#0a2463;color:#fff;text-decoration:none;font-size:13px;font-weight:600;margin-top:16px;}</style>
</head><body>
<div class="box">
  <div style="font-size:4rem;margin-bottom:16px;">🔧</div>
  <h1 style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:8px;">Nie znaleziono strony</h1>
  <p style="color:#6b7280;margin-bottom:8px;">Zasób, którego szukasz, nie istnieje.</p>
  <?php if (\App\Helpers\Auth::check()): ?>
  <a href="<?= BASE_URL ?>/index.php?route=dashboard" class="btn">← Wróć na Pulpit</a>
  <?php else: ?>
  <a href="<?= BASE_URL ?>/index.php?route=report" class="btn">← Formularz zgłoszenia</a>
  <?php endif; ?>
</div>
</body></html>
