<?php
/**
 * template-parts/matos-panel.php
 * Panneau latéral de détail d'un matériel.
 * Le contenu est injecté dynamiquement par assets/js/components/matos.js
 */
?>
<div class="matos-panel-overlay" id="matos-panel-overlay" aria-hidden="true" role="dialog" aria-labelledby="matos-panel-name">
  <div class="matos-panel" id="matos-panel">

    <div class="matos-panel-img" id="matos-panel-img">
      <img src="" alt="">
      <button class="matos-panel-close" id="matos-panel-close" aria-label="Fermer">✕</button>
    </div>

    <div class="matos-panel-body">
      <div class="matos-panel-cat" id="matos-panel-cat"></div>
      <h3 class="matos-panel-name" id="matos-panel-name"></h3>
      <div class="matos-panel-divider"></div>
      <p class="matos-panel-desc" id="matos-panel-desc"></p>

      <div class="matos-panel-pourquoi">
        <span class="pourquoi-icon"><?php echo rando_nono_icon( 'check' ); ?></span>
        <p></p>
      </div>

      <a href="#" class="matos-panel-link" target="_blank" rel="noopener nofollow">
        <?php echo rando_nono_icon( 'arrow-right' ); ?> Voir le produit
      </a>
    </div>

  </div>
</div>
