<?php
/**
 * Résultats de recherche — couvre à la fois les randonnées et les articles.
 */
get_header();
rando_nono_breadcrumb();

$rando_nono_s = get_search_query();

$rando_nono_search_randos = new WP_Query( array(
    'post_type'      => 'randonnee',
    's'              => $rando_nono_s,
    'posts_per_page' => -1,
) );

$rando_nono_search_articles = new WP_Query( array(
    'post_type'      => 'post',
    's'              => $rando_nono_s,
    'posts_per_page' => -1,
) );

$rando_nono_search_total = $rando_nono_search_randos->found_posts + $rando_nono_search_articles->found_posts;
?>

<main id="main-content">
<section class="site-section" style="padding-top:3rem">
  <div class="section-eyebrow">Recherche</div>
  <h1 class="section-title">R&eacute;sultats pour &laquo;&nbsp;<?php echo esc_html( $rando_nono_s ); ?>&nbsp;&raquo;</h1>
  <div class="divider"></div>

  <form method="get" class="archive-filters" action="<?php echo esc_url( home_url( '/' ) ); ?>">
    <div class="filter-group">
      <label for="s">Recherche</label>
      <input type="search" id="s" name="s" value="<?php echo esc_attr( $rando_nono_s ); ?>" placeholder="Nom, lieu, mot-cl&eacute;...">
    </div>
    <button type="submit" class="btn btn-sm">Rechercher</button>
  </form>

  <?php if ( $rando_nono_search_total > 0 ) : ?>
    <p class="archive-count"><?php echo esc_html( $rando_nono_search_total ); ?> r&eacute;sultat<?php echo $rando_nono_search_total > 1 ? 's' : ''; ?> trouv&eacute;<?php echo $rando_nono_search_total > 1 ? 's' : ''; ?></p>

    <?php if ( $rando_nono_search_randos->have_posts() ) : ?>
      <h2 class="sr-section-title" style="margin-top:2.5rem">Randonn&eacute;es</h2>
      <div class="randos-grid">
        <?php while ( $rando_nono_search_randos->have_posts() ) : $rando_nono_search_randos->the_post(); ?>
          <div><?php get_template_part( 'template-parts/card', 'rando' ); ?></div>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    <?php endif; ?>

    <?php if ( $rando_nono_search_articles->have_posts() ) : ?>
      <h2 class="sr-section-title" style="margin-top:2.5rem">Articles</h2>
      <div class="sr-related-grid">
        <?php while ( $rando_nono_search_articles->have_posts() ) : $rando_nono_search_articles->the_post(); ?>
          <a href="<?php the_permalink(); ?>" class="sr-related-card">
            <?php if ( has_post_thumbnail() ) : ?>
            <div class="sr-related-img-wrap">
              <img src="<?php echo esc_url( get_the_post_thumbnail_url( get_the_ID(), 'medium' ) ); ?>"
                   alt="<?php the_title_attribute(); ?>" loading="lazy" decoding="async">
            </div>
            <?php endif; ?>
            <div class="sr-related-info">
              <span class="sr-related-title"><?php the_title(); ?></span>
              <span class="sr-related-lieu"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></span>
              <p class="sr-articles-excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_content() ), 20, '…' ) ); ?></p>
            </div>
          </a>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    <?php endif; ?>

  <?php else : ?>
    <p style="color:var(--gris)">Aucun r&eacute;sultat pour &laquo;&nbsp;<?php echo esc_html( $rando_nono_s ); ?>&nbsp;&raquo;. <a href="<?php echo esc_url( get_post_type_archive_link( 'randonnee' ) ); ?>" style="color:var(--orange)">Voir toutes les randonn&eacute;es</a></p>
  <?php endif; ?>
</section>
</main>

<?php get_template_part( 'template-parts/modal', 'rando' ); ?>

<?php get_footer(); ?>
