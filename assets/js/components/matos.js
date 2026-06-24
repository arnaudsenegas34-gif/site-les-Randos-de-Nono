/**
 * matos.js — Les Randos de Nono
 * Modal détail matériel, filtres dynamiques, tri par taille, animations.
 */

(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {

    var panelOverlay = document.getElementById('matos-panel-overlay');
    var panel        = document.getElementById('matos-panel');

    if (!panelOverlay || !panel) return;

    var btnClose   = document.getElementById('matos-panel-close');
    var grid       = document.getElementById('matos-grid');
    var cards      = Array.from(document.querySelectorAll('.matos-card'));
    var filterBtns = document.querySelectorAll('.matos-filter-btn');

    /* ──────────────────────────────────────────
       TRI PAR TAILLE + dimensionnement
       Plus grand en haut à gauche, plus petit en bas à droite.
       La taille d'affichage est calculée à partir des
       dimensions réelles (cm) et de l'importance.
    ────────────────────────────────────────── */
    var MIN_W = 80;
    var MAX_W = 300;

    function getRealSize(card) {
      var w = parseFloat(card.dataset.largeur) || 20;
      var h = parseFloat(card.dataset.hauteur) || 15;
      return Math.max(w, h);
    }

    function sortAndSizeCards() {
      cards.sort(function (a, b) {
        return getRealSize(b) - getRealSize(a);
      });

      var maxSize = 0;
      cards.forEach(function (c) {
        var s = getRealSize(c);
        if (s > maxSize) maxSize = s;
      });
      if (maxSize === 0) maxSize = 1;

      cards.forEach(function (card) {
        var pct = getRealSize(card) / maxSize;
        var cardW = MIN_W + pct * (MAX_W - MIN_W);
        card.style.setProperty('--card-w', Math.round(cardW) + 'px');
        grid.appendChild(card);
      });
    }

    sortAndSizeCards();

    /* ──────────────────────────────────────────
       PANEL DÉTAIL — inchangé
    ────────────────────────────────────────── */
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
      var d = card.dataset;

      var imgEl = panel.querySelector('#matos-panel-img img');
      var imgWrap = panel.querySelector('#matos-panel-img');
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
      _setText('matos-panel-poids', d.poids ? d.poids + ' g' : '');
      _setText('matos-panel-desc', d.desc || '');

      var pourquoiEl = panel.querySelector('.matos-panel-pourquoi');
      var pourquoiText = panel.querySelector('.matos-panel-pourquoi p');
      if (pourquoiEl && pourquoiText) {
        if (d.pourquoi) {
          pourquoiEl.style.display = '';
          pourquoiText.textContent = d.pourquoi;
        } else {
          pourquoiEl.style.display = 'none';
        }
      }

      var linkEl = panel.querySelector('.matos-panel-link');
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

    /* ──────────────────────────────────────────
       ÉVÉNEMENTS — CARTES
    ────────────────────────────────────────── */
    cards.forEach(function (card) {
      card.addEventListener('click', function () { openPanel(card); });
      card.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openPanel(card); }
      });
    });

    /* ──────────────────────────────────────────
       ÉVÉNEMENTS — FERMETURE
    ────────────────────────────────────────── */
    if (btnClose) btnClose.addEventListener('click', closePanel);

    panelOverlay.addEventListener('click', function (e) {
      if (e.target === panelOverlay) closePanel();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && panelOverlay.classList.contains('is-open')) closePanel();
    });

    /* ──────────────────────────────────────────
       FILTRES DYNAMIQUES
    ────────────────────────────────────────── */
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

    /* ──────────────────────────────────────────
       ANIMATION D'APPARITION AU SCROLL
    ────────────────────────────────────────── */
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
