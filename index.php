<?php
get_header();
?>

<main class="simple-page" id="main-content">
  <?php if ( have_posts() ) : ?>
    <?php while ( have_posts() ) : the_post(); ?>
      <article style="margin-bottom:3rem">
        <h1><?php the_title(); ?></h1>
        <div><?php the_content(); ?></div>
      </article>
    <?php endwhile; ?>
  <?php else : ?>
    <p>Aucun contenu trouvé.</p>
  <?php endif; ?>
</main>

<?php get_footer(); ?>
