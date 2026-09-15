<?php
/**
 * Données structurées JSON-LD (schema.org)
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 1493-1846 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

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

/**
 * Libellé de lieu COURT, pour les surfaces où la place est comptée : cartes
 * des grilles, popups de la carte d'ensemble, suggestions de la page 404,
 * favoris. La fiche de randonnée, le schema.org et les e-mails gardent le
 * lieu complet — c'est là qu'il a une valeur (référencement local, précision).
 *
 * Le champ « lieu » contient parfois une adresse entière (exemple réel :
 * « Cascade du Fornet Auvergne-Rhône-Alpes Savoie (73) Val-d'Isère, hameau
 * du Fornet, Parc national de la Vanoise »). On s'appuyait jusqu'ici sur une
 * troncature CSS, qui coupe sans discernement et n'aide ni le référencement
 * ni la lecture.
 *
 * Ordre de priorité :
 *   1. le champ « Lieu court » s'il est renseigné — c'est toujours Nono qui
 *      tranche, aucune heuristique ne devinera « Val-d'Isère » toute seule ;
 *   2. à défaut, la portion avant la première virgule (« Mourèze, Hérault »
 *      → « Mourèze »), coupée sur un espace si elle reste trop longue.
 *
 * Ce repli fait que les randonnées déjà publiées s'améliorent sans être
 * rouvertes une par une ; la colonne « Lieu (cartes) » de la liste des
 * randonnées signale celles qui méritent encore un libellé écrit à la main.
 */
/**
 * Les N premiers mots d'une chaîne — sert à repérer qu'un libellé de lieu
 * recopie le début du titre de la randonnée.
 */
function rando_nono_premiers_mots( $texte, $n = 3 ) {
    $mots = preg_split( '/\s+/u', trim( (string) $texte ), -1, PREG_SPLIT_NO_EMPTY );
    if ( ! $mots ) return '';
    return implode( ' ', array_slice( $mots, 0, $n ) );
}

/**
 * Commune de la randonnée, pour addressLocality du schema.org.
 *
 * schema.org attend UNE commune ; le champ « lieu » est libre et contient
 * parfois l'itinéraire complet. Trois sources, de la plus sûre à la plus
 * hasardeuse — et aucune valeur plutôt qu'une valeur fausse, qui ferait plus
 * de mal que de bien au référencement local.
 */
function rando_nono_lieu_commune( $post_id ) {
    // 1. Le champ « Lieu court » : c'est Nono qui a tranché.
    $court = trim( (string) get_post_meta( $post_id, 'rando_lieu_court', true ) );
    if ( '' !== $court ) {
        // « Pic Saint-Loup (34) » → « Pic Saint-Loup »
        return trim( preg_replace( '/\s*\(\d{2,3}[AB]?\)\s*$/u', '', $court ) );
    }

    $lieu = trim( (string) get_post_meta( $post_id, 'rando_lieu', true ) );
    if ( '' === $lieu ) return '';
    $premier = trim( explode( ',', $lieu )[0] );

    // 2. Ce qui suit un code de département entre parenthèses est presque
    //    toujours la commune : « … Savoie (73) Val-d'Isère » → « Val-d'Isère ».
    if ( preg_match( '/\(\d{2,3}[AB]?\)\s*(.+)$/u', $premier, $m ) ) {
        $candidat = trim( $m[1] );
        if ( '' !== $candidat && mb_strlen( $candidat ) <= 40 ) return $candidat;
    }

    // 3. Un premier segment court est en général déjà la commune
    //    (« Mourèze, Hérault »). Au-delà, on ne devine pas.
    return ( mb_strlen( $premier ) <= 40 ) ? $premier : '';
}

