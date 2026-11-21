<?php
/**
 * Durées, distances, randonnées similaires, statistiques d'accueil
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 2097-2294 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ──────────────────────────────────────────
   MAILLAGE INTERNE AUTOMATIQUE — randonnées similaires
   Sélectionne, sans aucune saisie manuelle, jusqu'à 4 randonnées proches
   géographiquement, de même difficulté et de durée comparable.
   ────────────────────────────────────────── */

/**
 * Convertit une durée texte libre ("4h30", "3 h", "2h") en minutes.
 */
function rando_nono_duree_to_minutes( $duree ) {
    if ( ! $duree ) return null;
    if ( preg_match( '/(\d+)\s*h(?:\D*(\d+))?/i', $duree, $m ) ) {
        $h   = (int) $m[1];
        $min = isset( $m[2] ) && '' !== $m[2] ? (int) $m[2] : 0;
        return $h * 60 + $min;
    }
    return null;
}

/**
 * Distance à vol d'oiseau entre deux points GPS (formule de Haversine), en km.
 */
function rando_nono_haversine_km( $lat1, $lon1, $lat2, $lon2 ) {
    $earth_radius = 6371;
    $d_lat = deg2rad( $lat2 - $lat1 );
    $d_lon = deg2rad( $lon2 - $lon1 );
    $a = sin( $d_lat / 2 ) * sin( $d_lat / 2 )
        + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $d_lon / 2 ) * sin( $d_lon / 2 );
    $c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
    return $earth_radius * $c;
}

/**
 * Retourne jusqu'à $limit randonnées similaires à $post_id : priorité à la
 * proximité géographique, puis à la même difficulté, puis à une durée
 * comparable. Alimente automatiquement le maillage interne de chaque fiche.
 * Le résultat (juste les ID, moins lourd à stocker qu'une liste de WP_Post)
 * est mis en cache : ce calcul relit tout le post type à chaque appel, coûteux
 * à répéter à chaque visite d'une fiche randonnée. Pas d'invalidation ciblée
 * (le classement d'UNE fiche peut changer quand N'IMPORTE QUELLE autre est
 * modifiée) : une expiration de quelques heures suffit, un léger retard sur
 * une suggestion "similaire" n'étant pas gênant pour l'utilisateur.
 *
 * @return WP_Post[]
 */
