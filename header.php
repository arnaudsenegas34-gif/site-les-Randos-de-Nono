<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main-content">Aller au contenu principal</a>

<header class="site-header">
  <a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">Les Randos de <span>Nono</span></a>

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
      <a href="<?php echo esc_url( get_post_type_archive_link( 'randonnee' ) ); ?>" class="btn-nav btn-nav-solid">Toutes les randos</a>
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>#matos" class="btn-nav">Matos de Nono</a>
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>#statistiques" class="btn-nav">Statistiques</a>
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>#apropos" class="btn-nav">À propos</a>
      <a href="<?php echo esc_url( home_url( '/favoris/' ) ); ?>" class="btn-nav">Mes favoris</a>
      <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>" class="btn-nav">Contact</a>
    </nav>
  <?php endif; ?>

  <div class="site-search-wrap">
    <form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-search" role="search">
      <input type="search" name="s" placeholder="Rechercher..." value="<?php echo esc_attr( get_search_query() ); ?>">
      <button type="submit" aria-label="Rechercher"><?php echo rando_nono_icon( 'search' ); ?></button>
    </form>
  </div>

  <button class="menu-toggle" id="menu-toggle" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="nav-mobile-drawer">☰</button>
</header>

<div class="nav-mobile-drawer" id="nav-mobile-drawer">
  <form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-search site-search-mobile" role="search">
    <input type="search" name="s" placeholder="Rechercher..." value="<?php echo esc_attr( get_search_query() ); ?>">
    <button type="submit" aria-label="Rechercher"><?php echo rando_nono_icon( 'search' ); ?></button>
  </form>
  <a href="<?php echo esc_url( get_post_type_archive_link( 'randonnee' ) ); ?>" class="solid">Toutes les randos</a>
  <a href="<?php echo esc_url( home_url( '/' ) ); ?>#matos">Matos de Nono</a>
  <a href="<?php echo esc_url( home_url( '/' ) ); ?>#statistiques">Statistiques</a>
  <a href="<?php echo esc_url( home_url( '/' ) ); ?>#apropos">À propos</a>
  <a href="<?php echo esc_url( home_url( '/favoris/' ) ); ?>">Mes randos à faire</a>
  <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contact</a>
</div>
