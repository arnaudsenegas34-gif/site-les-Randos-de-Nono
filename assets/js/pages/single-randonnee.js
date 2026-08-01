/**
 * single-randonnee.js — Fiche complète d'une randonnée
 */
(function () {
  'use strict';

  function isDarkTheme() {
    var t = document.documentElement.getAttribute('data-theme');
    if (t === 'dark') return true;
    if (t === 'light') return false;
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
  }

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
        buildAltitudeChart(e.target);
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

  /* ── Profil altimétrique (Chart.js), à partir des points du GPX déjà chargé pour la carte ── */
  function buildAltitudeChart(gpxLayer) {
    var section = document.getElementById('sr-altitude-section');
    var canvas = document.getElementById('sr-altitude-chart');
    if (!section || !canvas || typeof Chart === 'undefined') return;

    var points = [];
    gpxLayer.getLayers().forEach(function (layer) {
      if (layer.getLatLngs) {
        var lls = layer.getLatLngs();
        var flat = Array.isArray(lls[0]) ? lls[0] : lls;
        flat.forEach(function (ll) {
          var ele = (ll.meta && ll.meta.ele != null) ? ll.meta.ele : ll.alt;
          if (ele !== undefined && ele !== null) points.push({ alt: ele, lat: ll.lat, lng: ll.lng });
        });
      }
    });

    if (points.length < 2) return;

    var labels = [], alts = [], cumDist = 0;
    for (var i = 0; i < points.length; i++) {
      if (i > 0) {
        var prev = points[i - 1], cur = points[i];
        var dLat = (cur.lat - prev.lat) * Math.PI / 180;
        var dLon = (cur.lng - prev.lng) * Math.PI / 180;
        var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(prev.lat * Math.PI / 180) * Math.cos(cur.lat * Math.PI / 180) *
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
        cumDist += 6371 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      }
      if (i % Math.max(1, Math.floor(points.length / 80)) === 0) {
        labels.push(cumDist.toFixed(1) + ' km');
        alts.push(Math.round(points[i].alt));
      }
    }

    section.style.display = '';

    var dark = isDarkTheme();
    var tickColor = dark ? '#A6AC9C' : '#888';
    var gridColor = dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';

    new Chart(canvas, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          data: alts, borderColor: '#D97706', backgroundColor: altGradient,
          fill: true, tension: 0.3, pointRadius: 0, borderWidth: 2
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (ctx) { return ctx.parsed.y + ' m'; } } } },
        scales: {
          x: { ticks: { maxTicksLimit: 8, font: { size: 10 }, color: tickColor }, grid: { display: false } },
          y: { ticks: { font: { size: 10 }, color: tickColor, callback: function (v) { return v + ' m'; } }, grid: { color: gridColor } }
        }
      }
    });
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

    /* Tuiles quasi à leur taille native (256px) pour un rendu net, et une
       grille assez compacte pour tenir dans la largeur imprimable de la page
       (~700px, cf. .sr-container : 820px max-width - 2×3rem de padding) sans
       forcer le moteur d'impression à réduire toute la page à l'échelle. */
    var TILE = 230;
    var COLS = 3, ROWS = 3;
    var MAX_ZOOM = 18;
    var TARGET_ZOOM = 14; /* en dessous, le tracé est jugé pas assez zoomé */
    var subdomains = [ 'a', 'b', 'c' ];

    function project(la, lo, zoom) {
      var n = Math.pow(2, zoom);
      var latRad = la * Math.PI / 180;
      return {
        x: ( lo + 180 ) / 360 * n,
        y: ( 1 - Math.log( Math.tan( latRad ) + 1 / Math.cos( latRad ) ) / Math.PI ) / 2 * n
      };
    }

    function bboxOf(points) {
      var minLat = points[0][0], maxLat = points[0][0], minLon = points[0][1], maxLon = points[0][1];
      for ( var i = 1; i < points.length; i++ ) {
        if ( points[i][0] < minLat ) minLat = points[i][0];
        if ( points[i][0] > maxLat ) maxLat = points[i][0];
        if ( points[i][1] < minLon ) minLon = points[i][1];
        if ( points[i][1] > maxLon ) maxLon = points[i][1];
      }
      return { minLat: minLat, maxLat: maxLat, minLon: minLon, maxLon: maxLon };
    }

    function fitZoom(box) {
      var maxAllowedW = COLS * TILE * 0.96;
      var maxAllowedH = ROWS * TILE * 0.96;
      var zoom = 8;
      for ( var z = MAX_ZOOM; z >= 8; z-- ) {
        var p1 = project( box.maxLat, box.minLon, z );
        var p2 = project( box.minLat, box.maxLon, z );
        var wPx = ( p2.x - p1.x ) * TILE;
        var hPx = ( p2.y - p1.y ) * TILE;
        if ( wPx <= maxAllowedW && hPx <= maxAllowedH ) { zoom = z; break; }
      }
      return zoom;
    }

    /* Construit un segment de carte : mosaïque de tuiles OSM (même serveur que
       la carte interactive, plus fiable qu'un service tiers de "static map"),
       avec le tracé GPX et des repères de départ/arrivée bien visibles. */
    function buildSegment(points, label, showStart, endLabel, countTile, tileDone) {
      var hasTrack = points.length > 1;
      var box = hasTrack ? bboxOf(points) : { minLat: points[0][0], maxLat: points[0][0], minLon: points[0][1], maxLon: points[0][1] };
      var centerLat = ( box.minLat + box.maxLat ) / 2;
      var centerLon = ( box.minLon + box.maxLon ) / 2;
      var zoom = hasTrack ? fitZoom(box) : TARGET_ZOOM;

      /* Centrage continu en pixels (pas sur un index de tuile entier) : le
         point/tracé reste exactement centré quelle que soit la taille de la
         grille, sans risque de déborder d'un côté à cause d'un arrondi. */
      var frameW = COLS * TILE;
      var frameH = ROWS * TILE;
      var centerP = project( centerLat, centerLon, zoom );
      var originX = centerP.x * TILE - frameW / 2;
      var originY = centerP.y * TILE - frameH / 2;

      var wrap = document.createElement('div');
      wrap.style.cssText = 'margin-bottom:1.25rem;';

      if ( label ) {
        var title = document.createElement('p');
        title.textContent = label;
        title.style.cssText = 'font-weight:600;font-size:0.85rem;margin:0 0 0.4rem;color:#1A2E1F;';
        wrap.appendChild(title);
      }

      var frame = document.createElement('div');
      frame.className = 'sr-print-map-frame';
      frame.style.cssText = 'position:relative;display:inline-block;width:' + frameW + 'px;height:' + frameH + 'px;';

      var grid = document.createElement('div');
      grid.style.cssText = 'position:absolute;left:0;top:0;width:' + frameW + 'px;height:' + frameH + 'px;overflow:hidden;';

      var txStart = Math.floor( originX / TILE );
      var txEnd = Math.floor( ( originX + frameW ) / TILE );
      var tyStart = Math.floor( originY / TILE );
      var tyEnd = Math.floor( ( originY + frameH ) / TILE );

      for ( var ty = tyStart; ty <= tyEnd; ty++ ) {
        for ( var tx = txStart; tx <= txEnd; tx++ ) {
          var sub = subdomains[ Math.abs( tx + ty ) % subdomains.length ];
          var tileImg = document.createElement('img');
          tileImg.width = TILE;
          tileImg.height = TILE;
          tileImg.alt = '';
          tileImg.style.cssText = 'position:absolute;left:' + ( tx * TILE - originX ) + 'px;top:' + ( ty * TILE - originY ) + 'px;width:' + TILE + 'px;height:' + TILE + 'px;display:block;';
          countTile();
          tileImg.addEventListener('load', tileDone);
          tileImg.addEventListener('error', tileDone);
          tileImg.src = 'https://' + sub + '.tile.openstreetmap.org/' + zoom + '/' + tx + '/' + ty + '.png';
          grid.appendChild(tileImg);
        }
      }

      function toLocalPx( la, lo ) {
        var p = project( la, lo, zoom );
        return {
          x: p.x * TILE - originX,
          y: p.y * TILE - originY
        };
      }

      frame.appendChild(grid);

      if ( hasTrack ) {
        var svgNS = 'http://www.w3.org/2000/svg';
        var svg = document.createElementNS(svgNS, 'svg');
        svg.setAttribute('width', COLS * TILE);
        svg.setAttribute('height', ROWS * TILE);
        svg.style.cssText = 'position:absolute;left:0;top:0;';
        var poly = document.createElementNS(svgNS, 'polyline');
        var step = Math.max( 1, Math.floor( points.length / 500 ) );
        var coords = [];
        for ( var ti = 0; ti < points.length; ti += step ) {
          var pt = toLocalPx( points[ti][0], points[ti][1] );
          coords.push( pt.x.toFixed(1) + ',' + pt.y.toFixed(1) );
        }
        var lastIdx = points.length - 1;
        if ( ( lastIdx % step ) !== 0 ) {
          var lastPt = toLocalPx( points[lastIdx][0], points[lastIdx][1] );
          coords.push( lastPt.x.toFixed(1) + ',' + lastPt.y.toFixed(1) );
        }
        poly.setAttribute('points', coords.join(' '));
        poly.setAttribute('fill', 'none');
        poly.setAttribute('stroke', '#D97706');
        poly.setAttribute('stroke-width', '4');
        poly.setAttribute('stroke-linecap', 'round');
        poly.setAttribute('stroke-linejoin', 'round');
        svg.appendChild(poly);
        frame.appendChild(svg);
      }

      /* Repère rond, gros, contrasté : la précision (centre exact du point
         GPS) prime sur la forme — pas d'icône en goutte dont la pointe
         pourrait être mal alignée. */
      function addPin( la, lo, color, text ) {
        var p = toLocalPx( la, lo );
        var dot = document.createElement('div');
        dot.style.cssText = 'position:absolute;left:' + ( p.x - 11 ) + 'px;top:' + ( p.y - 11 ) + 'px;width:22px;height:22px;border-radius:50%;background:' + color + ';border:4px solid #fff;box-shadow:0 1px 6px rgba(0,0,0,.55);z-index:2;';
        frame.appendChild(dot);
        if ( text ) {
          var tag = document.createElement('span');
          tag.textContent = text;
          tag.style.cssText = 'position:absolute;left:' + p.x + 'px;top:' + ( p.y - 34 ) + 'px;transform:translateX(-50%);background:#fff;color:#1A2E1F;font-size:12px;font-weight:700;padding:2px 8px;border-radius:4px;box-shadow:0 1px 4px rgba(0,0,0,.4);white-space:nowrap;z-index:3;';
          frame.appendChild(tag);
        }
      }

      if ( showStart ) addPin( points[0][0], points[0][1], '#2E5E3B', 'Départ' );
      if ( endLabel && hasTrack ) addPin( points[points.length - 1][0], points[points.length - 1][1], '#D97706', endLabel );

      wrap.appendChild(frame);
      return wrap;
    }

    function firstMatching( xml, localName ) {
      /* getElementsByTagNameNS('*', …) trouve l'élément quel que soit le
         préfixe de namespace utilisé par l'exporteur GPX (ex: <gpx:trkpt>),
         contrairement à getElementsByTagName qui exige le nom exact. */
      var nodes = xml.getElementsByTagNameNS( '*', localName );
      return nodes.length ? nodes : xml.getElementsByTagName( localName );
    }

    function loadPrintMap(callback) {
      if (mapLoaded || !mapWrap) { if (callback) callback(); return; }
      var lat = parseFloat(mapWrap.dataset.lat);
      var lon = parseFloat(mapWrap.dataset.lon);
      if (isNaN(lat) || isNaN(lon)) { if (callback) callback(); return; }
      mapLoaded = true;
      var gpxUrl = mapWrap.dataset.gpx || '';

      function render(points, gpxFailed) {
        var toLoad = 0, loaded = 0, done = false;
        function finish() {
          if ( done ) return;
          done = true;
          if ( callback ) callback();
        }
        setTimeout(finish, 5000);
        function countTile() { toLoad++; }
        function tileDone() {
          loaded++;
          if ( loaded >= toLoad ) finish();
        }

        if ( !points || points.length < 2 ) {
          var seg = buildSegment( [ [ lat, lon ] ], null, true, null, countTile, tileDone );
          if ( gpxFailed ) {
            var warn = document.createElement('p');
            warn.textContent = 'Trace GPX indisponible pour l\'impression (fichier introuvable ou illisible) — seul le point de départ est affiché.';
            warn.style.cssText = 'font-size:0.75rem;color:#9a3b12;margin-top:0.4rem;';
            seg.appendChild(warn);
          }
          mapWrap.appendChild( seg );
        } else {
          var fullZoom = fitZoom( bboxOf(points) );
          if ( fullZoom >= TARGET_ZOOM ) {
            mapWrap.appendChild( buildSegment( points, null, true, 'Arrivée', countTile, tileDone ) );
          } else {
            var mid = Math.floor( points.length / 2 );
            var part1 = points.slice( 0, mid + 1 );
            var part2 = points.slice( mid );
            var seg1 = buildSegment( part1, 'Trace GPX — partie 1/2', true, 'Suite →', countTile, tileDone );
            var seg2 = buildSegment( part2, 'Trace GPX — partie 2/2', false, 'Arrivée', countTile, tileDone );
            seg2.style.pageBreakBefore = 'always';
            seg2.style.breakBefore = 'page';
            mapWrap.appendChild(seg1);
            mapWrap.appendChild(seg2);
          }
        }

        if ( toLoad === 0 ) finish();
      }

      if ( gpxUrl ) {
        fetch(gpxUrl).then(function (r) {
          if ( !r.ok ) throw new Error( 'HTTP ' + r.status + ' en récupérant le GPX' );
          return r.text();
        }).then(function (text) {
          var xml = new DOMParser().parseFromString(text, 'application/xml');
          if ( xml.getElementsByTagName('parsererror').length ) {
            console.warn( '[Fiche imprimable] Le fichier GPX n\'a pas pu être analysé (XML invalide) :', gpxUrl );
            render(null, true);
            return;
          }
          var nodes = firstMatching( xml, 'trkpt' );
          if ( !nodes.length ) nodes = firstMatching( xml, 'rtept' ); /* GPX "route" sans track */
          var pts = [];
          for ( var i = 0; i < nodes.length; i++ ) {
            var la = parseFloat( nodes[i].getAttribute('lat') );
            var lo = parseFloat( nodes[i].getAttribute('lon') );
            if ( !isNaN(la) && !isNaN(lo) ) pts.push([ la, lo ]);
          }
          if ( pts.length < 2 ) {
            console.warn( '[Fiche imprimable] Aucun point <trkpt>/<rtept> exploitable trouvé dans le GPX, le tracé ne sera pas affiché :', gpxUrl );
          }
          render( pts.length > 1 ? pts : null, pts.length < 2 );
        }).catch(function (err) {
          console.warn( '[Fiche imprimable] Impossible de charger le GPX (réseau ou CORS), le tracé ne sera pas affiché :', gpxUrl, err );
          render(null, true);
        });
      } else {
        console.info( '[Fiche imprimable] Aucune URL de GPX renseignée pour cette randonnée : seul le point de départ sera affiché.' );
        render(null, false);
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

  function altGradient(context) {
    var chart = context.chart, chartArea = chart.chartArea;
    if (!chartArea) return null;
    var gradient = chart.ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
    gradient.addColorStop(0, 'rgba(217,119,6,0.35)');
    gradient.addColorStop(1, 'rgba(217,119,6,0)');
    return gradient;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
