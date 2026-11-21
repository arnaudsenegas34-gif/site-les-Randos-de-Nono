<?php
/**
 * Fiche d'un objet du Matos de Nono.
 *
 * Avant : le modèle des articles (une photo, un titre, une date, et
 * l'étiquette « Récit » hors sujet). Maintenant : catégorie, photo, « Pourquoi
 * je l'emporte », poids et dimensions, lien produit, et les randos où il est
 * cité dans « Dans le sac ».
 */
get_header();

while ( have_posts() ) : the_post();

$mid       = get_the_ID();
$pourquoi  = get_post_meta( $mid, 'matos_pourquoi', true );
$lien      = get_post_meta( $mid, 'matos_lien', true );
$poids     = get_post_meta( $mid, 'matos_poids_g', true );
$largeur   = get_post_meta( $mid, 'matos_largeur_cm', true );
$hauteur   = get_post_meta( $mid, 'matos_hauteur_cm', true );
$essentiel = get_post_meta( $mid, 'matos_essentiel', true );
$cats      = get_the_terms( $mid, 'categorie_matos' );

// Randos où l'objet apparaît dans « Dans le sac » (titre cherché tel quel,
// sans tenir compte des majuscules).
$titre_matos = trim( get_the_title() );
$randos_avec = array();
if ( mb_strlen( $titre_matos ) >= 4 ) {
    $toutes = get_posts( array( 'post_type' => 'randonnee', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ) );
    foreach ( $toutes as $rid ) {
        $sac = (string) get_post_meta( $rid, 'rando_sac', true );
        if ( $sac && false !== mb_stripos( $sac, $titre_matos ) ) $randos_avec[] = $rid;
    }
}
?>

<main class="simple-page matos-page" id="main-content">
  <?php rando_nono_breadcrumb(); ?>
  <article>
    <div class="art-etiquettes">
      <?php if ( $cats && ! is_wp_error( $cats ) ) : foreach ( $cats as $cat ) : ?>
        <span class="art-etiquette art-etiquette-recit"><?php echo esc_html( $cat->name ); ?></span>
      <?php endforeach; endif; ?>
      <?php if ( $essentiel ) : ?><span class="art-etiquette">Dans le sac à chaque sortie</span><?php endif; ?>
    </div>
    <h1><?php the_title(); ?></h1>

    <?php if ( has_post_thumbnail() ) : ?>
      <div class="matos-photo"><?php echo get_the_post_thumbnail( $mid, 'large', array( 'alt' => the_title_attribute( array( 'echo' => false ) ), 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- généré par WordPress ?></div>
    <?php endif; ?>

    <?php if ( $pourquoi ) : ?>
      <h2 class="sr-section-title">Pourquoi je l&rsquo;emporte</h2>
      <p class="matos-pourquoi"><?php echo nl2br( esc_html( $pourquoi ) ); ?></p>
    <?php endif; ?>

    <?php if ( get_the_content() ) : ?>
      <div class="article-content"><?php the_content(); ?></div>
    <?php endif; ?>

    <?php if ( $poids || ( $largeur && $hauteur ) ) : ?>
      <div class="sr-meta-grid matos-carac">
        <?php if ( $poids ) : ?><div class="sr-meta-card"><div class="sr-meta-label">Poids</div><div class="sr-meta-value"><?php echo esc_html( number_format_i18n( (float) $poids ) ); ?> g</div></div><?php endif; ?>
        <?php if ( $largeur && $hauteur ) : ?><div class="sr-meta-card"><div class="sr-meta-label">Dimensions</div><div class="sr-meta-value"><?php echo esc_html( $largeur . ' × ' . $hauteur ); ?> cm</div></div><?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ( $randos_avec ) : ?>
      <h2 class="sr-section-title">Utilis&eacute; sur ces randos</h2>
      <div class="matos-randos">
        <?php foreach ( $randos_avec as $rid ) : ?>
          <a class="matos-rando" href="<?php echo esc_url( get_permalink( $rid ) ); ?>">
            <?php echo get_the_post_thumbnail( $rid, 'thumbnail', array( 'alt' => '', 'loading' => 'lazy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- généré par WordPress ?>
            <span><b><?php echo esc_html( get_the_title( $rid ) ); ?></b><small><?php echo esc_html( rando_nono_lieu_court( $rid ) ); ?></small></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <p class="matos-actions">
      <?php if ( $lien ) : ?>
        <a class="btn btn-sm" href="<?php echo esc_url( $lien ); ?>" target="_blank" rel="noopener nofollow">Voir le produit</a>
      <?php endif; ?>
      <a class="btn btn-sm btn-outline-vert" href="<?php echo esc_url( home_url( '/#matos' ) ); ?>">&larr; Tout le matos de Nono</a>
    </p>
  </article>
</main>

<?php endwhile; ?>

<?php get_footer(); ?>
