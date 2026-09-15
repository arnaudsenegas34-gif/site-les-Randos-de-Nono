<?php
/**
 * Avis et notes des lecteurs, modération, export
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 2893-3150 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ──────────────────────────────────────────
   10. AVIS & NOTES DES LECTEURS — modération avant publication
   ────────────────────────────────────────── */
function rando_nono_avis_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'rando_nono_avis';
}

function rando_nono_avis_create_table() {
    global $wpdb;
    $table           = rando_nono_avis_table_name();
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        rando_id BIGINT UNSIGNED NOT NULL,
        nom VARCHAR(100) NOT NULL,
        note TINYINT UNSIGNED NOT NULL,
        commentaire TEXT NOT NULL,
        date_avis DATETIME NOT NULL,
        statut VARCHAR(20) NOT NULL DEFAULT 'en_attente',
        PRIMARY KEY (id),
        KEY rando_id (rando_id),
        KEY statut (statut)
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
rando_nono_run_once_daily( 'rando_nono_avis_table_checked', 'rando_nono_avis_create_table' );

/**
 * Moyenne et nombre d'avis publiés pour une randonnée (utilisé dans l'affichage et le schema.org).
 */
function rando_nono_get_avis_stats( $rando_id ) {
    global $wpdb;
    $table = rando_nono_avis_table_name();
    $row   = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) as total, AVG(note) as moyenne FROM $table WHERE rando_id = %d AND statut = 'publie'", $rando_id ) );
    return array(
        'total'   => $row ? (int) $row->total : 0,
        'moyenne' => ( $row && $row->total > 0 ) ? round( (float) $row->moyenne, 1 ) : 0,
    );
}

/**
 * Moyenne et nombre total d'avis publiés, toutes randonnées confondues —
 * utilisé pour la preuve sociale affichée en page d'accueil. Mis en cache
 * (l'accueil est la page la plus visitée du site : évite de recalculer cette
 * agrégation SQL à chaque chargement) et invalidé dès qu'un avis est
 * approuvé ou supprimé (voir rando_nono_avis_approve/delete plus bas).
 */
function rando_nono_get_avis_stats_global() {
    $cached = get_transient( 'rando_nono_avis_stats_global' );
    if ( false !== $cached ) return $cached;

    global $wpdb;
    $table = rando_nono_avis_table_name();
    $row   = $wpdb->get_row( "SELECT COUNT(*) as total, AVG(note) as moyenne FROM $table WHERE statut = 'publie'" );
    $stats = array(
        'total'   => $row ? (int) $row->total : 0,
        'moyenne' => ( $row && $row->total > 0 ) ? round( (float) $row->moyenne, 1 ) : 0,
    );
    set_transient( 'rando_nono_avis_stats_global', $stats, DAY_IN_SECONDS );
    return $stats;
}

function rando_nono_get_avis_list( $rando_id ) {
    global $wpdb;
    $table = rando_nono_avis_table_name();
    return $wpdb->get_results( $wpdb->prepare( "SELECT nom, note, commentaire, date_avis FROM $table WHERE rando_id = %d AND statut = 'publie' ORDER BY date_avis DESC", $rando_id ) );
}

/**
 * Traitement du formulaire d'avis (sur la fiche de chaque randonnée). Les avis sont
 * enregistrés "en attente" et n'apparaissent qu'après validation manuelle (anti-spam).
 */
