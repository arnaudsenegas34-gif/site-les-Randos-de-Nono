<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

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
    </nav>
  <?php endif; ?>

  <button class="menu-toggle" id="menu-toggle" aria-label="Ouvrir le menu">☰</button>
</header>

<div class="nav-mobile-drawer" id="nav-mobile-drawer">
  <a href="<?php echo esc_url( get_post_type_archive_link( 'randonnee' ) ); ?>" class="solid">Toutes les randos</a>
  <a href="<?php echo esc_url( home_url( '/' ) ); ?>#matos">Matos de Nono</a>
  <a href="<?php echo esc_url( home_url( '/' ) ); ?>#statistiques">Statistiques</a>
  <a href="<?php echo esc_url( home_url( '/' ) ); ?>#apropos">À propos</a>
</div>
