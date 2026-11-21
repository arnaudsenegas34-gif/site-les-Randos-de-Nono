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

<?php
// ── Préparation de l'article ─────────────────────────────────────────
$rando_ok   = $rando && 'publish' === get_post_status( $rando );
$contenu    = apply_filters( 'the_content', get_the_content() );
$thumb_id   = get_post_thumbnail_id( $id );

// La photo mise en avant était aussi la première image du texte : on la
// voyait deux fois de suite. On retire ce doublon du texte.
if ( $thumb_id ) {
    $contenu = preg_replace( '#<figure[^>]*>\s*<img[^>]*wp-image-' . (int) $thumb_id . '\b[^>]*>.*?</figure>#s', '', $contenu, 1 );
}

// Sommaire : un lien par intertitre (h2), avec une ancre sur chacun.
$sommaire = array();
$contenu  = preg_replace_callback( '#<h2([^>]*)>(.*?)</h2>#s', function ( $m ) use ( &$sommaire ) {
    $texte = trim( wp_strip_all_tags( $m[2] ) );
    if ( '' === $texte || preg_match( '/\bid=/', $m[1] ) ) return $m[0];
    $ancre      = sanitize_title( $texte );
    $sommaire[] = array( 'ancre' => $ancre, 'texte' => $texte );
    return '<h2' . $m[1] . ' id="' . esc_attr( $ancre ) . '">' . $m[2] . '</h2>';
}, $contenu );

// Temps de lecture (≈ 200 mots/min) et étiquettes de la rando liée.
$minutes  = max( 1, (int) round( str_word_count( wp_strip_all_tags( $contenu ) ) / 200 ) );
$etiquettes = array( 'Récit' );
if ( $rando_ok ) {
    $r_terms = get_the_terms( $rando->ID, 'difficulte' );
    if ( $r_terms && ! is_wp_error( $r_terms ) ) $etiquettes[] = $r_terms[0]->name;
    $r_lieu = rando_nono_lieu_court( $rando->ID, 40 );
    if ( $r_lieu ) $etiquettes[] = $r_lieu;
}
$date_sortie = $rando_ok ? get_post_meta( $rando->ID, 'rando_date', true ) : '';
?>

<main class="simple-page" id="main-content">
  <?php rando_nono_breadcrumb(); ?>
  <article>
    <div class="art-etiquettes">
      <?php foreach ( $etiquettes as $i => $et ) : ?>
        <span class="art-etiquette<?php echo 0 === $i ? ' art-etiquette-recit' : ''; ?>"><?php echo esc_html( $et ); ?></span>
      <?php endforeach; ?>
    </div>
    <h1><?php the_title(); ?></h1>
    <p class="art-meta">
      Par Arnaud<?php if ( $date_sortie ) : ?> · sortie du <?php echo esc_html( $date_sortie ); ?><?php endif; ?> · <?php echo esc_html( $minutes ); ?> min de lecture
    </p>

    <?php if ( has_post_thumbnail() ) : ?>
      <?php echo get_the_post_thumbnail( $id, 'large', array( 'class' => 'art-photo', 'alt' => the_title_attribute( array( 'echo' => false ) ), 'decoding' => 'async', 'fetchpriority' => 'high' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- généré par WordPress ?>
    <?php endif; ?>

    <?php if ( $rando_ok ) :
        $b_dist  = get_post_meta( $rando->ID, 'rando_distance', true );
        $b_deniv = get_post_meta( $rando->ID, 'rando_denivele', true );
        $b_duree = get_post_meta( $rando->ID, 'rando_duree', true );
        $b_gpx   = get_post_meta( $rando->ID, 'rando_gpx_url', true );
        ?>
    <aside class="art-bref" aria-label="La fiche en bref">
      <div class="art-bref-titre">La fiche en bref</div>
      <div class="art-bref-chiffres">
        <?php if ( $b_dist ) : ?><div><b><?php echo esc_html( $b_dist ); ?></b><small>distance</small></div><?php endif; ?>
        <?php if ( $b_deniv ) : ?><div><b><?php echo esc_html( $b_deniv ); ?></b><small>dénivelé</small></div><?php endif; ?>
        <?php if ( $b_duree ) : ?><div><b><?php echo esc_html( $b_duree ); ?></b><small>de marche</small></div><?php endif; ?>
      </div>
      <div class="art-bref-actions">
        <?php if ( $b_gpx ) : ?>
          <a class="btn art-bref-gpx" href="<?php echo esc_url( $b_gpx ); ?>" download><?php echo rando_nono_icon( 'download' ); ?> Trace GPX</a>
        <?php endif; ?>
        <a class="btn art-bref-fiche" href="<?php echo esc_url( get_permalink( $rando ) ); ?>">Voir la fiche complète</a>
      </div>
    </aside>
    <?php endif; ?>

    <?php if ( count( $sommaire ) > 2 ) : ?>
    <nav class="art-sommaire" aria-label="Sommaire">
      <div class="art-sommaire-titre">Au sommaire</div>
      <ol>
        <?php foreach ( $sommaire as $s ) : ?>
          <li><a href="#<?php echo esc_attr( $s['ancre'] ); ?>"><?php echo esc_html( $s['texte'] ); ?></a></li>
        <?php endforeach; ?>
      </ol>
    </nav>
    <?php endif; ?>

    <div class="article-content"><?php echo $contenu; // phpcs:ignore WordPress.Security.EscapeOutput -- contenu filtré par the_content ?></div>
    <?php get_template_part( 'template-parts/cta', 'newsletter', array( 'type' => 'article' ) ); ?>
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
