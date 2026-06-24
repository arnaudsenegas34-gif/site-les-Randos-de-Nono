<?php
get_header();
rando_nono_breadcrumb();
?>

<section class="site-section" style="padding-top:2.5rem">
  <div class="randos-grid" style="grid-template-columns:1fr;max-width:420px">
    <?php
    while ( have_posts() ) : the_post();
        get_template_part( 'template-parts/card', 'rando' );
    endwhile;
    ?>
  </div>
</section>

<?php get_template_part( 'template-parts/modal', 'rando' ); ?>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const card = document.querySelector('.rando-card');
    if (card) card.click();
  });
</script>

<?php get_footer(); ?>
