<?php
/**
 * Les Randos de Nono — fonctions du thème
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once get_template_directory() . '/inc/icons.php';

// Seeder de données de test (admin uniquement — à retirer en production)
if ( is_admin() ) {
    require_once get_template_directory() . '/inc/data-seeder.php';
}

/* ──────────────────────────────────────────
   1. SETUP DU THÈME
   ────────────────────────────────────────── */
function rando_nono_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'menus' );
    register_nav_menus( array( 'primary' => __( 'Menu principal', 'rando-nono' ) ) );
}
add_action( 'after_setup_theme', 'rando_nono_setup' );

/* ──────────────────────────────────────────
   2. ENQUEUE STYLES & SCRIPTS
   ────────────────────────────────────────── */
function rando_nono_assets() {
    $theme_version = wp_get_theme()->get( 'Version' );
    $theme_uri     = get_template_directory_uri();

    // ── Polices ──
    wp_enqueue_style( 'rando-nono-fonts', $theme_uri . '/assets/css/fonts.css', array(), $theme_version );

    // ── Style principal ──
    wp_enqueue_style( 'rando-nono-style', get_stylesheet_uri(), array( 'rando-nono-fonts' ), $theme_version );

    // La modale (carte + profil altimétrique) n'existe que sur l'accueil et l'archive des randonnées :
    // inutile de charger Leaflet/Chart.js/modal.js sur les mentions légales, le 404, etc.
    $needs_modal   = is_front_page() || is_post_type_archive( 'randonnee' );
    $needs_leaflet = $needs_modal || is_singular( 'randonnee' );
    $main_deps     = array();

    // ── Leaflet (carte interactive) ──
    if ( $needs_leaflet ) {
        wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
        wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
        wp_enqueue_script( 'leaflet-gpx', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet-gpx/1.7.0/gpx.min.js', array( 'leaflet' ), '1.7.0', true );
    }

    if ( $needs_modal ) {
        // ── CSS modal isolé ──
        wp_enqueue_style( 'rando-nono-modal', $theme_uri . '/assets/css/components/modal.css', array( 'rando-nono-style' ), $theme_version );

        // ── Chart.js (profil altimétrique) ──
        wp_enqueue_script( 'chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', array(), '4.4.0', true );

        wp_enqueue_script( 'rando-nono-modal', $theme_uri . '/assets/js/components/modal.js', array( 'leaflet', 'leaflet-gpx', 'chartjs' ), $theme_version, true );
        wp_enqueue_script( 'rando-nono-randos', $theme_uri . '/assets/js/pages/randos.js', array( 'rando-nono-modal' ), $theme_version, true );

        // Données PHP → JS (URLs dynamiques)
        wp_localize_script( 'rando-nono-modal', 'randoNono', array(
            'placeholderUrl' => $theme_uri . '/assets/img/placeholder-rando.jpg',
            'themeUri'       => $theme_uri,
        ) );

        $main_deps[] = 'rando-nono-modal';
        $main_deps[] = 'rando-nono-randos';
    }

    // Le panneau "Matos de Nono" n'existe que sur l'accueil.
    if ( is_front_page() ) {
        wp_enqueue_style( 'rando-nono-matos', $theme_uri . '/assets/css/components/matos.css', array( 'rando-nono-style' ), $theme_version );
        wp_enqueue_script( 'rando-nono-matos', $theme_uri . '/assets/js/components/matos.js', array(), $theme_version, true );
    }

    wp_enqueue_script( 'rando-nono-main', $theme_uri . '/assets/js/main.js', $main_deps, $theme_version, true );

    // ── Single randonnée (CSS + JS chargés uniquement sur la fiche) ──
    // Les articles (post) réutilisent le même CSS pour la navigation précédent/suivant,
    // mais n'ont pas besoin de la carte Leaflet.
    if ( is_singular( 'randonnee' ) || is_singular( 'post' ) ) {
        wp_enqueue_style( 'rando-nono-single', $theme_uri . '/assets/css/single-randonnee.css', array( 'rando-nono-style' ), filemtime( get_template_directory() . '/assets/css/single-randonnee.css' ) );
    }
    if ( is_singular( 'randonnee' ) ) {
        wp_enqueue_script( 'rando-nono-single', $theme_uri . '/assets/js/pages/single-randonnee.js', array( 'leaflet', 'leaflet-gpx' ), filemtime( get_template_directory() . '/assets/js/pages/single-randonnee.js' ), true );
    }
}
add_action( 'wp_enqueue_scripts', 'rando_nono_assets' );

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
        }
    }
}
add_action( 'save_post_randonnee', 'rando_nono_save_meta' );

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

