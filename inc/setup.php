<?php
/**
 * Déclaration du thème, tailles d'images, formats de conversion
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 43-86 de la v6.2).
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
    // Seconde taille au MÊME rapport que rando-card : sans elle, WordPress ne
    // peut construire aucun srcset pour les vignettes (il ne mélange pas les
    // proportions), et le même fichier de 640 px partait vers tous les écrans.
    // Rappel : add_image_size() ne vaut que pour l'avenir — il faut régénérer
    // les miniatures pour que les images déjà en ligne en profitent.
    add_image_size( 'rando-card-sm', 320, 210, true );
    add_image_size( 'rando-hero', 1600, 900, true );
    add_image_size( 'rando-gallery', 1000, 0, false );
}
add_action( 'after_setup_theme', 'rando_nono_setup' );

/**
 * Génère automatiquement les tailles d'images (thumbnail, medium, rando-card…)
 * au format WebP plutôt que JPEG/PNG pour tout nouvel envoi dans la médiathèque
 * (fichier original conservé tel quel). Gain moyen constaté : -25 à -35 % de
 * poids par image, sans réglage à faire depuis l'administration.
 *
 * Ne force WebP que si la bibliothèque d'images du serveur (GD/Imagick) sait
 * réellement l'encoder — sinon la génération des tailles échoue en silence
 * et certaines photos (souvent les plus récentes) n'apparaissent plus nulle
 * part sur le site. Fréquent sur les hébergements mutualisés bas de gamme
 * (GD compilé sans le support WebP).
 */
add_filter( 'image_editor_output_format', function( $formats ) {
    if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
        $formats['image/jpeg'] = 'image/webp';
        $formats['image/png']  = 'image/webp';
    }
    return $formats;
} );