function rando_nono_get_related_randos( $post_id, $limit = 4 ) {
    // La version dans la clé change à chaque publication ou modification d'une
    // rando : sans elle, une fiche gardait 12 h sa liste calculée avant la
    // mise en ligne de ses voisines (la Sassière proposait le Gard au lieu de
    // l'Ouillette et de la passerelle, publiées le même jour).
    $cache_key = 'rando_nono_related_' . (int) get_option( 'rando_nono_related_v', 1 ) . '_' . $post_id . '_' . $limit;
    $cached_ids = get_transient( $cache_key );
    if ( false !== $cached_ids ) {
        return array_filter( array_map( 'get_post', $cached_ids ) );
    }

    $lat        = (float) get_post_meta( $post_id, 'rando_lat', true );
    $lon        = (float) get_post_meta( $post_id, 'rando_lon', true );
    $minutes    = rando_nono_duree_to_minutes( get_post_meta( $post_id, 'rando_duree', true ) );
    $diff_terms = get_the_terms( $post_id, 'difficulte' );
    $difficulte = $diff_terms && ! is_wp_error( $diff_terms ) ? $diff_terms[0]->term_id : 0;

    $candidates = get_posts( array(
        'post_type'      => 'randonnee',
        'posts_per_page' => -1,
        'post__not_in'   => array( $post_id ),
        'orderby'        => 'date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ) );

    $scored = array();
    foreach ( $candidates as $candidate ) {
        $cid       = $candidate->ID;
        $c_lat     = (float) get_post_meta( $cid, 'rando_lat', true );
        $c_lon     = (float) get_post_meta( $cid, 'rando_lon', true );
        $c_minutes = rando_nono_duree_to_minutes( get_post_meta( $cid, 'rando_duree', true ) );
        $c_terms   = get_the_terms( $cid, 'difficulte' );
        $c_diff    = $c_terms && ! is_wp_error( $c_terms ) ? $c_terms[0]->term_id : 0;

        // Score composite (plus bas = plus proche) : km à vol d'oiseau +
        // pénalité si difficulté différente + pénalité si durée très différente.
        $score = 0;
        $score += ( $lat && $lon && $c_lat && $c_lon ) ? rando_nono_haversine_km( $lat, $lon, $c_lat, $c_lon ) : 100;
        if ( ! $difficulte || $c_diff !== $difficulte ) $score += 50;
        $score += ( null !== $minutes && null !== $c_minutes ) ? abs( $minutes - $c_minutes ) / 10 : 15;

        $scored[] = array( 'id' => $cid, 'score' => $score );
    }

    usort( $scored, function( $a, $b ) { return $a['score'] <=> $b['score']; } );
    $scored = array_slice( $scored, 0, $limit );

    $ids = wp_list_pluck( $scored, 'id' );
    set_transient( $cache_key, $ids, 12 * HOUR_IN_SECONDS );

    return array_map( 'get_post', $ids );
}

/**
 * Tronque un texte libre ("12 km", "+380 m"...) à sa première valeur
 * numérique. Utilisé par les statistiques d'accueil ci-dessous.
 */
function rando_nono_extract_number( $text ) {
    if ( ! $text ) return 0;
    preg_match( '/-?[\d]+(?:[.,]\d+)?/', $text, $matches );
    if ( empty( $matches ) ) return 0;
    return (float) str_replace( ',', '.', $matches[0] );
}

/**
 * Statistiques agrégées de l'accueil (km, dénivelé, régions parcourues),
 * calculées à partir de toutes les randonnées publiées. Mises en cache — la
 * page d'accueil est la plus visitée du site, inutile de relire tout le post
 * type et ses meta à chaque affichage — et invalidées dès qu'une randonnée
 * est publiée, modifiée ou supprimée, pour que "X randonnées documentées"
 * reflète immédiatement une action dans l'admin plutôt qu'une fenêtre de
 * cache aveugle.
 */
function rando_nono_get_homepage_stats() {
    $cached = get_transient( 'rando_nono_homepage_stats_v2' );
    if ( false !== $cached ) return $cached;

    $regions         = array();
    $departements    = array();
    $total_km        = 0;
    $total_deniv_pos = 0;
    $total_deniv_neg = 0;

    $stats_query = new WP_Query( array( 'post_type' => 'randonnee', 'posts_per_page' => -1, 'no_found_rows' => true ) );
    if ( $stats_query->have_posts() ) {
        while ( $stats_query->have_posts() ) {
            $stats_query->the_post();
            $sid = get_the_ID();

            $lieu_rando = get_post_meta( $sid, 'rando_lieu', true );
            if ( $lieu_rando ) {
                $parts  = explode( ',', $lieu_rando );
                $region = trim( end( $parts ) );
                if ( $region ) $regions[ strtolower( $region ) ] = true;
            }

            // Département = texte entre parenthèses du lieu court (« Tignes (Savoie) »).
            $lieu_court = get_post_meta( $sid, 'rando_lieu_court', true );
            if ( $lieu_court && preg_match( '/\(([^)]+)\)\s*$/u', $lieu_court, $dm ) ) {
                $dep = trim( $dm[1] );
                $departements[ $dep ] = isset( $departements[ $dep ] ) ? $departements[ $dep ] + 1 : 1;
            }

            $total_km       += abs( rando_nono_extract_number( get_post_meta( $sid, 'rando_distance', true ) ) );
            $total_deniv_pos += abs( rando_nono_extract_number( get_post_meta( $sid, 'rando_denivele', true ) ) );
            $total_deniv_neg += abs( rando_nono_extract_number( get_post_meta( $sid, 'rando_denivele_neg', true ) ) );
        }
        wp_reset_postdata();
    }

    $stats = array(
        'total_km'      => $total_km,
        'deniv_pos'     => $total_deniv_pos,
        'deniv_neg'     => $total_deniv_neg,
        'regions_count' => count( $regions ),
        'departements'  => $departements,
    );
    set_transient( 'rando_nono_homepage_stats_v2', $stats, DAY_IN_SECONDS );
    return $stats;
}

/**
 * Bandeau du hero : les départements où il y a des randos, du plus fourni au
 * moins fourni (3 au plus), au lieu d'un texte figé qui oubliait la Savoie.
 */
function rando_nono_departements_libelle( $stats ) {
    $deps = isset( $stats['departements'] ) ? $stats['departements'] : array();
    if ( ! $deps ) return 'Hérault · Languedoc · et ailleurs';
    arsort( $deps );
    return implode( ' · ', array_slice( array_keys( $deps ), 0, 3 ) );
}

/**
 * Cartes « Randonner par région » de l'accueil. Chaque région renvoie vers sa
 * page de synthèse si elle existe, sinon vers la liste des randonnées. Une
 * région sans rando publiée n'est pas affichée.
 */
function rando_nono_regions_accueil( $stats ) {
    $deps = isset( $stats['departements'] ) ? $stats['departements'] : array();
    $defs = array(
        array( 'titre' => 'Hérault', 'deps' => array( 'Hérault' ), 'texte' => 'Garrigue, cirques et bivouacs', 'page' => 'les-plus-belles-randonnees-de-lherault', 'type' => 'page', 'mot' => 'randos' ),
        array( 'titre' => 'Val-d’Isère et Tignes', 'deps' => array( 'Savoie' ), 'texte' => 'Lacs d’altitude en famille', 'page' => 'randonnees-faciles-val-d-isere-tignes', 'type' => 'post', 'mot' => 'balades' ),
        array( 'titre' => 'Gard et Larzac', 'deps' => array( 'Gard', 'Aveyron' ), 'texte' => 'Camargue, Mer des Rochers, Canalettes', 'page' => 'etang-du-scamandrenie', 'type' => 'randonnee', 'mot' => 'randos', 'liste' => true ),
    );
    $out = array();
    foreach ( $defs as $d ) {
        $n = 0;
        foreach ( $d['deps'] as $dep ) $n += isset( $deps[ $dep ] ) ? $deps[ $dep ] : 0;
        if ( ! $n ) continue;
        $cible = get_page_by_path( $d['page'], OBJECT, $d['type'] );
        $url   = ( $cible && empty( $d['liste'] ) ) ? get_permalink( $cible ) : get_post_type_archive_link( 'randonnee' );
        $img   = $cible ? get_post_thumbnail_id( $cible ) : 0;
        $out[] = array( 'titre' => $d['titre'], 'texte' => $d['texte'], 'url' => $url, 'img' => $img, 'n' => $n, 'mot' => $n > 1 ? $d['mot'] : rtrim( $d['mot'], 's' ) );
    }
    return $out;
}

/**
 * Bornes maximales (distance, dénivelé positif) pour les curseurs de filtre
 * de l'archive — calculées sur les randonnées publiées et arrondies pour un
 * curseur confortable à manipuler (pas de borne du type "17.3 km"). Mise en
 * cache comme les autres statistiques agrégées de la page (voir
 * rando_nono_bust_homepage_stats_cache, qui invalide aussi ce transient).
 */
function rando_nono_get_archive_filter_bounds() {
    $cached = get_transient( 'rando_nono_archive_filter_bounds' );
    if ( false !== $cached ) return $cached;

    $max_distance = 0;
    $max_denivele = 0;

    $bounds_query = new WP_Query( array( 'post_type' => 'randonnee', 'posts_per_page' => -1, 'no_found_rows' => true ) );
    if ( $bounds_query->have_posts() ) {
        while ( $bounds_query->have_posts() ) {
            $bounds_query->the_post();
            $sid = get_the_ID();
            $max_distance = max( $max_distance, abs( rando_nono_extract_number( get_post_meta( $sid, 'rando_distance', true ) ) ) );
            $max_denivele = max( $max_denivele, abs( rando_nono_extract_number( get_post_meta( $sid, 'rando_denivele', true ) ) ) );
        }
        wp_reset_postdata();
    }

    $bounds = array(
        'distance_max' => max( 5, (int) ( ceil( $max_distance / 5 ) * 5 ) ),
        'denivele_max' => max( 100, (int) ( ceil( $max_denivele / 100 ) * 100 ) ),
    );
    set_transient( 'rando_nono_archive_filter_bounds', $bounds, DAY_IN_SECONDS );
    return $bounds;
}

function rando_nono_bust_homepage_stats_cache() {
    delete_transient( 'rando_nono_homepage_stats' ); delete_transient( 'rando_nono_homepage_stats_v2' );
    update_option( 'rando_nono_related_v', (int) get_option( 'rando_nono_related_v', 1 ) + 1, false );
    delete_transient( 'rando_nono_archive_filter_bounds' );
}
add_action( 'save_post_randonnee', 'rando_nono_bust_homepage_stats_cache' );
add_action( 'before_delete_post', function( $post_id ) {
    if ( 'randonnee' === get_post_type( $post_id ) ) {
        rando_nono_bust_homepage_stats_cache();
    }
} );

