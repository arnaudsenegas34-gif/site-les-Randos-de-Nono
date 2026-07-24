/**
 * randos.js — Les Randos de Nono
 * Gestion des clics sur les cartes randonnée et la rando mise en avant.
 * Dépend de modal.js (window.RandoModal doit être chargé avant).
 */

(function () {
  'use strict';

  // Attendre que le DOM soit prêt
  document.addEventListener('DOMContentLoaded', function () {

    // Sécurité : RandoModal doit être disponible
    if (typeof window.RandoModal === 'undefined') {
      console.error('RandoModal non chargé — vérifier l\'ordre des scripts');
      return;
    }

    /* ── Cartes de la grille ──
       Le titre et le bouton "Voir la rando" sont de vrais liens <a href="…">
       vers la page de la randonnée (crawlables par Google, fonctionnels sans JS).
       Ici on intercepte le clic pour garder l'ouverture en modale. */
    document.querySelectorAll('.rando-card').forEach(card => {
      card.addEventListener('click', function (e) {
        // Ignorer les clics sur le bouton GPX
        if (e.target.closest('[download]')) return;
        e.preventDefault();
        window.RandoModal.open(card);
      });

      // Bouton "Voir la rando" dans la carte
      const btnVoir = card.querySelector('.js-open-modal');
      if (btnVoir) {
        btnVoir.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          window.RandoModal.open(card);
        });
      }
    });

    /* ── Rando mise en avant (featured) ── */
    document.querySelectorAll('.js-open-modal-featured').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        // Le bouton porte directement tous ses data-*
        window.RandoModal.open(btn);
      });
    });

    /* ── Animations d'apparition au scroll ── */
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (!prefersReducedMotion) {
      const fadeTargets = document.querySelectorAll('.rando-card, .matos-card');
      const fadeObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            fadeObserver.unobserve(entry.target);
          }
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
      fadeTargets.forEach(el => fadeObserver.observe(el));
    } else {
      document.querySelectorAll('.rando-card, .matos-card').forEach(el => {
        el.classList.add('is-visible');
      });
    }

    /* ── Filtre par catégorie — Matos de Nono ── */
    const matosFilters = document.getElementById('matos-filters');
    if (matosFilters) {
      const matosCards = document.querySelectorAll('#matos-grid .matos-card');
      matosFilters.querySelectorAll('.matos-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          matosFilters.querySelectorAll('.matos-filter-btn').forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          const filter = btn.dataset.filter;
          matosCards.forEach(card => {
            card.classList.toggle('matos-hidden', filter !== '*' && card.dataset.cat !== filter);
          });
        });
      });
    }

    /* ── Bouton "Toutes les randonnées" (voir plus) ── */
    const btnVoirPlus = document.getElementById('btn-voir-plus');
    if (btnVoirPlus) {
      btnVoirPlus.addEventListener('click', () => {
        document.querySelectorAll('.rando-hidden').forEach(el => el.classList.remove('rando-hidden'));
        btnVoirPlus.style.display = 'none';
      });
    }

  });

})();
