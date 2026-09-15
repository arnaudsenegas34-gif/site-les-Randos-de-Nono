<?php
/**
 * Archive d'un niveau de difficulté — /difficulte/<slug>/
 *
 * Sans ce gabarit, WordPress retombait sur index.php, un repli de 18 lignes
 * qui boucle sur the_title() en <h1> et the_content() : la page affichait
 * jusqu'à dix <h1> et le texte intégral de chaque randonnée, sans image, sans
 * carte, sans mise en forme. Ces URL n'avaient aucun lien entrant mais
 * étaient déclarées au sitemap XML, donc soumises à Google.
 *
 * Elles ont maintenant un vrai contenu — et les pastilles de difficulté des
 * cartes pointent vers elles, ce qui leur donne enfin des liens entrants.
 */
get_header();
rando_nono_breadcrumb();

$terme = get_queried_object();
$paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

$query = new WP_Query( array(
    'post_type'      => 'randonnee',
    'posts_per_page' => 9,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'tax_query'      => array(
        array( 'taxonomy' => 'difficulte', 'field' => 'term_id', 'terms' => $terme->term_id ),
    ),
) );

$rang  = rando_nono_difficulte_rang( $terme );
$total = rando_nono_difficulte_total();
?>

<main id="main-content">
  <section class="site-section">
    <div class="section-inner">
      <div class="section-eyebrow">Niveau de difficulté</div>
      <h1 class="section-title"><?php echo esc_html( $terme->name ); ?></h1>
      <div class="divider"></div>

      <p class="section-sub">
        <?php
        if ( $terme->description ) {
            echo esc_html( $terme->description );
        } else {
            printf(
                /* translators: 1: nom de la difficulté, 2: rang, 3: nombre de niveaux */
                esc_html__( 'Les randonnées cotées « %1$s »%2$s : distance, dénivelé, durée et trace GPX pour chacune.', 'rando-nono' ),
                esc_html( $terme->name ),
                $rang ? esc_html( sprintf( ' (niveau %d sur %d)', $rang, $total ) ) : ''
            );
        }
        ?>
      </p>

      <p class="archive-count">
        <?php
        printf(
            esc_html( _n( '%s randonnée à ce niveau', '%s randonnées à ce niveau', $query->found_posts, 'rando-nono' ) ),
            esc_html( number_format_i18n( $query->found_posts ) )
        );
        ?>
        — <a href="<?php echo esc_url( get_post_type_archive_link( 'randonnee' ) ); ?>">voir toutes les randonnées</a>
      </p>

      <?php if ( $query->have_posts() ) : ?>
        <div class="randos-grid">
          <?php
          while ( $query->have_posts() ) :
              $query->the_post();
              // h2 : le titre de la page est un h1, les cartes viennent juste
              // en dessous — un h3 créerait un saut de niveau.
              get_template_part( 'template-parts/card', 'rando', array( 'niveau' => 'h2' ) );
          endwhile;
          ?>
        </div>

        <?php
        $pagination = paginate_links( array(
            'total'     => $query->max_num_pages,
            'current'   => $paged,
            'prev_text' => '←',
            'next_text' => '→',
            'type'      => 'array',
        ) );
        if ( $pagination ) :
        ?>
          <nav class="pagination" aria-label="Pagination des randonnées">
            <?php foreach ( $pagination as $lien ) { echo wp_kses_post( $lien ); } ?>
          </nav>
        <?php endif; ?>

      <?php else : ?>
        <p class="archive-vide">
          Aucune randonnée à ce niveau pour l'instant.
          <a href="<?php echo esc_url( get_post_type_archive_link( 'randonnee' ) ); ?>">Voir toutes les randonnées</a>
        </p>
      <?php endif; ?>

      <?php wp_reset_postdata(); ?>
    </div>
  </section>
</main>

<?php get_footer(); ?>
