<?php
/**
 * Traitement du formulaire de contact et journal des échecs d'envoi
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 913-991 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function rando_nono_handle_contact_form() {
    if ( ! is_page( 'contact' ) || ! isset( $_POST['rando_nono_contact_submit'] ) ) return;

    $redirect = get_permalink();

    // Quatre situations distinctes menaient toutes à `contact=error`, avec le
    // même message « vérifiez les champs » — y compris quand les champs étaient
    // corrects et que c'était l'envoi qui avait échoué. Chacune a désormais son
    // code, et page-contact.php affiche le message qui correspond.
    //
    // Le cas du nonce périmé n'est pas théorique : W3 Total Cache sert
    // /contact/ depuis son cache disque avec le nonce figé dans le HTML, et un
    // nonce WordPress expire au bout de 12 à 24 h. Passé ce délai, tout
    // visiteur anonyme échouait, et le message l'envoyait corriger des champs
    // qui n'avaient rien de faux.
    if ( ! isset( $_POST['rando_nono_contact_nonce'] ) || ! wp_verify_nonce( $_POST['rando_nono_contact_nonce'], 'rando_nono_contact_form' ) ) {
        wp_safe_redirect( add_query_arg( 'contact', 'expire', $redirect ) );
        exit;
    }

    if ( ! rando_nono_throttle_submission( 'contact' ) ) {
        wp_safe_redirect( add_query_arg( 'contact', 'attente', $redirect ) );
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
        wp_safe_redirect( add_query_arg( 'contact', 'champs', $redirect ) );
        exit;
    }

    // L'adresse de destination suit le réglage WordPress (Réglages > Général),
    // au lieu d'être écrite en dur dans le thème : la changer ne demande plus
    // de livrer une nouvelle version.
    $to      = get_option( 'admin_email' );
    $subject = 'Nouveau message depuis Les Randos de Nono';
    $body    = "Nom : {$nom}\nEmail : {$email}\n\nMessage :\n{$message}";
    $headers = array( 'Reply-To: ' . $nom . ' <' . $email . '>' );

    $sent = wp_mail( $to, $subject, $body, $headers );

    wp_safe_redirect( add_query_arg( 'contact', $sent ? 'ok' : 'envoi', $redirect ) );
    exit;
}
add_action( 'template_redirect', 'rando_nono_handle_contact_form' );

/**
 * Échecs d'envoi de courriel — journalisés plutôt que silencieux.
 *
 * Rien ne remontait une panne d'envoi : ni au visiteur (qui lisait « vérifiez
 * les champs »), ni à l'administrateur. Sur un mutualisé où l'envoi PHP est
 * souvent restreint, c'est la panne la plus probable — et la plus coûteuse,
 * puisqu'elle fait perdre des messages sans laisser de trace.
 *
 * La dernière erreur est conservée en option (consultable dans Réglages >
 * Vérification SEO, ou via get_option) et écrite dans le journal PHP quand
 * WP_DEBUG_LOG est actif.
 */
add_action( 'wp_mail_failed', function( $erreur ) {
    if ( ! is_wp_error( $erreur ) ) return;
    $message = $erreur->get_error_message();
    update_option( 'rando_nono_dernier_echec_mail', array(
        'date'    => current_time( 'mysql' ),
        'message' => $message,
    ), false );
    if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
        error_log( '[rando-nono] Échec wp_mail : ' . $message );
    }
} );

