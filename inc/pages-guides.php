<?php
/**
 * Pages « Article / Guide de randos » et filtres de sitemap
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 1847-1990 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ──────────────────────────────────────────
   7ter. PAGES "ARTICLE / GUIDE DE RANDOS"
   Pages éditoriales type "Les plus belles randos de l'Hérault" ou
   "Meilleures randos en famille" (voir page-article-guide.php) : texte
   libre depuis l'éditeur, complété automatiquement par une sélection de
   randonnées filtrée par difficulté et/ou lieu.
   ────────────────────────────────────────── */

/**
 * WP_Query des randonnées à afficher sur une page "Article / Guide de
 * randos", d'après les filtres choisis dans la métabox. Partagée par le
 * template et le schema.org (ItemList) pour rester synchronisée.
 */
function rando_nono_article_guide_query( $page_id ) {
    // Sélection manuelle (cases cochées dans la métabox) : si au moins une
    // rando est cochée, on affiche exactement celles-ci, dans l'ordre où
    // elles sont cochées — les filtres difficulté/lieu sont ignorés.
    $manuel = get_post_meta( $page_id, 'article_guide_manuel', true );
    $ids    = $manuel ? array_filter( array_map( 'absint', explode( ',', $manuel ) ) ) : array();

    if ( $ids ) {
        return new WP_Query( array(
            'post_type'      => 'randonnee',
            'post__in'       => $ids,
            'orderby'        => 'post__in',
            'posts_per_page' => count( $ids ),
        ) );
    }

    $difficulte = get_post_meta( $page_id, 'article_guide_difficulte', true );
    $lieu       = get_post_meta( $page_id, 'article_guide_lieu', true );
    $nombre     = (int) get_post_meta( $page_id, 'article_guide_nombre', true );
    if ( $nombre < 1 ) $nombre = 6;

    $args = array(
        'post_type'      => 'randonnee',
        'posts_per_page' => $nombre,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if ( $difficulte ) {
        $args['tax_query'] = array(
            array( 'taxonomy' => 'difficulte', 'field' => 'slug', 'terms' => $difficulte ),
        );
    }

    if ( $lieu ) {
        $args['meta_query'] = array(
            array( 'key' => 'rando_lieu', 'value' => $lieu, 'compare' => 'LIKE' ),
        );
    }

    return new WP_Query( $args );
}

function rando_nono_article_guide_add_meta_box() {
    add_meta_box( 'rando_nono_article_guide', 'Randonnées mises en avant', 'rando_nono_article_guide_callback', 'page', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'rando_nono_article_guide_add_meta_box' );

function rando_nono_article_guide_callback( $post ) {
    if ( 'page-article-guide.php' !== get_page_template_slug( $post->ID ) ) return;

    wp_nonce_field( 'rando_nono_article_guide_save', 'rando_nono_article_guide_nonce' );

    $eyebrow    = get_post_meta( $post->ID, 'article_guide_eyebrow', true ) ?: 'Guide';
    $difficulte = get_post_meta( $post->ID, 'article_guide_difficulte', true );
    $lieu       = get_post_meta( $post->ID, 'article_guide_lieu', true );
    $nombre     = get_post_meta( $post->ID, 'article_guide_nombre', true ) ?: '6';
    $manuel     = get_post_meta( $post->ID, 'article_guide_manuel', true );
    $manuel_ids = $manuel ? array_filter( array_map( 'absint', explode( ',', $manuel ) ) ) : array();

    echo '<p>Le texte de cette page (ci-dessus) reste entièrement libre. En dessous, une sélection de randonnées s\'affiche automatiquement : soit celles que tu coches toi-même ci-dessous, soit — si tu ne coches rien — celles qui correspondent aux filtres difficulté/lieu.</p>';

    echo '<p><strong>1. Choisir mes randos moi-même (prioritaire si des cases sont cochées)</strong></p>';
    $all_randos = get_posts( array( 'post_type' => 'randonnee', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
    if ( $all_randos ) {
        echo '<div style="max-height:220px;overflow-y:auto;border:1px solid #dcdcd4;padding:0.75rem;background:#fff">';
        foreach ( $all_randos as $r ) {
            $lieu_r = get_post_meta( $r->ID, 'rando_lieu', true );
            echo '<label style="display:block;margin-bottom:0.4rem"><input type="checkbox" name="article_guide_manuel[]" value="' . esc_attr( $r->ID ) . '" ' . checked( in_array( $r->ID, $manuel_ids, true ), true, false ) . ' /> ' . esc_html( get_the_title( $r ) ) . ( $lieu_r ? ' <span style="color:#6B6B5E">— ' . esc_html( $lieu_r ) . '</span>' : '' ) . '</label>';
        }
        echo '</div>';
    } else {
        echo '<p style="color:#6B6B5E">Aucune randonnée publiée pour le moment.</p>';
    }

    echo '<p style="margin-top:1.25rem"><strong>2. Ou laisser le site choisir automatiquement (si rien n\'est coché ci-dessus)</strong></p>';
    echo '<table class="form-table">';

    echo '<tr><th><label for="article_guide_eyebrow">Petit texte au-dessus du titre</label></th><td><input type="text" style="width:100%" id="article_guide_eyebrow" name="article_guide_eyebrow" value="' . esc_attr( $eyebrow ) . '" placeholder="Ex: Guide, Sélection, Coups de cœur..." /></td></tr>';

    echo '<tr><th><label for="article_guide_difficulte">Ne garder que cette difficulté</label></th><td><select id="article_guide_difficulte" name="article_guide_difficulte">';
    echo '<option value="">Toutes les difficultés</option>';
    $diff_terms = get_terms( array( 'taxonomy' => 'difficulte', 'hide_empty' => false ) );
    if ( $diff_terms && ! is_wp_error( $diff_terms ) ) {
        foreach ( $diff_terms as $term ) {
            echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( $difficulte, $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>';
        }
    }
    echo '</select></td></tr>';

    echo '<tr><th><label for="article_guide_lieu">Ne garder que ce lieu (optionnel)</label></th><td><input type="text" style="width:100%" id="article_guide_lieu" name="article_guide_lieu" value="' . esc_attr( $lieu ) . '" placeholder="Ex: Hérault, Mourèze..." />';
    echo '<p style="color:#6B6B5E;font-size:12px">Doit correspondre à un mot présent dans le champ "Lieu" des randonnées (ex: idéal pour "Meilleures randos en famille" combiné à la difficulté "Facile").</p></td></tr>';

    echo '<tr><th><label for="article_guide_nombre">Nombre de randos à afficher</label></th><td><input type="number" min="1" max="24" style="width:100px" id="article_guide_nombre" name="article_guide_nombre" value="' . esc_attr( $nombre ) . '" /></td></tr>';

    echo '</table>';
}

function rando_nono_article_guide_save( $post_id ) {
    if ( ! isset( $_POST['rando_nono_article_guide_nonce'] ) || ! wp_verify_nonce( $_POST['rando_nono_article_guide_nonce'], 'rando_nono_article_guide_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_page', $post_id ) ) return;

    update_post_meta( $post_id, 'article_guide_eyebrow', sanitize_text_field( wp_unslash( $_POST['article_guide_eyebrow'] ?? '' ) ) );
    update_post_meta( $post_id, 'article_guide_difficulte', sanitize_key( $_POST['article_guide_difficulte'] ?? '' ) );
    update_post_meta( $post_id, 'article_guide_lieu', sanitize_text_field( wp_unslash( $_POST['article_guide_lieu'] ?? '' ) ) );
    update_post_meta( $post_id, 'article_guide_nombre', absint( $_POST['article_guide_nombre'] ?? 6 ) ?: 6 );

    $manuel_ids = isset( $_POST['article_guide_manuel'] ) ? array_filter( array_map( 'absint', (array) $_POST['article_guide_manuel'] ) ) : array();
    update_post_meta( $post_id, 'article_guide_manuel', implode( ',', $manuel_ids ) );
}
add_action( 'save_post_page', 'rando_nono_article_guide_save' );

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

