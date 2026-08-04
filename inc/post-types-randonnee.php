<?php
/**
 * Custom post type "randonnée" : enregistrement, exposition REST, réglage
 * "prochain projet" et champs personnalisés (méta-boxes de l'écran d'édition).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ──────────────────────────────────────────
   3. CUSTOM POST TYPE "RANDONNÉE"
   ────────────────────────────────────────── */
function rando_nono_register_cpt() {
    register_post_type( 'randonnee', array(
        'labels' => array(
            'name'          => 'Randonnées',
            'singular_name' => 'Randonnée',
            'add_new_item'  => 'Ajouter une randonnée',
            'edit_item'     => 'Modifier la randonnée',
            'menu_name'     => 'Randonnées',
        ),
        'public'       => true,
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-palmtree',
        'supports'     => array( 'title', 'editor', 'thumbnail' ),
        'rewrite'      => array( 'slug' => 'randonnee' ),
        'show_in_rest' => true,
    ) );

    register_taxonomy( 'difficulte', 'randonnee', array(
        'labels'       => array( 'name' => 'Difficulté', 'singular_name' => 'Difficulté' ),
        'public'       => true,
        'hierarchical' => true,
        'show_in_rest' => true,
    ) );
}
add_action( 'init', 'rando_nono_register_cpt' );

/**
 * Expose les champs personnalisés de la randonnée dans l'API REST
 * (/wp-json/wp/v2/randonnee/<id>, champ "meta") — lecture seule pour un
 * client externe (future appli mobile, widget, intégration partenaire) ;
 * l'écriture reste réservée à qui peut éditer l'article (comportement par
 * défaut de register_post_meta sans auth_callback dédié).
 */
function rando_nono_register_rest_meta() {
    $fields = array(
        'rando_lieu', 'rando_lat', 'rando_lon', 'rando_distance', 'rando_denivele',
        'rando_denivele_neg', 'rando_duree', 'rando_date', 'rando_meilleure_saison',
        'rando_maps_url', 'rando_gpx_url', 'rando_conseils',
    );
    foreach ( $fields as $field ) {
        register_post_meta( 'randonnee', $field, array(
            'type'         => 'string',
            'single'       => true,
            'show_in_rest' => true,
        ) );
    }
}
add_action( 'init', 'rando_nono_register_rest_meta' );

/* ──────────────────────────────────────────
   3bis. RÉGLAGE "PROCHAIN PROJET" — paramétrable depuis l'admin, sans coder
   ────────────────────────────────────────── */
function rando_nono_projet_menu() {
    add_options_page(
        'Prochain projet',
        'Prochain projet',
        'manage_options',
        'rando-nono-projet',
        'rando_nono_projet_page'
    );
}
add_action( 'admin_menu', 'rando_nono_projet_menu' );

function rando_nono_projet_register_settings() {
    register_setting( 'rando_nono_projet_group', 'rando_nono_projet_titre' );
    register_setting( 'rando_nono_projet_group', 'rando_nono_projet_description' );
    register_setting( 'rando_nono_projet_group', 'rando_nono_projet_distance' );
    register_setting( 'rando_nono_projet_group', 'rando_nono_projet_denivele' );
    register_setting( 'rando_nono_projet_group', 'rando_nono_projet_date' );
    register_setting( 'rando_nono_projet_group', 'rando_nono_projet_groupe' );
    register_setting( 'rando_nono_projet_group', 'rando_nono_projet_actif' );
}
add_action( 'admin_init', 'rando_nono_projet_register_settings' );

