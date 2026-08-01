<?php get_header(); ?>

<main id="main-content">

<!-- ════════ HERO ════════ -->
<section class="hero" id="hero">
  <picture class="hero-bg-layer" id="hero-bg-layer">
    <source type="image/webp" srcset="<?php echo esc_attr( rando_nono_hero_srcset( 'webp' ) ); ?>" sizes="100vw">
    <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/responsive/hero-bg-1983.jpg' ); ?>"
         srcset="<?php echo esc_attr( rando_nono_hero_srcset( 'jpg' ) ); ?>"
         sizes="100vw"
         alt="Sentier de randonnée dans la garrigue de l'Hérault"
         fetchpriority="high" decoding="async">
  </picture>
  <div class="hero-inner">
    <p class="hero-eyebrow">Hérault · Languedoc · et d'autres</p>
    <h1>Les Randos de <span style="color:var(--beige)">Nono</span></h1>
    <p class="hero-desc">Récits de randonnées, traces GPX à télécharger, météo en temps réel et les détails de chaque sortie.</p>
  </div>
</section>

<?php
$randos_count = wp_count_posts( 'randonnee' )->publish;
$global_avis  = rando_nono_get_avis_stats_global();
?>

<!-- ════════ PREUVE SOCIALE ════════ -->
<section class="trust-bar" aria-label="Confiance">
  <?php if ( $global_avis['total'] > 0 ) : ?>
    <div class="trust-item">
      <span class="trust-stars" aria-hidden="true"><?php echo str_repeat( '★', (int) round( $global_avis['moyenne'] ) ) . str_repeat( '☆', 5 - (int) round( $global_avis['moyenne'] ) ); ?></span>
      <span><strong><?php echo esc_html( number_format_i18n( $global_avis['moyenne'], 1 ) ); ?>/5</strong> sur <?php echo esc_html( $global_avis['total'] ); ?> avis de randonneurs</span>
    </div>
  <?php endif; ?>
  <div class="trust-item">
    <span><strong><?php echo esc_html( $randos_count ); ?></strong> randonnées documentées avec trace GPX</span>
  </div>
  <a class="trust-item trust-instagram" href="https://www.instagram.com/a._.sng?igsh=MWpyYWVyazh6NWJ6dw==" target="_blank" rel="noopener noreferrer">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M12 2c2.717 0 3.056.01 4.122.06 1.065.048 1.79.218 2.428.465a4.9 4.9 0 0 1 1.771 1.153 4.9 4.9 0 0 1 1.153 1.771c.247.637.417 1.363.465 2.428.05 1.066.06 1.405.06 4.122s-.01 3.056-.06 4.122c-.048 1.065-.218 1.79-.465 2.428a4.9 4.9 0 0 1-1.153 1.771 4.9 4.9 0 0 1-1.771 1.153c-.637.247-1.363.417-2.428.465-1.066.05-1.405.06-4.122.06s-3.056-.01-4.122-.06c-1.065-.048-1.79-.218-2.428-.465a4.9 4.9 0 0 1-1.771-1.153 4.9 4.9 0 0 1-1.153-1.771c-.247-.637-.417-1.363-.465-2.428C2.01 15.056 2 14.717 2 12s.01-3.056.06-4.122c.048-1.065.218-1.79.465-2.428a4.9 4.9 0 0 1 1.153-1.771A4.9 4.9 0 0 1 5.45 2.525c.637-.247 1.363-.417 2.428-.465C8.944 2.01 9.283 2 12 2zm0 1.802c-2.67 0-2.986.01-4.04.058-.976.045-1.505.207-1.858.344-.467.182-.8.399-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.858-.048 1.054-.058 1.37-.058 4.04s.01 2.986.058 4.04c.045.976.207 1.505.344 1.858.182.467.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.858.344 1.054.048 1.37.058 4.04.058s2.986-.01 4.04-.058c.976-.045 1.505-.207 1.858-.344.467-.182.8-.399 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.858.048-1.054.058-1.37.058-4.04s-.01-2.986-.058-4.04c-.045-.976-.207-1.505-.344-1.858a3.09 3.09 0 0 0-.748-1.15 3.09 3.09 0 0 0-1.15-.748c-.353-.137-.882-.3-1.858-.344-1.054-.048-1.37-.058-4.04-.058zm0 3.063a5.135 5.135 0 1 1 0 10.27 5.135 5.135 0 0 1 0-10.27zm0 1.802a3.333 3.333 0 1 0 0 6.666 3.333 3.333 0 0 0 0-6.666zm5.338-1.802a1.2 1.2 0 1 1-2.4 0 1.2 1.2 0 0 1 2.4 0z"/></svg>
    Suivi sur Instagram
  </a>
