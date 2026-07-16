/**
 * favoris.js — "Mes randos à faire"
 * Favoris stockés uniquement dans le navigateur (localStorage), sans compte.
 * Alimente les boutons cœur (cartes + fiche randonnée) et la page /favoris/.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'randoNonoFavoris';

  function getFavoris() {
    try {
      var raw = JSON.parse(localStorage.getItem(STORAGE_KEY));
      return Array.isArray(raw) ? raw : [];
    } catch (e) {
      return [];
    }
  }

  function setFavoris(list) {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(list)); } catch (e) {}
  }

  function isFavori(id) {
    return getFavoris().indexOf(id) !== -1;
  }

  function toggleFavori(id) {
    var list = getFavoris();
    var idx = list.indexOf(id);
    if (idx === -1) {
      list.push(id);
    } else {
      list.splice(idx, 1);
    }
    setFavoris(list);
    return idx === -1;
  }

  function updateBtnState(btn, active) {
    btn.classList.toggle('is-favori', active);
    btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    btn.setAttribute('aria-label', active ? 'Retirer des favoris' : 'Ajouter aux favoris');
  }

  function initButtons() {
    var buttons = document.querySelectorAll('.js-favori-btn');
    buttons.forEach(function (btn) {
      var id = parseInt(btn.dataset.id, 10);
      if (!id) return;
      updateBtnState(btn, isFavori(id));
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var added = toggleFavori(id);
        updateBtnState(btn, added);
      });
    });
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
  }

  function initFavorisPage() {
    var grid = document.getElementById('favoris-grid');
    var emptyMsg = document.getElementById('favoris-empty');
    if (!grid) return;

    var allRandos = [];
    try { allRandos = JSON.parse(grid.dataset.randos) || []; } catch (e) {}

    var favIds = getFavoris();
    var favRandos = allRandos.filter(function (r) { return favIds.indexOf(r.id) !== -1; });

    if (!favRandos.length) {
      if (emptyMsg) emptyMsg.hidden = false;
      return;
    }

    grid.innerHTML = favRandos.map(function (r) {
      var img = r.thumb
        ? '<img src="' + r.thumb + '" alt="' + esc(r.titre) + '" loading="lazy" decoding="async">'
        : '';
      var lieu = r.lieu ? '<span class="favoris-lieu">' + esc(r.lieu) + '</span>' : '';
      return (
        '<a href="' + r.url + '" class="favoris-card">' +
          '<div class="favoris-img-wrap">' + img + '</div>' +
          '<div class="favoris-info">' +
            '<span class="favoris-title">' + esc(r.titre) + '</span>' +
            lieu +
          '</div>' +
        '</a>'
      );
    }).join('');
  }

  function init() {
    initButtons();
    initFavorisPage();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
