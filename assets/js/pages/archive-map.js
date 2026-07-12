/**
 * archive-map.js — Carte d'ensemble des randonnées (page "Toutes les randonnées")
 * Affiche un pin par randonnée correspondant aux filtres actifs.
 */
(function () {
  'use strict';

  function init() {
    var el = document.getElementById('archive-map');
    if (!el || typeof L === 'undefined') return;

    var markers;
    try {
      markers = JSON.parse(el.dataset.markers || '[]');
    } catch (e) {
      markers = [];
    }
    if (!markers.length) return;

    var map = L.map(el, { zoomControl: true, scrollWheelZoom: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap', maxZoom: 18
    }).addTo(map);

    var icon = L.divIcon({
      className: '',
      html: '<div style="background:#2E5E3B;width:14px;height:14px;border-radius:50%;border:3px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)"></div>',
      iconSize: [14, 14], iconAnchor: [7, 7]
    });

    var bounds = [];
    markers.forEach(function (m) {
      if (isNaN(m.lat) || isNaN(m.lon)) return;
      bounds.push([m.lat, m.lon]);

      var popupHtml = '<div class="archive-map-popup">' +
        (m.thumb ? '<img src="' + m.thumb + '" alt="' + esc(m.titre) + '" loading="lazy" decoding="async">' : '') +
        '<div class="amp-body">' +
          '<div class="amp-titre">' + esc(m.titre) + '</div>' +
          '<div class="amp-meta">' + esc(m.lieu) + (m.distance ? ' · ' + esc(m.distance) : '') + '</div>' +
          '<a href="' + m.url + '" class="amp-link">Voir la fiche</a>' +
        '</div>' +
      '</div>';

      L.marker([m.lat, m.lon], { icon: icon }).addTo(map).bindPopup(popupHtml);
    });

    if (bounds.length === 1) {
      map.setView(bounds[0], 11);
    } else if (bounds.length > 1) {
      map.fitBounds(bounds, { padding: [30, 30] });
    }

    setTimeout(function () { map.invalidateSize(); }, 300);
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
