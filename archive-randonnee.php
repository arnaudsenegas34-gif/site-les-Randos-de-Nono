<?php
/**
 * Archive des randonnées — page "Toutes les randonnées"
 * Filtres : difficulté (taxonomie), distance max, dénivelé max, recherche texte.
 */
get_header();
rando_nono_breadcrumb();
?>
<main id="main-content">
<?php

$selected_diff = isset( $_GET['difficulte'] ) ? sanitize_text_field( $_GET['difficulte'] ) : '';
$search_term   = isset( $_GET['recherche'] ) ? sanitize_text_field( $_GET['recherche'] ) : '';

// Bornes réelles (calculées sur les randos publiées) pour les curseurs —
// le curseur part toujours du maximum tant qu'on n'y a pas touché.
$filter_bounds    = rando_nono_get_archive_filter_bounds();
$distance_max_cap = $filter_bounds['distance_max'];
$denivele_max_cap = $filter_bounds['denivele_max'];

$selected_distance_max = isset( $_GET['distance_max'] ) ? min( max( 0, (int) $_GET['distance_max'] ), $distance_max_cap ) : $distance_max_cap;
$selected_denivele_max = isset( $_GET['denivele_max'] ) ? min( max( 0, (int) $_GET['denivele_max'] ), $denivele_max_cap ) : $denivele_max_cap;
$distance_filter_active = $selected_distance_max < $distance_max_cap;
$denivele_filter_active = $selected_denivele_max < $denivele_max_cap;

$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

$args = array(
    'post_type'      => 'randonnee',
    'posts_per_page' => 9,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
);

if ( $selected_diff ) {
    $args['tax_query'] = array(
        array( 'taxonomy' => 'difficulte', 'field' => 'slug', 'terms' => $selected_diff ),
    );
}

// rando_distance / rando_denivele sont du texte libre ("12 km", "+380 m") :
// CAST(... AS DECIMAL) lit correctement la valeur numérique en tête de
// chaîne (comportement standard MySQL), sans avoir à dupliquer ces champs
// dans un format numérique séparé.
$meta_query = array();
if ( $distance_filter_active ) {
    $meta_query[] = array( 'key' => 'rando_distance', 'value' => $selected_distance_max, 'compare' => '<=', 'type' => 'DECIMAL(10,1)' );
}
if ( $denivele_filter_active ) {
    $meta_query[] = array( 'key' => 'rando_denivele', 'value' => $selected_denivele_max, 'compare' => '<=', 'type' => 'DECIMAL(10,1)' );
}
if ( $meta_query ) {
    if ( count( $meta_query ) > 1 ) $meta_query['relation'] = 'AND';
    $args['meta_query'] = $meta_query;
}

if ( $search_term ) {
    $args['s'] = $search_term;
}

$archive_query = new WP_Query( $args );

// Toutes les difficultés disponibles pour le filtre.
$all_difficultes = get_terms( array( 'taxonomy' => 'difficulte', 'hide_empty' => true ) );

// Marqueurs de la carte : mêmes filtres que la liste, mais sans pagination —
// la carte doit toujours montrer TOUTES les randonnées correspondantes.
$map_args = $args;
unset( $map_args['paged'] );
$map_args['posts_per_page'] = -1;
$map_query = new WP_Query( $map_args );

$map_markers = array();
if ( $map_query->have_posts() ) {
    while ( $map_query->have_posts() ) {
        $map_query->the_post();
        $mid = get_the_ID();
        $mlat = get_post_meta( $mid, 'rando_lat', true );
        $mlon = get_post_meta( $mid, 'rando_lon', true );
        if ( $mlat === '' || $mlon === '' ) continue;

        $map_markers[] = array(
            'lat'      => (float) $mlat,
            'lon'      => (float) $mlon,
            'titre'    => get_the_title(),
            'lieu'     => get_post_meta( $mid, 'rando_lieu', true ),
            'distance' => get_post_meta( $mid, 'rando_distance', true ),
            'url'      => get_permalink( $mid ),
            'thumb'    => get_the_post_thumbnail_url( $mid, 'medium' ),
        );
    }
    wp_reset_postdata();
}
?>

