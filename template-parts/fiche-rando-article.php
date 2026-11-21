<?php
/**
 * Fiche randonnée complète, affichée directement dans un article lié.
 *
 * Arnaud ne veut pas d'encadré résumé (« fiche en bref ») : l'article montre
 * tout de suite la fiche entière — chiffres, trace GPX, carte avec le
 * parcours, profil altimétrique, météo du lieu, sac et conseils — puis le
 * récit. Même balisage (identifiants sr-…) que la fiche randonnée, pour
 * réutiliser son script (carte Leaflet, profil, météo) sans le dupliquer.
 *
 * @param array $args { rando_id : identifiant de la randonnée liée }
 */
$rid = isset( $args['rando_id'] ) ? (int) $args['rando_id'] : 0;
if ( ! $rid || 'publish' !== get_post_status( $rid ) ) return;

$f_lieu     = get_post_meta( $rid, 'rando_lieu', true );
$f_lat      = get_post_meta( $rid, 'rando_lat', true );
$f_lon      = get_post_meta( $rid, 'rando_lon', true );
$f_gpx      = get_post_meta( $rid, 'rando_gpx_url', true );
$f_maps     = get_post_meta( $rid, 'rando_maps_url', true );
$f_sac      = array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $rid, 'rando_sac', true ) ) ) );
$f_conseils = array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $rid, 'rando_conseils', true ) ) ) );
$f_terms    = get_the_terms( $rid, 'difficulte' );
$f_chiffres = array_filter( array(
    'Distance'         => get_post_meta( $rid, 'rando_distance', true ),
    'D+'               => get_post_meta( $rid, 'rando_denivele', true ),
    'D-'               => get_post_meta( $rid, 'rando_denivele_neg', true ),
    'Durée'            => get_post_meta( $rid, 'rando_duree', true ),
    'Date'             => get_post_meta( $rid, 'rando_date', true ),
    'Meilleure saison' => get_post_meta( $rid, 'rando_meilleure_saison', true ),
) );
?>
<section class="fa-fiche" aria-labelledby="fa-fiche-titre">
  <h2 class="sr-section-title" id="fa-fiche-titre"><?php echo rando_nono_icon( 'map' ); ?> La fiche de la rando</h2>

  <?php if ( $f_terms && ! is_wp_error( $f_terms ) ) :
      $f_rang = rando_nono_difficulte_rang_ou_defaut( $f_terms[0] );
      if ( $f_rang ) : ?>
  <div class="sr-diff-jauge" role="img" aria-label="<?php echo esc_attr( sprintf( 'Difficulté : %s, niveau %d sur 5', $f_terms[0]->name, $f_rang ) ); ?>">
    <div>
      <div class="sr-diff-label">Difficult&eacute;</div>
      <a class="sr-diff-nom" href="<?php echo esc_url( get_term_link( $f_terms[0] ) ); ?>"><?php echo esc_html( $f_terms[0]->name ); ?></a>
    </div>
    <div class="sr-diff-barres" aria-hidden="true"><?php for ( $i = 1; $i <= 5; $i++ ) : ?><span class="<?php echo $i <= $f_rang ? 'on' : ''; ?>"></span><?php endfor; ?></div>
    <span class="sr-diff-n" aria-hidden="true"><?php echo esc_html( $f_rang . '/5' ); ?></span>
  </div>
  <?php endif; endif; ?>

  <?php if ( $f_chiffres ) : ?>
  <div class="sr-meta-grid">
    <?php foreach ( $f_chiffres as $f_label => $f_valeur ) : ?>
      <div class="sr-meta-card">
        <div class="sr-meta-label"><?php echo esc_html( $f_label ); ?></div>
        <div class="sr-meta-value"><?php echo esc_html( $f_valeur ); ?></div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ( $f_gpx ) : ?>
  <div class="sr-gpx-bar">
    <div class="sr-gpx-info"><?php echo rando_nono_icon( 'download' ); ?> <span>Trace GPX &mdash; <?php echo esc_html( get_the_title( $rid ) ); ?></span></div>
    <a href="<?php echo esc_url( $f_gpx ); ?>" download class="btn btn-sm sr-btn-gpx"><?php echo rando_nono_icon( 'download' ); ?> T&eacute;l&eacute;charger le GPX</a>
  </div>
  <?php endif; ?>

  <?php if ( $f_lat && $f_lon ) : ?>
  <div class="sr-map-section">
    <div class="sr-map" id="sr-map" data-lat="<?php echo esc_attr( $f_lat ); ?>" data-lon="<?php echo esc_attr( $f_lon ); ?>" data-gpx="<?php echo esc_attr( $f_gpx ); ?>"></div>
    <div class="sr-map-actions sr-no-print">
      <?php if ( $f_maps ) : ?>
        <a href="<?php echo esc_url( $f_maps ); ?>" target="_blank" rel="noopener" class="btn btn-sm sr-btn-gpx sr-btn-secondaire"><?php echo rando_nono_icon( 'map' ); ?> Voir le d&eacute;part sur la carte</a>
      <?php endif; ?>
      <a href="https://www.google.com/maps/dir/?api=1&amp;destination=<?php echo esc_attr( $f_lat ); ?>,<?php echo esc_attr( $f_lon ); ?>&amp;travelmode=driving" target="_blank" rel="noopener" class="btn btn-sm sr-btn-gpx sr-btn-secondaire"><?php echo rando_nono_icon( 'navigation' ); ?> Itin&eacute;raire vers le d&eacute;part</a>
    </div>
  </div>
  <?php endif; ?>

  <?php if ( $f_gpx ) : ?>
  <div class="sr-altitude-section" id="sr-altitude-section" style="display:none;">
    <h3 class="sr-section-title"><?php echo rando_nono_icon( 'trending-up' ); ?> Profil altim&eacute;trique</h3>
    <div class="sr-altitude-chart-wrap"><canvas id="sr-altitude-chart"></canvas></div>
  </div>
  <?php endif; ?>

  <?php if ( $f_lat && $f_lon ) : ?>
  <div class="sr-meteo-section">
    <h3 class="sr-section-title"><?php echo rando_nono_icon( 'thermometer' ); ?> M&eacute;t&eacute;o sur place</h3>
    <div id="sr-meteo" data-lat="<?php echo esc_attr( $f_lat ); ?>" data-lon="<?php echo esc_attr( $f_lon ); ?>" data-lieu="<?php echo esc_attr( rando_nono_lieu_court( $rid, 60 ) ?: $f_lieu ); ?>" aria-busy="true" aria-label="Chargement de la m&eacute;t&eacute;o"></div>
  </div>
  <?php endif; ?>

  <?php if ( $f_sac ) : ?>
  <div class="sr-sac-section">
    <h3 class="sr-section-title"><?php echo rando_nono_icon( 'backpack' ); ?> Dans le sac</h3>
    <ul class="sr-sac-list"><?php foreach ( $f_sac as $f_item ) : ?><li><?php echo esc_html( $f_item ); ?></li><?php endforeach; ?></ul>
  </div>
  <?php endif; ?>

  <?php if ( $f_conseils ) : ?>
  <div class="sr-conseils-section">
    <h3 class="sr-section-title"><?php echo rando_nono_icon( 'lightbulb' ); ?> Conseils</h3>
    <ul class="sr-conseils-list"><?php foreach ( $f_conseils as $f_c ) : ?><li><?php echo esc_html( $f_c ); ?></li><?php endforeach; ?></ul>
  </div>
  <?php endif; ?>

  <p class="fa-fiche-lien"><a href="<?php echo esc_url( get_permalink( $rid ) ); ?>">Photos, avis et suivi GPS sur la fiche compl&egrave;te &rarr;</a></p>
</section>
