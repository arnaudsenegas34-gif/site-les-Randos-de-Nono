<?php
get_header();
rando_nono_breadcrumb();
?>

<section class="site-section" style="min-height:60vh;display:flex;align-items:center">
  <div class="error-404-inner">

    <div class="error-404-num">404</div>
    <div class="section-eyebrow">Sentier perdu</div>
    <h1 class="section-title">Cette page n'existe pas</h1>
    <div class="divider"></div>
    <p class="section-sub">Le chemin que tu cherches s'est perdu dans la garrigue. Pas de panique — le sentier principal est juste là.</p>

    <div class="error-404-actions">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn">Retour à l'accueil</a>
      <a href="<?php echo esc_url( get_post_type_archive_link( 'randonnee' ) ); ?>" class="btn btn-outline" style="border-color:var(--vert);color:var(--vert)">Voir les randonnées</a>
    </div>

    <?php
    // Afficher les 3 dernières randonnées comme suggestions
    $recent = new WP_Query( array(
        'post_type'      => 'randonnee',
        'posts_per_page' => 3,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    if ( $recent->have_posts() ) :
    ?>
    <div class="error-404-suggestions">
      <p class="error-404-suggest-title">Quelques randonnées pour te remettre sur la bonne piste :</p>
      <div class="error-404-cards">
        <?php while ( $recent->have_posts() ) : $recent->the_post(); ?>
          <?php
          $thumb = get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' );
          $lieu  = get_post_meta( get_the_ID(), 'rando_lieu', true );
          $dist  = get_post_meta( get_the_ID(), 'rando_distance', true );
          ?>
          <div class="error-404-card">
            <?php if ( $thumb ) : ?>
              <div class="error-404-thumb" style="background-image:url('<?php echo esc_url( $thumb ); ?>')"></div>
            <?php else : ?>
              <div class="error-404-thumb error-404-thumb-placeholder"><?php echo rando_nono_icon( 'mountain' ); ?></div>
            <?php endif; ?>
            <div class="error-404-card-body">
              <div class="error-404-card-title"><?php the_title(); ?></div>
              <?php if ( $lieu ) : ?>
                <div class="error-404-card-meta"><?php echo rando_nono_icon( 'pin' ); ?> <?php echo esc_html( $lieu ); ?><?php if ( $dist ) echo ' · ' . esc_html( $dist ); ?></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</section>

<?php get_footer(); ?>
