<?php
/**
 * Template Name: Favoris
 * Slug: favoris
 * Liste les randonnées que le visiteur a mises de côté — stockées uniquement
 * dans son navigateur (localStorage), sans compte ni connexion nécessaire.
 */
get_header();

$rando_nono_all_randos = get_posts( array(
    'post_type'      => 'randonnee',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
) );

$rando_nono_randos_data = array();
foreach ( $rando_nono_all_randos as $r ) {
    $rando_nono_randos_data[] = array(
        'id'    => $r->ID,
        'titre' => get_the_title( $r ),
        'url'   => get_permalink( $r ),
        'lieu'  => rando_nono_lieu_court( $r->ID ),
        'thumb' => get_the_post_thumbnail_url( $r->ID, 'medium' ),
    );
}
?>

<main class="simple-page" id="main-content" style="max-width:1100px">
  <article>
    <h1>Mes randos &agrave; faire</h1>
    <p>Retrouve ici les randonn&eacute;es que tu as mises de c&ocirc;t&eacute;, enregistr&eacute;es uniquement sur cet appareil (clique sur le c&oelig;ur sur une fiche pour l'ajouter).</p>

    <div class="favoris-grid" id="favoris-grid" data-randos='<?php echo esc_attr( wp_json_encode( $rando_nono_randos_data ) ); ?>'></div>
    <?php
    // Liste vide : au lieu d'une phrase seule, un vrai point de départ —
    // trois randos faciles à mettre de côté d'un toucher sur le cœur.
    $rando_nono_suggestions = get_posts( array(
        'post_type'      => 'randonnee',
        'posts_per_page' => 3,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'tax_query'      => array( array( 'taxonomy' => 'difficulte', 'field' => 'slug', 'terms' => 'simpliste' ) ),
    ) );
    ?>
    <div class="favoris-empty" id="favoris-empty" hidden>
      <div class="favoris-vide">
        <div class="favoris-vide-coeur" aria-hidden="true"><?php echo rando_nono_icon( 'heart' ); ?></div>
        <p class="favoris-vide-titre">Rien pour l&rsquo;instant</p>
        <p>Touche le c&oelig;ur d&rsquo;une rando pour la retrouver ici, m&ecirc;me sans r&eacute;seau sur le terrain.</p>
        <a class="btn btn-sm" href="<?php echo esc_url( get_post_type_archive_link( 'randonnee' ) ); ?>">Parcourir les randos</a>
      </div>
      <?php if ( $rando_nono_suggestions ) : ?>
        <h2 class="sr-section-title favoris-sugg-titre">Pour commencer, les plus faciles</h2>
        <div class="favoris-sugg">
          <?php foreach ( $rando_nono_suggestions as $s ) : ?>
            <div class="favoris-sugg-item">
              <a class="favoris-sugg-lien" href="<?php echo esc_url( get_permalink( $s ) ); ?>">
                <?php echo get_the_post_thumbnail( $s->ID, 'thumbnail', array( 'alt' => '', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- généré par WordPress ?>
                <span><b><?php echo esc_html( get_the_title( $s ) ); ?></b><small><?php echo esc_html( trim( rando_nono_lieu_court( $s->ID ) . ' · ' . get_post_meta( $s->ID, 'rando_distance', true ), ' ·' ) ); ?></small></span>
              </a>
              <button type="button" class="favoris-sugg-coeur js-favori-btn" data-id="<?php echo esc_attr( $s->ID ); ?>" aria-pressed="false" aria-label="Ajouter aux favoris"><?php echo rando_nono_icon( 'heart' ); ?></button>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </article>
</main>

<?php get_footer(); ?>