/* ──────────────────────────────────────────
   5bis. CRÉATION AUTOMATIQUE DE LA PAGE "MENTIONS LÉGALES"
   ────────────────────────────────────────── */
function rando_nono_create_mentions_legales_page() {
    if ( get_page_by_path( 'mentions-legales' ) ) return;
    wp_insert_post( array(
        'post_title'   => 'Mentions légales',
        'post_name'    => 'mentions-legales',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '',
    ) );
}
add_action( 'after_switch_theme', 'rando_nono_create_mentions_legales_page' );
add_action( 'init', function() {
    if ( get_transient( 'rando_nono_ml_checked' ) ) return;
    rando_nono_create_mentions_legales_page();
    set_transient( 'rando_nono_ml_checked', 1, DAY_IN_SECONDS );
} );

/* ──────────────────────────────────────────
   6. NETTOYAGE — sécurité & performance de base
   ────────────────────────────────────────── */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'xmlrpc_enabled', '__return_false' );

// Retire la balise canonique par défaut de WordPress : le thème en génère déjà
// une (voir rando_nono_seo_meta_tags) — deux balises canoniques dupliquaient
// systématiquement le <head> de chaque page.
remove_action( 'wp_head', 'rel_canonical' );

/* ──────────────────────────────────────────
   6bis. PERMALIENS LISIBLES
   Force une structure d'URL lisible (/randonnee/nom-de-la-sortie/) au lieu
   des URLs par défaut de type ?p=123, y compris pour un site fraîchement
   installé qui n'aurait pas encore de permaliens personnalisés.
   ────────────────────────────────────────── */
function rando_nono_force_pretty_permalinks() {
    if ( '' === get_option( 'permalink_structure' ) ) {
        update_option( 'permalink_structure', '/%postname%/' );
        flush_rewrite_rules();
    }
}
add_action( 'after_switch_theme', 'rando_nono_force_pretty_permalinks' );

/* ──────────────────────────────────────────
   7. SEO DE BASE — meta description + Open Graph
   (pas de plugin nécessaire pour ce niveau de besoin)
   ────────────────────────────────────────── */
