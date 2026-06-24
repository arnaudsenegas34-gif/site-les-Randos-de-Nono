/**
 * matos.js — Les Randos de Nono
 * Modal détail matériel, filtres dynamiques, animations.
 */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {

    const panelOverlay = document.getElementById('matos-panel-overlay');
    const panel        = document.getElementById('matos-panel');

    if (!panelOverlay || !panel) return;

    const btnClose   = document.getElementById('matos-panel-close');
    const cards      = document.querySelectorAll('.matos-card');
    const filterBtns = document.querySelectorAll('.matos-filter-btn');

    function lockScroll() {
      var sbw = window.innerWidth - document.documentElement.clientWidth;
      if (sbw > 0) document.body.style.paddingRight = sbw + 'px';
      document.body.style.overflow = 'hidden';
    }

    function unlockScroll() {
      document.body.style.overflow = '';
      document.body.style.paddingRight = '';
    }

    function openPanel(card) {
      const d = card.dataset;

      const imgEl = panel.querySelector('#matos-panel-img img');
      const imgWrap = panel.querySelector('#matos-panel-img');
      if (imgEl && imgWrap) {
        if (d.thumb) {
          imgEl.src = d.thumb;
          imgEl.style.display = '';
          imgWrap.style.background = '';
        } else {
          imgEl.style.display = 'none';
          imgWrap.style.background = 'linear-gradient(135deg, var(--vert), #3d7a4e)';
        }
      }

      _setText('matos-panel-cat',  d.catLabel || d.cat || '');
      _setText('matos-panel-name', d.name || '');
      _setText('matos-panel-desc', d.desc || '');

      const pourquoiEl = panel.querySelector('.matos-panel-pourquoi');
      const pourquoiText = panel.querySelector('.matos-panel-pourquoi p');
      if (pourquoiEl && pourquoiText) {
        if (d.pourquoi) {
          pourquoiEl.style.display = '';
          pourquoiText.textContent = d.pourquoi;
        } else {
          pourquoiEl.style.display = 'none';
        }
      }

      const linkEl = panel.querySelector('.matos-panel-link');
      if (linkEl) {
        if (d.lien) {
          linkEl.href = d.lien;
          linkEl.style.display = '';
        } else {
          linkEl.style.display = 'none';
        }
      }

      lockScroll();
      panel.scrollTop = 0;
      panelOverlay.classList.add('is-open');
      if (btnClose) btnClose.focus({ preventScroll: true });
    }

    function closePanel() {
      panelOverlay.classList.remove('is-open');
      unlockScroll();
    }

    cards.forEach(function (card) {
      card.addEventListener('click', function () { openPanel(card); });
      card.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openPanel(card); }
      });
    });

    if (btnClose) btnClose.addEventListener('click', closePanel);

    panelOverlay.addEventListener('click', function (e) {
      if (e.target === panelOverlay) closePanel();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && panelOverlay.classList.contains('is-open')) closePanel();
    });

    filterBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        filterBtns.forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');

        var filter = btn.dataset.filter;
        var delay = 0;

        cards.forEach(function (card) {
          var matches = filter === '*' || card.dataset.cat === filter;
          if (matches) {
            card.classList.remove('matos-hidden');
            card.classList.remove('is-visible');
            setTimeout(function () { card.classList.add('is-visible'); }, delay);
            delay += 30;
          } else {
            card.classList.add('matos-hidden');
          }
        });
      });
    });

    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!prefersReducedMotion) {
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });

      cards.forEach(function (card) { observer.observe(card); });
    } else {
      cards.forEach(function (card) { card.classList.add('is-visible'); });
    }

    function _setText(id, value) {
      var el = document.getElementById(id);
      if (el) el.textContent = value;
    }

  });

})();
