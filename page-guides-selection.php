<?php
/**
 * Template Name: Guides & Sélections (hub)
 * Page centrale qui réunit toutes les pages "Article — Guide de randos"
 * (voir page-article-guide.php) et les derniers récits du blog (matériel,
 * bivouac, randos sur plusieurs jours, vécu...) au même endroit.
 *
 * À créer avec le slug "guides" pour correspondre au lien "Guides &
 * Sélections" du menu (voir header.php) — sinon, adapte le lien du menu.
 */
get_header();
rando_nono_breadcrumb();

$guides_query = new WP_Query( array(
    'post_type'      => 'page',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'meta_key'       => '_wp_page_template',
    'meta_value'     => 'page-article-guide.php',
    'orderby'        => 'title',
    'order'          => 'ASC',
) );

$recits_query = new WP_Query( array(
    'post_type'      => 'post',
    // Tous les récits : à 9, le 10e article disparaissait sans pagination.
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
) );

// Départements des récits (via la rando liée) pour le filtre en pastilles.
$recits_deps = array();
$recits_info = array(); // par ID d'article : département et lieu de la rando liée
foreach ( $recits_query->posts as $rp ) {
    $rid = (int) get_post_meta( $rp->ID, 'article_rando_id', true );
    $lc  = $rid ? (string) get_post_meta( $rid, 'rando_lieu_court', true ) : '';
    $rn_dep = preg_match( '/\(([^)]+)\)\s*$/u', $lc, $dm ) ? trim( $dm[1] ) : '';
    $recits_info[ $rp->ID ] = array( "dep" => $rn_dep, "lieu" => $rid ? rando_nono_lieu_court( $rid ) : "" );
    if ( $rn_dep ) $recits_deps[ $rn_dep ] = isset( $recits_deps[ $rn_dep ] ) ? $recits_deps[ $rn_dep ] + 1 : 1;
}
arsort( $recits_deps );
?>

<main id="main-content">

<section class="site-section" style="padding-top:3rem">
  <div class="section-eyebrow">À lire</div>

  <?php while ( have_posts() ) : the_post(); ?>
    <h1 class="section-title"><?php the_title(); ?></h1>
    <div class="divider"></div>
    <?php if ( get_the_content() ) : ?>
      <div class="article-body" style="margin:0 0 2.5rem"><?php the_content(); ?></div>
    <?php endif; ?>
  <?php endwhile; ?>

  <h2 class="section-title" style="font-size:1.4rem">Guides & sélections</h2>
  <p class="section-sub" style="margin-bottom:1.5rem">Les meilleures randos triées par thème : familiales, panoramas, coups de cœur...</p>

  <?php if ( $guides_query->have_posts() ) : ?>
    <div class="guides-grid">
      <?php while ( $guides_query->have_posts() ) : $guides_query->the_post(); ?>
        <a class="guide-card" href="<?php the_permalink(); ?>">
          <?php if ( has_post_thumbnail() ) : ?>
            <div class="guide-card-img"><?php the_post_thumbnail( 'rando-card', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); ?></div>
          <?php endif; ?>
          <h3><?php the_title(); ?></h3>
          <p><?php echo esc_html( wp_trim_words( get_the_excerpt() ?: wp_strip_all_tags( get_the_content() ), 20 ) ); ?></p>
        </a>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
  <?php else : ?>
    <p style="color:var(--gris)">Les premiers guides arrivent bientôt.</p>
  <?php endif; ?>

  <h2 class="section-title" style="font-size:1.4rem;margin-top:3.5rem">Récits & vécus de rando</h2>
  <p class="section-sub" style="margin-bottom:1.5rem">Matériel, bivouac, randos sur plusieurs jours, retours d'expérience sur le terrain.</p>

  <?php if ( $recits_query->have_posts() ) : ?>
    <?php if ( count( $recits_deps ) > 1 ) : ?>
    <div class="archive-regions guides-filtre" role="group" aria-label="Filtrer les récits par département">
      <button type="button" class="archive-region is-active" data-dep="" aria-pressed="true">Tous · <?php echo esc_html( $recits_query->post_count ); ?></button>
      <?php foreach ( $recits_deps as $dep => $nb ) : ?>
        <button type="button" class="archive-region" data-dep="<?php echo esc_attr( $dep ); ?>" aria-pressed="false"><?php echo esc_html( $dep . ' · ' . $nb ); ?></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="guides-grid" id="guides-recits">
      <?php while ( $recits_query->have_posts() ) : $recits_query->the_post();
          $rn_info = isset( $recits_info[ get_the_ID() ] ) ? $recits_info[ get_the_ID() ] : array( "dep" => "", "lieu" => "" );
          $rn_min  = max( 1, (int) round( str_word_count( wp_strip_all_tags( get_the_content() ) ) / 200 ) ); ?>
        <a class="guide-card" href="<?php the_permalink(); ?>" data-dep="<?php echo esc_attr( $rn_info["dep"] ); ?>">
          <?php if ( has_post_thumbnail() ) : ?>
            <div class="guide-card-img"><?php the_post_thumbnail( 'rando-card', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); ?></div>
          <?php endif; ?>
          <span class="guide-card-tag"><?php echo esc_html( trim( ( $rn_info["lieu"] ? $rn_info["lieu"] . ' · ' : '' ) . $rn_min . ' min de lecture' ) ); ?></span>
          <h3><?php the_title(); ?></h3>
          <p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
        </a>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
  <?php else : ?>
    <p style="color:var(--gris)">Les premiers récits arrivent bientôt.</p>
  <?php endif; ?>

</section>

</main>

<?php get_footer(); ?>
