/**
 * The Daily Pulse — Custom JS
 */
(function() {
  'use strict';

  // Orb elementlerini body'ye ekle
  function addOrbs() {
    const orbs = ['dp-orb dp-orb-1', 'dp-orb dp-orb-2', 'dp-orb dp-orb-3'];
    orbs.forEach(cls => {
      const orb = document.createElement('div');
      orb.className = cls;
      document.body.appendChild(orb);
    });
  }

  // Scroll reveal animasyonları
  function initReveal() {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.dp-reveal').forEach(el => observer.observe(el));
  }

  // Kategori pill toggle
  function initCategoryPills() {
    document.querySelectorAll('.dp-cat-pill').forEach(pill => {
      pill.addEventListener('click', function(e) {
        if (this.getAttribute('href') === '#') {
          e.preventDefault();
          document.querySelectorAll('.dp-cat-pill').forEach(p => p.classList.remove('active'));
          this.classList.add('active');
        }
      });
    });
  }

  // Email form submit handling
  function initForms() {
    document.querySelectorAll('.dp-subscribe-form').forEach(form => {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        const email = this.querySelector('input[type="email"]');
        const btn = this.querySelector('button');
        const originalText = btn.textContent;

        if (email && email.value) {
          btn.textContent = 'Gönderiliyor...';
          btn.disabled = true;

          // AJAX submit — FluentCRM veya özel endpoint
          const formData = new FormData();
          formData.append('action', 'dp_subscribe');
          formData.append('email', email.value);
          formData.append('nonce', (typeof dpAjax !== 'undefined') ? dpAjax.nonce : '');

          const nameInput = this.querySelector('input[name="name"]');
          if (nameInput) formData.append('name', nameInput.value);

          fetch((typeof dpAjax !== 'undefined') ? dpAjax.url : '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
          })
          .then(r => r.json())
          .then(data => {
            btn.textContent = 'Teşekkürler! ✓';
            btn.style.background = '#00e676';
            btn.style.color = '#030014';
            email.value = '';
            if (nameInput) nameInput.value = '';
          })
          .catch(() => {
            btn.textContent = 'Teşekkürler! ✓';
            btn.style.background = '#00e676';
            btn.style.color = '#030014';
            email.value = '';
          })
          .finally(() => {
            setTimeout(() => {
              btn.textContent = originalText;
              btn.style.background = '';
              btn.style.color = '';
              btn.disabled = false;
            }, 3000);
          });
        }
      });
    });
  }

  // Smooth scroll
  function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });
  }

  // Init
  document.addEventListener('DOMContentLoaded', function() {
    addOrbs();
    initReveal();
    initCategoryPills();
    initForms();
    initSmoothScroll();
  });

})();
