<?php
get_header();
?>

<main class="simple-page" id="main-content">
  <?php while ( have_posts() ) : the_post(); ?>
    <article>
      <?php the_content(); ?>
    </article>
  <?php endwhile; ?>
</main>

<?php get_footer(); ?>