function rando_nono_lieu_court( $post_id, $max = 34 ) {
    $court = trim( (string) get_post_meta( $post_id, 'rando_lieu_court', true ) );
    if ( '' !== $court ) {
        return $court;
    }

    $lieu = trim( (string) get_post_meta( $post_id, 'rando_lieu', true ) );
    if ( '' === $lieu ) {
        return '';
    }

    $parts = explode( ',', $lieu );
    $court = trim( $parts[0] );

    // Quand le lieu commence par le nom du site remarquable, la troncature ne
    // garde que ce qui est déjà dans le titre : la carte de « Cascade du
    // Fornet et vallon de la Sassière » affichait « Cascade du Fornet… » et
    // perdait la seule information qui aide à choisir — la région. Dans ce
    // cas, on préfère le dernier segment (souvent le massif ou le département).
    $titre_mots = rando_nono_premiers_mots( get_the_title( $post_id ), 3 );
    if ( $titre_mots && 0 === mb_stripos( $court, $titre_mots ) ) {
        $repli = trim( end( $parts ) );
        if ( '' !== $repli && mb_strlen( $repli ) <= $max ) {
            return $repli;
        }
    }

    // mb_substr / mb_strlen : WordPress les fournit lui-même (wp-includes/
    // compat.php) si mbstring manque sur l'hébergement. Interdiction de
    // passer par substr() seul : il coupe au milieu d'un caractère accentué.
    if ( mb_strlen( $court ) <= $max ) {
        return $court;
    }

    $coupe = mb_substr( $court, 0, $max );
    // strrpos sur une ESPACE est sûr en UTF-8 (0x20 n'apparaît jamais à
    // l'intérieur d'une séquence multi-octets), et substr sur cette position
    // tombe donc forcément sur une frontière de caractère.
    $espace = strrpos( $coupe, ' ' );
    if ( false !== $espace && $espace > 8 ) {
        $coupe = substr( $coupe, 0, $espace );
    }

    return rtrim( $coupe, " \t\n\r\0\x0B,;:-" ) . '…';
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

    // ── Article de blog : BlogPosting ──
    // Les articles étaient le seul type de contenu sans données structurées :
    // zéro bloc JSON-LD, donc aucun résultat enrichi possible sur des sujets
    // qui s'y prêtent (« comment choisir ses chaussures »), et ni date ni
    // auteur transmis à Google.
    if ( is_singular( 'post' ) ) {
        $id_art  = get_the_ID();
        $img_art = get_the_post_thumbnail_url( $id_art, 'rando-hero' );
        $article = array(
            '@context'         => 'https://schema.org',
            '@type'            => 'BlogPosting',
            'headline'         => rando_nono_trim_title( get_the_title( $id_art ), 110 ),
            'description'      => rando_nono_meta_description_trim( get_the_excerpt( $id_art ) ),
            'url'              => get_permalink( $id_art ),
            'mainEntityOfPage' => array( '@type' => 'WebPage', '@id' => get_permalink( $id_art ) ),
            'datePublished'    => get_the_date( 'c', $id_art ),
            'dateModified'     => get_the_modified_date( 'c', $id_art ),
            'inLanguage'       => 'fr-FR',
            'author'           => array(
                '@type' => 'Person',
                'name'  => ( $a = get_userdata( (int) get_post_field( 'post_author', $id_art ) ) ) ? $a->display_name : get_bloginfo( 'name' ),
            ),
            'publisher'        => array(
                '@type' => 'Organization',
                'name'  => get_bloginfo( 'name' ),
                'logo'  => array(
                    '@type' => 'ImageObject',
                    'url'   => get_template_directory_uri() . '/assets/img/favicon-512.png',
                ),
            ),
        );
        if ( $img_art ) $article['image'] = $img_art;

        echo '<script type="application/ld+json">' . wp_json_encode( $article, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
        return;
    }

    // ── Page "Article / Guide de randos" : ItemList des randos mises en avant ──
    if ( is_page() && 'page-article-guide.php' === get_page_template_slug() ) {
        $guide_query = rando_nono_article_guide_query( get_the_ID() );
        if ( $guide_query->have_posts() ) {
            $items    = array();
            $position = 1;
            while ( $guide_query->have_posts() ) {
                $guide_query->the_post();
                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => $position++,
                    'url'      => get_permalink(),
                    'name'     => get_the_title(),
                );
            }
            wp_reset_postdata();

            $item_list = array(
                '@context'        => 'https://schema.org',
                '@type'           => 'ItemList',
                'name'            => get_the_title(),
                'itemListElement' => $items,
            );
            echo '<script type="application/ld+json">' . wp_json_encode( $item_list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
        }
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
        // addressLocality attend UNE commune. Y verser le champ « lieu » entier
        // produisait des valeurs de 100 caractères (« Cascade du Fornet
        // Auvergne-Rhône-Alpes Savoie (73) Val-d'Isère, hameau du Fornet, Parc
        // national de la Vanoise ») : balisage valide, mais inexploitable pour
        // le référencement local — précisément l'usage pour lequel la fiche
        // conserve le lieu complet. On découpe : la commune d'un côté, la
        // région de l'autre, le libellé entier restant dans description.
        $adresse = array( '@type' => 'PostalAddress', 'addressCountry' => 'FR' );

        $commune = rando_nono_lieu_commune( $id );
        if ( $commune ) $adresse['addressLocality'] = $commune;

        // Le dernier segment du lieu est souvent le département ou la région,
        // mais parfois un massif ou un parc : « Parc national de la Vanoise »
        // n'est pas une addressRegion, et une valeur trompeuse dessert plus le
        // référencement local qu'une valeur absente.
        $region_schema = rando_nono_lieu_region( $lieu );
        if ( $region_schema && ! preg_match( '/\b(parc|massif|réserve|forêt|vallée|sentier|GR\s?\d+)\b/iu', $region_schema ) ) {
            $adresse['addressRegion'] = $region_schema;
        }

        // Sans commune identifiable, mieux vaut le libellé brut que rien.
        if ( ! isset( $adresse['addressLocality'] ) ) {
            $adresse['addressLocality'] = rando_nono_lieu_court( $id );
        }

        $trail['address'] = $adresse;
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

    // La distance a une propriété dédiée dans schema.org : la laisser dans le
    // fourre-tout additionalProperty la rendait beaucoup moins exploitable.
    // On conserve la difficulté en PropertyValue — c'est une échelle maison
    // qui n'a pas d'équivalent normalisé.
    $distance_km = rando_nono_extract_number( $distance );
    if ( $distance_km > 0 ) {
        $trail['distance'] = array(
            '@type'    => 'QuantitativeValue',
            'value'    => $distance_km,
            'unitCode' => 'KMT',
        );
    }

    // Dates et auteur : disponibles sans effort, attendus par Google, et
    // absents jusqu'ici.
    $trail['datePublished'] = get_the_date( 'c', $id );
    $trail['dateModified']  = get_the_modified_date( 'c', $id );
    $auteur = get_userdata( (int) get_post_field( 'post_author', $id ) );
    if ( $auteur ) {
        $trail['author'] = array( '@type' => 'Person', 'name' => $auteur->display_name );
    }

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

