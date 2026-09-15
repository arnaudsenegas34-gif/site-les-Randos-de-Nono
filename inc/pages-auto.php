<?php
/**
 * Création automatique des pages du thème
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 870-912 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ──────────────────────────────────────────
   5bis. CRÉATION AUTOMATIQUE DES PAGES DU THÈME
   (mentions légales, contact, favoris) — une seule fabrique factorisée au
   lieu de trois blocs quasi identiques : la page est créée si elle manque au
   changement de thème, puis re-vérifiée une fois par jour via un transient
   (utile après une restauration de sauvegarde ayant perdu la page).
   ────────────────────────────────────────── */
function rando_nono_ensure_page_exists( $slug, $title ) {
    if ( get_page_by_path( $slug ) ) return;
    wp_insert_post( array(
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '',
    ) );
}

/**
 * Exécute $callback au plus une fois par jour (via transient), en plus d'un
 * déclenchement systématique à l'activation du thème. Sert pour toute
 * vérification idempotente coûteuse (création de page, de table SQL, flush
 * des règles de réécriture...) qu'on ne veut pas relancer à chaque requête.
 */
function rando_nono_run_once_daily( $transient_key, callable $callback ) {
    add_action( 'after_switch_theme', $callback );
    add_action( 'init', function() use ( $transient_key, $callback ) {
        if ( get_transient( $transient_key ) ) return;
        $callback();
        set_transient( $transient_key, 1, DAY_IN_SECONDS );
    } );
}

function rando_nono_register_page_autocreate( $slug, $title ) {
    rando_nono_run_once_daily( 'rando_nono_page_checked_' . $slug, function() use ( $slug, $title ) {
        rando_nono_ensure_page_exists( $slug, $title );
    } );
}

rando_nono_register_page_autocreate( 'mentions-legales', 'Mentions légales' );
rando_nono_register_page_autocreate( 'contact', 'Contact' );
rando_nono_register_page_autocreate( 'favoris', 'Mes randos à faire' );

