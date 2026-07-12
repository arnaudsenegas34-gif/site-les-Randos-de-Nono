/**
 * live-tracking.js — Démarrer et suivre une randonnée en direct depuis le téléphone
 * (position GPS, distance parcourue, temps, allure, dénivelé, trace sur la carte)
 */
(function () {
  'use strict';

  var MIN_ACCURACY = 30;      // m — au-delà, le point GPS est jugé trop imprécis
  var MAX_SPEED_MS = 8;       // m/s (~29 km/h) — au-delà, on suppose un saut GPS
  var SAVE_EVERY_N = 3;       // sauvegarde localStorage tous les N points

  var els = {};
  var randoId = null;
  var storageKey = null;

  var state = null;
  function freshState() {
    return {
      status: 'idle',          // idle | tracking | paused | stopped
      watchId: null,
      timerId: null,
      startedAt: null,
      pausedAt: null,
      pausedTotal: 0,
      points: [],
      elevationGain: 0,
      wakeLock: null,
      polyline: null,
      meMarker: null,
      saveCounter: 0
    };
  }

  function init() {
    els.startBtn = document.getElementById('sr-track-start');
    if (!els.startBtn) return;

    randoId = els.startBtn.dataset.randoId || 'rando';
    storageKey = 'srTrack_' + randoId;
    state = freshState();

    els.hint            = document.getElementById('sr-tracking-hint');
    els.bar            = document.getElementById('sr-track-bar');
    els.time           = document.getElementById('sr-track-time');
    els.distance       = document.getElementById('sr-track-distance');
    els.pace           = document.getElementById('sr-track-pace');
    els.elevation      = document.getElementById('sr-track-elevation');
    els.status         = document.getElementById('sr-track-status');
    els.pauseBtn       = document.getElementById('sr-track-pause');
    els.stopBtn        = document.getElementById('sr-track-stop');
    els.recap          = document.getElementById('sr-track-recap');
    els.recapTime      = document.getElementById('sr-recap-time');
    els.recapDistance  = document.getElementById('sr-recap-distance');
    els.recapPace      = document.getElementById('sr-recap-pace');
    els.recapElevation = document.getElementById('sr-recap-elevation');
    els.recapDownload  = document.getElementById('sr-recap-download');
    els.recapRestart   = document.getElementById('sr-recap-restart');

    els.startBtn.addEventListener('click', function () { requestStart(false); });
    els.pauseBtn.addEventListener('click', togglePause);
    els.stopBtn.addEventListener('click', stopTracking);
    els.recapDownload.addEventListener('click', downloadGpx);
    els.recapRestart.addEventListener('click', resetToIdle);

    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'visible' && state.status === 'tracking') {
        acquireWakeLock();
      }
    });

    maybeOfferResume();
  }

  /* ── Reprise d'une session laissée en cours ── */
  function maybeOfferResume() {
    var saved = loadSaved();
    if (!saved || (saved.status !== 'tracking' && saved.status !== 'paused')) return;

    var when = saved.startedAt ? new Date(saved.startedAt).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : '';
    var resume = window.confirm(
      'Une randonnée en cours a été détectée' + (when ? ' (démarrée à ' + when + ')' : '') + '.\nVoulez-vous reprendre le suivi ?'
    );

    if (!resume) {
      clearSaved();
      return;
    }

    state.startedAt     = saved.startedAt;
    state.pausedTotal    = saved.pausedTotal || 0;
    state.pausedAt        = saved.pausedAt || null;
    state.points          = saved.points || [];
    state.elevationGain   = saved.elevationGain || 0;

    hideStartControls();
    showBar();
    redrawPath();
    updateDisplay();
    document.body.classList.add('sr-tracking-active');

    if (saved.status === 'paused') {
      state.status = 'paused';
      setPausedUi(true);
      persist();
    } else {
      requestStart(true);
    }
  }

  /* ── Démarrage ── */
  function requestStart(resumed) {
    if (!('geolocation' in navigator)) {
      setStatus('La géolocalisation n’est pas disponible sur cet appareil.');
      return;
    }

    if (!resumed) {
      state = freshState();
      state.startedAt = Date.now();
    }
    state.status = 'tracking';

    hideStartControls();
    showBar();
    setPausedUi(false);
    setStatus('Recherche du signal GPS…');
    acquireWakeLock();

    state.watchId = navigator.geolocation.watchPosition(onPosition, onPositionError, {
      enableHighAccuracy: true,
      maximumAge: 1000,
      timeout: 20000
    });

    state.timerId = window.setInterval(updateDisplay, 1000);
    document.body.classList.add('sr-tracking-active');
    persist();
  }

  function onPosition(pos) {
    var c = pos.coords;
    setStatus('Signal GPS actif' + (c.accuracy ? ' — précision ±' + Math.round(c.accuracy) + ' m' : ''));

    var point = { lat: c.latitude, lon: c.longitude, alt: c.altitude, acc: c.accuracy, t: pos.timestamp || Date.now() };

    var last = state.points.length ? state.points[state.points.length - 1] : null;
    var keep = true;

    if (c.accuracy && c.accuracy > MIN_ACCURACY) keep = false;

    if (keep && last) {
      var dt = (point.t - last.t) / 1000;
      var d = haversine(last.lat, last.lon, point.lat, point.lon);
      if (dt > 0 && d / dt > MAX_SPEED_MS) keep = false;
      if (keep) {
        if (typeof point.alt === 'number' && typeof last.alt === 'number') {
          var delta = point.alt - last.alt;
          if (delta > 0.5) state.elevationGain += delta;
        }
      }
    }

    if (keep) {
      state.points.push(point);
      state.saveCounter++;
      redrawPath();
      if (state.saveCounter % SAVE_EVERY_N === 0) persist();
    } else {
      moveMeMarker(point.lat, point.lon);
    }

    updateDisplay();
  }

  function onPositionError(err) {
    var msg = 'Position indisponible.';
    if (err.code === err.PERMISSION_DENIED) {
      msg = 'Accès à la position refusé. Autorisez la géolocalisation pour suivre la randonnée.';
    } else if (err.code === err.TIMEOUT) {
      msg = 'Signal GPS perdu, nouvelle tentative…';
    }
    setStatus(msg);
  }

  /* ── Pause / reprise ── */
  function togglePause() {
    if (state.status === 'tracking') {
      if (state.watchId !== null) navigator.geolocation.clearWatch(state.watchId);
      state.watchId = null;
      state.status = 'paused';
      state.pausedAt = Date.now();
      if (state.timerId) window.clearInterval(state.timerId);
      releaseWakeLock();
      setPausedUi(true);
      setStatus('Suivi en pause.');
      persist();
    } else if (state.status === 'paused') {
      state.pausedTotal += Date.now() - state.pausedAt;
      state.pausedAt = null;
      state.status = 'tracking';
      setPausedUi(false);
      acquireWakeLock();
      state.watchId = navigator.geolocation.watchPosition(onPosition, onPositionError, {
        enableHighAccuracy: true,
        maximumAge: 1000,
        timeout: 20000
      });
      state.timerId = window.setInterval(updateDisplay, 1000);
      setStatus('Suivi repris.');
      persist();
    }
  }

  function setPausedUi(paused) {
    els.bar.classList.toggle('is-paused', paused);
    els.pauseBtn.classList.toggle('is-resume', paused);
    els.pauseBtn.textContent = paused ? '▶ Reprendre' : '❙❙ Pause';
  }

  /* ── Arrêt ── */
  function stopTracking() {
    if (state.watchId !== null) navigator.geolocation.clearWatch(state.watchId);
    if (state.timerId) window.clearInterval(state.timerId);
    state.watchId = null;
    state.timerId = null;
    state.status = 'stopped';
    releaseWakeLock();
    document.body.classList.remove('sr-tracking-active');

    els.bar.hidden = true;
    showRecap();
    clearSaved();
  }

  function resetToIdle() {
    if (state.polyline && window.srMap) window.srMap.removeLayer(state.polyline);
    if (state.meMarker && window.srMap) window.srMap.removeLayer(state.meMarker);
    state = freshState();
    els.recap.hidden = true;
    showStartControls();
    setStatus('');
  }

  function hideStartControls() {
    els.startBtn.hidden = true;
    if (els.hint) els.hint.hidden = true;
  }

  function showStartControls() {
    els.startBtn.hidden = false;
    if (els.hint) els.hint.hidden = false;
  }

  /* ── Affichage ── */
  function showBar() {
    els.bar.hidden = false;
  }

  function showRecap() {
    var elapsed = elapsedMs();
    var distanceKm = totalDistanceKm();

    els.recapTime.textContent = formatTime(elapsed);
    els.recapDistance.textContent = formatDistance(distanceKm);
    els.recapPace.textContent = formatPace(elapsed, distanceKm);
    els.recapElevation.textContent = state.elevationGain > 0 ? Math.round(state.elevationGain) + ' m' : '–';

    els.recap.hidden = false;
    els.recap.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function updateDisplay() {
    if (!state.startedAt) return;
    var elapsed = elapsedMs();
    var distanceKm = totalDistanceKm();

    els.time.textContent = formatTime(elapsed);
    els.distance.textContent = formatDistance(distanceKm);
    els.pace.textContent = formatPace(elapsed, distanceKm);
    els.elevation.textContent = state.elevationGain > 0 ? Math.round(state.elevationGain) + ' m' : '––';
  }

  function setStatus(msg) {
    if (els.status) els.status.textContent = msg;
  }

  function elapsedMs() {
    if (!state.startedAt) return 0;
    var end = state.status === 'paused' && state.pausedAt ? state.pausedAt : Date.now();
    return Math.max(0, end - state.startedAt - state.pausedTotal);
  }

  function totalDistanceKm() {
    var total = 0;
    for (var i = 1; i < state.points.length; i++) {
      total += haversine(state.points[i - 1].lat, state.points[i - 1].lon, state.points[i].lat, state.points[i].lon);
    }
    return total / 1000;
  }

  function formatTime(ms) {
    var totalSec = Math.floor(ms / 1000);
    var h = Math.floor(totalSec / 3600);
    var m = Math.floor((totalSec % 3600) / 60);
    var s = totalSec % 60;
    return [h, m, s].map(function (n) { return String(n).padStart(2, '0'); }).join(':');
  }

  function formatDistance(km) {
    return km.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' km';
  }

  function formatPace(ms, km) {
    if (km < 0.1) return '––';
    var minPerKm = (ms / 60000) / km;
    var min = Math.floor(minPerKm);
    var sec = Math.round((minPerKm - min) * 60);
    if (sec === 60) { min++; sec = 0; }
    return min + '’' + String(sec).padStart(2, '0') + '/km';
  }

  /* ── Carte Leaflet ── */
  function redrawPath() {
    if (typeof L === 'undefined' || !window.srMap || !state.points.length) return;
    var latlngs = state.points.map(function (p) { return [p.lat, p.lon]; });

    if (!state.polyline) {
      state.polyline = L.polyline(latlngs, { color: '#1E88E5', weight: 4, opacity: 0.85 }).addTo(window.srMap);
    } else {
      state.polyline.setLatLngs(latlngs);
    }

    var last = state.points[state.points.length - 1];
    moveMeMarker(last.lat, last.lon);
  }

  function moveMeMarker(lat, lon) {
    if (typeof L === 'undefined' || !window.srMap) return;
    if (!state.meMarker) {
      var icon = L.divIcon({ className: '', html: '<div class="sr-track-me-marker"></div>', iconSize: [16, 16], iconAnchor: [8, 8] });
      state.meMarker = L.marker([lat, lon], { icon: icon, zIndexOffset: 1000 }).bindTooltip('Vous êtes ici').addTo(window.srMap);
    } else {
      state.meMarker.setLatLng([lat, lon]);
    }
    window.srMap.panTo([lat, lon], { animate: true });
  }

  /* ── Wake Lock (garde l'écran allumé pendant le suivi) ── */
  function acquireWakeLock() {
    if (!('wakeLock' in navigator)) return;
    navigator.wakeLock.request('screen').then(function (lock) {
      state.wakeLock = lock;
    }).catch(function () { /* refusé ou indisponible — pas bloquant */ });
  }

  function releaseWakeLock() {
    if (state.wakeLock) {
      state.wakeLock.release().catch(function () {});
      state.wakeLock = null;
    }
  }

  /* ── Persistance locale (résiste au verrouillage / rechargement du téléphone) ── */
  function persist() {
    try {
      window.localStorage.setItem(storageKey, JSON.stringify({
        status: state.status,
        startedAt: state.startedAt,
        pausedTotal: state.pausedTotal,
        pausedAt: state.pausedAt,
        points: state.points,
        elevationGain: state.elevationGain
      }));
    } catch (e) { /* stockage indisponible — le suivi continue sans persistance */ }
  }

  function loadSaved() {
    try {
      var raw = window.localStorage.getItem(storageKey);
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  }

  function clearSaved() {
    try { window.localStorage.removeItem(storageKey); } catch (e) {}
  }

  /* ── Export GPX de la trace enregistrée ── */
  function downloadGpx() {
    if (!state.points.length) return;
    var title = els.startBtn.dataset.randoTitle || 'randonnee';
    var trkpts = state.points.map(function (p) {
      var ele = typeof p.alt === 'number' ? '<ele>' + p.alt.toFixed(1) + '</ele>' : '';
      return '<trkpt lat="' + p.lat + '" lon="' + p.lon + '">' + ele + '<time>' + new Date(p.t).toISOString() + '</time></trkpt>';
    }).join('');

    var gpx = '<?xml version="1.0" encoding="UTF-8"?>' +
      '<gpx version="1.1" creator="Les Randos de Nono" xmlns="http://www.topografix.com/GPX/1/1">' +
      '<trk><name>' + escapeXml(title) + ' — ma trace</name><trkseg>' + trkpts + '</trkseg></trk></gpx>';

    var blob = new Blob([gpx], { type: 'application/gpx+xml' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = slugify(title) + '-ma-trace.gpx';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  function escapeXml(s) {
    return String(s).replace(/[<>&'"]/g, function (c) {
      return { '<': '&lt;', '>': '&gt;', '&': '&amp;', '\'': '&apos;', '"': '&quot;' }[c];
    });
  }

  function slugify(s) {
    return String(s).toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '') || 'rando';
  }

  /* ── Distance Haversine (mètres) ── */
  function haversine(lat1, lon1, lat2, lon2) {
    var R = 6371000;
    var dLat = toRad(lat2 - lat1);
    var dLon = toRad(lon2 - lon1);
    var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
      Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
      Math.sin(dLon / 2) * Math.sin(dLon / 2);
    return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
  }

  function toRad(deg) { return deg * Math.PI / 180; }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
