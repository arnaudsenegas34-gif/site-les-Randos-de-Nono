<?php
/**
 * Nettoyage sécurité/perf de base, en-têtes HTTP (dont la Content-Security-Policy)
 * et permaliens lisibles.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ──────────────────────────────────────────
   6. NETTOYAGE — sécurité & performance de base
   ────────────────────────────────────────── */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'xmlrpc_enabled', '__return_false' );

// Désactive le script/style de détection d'émojis de WordPress core : il
// s'agit d'un <script> inline (donc bloqué par notre Content-Security-Policy
// stricte ci-dessous, qui n'autorise que les scripts du thème + le nonce
// dédié) et d'une requête externe pour la plupart des visiteurs qui n'en ont
// pas besoin (navigateurs modernes gérant nativement les émojis Unicode).
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );

/**
 * Nonce CSP unique pour la requête en cours : partagé entre l'en-tête
 * Content-Security-Policy (script-src 'nonce-...') et le seul script inline
 * du thème qui en a besoin (préférence de thème clair/sombre dans header.php).
 * Mémoïsé en variable statique pour rester identique partout dans la même requête.
 */
function rando_nono_csp_nonce() {
    static $nonce = null;
    if ( null === $nonce ) {
        $nonce = base64_encode( random_bytes( 16 ) );
    }
    return $nonce;
}

/**
 * En-têtes de sécurité HTTP de base, posés au niveau PHP (fonctionnent même si
 * mod_headers n'est pas disponible côté serveur — les règles équivalentes du
 * .htaccess servent de première ligne, celles-ci de filet de sécurité).
 *
 * La Content-Security-Policy ne s'applique qu'au front (jamais à /wp-admin/ :
 * l'admin WordPress dépend de nombreux scripts/styles inline générés par le
 * cœur et les plugins, qu'une CSP stricte casserait). Elle limite l'exécution
 * de script à ceux du thème (même origine) + au script "nonce" du thème, plus
 * Google Analytics/Facebook Pixel (chargés dynamiquement par cookie-consent.js
 * seulement après consentement) — ce qui neutralise l'essentiel de l'impact
 * d'une éventuelle faille XSS.
 */
function rando_nono_security_headers() {
    if ( headers_sent() ) return;
    header( 'X-Content-Type-Options: nosniff' );
    header( 'X-Frame-Options: SAMEORIGIN' );
    header( 'Referrer-Policy: strict-origin-when-cross-origin' );
    header( 'Permissions-Policy: geolocation=(self), camera=(), microphone=(), payment=()' );

    if ( is_admin() ) return;

    $nonce = rando_nono_csp_nonce();
    $csp   = array(
        "default-src 'self'",
        "script-src 'self' 'nonce-{$nonce}' https://www.googletagmanager.com https://connect.facebook.net",
        // 'unsafe-inline' pour les styles : le thème utilise de nombreux attributs
        // style="" ponctuels (position, dimensions calculées côté serveur) sur des
        // pages générées dynamiquement — un passage en nonce nécessiterait de
        // ré-écrire chacun d'eux. Le risque d'un CSS-only XSS reste très faible
        // comparé à script-src, qui lui reste strict.
        "style-src 'self' 'unsafe-inline'",
        "img-src 'self' data: https://tile.openstreetmap.org https://*.google-analytics.com https://www.facebook.com",
        "font-src 'self'",
        "connect-src 'self' https://api.open-meteo.com https://*.google-analytics.com https://*.analytics.google.com",
        "frame-ancestors 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "object-src 'none'",
        'upgrade-insecure-requests',
    );
    header( 'Content-Security-Policy: ' . implode( '; ', $csp ) );
}
add_action( 'send_headers', 'rando_nono_security_headers' );

// Retire la balise canonique par défaut de WordPress : le thème en génère déjà
// une (voir rando_nono_seo_meta_tags) — deux balises canoniques dupliquaient
// systématiquement le <head> de chaque page.
remove_action( 'wp_head', 'rel_canonical' );

/* ──────────────────────────────────────────
   6bis. PERMALIENS LISIBLES
   Force une structure d'URL lisible (/randonnee/nom-de-la-sortie/) au lieu
   des URLs par défaut de type ?p=123, y compris pour un site fraîchement
   installé qui n'aurait pas encore de permaliens personnalisés.
   ────────────────────────────────────────── */
function rando_nono_force_pretty_permalinks() {
    if ( '' === get_option( 'permalink_structure' ) ) {
        update_option( 'permalink_structure', '/%postname%/' );
        flush_rewrite_rules();
    }
}
add_action( 'after_switch_theme', 'rando_nono_force_pretty_permalinks' );
