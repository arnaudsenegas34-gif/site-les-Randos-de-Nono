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
        'lieu'  => get_post_meta( $r->ID, 'rando_lieu', true ),
        'thumb' => get_the_post_thumbnail_url( $r->ID, 'medium' ),
    );
}
?>

<main class="simple-page" id="main-content" style="max-width:1100px">
  <article>
    <h1>Mes randos &agrave; faire</h1>
    <p>Retrouve ici les randonn&eacute;es que tu as mises de c&ocirc;t&eacute;, enregistr&eacute;es uniquement sur cet appareil (clique sur le c&oelig;ur sur une fiche pour l'ajouter).</p>

    <div class="favoris-grid" id="favoris-grid" data-randos='<?php echo esc_attr( wp_json_encode( $rando_nono_randos_data ) ); ?>'></div>
    <p class="favoris-empty" id="favoris-empty" hidden>Tu n'as pas encore de randonn&eacute;e en favoris. Parcours <a href="<?php echo esc_url( get_post_type_archive_link( 'randonnee' ) ); ?>">toutes les randonn&eacute;es</a> et clique sur le c&oelig;ur pour en ajouter ici.</p>
  </article>
</main>

<?php get_footer(); ?>
