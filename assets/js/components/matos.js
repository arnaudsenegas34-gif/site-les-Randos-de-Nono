/**
 * matos.js — Les Randos de Nono
 * Panneau latéral détail matériel, filtres dynamiques, animations.
 */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {

    /* ──────────────────────────────────────────
       RÉFÉRENCES DOM
    ────────────────────────────────────────── */
    const panelOverlay = document.getElementById('matos-panel-overlay');
    const panel        = document.getElementById('matos-panel');

    if (!panelOverlay || !panel) return;

    const btnClose   = document.getElementById('matos-panel-close');
    const cards      = document.querySelectorAll('.matos-card');
    const filterBtns = document.querySelectorAll('.matos-filter-btn');

    let _savedScrollY = 0;

    /* ──────────────────────────────────────────
       SCROLL LOCK (même méthode que modal.js)
    ────────────────────────────────────────── */
    function lockScroll() {
      const sbw = window.innerWidth - document.documentElement.clientWidth;
      if (sbw > 0) document.body.style.paddingRight = sbw + 'px';
      document.body.classList.add('modal-open');
    }

    function unlockScroll() {
      document.body.classList.remove('modal-open');
      document.body.style.paddingRight = '';
    }

    /* ──────────────────────────────────────────
       OUVERTURE DU PANEL
    ────────────────────────────────────────── */
    function openPanel(card) {
      const d = card.dataset;

      // Image
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

      // Textes
      _setText('matos-panel-cat',  d.cat  || '');
      _setText('matos-panel-name', d.name || '');
      _setText('matos-panel-desc', d.desc || '');

      // Pourquoi
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

      // Lien produit
      const linkEl = panel.querySelector('.matos-panel-link');
      if (linkEl) {
        if (d.lien) {
          linkEl.href = d.lien;
          linkEl.style.display = '';
        } else {
          linkEl.style.display = 'none';
        }
      }

      // Ouvrir
      lockScroll();
      panelOverlay.classList.add('is-open');
      if (btnClose) btnClose.focus();
    }

    /* ──────────────────────────────────────────
       FERMETURE
    ────────────────────────────────────────── */
    function closePanel() {
      panelOverlay.classList.remove('is-open');
      unlockScroll();
    }

    /* ──────────────────────────────────────────
       ÉVÉNEMENTS — CARTES
    ────────────────────────────────────────── */
    cards.forEach(card => {
      card.addEventListener('click', () => openPanel(card));
    });

    /* ──────────────────────────────────────────
       ÉVÉNEMENTS — FERMETURE
    ────────────────────────────────────────── */
    if (btnClose) btnClose.addEventListener('click', closePanel);

    panelOverlay.addEventListener('click', (e) => {
      if (e.target === panelOverlay) closePanel();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && panelOverlay.classList.contains('is-open')) closePanel();
    });

    /* ──────────────────────────────────────────
       FILTRES DYNAMIQUES
    ────────────────────────────────────────── */
    filterBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const filter = btn.dataset.filter;
        let delay = 0;

        cards.forEach(card => {
          const matches = filter === '*' || card.dataset.cat === filter;
          if (matches) {
            card.classList.remove('matos-hidden');
            // Réanimer les cartes qui réapparaissent
            card.classList.remove('is-visible');
            setTimeout(() => card.classList.add('is-visible'), delay);
            delay += 30; // décalage en cascade
          } else {
            card.classList.add('matos-hidden');
          }
        });
      });
    });

    /* ──────────────────────────────────────────
       ANIMATION D'APPARITION AU SCROLL
    ────────────────────────────────────────── */
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!prefersReducedMotion) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      }, { threshold: 0.1, rootMargin: '0px 0px -30px 0px' });

      cards.forEach(card => observer.observe(card));
    } else {
      cards.forEach(card => card.classList.add('is-visible'));
    }

    /* ──────────────────────────────────────────
       UTILITAIRE
    ────────────────────────────────────────── */
    function _setText(id, value) {
      const el = document.getElementById(id);
      if (el) el.textContent = value;
    }

  });

})();
