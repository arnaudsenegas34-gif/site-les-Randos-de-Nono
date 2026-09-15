<?php
/**
 * Title, meta description, Open Graph, URL canonique, robots
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 1212-1492 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ──────────────────────────────────────────
   7. SEO DE BASE — title, meta description, Open Graph, Twitter Cards
   (pas de plugin nécessaire pour ce niveau de besoin)
   ────────────────────────────────────────── */

// Séparateur "|" pour tous les <title> (donne "Titre | Les Randos de Nono").
add_filter( 'document_title_separator', function() { return '|'; } );

/**
 * Tronque un texte à $max caractères sur une frontière de mot, avec ellipse.
 */
function rando_nono_trim_title( $text, $max ) {
    $text = trim( $text );
    if ( mb_strlen( $text ) <= $max ) return $text;
    $trimmed    = mb_substr( $text, 0, $max );
    $last_space = mb_strrpos( $trimmed, ' ' );
    if ( false !== $last_space && $last_space > $max * 0.5 ) {
        $trimmed = mb_substr( $trimmed, 0, $last_space );
    }
    return rtrim( $trimmed, " \t\n\r\0\x0B–—," ) . '…';
}

/**
 * Recadre une meta description sur la fourchette [140, 160] caractères
 * (coupe proprement sur un mot, sans dépasser $max).
 */
function rando_nono_meta_description_trim( $text, $max = 160 ) {
    $text = trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
    if ( mb_strlen( $text ) > $max ) {
        $trimmed    = mb_substr( $text, 0, $max - 1 );
        $last_space = mb_strrpos( $trimmed, ' ' );
        if ( false !== $last_space && $last_space > $max * 0.6 ) {
            $trimmed = mb_substr( $trimmed, 0, $last_space );
        }
        $text = rtrim( $trimmed, " \t\n\r\0\x0B,." ) . '…';
    }
    return $text;
}

/**
 * Title tag propre par contexte — budget ~55-60 caractères en tenant compte
 * du " | Les Randos de Nono" ajouté automatiquement par WordPress
 * (add_theme_support('title-tag') + document_title_parts).
 */
function rando_nono_document_title_parts( $title ) {
    $overhead = mb_strlen( ' | ' . get_bloginfo( 'name' ) );
    $budget   = max( 25, 60 - $overhead );

    if ( is_singular( 'randonnee' ) ) {
        global $post;
        $lieu       = get_post_meta( $post->ID, 'rando_lieu', true );
        $base_title = get_the_title();
        $with_lieu  = $lieu ? $base_title . ' — ' . $lieu : $base_title;
        $title['title'] = ( mb_strlen( $with_lieu ) <= $budget )
            ? $with_lieu
            : rando_nono_trim_title( $base_title, $budget );
    } elseif ( is_front_page() ) {
        $title['title']   = 'Les Randos de Nono';
        $title['tagline'] = 'carnet de randonnée & traces GPX';
    } elseif ( is_post_type_archive( 'randonnee' ) ) {
        $title['title'] = rando_nono_trim_title( 'Toutes les randonnées avec trace GPX', $budget );
    } elseif ( is_singular( 'matos' ) ) {
        $title['title'] = rando_nono_trim_title( get_the_title() . ' — matériel testé', $budget );
    } elseif ( is_page() ) {
        $title['title'] = rando_nono_trim_title( get_the_title(), $budget );
    } elseif ( is_singular( 'post' ) ) {
        // Les articles passaient sans troncature : « Bien choisir ses
        // chaussures de randonnée | Les Randos de Nono » faisait 61 caractères,
        // au-delà de ce que Google affiche.
        $title['title'] = rando_nono_trim_title( get_the_title(), $budget );
    }
    return $title;
}
add_filter( 'document_title_parts', 'rando_nono_document_title_parts' );

/**
 * URL canonique — construite depuis l'objet affiché, jamais depuis la requête.
 *
 * `home_url( add_query_arg( null, null ) )` recopiait la chaîne de requête
 * telle quelle : le paramètre anti-bot `?i=1` d'InfinityFree se retrouvait
 * donc dans <link rel="canonical"> et og:url, et Google se voyait désigner
 * « /?i=1 » comme l'adresse de référence de la page d'accueil.
 */
