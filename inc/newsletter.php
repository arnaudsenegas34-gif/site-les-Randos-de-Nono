<?php
/**
 * Newsletter — inscription (pied de page), désabonnement, notification
 * automatique des abonnés à chaque nouvelle randonnée, et administration.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function rando_nono_newsletter_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'rando_nono_newsletter';
}

function rando_nono_newsletter_create_table() {
    global $wpdb;
    $table           = rando_nono_newsletter_table_name();
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(190) NOT NULL,
        token VARCHAR(64) NOT NULL,
        date_inscription DATETIME NOT NULL,
        statut VARCHAR(20) NOT NULL DEFAULT 'actif',
        PRIMARY KEY (id),
        UNIQUE KEY email (email)
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
rando_nono_run_once_daily( 'rando_nono_newsletter_table_checked', 'rando_nono_newsletter_create_table' );

/**
 * Traitement du formulaire d'inscription (présent dans le pied de page, sur toutes les pages).
 */
function rando_nono_handle_newsletter_form() {
    if ( ! isset( $_POST['rando_nono_newsletter_submit'] ) ) return;

    // wp_get_referer() renvoie systématiquement false quand le formulaire est
    // posté sur l'URL même de la page (cas du formulaire du pied de page,
    // présent sur toutes les pages) : on utilise donc l'URL d'origine transmise
    // en champ caché, validée pour rester sur le site.
    $submitted_redirect = isset( $_POST['newsletter_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['newsletter_redirect'] ) ) : '';
    $redirect            = wp_validate_redirect( $submitted_redirect, home_url( '/' ) );

    if ( ! isset( $_POST['rando_nono_newsletter_nonce'] ) || ! wp_verify_nonce( $_POST['rando_nono_newsletter_nonce'], 'rando_nono_newsletter_form' ) ) {
        wp_safe_redirect( add_query_arg( 'newsletter', 'error', $redirect ) );
        exit;
    }

    // Piège à robots.
    if ( ! empty( $_POST['site_web_nl'] ) ) {
        wp_safe_redirect( add_query_arg( 'newsletter', 'ok', $redirect ) );
        exit;
    }

    // trim() est indispensable : un espace ajouté par le clavier mobile ou un
    // copier-coller fait échouer is_email() alors que l'adresse est valide.
    $email = isset( $_POST['newsletter_email'] ) ? sanitize_email( trim( wp_unslash( $_POST['newsletter_email'] ) ) ) : '';
    if ( ! is_email( $email ) ) {
        wp_safe_redirect( add_query_arg( 'newsletter', 'error', $redirect ) );
        exit;
    }

    global $wpdb;
    $table    = rando_nono_newsletter_table_name();
    $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE email = %s", $email ) );
    if ( ! $existing ) {
        $wpdb->insert( $table, array(
            'email'            => $email,
            'token'            => wp_generate_password( 32, false ),
            'date_inscription' => current_time( 'mysql' ),
            'statut'           => 'actif',
        ) );
        rando_nono_send_newsletter_welcome_email( $email );
    }

    wp_safe_redirect( add_query_arg( 'newsletter', 'ok', $redirect ) );
    exit;
}
add_action( 'template_redirect', 'rando_nono_handle_newsletter_form' );

/**
 * E-mail de bienvenue envoyé à chaque nouvel inscrit — contient le lien vers
 * la checklist PDF offerte en échange de l'inscription (lead magnet).
 */
function rando_nono_send_newsletter_welcome_email( $email ) {
    $checklist_url = get_template_directory_uri() . '/assets/downloads/checklist-sac-a-dos-randonnee.pdf';
    $subject = 'Bienvenue — ta checklist du sac à dos est prête';
    $message  = "Merci de t'être inscrit à la newsletter des Randos de Nono !\n\n";
    $message .= "Comme promis, voici ta checklist gratuite à consulter ou imprimer avant chaque départ :\n";
    $message .= $checklist_url . "\n\n";
    $message .= "Tu recevras désormais un e-mail à chaque nouvelle randonnée publiée, avec le récit complet et la trace GPX.\n\n";
    $message .= "Bonne rando !\n" . get_bloginfo( 'name' );

    $domain  = wp_parse_url( home_url(), PHP_URL_HOST );
    $headers = array( 'From: ' . get_bloginfo( 'name' ) . ' <newsletter@' . $domain . '>' );

    $sent = wp_mail( $email, $subject, $message, $headers );
    if ( ! $sent ) {
        error_log( 'Rando Nono newsletter : échec de l\'e-mail de bienvenue pour ' . $email );
    }
}

/**
 * Désabonnement en un clic, depuis le lien présent dans chaque e-mail envoyé.
 */
function rando_nono_handle_newsletter_unsubscribe() {
    if ( ! isset( $_GET['newsletter_desabonner'] ) ) return;
    $token = sanitize_text_field( wp_unslash( $_GET['newsletter_desabonner'] ) );

    global $wpdb;
    $table = rando_nono_newsletter_table_name();
    $wpdb->delete( $table, array( 'token' => $token ) );

    wp_safe_redirect( add_query_arg( 'newsletter', 'desabonne', home_url( '/' ) ) );
    exit;
}
add_action( 'template_redirect', 'rando_nono_handle_newsletter_unsubscribe' );

/**
 * Dès qu'une randonnée passe en "publié" pour la première fois, envoie un
 * e-mail à tous les abonnés. Le flag _rando_nono_newsletter_sent évite les
 * envois en double si l'article est ensuite modifié et republié.
 */
function rando_nono_schedule_newsletter_send( $post_id ) {
    update_post_meta( $post_id, '_rando_nono_newsletter_sent', current_time( 'mysql' ) );

    // Envoi immédiat plutôt que différé via WP-Cron : sur beaucoup
    // d'hébergeurs, WP-Cron dépend d'une requête "loopback" (le serveur
    // s'appelle lui-même) qui est bloquée par le pare-feu/l'hébergeur, et
    // l'e-mail programmé ne part alors jamais. On envoie donc tout de suite,
    // pendant la même requête que la publication/le renvoi manuel.
    rando_nono_send_newsletter_event( $post_id );
}

function rando_nono_newsletter_notify_new_rando( $new_status, $old_status, $post ) {
    if ( 'randonnee' !== $post->post_type ) return;
    if ( 'publish' !== $new_status || 'publish' === $old_status ) return;
    if ( get_post_meta( $post->ID, '_rando_nono_newsletter_sent', true ) ) return;

    rando_nono_schedule_newsletter_send( $post->ID );
}
add_action( 'transition_post_status', 'rando_nono_newsletter_notify_new_rando', 10, 3 );

function rando_nono_send_newsletter_event( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || 'publish' !== $post->post_status ) return;

    global $wpdb;
    $table = rando_nono_newsletter_table_name();
    $subs  = $wpdb->get_results( "SELECT email, token FROM $table WHERE statut = 'actif'" );
    if ( empty( $subs ) ) return;

    $lieu     = get_post_meta( $post_id, 'rando_lieu', true );
    $distance = get_post_meta( $post_id, 'rando_distance', true );
    $url      = get_permalink( $post_id );
    $titre    = get_the_title( $post_id );
    $subject  = 'Nouvelle randonnée : ' . $titre;

    // Expéditeur générique du site plutôt que l'adresse e-mail personnelle de
    // l'administrateur (celle utilisée pour Réglages > Général), pour ne pas
    // l'exposer à chaque abonné.
    $domain  = wp_parse_url( home_url(), PHP_URL_HOST );
    $headers = array( 'From: ' . get_bloginfo( 'name' ) . ' <newsletter@' . $domain . '>' );

    // wp_mail() peut échouer silencieusement (SMTP mal configuré, hébergeur qui
    // bloque l'envoi...) ; on journalise l'erreur pour pouvoir la diagnostiquer.
    $log_failure = function( $wp_error ) {
        error_log( 'Rando Nono newsletter : échec wp_mail — ' . $wp_error->get_error_message() );
    };
    add_action( 'wp_mail_failed', $log_failure );

    foreach ( $subs as $sub ) {
        $unsub  = add_query_arg( 'newsletter_desabonner', $sub->token, home_url( '/' ) );
        $body   = "Une nouvelle randonnée vient d'être publiée sur Les Randos de Nono !\n\n";
        $body  .= $titre . ( $lieu ? ' — ' . $lieu : '' ) . ( $distance ? ' (' . $distance . ')' : '' ) . "\n\n";
        $body  .= "Découvrir le récit et la trace GPX :\n" . $url . "\n\n";
        $body  .= "---\nSe désabonner en un clic :\n" . $unsub . "\n";
        wp_mail( $sub->email, $subject, $body, $headers );
    }

    remove_action( 'wp_mail_failed', $log_failure );
}
add_action( 'rando_nono_send_newsletter_event', 'rando_nono_send_newsletter_event' );

/**
 * Page d'administration — liste des abonnés + export CSV.
 */
function rando_nono_newsletter_menu() {
    add_menu_page( 'Newsletter', 'Newsletter', 'manage_options', 'rando-nono-newsletter', 'rando_nono_newsletter_page', 'dashicons-email-alt', 26 );
}
add_action( 'admin_menu', 'rando_nono_newsletter_menu' );

function rando_nono_newsletter_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    global $wpdb;
    $table       = rando_nono_newsletter_table_name();
    $subscribers = $wpdb->get_results( "SELECT email, date_inscription FROM $table WHERE statut = 'actif' ORDER BY date_inscription DESC" );
    $count       = count( $subscribers );
    ?>
    <div class="wrap">
        <h1>Newsletter</h1>
        <p><strong><?php echo intval( $count ); ?></strong> abonné<?php echo $count > 1 ? 's' : ''; ?> actif<?php echo $count > 1 ? 's' : ''; ?>.</p>
        <p>Un e-mail est envoyé automatiquement à tous les abonnés (une minute après publication, via WP-Cron) à chaque nouvelle randonnée mise en ligne.</p>
        <?php if ( $count ) : ?>
        <p>
            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=rando_nono_newsletter_export' ), 'rando_nono_newsletter_export' ) ); ?>" class="button">Exporter en CSV</a>
        </p>
        <table class="widefat striped" style="max-width:600px">
            <thead><tr><th>E-mail</th><th>Inscrit le</th></tr></thead>
            <tbody>
            <?php foreach ( $subscribers as $sub ) : ?>
                <tr><td><?php echo esc_html( $sub->email ); ?></td><td><?php echo esc_html( mysql2date( 'd/m/Y', $sub->date_inscription ) ); ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <p><em>Aucun abonné pour le moment.</em></p>
        <?php endif; ?>
    </div>
    <?php
}

function rando_nono_newsletter_export() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Accès refusé' );
    check_admin_referer( 'rando_nono_newsletter_export' );

    global $wpdb;
    $table       = rando_nono_newsletter_table_name();
    $subscribers = $wpdb->get_results( "SELECT email, date_inscription FROM $table WHERE statut = 'actif' ORDER BY date_inscription ASC" );

    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=newsletter-randos-de-nono.csv' );

    // Neutralise une éventuelle injection de formule si le fichier est ouvert
    // dans Excel/Google Sheets (une valeur commençant par =, +, -, @ ou une
    // tabulation peut sinon être interprétée comme une formule par le tableur).
    $csv_safe = function( $value ) {
        $value = (string) $value;
        if ( preg_match( '/^[=+\-@\t]/', $value ) ) {
            $value = "'" . $value;
        }
        return $value;
    };

    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, array( 'email', 'date_inscription' ) );
    foreach ( $subscribers as $sub ) {
        fputcsv( $out, array( $csv_safe( $sub->email ), $csv_safe( $sub->date_inscription ) ) );
    }
    fclose( $out );
    exit;
}
add_action( 'admin_post_rando_nono_newsletter_export', 'rando_nono_newsletter_export' );