function rando_nono_handle_avis_form() {
    if ( ! isset( $_POST['rando_nono_avis_submit'] ) || ! is_singular( 'randonnee' ) ) return;

    $post_id  = get_queried_object_id();
    $redirect = get_permalink( $post_id ) . '#avis';

    if ( ! isset( $_POST['rando_nono_avis_nonce'] ) || ! wp_verify_nonce( $_POST['rando_nono_avis_nonce'], 'rando_nono_avis_form_' . $post_id ) ) {
        wp_safe_redirect( add_query_arg( 'avis', 'error', $redirect ) );
        exit;
    }

    if ( ! rando_nono_throttle_submission( 'avis' ) ) {
        wp_safe_redirect( add_query_arg( 'avis', 'error', $redirect ) );
        exit;
    }

    // Piège à robots.
    if ( ! empty( $_POST['site_web_avis'] ) ) {
        wp_safe_redirect( add_query_arg( 'avis', 'merci', $redirect ) );
        exit;
    }

    $nom         = isset( $_POST['avis_nom'] ) ? sanitize_text_field( wp_unslash( $_POST['avis_nom'] ) ) : '';
    $note        = isset( $_POST['avis_note'] ) ? intval( $_POST['avis_note'] ) : 0;
    $commentaire = isset( $_POST['avis_commentaire'] ) ? sanitize_textarea_field( wp_unslash( $_POST['avis_commentaire'] ) ) : '';

    if ( '' === $nom || $note < 1 || $note > 5 || '' === $commentaire ) {
        wp_safe_redirect( add_query_arg( 'avis', 'error', $redirect ) );
        exit;
    }

    global $wpdb;
    $wpdb->insert( rando_nono_avis_table_name(), array(
        'rando_id'    => $post_id,
        'nom'         => $nom,
        'note'        => $note,
        'commentaire' => $commentaire,
        'date_avis'   => current_time( 'mysql' ),
        'statut'      => 'en_attente',
    ) );

    wp_safe_redirect( add_query_arg( 'avis', 'merci', $redirect ) );
    exit;
}
add_action( 'template_redirect', 'rando_nono_handle_avis_form' );

/**
 * Page d'administration — modération des avis (approuver / supprimer).
 */
function rando_nono_avis_pending_count() {
    global $wpdb;
    $table = rando_nono_avis_table_name();
    return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE statut = 'en_attente'" );
}

function rando_nono_avis_menu() {
    $pending    = rando_nono_avis_pending_count();
    $menu_label = 'Avis';
    if ( $pending > 0 ) {
        $menu_label .= ' <span class="awaiting-mod"><span class="pending-count">' . intval( $pending ) . '</span></span>';
    }
    add_menu_page( 'Avis lecteurs', $menu_label, 'moderate_comments', 'rando-nono-avis', 'rando_nono_avis_page', 'dashicons-star-half', 27 );
}
add_action( 'admin_menu', 'rando_nono_avis_menu' );

function rando_nono_avis_page() {
    if ( ! current_user_can( 'moderate_comments' ) ) return;

    global $wpdb;
    $table = rando_nono_avis_table_name();
    $avis  = $wpdb->get_results( "SELECT a.*, p.post_title FROM $table a LEFT JOIN {$wpdb->posts} p ON p.ID = a.rando_id ORDER BY (a.statut = 'en_attente') DESC, a.date_avis DESC" );
    ?>
    <div class="wrap">
        <h1>Avis lecteurs</h1>
        <p>Chaque avis déposé sur une fiche randonnée apparaît ici en attente de validation avant d'être visible publiquement.</p>
        <p>
          <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=rando_nono_avis_export' ), 'rando_nono_avis_export' ) ); ?>" class="button">Exporter en CSV</a>
          <span class="description" style="margin-left:.6rem">
            Les avis vivent dans une table propre au thème : un export WordPress (Outils&nbsp;→&nbsp;Exporter) ne les contient pas.
            Cet export, ou une sauvegarde de la base entière, est le seul moyen de les conserver.
          </span>
        </p>
        <?php if ( empty( $avis ) ) : ?>
            <p><em>Aucun avis pour le moment.</em></p>
        <?php else : ?>
        <table class="widefat striped">
            <thead><tr><th>Randonnée</th><th>Nom</th><th>Note</th><th>Commentaire</th><th>Date</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ( $avis as $a ) : ?>
                <tr>
                    <td><?php echo esc_html( $a->post_title ? $a->post_title : '(rando supprimée)' ); ?></td>
                    <td><?php echo esc_html( $a->nom ); ?></td>
                    <td><?php echo esc_html( str_repeat( '★', (int) $a->note ) . str_repeat( '☆', 5 - (int) $a->note ) ); ?></td>
                    <td><?php echo esc_html( wp_trim_words( $a->commentaire, 20 ) ); ?></td>
                    <td><?php echo esc_html( mysql2date( 'd/m/Y', $a->date_avis ) ); ?></td>
                    <td><?php echo ( 'publie' === $a->statut ) ? '<span style="color:#2E5E3B">Publié</span>' : '<span style="color:#D97706">En attente</span>'; ?></td>
                    <td>
                        <?php if ( 'publie' !== $a->statut ) : ?>
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=rando_nono_avis_approve&id=' . $a->id ), 'rando_nono_avis_action_' . $a->id ) ); ?>" class="button button-small">Approuver</a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=rando_nono_avis_delete&id=' . $a->id ), 'rando_nono_avis_action_' . $a->id ) ); ?>" class="button button-small" onclick="return confirm('Supprimer cet avis ?');">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
}

