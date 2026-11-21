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
// Filtre par département (« Hérault », « Savoie »…), lu dans le lieu court.
$archive_stats = rando_nono_get_homepage_stats();
$departements  = isset( $archive_stats['departements'] ) ? $archive_stats['departements'] : array();
arsort( $departements );
$selected_dep  = isset( $_GET['departement'] ) ? sanitize_text_field( wp_unslash( $_GET['departement'] ) ) : '';
if ( $selected_dep && ! isset( $departements[ $selected_dep ] ) ) $selected_dep = '';
// Tri : récentes (défaut), plus courtes, moins de dénivelé, ou par région.
$tris = array( 'recent' => 'Les plus récentes', 'courtes' => 'Les plus courtes', 'denivele' => 'Le moins de dénivelé', 'region' => 'Par région' );
$selected_tri = isset( $_GET['tri'] ) ? sanitize_key( $_GET['tri'] ) : 'recent';
if ( ! isset( $tris[ $selected_tri ] ) ) $selected_tri = 'recent';

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
// Distance et dénivelé sont du texte (« 7,0 km », « +240 m ») : le CAST en
// DECIMAL lit le nombre de tête, suffisant pour trier.
if ( 'courtes' === $selected_tri || 'denivele' === $selected_tri ) {
    $meta_query['tri_clause'] = array( 'key' => 'courtes' === $selected_tri ? 'rando_distance' : 'rando_denivele', 'type' => 'DECIMAL(10,1)' );
    $args['orderby'] = array( 'tri_clause' => 'ASC', 'date' => 'DESC' );
}
// Par région : toutes les randos sur une page, regroupées sous un intertitre.
if ( 'region' === $selected_tri ) {
    $args['posts_per_page'] = -1;
}
if ( $selected_dep ) {
    $meta_query[] = array( 'key' => 'rando_lieu_court', 'value' => '(' . $selected_dep . ')', 'compare' => 'LIKE' );
}
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

