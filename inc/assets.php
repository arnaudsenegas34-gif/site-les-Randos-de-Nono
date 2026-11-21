<?php
/**
 * Chargement CSS/JS, repli de taille d'image, allègement du front
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 87-284 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

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
    // sur l'accueil, l'archive, les résultats de recherche et les pages
    // "Article — Guide de randos" (elles réutilisent .rando-card, qui reste
    // invisible tant que ce script n'ajoute pas .is-visible au scroll).
    $needs_randos_js = is_front_page() || is_post_type_archive( 'randonnee' ) || is_search()
        || is_page_template( 'page-article-guide.php' );
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

/**
 * Repli quand une taille d'image du thème n'a pas été générée.
 *
 * `add_image_size()` ne vaut que pour les images téléversées APRÈS son ajout.
 * Pour les plus anciennes, la taille demandée n'existe pas et WordPress
 * retombe sur le FICHIER D'ORIGINE. Concrètement, sur la page d'accueil du
 * 01/09/2026 : des PNG de 1 172 px servis pour des vignettes affichées à
 * 250 px, et la grille « Matos » qui en réclamait 25 d'un coup — assez pour
 * qu'un hébergement mutualisé refuse une partie des requêtes. Symptôme :
 * des images cassées qui se chargent parfaitement une par une au clic droit.
 *
 * On retombe donc sur la plus petite taille intermédiaire réellement
 * générée qui reste assez grande, au lieu de l'original.
 *
 * Ce filet ne remplace PAS une régénération des miniatures (qui, elle,
 * produit la vraie taille recadrée) : il évite seulement qu'une image
 * ancienne coûte plusieurs fois son poids utile. En cas de doute, la
 * fonction rend la valeur de WordPress inchangée.
 */
function rando_nono_repli_taille_image( $image, $attachment_id, $size, $icon ) {
    // Site public UNIQUEMENT. L'administration doit voir les vraies tailles :
    // l'éditeur d'image, la médiathèque et le sélecteur de vignette
    // travaillent sur ces valeurs, il serait faux de leur en substituer
    // d'autres. Cela met aussi ce filtre hors de cause pour tout problème
    // survenant dans l'admin.
    if ( is_admin() ) {
        return $image;
    }

    // rando-hero est volontairement exclu : y retomber sur « large » (1024 px)
    // dégraderait visiblement la grande image d'en-tête.
    $largeurs_cibles = array(
        'rando-card'    => 640,
        'rando-gallery' => 1000,
    );

    if ( $icon || ! is_string( $size ) || ! isset( $largeurs_cibles[ $size ] ) ) {
        return $image;
    }

    // La taille demandée a bien été générée : on ne touche à rien.
    if ( image_get_intermediate_size( $attachment_id, $size ) ) {
        return $image;
    }

    $mini = $largeurs_cibles[ $size ] * 0.6;

    foreach ( array( 'medium', 'medium_large', 'large' ) as $repli ) {
        $inter = image_get_intermediate_size( $attachment_id, $repli );
        if ( ! empty( $inter['url'] ) && ! empty( $inter['width'] ) && $inter['width'] >= $mini ) {
            return array( $inter['url'], (int) $inter['width'], (int) $inter['height'], true );
        }
    }

    // Aucune taille intermédiaire assez grande : on garde le choix de WordPress.
    return $image;
}
add_filter( 'wp_get_attachment_image_src', 'rando_nono_repli_taille_image', 10, 4 );

/**
 * Allègement du site public — deux ressources chargées pour rien.
 *
 * Constaté sur la page d'accueil en ligne (PageSpeed, 01/09/2026) :
 * 2 080 ms de requêtes bloquant le rendu sur mobile, dont une bonne part
 * imputable à ces deux-là.
 */
function rando_nono_alleger_front() {
    if ( is_admin() ) {
        return;
    }
    // Dashicons est la police d'icônes de l'ADMINISTRATION. Un plugin la
    // charge aussi sur le site public, où elle bloque le rendu pour ~45 Ko
    // dont aucune page publique ne se sert. Les visiteurs connectés la
    // gardent : la barre d'admin en a besoin.
    if ( ! is_user_logged_in() ) {
        wp_dequeue_style( 'dashicons' );
    }
}
add_action( 'wp_enqueue_scripts', 'rando_nono_alleger_front', 100 );

/**
 * CSS des blocs Gutenberg — chargé à la demande plutôt qu'en bloc.
 *
 * wp-includes/css/dist/block-library/style.css pesait 122 Ko sur CHAQUE page,
 * soit 36 % du poids d'une page simple, en ressource bloquant le rendu — pour
 * un thème classique qui n'utilise presque aucun bloc. Ce filtre fait charger
 * à WordPress le style des seuls blocs réellement présents dans le contenu :
 * aucune mise en forme n'est perdue, y compris sur une page rédigée avec des
 * colonnes ou une galerie.
 */
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

/**
 * Émojis WordPress — script inutile ici, et bruyant.
 *
 * Le thème n'affiche pas d'émoji dans son contenu, mais le chargeur de
 * WordPress tente de créer un worker depuis une URL `blob:` que la CSP du
 * site refuse : chaque visiteur récolte une erreur dans sa console (relevée
 * par Lighthouse dans « Bonnes pratiques »). On retire le tout.
 */
function rando_nono_desactiver_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', 'rando_nono_desactiver_emojis' );

