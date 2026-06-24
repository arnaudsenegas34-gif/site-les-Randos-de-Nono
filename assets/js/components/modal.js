/**
 * modal.js — Les Randos de Nono v3.1
 * Bug fix : centrage garanti, pas de race condition, scroll-lock simplifié.
 *
 * Changements vs v3.0 :
 * - aria-hidden → classe CSS .is-open (plus fiable cross-browser)
 * - Suppression du requestAnimationFrame dans open() (race condition)
 * - Suppression du window.scrollTo() dans open() (causait le décalage)
 * - scroll-lock via overflow:hidden uniquement (pas position:fixed)
 * - Compensation scrollbar via padding-right pour éviter le saut de layout
 */

(function () {
  'use strict';

  /* ══════════════════════════════════════════════
     INIT — attendre DOMContentLoaded
  ══════════════════════════════════════════════ */
  function init() {
    const overlay = document.getElementById('rando-modal-overlay');
    if (!overlay) return; // page sans modal, on sort proprement

    const btnClose     = document.getElementById('rando-modal-close');
    const slideshow    = document.getElementById('rando-slideshow');
    const slideDots    = document.getElementById('rando-slide-dots');
    const slideCounter = document.getElementById('rando-slide-counter');
    const btnPrev      = document.getElementById('rando-slide-prev');
    const btnNext      = document.getElementById('rando-slide-next');

    let currentSlide = 0;
    let totalSlides  = 0;

    /* ──────────────────────────────────────────
       SCROLL LOCK simplifié
       overflow:hidden sur body uniquement.
       Compensation de la scrollbar pour éviter
       le saut de layout (shiftLayout).
    ────────────────────────────────────────── */
    function lockScroll() {
      const scrollBarWidth = window.innerWidth - document.documentElement.clientWidth;
      if (scrollBarWidth > 0) {
        document.body.style.paddingRight = scrollBarWidth + 'px';
        document.querySelector('.site-header') &&
          (document.querySelector('.site-header').style.paddingRight = scrollBarWidth + 'px');
      }
      document.body.classList.add('modal-open');
    }

    function unlockScroll() {
      document.body.classList.remove('modal-open');
      document.body.style.paddingRight = '';
      const header = document.querySelector('.site-header');
      if (header) header.style.paddingRight = '';
    }

    /* ──────────────────────────────────────────
       OUVERTURE — séquence simple et fiable
    ────────────────────────────────────────── */
    function open(source) {
      if (!source) return;
      const d = source.dataset;

      // 1. Remplir le contenu
      _setText('rando-modal-title',    d.titre    || '');
      _setText('rando-modal-lieu',     d.lieu     || '');
      _setText('rando-modal-distance', d.distance || '—');
      _setText('rando-modal-denivele', d.denivele || '—');
      _setText('rando-modal-duree',    d.duree    || '—');
      _setText('rando-modal-date',     d.date     || '—');
      _setText('rando-modal-recit',    d.recit    || '');

      // Badge difficulté
      const badge = document.getElementById('rando-modal-diff-badge');
      if (badge) {
        const diff = d.difficulte || 'moyen';
        badge.textContent = diff.charAt(0).toUpperCase() + diff.slice(1);
        const textColors = { facile: '#2f6b3a', moyen: '#9a5408', difficile: '#9a2c1d' };
        const dotColors  = { facile: '#4CAF50', moyen: '#D97706', difficile: '#c0392b' };
        badge.style.color = textColors[diff] || textColors.moyen;
        badge.style.setProperty('--dot-color', dotColors[diff] || dotColors.moyen);
      }

      // GPX
      const gpxLink = document.getElementById('rando-modal-gpx-link');
      const gpxName = document.getElementById('rando-modal-gpx-name');
      if (gpxLink && gpxName) {
        if (d.gpx) {
          gpxLink.href = d.gpx;
          gpxLink.style.display = '';
          gpxName.textContent   = (d.titre || 'trace') + '.gpx';
        } else {
          gpxLink.style.display = 'none';
          gpxName.textContent   = 'Pas de trace GPX';
        }
      }

      // Lien Maps
      const mapsLink = document.getElementById('rando-modal-maps-link');
      if (mapsLink) mapsLink.href = d.maps || '#';

      // Carte OSM
      const mapContainer = document.getElementById('rando-modal-map');
      if (mapContainer) {
        if (d.lat && d.lon) {
          const lat = parseFloat(d.lat), lon = parseFloat(d.lon);
          mapContainer.innerHTML = `<iframe loading="lazy"
            src="https://www.openstreetmap.org/export/embed.html?bbox=${lon-0.05},${lat-0.04},${lon+0.05},${lat+0.04}&layer=mapnik&marker=${lat},${lon}">
          </iframe>`;
        } else {
          mapContainer.innerHTML = '';
        }
      }

      // Listes sac & conseils
      _fillList('rando-modal-sac',     d.sac,     'Aucun détail renseigné');
      _fillList('rando-modal-conseils', d.conseils, 'Aucun conseil renseigné');

      // Slideshow
      _buildSlideshow(_parseJson(d.photos));

      // Météo
      const meteoEl = document.getElementById('rando-modal-meteo');
      if (meteoEl) {
        meteoEl.innerHTML = '<p class="meteo-loading">Chargement de la météo…</p>';
        if (d.lat && d.lon) {
          _fetchMeteo(parseFloat(d.lat), parseFloat(d.lon), d.lieu || '', meteoEl);
        } else {
          meteoEl.innerHTML = '<p class="meteo-loading">Coordonnées manquantes.</p>';
        }
      }

      // Partage social
      const pageUrl  = encodeURIComponent(window.location.origin + '/randonnee/' + (d.id || '') + '/');
      const pageTitle = encodeURIComponent(d.titre || 'Randonnée — Les Randos de Nono');
      _setHref('share-whatsapp', `https://wa.me/?text=${pageTitle}%20${pageUrl}`);
      _setHref('share-facebook', `https://www.facebook.com/sharer/sharer.php?u=${pageUrl}`);
      const btnCopy = document.getElementById('share-copy');
      if (btnCopy) {
        btnCopy.onclick = () => {
          navigator.clipboard.writeText(decodeURIComponent(pageUrl)).then(() => {
            btnCopy.classList.add('copied');
            const lbl = document.getElementById('share-copy-label');
            if (lbl) lbl.textContent = 'Lien copié !';
            setTimeout(() => {
              btnCopy.classList.remove('copied');
              if (lbl) lbl.textContent = 'Copier le lien';
            }, 2000);
          });
        };
      }

      // Reset onglets sur "Infos"
      overlay.querySelectorAll('.modal-tab').forEach(t => t.classList.remove('active'));
      overlay.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      const firstTab   = overlay.querySelector('.modal-tab[data-tab="infos"]');
      const firstPanel = overlay.querySelector('.tab-panel[data-panel="infos"]');
      if (firstTab)   firstTab.classList.add('active');
      if (firstPanel) firstPanel.classList.add('active');

      // 2. Ouvrir — ordre: lock PUIS afficher (évite le flash)
      lockScroll();
      overlay.scrollTop = 0;
      overlay.classList.add('is-open');
      overlay.setAttribute('aria-hidden', 'false');
      if (btnClose) setTimeout(() => btnClose.focus(), 50);
    }

    /* ──────────────────────────────────────────
       FERMETURE
    ────────────────────────────────────────── */
    function close() {
      overlay.classList.remove('is-open');
      overlay.setAttribute('aria-hidden', 'true');
      unlockScroll();
      // Nettoie l'iframe OSM pour libérer la mémoire
      const mapContainer = document.getElementById('rando-modal-map');
      if (mapContainer) mapContainer.innerHTML = '';
    }

    /* ──────────────────────────────────────────
       SLIDESHOW
    ────────────────────────────────────────── */
    function _buildSlideshow(photos) {
      if (!slideshow) return;
      slideshow.innerHTML = '';
      if (slideDots) slideDots.innerHTML = '';
      currentSlide = 0;

      if (!photos || !photos.length) {
        const placeholder = (typeof randoNono !== 'undefined') ? randoNono.placeholderUrl : '';
        slideshow.innerHTML = placeholder
          ? `<div class="slide active"><img src="${placeholder}" alt="Photo à venir"></div>`
          : '<div class="slide active" style="background:linear-gradient(135deg,#1A2E1F,#2E5E3B)"></div>';
        totalSlides = 1;
        if (slideCounter) slideCounter.textContent = '1 / 1';
        return;
      }

      totalSlides = photos.length;
      photos.forEach((url, i) => {
        const slide = document.createElement('div');
        slide.className = 'slide' + (i === 0 ? ' active' : '');
        const img = new Image();
        img.src = url; img.alt = '';
        slide.appendChild(img);
        slideshow.appendChild(slide);

        if (slideDots) {
          const dot = document.createElement('button');
          dot.className = 'slide-dot' + (i === 0 ? ' active' : '');
          dot.addEventListener('click', () => _goToSlide(i));
          slideDots.appendChild(dot);
        }
      });
      _updateSlideUI();
    }

    function _updateSlideUI() {
      if (slideCounter) slideCounter.textContent = `${currentSlide + 1} / ${totalSlides}`;
      slideshow && slideshow.querySelectorAll('.slide').forEach((s, i) =>
        s.classList.toggle('active', i === currentSlide));
      slideDots && slideDots.querySelectorAll('.slide-dot').forEach((d, i) =>
        d.classList.toggle('active', i === currentSlide));
    }

    function _goToSlide(i) { currentSlide = i; _updateSlideUI(); }

    if (btnPrev) btnPrev.addEventListener('click', () => {
      currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
      _updateSlideUI();
    });
    if (btnNext) btnNext.addEventListener('click', () => {
      currentSlide = (currentSlide + 1) % totalSlides;
      _updateSlideUI();
    });

    /* ──────────────────────────────────────────
       ONGLETS
    ────────────────────────────────────────── */
    overlay.querySelectorAll('.modal-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        overlay.querySelectorAll('.modal-tab').forEach(t => t.classList.remove('active'));
        overlay.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        const panel = overlay.querySelector(`.tab-panel[data-panel="${tab.dataset.tab}"]`);
        if (panel) panel.classList.add('active');
      });
    });

    /* ──────────────────────────────────────────
       MÉTÉO
    ────────────────────────────────────────── */
    const WX = {
      0:'☀️',1:'🌤',2:'⛅',3:'☁️',45:'🌫',48:'🌫',
      51:'🌦',53:'🌧',55:'🌧',61:'🌧',63:'🌧',65:'🌧',
      71:'❄️',73:'❄️',75:'❄️',80:'🌦',81:'🌧',82:'⛈',95:'⛈',96:'⛈',99:'⛈'
    };
    const JOURS = ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'];

    async function _fetchMeteo(lat, lon, lieu, container) {
      try {
        const r = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=temperature_2m,weathercode&daily=weathercode,temperature_2m_max&timezone=Europe/Paris&forecast_days=7`);
        const data = await r.json();
        const icon = WX[data.current.weathercode] || '🌤';
        const temp = Math.round(data.current.temperature_2m);
        const days = data.daily.time.map((t, i) => {
          const d = new Date(t);
          return `<div class="meteo-day${i===0?' today':''}">
            <div>${JOURS[d.getDay()]}</div>
            <div class="icon">${WX[data.daily.weathercode[i]]||'🌤'}</div>
            <div class="temp">${Math.round(data.daily.temperature_2m_max[i])}°</div>
          </div>`;
        }).join('');
        container.innerHTML = `
          <div class="meteo-now">
            <div class="big-icon">${icon}</div>
            <div>
              <div class="now-lieu">Maintenant à ${_esc(lieu)}</div>
              <div class="now-temp">${temp}°C</div>
            </div>
          </div>
          <div class="meteo-week-title">Prévisions 7 jours</div>
          <div class="meteo-days">${days}</div>`;
      } catch {
        container.innerHTML = '<p class="meteo-loading">Météo indisponible.</p>';
      }
    }

    /* ──────────────────────────────────────────
       ÉVÉNEMENTS DE FERMETURE
    ────────────────────────────────────────── */
    if (btnClose) btnClose.addEventListener('click', close);

    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) close();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && overlay.classList.contains('is-open')) close();
    });

    /* ──────────────────────────────────────────
       UTILITAIRES
    ────────────────────────────────────────── */
    function _setText(id, v) {
      const el = document.getElementById(id);
      if (el) el.textContent = v;
    }
    function _setHref(id, href) {
      const el = document.getElementById(id);
      if (el) el.href = href;
    }
    function _fillList(id, jsonStr, emptyMsg) {
      const el = document.getElementById(id);
      if (!el) return;
      const items = _parseJson(jsonStr);
      el.innerHTML = items.length
        ? items.map(s => `<li>${_esc(s)}</li>`).join('')
        : `<li style="opacity:.6">${emptyMsg}</li>`;
    }
    function _parseJson(s) {
      try { return JSON.parse(s || '[]'); } catch { return []; }
    }
    function _esc(s) {
      const d = document.createElement('div');
      d.textContent = s;
      return d.innerHTML;
    }

    /* ──────────────────────────────────────────
       API PUBLIQUE
    ────────────────────────────────────────── */
    window.RandoModal = { open, close };
  }

  /* Lancement après chargement du DOM */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
