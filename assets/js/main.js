/**
 * main.js — Les Randos de Nono
 * Script principal : parallaxe hero, compteurs stats, menu mobile, transition de page.
 * La logique modale est dans components/modal.js
 * La logique randonnées est dans pages/randos.js
 */

(function () {
  'use strict';

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ══════════════════════════════════════════════
     PWA HORS-LIGNE — enregistrement du service worker
     Permet de consulter hors-ligne les randonnées déjà visitées sur le terrain.
  ══════════════════════════════════════════════ */
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {});
    });
  }

  /* ══════════════════════════════════════════════
     PARALLAXE HERO
  ══════════════════════════════════════════════ */
  const heroEl      = document.getElementById('hero');
  const heroBgLayer = document.getElementById('hero-bg-layer');

  if (heroEl && heroBgLayer && !prefersReducedMotion) {
    let ticking = false;
    function updateParallax() {
      const scrollY  = window.scrollY;
      const heroH    = heroEl.offsetHeight;
      if (scrollY < heroH) {
        heroBgLayer.style.transform = `translate3d(0, ${scrollY * 0.35}px, 0)`;
      }
      ticking = false;
    }
    window.addEventListener('scroll', () => {
      if (!ticking) { requestAnimationFrame(updateParallax); ticking = true; }
    }, { passive: true });
  }

  /* ══════════════════════════════════════════════
     RÉVÉLATION AU SCROLL — titres de section et grands blocs
     Même principe que .rando-card/.matos-card (voir randos.js),
     étendu ici aux en-têtes de section pour que chaque page (accueil,
     archive, fiche rando, 404…) profite du même effet de fondu.
  ══════════════════════════════════════════════ */
  const revealTargets = document.querySelectorAll(
    '.section-eyebrow, .section-title, .section-sub, .divider, ' +
    '.derniere-card, .apropos-visual, .stat-block, .matos-filters, ' +
    '.archive-filters, .newsletter-inner'
  );
  if (revealTargets.length) {
    if (prefersReducedMotion) {
      revealTargets.forEach(el => el.classList.add('is-revealed'));
    } else {
      const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-revealed');
            revealObserver.unobserve(entry.target);
          }
        });
      }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
      revealTargets.forEach(el => revealObserver.observe(el));
    }
  }

  /* ══════════════════════════════════════════════
     COMPTEURS ANIMÉS — STATISTIQUES
  ══════════════════════════════════════════════ */
  function animateCounters() {
    document.querySelectorAll('.big-num[data-count]').forEach(el => {
      const target   = parseInt(el.dataset.count, 10) || 0;
      const suffix   = el.dataset.suffix || '';
      const duration = 1400;
      const start    = performance.now();
      function tick(now) {
        const progress = Math.min((now - start) / duration, 1);
        const eased    = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(target * eased).toLocaleString('fr-FR') + suffix;
        if (progress < 1) requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
    });
  }

  const statsSection = document.getElementById('statistiques');
  if (statsSection) {
    const obs = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) { animateCounters(); obs.disconnect(); }
      });
    }, { threshold: 0.3 });
    obs.observe(statsSection);
  }

  /* ══════════════════════════════════════════════
     NAV ACTIVE — mise en avant du bouton de la section visible
     Sur la page d'accueil, "Accueil" reste actif tant qu'aucune
     des sections ancrées (Matos, Statistiques, À propos) n'est
     à l'écran ; ensuite le bouton correspondant prend le relais.
  ══════════════════════════════════════════════ */
  if (heroEl) {
    const navButtons = document.querySelectorAll('[data-nav-key]');
    const setActiveNav = (key) => {
      navButtons.forEach(btn => btn.classList.toggle('is-current', btn.dataset.navKey === key));
    };
    const navSections = ['matos', 'statistiques', 'apropos']
      .map(id => ({ id, el: document.getElementById(id) }))
      .filter(s => s.el);

    if (navSections.length) {
      const navObs = new IntersectionObserver((entries) => {
        const visible = navSections.find(s => {
          const rect = s.el.getBoundingClientRect();
          return rect.top < window.innerHeight / 2 && rect.bottom > window.innerHeight / 2;
        });
        setActiveNav(visible ? visible.id : 'accueil');
      }, { rootMargin: '-45% 0px -45% 0px', threshold: 0 });
      navSections.forEach(s => navObs.observe(s.el));
    }
  }

  /* ══════════════════════════════════════════════
     MODE SOMBRE — bascule + persistance (localStorage)
     Au chargement, le thème choisi est déjà appliqué par le script
     inline dans <head> (évite le flash). Ici on ne gère que le clic.
  ══════════════════════════════════════════════ */
  const themeToggle = document.getElementById('theme-toggle');
  if (themeToggle) {
    const THEME_KEY = 'rando-nono-theme';
    const prefersDarkQuery = window.matchMedia('(prefers-color-scheme: dark)');

    function currentTheme() {
      const explicit = document.documentElement.getAttribute('data-theme');
      if (explicit === 'dark' || explicit === 'light') return explicit;
      return prefersDarkQuery.matches ? 'dark' : 'light';
    }

    function updateToggleLabel(theme) {
      const label = theme === 'dark' ? 'Activer le mode clair' : 'Activer le mode sombre';
      themeToggle.setAttribute('aria-label', label);
      themeToggle.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
    }

    updateToggleLabel(currentTheme());

    themeToggle.addEventListener('click', () => {
      const next = currentTheme() === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      try { localStorage.setItem(THEME_KEY, next); } catch (e) {}
      updateToggleLabel(next);
    });

    // Tant que l'utilisateur n'a jamais choisi manuellement, le thème
    // suit le réglage clair/sombre du téléphone en direct : le CSS
    // (@media prefers-color-scheme) bascule déjà les couleurs tout seul,
    // on ne fait ici que garder le libellé du bouton synchronisé.
    var systemChangeHandler = function () {
      if (!document.documentElement.getAttribute('data-theme')) {
        updateToggleLabel(currentTheme());
      }
    };
    if (prefersDarkQuery.addEventListener) {
      prefersDarkQuery.addEventListener('change', systemChangeHandler);
    } else if (prefersDarkQuery.addListener) {
      prefersDarkQuery.addListener(systemChangeHandler);
    }
  }

  /* ══════════════════════════════════════════════
     MENU HAMBURGER MOBILE
  ══════════════════════════════════════════════ */
  const menuToggle   = document.getElementById('menu-toggle');
  const mobileDrawer = document.getElementById('nav-mobile-drawer');

  if (menuToggle && mobileDrawer) {
    menuToggle.addEventListener('click', () => {
      const isOpen = mobileDrawer.classList.toggle('open');
      menuToggle.textContent = isOpen ? '✕' : '☰';
      menuToggle.setAttribute('aria-label', isOpen ? 'Fermer le menu' : 'Ouvrir le menu');
      menuToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    mobileDrawer.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        mobileDrawer.classList.remove('open');
        menuToggle.textContent = '☰';
        menuToggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  /* ══════════════════════════════════════════════
     TRANSITION DE PAGE
     — jamais pendant l'ouverture du modal
     — jamais sur les ancres (#)
     — un seul setTimeout actif à la fois
  ══════════════════════════════════════════════ */
  if (!prefersReducedMotion) {
    let pageExitTimer = null;

    document.addEventListener('click', (e) => {
      // Ne pas déclencher si le modal est ouvert
      const overlay = document.getElementById('rando-modal-overlay');
      if (overlay && overlay.classList.contains('is-open')) return;

      const link = e.target.closest('a[href]');
      if (!link) return;

      const href = link.getAttribute('href');
      if (
        !href ||
        href.startsWith('#')        ||
        href.includes('/#')         ||
        href.startsWith('mailto:')  ||
        href.startsWith('tel:')     ||
        href.startsWith('javascript:') ||
        link.hostname !== window.location.hostname ||
        link.hasAttribute('download') ||
        link.target === '_blank'    ||
        link.classList.contains('btn-nav') ||
        e.ctrlKey || e.metaKey || e.shiftKey
      ) return;

      e.preventDefault();
      if (pageExitTimer) clearTimeout(pageExitTimer);
      document.body.classList.add('page-exit');
      pageExitTimer = setTimeout(() => { window.location.href = href; }, 190);
    });
  }

})();
