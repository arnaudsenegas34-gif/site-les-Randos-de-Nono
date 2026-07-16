<?php
/**
 * Carte d'une randonnée — utilisée dans la grille de la page d'accueil.
 * Les attributs data-* alimentent le modal en JavaScript (voir assets/js/main.js).
 */

$id        = get_the_ID();
$lieu      = get_post_meta( $id, 'rando_lieu', true );
$lat       = get_post_meta( $id, 'rando_lat', true );
$lon       = get_post_meta( $id, 'rando_lon', true );
$distance  = get_post_meta( $id, 'rando_distance', true );
$denivele  = get_post_meta( $id, 'rando_denivele', true );
$duree     = get_post_meta( $id, 'rando_duree', true );
$date_sortie = get_post_meta( $id, 'rando_date', true );
$meilleure_saison = get_post_meta( $id, 'rando_meilleure_saison', true );
$maps_url  = get_post_meta( $id, 'rando_maps_url', true );
$gpx_url   = get_post_meta( $id, 'rando_gpx_url', true );
$photos_raw = get_post_meta( $id, 'rando_photos', true );
$sac_raw   = get_post_meta( $id, 'rando_sac', true );
$conseils_raw = get_post_meta( $id, 'rando_conseils', true );

$diff_terms = get_the_terms( $id, 'difficulte' );
$difficulte = $diff_terms && ! is_wp_error( $diff_terms ) ? strtolower( $diff_terms[0]->name ) : 'moyen';

$photos_urls = array();
if ( $photos_raw ) {
    $ids = array_map( 'trim', explode( ',', $photos_raw ) );
    foreach ( $ids as $photo_id ) {
        $url = wp_get_attachment_image_url( $photo_id, 'large' );
        if ( $url ) $photos_urls[] = $url;
    }
}
$sac_items = $sac_raw ? array_filter( array_map( 'trim', explode( "\n", $sac_raw ) ) ) : array();
$conseils_items = $conseils_raw ? array_filter( array_map( 'trim', explode( "\n", $conseils_raw ) ) ) : array();

$thumb = get_the_post_thumbnail_url( $id, 'medium' );
?>

<div class="rando-card"
     data-id="<?php echo esc_attr( $id ); ?>"
     data-slug="<?php echo esc_attr( get_post_field( 'post_name', $id ) ); ?>"
     data-url="<?php echo esc_url( get_permalink( $id ) ); ?>"
     data-titre="<?php echo esc_attr( get_the_title() ); ?>"
     data-lieu="<?php echo esc_attr( $lieu ); ?>"
     data-lat="<?php echo esc_attr( $lat ); ?>"
     data-lon="<?php echo esc_attr( $lon ); ?>"
     data-distance="<?php echo esc_attr( $distance ); ?>"
     data-denivele="<?php echo esc_attr( $denivele ); ?>"
     data-duree="<?php echo esc_attr( $duree ); ?>"
     data-date="<?php echo esc_attr( $date_sortie ); ?>"
     data-saison="<?php echo esc_attr( $meilleure_saison ); ?>"
     data-difficulte="<?php echo esc_attr( $difficulte ); ?>"
     data-maps="<?php echo esc_attr( $maps_url ); ?>"
     data-gpx="<?php echo esc_attr( $gpx_url ); ?>"
     data-photos='<?php echo esc_attr( wp_json_encode( $photos_urls ) ); ?>'
     data-recit="<?php echo esc_attr( wp_strip_all_tags( get_the_content() ) ); ?>"
     data-sac='<?php echo esc_attr( wp_json_encode( $sac_items ) ); ?>'
     data-conseils='<?php echo esc_attr( wp_json_encode( $conseils_items ) ); ?>'
>
  <div class="card-photo-wrap">
    <?php if ( $thumb ) : ?>
      <img class="card-photo" src="<?php echo esc_url( $thumb ); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" decoding="async">
    <?php else : ?>
      <img class="card-photo card-photo-placeholder" src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/placeholder-rando.jpg' ); ?>" alt="Photo à venir — <?php the_title_attribute(); ?>" loading="lazy" decoding="async">
    <?php endif; ?>
    <div class="card-badges">
      <span class="badge badge-diff-<?php echo esc_attr( $difficulte ); ?>"><?php echo esc_html( ucfirst( $difficulte ) ); ?></span>
      <?php if ( $gpx_url ) : ?><span class="badge badge-gpx">GPX</span><?php endif; ?>
    </div>
    <button type="button" class="card-fav-btn js-favori-btn" data-id="<?php echo esc_attr( $id ); ?>" aria-pressed="false" aria-label="Ajouter aux favoris" onclick="event.stopPropagation()">
      <?php echo rando_nono_icon( 'heart' ); ?>
    </button>
  </div>
  <div class="card-body">
    <div class="card-meta">
      <span class="meta-item"><?php echo rando_nono_icon( 'pin' ); ?> <?php echo esc_html( $lieu ); ?></span>
      <span class="meta-item"><?php echo rando_nono_icon( 'calendar' ); ?> <?php echo esc_html( $date_sortie ); ?></span>
    </div>
    <div class="card-title"><?php the_title(); ?></div>
    <div class="card-meta" style="margin-bottom:0.85rem">
      <span class="meta-item"><?php echo rando_nono_icon( 'ruler' ); ?> <?php echo esc_html( $distance ); ?></span>
      <span class="meta-item"><?php echo rando_nono_icon( 'trending-up' ); ?> <?php echo esc_html( $denivele ); ?></span>
      <span class="meta-item"><?php echo rando_nono_icon( 'clock' ); ?> <?php echo esc_html( $duree ); ?></span>
    </div>
    <?php
    // Légère variation de longueur (16 à 22 mots) basée sur l'ID, pour un rythme de lecture
    // moins mécanique qu'une troncature strictement identique sur chaque carte.
    $trim_length = 16 + ( $id % 4 ) * 2;
    ?>
    <div class="card-desc"><?php echo esc_html( wp_trim_words( get_the_content(), $trim_length ) ); ?></div>
    <div class="card-actions">
      <button class="btn btn-sm js-open-modal">Voir la rando</button>
      <?php if ( $gpx_url ) : ?>
        <a class="btn btn-sm" style="background:var(--vert)" href="<?php echo esc_url( $gpx_url ); ?>" download onclick="event.stopPropagation()"><?php echo rando_nono_icon( 'download' ); ?> GPX</a>
      <?php endif; ?>
    </div>
  </div>
</div>