function rando_nono_avis_approve() {
    if ( ! current_user_can( 'moderate_comments' ) ) wp_die( 'Accès refusé' );
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    check_admin_referer( 'rando_nono_avis_action_' . $id );
    global $wpdb;
    $wpdb->update( rando_nono_avis_table_name(), array( 'statut' => 'publie' ), array( 'id' => $id ) );
    delete_transient( 'rando_nono_avis_stats_global' );
    wp_safe_redirect( admin_url( 'admin.php?page=rando-nono-avis' ) );
    exit;
}
add_action( 'admin_post_rando_nono_avis_approve', 'rando_nono_avis_approve' );

function rando_nono_avis_delete() {
    if ( ! current_user_can( 'moderate_comments' ) ) wp_die( 'Accès refusé' );
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    check_admin_referer( 'rando_nono_avis_action_' . $id );
    global $wpdb;
    $wpdb->delete( rando_nono_avis_table_name(), array( 'id' => $id ) );
    delete_transient( 'rando_nono_avis_stats_global' );
    wp_safe_redirect( admin_url( 'admin.php?page=rando-nono-avis' ) );
    exit;
}
add_action( 'admin_post_rando_nono_avis_delete', 'rando_nono_avis_delete' );

/**
 * Export CSV des avis — l'équivalent de celui des abonnés.
 *
 * Les avis n'étaient accessibles qu'en base : répondre à une demande d'accès
 * ou d'effacement demandait une intervention SQL, et ils échappaient à toute
 * sauvegarde qui ne porte pas sur la base entière (l'export WordPress ne
 * couvre pas les tables créées par un thème).
 */
function rando_nono_avis_export() {
    if ( ! current_user_can( 'moderate_comments' ) ) wp_die( 'Accès refusé' );
    check_admin_referer( 'rando_nono_avis_export' );

    global $wpdb;
    $table = rando_nono_avis_table_name();
    $avis  = $wpdb->get_results( "SELECT id, rando_id, nom, note, commentaire, date_avis, statut FROM $table ORDER BY date_avis ASC" );

    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=avis-randos-de-nono.csv' );

    // Même protection que l'export des abonnés : une valeur commençant par
    // =, +, - ou @ serait interprétée comme une formule par un tableur.
    $csv_safe = function( $value ) {
        $value = (string) $value;
        if ( preg_match( '/^[=+\-@\t]/', $value ) ) {
            $value = "'" . $value;
        }
        return $value;
    };

    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, array( 'id', 'randonnee', 'url', 'prenom', 'note', 'commentaire', 'date', 'statut' ) );
    foreach ( $avis as $a ) {
        fputcsv( $out, array(
            (int) $a->id,
            $csv_safe( get_the_title( $a->rando_id ) ),
            $csv_safe( get_permalink( $a->rando_id ) ),
            $csv_safe( $a->nom ),
            (int) $a->note,
            $csv_safe( $a->commentaire ),
            $csv_safe( $a->date_avis ),
            $csv_safe( $a->statut ),
        ) );
    }
    fclose( $out );
    exit;
}
add_action( 'admin_post_rando_nono_avis_export', 'rando_nono_avis_export' );

