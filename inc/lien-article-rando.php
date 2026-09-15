<?php
/**
 * Association d'un article à une randonnée
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 836-869 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

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

