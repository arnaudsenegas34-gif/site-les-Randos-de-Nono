<?php
get_header();

while ( have_posts() ) : the_post();

$id           = get_the_ID();
$lieu         = get_post_meta( $id, 'rando_lieu', true );
$lat          = get_post_meta( $id, 'rando_lat', true );
$lon          = get_post_meta( $id, 'rando_lon', true );
$distance     = get_post_meta( $id, 'rando_distance', true );
$denivele     = get_post_meta( $id, 'rando_denivele', true );
$denivele_neg = get_post_meta( $id, 'rando_denivele_neg', true );
$duree        = get_post_meta( $id, 'rando_duree', true );
$date_sortie       = get_post_meta( $id, 'rando_date', true );
$meilleure_saison  = get_post_meta( $id, 'rando_meilleure_saison', true );
$maps_url          = get_post_meta( $id, 'rando_maps_url', true );
$gpx_url      = get_post_meta( $id, 'rando_gpx_url', true );
$photos_raw   = get_post_meta( $id, 'rando_photos', true );
$sac_raw      = get_post_meta( $id, 'rando_sac', true );
$conseils_raw = get_post_meta( $id, 'rando_conseils', true );

$diff_terms = get_the_terms( $id, 'difficulte' );
$difficulte = $diff_terms && ! is_wp_error( $diff_terms ) ? strtolower( $diff_terms[0]->name ) : 'moyen';

$photos_urls = array();
if ( $photos_raw ) {
    $ids = array_map( 'trim', explode( ',', $photos_raw ) );
    foreach ( $ids as $photo_id ) {
        $url = wp_get_attachment_image_url( intval( $photo_id ), 'large' );
        if ( $url ) $photos_urls[] = $url;
    }
}
$sac_items     = $sac_raw ? array_filter( array_map( 'trim', explode( "\n", $sac_raw ) ) ) : array();
$conseils_items = $conseils_raw ? array_filter( array_map( 'trim', explode( "\n", $conseils_raw ) ) ) : array();

$thumb_large = get_the_post_thumbnail_url( $id, 'full' );

$diff_classes = array( 'facile' => 'diff-facile', 'moyen' => 'diff-moyen', 'difficile' => 'diff-difficile' );
$diff_class   = isset( $diff_classes[ $difficulte ] ) ? $diff_classes[ $difficulte ] : 'diff-moyen';
?>

<?php rando_nono_breadcrumb(); ?>

<!-- HERO -->
<section class="sr-hero">
  <?php if ( $thumb_large ) : ?>
    <img class="sr-hero-img" src="<?php echo esc_url( $thumb_large ); ?>" alt="<?php the_title_attribute(); ?>">
  <?php else : ?>
    <div class="sr-hero-img sr-hero-placeholder"></div>
  <?php endif; ?>
  <div class="sr-hero-overlay">
    <div class="sr-hero-content">
      <span class="sr-hero-badge sr-<?php echo esc_attr( $diff_class ); ?>"><?php echo esc_html( ucfirst( $difficulte ) ); ?></span>
      <h1 class="sr-hero-title"><?php the_title(); ?></h1>
      <?php if ( $lieu ) : ?>
        <div class="sr-hero-lieu"><?php echo rando_nono_icon( 'pin' ); ?> <?php echo esc_html( $lieu ); ?></div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- META GRID -->
