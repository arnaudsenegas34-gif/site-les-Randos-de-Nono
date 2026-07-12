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
        $photo_id = intval( $photo_id );
        $url = wp_get_attachment_image_url( $photo_id, 'large' );
        if ( $url ) {
            $alt = get_post_meta( $photo_id, '_wp_attachment_image_alt', true );
            $photos_urls[] = array( 'url' => $url, 'alt' => $alt );
        }
    }
}
$sac_items     = $sac_raw ? array_filter( array_map( 'trim', explode( "\n", $sac_raw ) ) ) : array();
$conseils_items = $conseils_raw ? array_filter( array_map( 'trim', explode( "\n", $conseils_raw ) ) ) : array();

$linked_articles = get_posts( array(
    'post_type'      => 'post',
    'posts_per_page' => -1,
    'meta_key'       => 'article_rando_id',
    'meta_value'     => $id,
    'orderby'        => 'date',
    'order'          => 'DESC',
) );

$thumb_large = get_the_post_thumbnail_url( $id, 'full' );

$diff_classes = array( 'facile' => 'diff-facile', 'moyen' => 'diff-moyen', 'difficile' => 'diff-difficile' );
$diff_class   = isset( $diff_classes[ $difficulte ] ) ? $diff_classes[ $difficulte ] : 'diff-moyen';
?>

<main id="main-content">

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
            <?php echo rando_nono_icon( 'map' ); ?> Activité Suunto
          </a>
        <?php endif; ?>
        <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo esc_attr( $lat ); ?>,<?php echo esc_attr( $lon ); ?>&travelmode=driving" target="_blank" rel="noopener" class="btn btn-sm sr-btn-gpx">
          Aller au d&eacute;part
        </a>
      </div>
    </div>
    <?php endif; ?>

    <!-- SUIVI GPS EN DIRECT -->
    <div class="sr-tracking" id="sr-tracking" data-rando-id="<?php echo esc_attr( $id ); ?>" data-rando-title="<?php echo esc_attr( get_the_title() ); ?>">
      <button type="button" class="btn sr-track-start" id="sr-track-start">
        <?php echo rando_nono_icon( 'play' ); ?> D&eacute;marrer la randonn&eacute;e
      </button>
      <p class="sr-tracking-hint">Suivez votre position, votre distance parcourue et votre temps en direct depuis votre t&eacute;l&eacute;phone pendant la rando.</p>
    </div>

    <!-- Barre de suivi live (affichée pendant la randonnée) -->
    <div class="sr-track-bar" id="sr-track-bar" hidden aria-live="polite">
      <div class="sr-track-stats">
        <div class="sr-track-stat">
          <span class="sr-track-value" id="sr-track-time">00:00:00</span>
          <span class="sr-track-label">Temps</span>
        </div>
        <div class="sr-track-stat">
          <span class="sr-track-value" id="sr-track-distance">0,00 km</span>
          <span class="sr-track-label">Distance</span>
        </div>
        <div class="sr-track-stat">
          <span class="sr-track-value" id="sr-track-pace">&ndash;&ndash;</span>
          <span class="sr-track-label">Allure</span>
        </div>
        <div class="sr-track-stat">
          <span class="sr-track-value" id="sr-track-elevation">&ndash;&ndash;</span>
          <span class="sr-track-label">D+ parcouru</span>
        </div>
      </div>
      <p class="sr-track-status" id="sr-track-status"></p>
      <div class="sr-track-controls">
        <button type="button" class="sr-track-btn sr-track-btn-pause" id="sr-track-pause"><?php echo rando_nono_icon( 'pause' ); ?> Pause</button>
        <button type="button" class="sr-track-btn sr-track-btn-stop" id="sr-track-stop"><?php echo rando_nono_icon( 'stop' ); ?> Terminer</button>
      </div>
    </div>

    <!-- Récapitulatif de fin de randonnée -->
    <div class="sr-track-recap" id="sr-track-recap" hidden>
      <h2 class="sr-section-title"><?php echo rando_nono_icon( 'navigation' ); ?> R&eacute;capitulatif de votre sortie</h2>
      <div class="sr-track-recap-grid">
        <div class="sr-track-recap-item">
          <span class="sr-track-recap-value" id="sr-recap-time">&ndash;</span>
          <span class="sr-track-recap-label">Dur&eacute;e</span>
        </div>
        <div class="sr-track-recap-item">
          <span class="sr-track-recap-value" id="sr-recap-distance">&ndash;</span>
          <span class="sr-track-recap-label">Distance</span>
        </div>
        <div class="sr-track-recap-item">
          <span class="sr-track-recap-value" id="sr-recap-pace">&ndash;</span>
          <span class="sr-track-recap-label">Allure moyenne</span>
        </div>
        <div class="sr-track-recap-item">
          <span class="sr-track-recap-value" id="sr-recap-elevation">&ndash;</span>
          <span class="sr-track-recap-label">D&eacute;nivel&eacute; positif</span>
        </div>
      </div>
      <div class="sr-track-recap-actions">
        <button type="button" class="btn btn-sm sr-btn-gpx" id="sr-recap-download">
          <?php echo rando_nono_icon( 'download' ); ?> T&eacute;l&eacute;charger ma trace GPX
        </button>
        <button type="button" class="btn btn-sm sr-btn-outline" id="sr-recap-restart">Refaire un suivi</button>
      </div>
    </div>

    <!-- GALERIE PHOTOS -->
    <?php if ( ! empty( $photos_urls ) ) : ?>
    <div class="sr-photos-section">
      <h2 class="sr-section-title">Photos</h2>
      <div class="sr-photos-grid">
        <?php foreach ( $photos_urls as $i => $photo ) : ?>
          <?php $alt_text = $photo['alt'] ? $photo['alt'] : get_the_title() . ' - photo ' . ( $i + 1 ); ?>
          <img class="sr-photo" src="<?php echo esc_url( $photo['url'] ); ?>" alt="<?php echo esc_attr( $alt_text ); ?>" loading="lazy" data-index="<?php echo intval( $i ); ?>">
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

    <!-- ARTICLES & RÉCITS LIÉS -->
    <?php if ( ! empty( $linked_articles ) ) : ?>
    <div class="sr-articles-section">
      <h2 class="sr-section-title"><?php echo rando_nono_icon( 'book' ); ?> Articles &amp; r&eacute;cits li&eacute;s</h2>
      <div class="sr-related-grid">
        <?php foreach ( $linked_articles as $article ) : ?>
          <a href="<?php echo esc_url( get_permalink( $article ) ); ?>" class="sr-related-card">
            <?php if ( has_post_thumbnail( $article ) ) : ?>
            <div class="sr-related-img-wrap">
              <img src="<?php echo esc_url( get_the_post_thumbnail_url( $article, 'medium' ) ); ?>"
                   alt="<?php echo esc_attr( get_the_title( $article ) ); ?>" loading="lazy">
            </div>
            <?php endif; ?>
            <div class="sr-related-info">
              <span class="sr-related-title"><?php echo esc_html( get_the_title( $article ) ); ?></span>
              <span class="sr-related-lieu"><?php echo esc_html( get_the_date( 'j F Y', $article ) ); ?></span>
              <p class="sr-articles-excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $article->post_content ), 20, '…' ) ); ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- NAVIGATION PRÉCÉDENT / SUIVANT -->
    <?php
    $prev_rando = get_previous_post();
    $next_rando = get_next_post();
    if ( $prev_rando || $next_rando ) :
    ?>
    <nav class="sr-nav-posts" aria-label="Navigation entre randonnées">
      <?php if ( $prev_rando ) : ?>
      <a href="<?php echo esc_url( get_permalink( $prev_rando ) ); ?>" class="sr-nav-post sr-nav-prev">
        <span class="sr-nav-direction">&larr; Rando pr&eacute;c&eacute;dente</span>
        <span class="sr-nav-title"><?php echo esc_html( get_the_title( $prev_rando ) ); ?></span>
      </a>
      <?php else : ?>
      <span class="sr-nav-post sr-nav-prev sr-nav-empty"></span>
      <?php endif; ?>
      <?php if ( $next_rando ) : ?>
      <a href="<?php echo esc_url( get_permalink( $next_rando ) ); ?>" class="sr-nav-post sr-nav-next">
        <span class="sr-nav-direction">Rando suivante &rarr;</span>
        <span class="sr-nav-title"><?php echo esc_html( get_the_title( $next_rando ) ); ?></span>
      </a>
      <?php else : ?>
      <span class="sr-nav-post sr-nav-next sr-nav-empty"></span>
      <?php endif; ?>
    </nav>
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

