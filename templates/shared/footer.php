  </div><!-- /.page -->
  </div><!-- /.main-wrap -->
  </div><!-- /.app-shell -->
  <script>
    // DUR Notice
    if (window.SHOW_DUR_NOTICE) {
      var n = document.getElementById('durNotice');
      if (n) {
        n.style.display = 'block';
        setTimeout(function() {
          n.style.display = 'none';
        }, 5000);
      }
    }
    // Podgląd koloru statusu
    var nc = document.getElementById('nsColor'),
      np = document.getElementById('nsPreview');
    if (nc && np) {
      nc.addEventListener('input', function() {
        np.style.background = this.value;
        np.textContent = this.value;
      });
    }
    // Podgląd koloru kategorii
    var kk = document.getElementById('katKolor'),
      kp = document.getElementById('katKolorPrev');
    if (kk && kp) {
      kk.addEventListener('input', function() {
        kp.style.background = this.value;
      });
    }
    // Filtr słownika po kategorii
    var pubCat = document.getElementById('pubCat'),
      pubDict = document.getElementById('pubDict');
    if (pubCat && pubDict) {
      pubCat.addEventListener('change', function() {
        var cat = this.value;
        pubDict.querySelectorAll('option[data-cat]').forEach(function(o) {
          o.hidden = cat ? o.dataset.cat !== cat : false;
        });
        pubDict.value = '';
        var dw = document.getElementById('dupWarn');
        if (dw) dw.style.display = 'none';
      });
    }
  </script>
  <!-- ══ Modal: Zmiana hasła ════════════════════════════════ -->
  <?php if (isset($user) && $user): ?>
    <div class="modal-overlay" id="passModal" onclick="closePassModalOutside(event)">
      <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="passModalTitle">
        <div class="modal-head">
          <span id="passModalTitle">🔑 Zmiana hasła</span>
          <button class="modal-close" onclick="closePassModal()" type="button" aria-label="Zamknij">×</button>
        </div>
        <div class="modal-body">
          <?php
          // Przekaż bieżącą trasę do powrotu po zmianie hasła
          $currentRoute = $_GET['route'] ?? 'dashboard';
          ?>
          <form method="POST" action="<?= BASE_URL ?>/index.php?route=change_password" id="passForm">
            <input type="hidden" name="csrf_token" value="<?= \App\Helpers\Auth::csrfToken() ?>">
            <input type="hidden" name="return_route" value="<?= htmlspecialchars($currentRoute) ?>">

            <div class="fg">
              <label class="flbl">Obecne hasło <span class="req">*</span></label>
              <input type="password" name="current_password" class="fc"
                placeholder="Wpisz aktualne hasło"
                autocomplete="current-password" required>
            </div>
            <div class="fg">
              <label class="flbl">Nowe hasło <span class="req">*</span></label>
              <input type="password" name="new_password" class="fc" id="newPassInput"
                placeholder="Min. 6 znaków"
                autocomplete="new-password" required minlength="6">
            </div>
            <div class="fg">
              <label class="flbl">Powtórz nowe hasło <span class="req">*</span></label>
              <input type="password" name="confirm_password" class="fc" id="confirmPassInput"
                placeholder="Wpisz nowe hasło ponownie"
                autocomplete="new-password" required minlength="6">
              <div class="fhint" id="passMatchHint" style="display:none;color:#dc2626;">
                ✗ Hasła nie są identyczne
              </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:4px;">
              <button type="submit" class="btn btn-p" style="flex:1;" id="passSaveBtn">Zmień hasło</button>
              <button type="button" class="btn" onclick="closePassModal()">Anuluj</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <script>
    // ── Dropdown użytkownika ──────────────────────────────────
    function toggleUserMenu(e) {
      e.stopPropagation();
      var btn = document.getElementById('userMenuBtn');
      var dd = document.getElementById('userDropdown');
      if (!btn || !dd) return;
      var open = dd.classList.contains('open');
      if (open) {
        dd.classList.remove('open');
        btn.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
      } else {
        dd.classList.add('open');
        btn.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
      }
    }
    document.addEventListener('click', function(e) {
      var dd = document.getElementById('userDropdown');
      var btn = document.getElementById('userMenuBtn');
      if (!dd || !btn) return;
      if (!btn.contains(e.target) && !dd.contains(e.target)) {
        dd.classList.remove('open');
        btn.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
      }
    });
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        var dd = document.getElementById('userDropdown');
        var btn = document.getElementById('userMenuBtn');
        if (dd) dd.classList.remove('open');
        if (btn) {
          btn.classList.remove('open');
          btn.setAttribute('aria-expanded', 'false');
        }
      }
    });

    // ── Modal zmiany hasła ────────────────────────────────────
    function openPassModal() {
      var m = document.getElementById('passModal');
      if (!m) return;
      // Zamknij dropdown
      var dd = document.getElementById('userDropdown');
      var btn = document.getElementById('userMenuBtn');
      if (dd) dd.classList.remove('open');
      if (btn) {
        btn.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
      }
      // Otwórz modal
      m.classList.add('open');
      document.body.style.overflow = 'hidden';
      setTimeout(function() {
        var f = m.querySelector('input[name=current_password]');
        if (f) f.focus();
      }, 60);
    }

    function closePassModal() {
      var m = document.getElementById('passModal');
      if (!m) return;
      m.classList.remove('open');
      document.body.style.overflow = '';
      var fm = document.getElementById('passForm');
      if (fm) fm.reset();
      var hint = document.getElementById('passMatchHint');
      if (hint) hint.style.display = 'none';
    }

    function closePassModalOutside(e) {
      if (e.target === document.getElementById('passModal')) closePassModal();
    }

    // Walidacja zgodności haseł
    (function() {
      var np = document.getElementById('newPassInput');
      var cp = document.getElementById('confirmPassInput');
      var hint = document.getElementById('passMatchHint');
      var btn = document.getElementById('passSaveBtn');
      if (!np || !cp) return;

      function check() {
        if (cp.value && np.value !== cp.value) {
          if (hint) hint.style.display = '';
          if (btn) btn.disabled = true;
        } else {
          if (hint) hint.style.display = 'none';
          if (btn) btn.disabled = false;
        }
      }
      np.addEventListener('input', check);
      cp.addEventListener('input', check);
    })();
  </script>
  </body>

  </html>