/**
 * randos.js — Les Randos de Nono
 * Animations et interactions de la grille de randonnées et du panneau Matos.
 * Les cartes sont de vrais liens <a href="…"> vers la page de la randonnée :
 * aucune interception JS du clic n'est nécessaire.
 */

(function () {
  'use strict';

  // Attendre que le DOM soit prêt
  document.addEventListener('DOMContentLoaded', function () {

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
