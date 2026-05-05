/**
 * Kampanya.website — Custom JS
 * Email autocomplete + abone ol / abonelikten çık formu
 */
(function () {
  'use strict';

  /* -------------------------------------------------- *
   *  YARDIMCI FONKSİYONLAR
   * -------------------------------------------------- */
  function $(sel, ctx) { return (ctx || document).querySelector(sel); }
  function $$(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }

  /* -------------------------------------------------- *
   *  E-POSTA ALAN ADLARI (autocomplete için)
   * -------------------------------------------------- */
  var EMAIL_DOMAINS = [
    'gmail.com',
    'hotmail.com',
    'outlook.com',
    'yahoo.com',
    'icloud.com',
    'yandex.com',
    'hotmail.com.tr',
    'yahoo.com.tr',
    'mynet.com',
    'superonline.com',
    'turk.net',
    'windowslive.com',
    'live.com',
    'live.com.tr',
    'googlemail.com',
  ];

  /* -------------------------------------------------- *
   *  E-POSTA OTOMATİK TAMAMLAMA
   * -------------------------------------------------- */
  function initEmailAutocomplete() {
    var input = document.getElementById('k-email');
    var hint  = document.getElementById('k-autocomplete');
    if (!input || !hint) return;

    var currentSuggestion = '';

    function getSuggestion(val) {
      if (!val) return '';
      var atIdx = val.indexOf('@');
      if (atIdx === -1) return ''; // @ henüz girilmemiş

      var typed  = val.slice(atIdx + 1).toLowerCase();
      if (typed.length === 0) return EMAIL_DOMAINS[0]; // @ sonrası boş → ilk domain

      for (var i = 0; i < EMAIL_DOMAINS.length; i++) {
        if (EMAIL_DOMAINS[i].indexOf(typed) === 0 && EMAIL_DOMAINS[i] !== typed) {
          return EMAIL_DOMAINS[i];
        }
      }
      return '';
    }

    function renderHint(val, domain) {
      if (!domain) {
        hint.textContent = '';
        hint.innerHTML   = '';
        return;
      }
      var atIdx  = val.indexOf('@');
      var before = val.slice(0, atIdx + 1);         // "ali@"
      var typed  = val.slice(atIdx + 1);             // "gm"
      var rest   = domain.slice(typed.length);       // "ail.com"

      // Hint katmanını oluştur: görünmez typed kısım + gri rest kısım
      hint.innerHTML =
        '<span class="k-autocomplete__typed">' + escHtml(before + typed) + '</span>' +
        '<span class="k-autocomplete__suggest">' + escHtml(rest) + '</span>';
    }

    function escHtml(s) {
      return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function acceptSuggestion() {
      if (!currentSuggestion) return false;
      var val   = input.value;
      var atIdx = val.indexOf('@');
      if (atIdx === -1) return false;
      input.value       = val.slice(0, atIdx + 1) + currentSuggestion;
      currentSuggestion = '';
      hint.innerHTML    = '';
      return true;
    }

    input.addEventListener('input', function () {
      var domain       = getSuggestion(input.value);
      currentSuggestion = domain;
      renderHint(input.value, domain);
    });

    input.addEventListener('keydown', function (e) {
      if ((e.key === 'Tab' || e.key === 'ArrowRight') && currentSuggestion) {
        e.preventDefault();
        acceptSuggestion();
      }
    });

    // Mobil: hint'e dokunma ile tamamla
    hint.addEventListener('click', function () { acceptSuggestion(); input.focus(); });
    hint.style.cursor = 'text';
  }

  /* -------------------------------------------------- *
   *  ABONE OL FORMU
   * -------------------------------------------------- */
  function initSubscribeForm() {
    var form = document.getElementById('k-subscribe-form');
    if (!form) return;

    var emailEl  = document.getElementById('k-email');
    var kvkkEl   = document.getElementById('k-kvkk');
    var btnEl    = document.getElementById('k-subscribe-btn');
    var btnText  = form.querySelector('.k-btn-text');
    var btnLoad  = form.querySelector('.k-btn-loading');
    var msgEl    = document.getElementById('k-form-msg');

    function showMsg(type, text) {
      msgEl.className = 'k-form-msg k-msg-' + type;
      msgEl.textContent = text;
      msgEl.removeAttribute('hidden');
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var email = emailEl.value.trim();
      if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showMsg('error', 'Lütfen geçerli bir e-posta adresi girin.');
        emailEl.focus();
        return;
      }
      if (kvkkEl && !kvkkEl.checked) {
        showMsg('error', 'Devam etmek için KVKK ve Açık Rıza Metni\'ni onaylamanız gerekiyor.');
        return;
      }

      // Yükleniyor durumu
      btnEl.disabled    = true;
      btnText.hidden    = true;
      btnLoad.hidden    = false;
      msgEl.hidden      = true;

      fetch('/wp-json/kampanya/v1/subscribe', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ email: email }),
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          showMsg('success', data.message || 'Abone oldunuz! 🎉');
          emailEl.value = '';
          document.getElementById('k-autocomplete').innerHTML = '';
          // Butonu başarı rengine çevir
          btnEl.style.background = '#16a34a';
          btnText.textContent    = '✓';
          btnText.hidden         = false;
          btnLoad.hidden         = true;
        } else {
          showMsg('error', (data.message || data.data && data.data.message) || 'Bir hata oluştu.');
          btnEl.disabled  = false;
          btnText.hidden  = false;
          btnLoad.hidden  = true;
        }
      })
      .catch(function () {
        showMsg('error', 'Bağlantı hatası. Lütfen tekrar deneyin.');
        btnEl.disabled  = false;
        btnText.hidden  = false;
        btnLoad.hidden  = true;
      });
    });
  }

  /* -------------------------------------------------- *
   *  ABONELİKTEN ÇIK FORMU
   * -------------------------------------------------- */
  function initUnsubscribeForm() {
    var form = document.getElementById('k-unsub-form');
    if (!form) return;

    var emailEl = form.querySelector('input[type="email"]');
    var btnEl   = form.querySelector('button[type="submit"]');
    var msgEl   = document.getElementById('k-unsub-msg');

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var email = emailEl.value.trim();
      if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        msgEl.className   = 'k-unsub-msg error';
        msgEl.textContent = 'Lütfen geçerli bir e-posta adresi girin.';
        msgEl.hidden      = false;
        return;
      }

      btnEl.disabled    = true;
      btnEl.textContent = '…';
      msgEl.hidden      = true;

      fetch('/wp-json/kampanya/v1/unsubscribe', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ email: email }),
      })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (data.success) {
          msgEl.className   = 'k-unsub-msg success';
          msgEl.textContent = data.message || 'Aboneliğiniz iptal edildi.';
          emailEl.value     = '';
        } else {
          msgEl.className   = 'k-unsub-msg error';
          msgEl.textContent = (data.message || data.data && data.data.message) || 'Bir hata oluştu.';
          btnEl.disabled    = false;
          btnEl.textContent = 'Aboneliği İptal Et';
        }
        msgEl.hidden = false;
      })
      .catch(function () {
        msgEl.className   = 'k-unsub-msg error';
        msgEl.textContent = 'Bağlantı hatası. Lütfen tekrar deneyin.';
        msgEl.hidden      = false;
        btnEl.disabled    = false;
        btnEl.textContent = 'Aboneliği İptal Et';
      });
    });
  }

  /* -------------------------------------------------- *
   *  SCROLL REVEAL
   * -------------------------------------------------- */
  function initReveal() {
    if (!window.IntersectionObserver) return;
    var obs = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          obs.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });
    $$('.dp-reveal').forEach(function (el) { obs.observe(el); });
  }

  /* -------------------------------------------------- *
   *  SMOOTH SCROLL
   * -------------------------------------------------- */
  function initSmoothScroll() {
    $$('a[href^="#"]').forEach(function (a) {
      a.addEventListener('click', function (e) {
        var target = document.querySelector(this.getAttribute('href'));
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });
  }

  /* -------------------------------------------------- *
   *  NAV BAR SEARCH — Inject magnifier into main nav
   * -------------------------------------------------- */
  function initNavSearch() {
    var mainNavWrap = document.querySelector('[data-row="middle"] [data-column="end"] [data-items="primary"]');
    if (!mainNavWrap || mainNavWrap.querySelector('.k-nav-search-btn')) return;

    // Build a plain button — no Blocksy classes.
    // On click we programmatically click Blocksy's OWN toggle that was bound
    // at init time, so its event handlers fire exactly as if the user clicked it.
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'k-nav-search-btn';
    btn.setAttribute('aria-label', 'Ara');
    btn.innerHTML = '<svg viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" width="18" height="18"><path d="M14.8 13.7 12 11a6.8 6.8 0 1 0-1 1l2.8 2.8a.7.7 0 0 0 1-.1ZM1.5 6.8a5.3 5.3 0 1 1 5.3 5.2 5.3 5.3 0 0 1-5.3-5.2Z"/></svg>';

    mainNavWrap.appendChild(btn);

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      // Delegate to Blocksy's already-bound search toggle in the top row
      var orig = document.querySelector('.ct-header-search[data-toggle-panel="#search-modal"]');
      if (orig) {
        orig.click();
      } else {
        // Fallback: manually toggle modal classes if original not found
        var modal = document.getElementById('search-modal');
        if (!modal) return;
        var isOpen = !modal.hasAttribute('inert');
        if (isOpen) {
          modal.setAttribute('inert', '');
          modal.classList.remove('ct-active');
          document.documentElement.classList.remove('ct-panel-open');
        } else {
          document.querySelectorAll('.ct-panel:not([inert])').forEach(function(p) {
            p.setAttribute('inert', ''); p.classList.remove('ct-active');
          });
          modal.removeAttribute('inert');
          modal.classList.add('ct-active');
          document.documentElement.classList.add('ct-panel-open');
        }
      }
      // Focus the search input after modal opens
      setTimeout(function () {
        var modal = document.getElementById('search-modal');
        var inp = modal && modal.querySelector('input[type="search"]');
        if (inp && !modal.hasAttribute('inert')) inp.focus();
      }, 150);
    });
  }

  /* -------------------------------------------------- *
   *  PAGE-LEVEL TURKISH TRANSLATIONS
   *  Blocksy outputs some headings/text directly in PHP
   *  templates that bypass gettext — fix client-side.
   * -------------------------------------------------- */
  function initPageTranslations() {
    var body = document.body;
    var cls  = body.className;

    // ── Search results page ──────────────────────────
    if (cls.indexOf('search') !== -1 && cls.indexOf('search-results') !== -1) {
      var h1 = document.querySelector('.page-title, h1.entry-title');
      if (h1) {
        var txt = h1.textContent || h1.innerText || '';
        // Match "Search Results for X" or "Search Results for: X"
        var m = txt.match(/^Search Results for:?\s*(.+)$/i);
        if (m) {
          h1.innerHTML = '“<span>' + m[1].trim() + '</span>” için arama sonuçları';
        } else if (/^Search Results/i.test(txt)) {
          h1.textContent = 'Arama Sonuçları';
        }
      }
    }

    // ── 404 page ─────────────────────────────────────
    if (cls.indexOf('error404') !== -1) {
      // h1
      var h404 = document.querySelector('.page-title, h1.entry-title, .page-header .page-title');
      if (h404) {
        var t = h404.textContent || '';
        if (/oops/i.test(t) || /can.t be found/i.test(t) || /not found/i.test(t)) {
          h404.textContent = 'Ups! Sayfa Bulunamadı';
        }
      }
      // Description / paragraph text
      document.querySelectorAll('.page-description, .entry-content p, .error-404 p').forEach(function(p) {
        var pt = p.innerHTML;
        pt = pt.replace(/Oops! That page can[''`]?t be found\./gi, 'Ups! Bu sayfa bulunamadı.');
        pt = pt.replace(/It looks like nothing was found at this location\./gi, 'Bu konumda hiçbir şey bulunamadı.');
        pt = pt.replace(/Maybe try to search for something else\?/gi, 'Başka bir şey aramayı deneyin.');
        pt = pt.replace(/It seems we can[''`]?t find what you[''`]?re looking for\./gi, 'Aradığınız içerik bulunamadı.');
        pt = pt.replace(/Perhaps searching can help\./gi, 'Belki arama yaparak bulabilirsiniz.');
        p.innerHTML = pt;
      });
    }
  }

  /* -------------------------------------------------- *
   *  BAŞLAT
   * -------------------------------------------------- */
  document.addEventListener('DOMContentLoaded', function () {
    initEmailAutocomplete();
    initSubscribeForm();
    initUnsubscribeForm();
    initReveal();
    initSmoothScroll();
    initNavSearch();
    initPageTranslations();
  });

})();
