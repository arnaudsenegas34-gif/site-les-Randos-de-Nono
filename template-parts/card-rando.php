<?php
/**
 * Carte d'une randonnée — utilisée dans la grille de la page d'accueil.
 * Toute la carte est un lien natif vers la fiche de la randonnée
 * (voir .card-title-link dans style.css) : fonctionne sans JavaScript.
 */

$id        = get_the_ID();
$lieu      = get_post_meta( $id, 'rando_lieu', true );
$distance  = get_post_meta( $id, 'rando_distance', true );
$denivele  = get_post_meta( $id, 'rando_denivele', true );
$duree     = get_post_meta( $id, 'rando_duree', true );
$date_sortie = get_post_meta( $id, 'rando_date', true );
$gpx_url   = get_post_meta( $id, 'rando_gpx_url', true );

$diff_terms = get_the_terms( $id, 'difficulte' );
$difficulte = $diff_terms && ! is_wp_error( $diff_terms ) ? strtolower( $diff_terms[0]->name ) : 'moyen';

$thumb_tag = has_post_thumbnail( $id ) ? get_the_post_thumbnail( $id, 'rando-card', array(
    'class'   => 'card-photo',
    'loading' => 'lazy',
    'decoding' => 'async',
) ) : '';
?>

<div class="rando-card">
  <div class="card-photo-wrap">
    <?php if ( $thumb_tag ) : ?>
      <?php echo $thumb_tag; // phpcs:ignore WordPress.Security.EscapeOutput -- généré par get_the_post_thumbnail(), déjà échappé par WordPress ?>
    <?php else : ?>
      <picture>
        <source type="image/webp" srcset="<?php echo esc_url( get_template_directory_uri() . '/assets/img/responsive/placeholder-rando-400.webp' ); ?>">
        <img class="card-photo card-photo-placeholder" src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/responsive/placeholder-rando-400.jpg' ); ?>" alt="Photo à venir — <?php the_title_attribute(); ?>" loading="lazy" decoding="async">
      </picture>
    <?php endif; ?>
    <div class="card-badges">
      <span class="badge badge-diff-<?php echo esc_attr( $difficulte ); ?>"><?php echo esc_html( ucfirst( $difficulte ) ); ?></span>
      <?php if ( $gpx_url ) : ?><span class="badge badge-gpx">GPX</span><?php endif; ?>
    </div>
    <button type="button" class="card-fav-btn js-favori-btn" data-id="<?php echo esc_attr( $id ); ?>" aria-pressed="false" aria-label="Ajouter aux favoris">
      <?php echo rando_nono_icon( 'heart' ); ?>
    </button>
  </div>
  <div class="card-body">
    <div class="card-meta">
      <span class="meta-item"><?php echo rando_nono_icon( 'pin' ); ?> <?php echo esc_html( $lieu ); ?></span>
      <span class="meta-item"><?php echo rando_nono_icon( 'calendar' ); ?> <?php echo esc_html( $date_sortie ); ?></span>
    </div>
    <h3 class="card-title"><a class="card-title-link" href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php the_title(); ?></a></h3>
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
      <a class="btn btn-sm" href="<?php echo esc_url( get_permalink( $id ) ); ?>">Voir la rando</a>
      <?php if ( $gpx_url ) : ?>
        <a class="btn btn-sm" style="background:var(--vert)" href="<?php echo esc_url( $gpx_url ); ?>" download><?php echo rando_nono_icon( 'download' ); ?> GPX</a>
      <?php endif; ?>
    </div>
  </div>
</div>
