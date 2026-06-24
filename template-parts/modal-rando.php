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
          </div>
          <div class="map-container leaflet-wrap" id="rando-modal-map" style="height:220px; border-radius:6px; overflow:hidden; margin-bottom:0.8rem;"></div>
          <div class="modal-map-actions" style="display:flex; gap:0.5rem; margin-bottom:1rem; flex-wrap:wrap;">
            <a id="rando-modal-maps-link" href="#" target="_blank" rel="noopener" class="btn btn-sm">
              <?php echo rando_nono_icon( 'map' ); ?> Ouvrir dans Maps
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

          <!-- Partage social -->
          <div class="share-bar">
            <span class="share-label">Partager</span>
            <a id="share-whatsapp" href="#" target="_blank" rel="noopener" class="share-btn share-whatsapp">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
              WhatsApp
            </a>
            <a id="share-facebook" href="#" target="_blank" rel="noopener" class="share-btn share-facebook">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
              Facebook
            </a>
            <button id="share-copy" class="share-btn share-copy">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
              <span id="share-copy-label">Copier le lien</span>
            </button>
          </div>
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