</section>

<?php
// Rando à la une (cochée) sinon la plus récente
$featured_query = new WP_Query( array(
    'post_type'      => 'randonnee',
    'posts_per_page' => 1,
    'meta_key'       => 'rando_a_la_une',
    'meta_value'     => '1',
) );
if ( ! $featured_query->have_posts() ) {
    $featured_query = new WP_Query( array(
        'post_type'      => 'randonnee',
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );
}
?>

<!-- ════════ DERNIÈRE RANDONNÉE ════════ -->
<?php if ( $featured_query->have_posts() ) :
    $featured_query->the_post();
    $fid         = get_the_ID();
    $f_lieu      = get_post_meta( $fid, 'rando_lieu', true );
    $f_dist      = get_post_meta( $fid, 'rando_distance', true );
    $f_deniv     = get_post_meta( $fid, 'rando_denivele', true );
    $f_duree     = get_post_meta( $fid, 'rando_duree', true );
    $f_date      = get_post_meta( $fid, 'rando_date', true );
    $f_gpx       = get_post_meta( $fid, 'rando_gpx_url', true );
    $f_thumb     = has_post_thumbnail( $fid ) ? get_the_post_thumbnail( $fid, 'rando-hero', array(
        'alt'           => get_the_title( $fid ),
        'decoding'      => 'async',
        'fetchpriority' => 'high',
        'style'         => 'width:100%;height:100%;object-fit:cover;position:absolute;inset:0',
    ) ) : '';
    ?>
    <section id="derniere-rando" class="site-section">
      <div class="section-eyebrow">À l'instant</div>
      <h2 class="section-title">Dernière randonnée</h2>
      <div class="divider"></div>

      <div class="derniere-card">
        <div class="derniere-img-wrap">
          <span class="derniere-badge">Nouveau</span>
          <?php if ( $f_thumb ) : ?>
            <?php echo $f_thumb; // phpcs:ignore WordPress.Security.EscapeOutput -- généré par get_the_post_thumbnail(), déjà échappé par WordPress ?>
          <?php else : ?>
            <picture>
              <source type="image/webp" srcset="<?php echo esc_url( get_template_directory_uri() . '/assets/img/responsive/placeholder-rando-800.webp' ); ?>">
              <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/responsive/placeholder-rando-800.jpg' ); ?>" alt="Photo à venir" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0" decoding="async" fetchpriority="high">
            </picture>
          <?php endif; ?>
        </div>
        <div class="derniere-content">
          <span class="meta-item"><?php echo rando_nono_icon( 'pin' ); ?> <?php echo esc_html( $f_lieu ); ?> · <?php echo rando_nono_icon( 'calendar' ); ?> <?php echo esc_html( $f_date ); ?></span>
          <h3 class="card-title"><a href="<?php echo esc_url( get_permalink( $fid ) ); ?>"><?php the_title(); ?></a></h3>
          <p class="derniere-desc"><?php echo esc_html( wp_trim_words( get_the_content(), 28 ) ); ?></p>

          <div class="derniere-stats-row">
            <div class="derniere-stat"><div class="num"><?php echo esc_html( $f_dist ); ?></div><div class="label">Distance</div></div>
            <div class="derniere-stat"><div class="num"><?php echo esc_html( $f_deniv ); ?></div><div class="label">Dénivelé</div></div>
            <div class="derniere-stat"><div class="num"><?php echo esc_html( $f_duree ); ?></div><div class="label">Durée</div></div>
          </div>

          <div class="derniere-actions">
            <a class="btn" href="<?php echo esc_url( get_permalink( $fid ) ); ?>">Voir la randonnée</a>
            <?php if ( $f_gpx ) : ?>
              <a class="btn btn-outline" style="border-color:var(--accent);color:var(--accent)" href="<?php echo esc_url( $f_gpx ); ?>" download><?php echo rando_nono_icon( 'download' ); ?> GPX</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
    <?php
    wp_reset_postdata();
endif;
?>

<!-- ════════ RANDONNÉES ════════ -->
<section id="randos" class="site-section">
  <div class="section-eyebrow">Mes sorties</div>
  <h2 class="section-title">Randonnées</h2>
  <div class="divider"></div>
  <p class="section-sub">Chaque sortie est documentée : tracé GPX, photos, météo du lieu et détail de l'itinéraire.</p>
  <p>Ce carnet de randonnée rassemble mes itinéraires de randonnée pédestre dans l'Hérault, les Cévennes, le Languedoc et au-delà. Pour chaque randonnée, tu trouveras la distance, le dénivelé, la difficulté, la trace GPX à télécharger et un récit détaillé de la sortie, pour t'aider à préparer la tienne.</p>

  <div class="randos-grid">
    <?php
    $all_randos = new WP_Query( array(
        'post_type'      => 'randonnee',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    $i = 0;
    if ( $all_randos->have_posts() ) :
        while ( $all_randos->have_posts() ) : $all_randos->the_post();
            $i++;
            $extra_class = ( $i > 5 ) ? ' rando-hidden' : '';
            echo '<div class="' . esc_attr( ltrim( $extra_class ) ) . '">';
            get_template_part( 'template-parts/card', 'rando' );
            echo '</div>';
        endwhile;
        wp_reset_postdata();
    else :
        echo '<p style="color:var(--gris)">Aucune randonnée publiée pour le moment. Ajoute ta première sortie depuis l\'administration → Randonnées → Ajouter.</p>';
    endif;
    ?>
  </div>

  <?php if ( $i > 5 ) : ?>
  <div class="see-more-wrap">
    <a href="<?php echo esc_url( get_post_type_archive_link( 'randonnee' ) ); ?>" class="btn">Toutes les randonnées (<?php echo esc_html( $i ); ?>)</a>
  </div>
  <?php endif; ?>
</section>

<!-- ════════ MATOS DE NONO ════════ -->
<section id="matos" class="site-section" style="background:var(--surface)">
  <div class="section-wave section-wave-top">
    <svg viewBox="0 0 1200 60" preserveAspectRatio="none"><path d="M0,30 C300,60 900,0 1200,30 L1200,0 L0,0 Z" fill="#FAF8F3"></path></svg>
  </div>
  <div class="section-eyebrow">Équipement</div>
  <h2 class="section-title">Matos de Nono</h2>
  <div class="divider"></div>
  <p class="section-sub">Le matériel que j'utilise vraiment, sorti après sortie. Clique sur un équipement pour en savoir plus.</p>

  <?php
  $matos_cats = get_terms( array( 'taxonomy' => 'categorie_matos', 'hide_empty' => true ) );
  if ( $matos_cats && ! is_wp_error( $matos_cats ) && count( $matos_cats ) > 1 ) :
  ?>
  <div class="matos-filters" id="matos-filters">
    <button class="matos-filter-btn active" data-filter="*">Tout voir</button>
    <?php foreach ( $matos_cats as $cat ) : ?>
      <button class="matos-filter-btn" data-filter="<?php echo esc_attr( $cat->slug ); ?>">
        <?php echo esc_html( $cat->name ); ?>
      </button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="matos-grid" id="matos-grid">
    <?php
    $matos_query = new WP_Query( array(
        'post_type'      => 'matos',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    if ( $matos_query->have_posts() ) :
        while ( $matos_query->have_posts() ) : $matos_query->the_post();
            $m_id        = get_the_ID();
            $m_lien      = get_post_meta( $m_id, 'matos_lien', true );
            $m_pourquoi  = get_post_meta( $m_id, 'matos_pourquoi', true );
            $m_essentiel = get_post_meta( $m_id, 'matos_essentiel', true );
            $m_cats      = get_the_terms( $m_id, 'categorie_matos' );
            $m_cat_name  = $m_cats && ! is_wp_error( $m_cats ) ? $m_cats[0]->name : '';
            $m_cat_slug  = $m_cats && ! is_wp_error( $m_cats ) ? $m_cats[0]->slug : '';
            $m_thumb      = get_the_post_thumbnail_url( $m_id, 'medium' );
            $m_thumb_lg   = get_the_post_thumbnail_url( $m_id, 'large' );
            $m_thumb_tag  = has_post_thumbnail( $m_id ) ? get_the_post_thumbnail( $m_id, 'rando-card', array( 'loading' => 'lazy', 'decoding' => 'async' ) ) : '';
            $m_desc       = wp_strip_all_tags( get_the_content() );
            $m_largeur    = get_post_meta( $m_id, 'matos_largeur_cm', true );
            $m_hauteur    = get_post_meta( $m_id, 'matos_hauteur_cm', true );
            $m_poids      = get_post_meta( $m_id, 'matos_poids_g', true );
            ?>
            <div class="matos-card<?php if ( $m_essentiel ) echo ' is-essentiel'; ?>"
                 data-cat="<?php echo esc_attr( $m_cat_slug ); ?>"
                 data-name="<?php echo esc_attr( get_the_title() ); ?>"
                 data-cat-label="<?php echo esc_attr( $m_cat_name ); ?>"
                 data-desc="<?php echo esc_attr( $m_desc ); ?>"
                 data-pourquoi="<?php echo esc_attr( $m_pourquoi ); ?>"
                 data-lien="<?php echo esc_attr( $m_lien ); ?>"
                 data-thumb="<?php echo esc_attr( $m_thumb_lg ?: $m_thumb ); ?>"
                 data-largeur="<?php echo esc_attr( $m_largeur ); ?>"
                 data-hauteur="<?php echo esc_attr( $m_hauteur ); ?>"
                 data-poids="<?php echo esc_attr( $m_poids ); ?>"
                 role="button"
                 tabindex="0"
                 aria-label="Voir le détail de <?php the_title_attribute(); ?>">
              <div class="matos-img">
                <?php if ( $m_thumb_tag ) : ?>
                  <?php echo $m_thumb_tag; // phpcs:ignore WordPress.Security.EscapeOutput -- généré par get_the_post_thumbnail(), déjà échappé par WordPress ?>
                <?php else : ?>
                  <?php echo rando_nono_icon( 'backpack', 'icon-svg-lg' ); ?>
                <?php endif; ?>
                <div class="matos-img-overlay"><span>+</span></div>
              </div>
              <span class="matos-name"><?php the_title(); ?></span>
            </div>
            <?php
        endwhile;
        wp_reset_postdata();
    else :
        echo '<p style="color:var(--gris)">Aucun matériel ajouté. Ajoute ton équipement depuis l\'administration → Matos de Nono → Ajouter.</p>';
    endif;
    ?>
  </div>
</section>

<?php get_template_part( 'template-parts/matos', 'panel' ); ?>

<!-- ════════ STATISTIQUES (unique section verte, unique emplacement des stats) ════════ -->
<?php
/**
 * Calcul dynamique de toutes les statistiques à partir des randonnées publiées.
 * Les champs distance/dénivelé sont du texte libre ("12 km", "+380 m"),
 * on en extrait donc la valeur numérique avec une regex tolérante.
 */
function rando_nono_extract_number( $text ) {
    if ( ! $text ) return 0;
    preg_match( '/-?[\d]+(?:[.,]\d+)?/', $text, $matches );
    if ( empty( $matches ) ) return 0;
    return (float) str_replace( ',', '.', $matches[0] );
}

$regions = array();
$total_km = 0;
$total_deniv_pos = 0;
$total_deniv_neg = 0;

$stats_query = new WP_Query( array( 'post_type' => 'randonnee', 'posts_per_page' => -1 ) );
if ( $stats_query->have_posts() ) {
    while ( $stats_query->have_posts() ) {
        $stats_query->the_post();
        $sid = get_the_ID();

        $lieu_rando = get_post_meta( $sid, 'rando_lieu', true );
        if ( $lieu_rando ) {
            $parts = explode( ',', $lieu_rando );
            $region = trim( end( $parts ) );
            if ( $region ) $regions[ strtolower( $region ) ] = true;
        }

        $total_km += abs( rando_nono_extract_number( get_post_meta( $sid, 'rando_distance', true ) ) );
        $total_deniv_pos += abs( rando_nono_extract_number( get_post_meta( $sid, 'rando_denivele', true ) ) );
        $total_deniv_neg += abs( rando_nono_extract_number( get_post_meta( $sid, 'rando_denivele_neg', true ) ) );
    }
    wp_reset_postdata();
}
$regions_count = count( $regions );
?>
<section id="statistiques" class="site-section">
  <div class="section-wave section-wave-top">
    <svg viewBox="0 0 1200 60" preserveAspectRatio="none"><path d="M0,30 C300,60 900,0 1200,30 L1200,0 L0,0 Z" fill="#FAF8F3"></path></svg>
  </div>
  <div class="section-eyebrow">En chiffres</div>
  <h2 class="section-title">Statistiques</h2>
  <div class="divider" style="background:var(--orange)"></div>
  <p class="section-sub">Le cumul de toutes mes sorties documentées sur ce site.</p>

  <div class="stats-grid stats-grid-5">
    <div class="stat-block">
      <div class="big-num" data-count="<?php echo esc_attr( $randos_count ); ?>">0</div>
      <div class="big-label">Randonnées</div>
    </div>
    <div class="stat-block">
      <div class="big-num" data-count="<?php echo esc_attr( round( $total_km ) ); ?>" data-suffix=" km">0</div>
      <div class="big-label">Kilomètres parcourus</div>
    </div>
    <div class="stat-block">
      <div class="big-num" data-count="<?php echo esc_attr( round( $total_deniv_pos ) ); ?>" data-suffix=" m">0</div>
      <div class="big-label">Dénivelé positif cumulé</div>
    </div>
    <div class="stat-block">
      <div class="big-num" data-count="<?php echo esc_attr( round( $total_deniv_neg ) ); ?>" data-suffix=" m">0</div>
      <div class="big-label">Dénivelé négatif cumulé</div>
    </div>
    <div class="stat-block">
      <div class="big-num" data-count="<?php echo esc_attr( $regions_count ); ?>">0</div>
      <div class="big-label">Régions parcourues</div>
    </div>
  </div>
  <div class="section-wave section-wave-bottom">
    <svg viewBox="0 0 1200 60" preserveAspectRatio="none"><path d="M0,30 C300,60 900,0 1200,30 L1200,0 L0,0 Z" fill="#FAF8F3"></path></svg>
  </div>
</section>

<!-- ════════ À PROPOS ════════ -->
<?php
$projet_actif       = get_option( 'rando_nono_projet_actif' );
$projet_titre       = get_option( 'rando_nono_projet_titre' );
$projet_description = get_option( 'rando_nono_projet_description' );
$projet_distance    = get_option( 'rando_nono_projet_distance' );
$projet_denivele    = get_option( 'rando_nono_projet_denivele' );
$projet_date        = get_option( 'rando_nono_projet_date' );
$projet_groupe      = get_option( 'rando_nono_projet_groupe' );
$show_projet = $projet_actif && $projet_titre;
?>
<section id="apropos" class="site-section">
  <div>
    <?php
    // Dépose une photo à /assets/img/apropos-photo.jpg pour l'afficher ici —
    // à défaut, une icône de substitution est affichée automatiquement.
    $apropos_photo_file = get_template_directory() . '/assets/img/apropos-photo.jpg';
    $apropos_photo_uri  = get_template_directory_uri() . '/assets/img/apropos-photo.jpg';
    ?>
    <div class="apropos-author">
      <?php if ( file_exists( $apropos_photo_file ) ) : ?>
        <img class="apropos-photo" src="<?php echo esc_url( $apropos_photo_uri ); ?>" alt="Arnaud, auteur des Randos de Nono" loading="lazy" decoding="async">
      <?php else : ?>
        <span class="apropos-photo apropos-photo-placeholder" aria-hidden="true"><?php echo rando_nono_icon( 'mountain' ); ?></span>
      <?php endif; ?>
      <div class="apropos-author-info">
        <span class="apropos-author-name">Arnaud</span>
        <span class="apropos-author-role">Auteur — Les Randos de Nono</span>
      </div>
    </div>
    <div class="section-eyebrow">Qui suis-je</div>
    <h2 class="section-title">Passionné de montagne & de sentiers</h2>
    <div class="divider"></div>
    <p>Je m'appelle Arnaud. Je vis à Aniane, dans l'Hérault, entre garrigue et Cévennes — un terrain de jeu idéal pour la randonnée toute l'année.</p>
    <p>Ce site est né d'une habitude simple : garder une trace de mes sorties. Il rassemble mes itinéraires avec leurs traces GPX, pour les retrouver facilement et pour que d'autres puissent s'en inspirer.</p>
    <p>Chaque fiche randonnée précise la distance, le dénivelé positif et négatif, la durée estimée et la meilleure saison pour partir, avec un lien Google Maps vers le point de départ. Tu y trouveras aussi mes conseils pratiques (eau, équipement, horaires de départ) et le contenu exact de mon sac pour cette sortie.</p>
    <?php if ( $show_projet && $projet_description ) : ?>
      <p><?php echo esc_html( $projet_description ); ?></p>
    <?php endif; ?>
    <p style="font-size:0.85rem;opacity:0.7;margin-top:1.25rem">Une question, une suggestion ? <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" style="color:var(--orange)">Contacte-moi</a></p>
  </div>
  <div class="apropos-visual">
    <div class="apropos-stat">
      <div class="num"><?php echo esc_html( $randos_count ); ?></div>
      <div class="info"><div class="label">Randonnées documentées</div><div class="sub">Hérault, Pyrénées, Cévennes</div></div>
    </div>
    <?php if ( $show_projet ) : ?>
      <div class="apropos-stat">
        <div class="num"><?php echo $projet_date ? esc_html( $projet_date ) : '—'; ?></div>
        <div class="info">
          <div class="label">Prochain projet — <?php echo esc_html( $projet_titre ); ?></div>
          <div class="sub">
            <?php
            $details = array_filter( array( $projet_distance, $projet_denivele, $projet_groupe ) );
            echo esc_html( implode( ' · ', $details ) );
            ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

</main>

<?php get_footer(); ?>
