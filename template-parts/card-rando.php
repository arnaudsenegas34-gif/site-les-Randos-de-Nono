<?php
/**
 * Carte d'une randonnée — utilisée dans la grille de la page d'accueil.
 * Toute la carte est un lien natif vers la fiche de la randonnée
 * (voir .card-title-link dans style.css) : fonctionne sans JavaScript.
 */

$id        = get_the_ID();
// Lieu COMPLET en `title` (l'info reste accessible), lieu COURT à l'écran :
// une carte n'a pas la place d'une adresse entière, et la troncature CSS
// coupait au milieu d'un mot. Voir rando_nono_lieu_court() dans functions.php.
$lieu       = get_post_meta( $id, 'rando_lieu', true );
$lieu_court = rando_nono_lieu_court( $id );
$distance  = get_post_meta( $id, 'rando_distance', true );
$denivele  = get_post_meta( $id, 'rando_denivele', true );
$duree     = get_post_meta( $id, 'rando_duree', true );
$date_sortie = get_post_meta( $id, 'rando_date', true );
$gpx_url   = get_post_meta( $id, 'rando_gpx_url', true );

$diff_terms = get_the_terms( $id, 'difficulte' );
// Le NOM sert de libellé (tel que saisi, accents compris), la classe CSS est
// dérivée par sanitize_title() : un nom libre comme « Ça se corse » donnait
// sinon class="badge-diff-ça se corse", soit trois classes invalides.
$difficulte_nom  = $diff_terms && ! is_wp_error( $diff_terms ) ? $diff_terms[0]->name : 'Moyen';
$difficulte_slug = sanitize_title( $difficulte_nom );

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
      <?php
      // Le repère « 2/4 » situe la difficulté sur l'échelle : les noms sont des
      // formules maison, rien n'indiquait lequel était le plus facile.
      $diff_terme  = ( $diff_terms && ! is_wp_error( $diff_terms ) ) ? $diff_terms[0] : null;
      $diff_repere = $diff_terme ? rando_nono_difficulte_repere( $diff_terme ) : '';
      $diff_lien   = $diff_terme ? get_term_link( $diff_terme ) : '';
      ?>
      <?php if ( $diff_lien && ! is_wp_error( $diff_lien ) ) : ?>
        <a class="badge badge-diff badge-diff-<?php echo esc_attr( $difficulte_slug ); ?>" href="<?php echo esc_url( $diff_lien ); ?>" title="Voir toutes les randonnées de ce niveau"><?php echo esc_html( $difficulte_nom ); ?><?php if ( $diff_repere ) : ?><span class="badge-repere"><?php echo esc_html( $diff_repere ); ?></span><?php endif; ?></a>
      <?php else : ?>
        <span class="badge badge-diff badge-diff-<?php echo esc_attr( $difficulte_slug ); ?>"><?php echo esc_html( $difficulte_nom ); ?><?php if ( $diff_repere ) : ?><span class="badge-repere"><?php echo esc_html( $diff_repere ); ?></span><?php endif; ?></span>
      <?php endif; ?>
      <?php if ( $gpx_url ) : ?><span class="badge badge-gpx">GPX</span><?php endif; ?>
    </div>
    <button type="button" class="card-fav-btn js-favori-btn" data-id="<?php echo esc_attr( $id ); ?>" aria-pressed="false" aria-label="Ajouter aux favoris">
      <?php echo rando_nono_icon( 'heart' ); ?>
    </button>
  </div>
  <div class="card-body">
    <div class="card-meta">
      <span class="meta-item meta-item-lieu"><?php echo rando_nono_icon( 'pin' ); ?> <span class="meta-text" title="<?php echo esc_attr( $lieu ); ?>"><?php echo esc_html( $lieu_court ); ?></span></span>
      <span class="meta-item"><?php echo rando_nono_icon( 'calendar' ); ?> <?php echo esc_html( $date_sortie ); ?></span>
    </div>
    <?php
    // Le niveau du titre dépend de ce qui précède la grille sur la page :
    // h3 sous un h2 de section (accueil, page guide), h2 directement sous le
    // h1 d'une archive. Un h1 suivi de h3 crée un saut de niveau qu'axe-core
    // signale et qu'un lecteur d'écran restitue comme un trou dans le plan.
    $rn_niveau = isset( $args['niveau'] ) && in_array( $args['niveau'], array( 'h2', 'h3', 'h4' ), true )
        ? $args['niveau'] : 'h3';
    ?>
    <<?php echo $rn_niveau; ?> class="card-title"><a class="card-title-link" href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php the_title(); ?></a></<?php echo $rn_niveau; ?>>
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
