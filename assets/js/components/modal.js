/**
 * modal.js — Les Randos de Nono v4
 * Scroll-lock via position:fixed sur body — zéro déplacement de page.
 */

(function () {
  'use strict';

  function init() {
    var overlay = document.getElementById('rando-modal-overlay');
    if (!overlay) return;

    var btnClose     = document.getElementById('rando-modal-close');
    var slideshow    = document.getElementById('rando-slideshow');
    var slideDots    = document.getElementById('rando-slide-dots');
    var slideCounter = document.getElementById('rando-slide-counter');
    var btnPrev      = document.getElementById('rando-slide-prev');
    var btnNext      = document.getElementById('rando-slide-next');

    var currentSlide = 0;
    var totalSlides  = 0;
    function lockScroll() {
      var sbw = window.innerWidth - document.documentElement.clientWidth;
      if (sbw > 0) {
        document.body.style.paddingRight = sbw + 'px';
        var header = document.querySelector('.site-header');
        if (header) header.style.paddingRight = sbw + 'px';
      }
      document.body.style.overflow = 'hidden';
    }

    function unlockScroll() {
      document.body.style.overflow = '';
      document.body.style.paddingRight = '';
      var header = document.querySelector('.site-header');
      if (header) header.style.paddingRight = '';
    }

    function open(source) {
      if (!source) return;
      var d = source.dataset;

      _setText('rando-modal-title',    d.titre    || '');
      _setText('rando-modal-lieu',     d.lieu     || '');
      _setText('rando-modal-distance', d.distance || '—');
      _setText('rando-modal-denivele', d.denivele || '—');
      _setText('rando-modal-duree',    d.duree    || '—');
      _setText('rando-modal-date',     d.date     || '—');
      _setText('rando-modal-recit',    d.recit    || '');

      var badge = document.getElementById('rando-modal-diff-badge');
      if (badge) {
        var diff = d.difficulte || 'moyen';
        badge.textContent = diff.charAt(0).toUpperCase() + diff.slice(1);
        var textColors = { facile: '#2f6b3a', moyen: '#9a5408', difficile: '#9a2c1d' };
        var dotColors  = { facile: '#4CAF50', moyen: '#D97706', difficile: '#c0392b' };
        badge.style.color = textColors[diff] || textColors.moyen;
        badge.style.setProperty('--dot-color', dotColors[diff] || dotColors.moyen);
      }

      var gpxLink = document.getElementById('rando-modal-gpx-link');
      var gpxName = document.getElementById('rando-modal-gpx-name');
      if (gpxLink && gpxName) {
        if (d.gpx) {
          gpxLink.href = d.gpx;
          gpxLink.style.display = '';
          gpxName.textContent = (d.titre || 'trace') + '.gpx';
        } else {
          gpxLink.style.display = 'none';
          gpxName.textContent = 'Pas de trace GPX';
        }
      }

      var mapsLink = document.getElementById('rando-modal-maps-link');
      if (mapsLink) mapsLink.href = d.maps || '#';

      var mapContainer = document.getElementById('rando-modal-map');
      if (mapContainer) {
        if (d.lat && d.lon) {
          var lat = parseFloat(d.lat), lon = parseFloat(d.lon);
          mapContainer.innerHTML = '<iframe loading="lazy" src="https://www.openstreetmap.org/export/embed.html?bbox=' + (lon-0.05) + ',' + (lat-0.04) + ',' + (lon+0.05) + ',' + (lat+0.04) + '&layer=mapnik&marker=' + lat + ',' + lon + '"></iframe>';
        } else {
          mapContainer.innerHTML = '';
        }
      }

      _fillList('rando-modal-sac',     d.sac,     'Aucun détail renseigné');
      _fillList('rando-modal-conseils', d.conseils, 'Aucun conseil renseigné');

      _buildSlideshow(_parseJson(d.photos));

      var meteoEl = document.getElementById('rando-modal-meteo');
      if (meteoEl) {
        meteoEl.innerHTML = '<p class="meteo-loading">Chargement de la météo…</p>';
        if (d.lat && d.lon) {
          _fetchMeteo(parseFloat(d.lat), parseFloat(d.lon), d.lieu || '', meteoEl);
        } else {
          meteoEl.innerHTML = '<p class="meteo-loading">Coordonnées manquantes.</p>';
        }
      }

      var pageUrl   = encodeURIComponent(window.location.origin + '/randonnee/' + (d.id || '') + '/');
      var pageTitle = encodeURIComponent(d.titre || 'Randonnée — Les Randos de Nono');
      _setHref('share-whatsapp', 'https://wa.me/?text=' + pageTitle + '%20' + pageUrl);
      _setHref('share-facebook', 'https://www.facebook.com/sharer/sharer.php?u=' + pageUrl);
      var btnCopy = document.getElementById('share-copy');
      if (btnCopy) {
        btnCopy.onclick = function () {
          navigator.clipboard.writeText(decodeURIComponent(pageUrl)).then(function () {
            btnCopy.classList.add('copied');
            var lbl = document.getElementById('share-copy-label');
            if (lbl) lbl.textContent = 'Lien copié !';
            setTimeout(function () {
              btnCopy.classList.remove('copied');
              if (lbl) lbl.textContent = 'Copier le lien';
            }, 2000);
          });
        };
      }

      overlay.querySelectorAll('.modal-tab').forEach(function (t) { t.classList.remove('active'); });
      overlay.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('active'); });
      var firstTab   = overlay.querySelector('.modal-tab[data-tab="infos"]');
      var firstPanel = overlay.querySelector('.tab-panel[data-panel="infos"]');
      if (firstTab)   firstTab.classList.add('active');
      if (firstPanel) firstPanel.classList.add('active');

      lockScroll();
      overlay.scrollTop = 0;
      overlay.classList.add('is-open');
      overlay.setAttribute('aria-hidden', 'false');
      if (btnClose) btnClose.focus({ preventScroll: true });
    }

    function close() {
      overlay.classList.remove('is-open');
      overlay.setAttribute('aria-hidden', 'true');
      unlockScroll();
      var mapContainer = document.getElementById('rando-modal-map');
      if (mapContainer) mapContainer.innerHTML = '';
    }

    function _buildSlideshow(photos) {
      if (!slideshow) return;
      slideshow.innerHTML = '';
      if (slideDots) slideDots.innerHTML = '';
      currentSlide = 0;

      if (!photos || !photos.length) {
        var placeholder = (typeof randoNono !== 'undefined') ? randoNono.placeholderUrl : '';
        slideshow.innerHTML = placeholder
          ? '<div class="slide active"><img src="' + placeholder + '" alt="Photo à venir"></div>'
          : '<div class="slide active" style="background:linear-gradient(135deg,#1A2E1F,#2E5E3B)"></div>';
        totalSlides = 1;
        if (slideCounter) slideCounter.textContent = '1 / 1';
        return;
      }

      totalSlides = photos.length;
      photos.forEach(function (url, i) {
        var slide = document.createElement('div');
        slide.className = 'slide' + (i === 0 ? ' active' : '');
        var img = new Image();
        img.src = url; img.alt = '';
        slide.appendChild(img);
        slideshow.appendChild(slide);

        if (slideDots) {
          var dot = document.createElement('button');
          dot.className = 'slide-dot' + (i === 0 ? ' active' : '');
          dot.addEventListener('click', function () { _goToSlide(i); });
          slideDots.appendChild(dot);
        }
      });
      _updateSlideUI();
    }

    function _updateSlideUI() {
      if (slideCounter) slideCounter.textContent = (currentSlide + 1) + ' / ' + totalSlides;
      if (slideshow) slideshow.querySelectorAll('.slide').forEach(function (s, i) {
        s.classList.toggle('active', i === currentSlide);
      });
      if (slideDots) slideDots.querySelectorAll('.slide-dot').forEach(function (d, i) {
        d.classList.toggle('active', i === currentSlide);
      });
    }

    function _goToSlide(i) { currentSlide = i; _updateSlideUI(); }

    if (btnPrev) btnPrev.addEventListener('click', function () {
      currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
      _updateSlideUI();
    });
    if (btnNext) btnNext.addEventListener('click', function () {
      currentSlide = (currentSlide + 1) % totalSlides;
      _updateSlideUI();
    });

    overlay.querySelectorAll('.modal-tab').forEach(function (tab) {
      tab.addEventListener('click', function () {
        overlay.querySelectorAll('.modal-tab').forEach(function (t) { t.classList.remove('active'); });
        overlay.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('active'); });
        tab.classList.add('active');
        var panel = overlay.querySelector('.tab-panel[data-panel="' + tab.dataset.tab + '"]');
        if (panel) panel.classList.add('active');
      });
    });

    var WX = {
      0:'☀️',1:'🌤',2:'⛅',3:'☁️',45:'🌫',48:'🌫',
      51:'🌦',53:'🌧',55:'🌧',61:'🌧',63:'🌧',65:'🌧',
      71:'❄️',73:'❄️',75:'❄️',80:'🌦',81:'🌧',82:'⛈',95:'⛈',96:'⛈',99:'⛈'
    };
    var JOURS = ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'];

    function _fetchMeteo(lat, lon, lieu, container) {
      fetch('https://api.open-meteo.com/v1/forecast?latitude=' + lat + '&longitude=' + lon + '&current=temperature_2m,weathercode&daily=weathercode,temperature_2m_max&timezone=Europe/Paris&forecast_days=7')
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var icon = WX[data.current.weathercode] || '🌤';
          var temp = Math.round(data.current.temperature_2m);
          var days = data.daily.time.map(function (t, i) {
            var d = new Date(t);
            return '<div class="meteo-day' + (i === 0 ? ' today' : '') + '">' +
              '<div>' + JOURS[d.getDay()] + '</div>' +
              '<div class="icon">' + (WX[data.daily.weathercode[i]] || '🌤') + '</div>' +
              '<div class="temp">' + Math.round(data.daily.temperature_2m_max[i]) + '°</div>' +
            '</div>';
          }).join('');
          container.innerHTML =
            '<div class="meteo-now">' +
              '<div class="big-icon">' + icon + '</div>' +
              '<div>' +
                '<div class="now-lieu">Maintenant à ' + _esc(lieu) + '</div>' +
                '<div class="now-temp">' + temp + '°C</div>' +
              '</div>' +
            '</div>' +
            '<div class="meteo-week-title">Prévisions 7 jours</div>' +
            '<div class="meteo-days">' + days + '</div>';
        })
        .catch(function () {
          container.innerHTML = '<p class="meteo-loading">Météo indisponible.</p>';
        });
    }

    if (btnClose) btnClose.addEventListener('click', close);

    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) close();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && overlay.classList.contains('is-open')) close();
    });

    function _setText(id, v) {
      var el = document.getElementById(id);
      if (el) el.textContent = v;
    }
    function _setHref(id, href) {
      var el = document.getElementById(id);
      if (el) el.href = href;
    }
    function _fillList(id, jsonStr, emptyMsg) {
      var el = document.getElementById(id);
      if (!el) return;
      var items = _parseJson(jsonStr);
      el.innerHTML = items.length
        ? items.map(function (s) { return '<li>' + _esc(s) + '</li>'; }).join('')
        : '<li style="opacity:.6">' + emptyMsg + '</li>';
    }
    function _parseJson(s) {
      try { return JSON.parse(s || '[]'); } catch (e) { return []; }
    }
    function _esc(s) {
      var d = document.createElement('div');
      d.textContent = s;
      return d.innerHTML;
    }

    window.RandoModal = { open: open, close: close };
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
