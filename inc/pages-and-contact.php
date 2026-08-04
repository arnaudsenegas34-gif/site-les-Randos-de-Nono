<?php
/**
 * Création automatique des pages du thème (mentions légales, contact,
 * favoris) et traitement du formulaire de contact.
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

function rando_nono_handle_contact_form() {
    if ( ! is_page( 'contact' ) || ! isset( $_POST['rando_nono_contact_submit'] ) ) return;

    $redirect = get_permalink();

    if ( ! isset( $_POST['rando_nono_contact_nonce'] ) || ! wp_verify_nonce( $_POST['rando_nono_contact_nonce'], 'rando_nono_contact_form' ) ) {
        wp_safe_redirect( add_query_arg( 'contact', 'error', $redirect ) );
        exit;
    }

    // Piège à robots : ce champ caché doit rester vide.
    if ( ! empty( $_POST['site_web'] ) ) {
        wp_safe_redirect( add_query_arg( 'contact', 'ok', $redirect ) );
        exit;
    }

    $nom     = isset( $_POST['contact_nom'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_nom'] ) ) : '';
    $email   = isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '';
    $message = isset( $_POST['contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ) ) : '';

    if ( '' === $nom || ! is_email( $email ) || '' === $message ) {
        wp_safe_redirect( add_query_arg( 'contact', 'error', $redirect ) );
        exit;
    }

    $to      = 'arnaud.senegas34@gmail.com';
    $subject = 'Nouveau message depuis Les Randos de Nono';
    $body    = "Nom : {$nom}\nEmail : {$email}\n\nMessage :\n{$message}";
    $headers = array( 'Reply-To: ' . $nom . ' <' . $email . '>' );

    $sent = wp_mail( $to, $subject, $body, $headers );

    wp_safe_redirect( add_query_arg( 'contact', $sent ? 'ok' : 'error', $redirect ) );
    exit;
}
add_action( 'template_redirect', 'rando_nono_handle_contact_form' );
