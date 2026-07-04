<?php
/**
 * template-parts/modal-rando.php
 * Structure HTML du modal randonnée.
 * IDs préfixés "rando-" pour éviter tout conflit avec d'autres plugins WordPress.
 * Le contenu est injecté dynamiquement par assets/js/components/modal.js
 */
?>
<div class="rando-modal-overlay"
     id="rando-modal-overlay"
     aria-hidden="true"
     aria-modal="true"
     role="dialog"
     aria-labelledby="rando-modal-title">

  <div class="rando-modal" id="rando-modal-content" role="document">

    <!-- ══ GAUCHE : SLIDESHOW PHOTOS ══ -->
    <div class="modal-left">
      <div class="slide-title-bar">
        <h2 id="rando-modal-title"></h2>
        <div class="slide-lieu" id="rando-modal-lieu"></div>
      </div>
      <div class="slideshow" id="rando-slideshow"></div>
      <button class="slide-arrow prev" id="rando-slide-prev" aria-label="Photo précédente">‹</button>
      <button class="slide-arrow next" id="rando-slide-next" aria-label="Photo suivante">›</button>
      <div class="slide-bottom">
        <div class="slide-dots" id="rando-slide-dots"></div>
        <div class="slide-counter" id="rando-slide-counter"></div>
      </div>
    </div>

    <!-- ══ DROITE : INFORMATIONS ══ -->
    <div class="modal-right">
      <button class="modal-close" id="rando-modal-close" aria-label="Fermer">✕</button>

      <!-- Bandeau GPX -->
      <div class="modal-gpx-bar">
        <div class="gpx-meta"><strong id="rando-modal-gpx-name">Trace GPX</strong></div>
        <a id="rando-modal-gpx-link" href="#" download class="btn btn-sm" style="background:var(--orange)">
          <?php echo rando_nono_icon( 'download' ); ?> Télécharger
        </a>
      </div>

      <!-- Onglets -->
      <div class="modal-tabs" role="tablist">
        <button class="modal-tab active" data-tab="infos" role="tab" aria-selected="true">Infos</button>
        <button class="modal-tab"        data-tab="sac"   role="tab" aria-selected="false">Sac</button>
        <button class="modal-tab"        data-tab="conseils" role="tab" aria-selected="false">Conseils</button>
        <button class="modal-tab"        data-tab="meteo" role="tab" aria-selected="false">Météo</button>
      </div>

      <div class="modal-tab-content">

        <!-- Onglet Infos -->
        <div class="tab-panel active" data-panel="infos" role="tabpanel">
          <span class="info-diff-badge" id="rando-modal-diff-badge"></span>
          <div class="info-grid">
            <div class="info-card"><div class="ic-label">Distance</div><div class="ic-value" id="rando-modal-distance"></div></div>
            <div class="info-card"><div class="ic-label">Dénivelé</div><div class="ic-value" id="rando-modal-denivele"></div></div>
            <div class="info-card"><div class="ic-label">Durée</div><div class="ic-value"    id="rando-modal-duree"></div></div>
            <div class="info-card"><div class="ic-label">Date</div><div class="ic-value"     id="rando-modal-date"></div></div>
            <div class="info-card" id="rando-modal-saison-card" style="display:none"><div class="ic-label">Meilleure saison</div><div class="ic-value" id="rando-modal-saison"></div></div>
          </div>
          <div class="map-container leaflet-wrap" id="rando-modal-map" style="height:220px; border-radius:6px; overflow:hidden; margin-bottom:0.8rem;"></div>
          <div class="modal-map-actions" style="display:flex; gap:0.5rem; margin-bottom:1rem; flex-wrap:wrap;">
            <a id="rando-modal-maps-link" href="#" target="_blank" rel="noopener" class="btn btn-sm">
              <?php echo rando_nono_icon( 'map' ); ?> Activité Suunto
            </a>
            <a id="rando-modal-nav-link" href="#" target="_blank" rel="noopener" class="btn btn-sm" style="background:var(--orange); display:none;">
              &#x1F9ED; Aller au départ
            </a>
          </div>
          <div class="altitude-chart-wrap" id="rando-altitude-wrap" style="display:none; margin-bottom:1.2rem;">
            <div class="altitude-chart-title">Profil altimétrique</div>
            <div style="position:relative; height:120px;">
              <canvas id="rando-altitude-chart"></canvas>
            </div>
          </div>
          <p class="recit-text" id="rando-modal-recit"></p>
          <!-- Lien vers la fiche complète -->
          <a id="rando-modal-page-link" href="#" class="btn btn-outline" style="width:100%;justify-content:center;border-color:var(--vert);color:var(--vert);margin-bottom:0.75rem">
            📄 Voir la fiche complète
          </a>
        </div>

        <!-- Onglet Sac -->
        <div class="tab-panel" data-panel="sac" role="tabpanel">
          <ul class="sac-list" id="rando-modal-sac"></ul>
        </div>

        <!-- Onglet Conseils -->
        <div class="tab-panel" data-panel="conseils" role="tabpanel">
          <ul class="sac-list" id="rando-modal-conseils" style="grid-template-columns:1fr"></ul>
        </div>

        <!-- Onglet Météo -->
        <div class="tab-panel" data-panel="meteo" role="tabpanel">
          <div id="rando-modal-meteo">
            <p class="meteo-loading">Chargement de la météo…</p>
          </div>
        </div>

      </div><!-- /.modal-tab-content -->
    </div><!-- /.modal-right -->

  </div><!-- /.rando-modal -->
</div><!-- /.rando-modal-overlay -->
