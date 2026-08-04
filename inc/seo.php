<?php
/**
 * SEO : title, meta description, Open Graph/Twitter Cards, JSON-LD Schema.org,
 * sitemap, robots.txt, resource hints, maillage interne automatique,
 * favicon, fil d'Ariane et vérification Search Console / Bing.
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
    }
    return $title;
}
add_filter( 'document_title_parts', 'rando_nono_document_title_parts' );

function rando_nono_seo_meta_tags() {
    $description = '';
    $title       = get_bloginfo( 'name' );
    $image       = get_template_directory_uri() . '/assets/img/og-image.jpg';
    $url         = home_url( add_query_arg( null, null ) );
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
        $content_desc = wp_strip_all_tags( get_the_content() );
        $description  = rando_nono_meta_description_trim( $content_desc ?: get_the_title() . ' — ' . get_bloginfo( 'name' ) . '.' );
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

    $is_public = (bool) get_option( 'blog_public' );

    // Pages de recherche et "favoris" (contenu personnalisé côté client via
    // localStorage, vide pour Google) : ne rien indexer pour éviter le
    // contenu pauvre/dupliqué en resultats de recherche, tout en continuant
    // à suivre leurs liens internes.
    $should_noindex = is_search() || is_page( 'favoris' );

    echo "\n" . '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
    if ( $should_noindex ) {
        echo '<meta name="robots" content="noindex, follow">' . "\n";
    } elseif ( $is_public ) {
        echo '<meta name="robots" content="index, follow, max-image-preview:large">' . "\n";
    }
    echo '<meta name="author" content="Arnaud — ' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
    if ( $keywords ) {
        echo '<meta name="keywords" content="' . esc_attr( $keywords ) . '">' . "\n";
    }

    echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<meta property="og:type" content="' . ( is_singular( 'randonnee' ) || is_singular( 'post' ) ? 'article' : 'website' ) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
    echo '<meta property="og:locale" content="fr_FR">' . "\n";

    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";

    echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
}
add_action( 'wp_head', 'rando_nono_seo_meta_tags', 1 );

/* ──────────────────────────────────────────
   7bis. SCHEMA.ORG JSON-LD — données structurées pour Google
   ────────────────────────────────────────── */

/**
 * Extrait le dernier segment d'un lieu libre ("Mourèze, Hérault" → "Hérault"),
 * utilisé comme niveau intermédiaire du fil d'Ariane et du BreadcrumbList.
 */
function rando_nono_lieu_region( $lieu ) {
    if ( ! $lieu ) return '';
    $parts = array_map( 'trim', explode( ',', $lieu ) );
    return end( $parts );
}