<section class="sr-section">
  <div class="sr-container">
    <div class="sr-meta-grid">
      <?php if ( $distance ) : ?>
      <div class="sr-meta-card">
        <div class="sr-meta-icon"><?php echo rando_nono_icon( 'ruler' ); ?></div>
        <div class="sr-meta-label">Distance</div>
        <div class="sr-meta-value"><?php echo esc_html( $distance ); ?></div>
      </div>
      <?php endif; ?>
      <?php if ( $denivele ) : ?>
      <div class="sr-meta-card">
        <div class="sr-meta-icon"><?php echo rando_nono_icon( 'trending-up' ); ?></div>
        <div class="sr-meta-label">D+</div>
        <div class="sr-meta-value"><?php echo esc_html( $denivele ); ?></div>
      </div>
      <?php endif; ?>
      <?php if ( $denivele_neg ) : ?>
      <div class="sr-meta-card">
        <div class="sr-meta-icon"><?php echo rando_nono_icon( 'trending-up' ); ?></div>
        <div class="sr-meta-label">D-</div>
        <div class="sr-meta-value"><?php echo esc_html( $denivele_neg ); ?></div>
      </div>
      <?php endif; ?>
      <?php if ( $duree ) : ?>
      <div class="sr-meta-card">
        <div class="sr-meta-icon"><?php echo rando_nono_icon( 'clock' ); ?></div>
        <div class="sr-meta-label">Dur&eacute;e</div>
        <div class="sr-meta-value"><?php echo esc_html( $duree ); ?></div>
      </div>
      <?php endif; ?>
      <?php if ( $date_sortie ) : ?>
      <div class="sr-meta-card">
        <div class="sr-meta-icon"><?php echo rando_nono_icon( 'calendar' ); ?></div>
        <div class="sr-meta-label">Date</div>
        <div class="sr-meta-value"><?php echo esc_html( $date_sortie ); ?></div>
      </div>
      <?php endif; ?>
      <?php if ( $meilleure_saison ) : ?>
      <div class="sr-meta-card sr-meta-card-saison">
        <div class="sr-meta-icon"><?php echo rando_nono_icon( 'thermometer' ); ?></div>
        <div class="sr-meta-label">Meilleure saison</div>
        <div class="sr-meta-value"><?php echo esc_html( $meilleure_saison ); ?></div>
      </div>
      <?php endif; ?>
    </div>

    <!-- CONTENU PRINCIPAL -->
    <?php if ( get_the_content() ) : ?>
    <div class="sr-content">
      <?php the_content(); ?>
    </div>
    <?php endif; ?>

    <!-- BOUTON GPX -->
    <?php if ( $gpx_url ) : ?>
    <div class="sr-gpx-bar">
      <div class="sr-gpx-info">
        <?php echo rando_nono_icon( 'download' ); ?>
        <span>Trace GPX &mdash; <?php echo esc_html( get_the_title() ); ?></span>
      </div>
      <a href="<?php echo esc_url( $gpx_url ); ?>" download class="btn btn-sm sr-btn-gpx">
        <?php echo rando_nono_icon( 'download' ); ?> T&eacute;l&eacute;charger le GPX
      </a>
    </div>
    <?php endif; ?>

    <!-- CARTE OSM -->
    <?php if ( $lat && $lon ) : ?>
    <div class="sr-map-section">
      <h2 class="sr-section-title"><?php echo rando_nono_icon( 'map' ); ?> Localisation</h2>
      <div class="sr-map" id="sr-map"
           data-lat="<?php echo esc_attr( $lat ); ?>"
           data-lon="<?php echo esc_attr( $lon ); ?>"
           data-gpx="<?php echo esc_attr( $gpx_url ); ?>"></div>
      <div class="sr-map-actions">
        <?php if ( $maps_url ) : ?>
          <a href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener" class="btn btn-sm">
            <?php echo rando_nono_icon( 'map' ); ?> Ouvrir dans Maps
          </a>
        <?php endif; ?>
        <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo esc_attr( $lat ); ?>,<?php echo esc_attr( $lon ); ?>&travelmode=driving" target="_blank" rel="noopener" class="btn btn-sm sr-btn-gpx">
          Aller au d&eacute;part
        </a>
      </div>
    </div>
    <?php endif; ?>

    <!-- GALERIE PHOTOS -->
    <?php if ( ! empty( $photos_urls ) ) : ?>
    <div class="sr-photos-section">
      <h2 class="sr-section-title">Photos</h2>
      <div class="sr-photos-grid">
        <?php foreach ( $photos_urls as $i => $photo_url ) : ?>
          <img class="sr-photo" src="<?php echo esc_url( $photo_url ); ?>" alt="<?php echo esc_attr( get_the_title() . ' - photo ' . ( $i + 1 ) ); ?>" data-index="<?php echo intval( $i ); ?>">
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- METEO -->
    <?php if ( $lat && $lon ) : ?>
    <div class="sr-meteo-section">
      <h2 class="sr-section-title"><?php echo rando_nono_icon( 'thermometer' ); ?> M&eacute;t&eacute;o en temps r&eacute;el</h2>
      <div id="sr-meteo" data-lat="<?php echo esc_attr( $lat ); ?>" data-lon="<?php echo esc_attr( $lon ); ?>" data-lieu="<?php echo esc_attr( $lieu ); ?>">
        <p class="meteo-loading">Chargement de la m&eacute;t&eacute;o&hellip;</p>
      </div>
    </div>
    <?php endif; ?>

    <!-- SAC -->
    <?php if ( ! empty( $sac_items ) ) : ?>
    <div class="sr-sac-section">
      <h2 class="sr-section-title"><?php echo rando_nono_icon( 'backpack' ); ?> Dans le sac</h2>
      <ul class="sr-sac-list">
        <?php foreach ( $sac_items as $item ) : ?>
          <li><?php echo esc_html( $item ); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- CONSEILS -->
    <?php if ( ! empty( $conseils_items ) ) : ?>
    <div class="sr-conseils-section">
      <h2 class="sr-section-title"><?php echo rando_nono_icon( 'lightbulb' ); ?> Conseils</h2>
      <ul class="sr-conseils-list">
        <?php foreach ( $conseils_items as $conseil ) : ?>
          <li><?php echo esc_html( $conseil ); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <!-- PARTAGE SOCIAL -->
    <div class="sr-share-section">
      <span class="share-label">Partager cette rando</span>
      <div class="sr-share-buttons">
        <a class="share-btn share-whatsapp" id="sr-share-whatsapp" href="#" target="_blank" rel="noopener">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg>
          WhatsApp
        </a>
        <a class="share-btn share-facebook" id="sr-share-facebook" href="#" target="_blank" rel="noopener">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
          Facebook
        </a>
        <button class="share-btn share-copy" id="sr-share-copy">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
          <span id="sr-share-copy-label">Copier le lien</span>
        </button>
      </div>
    </div>

    <!-- RETOUR ARCHIVE -->
    <div class="sr-back">
      <a href="<?php echo esc_url( get_post_type_archive_link( 'randonnee' ) ); ?>" class="btn btn-sm">
        &larr; Toutes les randonn&eacute;es
      </a>
    </div>

  </div>
</section>

<?php endwhile; ?>

<!-- LIGHTBOX -->
<div class="sr-lightbox" id="sr-lightbox" aria-hidden="true">
  <button class="sr-lightbox-close" id="sr-lightbox-close" aria-label="Fermer">&times;</button>
  <button class="sr-lightbox-prev" id="sr-lightbox-prev" aria-label="Pr&eacute;c&eacute;dent">&lsaquo;</button>
  <button class="sr-lightbox-next" id="sr-lightbox-next" aria-label="Suivant">&rsaquo;</button>
  <img class="sr-lightbox-img" id="sr-lightbox-img" src="" alt="">
  <div class="sr-lightbox-counter" id="sr-lightbox-counter"></div>
</div>

<?php get_footer(); ?>
