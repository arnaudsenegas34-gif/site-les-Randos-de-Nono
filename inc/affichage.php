<?php
/**
 * Favicon, fil d'Ariane, texte alternatif automatique
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 2295-2362 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Favicon — généré à partir de l'image hero, recadré en carré.
 */
function rando_nono_favicon() {
    $base = get_template_directory_uri() . '/assets/img/';
    echo '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url( $base . 'favicon-32.png' ) . '">' . "\n";
    echo '<link rel="icon" type="image/png" sizes="192x192" href="' . esc_url( $base . 'favicon-192.png' ) . '">' . "\n";
    echo '<link rel="apple-touch-icon" sizes="512x512" href="' . esc_url( $base . 'favicon-512.png' ) . '">' . "\n";
}
add_action( 'wp_head', 'rando_nono_favicon', 2 );

/**
 * Fil d'Ariane (breadcrumb) — Accueil > Randonnées > Titre de la rando
 * Utilisation : <?php rando_nono_breadcrumb(); ?> dans n'importe quel template.
 */
function rando_nono_breadcrumb() {
    echo '<nav class="breadcrumb" aria-label="Fil d\'Ariane">';
    echo '<a href="' . esc_url( home_url( '/' ) ) . '">Accueil</a>';

    if ( is_post_type_archive( 'randonnee' ) ) {
        echo '<span class="breadcrumb-sep">›</span>';
        echo '<span class="breadcrumb-current">Toutes les randonnées</span>';

    } elseif ( is_singular( 'randonnee' ) ) {
        echo '<span class="breadcrumb-sep">›</span>';
        echo '<a href="' . esc_url( get_post_type_archive_link( 'randonnee' ) ) . '">Randonnées</a>';
        $region = rando_nono_lieu_region( get_post_meta( get_the_ID(), 'rando_lieu', true ) );
        if ( $region ) {
            echo '<span class="breadcrumb-sep">›</span>';
            echo '<a href="' . esc_url( add_query_arg( 'recherche', $region, get_post_type_archive_link( 'randonnee' ) ) ) . '">' . esc_html( $region ) . '</a>';
        }
        echo '<span class="breadcrumb-sep">›</span>';
        echo '<span class="breadcrumb-current">' . esc_html( get_the_title() ) . '</span>';

    } elseif ( is_singular( 'matos' ) ) {
        echo '<span class="breadcrumb-sep">›</span>';
        echo '<a href="' . esc_url( home_url( '/#matos' ) ) . '">Matos de Nono</a>';
        echo '<span class="breadcrumb-sep">›</span>';
        echo '<span class="breadcrumb-current">' . esc_html( get_the_title() ) . '</span>';

    } elseif ( is_page() ) {
        echo '<span class="breadcrumb-sep">›</span>';
        echo '<span class="breadcrumb-current">' . esc_html( get_the_title() ) . '</span>';

    } elseif ( is_search() ) {
        echo '<span class="breadcrumb-sep">›</span>';
        echo '<span class="breadcrumb-current">Recherche &laquo; ' . esc_html( get_search_query() ) . ' &raquo;</span>';

    } elseif ( is_404() ) {
        echo '<span class="breadcrumb-sep">›</span>';
        echo '<span class="breadcrumb-current">Page introuvable</span>';
    }

    echo '</nav>';
}

/**
 * Texte alternatif automatique pour les images à la une des randonnées
 * (si l'utilisateur n'a pas renseigné de texte alternatif manuellement).
 */
function rando_nono_auto_alt_text( $attr, $attachment, $size ) {
    if ( empty( $attr['alt'] ) && get_post_type() === 'randonnee' ) {
        $attr['alt'] = get_the_title() . ' — randonnée';
    }
    return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'rando_nono_auto_alt_text', 10, 3 );