function rando_nono_seo_meta_tags() {
    $description = '';
    $title       = get_bloginfo( 'name' );
    $image       = get_template_directory_uri() . '/assets/img/og-image.jpg';
    $url         = home_url( add_query_arg( null, null ) );

    if ( is_singular( 'randonnee' ) ) {
        global $post;
        $lieu      = get_post_meta( $post->ID, 'rando_lieu', true );
        $distance  = get_post_meta( $post->ID, 'rando_distance', true );
        $denivele  = get_post_meta( $post->ID, 'rando_denivele', true );
        $duree     = get_post_meta( $post->ID, 'rando_duree', true );
        $content_desc = wp_trim_words( get_the_content(), 28, '…' );
        if ( $content_desc ) {
            $description = $content_desc;
        } else {
            $parts = array_filter( array( $distance, $denivele, $duree ) );
            $description = 'Randonnée' . ( $lieu ? ' à ' . $lieu : '' )
                . ( $parts ? ' : ' . implode( ', ', $parts ) : '' )
                . '. Retrouvez la trace GPX, les photos et tous les détails de cette sortie.';
        }
        $title = get_the_title() . ( $lieu ? ' — ' . $lieu : '' ) . ' | ' . get_bloginfo( 'name' );
        $thumb = get_the_post_thumbnail_url( $post->ID, 'large' );
        if ( $thumb ) $image = $thumb;
    } elseif ( is_singular( 'matos' ) ) {
        $description = wp_trim_words( get_the_content(), 28, '…' );
        $title = get_the_title() . ' | Matos de Nono';
    } elseif ( is_post_type_archive( 'randonnee' ) ) {
        $description = 'Toutes les randonnées documentées par Nono dans l\'Hérault et ailleurs : traces GPX, photos, météo en temps réel et détails de chaque sortie.';
        $title = 'Toutes les randonnées | ' . get_bloginfo( 'name' );
    } elseif ( is_front_page() ) {
        $description = 'Carnet de randonnée : récits, traces GPX à télécharger, météo en temps réel, équipement et statistiques de mes sorties dans l\'Hérault et ailleurs.';
        $title = get_bloginfo( 'name' ) . ' — Carnet de randonnée, traces GPX & Hérault';
    } elseif ( is_page() ) {
        $description = wp_trim_words( get_the_content(), 28, '…' );
        $title = get_the_title() . ' | ' . get_bloginfo( 'name' );
    } elseif ( is_singular( 'post' ) ) {
        $description = has_excerpt() ? get_the_excerpt() : wp_trim_words( get_the_content(), 28, '…' );
        $title = get_the_title() . ' | ' . get_bloginfo( 'name' );
        $thumb = get_the_post_thumbnail_url( get_the_ID(), 'large' );
        if ( $thumb ) $image = $thumb;
    } elseif ( is_home() || is_category() || is_tag() ) {
        $description = 'Actus, récits et conseils de randonnée par Nono.';
        $title = ( is_home() ? 'Actus & récits' : single_cat_title( '', false ) . ' — Actus' ) . ' | ' . get_bloginfo( 'name' );
    }

    if ( ! $description ) {
        $description = get_bloginfo( 'description' );
    }

    echo "\n" . '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<meta property="og:type" content="' . ( is_singular( 'randonnee' ) || is_singular( 'post' ) ? 'article' : 'website' ) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
}
add_action( 'wp_head', 'rando_nono_seo_meta_tags', 1 );

/* ──────────────────────────────────────────
   7bis. SCHEMA.ORG JSON-LD — données structurées pour Google
   ────────────────────────────────────────── */
