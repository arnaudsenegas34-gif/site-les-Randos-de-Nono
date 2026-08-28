<?php
/**
 * Template Name: Article — Guide de randos
 * Page éditoriale libre (ex: "Les plus belles randos de l'Hérault",
 * "Meilleures randos en famille") : texte de l'éditeur, complété
 * automatiquement par une sélection de randonnées en bas de page
 * (voir la métabox "Randonnées mises en avant" dans l'admin).
 */
get_header();
rando_nono_breadcrumb();

$eyebrow     = get_post_meta( get_the_ID(), 'article_guide_eyebrow', true ) ?: 'Guide';
$guide_query = rando_nono_article_guide_query( get_the_ID() );
?>

<main id="main-content">

<section class="site-section" style="padding-top:3rem">
  <div class="section-eyebrow"><?php echo esc_html( $eyebrow ); ?></div>
  <h1 class="section-title"><?php the_title(); ?></h1>
  <div class="divider"></div>

  <?php if ( has_post_thumbnail() ) : ?>
    <div class="article-cover">
      <?php the_post_thumbnail( 'rando-hero', array( 'fetchpriority' => 'high', 'decoding' => 'async' ) ); ?>
    </div>
  <?php endif; ?>

  <?php while ( have_posts() ) : the_post(); ?>
    <div class="article-body">
      <?php the_content(); ?>
    </div>
  <?php endwhile; ?>

  <?php if ( $guide_query->have_posts() ) : ?>
    <h2 class="section-title" style="font-size:1.4rem;margin-top:1rem">Randonnées à découvrir</h2>
    <div class="randos-grid">
      <?php while ( $guide_query->have_posts() ) : $guide_query->the_post(); ?>
        <div>
          <?php get_template_part( 'template-parts/card', 'rando' ); ?>
        </div>
      <?php endwhile; ?>
    </div>
    <?php wp_reset_postdata(); ?>
  <?php endif; ?>

  <p style="margin-top:2.5rem">
    <a href="<?php echo esc_url( get_post_type_archive_link( 'randonnee' ) ); ?>" class="btn">Voir toutes les randonnées</a>
  </p>
</section>

</main>

<?php get_footer(); ?>