function rando_nono_canonical_url() {
    // Une page de résultats ou une 404 n'a pas d'adresse canonique : elle est
    // déjà en noindex, et pointer vers l'accueil envoyait deux signaux
    // contradictoires — « ignore cette page » et « attribue-lui la valeur de
    // l'accueil ». Mieux vaut n'en émettre aucune.
    if ( is_search() || is_404() ) {
        return '';
    }

    if ( is_front_page() ) {
        $url = home_url( '/' );
    } elseif ( is_singular() ) {
        $url = get_permalink();
    } elseif ( is_post_type_archive() ) {
        $post_type = get_query_var( 'post_type' );
        $url       = get_post_type_archive_link( is_array( $post_type ) ? reset( $post_type ) : $post_type );
    } elseif ( is_category() || is_tag() || is_tax() ) {
        $term = get_queried_object();
        $url  = ( $term && ! is_wp_error( $term ) ) ? get_term_link( $term ) : '';
    } elseif ( is_home() ) {
        $url = get_permalink( (int) get_option( 'page_for_posts' ) );
    } else {
        // Recherche, 404, archives de dates : le chemin nu, sans la requête.
        $path = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/', PHP_URL_PATH );
        $url  = home_url( is_string( $path ) ? $path : '/' );
    }

    if ( ! $url || is_wp_error( $url ) ) {
        $url = home_url( '/' );
    }

    // La pagination fait partie de l'adresse canonique : /page/2/ n'est pas
    // un doublon de la page 1.
    $paged = max( (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
    if ( $paged > 1 && ! is_singular() ) {
        $url = trailingslashit( $url ) . 'page/' . $paged . '/';
    }

    return $url;
}

function rando_nono_seo_meta_tags() {
    $description = '';
    $title       = get_bloginfo( 'name' );
    $image       = get_template_directory_uri() . '/assets/img/og-image.jpg';
    $url         = rando_nono_canonical_url();
    $keywords    = '';

    if ( is_singular( 'randonnee' ) ) {
        global $post;
        $lieu       = get_post_meta( $post->ID, 'rando_lieu', true );
        $distance   = get_post_meta( $post->ID, 'rando_distance', true );
        $duree      = get_post_meta( $post->ID, 'rando_duree', true );
        $diff_terms = get_the_terms( $post->ID, 'difficulte' );
        $difficulte = $diff_terms && ! is_wp_error( $diff_terms ) ? strtolower( $diff_terms[0]->name ) : '';

        // Description générée automatiquement : nom + lieu + difficulté + stats + appel à l'action.
        $phrase = 'Randonnée ' . get_the_title();
        if ( $lieu )       $phrase .= ' à ' . $lieu;
        if ( $difficulte ) $phrase .= ', niveau ' . $difficulte;
        $stats = array_filter( array( $distance, $duree ) );
        if ( $stats )      $phrase .= ' (' . implode( ', ', $stats ) . ')';
        $phrase .= '. Découvrez le récit complet, les photos et la trace GPX à télécharger.';

        $description = rando_nono_meta_description_trim( $phrase );
        $title = get_the_title() . ( $lieu ? ' — ' . $lieu : '' ) . ' | ' . get_bloginfo( 'name' );
        $thumb = get_the_post_thumbnail_url( $post->ID, 'large' );
        if ( $thumb ) $image = $thumb;

        $keywords = implode( ', ', array_filter( array( 'randonnée', $lieu, $difficulte ? 'randonnée ' . $difficulte : '', 'trace GPX', 'Hérault' ) ) );

    } elseif ( is_singular( 'matos' ) ) {
        $content_desc = wp_strip_all_tags( get_the_content() );
        $description  = rando_nono_meta_description_trim( $content_desc ?: get_the_title() . ' — le matériel de randonnée que Nono utilise vraiment sur le terrain, sortie après sortie.' );
        $title = get_the_title() . ' | Matos de Nono';

    } elseif ( is_post_type_archive( 'randonnee' ) ) {
        $description = rando_nono_meta_description_trim( 'Toutes les randonnées documentées par Nono dans l\'Hérault et ailleurs : distance, dénivelé, difficulté, trace GPX et météo en temps réel pour chaque sortie.' );
        $title = 'Toutes les randonnées | ' . get_bloginfo( 'name' );

    } elseif ( is_front_page() ) {
        $description = rando_nono_meta_description_trim( 'Carnet de randonnée dans l\'Hérault et ailleurs : récits, traces GPX à télécharger, météo en temps réel, équipement et statistiques de mes sorties.' );
        $title = get_bloginfo( 'name' ) . ' — Carnet de randonnée, traces GPX & Hérault';

    } elseif ( is_page() ) {
        // Les pages de service n'ont presque pas de contenu : le repli
        // « Titre — Les Randos de Nono. » produisait des descriptions de 29 à
        // 40 caractères, sans un mot sur ce qu'on y trouve. Une phrase écrite
        // pour chacune vaut mieux qu'un gabarit.
        $descriptions_pages = array(
            'contact'          => 'Une question sur une randonnée, une trace GPX à signaler, une suggestion d\'itinéraire ? Écris à Nono, la réponse arrive vite.',
            'mentions-legales' => 'Mentions légales des Randos de Nono : éditeur, hébergement, données personnelles, cookies, newsletter, avis et géolocalisation.',
            'favoris'          => 'Retrouve les randonnées que tu as mises de côté, enregistrées sur cet appareil et prêtes pour ta prochaine sortie.',
            'guides'           => 'Guides et sélections de randonnées : équipement, préparation du sac, lecture de carte et itinéraires regroupés par thème.',
        );
        $slug_page    = get_post_field( 'post_name', get_the_ID() );
        $content_desc = wp_strip_all_tags( get_the_content() );

        if ( isset( $descriptions_pages[ $slug_page ] ) ) {
            $description = rando_nono_meta_description_trim( $descriptions_pages[ $slug_page ] );
        } else {
            $description = rando_nono_meta_description_trim( $content_desc ?: get_the_title() . ' — ' . get_bloginfo( 'name' ) . '.' );
        }
        $title = get_the_title() . ' | ' . get_bloginfo( 'name' );

    } elseif ( is_singular( 'post' ) ) {
        $raw = has_excerpt() ? get_the_excerpt() : wp_strip_all_tags( get_the_content() );
        $description = rando_nono_meta_description_trim( $raw );
        $title = get_the_title() . ' | ' . get_bloginfo( 'name' );
        $thumb = get_the_post_thumbnail_url( get_the_ID(), 'large' );
        if ( $thumb ) $image = $thumb;

    } elseif ( is_home() || is_category() || is_tag() ) {
        $description = rando_nono_meta_description_trim( 'Actus, récits de randonnée et conseils pratiques par Nono : équipement, itinéraires et traces GPX dans l\'Hérault et ailleurs.' );
        $title = ( is_home() ? 'Actus & récits' : single_cat_title( '', false ) . ' — Actus' ) . ' | ' . get_bloginfo( 'name' );
    }

    if ( ! $description ) {
        $description = rando_nono_meta_description_trim( get_bloginfo( 'description' ) ?: get_bloginfo( 'name' ) );
    }

    echo "\n" . '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<meta name="author" content="Arnaud — ' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
    if ( $keywords ) {
        echo '<meta name="keywords" content="' . esc_attr( $keywords ) . '">' . "\n";
    }

    echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<meta property="og:type" content="' . ( is_singular( 'randonnee' ) || is_singular( 'post' ) ? 'article' : 'website' ) . '">' . "\n";
    // og:url sert à identifier la page pour les partages : sur la recherche et
    // le 404 (sans canonique), on retombe sur l'adresse courante nue.
    $og_url = $url ? $url : home_url( wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/', PHP_URL_PATH ) );
    echo '<meta property="og:url" content="' . esc_url( $og_url ) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
    echo '<meta property="og:locale" content="fr_FR">' . "\n";

    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";

    // $url est vide sur la recherche et le 404 : pas de balise du tout.
    if ( $url ) {
        echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
    }
}
add_action( 'wp_head', 'rando_nono_seo_meta_tags', 1 );

/**
 * Directives robots — une seule balise, via le filtre prévu par WordPress.
 *
 * Le thème écrivait sa propre <meta name="robots"> en plus de celle que
 * WordPress émet déjà : la page en sortait deux, avec des contenus
 * différents. Passer par `wp_robots` laisse WordPress en produire une seule,
 * cohérente, à laquelle on ajoute simplement nos règles.
 *
 * Recherche interne et page Favoris (liste personnelle en localStorage,
 * identique pour tout le monde côté serveur) : aucune valeur pour un moteur,
 * on évite le contenu pauvre dans l'index.
 */
/**
 * Les URL de filtres de l'archive ne doivent pas être indexées.
 *
 * Le formulaire passe ses critères en GET : 5 difficultés × 16 valeurs de
 * distance × 81 de dénivelé font 6 480 URL distinctes servant le même contenu,
 * sans compter la recherche libre. La balise canonique les ramène toutes vers
 * /randonnee/ — le contenu n'est donc pas dupliqué dans l'index — mais Google
 * les visite quand même avant de les consolider, sur un hébergement mutualisé
 * qui refuse déjà des connexions simultanées.
 */
function rando_nono_archive_filtree() {
    if ( ! is_post_type_archive( 'randonnee' ) ) return false;
    foreach ( array( 'recherche', 'difficulte', 'distance_max', 'denivele_max' ) as $param ) {
        if ( isset( $_GET[ $param ] ) && '' !== $_GET[ $param ] ) return true;
    }
    return false;
}

function rando_nono_robots( $robots ) {
    if ( is_search() || is_page( 'favoris' ) || rando_nono_archive_filtree() ) {
        $robots['noindex'] = true;
        $robots['follow']  = true;
        unset( $robots['index'] );
        return $robots;
    }

    if ( get_option( 'blog_public' ) ) {
        $robots['index']                 = true;
        $robots['follow']                = true;
        $robots['max-image-preview']     = 'large';
    }

    return $robots;
}
add_filter( 'wp_robots', 'rando_nono_robots' );

