<?php
get_header();
rando_nono_breadcrumb();
?>

<main id="main-content">

<section class="site-section" style="min-height:60vh;display:flex;align-items:center">
  <div class="error-404-inner">

    <div class="error-404-num">404</div>
    <div class="section-eyebrow">Sentier perdu</div>
    <h1 class="section-title">Cette page n'existe pas</h1>
    <div class="divider"></div>
    <p class="section-sub">Le chemin que tu cherches s'est perdu dans la garrigue. Pas de panique — le sentier principal est juste là.</p>

    <div class="error-404-actions">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn">Retour à l'accueil</a>
      <a href="<?php echo esc_url( get_post_type_archive_link( 'randonnee' ) ); ?>" class="btn btn-outline" style="border-color:var(--accent);color:var(--accent)">Voir les randonnées</a>
    </div>

    <?php // Souvent on arrive ici par un vieux lien : la recherche retrouve la rando. ?>
    <form class="error-404-recherche" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
      <label for="recherche-404">Tu cherchais une rando en particulier&nbsp;?</label>
      <div class="error-404-recherche-ligne">
        <input type="search" id="recherche-404" name="s" placeholder="Un lieu, un lac, un sommet…" value="<?php echo esc_attr( trim( str_replace( array( '-', '/' ), ' ', wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ) ) ) ); ?>">
        <button type="submit" class="btn btn-sm">Chercher</button>
      </div>
    </form>

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
          $lieu  = rando_nono_lieu_court( get_the_ID() );
          $dist  = get_post_meta( get_the_ID(), 'rando_distance', true );
          ?>
          <a href="<?php echo esc_url( get_permalink() ); ?>" class="error-404-card">
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
          </a>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</section>

</main>

<?php get_footer(); ?>
