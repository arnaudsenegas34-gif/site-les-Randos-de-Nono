<?php
/**
 * Custom post type "matos" (matériel) et rattachement d'un article de blog
 * à une randonnée.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ──────────────────────────────────────────
   5. CUSTOM POST TYPE "MATOS"
   ────────────────────────────────────────── */
function rando_nono_register_matos_cpt() {
    register_post_type( 'matos', array(
        'labels' => array(
            'name'          => 'Matos de Nono',
            'singular_name' => 'Matériel',
            'add_new_item'  => 'Ajouter un matériel',
            'edit_item'     => 'Modifier le matériel',
            'menu_name'     => 'Matos de Nono',
        ),
        'public'       => true,
        'has_archive'  => false,
        'menu_icon'    => 'dashicons-backpack',
        'supports'     => array( 'title', 'editor', 'thumbnail' ),
        'show_in_rest' => true,
    ) );

    register_taxonomy( 'categorie_matos', 'matos', array(
        'labels'       => array( 'name' => 'Catégorie', 'singular_name' => 'Catégorie' ),
        'public'       => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ) );
}
add_action( 'init', 'rando_nono_register_matos_cpt' );

function rando_nono_matos_meta_box() {
    add_meta_box( 'rando_nono_matos_lien', 'Lien produit (optionnel)', 'rando_nono_matos_lien_callback', 'matos', 'normal', 'high' );
    add_meta_box( 'rando_nono_matos_dimensions', 'Dimensions & poids', 'rando_nono_matos_dimensions_callback', 'matos', 'normal', 'default' );
    add_meta_box( 'rando_nono_matos_essentiel', 'Indispensable', 'rando_nono_matos_essentiel_callback', 'matos', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'rando_nono_matos_meta_box' );

function rando_nono_matos_lien_callback( $post ) {
    wp_nonce_field( 'rando_nono_matos_save', 'rando_nono_matos_nonce' );
    $lien = get_post_meta( $post->ID, 'matos_lien', true );
    $pourquoi = get_post_meta( $post->ID, 'matos_pourquoi', true );
    echo '<p><label for="matos_lien"><strong>URL du produit</strong> (Amazon, Decathlon...)</label><br>';
    echo '<input type="text" style="width:100%" id="matos_lien" name="matos_lien" value="' . esc_attr( $lien ) . '" /></p>';
    echo '<p style="margin-top:1rem"><label for="matos_pourquoi"><strong>Pourquoi je l\'utilise</strong> (l\'avantage concret que tu en tires)</label><br>';
    echo '<textarea style="width:100%;height:80px" id="matos_pourquoi" name="matos_pourquoi">' . esc_textarea( $pourquoi ) . '</textarea></p>';
}

function rando_nono_matos_dimensions_callback( $post ) {
    $largeur = get_post_meta( $post->ID, 'matos_largeur_cm', true );
    $hauteur = get_post_meta( $post->ID, 'matos_hauteur_cm', true );
    $poids   = get_post_meta( $post->ID, 'matos_poids_g', true );
    echo '<p style="color:#6B6B5E;font-size:12px;margin-bottom:8px">Ces dimensions servent à trier et dimensionner les objets sur la page (les plus grands en premier). Le poids est affiché dans la fiche détail.</p>';
    echo '<table class="form-table"><tr>';
    echo '<th><label for="matos_largeur_cm">Largeur (cm)</label></th>';
    echo '<td><input type="number" step="0.1" min="0" style="width:100px" id="matos_largeur_cm" name="matos_largeur_cm" value="' . esc_attr( $largeur ) . '" placeholder="30" /></td>';
    echo '<th><label for="matos_hauteur_cm">Hauteur (cm)</label></th>';
    echo '<td><input type="number" step="0.1" min="0" style="width:100px" id="matos_hauteur_cm" name="matos_hauteur_cm" value="' . esc_attr( $hauteur ) . '" placeholder="20" /></td>';
    echo '</tr><tr>';
    echo '<th><label for="matos_poids_g">Poids (g)</label></th>';
    echo '<td><input type="number" step="1" min="0" style="width:100px" id="matos_poids_g" name="matos_poids_g" value="' . esc_attr( $poids ) . '" placeholder="350" /></td>';
    echo '</tr></table>';
}

function rando_nono_matos_essentiel_callback( $post ) {
    $checked = get_post_meta( $post->ID, 'matos_essentiel', true );
    echo '<label><input type="checkbox" name="matos_essentiel" value="1" ' . checked( $checked, '1', false ) . ' /> Cet objet part dans le sac à chaque sortie</label>';
}

function rando_nono_matos_save( $post_id ) {
    if ( ! isset( $_POST['rando_nono_matos_nonce'] ) || ! wp_verify_nonce( $_POST['rando_nono_matos_nonce'], 'rando_nono_matos_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    if ( isset( $_POST['matos_lien'] ) ) {
        update_post_meta( $post_id, 'matos_lien', esc_url_raw( $_POST['matos_lien'] ) );
    }
    if ( isset( $_POST['matos_pourquoi'] ) ) {
        update_post_meta( $post_id, 'matos_pourquoi', sanitize_textarea_field( $_POST['matos_pourquoi'] ) );
    }
    foreach ( array( 'matos_largeur_cm', 'matos_hauteur_cm', 'matos_poids_g' ) as $dim_field ) {
        if ( isset( $_POST[ $dim_field ] ) ) {
            update_post_meta( $post_id, $dim_field, sanitize_text_field( $_POST[ $dim_field ] ) );
        }
    }
    update_post_meta( $post_id, 'matos_essentiel', isset( $_POST['matos_essentiel'] ) ? '1' : '' );
}
add_action( 'save_post_matos', 'rando_nono_matos_save' );

/* ──────────────────────────────────────────
   5ter. LIER UN ARTICLE À UNE RANDONNÉE
   Ajoute un champ sur l'écran d'édition d'un article pour le rattacher à
   une randonnée : l'article apparaît alors dans la section "Articles &
   récits liés" de la fiche de cette randonnée.
   ────────────────────────────────────────── */
function rando_nono_article_rando_meta_box() {
    add_meta_box( 'rando_nono_article_rando', 'Randonnée associée', 'rando_nono_article_rando_callback', 'post', 'side', 'default' );
}
add_action( 'add_meta_boxes', 'rando_nono_article_rando_meta_box' );

function rando_nono_article_rando_callback( $post ) {
    wp_nonce_field( 'rando_nono_article_rando_save', 'rando_nono_article_rando_nonce' );
    $selected = get_post_meta( $post->ID, 'article_rando_id', true );
    $randos = get_posts( array( 'post_type' => 'randonnee', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) );
    echo '<p style="color:#6B6B5E;font-size:12px">Rattache cet article à une randonnée pour qu\'il apparaisse dans sa fiche.</p>';
    echo '<select name="article_rando_id" style="width:100%">';
    echo '<option value="">Aucune</option>';
    foreach ( $randos as $rando ) {
        echo '<option value="' . esc_attr( $rando->ID ) . '" ' . selected( $selected, (string) $rando->ID, false ) . '>' . esc_html( $rando->post_title ) . '</option>';
    }
    echo '</select>';
}

function rando_nono_article_rando_save( $post_id ) {
    if ( ! isset( $_POST['rando_nono_article_rando_nonce'] ) || ! wp_verify_nonce( $_POST['rando_nono_article_rando_nonce'], 'rando_nono_article_rando_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    if ( isset( $_POST['article_rando_id'] ) ) {
        update_post_meta( $post_id, 'article_rando_id', sanitize_text_field( $_POST['article_rando_id'] ) );
    }
}
add_action( 'save_post_post', 'rando_nono_article_rando_save' );
