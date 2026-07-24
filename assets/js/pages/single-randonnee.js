/**
 * single-randonnee.js — Fiche complète d'une randonnée
 */
(function () {
  'use strict';

  var WX = {
    0:'☀️',1:'🌤',2:'⛅',3:'☁️',45:'🌫',48:'🌫',
    51:'🌦',53:'🌧',55:'🌧',61:'🌧',63:'🌧',65:'🌧',
    71:'❄️',73:'❄️',75:'❄️',80:'🌦',81:'🌧',82:'⛈',95:'⛈',96:'⛈',99:'⛈'
  };
  var WX_DESC = {
    0:'Ciel dégagé',1:'Peu nuageux',2:'Partiellement nuageux',3:'Couvert',
    45:'Brouillard',48:'Brouillard givrant',
    51:'Bruine légère',53:'Bruine',55:'Bruine forte',
    61:'Pluie légère',63:'Pluie',65:'Pluie forte',
    71:'Neige légère',73:'Neige',75:'Neige forte',
    80:'Averses légères',81:'Averses',82:'Averses fortes',
    95:'Orage',96:'Orage & grêle',99:'Orage & grêle fort'
  };
  var JOURS = ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'];

  function init() {
    initMap();
    initMeteo();
    initLightbox();
    initShare();
    initPrint();
  }

  /* ── Carte Leaflet ── */
  function initMap() {
    var mapEl = document.getElementById('sr-map');
    if (!mapEl || typeof L === 'undefined') return;

    var lat = parseFloat(mapEl.dataset.lat);
    var lon = parseFloat(mapEl.dataset.lon);
    var gpxUrl = mapEl.dataset.gpx || null;
    if (isNaN(lat) || isNaN(lon)) return;

    var map = L.map(mapEl, { zoomControl: true, scrollWheelZoom: false }).setView([lat, lon], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap', maxZoom: 18
    }).addTo(map);

    var startIcon = L.divIcon({
      className: '',
      html: '<div style="background:#2E5E3B;width:14px;height:14px;border-radius:50%;border:3px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)"></div>',
      iconSize: [14, 14], iconAnchor: [7, 7]
    });

    if (gpxUrl && typeof L.GPX !== 'undefined') {
      new L.GPX(gpxUrl, {
        async: true,
        marker_options: { startIconUrl: null, endIconUrl: null, shadowUrl: null, wptIconUrls: { '': null } },
        polyline_options: { color: '#D97706', weight: 3, opacity: 0.85 }
      })
      .on('loaded', function (e) {
        map.fitBounds(e.target.getBounds(), { padding: [30, 30] });
        var layers = e.target.getLayers();
        if (layers.length) {
          var pts = layers[0].getLatLngs ? layers[0].getLatLngs() : null;
          if (pts && pts.length) {
            var start = Array.isArray(pts[0]) ? pts[0][0] : pts[0];
            L.marker([start.lat, start.lng], { icon: startIcon }).bindTooltip('Départ', { permanent: false }).addTo(map);
          }
        }
      })
      .on('error', function () {
        L.marker([lat, lon], { icon: startIcon }).addTo(map);
      })
      .addTo(map);
    } else {
      L.marker([lat, lon], { icon: startIcon }).addTo(map);
    }

    setTimeout(function () { map.invalidateSize(); }, 300);

    window.srMap = map;
  }

  /* ── Météo Open-Meteo ── */
  function initMeteo() {
    var container = document.getElementById('sr-meteo');
    if (!container) return;

    var lat = parseFloat(container.dataset.lat);
    var lon = parseFloat(container.dataset.lon);
    var lieu = container.dataset.lieu || '';
    if (isNaN(lat) || isNaN(lon)) {
      container.innerHTML = '<p class="meteo-loading">Coordonnées manquantes.</p>';
      return;
    }

    fetch('https://api.open-meteo.com/v1/forecast?latitude=' + lat + '&longitude=' + lon + '&current=temperature_2m,weathercode&hourly=temperature_2m,weathercode&daily=weathercode,temperature_2m_max,temperature_2m_min&timezone=Europe/Paris&forecast_days=7')
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var icon = WX[data.current.weathercode] || '🌤';
        var temp = Math.round(data.current.temperature_2m);
        var desc = WX_DESC[data.current.weathercode] || '';

        var slotHours = [8, 12, 15, 20];
        var slotLabels = ['Matin', 'Midi', 'Après-midi', 'Soir'];
        var currentHour = new Date().getHours();
        var activeSlot = currentHour < 10 ? 0 : currentHour < 13 ? 1 : currentHour < 18 ? 2 : 3;

        var slotsHtml = slotLabels.map(function (label, i) {
          var idx = slotHours[i];
          var slotTemp = Math.round(data.hourly.temperature_2m[idx]);
          var slotIcon = WX[data.hourly.weathercode[idx]] || '🌤';
          return '<div class="meteo-slot' + (i === activeSlot ? ' active' : '') + '">' +
            '<div class="slot-label">' + label + '</div>' +
            '<div class="slot-icon">' + slotIcon + '</div>' +
            '<div class="slot-temp">' + slotTemp + '°</div>' +
          '</div>';
        }).join('');

        var days = data.daily.time.map(function (t, i) {
          var d = new Date(t);
          return '<div class="meteo-day' + (i === 0 ? ' today' : '') + '">' +
            '<div class="day-name">' + JOURS[d.getDay()] + '</div>' +
            '<div class="icon">' + (WX[data.daily.weathercode[i]] || '🌤') + '</div>' +
            '<div class="temp-range">' +
              '<span class="temp-max">' + Math.round(data.daily.temperature_2m_max[i]) + '°</span>' +
              '<span class="temp-min">' + Math.round(data.daily.temperature_2m_min[i]) + '°</span>' +
            '</div>' +
          '</div>';
        }).join('');

        container.innerHTML =
          '<div class="meteo-now">' +
            '<div class="big-icon">' + icon + '</div>' +
            '<div>' +
              '<div class="now-lieu">Maintenant à ' + esc(lieu) + '</div>' +
              '<div class="now-temp">' + temp + '°C</div>' +
              (desc ? '<div class="now-desc">' + esc(desc) + '</div>' : '') +
            '</div>' +
          '</div>' +
          '<div class="meteo-today-title">Aujourd\'hui</div>' +
          '<div class="meteo-slots">' + slotsHtml + '</div>' +
          '<div class="meteo-week-title">Prévisions 7 jours</div>' +
          '<div class="meteo-days">' + days + '</div>';
      })
      .catch(function () {
        container.innerHTML = '<p class="meteo-loading">Météo indisponible.</p>';
      });
  }

  /* ── Lightbox photos ── */
  function initLightbox() {
    var photos = document.querySelectorAll('.sr-photo');
    var lightbox = document.getElementById('sr-lightbox');
    if (!photos.length || !lightbox) return;

    var img = document.getElementById('sr-lightbox-img');
    var counter = document.getElementById('sr-lightbox-counter');
    var btnClose = document.getElementById('sr-lightbox-close');
    var btnPrev = document.getElementById('sr-lightbox-prev');
    var btnNext = document.getElementById('sr-lightbox-next');
    var urls = [];
    var current = 0;

    photos.forEach(function (photo) {
      urls.push(photo.src);
      photo.addEventListener('click', function () {
        current = parseInt(photo.dataset.index, 10);
        show();
      });
    });

    function show() {
      img.src = urls[current];
      img.alt = 'Photo ' + (current + 1);
      counter.textContent = (current + 1) + ' / ' + urls.length;
      lightbox.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function hide() {
      lightbox.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    btnClose.addEventListener('click', hide);
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) hide();
    });

    btnPrev.addEventListener('click', function (e) {
      e.stopPropagation();
      current = (current - 1 + urls.length) % urls.length;
      show();
    });
    btnNext.addEventListener('click', function (e) {
      e.stopPropagation();
      current = (current + 1) % urls.length;
      show();
    });

    document.addEventListener('keydown', function (e) {
      if (lightbox.getAttribute('aria-hidden') !== 'false') return;
      if (e.key === 'Escape') hide();
      if (e.key === 'ArrowLeft') { current = (current - 1 + urls.length) % urls.length; show(); }
      if (e.key === 'ArrowRight') { current = (current + 1) % urls.length; show(); }
    });
  }

  /* ── Partage social ── */
  function initShare() {
    var url = window.location.href;
    var title = document.title;
    var encoded = encodeURIComponent(url);
    var encodedTitle = encodeURIComponent(title);

    var wa = document.getElementById('sr-share-whatsapp');
    if (wa) wa.href = 'https://wa.me/?text=' + encodedTitle + '%20' + encoded;

    var fb = document.getElementById('sr-share-facebook');
    if (fb) fb.href = 'https://www.facebook.com/sharer/sharer.php?u=' + encoded;

    var btnCopy = document.getElementById('sr-share-copy');
    var lblCopy = document.getElementById('sr-share-copy-label');
    if (btnCopy) {
      btnCopy.addEventListener('click', function () {
        navigator.clipboard.writeText(url).then(function () {
          btnCopy.classList.add('copied');
          if (lblCopy) lblCopy.textContent = 'Lien copié !';
          setTimeout(function () {
            btnCopy.classList.remove('copied');
            if (lblCopy) lblCopy.textContent = 'Copier le lien';
          }, 2000);
        });
      });
    }
  }

  /* ── Fiche imprimable / PDF ── */
  function initPrint() {
    var btn = document.getElementById('sr-print-btn');
    var mapWrap = document.getElementById('sr-print-map-wrap');
    var mapLoaded = false;

    function project(la, lo, zoom) {
      var n = Math.pow(2, zoom);
      var latRad = la * Math.PI / 180;
      return {
        x: ( lo + 180 ) / 360 * n,
        y: ( 1 - Math.log( Math.tan( latRad ) + 1 / Math.cos( latRad ) ) / Math.PI ) / 2 * n
      };
    }

    /* Mosaïque de tuiles OSM (même serveur que la carte interactive) plutôt
       qu'un service tiers de "static map" externe, peu fiable/hors-ligne.
       Trace le GPX par-dessus si fourni, avec un zoom ajusté pour le contenir. */
    function buildPrintMap(lat, lon, track, callback) {
      var TILE = 150;
      var COLS = 5, ROWS = 5;
      var HALF = ( COLS - 1 ) / 2;
      var subdomains = [ 'a', 'b', 'c' ];

      var centerLat = lat, centerLon = lon, zoom = 13;

      if ( track && track.length > 1 ) {
        var minLat = track[0][0], maxLat = track[0][0], minLon = track[0][1], maxLon = track[0][1];
        for ( var i = 1; i < track.length; i++ ) {
          if ( track[i][0] < minLat ) minLat = track[i][0];
          if ( track[i][0] > maxLat ) maxLat = track[i][0];
          if ( track[i][1] < minLon ) minLon = track[i][1];
          if ( track[i][1] > maxLon ) maxLon = track[i][1];
        }
        centerLat = ( minLat + maxLat ) / 2;
        centerLon = ( minLon + maxLon ) / 2;

        var maxAllowedW = COLS * TILE * 0.82;
        var maxAllowedH = ROWS * TILE * 0.82;
        zoom = 8;
        for ( var z = 16; z >= 8; z-- ) {
          var p1 = project( maxLat, minLon, z );
          var p2 = project( minLat, maxLon, z );
          var wPx = ( p2.x - p1.x ) * TILE;
          var hPx = ( p2.y - p1.y ) * TILE;
          if ( wPx <= maxAllowedW && hPx <= maxAllowedH ) { zoom = z; break; }
        }
      }

      var centerP = project( centerLat, centerLon, zoom );
      var centerXTile = Math.floor( centerP.x );
      var centerYTile = Math.floor( centerP.y );

      var frame = document.createElement('div');
      frame.style.cssText = 'position:relative;width:' + ( COLS * TILE ) + 'px;height:' + ( ROWS * TILE ) + 'px;';

      var grid = document.createElement('div');
      grid.style.cssText = 'display:grid;grid-template-columns:repeat(' + COLS + ',' + TILE + 'px);grid-template-rows:repeat(' + ROWS + ',' + TILE + 'px);width:' + ( COLS * TILE ) + 'px;height:' + ( ROWS * TILE ) + 'px;overflow:hidden;';

      var toLoad = 0, loaded = 0, done = false;
      function finish() {
        if ( done ) return;
        done = true;
        if ( callback ) callback();
      }
      setTimeout(finish, 4000);
      function tileDone() {
        loaded++;
        if ( loaded >= toLoad ) finish();
      }

      for ( var dy = -HALF; dy <= HALF; dy++ ) {
        for ( var dx = -HALF; dx <= HALF; dx++ ) {
          var tx = centerXTile + dx;
          var ty = centerYTile + dy;
          var sub = subdomains[ Math.abs( tx + ty ) % subdomains.length ];
          var tileImg = document.createElement('img');
          tileImg.width = TILE;
          tileImg.height = TILE;
          tileImg.alt = '';
          tileImg.style.cssText = 'display:block;width:' + TILE + 'px;height:' + TILE + 'px;';
          toLoad++;
          tileImg.addEventListener('load', tileDone);
          tileImg.addEventListener('error', tileDone);
          tileImg.src = 'https://' + sub + '.tile.openstreetmap.org/' + zoom + '/' + tx + '/' + ty + '.png';
          grid.appendChild(tileImg);
        }
      }

      function toLocalPx( la, lo ) {
        var p = project( la, lo, zoom );
        return {
          x: ( p.x - ( centerXTile - HALF ) ) * TILE,
          y: ( p.y - ( centerYTile - HALF ) ) * TILE
        };
      }

      frame.appendChild(grid);

      if ( track && track.length > 1 ) {
        var svgNS = 'http://www.w3.org/2000/svg';
        var svg = document.createElementNS(svgNS, 'svg');
        svg.setAttribute('width', COLS * TILE);
        svg.setAttribute('height', ROWS * TILE);
        svg.style.cssText = 'position:absolute;left:0;top:0;';
        var poly = document.createElementNS(svgNS, 'polyline');
        var step = Math.max( 1, Math.floor( track.length / 400 ) );
        var coords = [];
        for ( var ti = 0; ti < track.length; ti += step ) {
          var pt = toLocalPx( track[ti][0], track[ti][1] );
          coords.push( pt.x.toFixed(1) + ',' + pt.y.toFixed(1) );
        }
        poly.setAttribute('points', coords.join(' '));
        poly.setAttribute('fill', 'none');
        poly.setAttribute('stroke', '#D97706');
        poly.setAttribute('stroke-width', '3');
        poly.setAttribute('stroke-linecap', 'round');
        poly.setAttribute('stroke-linejoin', 'round');
        svg.appendChild(poly);
        frame.appendChild(svg);
      }

      var startPt = toLocalPx( lat, lon );
      var marker = document.createElement('div');
      marker.setAttribute('aria-label', 'Point de départ');
      marker.style.cssText = 'position:absolute;left:' + ( startPt.x - 7 ) + 'px;top:' + ( startPt.y - 7 ) + 'px;width:14px;height:14px;border-radius:50%;background:#D97706;border:3px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4);';
      frame.appendChild(marker);

      mapWrap.appendChild(frame);
    }

    function loadPrintMap(callback) {
      if (mapLoaded || !mapWrap) { if (callback) callback(); return; }
      var lat = parseFloat(mapWrap.dataset.lat);
      var lon = parseFloat(mapWrap.dataset.lon);
      if (isNaN(lat) || isNaN(lon)) { if (callback) callback(); return; }
      mapLoaded = true;
      var gpxUrl = mapWrap.dataset.gpx || '';

      if ( gpxUrl ) {
        fetch(gpxUrl).then(function (r) { return r.text(); }).then(function (text) {
          var xml = new DOMParser().parseFromString(text, 'application/xml');
          var nodes = xml.getElementsByTagName('trkpt');
          var pts = [];
          for ( var i = 0; i < nodes.length; i++ ) {
            var la = parseFloat( nodes[i].getAttribute('lat') );
            var lo = parseFloat( nodes[i].getAttribute('lon') );
            if ( !isNaN(la) && !isNaN(lo) ) pts.push([ la, lo ]);
          }
          buildPrintMap( lat, lon, pts.length > 1 ? pts : null, callback );
        }).catch(function () {
          buildPrintMap( lat, lon, null, callback );
        });
      } else {
        buildPrintMap( lat, lon, null, callback );
      }
    }

    if (btn) {
      btn.addEventListener('click', function () {
        loadPrintMap(function () {
          window.print();
        });
      });
    }

    window.addEventListener('beforeprint', function () { loadPrintMap(); });
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
