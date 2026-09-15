<?php
/**
 * Préconnexions, préchargement et srcset de l'image du hero
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 2020-2096 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Manifest, theme-color et préchargement des polices critiques (au-dessus
 * de la ligne de flottaison sur toutes les pages : Abril Fatface pour les
 * titres, Merriweather Regular pour le texte).
 */
function rando_nono_head_extra() {
    $theme_uri = get_template_directory_uri();
    echo '<link rel="manifest" href="' . esc_url( $theme_uri . '/manifest.json' ) . '">' . "\n";
    echo '<meta name="theme-color" content="#2E5E3B">' . "\n";
    echo '<link rel="preload" href="' . esc_url( $theme_uri . '/assets/fonts/abril-fatface.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
    echo '<link rel="preload" href="' . esc_url( $theme_uri . '/assets/fonts/merriweather-regular.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
}
add_action( 'wp_head', 'rando_nono_head_extra', 1 );

/**
 * Préconnexion aux services externes (carte OSM, météo) sur les pages qui les
 * chargent réellement (mêmes conditions que l'enqueue de Leaflet/Chart.js dans
 * rando_nono_assets()) — la négociation DNS/TLS est faite en avance pendant que
 * la page se charge, au lieu d'attendre que le script JS déclenche la requête.
 */
function rando_nono_resource_hints() {
    $needs_map     = is_singular( 'randonnee' ) || is_post_type_archive( 'randonnee' );
    $needs_weather = is_singular( 'randonnee' );
    $ga_id         = rando_nono_ga_valid_id();
    $fb_id         = rando_nono_fb_valid_id();

    if ( $needs_map ) {
        echo '<link rel="preconnect" href="https://tile.openstreetmap.org">' . "\n";
        echo '<link rel="dns-prefetch" href="https://tile.openstreetmap.org">' . "\n";
    }
    if ( $needs_weather ) {
        echo '<link rel="preconnect" href="https://api.open-meteo.com" crossorigin>' . "\n";
        echo '<link rel="dns-prefetch" href="https://api.open-meteo.com">' . "\n";
    }
    // GA4/pixel Facebook ne se chargent qu'après consentement (voir
    // rando_nono_ga_assets()), mais la préconnexion peut démarrer plus tôt
    // sans rien télécharger : elle ne fait qu'accélérer la négociation
    // DNS/TLS si le visiteur accepte les cookies.
    if ( $ga_id ) {
        echo '<link rel="preconnect" href="https://www.googletagmanager.com">' . "\n";
        echo '<link rel="dns-prefetch" href="https://www.googletagmanager.com">' . "\n";
    }
    if ( $fb_id ) {
        echo '<link rel="preconnect" href="https://connect.facebook.net">' . "\n";
        echo '<link rel="dns-prefetch" href="https://connect.facebook.net">' . "\n";
    }
}
add_action( 'wp_head', 'rando_nono_resource_hints', 1 );

/**
 * Précharge l'image du hero (LCP de la page d'accueil) dans le format et la
 * définition réellement affichés, en WebP avec repli JPEG automatique via
 * `type="image/webp"` : le navigateur choisit la bonne largeur sans attendre
 * la découverte du <img> dans le HTML.
 */
function rando_nono_preload_hero_image() {
    if ( ! is_front_page() ) return;
    $theme_uri = get_template_directory_uri();
    $srcset = rando_nono_hero_srcset( 'webp' );
    echo '<link rel="preload" as="image" imagesrcset="' . esc_attr( $srcset ) . '" imagesizes="100vw" fetchpriority="high" type="image/webp">' . "\n";
}
add_action( 'wp_head', 'rando_nono_preload_hero_image', 1 );

/**
 * Construit la chaîne srcset des variantes responsive du hero, générées à
 * l'avance dans /assets/img/responsive/ (voir hero-bg-{largeur}.{format}).
 */
function rando_nono_hero_srcset( $format = 'webp' ) {
    $theme_uri = get_template_directory_uri();
    $widths = array( 640, 960, 1400, 1983 );
    $parts = array();
    foreach ( $widths as $w ) {
        $parts[] = $theme_uri . '/assets/img/responsive/hero-bg-' . $w . '.' . $format . ' ' . $w . 'w';
    }
    return implode( ', ', $parts );
}

