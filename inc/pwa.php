<?php
/**
 * PWA hors-ligne — service worker servi à la racine du site.
 * Permet de consulter hors-ligne (sur le terrain) les randonnées déjà
 * visitées : récit, carte, trace GPX. /sw.js et /hors-ligne/ sont de fausses
 * routes WordPress (aucun fichier physique à cet endroit) pour que le
 * service worker ait le scope '/', quel que soit le dossier du thème.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function rando_nono_pwa_rewrite_rules() {
    add_rewrite_rule( '^sw\.js$', 'index.php?rando_nono_sw=1', 'top' );
    add_rewrite_rule( '^hors-ligne/?$', 'index.php?rando_nono_offline=1', 'top' );
}
add_action( 'init', 'rando_nono_pwa_rewrite_rules' );

add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'rando_nono_sw';
    $vars[] = 'rando_nono_offline';
    return $vars;
} );

// Les nouvelles règles de réécriture doivent être prises en compte une fois sur
// les sites où le thème était déjà actif avant leur ajout (after_switch_theme
// ne se déclenche que lors d'une activation). Priorité 20 : après l'ajout des
// règles ci-dessus (priorité par défaut 10), pour qu'elles soient incluses au flush.
function rando_nono_pwa_maybe_flush_rewrites() {
    if ( get_transient( 'rando_nono_pwa_rewrite_flushed' ) ) return;
    flush_rewrite_rules();
    set_transient( 'rando_nono_pwa_rewrite_flushed', 1, DAY_IN_SECONDS );
}
add_action( 'init', 'rando_nono_pwa_maybe_flush_rewrites', 20 );

function rando_nono_serve_sw() {
    if ( ! get_query_var( 'rando_nono_sw' ) ) return;

    $theme_uri = get_template_directory_uri();
    $version   = wp_get_theme()->get( 'Version' );
    $offline_url = home_url( '/hors-ligne/' );

    $app_shell = array(
        home_url( '/' ),
        $offline_url,
        get_post_type_archive_link( 'randonnee' ),
        $theme_uri . '/style.css',
        $theme_uri . '/assets/css/fonts.css',
        $theme_uri . '/assets/js/main.js',
        $theme_uri . '/assets/js/components/favoris.js',
    );

    $sw_js = file_get_contents( get_template_directory() . '/assets/js/sw.js' );
    $sw_js = str_replace(
        array( '__CACHE_VERSION__', '__OFFLINE_URL__', '__APP_SHELL_JSON__' ),
        array(
            esc_js( $version ),
            wp_json_encode( $offline_url ),
            wp_json_encode( array_values( $app_shell ) ),
        ),
        $sw_js
    );

    nocache_headers();
    header( 'Content-Type: application/javascript; charset=utf-8' );
    header( 'Service-Worker-Allowed: /' );
    echo $sw_js;
    exit;
}
add_action( 'template_redirect', 'rando_nono_serve_sw' );

function rando_nono_serve_offline_page() {
    if ( ! get_query_var( 'rando_nono_offline' ) ) return;
    nocache_headers();
    header( 'Content-Type: text/html; charset=utf-8' );
    ?>
<!DOCTYPE html>
<html lang="fr-FR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hors ligne — Les Randos de Nono</title>
<style>
  body { font-family: Georgia, serif; background:#FAF8F3; color:#1A2E1F; text-align:center; padding:4rem 1.5rem; }
  h1 { color:#2E5E3B; font-size:1.6rem; margin-bottom:1rem; }
  p { color:#5E5E52; max-width:32em; margin:0 auto 1.5rem; }
  a { display:inline-block; padding:0.7rem 1.3rem; background:#D97706; color:#fff; border-radius:5px; text-decoration:none; font-weight:600; }
</style>
</head>
<body>
  <h1>Pas de connexion</h1>
  <p>Cette page n'est pas disponible hors ligne. Reconnecte-toi pour la consulter, ou retourne sur une randonnée déjà visitée pendant que tu avais du réseau.</p>
  <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Retour à l'accueil</a>
</body>
</html>
    <?php
    exit;
}
add_action( 'template_redirect', 'rando_nono_serve_offline_page' );
