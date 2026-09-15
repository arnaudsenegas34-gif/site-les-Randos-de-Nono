<?php
/**
 * Nettoyage, en-têtes de sécurité, verrou de connexion, permaliens
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 992-1211 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ──────────────────────────────────────────
   6. NETTOYAGE — sécurité & performance de base
   ────────────────────────────────────────── */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * En-têtes de sécurité HTTP de base, posés au niveau PHP (fonctionnent même si
 * mod_headers n'est pas disponible côté serveur — les règles équivalentes du
 * .htaccess servent de première ligne, celles-ci de filet de sécurité).
 */
function rando_nono_security_headers() {
    // JAMAIS dans l'administration. WordPress y dessine la médiathèque, la
    // liste des thèmes et l'éditeur avec underscore.js, qui compile ses
    // gabarits via new Function() — ce que `script-src` sans 'unsafe-eval'
    // interdit. Résultat : ces écrans restaient blancs, sans autre indice
    // qu'une EvalError dans la console. La CSP protège les pages publiques ;
    // l'admin est derrière authentification et n'a rien à y gagner.
    if ( is_admin() ) {
        return;
    }

    if ( headers_sent() ) return;

    // PHP annonce sa version dans X-Powered-By. Le .htaccess la retire via
    // `Header unset`, mais cela suppose mod_headers actif — or ce bloc existe
    // justement pour le cas contraire. header_remove() agit côté PHP, donc
    // quel que soit le serveur.
    header_remove( 'X-Powered-By' );

    header( 'X-Content-Type-Options: nosniff' );
    header( 'X-Frame-Options: SAMEORIGIN' );
    header( 'Referrer-Policy: strict-origin-when-cross-origin' );
    header( 'Permissions-Policy: geolocation=(self), camera=(), microphone=(), payment=(), browsing-topics=()' );
    header( 'Cross-Origin-Opener-Policy: same-origin' );
    // HSTS : présent dans le .htaccess, il manquait ici. Conditionné à une
    // connexion chiffrée — un HSTS servi sur HTTP est ignoré par les
    // navigateurs, et poserait problème sur un environnement de test local.
    if ( is_ssl() ) {
        header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
    }
    // Doit rester identique à la CSP posée par mod_headers dans .htaccess
    // (celle-ci ne sert que de filet de sécurité si mod_headers est absent :
    // avec "Header set", la valeur du .htaccess écrase celle-ci côté navigateur).
    // connect-src inclut api.sports-tracker.com : c'est le domaine réel de
    // l'export GPX de l'app Suunto utilisé dans le champ "URL du fichier GPX"
    // des randonnées (ex: .../apiserver/v2/routes/export/...?format=gpx-route),
    // appelé par Leaflet-GPX (carte + profil altimétrique) et par la fiche imprimable.
    header( "Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://connect.facebook.net; style-src 'self' 'unsafe-inline'; img-src 'self' data: https://*.tile.openstreetmap.org https://www.facebook.com; font-src 'self' data:; connect-src 'self' https://api.open-meteo.com https://api.sports-tracker.com https://*.google-analytics.com https://*.analytics.google.com https://www.facebook.com; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'; upgrade-insecure-requests" );
}
add_action( 'send_headers', 'rando_nono_security_headers' );

/**
 * Durcissement WordPress standard, sans dépendre de wp-config.php (souvent
 * hors dépôt) : désactive l'éditeur de fichiers thème/plugin dans l'admin
 * (réduit ce qu'un compte admin compromis peut modifier), masque quel
 * identifiant est en cause dans un échec de connexion (évite l'énumération
 * de comptes), et retire l'endpoint REST /wp/v2/users pour les visiteurs
 * non connectés (évite d'exposer le nom d'utilisateur de l'admin).
 */
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
    define( 'DISALLOW_FILE_EDIT', true );
}

add_filter( 'login_errors', function() {
    return 'Identifiants incorrects.';
} );

add_filter( 'rest_endpoints', function( $endpoints ) {
    if ( is_user_logged_in() ) return $endpoints;
    unset( $endpoints['/wp/v2/users'] );
    unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
    // Les commentaires WordPress ne sont ni affichés ni utilisés (le thème a
    // son propre système d'avis) : on ferme aussi leur porte d'entrée REST.
    unset( $endpoints['/wp/v2/comments'] );
    unset( $endpoints['/wp/v2/comments/(?P<id>[\d]+)'] );
    return $endpoints;
} );

/**
 * Énumération d'utilisateur — la porte laissée ouverte par les deux mesures
 * ci-dessus.
 *
 * Retirer /wp/v2/users et masquer le message d'erreur de connexion empêche de
 * découvrir l'identifiant de l'administrateur… sauf par deux autres chemins que
 * WordPress ouvre par défaut :
 *   - /?author=1 répond 301 vers /author/<login>/ : le login est dans l'URL ;
 *   - wp-sitemap-users-1.xml publie cette même URL, et l'annonce à Google.
 * Connaître l'identifiant transforme une attaque par force brute en une
 * recherche de mot de passe seul.
 *
 * Le site n'a qu'un auteur et n'affiche jamais de page d'auteur : les deux
 * peuvent disparaître sans rien perdre.
 */
