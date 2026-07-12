<?php
/**
 * Article (post) — rattaché ou non à une randonnée.
 */
get_header();

while ( have_posts() ) : the_post();

$id       = get_the_ID();
$rando_id = get_post_meta( $id, 'article_rando_id', true );
$rando    = $rando_id ? get_post( $rando_id ) : null;

$prev_article = null;
$next_article = null;
if ( $rando_id ) {
    $siblings = get_posts( array(
        'post_type'      => 'post',
        'posts_per_page' => -1,
        'meta_key'       => 'article_rando_id',
        'meta_value'     => $rando_id,
        'orderby'        => 'date',
        'order'          => 'ASC',
        'fields'         => 'ids',
    ) );
    $pos = array_search( $id, $siblings, true );
    if ( false !== $pos ) {
        if ( $pos > 0 )                          $prev_article = $siblings[ $pos - 1 ];
        if ( isset( $siblings[ $pos + 1 ] ) )     $next_article = $siblings[ $pos + 1 ];
    }
}
?>

<main class="simple-page" id="main-content">
  <article>
    <?php if ( has_post_thumbnail() ) : ?>
      <img src="<?php echo esc_url( get_the_post_thumbnail_url( $id, 'large' ) ); ?>" alt="<?php the_title_attribute(); ?>" style="width:100%;border-radius:8px;margin-bottom:1.5rem" decoding="async" fetchpriority="high">
    <?php endif; ?>
    <h1><?php the_title(); ?></h1>
    <p style="font-size:0.8rem;color:var(--gris);margin-top:-0.3rem"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></p>
    <div class="article-content"><?php the_content(); ?></div>
  </article>

  <?php if ( $rando ) : ?>
    <p style="margin-top:2.5rem">
      <a href="<?php echo esc_url( get_permalink( $rando ) ); ?>" class="btn btn-sm">
        &larr; Retour &agrave; la randonn&eacute;e : <?php echo esc_html( get_the_title( $rando ) ); ?>
      </a>
    </p>
  <?php endif; ?>

  <?php if ( $prev_article || $next_article ) : ?>
    <nav class="sr-nav-posts" aria-label="Navigation entre articles" style="margin-top:1.5rem">
      <?php if ( $prev_article ) : ?>
      <a href="<?php echo esc_url( get_permalink( $prev_article ) ); ?>" class="sr-nav-post sr-nav-prev">
        <span class="sr-nav-direction">&larr; Article pr&eacute;c&eacute;dent</span>
        <span class="sr-nav-title"><?php echo esc_html( get_the_title( $prev_article ) ); ?></span>
      </a>
      <?php else : ?>
      <span class="sr-nav-post sr-nav-prev sr-nav-empty"></span>
      <?php endif; ?>
      <?php if ( $next_article ) : ?>
      <a href="<?php echo esc_url( get_permalink( $next_article ) ); ?>" class="sr-nav-post sr-nav-next">
        <span class="sr-nav-direction">Article suivant &rarr;</span>
        <span class="sr-nav-title"><?php echo esc_html( get_the_title( $next_article ) ); ?></span>
      </a>
      <?php else : ?>
      <span class="sr-nav-post sr-nav-next sr-nav-empty"></span>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</main>

<?php endwhile; ?>

<?php get_footer(); ?>