function rando_nono_projet_page() {
    ?>
    <div class="wrap">
        <h1>Prochain projet</h1>
        <p>Ce bloc s'affiche dans la section "À propos" du site. Laisse "Afficher ce bloc" décoché si tu n'as pas de projet en cours à mettre en avant.</p>
        <form method="post" action="options.php">
            <?php settings_fields( 'rando_nono_projet_group' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="rando_nono_projet_actif">Afficher ce bloc</label></th>
                    <td><input type="checkbox" id="rando_nono_projet_actif" name="rando_nono_projet_actif" value="1" <?php checked( get_option( 'rando_nono_projet_actif' ), '1' ); ?> /></td>
                </tr>
                <tr>
                    <th><label for="rando_nono_projet_titre">Titre du projet</label></th>
                    <td><input type="text" style="width:400px" id="rando_nono_projet_titre" name="rando_nono_projet_titre" value="<?php echo esc_attr( get_option( 'rando_nono_projet_titre' ) ); ?>" placeholder="Ex: GR20 Corse" /></td>
                </tr>
                <tr>
                    <th><label for="rando_nono_projet_description">Description</label></th>
                    <td><textarea style="width:400px;height:100px" id="rando_nono_projet_description" name="rando_nono_projet_description" placeholder="Présente le projet en quelques phrases"><?php echo esc_textarea( get_option( 'rando_nono_projet_description' ) ); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="rando_nono_projet_distance">Distance</label></th>
                    <td><input type="text" style="width:200px" id="rando_nono_projet_distance" name="rando_nono_projet_distance" value="<?php echo esc_attr( get_option( 'rando_nono_projet_distance' ) ); ?>" placeholder="Ex: 189 km" /></td>
                </tr>
                <tr>
                    <th><label for="rando_nono_projet_denivele">Dénivelé</label></th>
                    <td><input type="text" style="width:200px" id="rando_nono_projet_denivele" name="rando_nono_projet_denivele" value="<?php echo esc_attr( get_option( 'rando_nono_projet_denivele' ) ); ?>" placeholder="Ex: +12 800 m" /></td>
                </tr>
                <tr>
                    <th><label for="rando_nono_projet_date">Date prévue</label></th>
                    <td><input type="text" style="width:200px" id="rando_nono_projet_date" name="rando_nono_projet_date" value="<?php echo esc_attr( get_option( 'rando_nono_projet_date' ) ); ?>" placeholder="Ex: Juin 2027" /></td>
                </tr>
                <tr>
                    <th><label for="rando_nono_projet_groupe">Groupe / participants</label></th>
                    <td><input type="text" style="width:200px" id="rando_nono_projet_groupe" name="rando_nono_projet_groupe" value="<?php echo esc_attr( get_option( 'rando_nono_projet_groupe' ) ); ?>" placeholder="Ex: Groupe de 4" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/* ──────────────────────────────────────────
   4. CHAMPS PERSONNALISÉS — RANDONNÉE
   ────────────────────────────────────────── */
function rando_nono_add_meta_boxes() {
    add_meta_box( 'rando_nono_details', 'Détails de la randonnée', 'rando_nono_details_callback', 'randonnee', 'normal', 'high' );
    add_meta_box( 'rando_nono_conseils', 'Conseils pratiques', 'rando_nono_conseils_callback', 'randonnee', 'normal', 'default' );
    add_meta_box( 'rando_nono_photos', 'Galerie photos (slideshow)', 'rando_nono_photos_callback', 'randonnee', 'normal', 'default' );
    add_meta_box( 'rando_nono_sac', 'Contenu du sac pour cette sortie', 'rando_nono_sac_callback', 'randonnee', 'side', 'default' );
    add_meta_box( 'rando_nono_featured', 'Mise en avant', 'rando_nono_featured_callback', 'randonnee', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'rando_nono_add_meta_boxes' );

function rando_nono_details_callback( $post ) {
    wp_nonce_field( 'rando_nono_save_meta', 'rando_nono_nonce' );
    $champs = array(
        'rando_lieu'         => 'Lieu (ex: Mourèze, Hérault)',
        'rando_lat'          => 'Latitude (ex: 43.5783)',
        'rando_lon'          => 'Longitude (ex: 3.3922)',
        'rando_distance'     => 'Distance (ex: 12 km)',
        'rando_denivele'     => 'Dénivelé positif (ex: +380 m)',
        'rando_denivele_neg' => 'Dénivelé négatif (ex: -380 m)',
        'rando_duree'        => 'Durée (ex: 4h30)',
        'rando_date'         => 'Date de la sortie',
        'rando_meilleure_saison' => 'Meilleure saison (ex: Printemps / Automne)',
        'rando_maps_url'     => 'Lien Google Maps',
        'rando_gpx_url'      => 'URL du fichier GPX (upload média)',
    );
    echo '<table class="form-table">';
    foreach ( $champs as $key => $label ) {
        $value = get_post_meta( $post->ID, $key, true );
        echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th>';
        echo '<td><input type="text" style="width:100%" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" /></td></tr>';
    }
    echo '</table>';
}

function rando_nono_conseils_callback( $post ) {
    $conseils = get_post_meta( $post->ID, 'rando_conseils', true );
    echo '<p>Un conseil par ligne (ex: "Prévoir 2L d\'eau minimum", "Départ tôt l\'été pour éviter la chaleur").</p>';
    echo '<textarea name="rando_conseils" style="width:100%;height:100px">' . esc_textarea( $conseils ) . '</textarea>';
}

function rando_nono_photos_callback( $post ) {
    $photos = get_post_meta( $post->ID, 'rando_photos', true );
    echo '<p>IDs des images de la médiathèque, séparés par des virgules.</p>';
    echo '<textarea name="rando_photos" style="width:100%;height:80px">' . esc_textarea( $photos ) . '</textarea>';
    echo '<p style="color:#6B6B5E;font-size:12px">Astuce : ouvre chaque image dans la médiathèque, l\'ID est visible dans l\'URL.</p>';
}

function rando_nono_sac_callback( $post ) {
    $sac = get_post_meta( $post->ID, 'rando_sac', true );
    echo '<p>Un élément par ligne.</p>';
    echo '<textarea name="rando_sac" style="width:100%;height:160px">' . esc_textarea( $sac ) . '</textarea>';
}

function rando_nono_featured_callback( $post ) {
    wp_nonce_field( 'rando_nono_featured_save', 'rando_nono_featured_nonce' );
    $checked = get_post_meta( $post->ID, 'rando_a_la_une', true );
    echo '<label><input type="checkbox" name="rando_a_la_une" value="1" ' . checked( $checked, '1', false ) . ' /> Afficher dans le bloc "Dernière randonnée"</label>';
    echo '<p style="color:#6B6B5E;font-size:12px">Si aucune n\'est cochée, la plus récente est affichée automatiquement.</p>';

    if ( 'publish' === $post->post_status ) {
        echo '<hr>';
        echo '<label><input type="checkbox" name="rando_nono_renvoyer_newsletter" value="1" /> Renvoyer l\'e-mail de newsletter aux abonnés</label>';
        echo '<p style="color:#6B6B5E;font-size:12px">À cocher puis "Mettre à jour" pour renvoyer (ex : le premier envoi n\'est jamais arrivé).</p>';
    }
}

function rando_nono_save_meta( $post_id ) {
    if ( isset( $_POST['rando_nono_nonce'] ) && wp_verify_nonce( $_POST['rando_nono_nonce'], 'rando_nono_save_meta' ) ) {
        if ( ! ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) && current_user_can( 'edit_post', $post_id ) ) {
            $fields = array( 'rando_lieu', 'rando_lat', 'rando_lon', 'rando_distance', 'rando_denivele', 'rando_denivele_neg', 'rando_duree', 'rando_date', 'rando_meilleure_saison', 'rando_maps_url', 'rando_gpx_url', 'rando_photos', 'rando_sac', 'rando_conseils' );
            foreach ( $fields as $field ) {
                if ( isset( $_POST[ $field ] ) ) {
                    update_post_meta( $post_id, $field, sanitize_textarea_field( $_POST[ $field ] ) );
                }
            }
        }
    }
    if ( isset( $_POST['rando_nono_featured_nonce'] ) && wp_verify_nonce( $_POST['rando_nono_featured_nonce'], 'rando_nono_featured_save' ) ) {
        if ( ! ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) && current_user_can( 'edit_post', $post_id ) ) {
            update_post_meta( $post_id, 'rando_a_la_une', isset( $_POST['rando_a_la_une'] ) ? '1' : '' );

            if ( ! empty( $_POST['rando_nono_renvoyer_newsletter'] ) && 'publish' === get_post_status( $post_id ) ) {
                rando_nono_schedule_newsletter_send( $post_id );
            }
        }
    }
}
add_action( 'save_post_randonnee', 'rando_nono_save_meta' );