function rando_nono_bloquer_enumeration_auteur() {
    if ( is_admin() || is_user_logged_in() ) return;
    if ( is_author() || isset( $_GET['author'] ) ) {
        wp_safe_redirect( home_url( '/' ), 301 );
        exit;
    }
}
add_action( 'template_redirect', 'rando_nono_bloquer_enumeration_auteur', 0 );

add_filter( 'wp_sitemaps_add_provider', function( $provider, $name ) {
    return 'users' === $name ? false : $provider;
}, 10, 2 );

/**
 * Commentaires WordPress — fermés partout.
 *
 * Le thème ne fournit pas de comments.php et n'appelle jamais
 * comments_template() : aucun commentaire n'a jamais été affiché. Mais
 * default_comment_status valait 'open', donc /wp-comments-post.php et l'API
 * REST les acceptaient quand même : un dépôt de spam invisible, qui gonfle
 * wp_comments sans que personne ne le voie. Les avis de randonnée
 * (rando_nono_avis_*) remplissent déjà ce rôle, avec modération.
 */
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_filter( 'feed_links_show_comments_feed', '__return_false' );

/**
 * Tentatives de connexion — WordPress n'en limite aucune.
 *
 * Cinq essais consécutifs sur wp-login.php passent sans blocage ni délai. Ce
 * verrou minimal compte les échecs par IP et refuse l'accès au formulaire
 * au-delà du seuil, le temps de la fenêtre. Il ne remplace pas une extension
 * dédiée (pas de liste noire durable, pas d'alerte e-mail) mais ferme la porte
 * ouverte par le couple « identifiant connu + essais illimités ».
 *
 * Les transients servent de compteur : ils expirent seuls, sans table ni cron.
 */
function rando_nono_login_cle_tentatives() {
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
    return $ip ? 'rando_nono_login_' . md5( $ip ) : '';
}

function rando_nono_login_verrou() {
    $cle = rando_nono_login_cle_tentatives();
    if ( ! $cle ) return;
    $tentatives = (int) get_transient( $cle );
    if ( $tentatives < 5 ) return;
    wp_die(
        '<h1>Trop de tentatives</h1><p>Trop d\'essais de connexion depuis cette adresse. Réessaie dans quinze minutes.</p>',
        'Connexion bloquée',
        array( 'response' => 429 )
    );
}
add_action( 'login_init', 'rando_nono_login_verrou' );

add_action( 'wp_login_failed', function() {
    $cle = rando_nono_login_cle_tentatives();
    if ( ! $cle ) return;
    $tentatives = (int) get_transient( $cle );
    set_transient( $cle, $tentatives + 1, 15 * MINUTE_IN_SECONDS );
} );

add_action( 'wp_login', function() {
    $cle = rando_nono_login_cle_tentatives();
    if ( $cle ) delete_transient( $cle );
} );

/**
 * Version de WordPress — retirée des URL de ressources.
 *
 * remove_action('wp_head','wp_generator') efface la balise <meta generator>,
 * mais les scripts et styles du cœur gardent ?ver=6.8.2 : la version reste
 * lisible dans le code source. Ce n'est pas une protection en soi — seules les
 * mises à jour le sont — mais cela évite de figurer dans les listes de cibles
 * constituées par scan automatique.
 */
add_filter( 'script_loader_src', 'rando_nono_retirer_version_core', 15 );
add_filter( 'style_loader_src', 'rando_nono_retirer_version_core', 15 );
function rando_nono_retirer_version_core( $src ) {
    if ( ! $src || false === strpos( $src, 'ver=' ) ) return $src;
    // Uniquement pour les ressources du cœur : celles du thème utilisent la
    // date de modification du fichier comme version (rando_nono_asset_ver),
    // indispensable pour invalider le cache après une mise à jour.
    if ( false === strpos( $src, '/wp-includes/' ) && false === strpos( $src, '/wp-admin/' ) ) {
        return $src;
    }
    return remove_query_arg( 'ver', $src );
}

/**
 * Anti-flood minimal sur les formulaires publics (contact, newsletter, avis) :
 * refuse une nouvelle soumission du même formulaire depuis la même IP avant
 * $seconds, en complément du nonce et du champ piège à robots déjà en place
 * sur chacun. Ralentit un script de spam basique sans CAPTCHA ni dépendance
 * externe ; best-effort (REMOTE_ADDR peut être partagé derrière un proxy),
 * mais sans coût pour un visiteur normal qui ne soumet pas deux fois de suite.
 */
function rando_nono_throttle_submission( $form_key, $seconds = 20 ) {
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
    if ( ! $ip ) return true;
    $key = 'rando_nono_throttle_' . $form_key . '_' . md5( $ip );
    if ( get_transient( $key ) ) return false;
    set_transient( $key, 1, $seconds );
    return true;
}

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