function rando_nono_schema_jsonld() {

    // ── Page d'accueil : WebSite + Organization ──
    if ( is_front_page() ) {
        $site_url = home_url( '/' );

        $website = array(
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            'name'            => get_bloginfo( 'name' ),
            'url'             => $site_url,
            'inLanguage'      => 'fr-FR',
            'potentialAction' => array(
                '@type'       => 'SearchAction',
                'target'      => array(
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => home_url( '/?s={search_term_string}' ),
                ),
                'query-input' => 'required name=search_term_string',
            ),
        );

        $organization = array(
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => get_bloginfo( 'name' ),
            'url'      => $site_url,
            'logo'     => get_template_directory_uri() . '/assets/img/favicon-512.png',
            'sameAs'   => array( 'https://www.instagram.com/a._.sng?igsh=MWpyYWVyazh6NWJ6dw==' ),
        );

        echo '<script type="application/ld+json">' . wp_json_encode( $website,      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
        echo '<script type="application/ld+json">' . wp_json_encode( $organization, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
        return;
    }

    if ( ! is_singular( 'randonnee' ) ) return;
    global $post;

    $id         = $post->ID;
    $titre      = get_the_title( $id );
    $url        = get_permalink( $id );
    $lieu       = get_post_meta( $id, 'rando_lieu', true );
    $region     = rando_nono_lieu_region( $lieu );
    $lat        = get_post_meta( $id, 'rando_lat', true );
    $lon        = get_post_meta( $id, 'rando_lon', true );
    $distance   = get_post_meta( $id, 'rando_distance', true );
    $denivele   = get_post_meta( $id, 'rando_denivele', true );
    $duree      = get_post_meta( $id, 'rando_duree', true );
    $image      = get_the_post_thumbnail_url( $id, 'large' );
    $contenu    = wp_strip_all_tags( get_the_content() );
    $diff_terms = get_the_terms( $id, 'difficulte' );
    $difficulte = $diff_terms && ! is_wp_error( $diff_terms ) ? $diff_terms[0]->name : '';

    $desc = $contenu
        ? mb_substr( $contenu, 0, 200 ) . ( mb_strlen( $contenu ) > 200 ? '…' : '' )
        : 'Randonnée' . ( $lieu ? ' à ' . $lieu : '' ) . ( $distance ? ' — ' . $distance : '' );

    // BreadcrumbList — Accueil > Randonnées > [Région] > Titre
    $crumbs = array(
        array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil',    'item' => home_url( '/' ) ),
        array( '@type' => 'ListItem', 'position' => 2, 'name' => 'Randonnées', 'item' => get_post_type_archive_link( 'randonnee' ) ),
    );
    if ( $region ) {
        $crumbs[] = array(
            '@type'    => 'ListItem',
            'position' => 3,
            'name'     => $region,
            'item'     => add_query_arg( 'recherche', $region, get_post_type_archive_link( 'randonnee' ) ),
        );
    }
    $crumbs[] = array( '@type' => 'ListItem', 'position' => count( $crumbs ) + 1, 'name' => $titre, 'item' => $url );

    $breadcrumb = array(
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => $crumbs,
    );

    // HikingTrail / TouristAttraction — type dédié aux itinéraires de randonnée
    $trail = array(
        '@context'    => 'https://schema.org',
        '@type'       => array( 'HikingTrail', 'TouristAttraction' ),
        'name'        => $titre,
        'url'         => $url,
        'description' => $desc,
    );
    if ( $image ) $trail['image'] = $image;
    if ( $lieu ) {
        $trail['address'] = array( '@type' => 'PostalAddress', 'addressLocality' => $lieu, 'addressCountry' => 'FR' );
    }
    if ( $lat && $lon ) {
        $trail['geo'] = array( '@type' => 'GeoCoordinates', 'latitude' => (float) $lat, 'longitude' => (float) $lon );
    }
    $props = array();
    if ( $difficulte ) $props[] = array( '@type' => 'PropertyValue', 'name' => 'Difficulté',       'value' => $difficulte );
    if ( $distance )   $props[] = array( '@type' => 'PropertyValue', 'name' => 'Distance',         'value' => $distance );
    if ( $denivele )   $props[] = array( '@type' => 'PropertyValue', 'name' => 'Dénivelé positif', 'value' => $denivele );
    if ( $duree )      $props[] = array( '@type' => 'PropertyValue', 'name' => 'Durée',            'value' => $duree );
    if ( $props ) $trail['additionalProperty'] = $props;

    $avis_stats = rando_nono_get_avis_stats( $id );
    if ( $avis_stats['total'] > 0 ) {
        $trail['aggregateRating'] = array(
            '@type'       => 'AggregateRating',
            'ratingValue' => $avis_stats['moyenne'],
            'reviewCount' => $avis_stats['total'],
            'bestRating'  => 5,
            'worstRating' => 1,
        );
    }

    echo '<script type="application/ld+json">' . wp_json_encode( $breadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    echo '<script type="application/ld+json">' . wp_json_encode( $trail,      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'rando_nono_schema_jsonld', 3 );

// S'assurer que les CPT randonnee et matos sont inclus dans le sitemap WordPress (>=5.5)
add_filter( 'wp_sitemaps_post_types', function( $post_types ) {
    foreach ( array( 'randonnee', 'matos' ) as $cpt ) {
        if ( ! isset( $post_types[ $cpt ] ) ) {
            $post_types[ $cpt ] = get_post_type_object( $cpt );
        }
    }
    return $post_types;
} );

// Inclure la taxonomie "difficulté" dans le sitemap XML natif (/wp-sitemap.xml).
add_filter( 'wp_sitemaps_taxonomies', function( $taxonomies ) {
    if ( ! isset( $taxonomies['difficulte'] ) ) {
        $taxonomies['difficulte'] = get_taxonomy( 'difficulte' );
    }
    return $taxonomies;
} );

/**
 * robots.txt virtuel — autorise l'indexation et référence le sitemap natif
 * de WordPress (/wp-sitemap.xml, toujours à jour, inutile d'en écrire un statique).
 * Ne s'applique que si le site est public (réglages > Lecture > "Décourager les
 * moteurs de recherche" désactivé) : WordPress gère lui-même le cas contraire.
 */
add_filter( 'robots_txt', function( $output, $public ) {
    if ( ! $public ) return $output;
    $output  = "User-agent: *\n";
    $output .= "Allow: /\n";
    $output .= "Disallow: /wp-admin/\n";
    $output .= "Allow: /wp-admin/admin-ajax.php\n";
    $output .= "\n";
    $output .= 'Sitemap: ' . home_url( '/wp-sitemap.xml' ) . "\n";
    return $output;
}, 10, 2 );

/**
 * Manifest, theme-color et préchargement des polices critiques (au-dessus
 * de la ligne de flottaison sur toutes les pages : Abril Fatface pour les
 * titres, Merriweather Regular pour le texte).
 */
function rando_nono_head_extra() {
    $theme_uri = get_template_directory_uri();
    echo '<link rel="manifest" href="' . esc_url( $theme_uri . '/manifest.json' ) . '">' . "\n";
    echo '<meta name="theme-color" content="#2E5E3B">' . "\n";
    echo '<link rel="preload" href="' . esc_url( $theme_uri . '/assets/fonts/abril-fatface.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
    echo '<link rel="preload" href="' . esc_url( $theme_uri . '/assets/fonts/merriweather-regular.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
}
add_action( 'wp_head', 'rando_nono_head_extra', 1 );

/**
 * Préconnexion aux services externes (carte OSM, météo) sur les pages qui les
 * chargent réellement (mêmes conditions que l'enqueue de Leaflet/Chart.js dans
 * rando_nono_assets()) — la négociation DNS/TLS est faite en avance pendant que
 * la page se charge, au lieu d'attendre que le script JS déclenche la requête.
 */
function rando_nono_resource_hints() {
    $needs_map     = is_singular( 'randonnee' ) || is_post_type_archive( 'randonnee' );
    $needs_weather = is_singular( 'randonnee' );
    if ( ! $needs_map && ! $needs_weather ) return;

    if ( $needs_map ) {
        echo '<link rel="preconnect" href="https://tile.openstreetmap.org">' . "\n";
    }
    if ( $needs_weather ) {
        echo '<link rel="preconnect" href="https://api.open-meteo.com" crossorigin>' . "\n";
    }
}
add_action( 'wp_head', 'rando_nono_resource_hints', 1 );

/**
 * Précharge l'image du hero (LCP de la page d'accueil) dans le format et la
 * définition réellement affichés, en WebP avec repli JPEG automatique via
 * `type="image/webp"` : le navigateur choisit la bonne largeur sans attendre
 * la découverte du <img> dans le HTML.
 */
function rando_nono_preload_hero_image() {
    if ( ! is_front_page() ) return;
    $theme_uri = get_template_directory_uri();
    $srcset = rando_nono_hero_srcset( 'webp' );
    echo '<link rel="preload" as="image" imagesrcset="' . esc_attr( $srcset ) . '" imagesizes="100vw" fetchpriority="high" type="image/webp">' . "\n";
}
add_action( 'wp_head', 'rando_nono_preload_hero_image', 1 );

/**
 * Construit la chaîne srcset des variantes responsive du hero, générées à
 * l'avance dans /assets/img/responsive/ (voir hero-bg-{largeur}.{format}).
 */
function rando_nono_hero_srcset( $format = 'webp' ) {
    $theme_uri = get_template_directory_uri();
    $widths = array( 640, 960, 1400, 1983 );
    $parts = array();
    foreach ( $widths as $w ) {
        $parts[] = $theme_uri . '/assets/img/responsive/hero-bg-' . $w . '.' . $format . ' ' . $w . 'w';
    }
    return implode( ', ', $parts );
}

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
 *
 * @return WP_Post[]
 */
function rando_nono_get_related_randos( $post_id, $limit = 4 ) {
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

    return array_map( function( $item ) { return get_post( $item['id'] ); }, $scored );
}

/**
 * Favicon — généré à partir de l'image hero, recadré en carré.
 */
function rando_nono_favicon() {
    $base = get_template_directory_uri() . '/assets/img/';
    echo '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url( $base . 'favicon-32.png' ) . '">' . "\n";
    echo '<link rel="icon" type="image/png" sizes="192x192" href="' . esc_url( $base . 'favicon-192.png' ) . '">' . "\n";
    echo '<link rel="apple-touch-icon" sizes="512x512" href="' . esc_url( $base . 'favicon-512.png' ) . '">' . "\n";
}
add_action( 'wp_head', 'rando_nono_favicon', 2 );

/**
 * Fil d'Ariane (breadcrumb) — Accueil > Randonnées > Titre de la rando
 * Utilisation : <?php rando_nono_breadcrumb(); ?> dans n'importe quel template.
 */
function rando_nono_breadcrumb() {
    echo '<nav class="breadcrumb" aria-label="Fil d\'Ariane">';
    echo '<a href="' . esc_url( home_url( '/' ) ) . '">Accueil</a>';

    if ( is_post_type_archive( 'randonnee' ) ) {
        echo '<span class="breadcrumb-sep">›</span>';
        echo '<span class="breadcrumb-current">Toutes les randonnées</span>';

    } elseif ( is_singular( 'randonnee' ) ) {
        echo '<span class="breadcrumb-sep">›</span>';
        echo '<a href="' . esc_url( get_post_type_archive_link( 'randonnee' ) ) . '">Randonnées</a>';
        $region = rando_nono_lieu_region( get_post_meta( get_the_ID(), 'rando_lieu', true ) );
        if ( $region ) {
            echo '<span class="breadcrumb-sep">›</span>';
            echo '<a href="' . esc_url( add_query_arg( 'recherche', $region, get_post_type_archive_link( 'randonnee' ) ) ) . '">' . esc_html( $region ) . '</a>';
        }
        echo '<span class="breadcrumb-sep">›</span>';
        echo '<span class="breadcrumb-current">' . esc_html( get_the_title() ) . '</span>';

    } elseif ( is_singular( 'matos' ) ) {
        echo '<span class="breadcrumb-sep">›</span>';
        echo '<a href="' . esc_url( home_url( '/#matos' ) ) . '">Matos de Nono</a>';
        echo '<span class="breadcrumb-sep">›</span>';
        echo '<span class="breadcrumb-current">' . esc_html( get_the_title() ) . '</span>';

    } elseif ( is_page() ) {
        echo '<span class="breadcrumb-sep">›</span>';
        echo '<span class="breadcrumb-current">' . esc_html( get_the_title() ) . '</span>';

    } elseif ( is_search() ) {
        echo '<span class="breadcrumb-sep">›</span>';
        echo '<span class="breadcrumb-current">Recherche &laquo; ' . esc_html( get_search_query() ) . ' &raquo;</span>';

    } elseif ( is_404() ) {
        echo '<span class="breadcrumb-sep">›</span>';
        echo '<span class="breadcrumb-current">Page introuvable</span>';
    }

    echo '</nav>';
}

/**
 * Texte alternatif automatique pour les images à la une des randonnées
 * (si l'utilisateur n'a pas renseigné de texte alternatif manuellement).
 */
function rando_nono_auto_alt_text( $attr, $attachment, $size ) {
    if ( empty( $attr['alt'] ) && get_post_type() === 'randonnee' ) {
        $attr['alt'] = get_the_title() . ' — randonnée';
    }
    return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'rando_nono_auto_alt_text', 10, 3 );

/* ──────────────────────────────────────────
   7ter. VÉRIFICATION SEARCH CONSOLE / BING WEBMASTER — configurable depuis l'admin
   ────────────────────────────────────────── */
function rando_nono_seo_verif_menu() {
    add_options_page(
        'Vérification SEO',
        'Vérification SEO',
        'manage_options',
        'rando-nono-seo-verif',
        'rando_nono_seo_verif_page'
    );
}
add_action( 'admin_menu', 'rando_nono_seo_verif_menu' );

function rando_nono_seo_verif_register_settings() {
    register_setting( 'rando_nono_seo_verif_group', 'rando_nono_google_verif', array( 'sanitize_callback' => 'sanitize_text_field' ) );
    register_setting( 'rando_nono_seo_verif_group', 'rando_nono_bing_verif', array( 'sanitize_callback' => 'sanitize_text_field' ) );
}
add_action( 'admin_init', 'rando_nono_seo_verif_register_settings' );

function rando_nono_seo_verif_page() {
    ?>
    <div class="wrap">
        <h1>Vérification SEO</h1>
        <p>Renseigne ici les codes de vérification fournis par <strong>Google Search Console</strong> et <strong>Bing Webmaster Tools</strong> pour prouver que tu es propriétaire du site (méthode « balise HTML »), sans avoir à modifier de fichier.</p>
        <form method="post" action="options.php">
            <?php settings_fields( 'rando_nono_seo_verif_group' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="rando_nono_google_verif">Google Search Console</label></th>
                    <td>
                        <input type="text" style="width:400px" id="rando_nono_google_verif" name="rando_nono_google_verif" value="<?php echo esc_attr( get_option( 'rando_nono_google_verif' ) ); ?>" placeholder="Contenu de la balise meta (ex: AbCdEf123...)" />
                        <p class="description">Dans Search Console : Paramètres → Propriété → Vérifier la propriété → méthode « Balise HTML » → copie uniquement la valeur de l'attribut <code>content</code>.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="rando_nono_bing_verif">Bing Webmaster Tools</label></th>
                    <td>
                        <input type="text" style="width:400px" id="rando_nono_bing_verif" name="rando_nono_bing_verif" value="<?php echo esc_attr( get_option( 'rando_nono_bing_verif' ) ); ?>" placeholder="Contenu de la balise meta" />
                        <p class="description">Dans Bing Webmaster Tools : Paramètres → Vérification de propriété → méthode « Balise meta » → copie uniquement la valeur de l'attribut <code>content</code>.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function rando_nono_seo_verif_meta_tags() {
    $google = get_option( 'rando_nono_google_verif' );
    $bing   = get_option( 'rando_nono_bing_verif' );
    if ( $google ) {
        echo '<meta name="google-site-verification" content="' . esc_attr( $google ) . '">' . "\n";
    }
    if ( $bing ) {
        echo '<meta name="msvalidate.01" content="' . esc_attr( $bing ) . '">' . "\n";
    }
}
add_action( 'wp_head', 'rando_nono_seo_verif_meta_tags', 1 );