<section class="site-section" style="padding-top:3rem">
  <div class="section-eyebrow">Le carnet complet</div>
  <h1 class="section-title">Toutes les randonnées</h1>
  <div class="divider"></div>
  <p class="section-sub">Filtre par difficulté, distance ou dénivelé, ou recherche une randonnée par son nom ou son lieu.</p>

  <!-- ════════ FILTRES ════════ -->
  <form method="get" class="archive-filters">
    <div class="filter-group">
      <label for="recherche">Recherche</label>
      <input type="text" id="recherche" name="recherche" placeholder="Nom, lieu..." value="<?php echo esc_attr( $search_term ); ?>">
    </div>

    <div class="filter-group">
      <label for="difficulte">Difficulté</label>
      <select id="difficulte" name="difficulte">
        <option value="">Toutes</option>
        <?php if ( $all_difficultes && ! is_wp_error( $all_difficultes ) ) : ?>
          <?php foreach ( $all_difficultes as $term ) : ?>
            <option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $selected_diff, $term->slug ); ?>>
              <?php echo esc_html( $term->name ); ?>
            </option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
    </div>

    <div class="filter-group">
      <label for="distance_max">Distance max&nbsp;: <output id="distance_max_out" for="distance_max"><?php echo esc_html( $selected_distance_max ); ?></output>&nbsp;km</label>
      <input type="range" id="distance_max" name="distance_max" min="0" max="<?php echo esc_attr( $distance_max_cap ); ?>" step="1" value="<?php echo esc_attr( $selected_distance_max ); ?>">
    </div>

    <div class="filter-group">
      <label for="denivele_max">Dénivelé max&nbsp;: <output id="denivele_max_out" for="denivele_max"><?php echo esc_html( $selected_denivele_max ); ?></output>&nbsp;m</label>
      <input type="range" id="denivele_max" name="denivele_max" min="0" max="<?php echo esc_attr( $denivele_max_cap ); ?>" step="10" value="<?php echo esc_attr( $selected_denivele_max ); ?>">
    </div>

    <button type="submit" class="btn btn-sm">Filtrer</button>
    <?php if ( $selected_diff || $search_term || $distance_filter_active || $denivele_filter_active ) : ?>
      <a href="<?php echo esc_url( get_post_type_archive_link( 'randonnee' ) ); ?>" class="filter-reset">Réinitialiser</a>
    <?php endif; ?>
  </form>

  <!-- ════════ CARTE D'ENSEMBLE ════════ -->
  <?php if ( ! empty( $map_markers ) ) : ?>
    <div class="archive-map-wrap">
      <div class="archive-map" id="archive-map" data-markers='<?php echo esc_attr( wp_json_encode( $map_markers ) ); ?>'></div>
    </div>
  <?php endif; ?>

  <!-- ════════ RÉSULTATS ════════ -->
  <?php if ( $archive_query->have_posts() ) : ?>
    <p class="archive-count"><?php echo esc_html( $archive_query->found_posts ); ?> randonnée<?php echo $archive_query->found_posts > 1 ? 's' : ''; ?> trouvée<?php echo $archive_query->found_posts > 1 ? 's' : ''; ?></p>

    <div class="randos-grid">
      <?php while ( $archive_query->have_posts() ) : $archive_query->the_post(); ?>
        <div>
          <?php get_template_part( 'template-parts/card', 'rando' ); ?>
        </div>
      <?php endwhile; ?>
    </div>

    <!-- ════════ PAGINATION ════════ -->
    <div class="archive-pagination">
      <?php
      echo paginate_links( array(
          'total'     => $archive_query->max_num_pages,
          'current'   => $paged,
          'prev_text' => '‹ Précédent',
          'next_text' => 'Suivant ›',
      ) );
      ?>
    </div>

  <?php else : ?>
    <p style="color:var(--gris)">Aucune randonnée ne correspond à ta recherche. <a href="<?php echo esc_url( get_post_type_archive_link( 'randonnee' ) ); ?>" style="color:var(--orange)">Voir toutes les randonnées</a></p>
  <?php endif; ?>

  <?php wp_reset_postdata(); ?>
</section>

</main>

<?php get_footer(); ?>
