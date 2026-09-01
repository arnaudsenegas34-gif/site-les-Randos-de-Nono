<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>
(function () {
  try {
    var saved    = localStorage.getItem( 'rando-nono-theme' );
    var savedSys = localStorage.getItem( 'rando-nono-theme-sys' );
    var sysNow   = window.matchMedia( '(prefers-color-scheme: dark)' ).matches ? 'dark' : 'light';
    if ( ( saved === 'dark' || saved === 'light' ) && savedSys === sysNow ) {
      document.documentElement.setAttribute( 'data-theme', saved );
    } else {
      localStorage.removeItem( 'rando-nono-theme' );
      localStorage.removeItem( 'rando-nono-theme-sys' );
    }
  } catch ( e ) {}
})();
</script>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main-content">Aller au contenu principal</a>

<header class="site-header">
  <a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">Les Randos de <span>Nono</span></a>

  <?php
  $rando_nono_is_home    = is_front_page();
  $rando_nono_is_archive = is_post_type_archive( 'randonnee' ) || is_singular( 'randonnee' );
  $rando_nono_is_favoris = is_page( 'favoris' );
  $rando_nono_is_contact = is_page( 'contact' );
  // "Guides & Sélections" couvre aussi les pages Article/Guide, le blog et
  // les catégories/étiquettes — pour que le lien reste actif partout où on
  // navigue depuis ce hub.
  $rando_nono_is_guides  = is_page( 'guides' ) || 'page-article-guide.php' === get_page_template_slug()
      || is_singular( 'post' ) || is_home() || is_category() || is_tag();

  // Source unique des liens de nav — utilisée par .nav-buttons (desktop)
  // ET .nav-mobile-drawer, pour qu'ils ne puissent plus diverger.
  //
  // « Matos », « Statistiques » et « À propos » ne sont pas des pages : ce
  // sont trois ancres DE la page d'accueil. Les laisser au premier niveau
  // gonflait la barre à 8 entrées (elle débordait) et laissait croire à
  // trois destinations distinctes. Elles vivent donc sous « Accueil », qui
  // reste un lien cliquable à part entière.
  $rando_nono_nav_items = array(
      array(
          'key' => 'accueil', 'href' => home_url( '/' ), 'label' => 'Accueil', 'current' => $rando_nono_is_home,
          'children' => array(
              array( 'key' => 'matos',        'href' => home_url( '/' ) . '#matos',        'label' => 'Matos de Nono' ),
              array( 'key' => 'statistiques', 'href' => home_url( '/' ) . '#statistiques', 'label' => 'Statistiques' ),
              array( 'key' => 'apropos',      'href' => home_url( '/' ) . '#apropos',      'label' => 'À propos' ),
          ),
      ),
      array( 'key' => 'randos-archive', 'href' => get_post_type_archive_link( 'randonnee' ),    'label' => 'Toutes les randos',  'current' => $rando_nono_is_archive ),
      array( 'key' => 'guides',         'href' => home_url( '/guides/' ),                       'label' => 'Guides & Sélections','current' => $rando_nono_is_guides ),
      array( 'key' => 'favoris',        'href' => home_url( '/favoris/' ),                      'label' => 'Mes randos à faire', 'current' => $rando_nono_is_favoris ),
      array( 'key' => 'contact',        'href' => home_url( '/contact/' ),                      'label' => 'Contact',            'current' => $rando_nono_is_contact ),
  );
  ?>

  <?php if ( has_nav_menu( 'primary' ) ) : ?>
    <nav>
      <?php
      wp_nav_menu( array(
          'theme_location' => 'primary',
          'container'      => false,
          'items_wrap'     => '<ul class="nav-links">%3$s</ul>',
      ) );
      ?>
    </nav>
  <?php else : ?>
    <nav class="nav-buttons">
      <?php foreach ( $rando_nono_nav_items as $item ) : ?>
        <?php if ( empty( $item['children'] ) ) : ?>
          <a href="<?php echo esc_url( $item['href'] ); ?>" data-nav-key="<?php echo esc_attr( $item['key'] ); ?>" class="btn-nav<?php echo $item['current'] ? ' is-current' : ''; ?>"><?php echo esc_html( $item['label'] ); ?></a>
        <?php else : ?>
          <?php
          // Le sous-menu s'ouvre au survol ET au focus clavier
          // (`:focus-within` en CSS) : le lien parent reste un vrai lien,
          // on ne perd pas l'accès à l'accueil pour ouvrir un menu.
          ?>
          <div class="nav-group<?php echo $item['current'] ? ' has-current' : ''; ?>">
            <a href="<?php echo esc_url( $item['href'] ); ?>" data-nav-key="<?php echo esc_attr( $item['key'] ); ?>" class="btn-nav btn-nav-parent<?php echo $item['current'] ? ' is-current' : ''; ?>">
              <?php echo esc_html( $item['label'] ); ?>
              <?php echo rando_nono_icon( 'chevron-down', 'nav-caret' ); ?>
            </a>
            <div class="nav-submenu">
              <?php foreach ( $item['children'] as $child ) : ?>
                <a href="<?php echo esc_url( $child['href'] ); ?>" data-nav-key="<?php echo esc_attr( $child['key'] ); ?>"><?php echo esc_html( $child['label'] ); ?></a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>

  <div class="site-search-wrap">
    <form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-search" role="search">
      <input type="search" name="s" placeholder="Rechercher..." value="<?php echo esc_attr( get_search_query() ); ?>">
      <button type="submit" aria-label="Rechercher"><?php echo rando_nono_icon( 'search' ); ?></button>
    </form>
  </div>

  <button class="theme-toggle" id="theme-toggle" type="button" aria-label="Activer le mode sombre" aria-pressed="false">
    <?php echo rando_nono_icon( 'sun', 'theme-toggle-icon theme-toggle-icon-sun' ); ?>
    <?php echo rando_nono_icon( 'moon', 'theme-toggle-icon theme-toggle-icon-moon' ); ?>
  </button>

  <button class="menu-toggle" id="menu-toggle" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="nav-mobile-drawer">☰</button>
</header>

<div class="nav-mobile-drawer" id="nav-mobile-drawer">
  <form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-search site-search-mobile" role="search">
    <input type="search" name="s" placeholder="Rechercher..." value="<?php echo esc_attr( get_search_query() ); ?>">
    <button type="submit" aria-label="Rechercher"><?php echo rando_nono_icon( 'search' ); ?></button>
  </form>
  <?php foreach ( $rando_nono_nav_items as $item ) : ?>
    <a href="<?php echo esc_url( $item['href'] ); ?>" data-nav-key="<?php echo esc_attr( $item['key'] ); ?>" class="<?php echo $item['current'] ? 'is-current' : ''; ?>"><?php echo esc_html( $item['label'] ); ?></a>
    <?php if ( ! empty( $item['children'] ) ) : ?>
      <?php // Pas de repli à déplier sur mobile : les 3 ancres sont visibles
            // d'emblée, simplement décalées sous leur parent. ?>
      <div class="nav-drawer-sub">
        <?php foreach ( $item['children'] as $child ) : ?>
          <a href="<?php echo esc_url( $child['href'] ); ?>" data-nav-key="<?php echo esc_attr( $child['key'] ); ?>"><?php echo esc_html( $child['label'] ); ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>
</div>