function rando_nono_schema_jsonld() {
    if ( ! is_singular( 'randonnee' ) ) return;
    global $post;

    $id         = $post->ID;
    $titre      = get_the_title( $id );
    $url        = get_permalink( $id );
    $lieu       = get_post_meta( $id, 'rando_lieu', true );
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

    // BreadcrumbList
    $breadcrumb = array(
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => array(
            array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil',      'item' => home_url( '/' ) ),
            array( '@type' => 'ListItem', 'position' => 2, 'name' => 'Randonnées',   'item' => get_post_type_archive_link( 'randonnee' ) ),
            array( '@type' => 'ListItem', 'position' => 3, 'name' => $titre,         'item' => $url ),
        ),
    );

    // SportsActivity
    $activity = array(
        '@context'    => 'https://schema.org',
        '@type'       => 'SportsEvent',
        'name'        => $titre,
        'url'         => $url,
        'description' => $desc,
        'sport'       => 'Randonnée pédestre',
    );
    if ( $image ) $activity['image'] = $image;
    if ( $lieu ) {
        $activity['location'] = array( '@type' => 'Place', 'name' => $lieu );
        if ( $lat && $lon ) {
            $activity['location']['geo'] = array( '@type' => 'GeoCoordinates', 'latitude' => (float) $lat, 'longitude' => (float) $lon );
        }
    }
    $props = array();
    if ( $difficulte ) $props[] = array( '@type' => 'PropertyValue', 'name' => 'Difficulté',         'value' => $difficulte );
    if ( $distance )   $props[] = array( '@type' => 'PropertyValue', 'name' => 'Distance',           'value' => $distance );
    if ( $denivele )   $props[] = array( '@type' => 'PropertyValue', 'name' => 'Dénivelé positif',   'value' => $denivele );
    if ( $duree )      $props[] = array( '@type' => 'PropertyValue', 'name' => 'Durée',              'value' => $duree );
    if ( $props ) $activity['additionalProperty'] = $props;

    echo '<script type="application/ld+json">' . wp_json_encode( $breadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    echo '<script type="application/ld+json">' . wp_json_encode( $activity,   JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
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

    } elseif ( is_404() ) {
        echo '<span class="breadcrumb-sep">›</span>';
        echo '<span class="breadcrumb-current">Page introuvable</span>';
    }

    echo '</nav>';
}

/**
 * Title tag propre par contexte (vient compléter add_theme_support('title-tag')
 * en forçant un format cohérent pour les randos).
 */
function rando_nono_document_title_parts( $title ) {
    if ( is_singular( 'randonnee' ) ) {
        global $post;
        $lieu = get_post_meta( $post->ID, 'rando_lieu', true );
        $title['title'] = get_the_title() . ( $lieu ? ' — ' . $lieu : '' ) . ' : trace GPX et récit de randonnée';
    } elseif ( is_front_page() ) {
        $title['title']   = 'Les Randos de Nono';
        $title['tagline'] = 'récits de randonnée, traces GPX à télécharger et carnet de sorties dans l\'Hérault';
    } elseif ( is_post_type_archive( 'randonnee' ) ) {
        $title['title'] = 'Toutes les randonnées avec trace GPX à télécharger';
    } elseif ( is_singular( 'matos' ) ) {
        $title['title'] = get_the_title() . ' — matériel de randonnée testé sur le terrain';
    }
    return $title;
}
add_filter( 'document_title_parts', 'rando_nono_document_title_parts' );

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

/* ──────────────────────────────────────────
   8. GOOGLE ANALYTICS (GA4) — configurable depuis l'admin, sans coder
   ────────────────────────────────────────── */
function rando_nono_ga_menu() {
    add_options_page(
        'Google Analytics',
        'Google Analytics',
        'manage_options',
        'rando-nono-ga',
        'rando_nono_ga_page'
    );
}
add_action( 'admin_menu', 'rando_nono_ga_menu' );

function rando_nono_ga_register_settings() {
    register_setting( 'rando_nono_ga_group', 'rando_nono_ga_id', array(
        'sanitize_callback' => 'sanitize_text_field',
    ) );
}
add_action( 'admin_init', 'rando_nono_ga_register_settings' );

function rando_nono_ga_page() {
    ?>
    <div class="wrap">
        <h1>Google Analytics</h1>
        <p>Renseigne ton identifiant de mesure GA4 (format <code>G-XXXXXXXXXX</code>, disponible dans Google Analytics → Admin → Flux de données) pour activer le suivi des visites. Laisse le champ vide pour désactiver le suivi.</p>
        <p>Un bandeau de consentement s'affiche automatiquement aux visiteurs dès qu'un ID est renseigné : Google Analytics ne se charge que si le visiteur clique sur « Accepter » (conformité RGPD/CNIL).</p>
        <form method="post" action="options.php">
            <?php settings_fields( 'rando_nono_ga_group' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="rando_nono_ga_id">ID de mesure GA4</label></th>
                    <td><input type="text" style="width:250px" id="rando_nono_ga_id" name="rando_nono_ga_id" value="<?php echo esc_attr( get_option( 'rando_nono_ga_id' ) ); ?>" placeholder="G-XXXXXXXXXX" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function rando_nono_ga_valid_id() {
    $ga_id = get_option( 'rando_nono_ga_id' );
    return ( $ga_id && preg_match( '/^G-[A-Z0-9]+$/', $ga_id ) ) ? $ga_id : '';
}

/* ──────────────────────────────────────────
   8bis. PIXEL FACEBOOK — configurable depuis l'admin, sans coder
   ────────────────────────────────────────── */
function rando_nono_fb_menu() {
    add_options_page(
        'Pixel Facebook',
        'Pixel Facebook',
        'manage_options',
        'rando-nono-fb',
        'rando_nono_fb_page'
    );
}
add_action( 'admin_menu', 'rando_nono_fb_menu' );

function rando_nono_fb_register_settings() {
    register_setting( 'rando_nono_fb_group', 'rando_nono_fb_pixel_id', array(
        'sanitize_callback' => 'sanitize_text_field',
    ) );
}
add_action( 'admin_init', 'rando_nono_fb_register_settings' );

function rando_nono_fb_page() {
    ?>
    <div class="wrap">
        <h1>Pixel Facebook</h1>
        <p>Renseigne ton identifiant de pixel Facebook (visible dans Meta Events Manager → Pixels, une suite de chiffres) pour activer le suivi publicitaire Facebook/Instagram. Laisse le champ vide pour désactiver.</p>
        <p>Le pixel utilise le même bandeau de consentement que Google Analytics : il ne se charge que si le visiteur clique sur « Accepter » (conformité RGPD/CNIL).</p>
        <form method="post" action="options.php">
            <?php settings_fields( 'rando_nono_fb_group' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="rando_nono_fb_pixel_id">ID du pixel Facebook</label></th>
                    <td><input type="text" style="width:250px" id="rando_nono_fb_pixel_id" name="rando_nono_fb_pixel_id" value="<?php echo esc_attr( get_option( 'rando_nono_fb_pixel_id' ) ); ?>" placeholder="123456789012345" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function rando_nono_fb_valid_id() {
    $fb_id = get_option( 'rando_nono_fb_pixel_id' );
    return ( $fb_id && preg_match( '/^\d{6,20}$/', $fb_id ) ) ? $fb_id : '';
}

// Charge le bandeau de consentement + les scripts GA4/Pixel Facebook (ils ne s'activent qu'après clic "Accepter")
function rando_nono_ga_assets() {
    $ga_id = rando_nono_ga_valid_id();
    $fb_id = rando_nono_fb_valid_id();
    if ( ! $ga_id && ! $fb_id ) return;

    $theme_uri     = get_template_directory_uri();
    $theme_version = wp_get_theme()->get( 'Version' );

    wp_enqueue_style( 'rando-nono-cookie-consent', $theme_uri . '/assets/css/components/cookie-consent.css', array( 'rando-nono-style' ), $theme_version );
    wp_enqueue_script( 'rando-nono-cookie-consent', $theme_uri . '/assets/js/components/cookie-consent.js', array(), $theme_version, true );
    wp_localize_script( 'rando-nono-cookie-consent', 'randoNonoGA', array(
        'id'        => $ga_id,
        'fbPixelId' => $fb_id,
    ) );
}
add_action( 'wp_enqueue_scripts', 'rando_nono_ga_assets' );

// Marquage HTML du bandeau — n'apparaît que si GA4 et/ou le pixel Facebook sont configurés
function rando_nono_cookie_banner() {
    $ga_id = rando_nono_ga_valid_id();
    $fb_id = rando_nono_fb_valid_id();
    if ( ! $ga_id && ! $fb_id ) return;

    if ( $ga_id && $fb_id ) {
        $texte = 'Ce site utilise Google Analytics et le pixel Facebook pour mesurer sa fréquentation et ses statistiques publicitaires.';
    } elseif ( $fb_id ) {
        $texte = 'Ce site utilise le pixel Facebook pour mesurer ses statistiques publicitaires.';
    } else {
        $texte = 'Ce site utilise Google Analytics pour mesurer sa fréquentation.';
    }
    ?>
    <div class="cookie-consent" id="cookie-consent" role="dialog" aria-live="polite" aria-label="Consentement aux cookies">
      <p>
        <?php echo esc_html( $texte ); ?> Ces cookies ne sont déposés qu'avec votre accord.
        <a href="<?php echo esc_url( home_url( '/mentions-legales/#cookies' ) ); ?>">En savoir plus</a>
      </p>
      <div class="cookie-consent-actions">
        <button type="button" id="cookie-consent-refuse" class="btn-nav">Refuser</button>
        <button type="button" id="cookie-consent-accept" class="btn-nav btn-nav-solid">Accepter</button>
      </div>
    </div>
    <?php
}
add_action( 'wp_footer', 'rando_nono_cookie_banner' );
