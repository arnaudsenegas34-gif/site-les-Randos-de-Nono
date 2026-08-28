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
    'posts_per_page' => 9,
    'orderby'        => 'date',
    'order'          => 'DESC',
) );
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
    <div class="guides-grid">
      <?php while ( $recits_query->have_posts() ) : $recits_query->the_post(); ?>
        <a class="guide-card" href="<?php the_permalink(); ?>">
          <?php if ( has_post_thumbnail() ) : ?>
            <div class="guide-card-img"><?php the_post_thumbnail( 'rando-card', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); ?></div>
          <?php endif; ?>
          <?php $rando_nono_cats = get_the_category(); ?>
          <?php if ( $rando_nono_cats ) : ?><span class="guide-card-tag"><?php echo esc_html( $rando_nono_cats[0]->name ); ?></span><?php endif; ?>
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
