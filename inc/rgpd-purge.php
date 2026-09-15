<?php
/**
 * Purge automatique des données arrivées à échéance
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 3151-3183 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ──────────────────────────────────────────
   DURÉES DE CONSERVATION — annoncées ET tenues

   Les mentions légales disaient « ni conservées au-delà du nécessaire » : une
   formulation honnête, mais la CNIL attend une durée précise, et rien ne
   purgeait quoi que ce soit. Ces deux règles suffisent au périmètre du site :

   - un avis refusé n'a plus de raison d'être après six mois ;
   - une inscription jamais confirmée (double opt-in) n'est pas un
     consentement : elle ne doit pas rester en base.

   Les abonnés désinscrits ne sont pas concernés : leur ligne est supprimée
   immédiatement au clic de désabonnement, pas seulement marquée.
   ────────────────────────────────────────── */
function rando_nono_purger_donnees() {
    global $wpdb;

    $avis = rando_nono_avis_table_name();
    $wpdb->query( $wpdb->prepare(
        "DELETE FROM $avis WHERE statut = %s AND date_avis < %s",
        'refuse',
        gmdate( 'Y-m-d H:i:s', time() - 6 * MONTH_IN_SECONDS )
    ) );

    $nl = rando_nono_newsletter_table_name();
    $wpdb->query( $wpdb->prepare(
        "DELETE FROM $nl WHERE statut = %s AND date_inscription < %s",
        'en_attente',
        gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS )
    ) );
}
rando_nono_run_once_daily( 'rando_nono_purge_faite', 'rando_nono_purger_donnees' );

