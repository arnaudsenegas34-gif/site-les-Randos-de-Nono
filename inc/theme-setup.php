<?php
/**
 * Setup du thème + enqueue des styles et scripts.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ──────────────────────────────────────────
   1. SETUP DU THÈME
   ────────────────────────────────────────── */
function rando_nono_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'menus' );
    register_nav_menus( array( 'primary' => __( 'Menu principal', 'rando-nono' ) ) );

    // Tailles d'images dédiées, pour servir des fichiers de la bonne
    // définition (au lieu du "large" générique) et permettre un vrai srcset
    // responsive sur les cartes, les héros et les galeries.
    add_image_size( 'rando-card', 640, 420, true );
    add_image_size( 'rando-hero', 1600, 900, true );
    add_image_size( 'rando-gallery', 1000, 0, false );
}
add_action( 'after_setup_theme', 'rando_nono_setup' );

/**
 * Génère automatiquement les tailles d'images (thumbnail, medium, rando-card…)
 * au format WebP plutôt que JPEG/PNG pour tout nouvel envoi dans la médiathèque
 * (fichier original conservé tel quel). Gain moyen constaté : -25 à -35 % de
 * poids par image, sans réglage à faire depuis l'administration.
 */
add_filter( 'image_editor_output_format', function( $formats ) {
    $formats['image/jpeg'] = 'image/webp';
    $formats['image/png']  = 'image/webp';
    return $formats;
} );

/* ──────────────────────────────────────────
   2. ENQUEUE STYLES & SCRIPTS
   ────────────────────────────────────────── */
/**
 * Version de cache-busting d'un asset local, basée sur sa date de modification
 * (fallback sur la version du thème si le fichier est introuvable).
 */
function rando_nono_asset_ver( $relative_path ) {
    $file = get_template_directory() . $relative_path;
    return file_exists( $file ) ? filemtime( $file ) : wp_get_theme()->get( 'Version' );
}

function rando_nono_assets() {
    $theme_uri = get_template_directory_uri();

    // ── Polices ──
    wp_enqueue_style( 'rando-nono-fonts', $theme_uri . '/assets/css/fonts.css', array(), rando_nono_asset_ver( '/assets/css/fonts.css' ) );

    // ── Style principal ──
    wp_enqueue_style( 'rando-nono-style', get_stylesheet_uri(), array( 'rando-nono-fonts' ), rando_nono_asset_ver( '/style.css' ) );

    // La carte interactive n'existe plus que sur la fiche complète d'une randonnée
    // et sur la carte d'ensemble de l'archive : inutile de charger Leaflet sur
    // l'accueil, les mentions légales, le 404, etc.
    $needs_leaflet = is_singular( 'randonnee' ) || is_post_type_archive( 'randonnee' );
    // Le profil altimétrique (Chart.js) n'est affiché que sur la fiche complète d'une randonnée.
    $needs_chart   = is_singular( 'randonnee' );
    // La grille de cartes (animations au scroll, filtre Matos, "voir plus") vit
    // sur l'accueil, l'archive et les résultats de recherche.
    $needs_randos_js = is_front_page() || is_post_type_archive( 'randonnee' ) || is_search();
    $main_deps     = array();

    // ── Leaflet (carte interactive) — hébergé localement dans assets/vendor/ ──
    // (plus de dépendance à unpkg/cdnjs : évite une requête réseau externe à chaque
    // visite et le risque d'un CDN tiers compromis servant du JS modifié)
    if ( $needs_leaflet ) {
        wp_enqueue_style( 'leaflet', $theme_uri . '/assets/vendor/leaflet/leaflet.css', array(), '1.9.4' );
        wp_enqueue_script( 'leaflet', $theme_uri . '/assets/vendor/leaflet/leaflet.js', array(), '1.9.4', true );
        wp_enqueue_script( 'leaflet-gpx', $theme_uri . '/assets/vendor/leaflet-gpx/gpx.min.js', array( 'leaflet' ), '1.7.0', true );
    }

    // ── Chart.js (profil altimétrique) — hébergé localement ──
    if ( $needs_chart ) {
        wp_enqueue_script( 'chartjs', $theme_uri . '/assets/vendor/chartjs/chart.umd.min.js', array(), '4.4.0', true );
    }

    if ( $needs_randos_js ) {
        wp_enqueue_script( 'rando-nono-randos', $theme_uri . '/assets/js/pages/randos.js', array(), rando_nono_asset_ver( '/assets/js/pages/randos.js' ), true );
        $main_deps[] = 'rando-nono-randos';
    }

    // Le panneau "Matos de Nono" n'existe que sur l'accueil.
    if ( is_front_page() ) {
        wp_enqueue_style( 'rando-nono-matos', $theme_uri . '/assets/css/components/matos.css', array( 'rando-nono-style' ), rando_nono_asset_ver( '/assets/css/components/matos.css' ) );
        wp_enqueue_script( 'rando-nono-matos', $theme_uri . '/assets/js/components/matos.js', array(), rando_nono_asset_ver( '/assets/js/components/matos.js' ), true );
    }

    // ── Favoris (localStorage) — boutons cœur présents sur les cartes, la fiche randonnée et la page /favoris/ ──
    wp_enqueue_script( 'rando-nono-favoris', $theme_uri . '/assets/js/components/favoris.js', array(), rando_nono_asset_ver( '/assets/js/components/favoris.js' ), true );

    wp_enqueue_script( 'rando-nono-main', $theme_uri . '/assets/js/main.js', $main_deps, rando_nono_asset_ver( '/assets/js/main.js' ), true );

    // ── Single randonnée (CSS + JS chargés uniquement sur la fiche) ──
    // Les articles (post) réutilisent le même CSS pour la navigation précédent/suivant,
    // mais n'ont pas besoin de la carte Leaflet.
    if ( is_singular( 'randonnee' ) || is_singular( 'post' ) ) {
        wp_enqueue_style( 'rando-nono-single', $theme_uri . '/assets/css/single-randonnee.css', array( 'rando-nono-style' ), rando_nono_asset_ver( '/assets/css/single-randonnee.css' ) );
    }
    if ( is_singular( 'randonnee' ) ) {
        wp_enqueue_script( 'rando-nono-single', $theme_uri . '/assets/js/pages/single-randonnee.js', array( 'leaflet', 'leaflet-gpx', 'chartjs' ), rando_nono_asset_ver( '/assets/js/pages/single-randonnee.js' ), true );

        // ── Suivi GPS en direct (démarrer / suivre la randonnée depuis le téléphone) ──
        wp_enqueue_style( 'rando-nono-live-tracking', $theme_uri . '/assets/css/components/live-tracking.css', array( 'rando-nono-single' ), rando_nono_asset_ver( '/assets/css/components/live-tracking.css' ) );
        wp_enqueue_script( 'rando-nono-live-tracking', $theme_uri . '/assets/js/components/live-tracking.js', array( 'rando-nono-single' ), rando_nono_asset_ver( '/assets/js/components/live-tracking.js' ), true );
    }

    // ── Carte d'ensemble (page "Toutes les randonnées") ──
    if ( is_post_type_archive( 'randonnee' ) ) {
        wp_enqueue_script( 'rando-nono-archive-map', $theme_uri . '/assets/js/pages/archive-map.js', array( 'leaflet' ), rando_nono_asset_ver( '/assets/js/pages/archive-map.js' ), true );
    }
}
add_action( 'wp_enqueue_scripts', 'rando_nono_assets' );