<!-- RANDONNÉES SIMILAIRES -->
<?php
$related_args = array(
    'post_type'      => 'randonnee',
    'posts_per_page' => 3,
    'post__not_in'   => array( $id ),
    'orderby'        => 'rand',
    'no_found_rows'  => true,
);
if ( $diff_terms && ! is_wp_error( $diff_terms ) ) {
    $related_args['tax_query'] = array(
        array(
            'taxonomy' => 'difficulte',
            'field'    => 'term_id',
            'terms'    => wp_list_pluck( $diff_terms, 'term_id' ),
        ),
    );
}
$related_query = new WP_Query( $related_args );
if ( $related_query->have_posts() ) :
?>
<section class="sr-related-section">
  <div class="sr-container">
    <h2 class="sr-section-title">Randonn&eacute;es similaires</h2>
    <div class="sr-related-grid">
      <?php while ( $related_query->have_posts() ) : $related_query->the_post(); ?>
      <a href="<?php the_permalink(); ?>" class="sr-related-card">
        <?php if ( has_post_thumbnail() ) : ?>
        <div class="sr-related-img-wrap">
          <img src="<?php echo esc_url( get_the_post_thumbnail_url( null, 'medium' ) ); ?>"
               alt="<?php the_title_attribute(); ?>" loading="lazy">
        </div>
        <?php endif; ?>
        <div class="sr-related-info">
          <span class="sr-related-title"><?php the_title(); ?></span>
          <?php $rlieu = get_post_meta( get_the_ID(), 'rando_lieu', true ); ?>
          <?php if ( $rlieu ) : ?>
          <span class="sr-related-lieu"><?php echo esc_html( $rlieu ); ?></span>
          <?php endif; ?>
        </div>
      </a>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php endwhile; ?>

</main>

<!-- LIGHTBOX -->
<div class="sr-lightbox" id="sr-lightbox" aria-hidden="true">
  <button class="sr-lightbox-close" id="sr-lightbox-close" aria-label="Fermer">&times;</button>
  <button class="sr-lightbox-prev" id="sr-lightbox-prev" aria-label="Pr&eacute;c&eacute;dent">&lsaquo;</button>
  <button class="sr-lightbox-next" id="sr-lightbox-next" aria-label="Suivant">&rsaquo;</button>
  <img class="sr-lightbox-img" id="sr-lightbox-img" src="" alt="">
  <div class="sr-lightbox-counter" id="sr-lightbox-counter"></div>
</div>

<?php get_footer(); ?>
