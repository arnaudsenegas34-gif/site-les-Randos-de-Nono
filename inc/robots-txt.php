<?php
/**
 * robots.txt virtuel
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 1991-2019 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * robots.txt virtuel — autorise l'indexation et référence le sitemap natif
 * de WordPress (/wp-sitemap.xml, toujours à jour, inutile d'en écrire un statique).
 * Ne s'applique que si le site est public (réglages > Lecture > "Décourager les
 * moteurs de recherche" désactivé) : WordPress gère lui-même le cas contraire.
 */
add_filter( 'robots_txt', function( $output, $public ) {
    if ( ! $public ) return $output;
    $output  = "User-agent: *\n";
    $output .= "Allow: /\n";
    $output .= "Disallow: /wp-admin/\n";
    $output .= "Allow: /wp-admin/admin-ajax.php\n";
    // Les combinaisons de filtres de l'archive : des milliers d'URL pour un
    // seul contenu. Le noindex (voir rando_nono_robots) les sort de l'index ;
    // ces lignes évitent en plus de les faire explorer.
    $output .= "Disallow: /*?recherche=\n";
    $output .= "Disallow: /*&recherche=\n";
    $output .= "Disallow: /*?difficulte=\n";
    $output .= "Disallow: /*&difficulte=\n";
    $output .= "Disallow: /*?distance_max=\n";
    $output .= "Disallow: /*&distance_max=\n";
    $output .= "Disallow: /*?denivele_max=\n";
    $output .= "Disallow: /*&denivele_max=\n";
    $output .= "Disallow: /*?s=\n";
    $output .= "\n";
    $output .= 'Sitemap: ' . home_url( '/wp-sitemap.xml' ) . "\n";
    return $output;
}, 10, 2 );