// Toutes les difficultés disponibles pour le filtre, du plus facile au plus
// exigeant — get_terms() les renvoyait par ordre alphabétique, ce qui plaçait
// « Ça se corse » après « Tu vas t'en souvenir » à cause de la cédille et ne
// disait rien de la progression.
$all_difficultes = rando_nono_difficultes_ordonnees( true );

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
            'lieu'     => rando_nono_lieu_court( $mid ),
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
  <p class="section-sub">Choisis une région, filtre par difficulté, distance ou dénivelé, ou recherche une randonnée par son nom ou son lieu.</p>

  <?php
  // Filtre par département en un clic, avec le nombre de randos de chacun.
  if ( count( $departements ) > 1 ) :
      $base_url = get_post_type_archive_link( 'randonnee' ); ?>
  <nav class="archive-regions" aria-label="Filtrer par département">
    <a class="archive-region<?php echo $selected_dep ? '' : ' is-active'; ?>" href="<?php echo esc_url( $base_url . '#resultats' ); ?>"<?php echo $selected_dep ? '' : ' aria-current="true"'; ?>>Toutes · <?php echo esc_html( array_sum( $departements ) ); ?></a>
    <?php foreach ( $departements as $dep => $nb ) : ?>
      <a class="archive-region<?php echo $selected_dep === $dep ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'departement' => rawurlencode( $dep ), 'tri' => 'recent' !== $selected_tri ? $selected_tri : false ), $base_url ) . '#resultats' ); ?>"<?php echo $selected_dep === $dep ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $dep . ' · ' . $nb ); ?></a>
    <?php endforeach; ?>
  </nav>
  <?php endif; ?>

  <!-- ════════ FILTRES ════════ -->
  <?php
      // Sans ancre, valider les filtres rechargeait la page en position haute :
      // il fallait refaire 1,4 écran de défilement pour voir les résultats
      // qu'on venait de demander, en repassant devant le formulaire et la
      // carte. L'ancre y ramène directement.
      ?>
      <form method="get" action="#resultats" class="archive-filters">
    <div class="filter-group">
      <label for="recherche">Recherche</label>
      <input type="text" id="recherche" name="recherche" placeholder="Nom, lieu..." value="<?php echo esc_attr( $search_term ); ?>">
    </div>

    <fieldset class="filter-group filter-diff">
      <legend>Difficulté</legend>
      <div class="filter-pills">
        <label class="filter-pill"><input type="radio" name="difficulte" value="" <?php checked( $selected_diff, '' ); ?>><span>Toutes</span></label>
        <?php if ( $all_difficultes && ! is_wp_error( $all_difficultes ) ) : ?>
          <?php foreach ( $all_difficultes as $term ) : ?>
            <?php $rn_repere = rando_nono_difficulte_repere( $term ); ?>
            <label class="filter-pill"<?php echo $rn_repere ? ' title="' . esc_attr( $rn_repere ) . '"' : ''; ?>><input type="radio" name="difficulte" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( $selected_diff, $term->slug ); ?>><span><?php echo esc_html( $term->name ); ?></span></label>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </fieldset>

    <div class="filter-group">
      <label for="distance_max">Distance max&nbsp;: <output id="distance_max_out" for="distance_max"><?php echo esc_html( $selected_distance_max ); ?></output>&nbsp;km</label>
      <input type="range" id="distance_max" name="distance_max" min="0" max="<?php echo esc_attr( $distance_max_cap ); ?>" step="1" value="<?php echo esc_attr( $selected_distance_max ); ?>">
    </div>

    <div class="filter-group">
      <label for="denivele_max">Dénivelé max&nbsp;: <output id="denivele_max_out" for="denivele_max"><?php echo esc_html( $selected_denivele_max ); ?></output>&nbsp;m</label>
      <input type="range" id="denivele_max" name="denivele_max" min="0" max="<?php echo esc_attr( $denivele_max_cap ); ?>" step="10" value="<?php echo esc_attr( $selected_denivele_max ); ?>">
    </div>

    <div class="filter-group">
      <label for="tri">Trier</label>
      <select id="tri" name="tri">
        <?php foreach ( $tris as $cle => $libelle ) : ?>
          <option value="<?php echo esc_attr( $cle ); ?>" <?php selected( $selected_tri, $cle ); ?>><?php echo esc_html( $libelle ); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <?php if ( $selected_dep ) : ?><input type="hidden" name="departement" value="<?php echo esc_attr( $selected_dep ); ?>"><?php endif; ?>
    <button type="submit" class="btn btn-sm">Filtrer</button>
    <?php if ( $selected_diff || $search_term || $distance_filter_active || $denivele_filter_active || $selected_dep || 'recent' !== $selected_tri ) : ?>
      <a href="<?php echo esc_url( get_post_type_archive_link( 'randonnee' ) ); ?>" class="filter-reset">Réinitialiser</a>
    <?php endif; ?>
  </form>

  <!-- ════════ CARTE D'ENSEMBLE ════════ -->
  <?php if ( ! empty( $map_markers ) ) : ?>
    <?php
    // Sur mobile, la carte prenait tout l'écran avant la liste : elle est
    // repliée par défaut (un toucher l'ouvre). Sur ordinateur elle reste
    // ouverte (attribut « open » ajouté par le script selon la largeur).
    ?>
    <details class="archive-map-details" id="archive-map-details">
      <summary class="archive-map-summary"><?php echo rando_nono_icon( 'map' ); ?> Voir les <?php echo esc_html( count( $map_markers ) ); ?> départs sur la carte</summary>
      <div class="archive-map-wrap">
        <div class="archive-map" id="archive-map" data-markers='<?php echo esc_attr( wp_json_encode( $map_markers ) ); ?>'></div>
      </div>
    </details>
  <?php endif; ?>

  <!-- ════════ RÉSULTATS ════════ -->
  <?php if ( $archive_query->have_posts() ) : ?>
    <p id="resultats" class="archive-count"><?php echo esc_html( $archive_query->found_posts ); ?> randonnée<?php echo $archive_query->found_posts > 1 ? 's' : ''; ?> trouvée<?php echo $archive_query->found_posts > 1 ? 's' : ''; ?></p>

    <?php if ( 'region' === $selected_tri ) :
        // Regroupement par département (texte entre parenthèses du lieu court),
        // les départements les plus fournis d'abord.
        $groupes = array();
        while ( $archive_query->have_posts() ) {
            $archive_query->the_post();
            $lc  = (string) get_post_meta( get_the_ID(), 'rando_lieu_court', true );
            $dep = preg_match( '/\(([^)]+)\)\s*$/u', $lc, $dm ) ? trim( $dm[1] ) : 'Ailleurs';
            $groupes[ $dep ][] = get_the_ID();
        }
        uasort( $groupes, function ( $a, $b ) { return count( $b ) - count( $a ); } );
        global $post;
        foreach ( $groupes as $dep => $ids ) : ?>
      <h2 class="archive-groupe"><?php echo esc_html( $dep ); ?> <small><?php echo esc_html( count( $ids ) . ' rando' . ( count( $ids ) > 1 ? 's' : '' ) ); ?></small></h2>
      <div class="randos-grid">
        <?php foreach ( $ids as $gid ) : $post = get_post( $gid ); setup_postdata( $post ); ?>
          <div><?php get_template_part( 'template-parts/card', 'rando', array( 'niveau' => 'h3' ) ); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; wp_reset_postdata();
    else : ?>
    <div class="randos-grid">
      <?php while ( $archive_query->have_posts() ) : $archive_query->the_post(); ?>
        <div>
          <?php get_template_part( 'template-parts/card', 'rando', array( 'niveau' => 'h2' ) ); ?>
        </div>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>

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
