<?php
/**
 * Les Randos de Nono — fonctions du thème
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once get_template_directory() . '/inc/icons.php';

// Seeder de données de test (admin uniquement, et seulement quand WP_DEBUG est
// actif — ainsi il est automatiquement inactif sur un site en production sans
// dépendre d'un oubli de suppression manuelle du fichier).
if ( is_admin() && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
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
/**
 * Version de cache-busting d'un asset local, basée sur sa date de modification
 * (fallback sur la version du thème si le fichier est introuvable).
 */
function rando_nono_asset_ver( $relative_path ) {
    $file = get_template_directory() . $relative_path;
    return file_exists( $file ) ? filemtime( $file ) : wp_get_theme()->get( 'Version' );
}

function rando_nono_assets() {
    $theme_uri = get_template_directory_uri();

    // ── Polices ──
    wp_enqueue_style( 'rando-nono-fonts', $theme_uri . '/assets/css/fonts.css', array(), rando_nono_asset_ver( '/assets/css/fonts.css' ) );

    // ── Style principal ──
    wp_enqueue_style( 'rando-nono-style', get_stylesheet_uri(), array( 'rando-nono-fonts' ), rando_nono_asset_ver( '/style.css' ) );

    // La modale (carte + profil altimétrique) n'existe que sur l'accueil et l'archive des randonnées :
    // inutile de charger Leaflet/Chart.js/modal.js sur les mentions légales, le 404, etc.
    $needs_modal   = is_front_page() || is_post_type_archive( 'randonnee' ) || is_search();
    $needs_leaflet = $needs_modal || is_singular( 'randonnee' );
    $main_deps     = array();

    // ── Leaflet (carte interactive) — hébergé localement dans assets/vendor/ ──
    // (plus de dépendance à unpkg/cdnjs : évite une requête réseau externe à chaque
    // visite et le risque d'un CDN tiers compromis servant du JS modifié)
    if ( $needs_leaflet ) {
        wp_enqueue_style( 'leaflet', $theme_uri . '/assets/vendor/leaflet/leaflet.css', array(), '1.9.4' );
        wp_enqueue_script( 'leaflet', $theme_uri . '/assets/vendor/leaflet/leaflet.js', array(), '1.9.4', true );
        wp_enqueue_script( 'leaflet-gpx', $theme_uri . '/assets/vendor/leaflet-gpx/gpx.min.js', array( 'leaflet' ), '1.7.0', true );
    }

    if ( $needs_modal ) {
        // ── CSS modal isolé ──
        wp_enqueue_style( 'rando-nono-modal', $theme_uri . '/assets/css/components/modal.css', array( 'rando-nono-style' ), rando_nono_asset_ver( '/assets/css/components/modal.css' ) );

        // ── Chart.js (profil altimétrique) — hébergé localement ──
        wp_enqueue_script( 'chartjs', $theme_uri . '/assets/vendor/chartjs/chart.umd.min.js', array(), '4.4.0', true );

        wp_enqueue_script( 'rando-nono-modal', $theme_uri . '/assets/js/components/modal.js', array( 'leaflet', 'leaflet-gpx', 'chartjs' ), rando_nono_asset_ver( '/assets/js/components/modal.js' ), true );
        wp_enqueue_script( 'rando-nono-randos', $theme_uri . '/assets/js/pages/randos.js', array( 'rando-nono-modal' ), rando_nono_asset_ver( '/assets/js/pages/randos.js' ), true );

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
        wp_enqueue_style( 'rando-nono-matos', $theme_uri . '/assets/css/components/matos.css', array( 'rando-nono-style' ), rando_nono_asset_ver( '/assets/css/components/matos.css' ) );
        wp_enqueue_script( 'rando-nono-matos', $theme_uri . '/assets/js/components/matos.js', array(), rando_nono_asset_ver( '/assets/js/components/matos.js' ), true );
    }

    // ── Favoris (localStorage) — boutons cœur présents sur les cartes, la fiche randonnée et la page /favoris/ ──
    wp_enqueue_script( 'rando-nono-favoris', $theme_uri . '/assets/js/components/favoris.js', array(), rando_nono_asset_ver( '/assets/js/components/favoris.js' ), true );

    wp_enqueue_script( 'rando-nono-main', $theme_uri . '/assets/js/main.js', $main_deps, rando_nono_asset_ver( '/assets/js/main.js' ), true );

    // ── Single randonnée (CSS + JS chargés uniquement sur la fiche) ──
    // Les articles (post) réutilisent le même CSS pour la navigation précédent/suivant,
    // mais n'ont pas besoin de la carte Leaflet.
    if ( is_singular( 'randonnee' ) || is_singular( 'post' ) ) {
        wp_enqueue_style( 'rando-nono-single', $theme_uri . '/assets/css/single-randonnee.css', array( 'rando-nono-style' ), rando_nono_asset_ver( '/assets/css/single-randonnee.css' ) );
    }
    if ( is_singular( 'randonnee' ) ) {
        wp_enqueue_script( 'rando-nono-single', $theme_uri . '/assets/js/pages/single-randonnee.js', array( 'leaflet', 'leaflet-gpx' ), rando_nono_asset_ver( '/assets/js/pages/single-randonnee.js' ), true );

        // ── Suivi GPS en direct (démarrer / suivre la randonnée depuis le téléphone) ──
        wp_enqueue_style( 'rando-nono-live-tracking', $theme_uri . '/assets/css/components/live-tracking.css', array( 'rando-nono-single' ), rando_nono_asset_ver( '/assets/css/components/live-tracking.css' ) );
        wp_enqueue_script( 'rando-nono-live-tracking', $theme_uri . '/assets/js/components/live-tracking.js', array( 'rando-nono-single' ), rando_nono_asset_ver( '/assets/js/components/live-tracking.js' ), true );
    }

    // ── Carte d'ensemble (page "Toutes les randonnées") ──
    if ( is_post_type_archive( 'randonnee' ) ) {
        wp_enqueue_script( 'rando-nono-archive-map', $theme_uri . '/assets/js/pages/archive-map.js', array( 'leaflet' ), rando_nono_asset_ver( '/assets/js/pages/archive-map.js' ), true );
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
   5bis. CRÉATION AUTOMATIQUE DES PAGES DU THÈME
   (mentions légales, contact, favoris) — une seule fabrique factorisée au
   lieu de trois blocs quasi identiques : la page est créée si elle manque au
   changement de thème, puis re-vérifiée une fois par jour via un transient
   (utile après une restauration de sauvegarde ayant perdu la page).
   ────────────────────────────────────────── */
function rando_nono_ensure_page_exists( $slug, $title ) {
    if ( get_page_by_path( $slug ) ) return;
    wp_insert_post( array(
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => '',
    ) );
}

/**
 * Exécute $callback au plus une fois par jour (via transient), en plus d'un
 * déclenchement systématique à l'activation du thème. Sert pour toute
 * vérification idempotente coûteuse (création de page, de table SQL, flush
 * des règles de réécriture...) qu'on ne veut pas relancer à chaque requête.
 */
function rando_nono_run_once_daily( $transient_key, callable $callback ) {
    add_action( 'after_switch_theme', $callback );
    add_action( 'init', function() use ( $transient_key, $callback ) {
        if ( get_transient( $transient_key ) ) return;
        $callback();
        set_transient( $transient_key, 1, DAY_IN_SECONDS );
    } );
}

function rando_nono_register_page_autocreate( $slug, $title ) {
    rando_nono_run_once_daily( 'rando_nono_page_checked_' . $slug, function() use ( $slug, $title ) {
        rando_nono_ensure_page_exists( $slug, $title );
    } );
}

rando_nono_register_page_autocreate( 'mentions-legales', 'Mentions légales' );
rando_nono_register_page_autocreate( 'contact', 'Contact' );
rando_nono_register_page_autocreate( 'favoris', 'Mes randos à faire' );

function rando_nono_handle_contact_form() {
    if ( ! is_page( 'contact' ) || ! isset( $_POST['rando_nono_contact_submit'] ) ) return;

    $redirect = get_permalink();

    if ( ! isset( $_POST['rando_nono_contact_nonce'] ) || ! wp_verify_nonce( $_POST['rando_nono_contact_nonce'], 'rando_nono_contact_form' ) ) {
        wp_safe_redirect( add_query_arg( 'contact', 'error', $redirect ) );
        exit;
    }

    // Piège à robots : ce champ caché doit rester vide.
    if ( ! empty( $_POST['site_web'] ) ) {
        wp_safe_redirect( add_query_arg( 'contact', 'ok', $redirect ) );
        exit;
    }

    $nom     = isset( $_POST['contact_nom'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_nom'] ) ) : '';
    $email   = isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '';
    $message = isset( $_POST['contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contact_message'] ) ) : '';

    if ( '' === $nom || ! is_email( $email ) || '' === $message ) {
        wp_safe_redirect( add_query_arg( 'contact', 'error', $redirect ) );
        exit;
    }

    $to      = 'arnaud.senegas34@gmail.com';
    $subject = 'Nouveau message depuis Les Randos de Nono';
    $body    = "Nom : {$nom}\nEmail : {$email}\n\nMessage :\n{$message}";
    $headers = array( 'Reply-To: ' . $nom . ' <' . $email . '>' );

    $sent = wp_mail( $to, $subject, $body, $headers );

    wp_safe_redirect( add_query_arg( 'contact', $sent ? 'ok' : 'error', $redirect ) );
    exit;
}
add_action( 'template_redirect', 'rando_nono_handle_contact_form' );

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

    echo "\n" . '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
    if ( $is_public ) {
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

/* ──────────────────────────────────────────
   9. NEWSLETTER — inscription + notification automatique des nouvelles randos
   ────────────────────────────────────────── */
function rando_nono_newsletter_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'rando_nono_newsletter';
}

function rando_nono_newsletter_create_table() {
    global $wpdb;
    $table           = rando_nono_newsletter_table_name();
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(190) NOT NULL,
        token VARCHAR(64) NOT NULL,
        date_inscription DATETIME NOT NULL,
        statut VARCHAR(20) NOT NULL DEFAULT 'actif',
        PRIMARY KEY (id),
        UNIQUE KEY email (email)
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
rando_nono_run_once_daily( 'rando_nono_newsletter_table_checked', 'rando_nono_newsletter_create_table' );

/**
 * Traitement du formulaire d'inscription (présent dans le pied de page, sur toutes les pages).
 */
function rando_nono_handle_newsletter_form() {
    if ( ! isset( $_POST['rando_nono_newsletter_submit'] ) ) return;

    // wp_get_referer() renvoie systématiquement false quand le formulaire est
    // posté sur l'URL même de la page (cas du formulaire du pied de page,
    // présent sur toutes les pages) : on utilise donc l'URL d'origine transmise
    // en champ caché, validée pour rester sur le site.
    $submitted_redirect = isset( $_POST['newsletter_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['newsletter_redirect'] ) ) : '';
    $redirect            = wp_validate_redirect( $submitted_redirect, home_url( '/' ) );

    if ( ! isset( $_POST['rando_nono_newsletter_nonce'] ) || ! wp_verify_nonce( $_POST['rando_nono_newsletter_nonce'], 'rando_nono_newsletter_form' ) ) {
        wp_safe_redirect( add_query_arg( 'newsletter', 'error', $redirect ) );
        exit;
    }

    // Piège à robots.
    if ( ! empty( $_POST['site_web_nl'] ) ) {
        wp_safe_redirect( add_query_arg( 'newsletter', 'ok', $redirect ) );
        exit;
    }

    // trim() est indispensable : un espace ajouté par le clavier mobile ou un
    // copier-coller fait échouer is_email() alors que l'adresse est valide.
    $email = isset( $_POST['newsletter_email'] ) ? sanitize_email( trim( wp_unslash( $_POST['newsletter_email'] ) ) ) : '';
    if ( ! is_email( $email ) ) {
        wp_safe_redirect( add_query_arg( 'newsletter', 'error', $redirect ) );
        exit;
    }

    global $wpdb;
    $table    = rando_nono_newsletter_table_name();
    $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE email = %s", $email ) );
    if ( ! $existing ) {
        $wpdb->insert( $table, array(
            'email'            => $email,
            'token'            => wp_generate_password( 32, false ),
            'date_inscription' => current_time( 'mysql' ),
            'statut'           => 'actif',
        ) );
    }

    wp_safe_redirect( add_query_arg( 'newsletter', 'ok', $redirect ) );
    exit;
}
add_action( 'template_redirect', 'rando_nono_handle_newsletter_form' );

/**
 * Désabonnement en un clic, depuis le lien présent dans chaque e-mail envoyé.
 */
function rando_nono_handle_newsletter_unsubscribe() {
    if ( ! isset( $_GET['newsletter_desabonner'] ) ) return;
    $token = sanitize_text_field( wp_unslash( $_GET['newsletter_desabonner'] ) );

    global $wpdb;
    $table = rando_nono_newsletter_table_name();
    $wpdb->delete( $table, array( 'token' => $token ) );

    wp_safe_redirect( add_query_arg( 'newsletter', 'desabonne', home_url( '/' ) ) );
    exit;
}
add_action( 'template_redirect', 'rando_nono_handle_newsletter_unsubscribe' );

/**
 * Dès qu'une randonnée passe en "publié" pour la première fois, programme
 * l'envoi (différé d'une minute, via WP-Cron) d'un e-mail à tous les abonnés.
 * Le flag _rando_nono_newsletter_sent évite les envois en double si l'article
 * est ensuite modifié et republié.
 */
function rando_nono_newsletter_notify_new_rando( $new_status, $old_status, $post ) {
    if ( 'randonnee' !== $post->post_type ) return;
    if ( 'publish' !== $new_status || 'publish' === $old_status ) return;
    if ( get_post_meta( $post->ID, '_rando_nono_newsletter_sent', true ) ) return;

    update_post_meta( $post->ID, '_rando_nono_newsletter_sent', current_time( 'mysql' ) );
    wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'rando_nono_send_newsletter_event', array( $post->ID ) );
}
add_action( 'transition_post_status', 'rando_nono_newsletter_notify_new_rando', 10, 3 );

function rando_nono_send_newsletter_event( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || 'publish' !== $post->post_status ) return;

    global $wpdb;
    $table = rando_nono_newsletter_table_name();
    $subs  = $wpdb->get_results( "SELECT email, token FROM $table WHERE statut = 'actif'" );
    if ( empty( $subs ) ) return;

    $lieu     = get_post_meta( $post_id, 'rando_lieu', true );
    $distance = get_post_meta( $post_id, 'rando_distance', true );
    $url      = get_permalink( $post_id );
    $titre    = get_the_title( $post_id );
    $subject  = 'Nouvelle randonnée : ' . $titre;

    foreach ( $subs as $sub ) {
        $unsub  = add_query_arg( 'newsletter_desabonner', $sub->token, home_url( '/' ) );
        $body   = "Une nouvelle randonnée vient d'être publiée sur Les Randos de Nono !\n\n";
        $body  .= $titre . ( $lieu ? ' — ' . $lieu : '' ) . ( $distance ? ' (' . $distance . ')' : '' ) . "\n\n";
        $body  .= "Découvrir le récit et la trace GPX :\n" . $url . "\n\n";
        $body  .= "---\nSe désabonner en un clic :\n" . $unsub . "\n";
        wp_mail( $sub->email, $subject, $body );
    }
}
add_action( 'rando_nono_send_newsletter_event', 'rando_nono_send_newsletter_event' );

/**
 * Page d'administration — liste des abonnés + export CSV.
 */
function rando_nono_newsletter_menu() {
    add_menu_page( 'Newsletter', 'Newsletter', 'manage_options', 'rando-nono-newsletter', 'rando_nono_newsletter_page', 'dashicons-email-alt', 26 );
}
add_action( 'admin_menu', 'rando_nono_newsletter_menu' );

function rando_nono_newsletter_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;

    global $wpdb;
    $table       = rando_nono_newsletter_table_name();
    $subscribers = $wpdb->get_results( "SELECT email, date_inscription FROM $table WHERE statut = 'actif' ORDER BY date_inscription DESC" );
    $count       = count( $subscribers );
    ?>
    <div class="wrap">
        <h1>Newsletter</h1>
        <p><strong><?php echo intval( $count ); ?></strong> abonné<?php echo $count > 1 ? 's' : ''; ?> actif<?php echo $count > 1 ? 's' : ''; ?>.</p>
        <p>Un e-mail est envoyé automatiquement à tous les abonnés (une minute après publication, via WP-Cron) à chaque nouvelle randonnée mise en ligne.</p>
        <?php if ( $count ) : ?>
        <p>
            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=rando_nono_newsletter_export' ), 'rando_nono_newsletter_export' ) ); ?>" class="button">Exporter en CSV</a>
        </p>
        <table class="widefat striped" style="max-width:600px">
            <thead><tr><th>E-mail</th><th>Inscrit le</th></tr></thead>
            <tbody>
            <?php foreach ( $subscribers as $sub ) : ?>
                <tr><td><?php echo esc_html( $sub->email ); ?></td><td><?php echo esc_html( mysql2date( 'd/m/Y', $sub->date_inscription ) ); ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <p><em>Aucun abonné pour le moment.</em></p>
        <?php endif; ?>
    </div>
    <?php
}

function rando_nono_newsletter_export() {
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Accès refusé' );
    check_admin_referer( 'rando_nono_newsletter_export' );

    global $wpdb;
    $table       = rando_nono_newsletter_table_name();
    $subscribers = $wpdb->get_results( "SELECT email, date_inscription FROM $table WHERE statut = 'actif' ORDER BY date_inscription ASC" );

    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=newsletter-randos-de-nono.csv' );

    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, array( 'email', 'date_inscription' ) );
    foreach ( $subscribers as $sub ) {
        fputcsv( $out, array( $sub->email, $sub->date_inscription ) );
    }
    fclose( $out );
    exit;
}
add_action( 'admin_post_rando_nono_newsletter_export', 'rando_nono_newsletter_export' );

/* ──────────────────────────────────────────
   10. AVIS & NOTES DES LECTEURS — modération avant publication
   ────────────────────────────────────────── */
function rando_nono_avis_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'rando_nono_avis';
}

function rando_nono_avis_create_table() {
    global $wpdb;
    $table           = rando_nono_avis_table_name();
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        rando_id BIGINT UNSIGNED NOT NULL,
        nom VARCHAR(100) NOT NULL,
        note TINYINT UNSIGNED NOT NULL,
        commentaire TEXT NOT NULL,
        date_avis DATETIME NOT NULL,
        statut VARCHAR(20) NOT NULL DEFAULT 'en_attente',
        PRIMARY KEY (id),
        KEY rando_id (rando_id),
        KEY statut (statut)
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}
rando_nono_run_once_daily( 'rando_nono_avis_table_checked', 'rando_nono_avis_create_table' );

/**
 * Moyenne et nombre d'avis publiés pour une randonnée (utilisé dans l'affichage et le schema.org).
 */
function rando_nono_get_avis_stats( $rando_id ) {
    global $wpdb;
    $table = rando_nono_avis_table_name();
    $row   = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) as total, AVG(note) as moyenne FROM $table WHERE rando_id = %d AND statut = 'publie'", $rando_id ) );
    return array(
        'total'   => $row ? (int) $row->total : 0,
        'moyenne' => ( $row && $row->total > 0 ) ? round( (float) $row->moyenne, 1 ) : 0,
    );
}

function rando_nono_get_avis_list( $rando_id ) {
    global $wpdb;
    $table = rando_nono_avis_table_name();
    return $wpdb->get_results( $wpdb->prepare( "SELECT nom, note, commentaire, date_avis FROM $table WHERE rando_id = %d AND statut = 'publie' ORDER BY date_avis DESC", $rando_id ) );
}

/**
 * Traitement du formulaire d'avis (sur la fiche de chaque randonnée). Les avis sont
 * enregistrés "en attente" et n'apparaissent qu'après validation manuelle (anti-spam).
 */
function rando_nono_handle_avis_form() {
    if ( ! isset( $_POST['rando_nono_avis_submit'] ) || ! is_singular( 'randonnee' ) ) return;

    $post_id  = get_queried_object_id();
    $redirect = get_permalink( $post_id ) . '#avis';

    if ( ! isset( $_POST['rando_nono_avis_nonce'] ) || ! wp_verify_nonce( $_POST['rando_nono_avis_nonce'], 'rando_nono_avis_form_' . $post_id ) ) {
        wp_safe_redirect( add_query_arg( 'avis', 'error', $redirect ) );
        exit;
    }

    // Piège à robots.
    if ( ! empty( $_POST['site_web_avis'] ) ) {
        wp_safe_redirect( add_query_arg( 'avis', 'merci', $redirect ) );
        exit;
    }

    $nom         = isset( $_POST['avis_nom'] ) ? sanitize_text_field( wp_unslash( $_POST['avis_nom'] ) ) : '';
    $note        = isset( $_POST['avis_note'] ) ? intval( $_POST['avis_note'] ) : 0;
    $commentaire = isset( $_POST['avis_commentaire'] ) ? sanitize_textarea_field( wp_unslash( $_POST['avis_commentaire'] ) ) : '';

    if ( '' === $nom || $note < 1 || $note > 5 || '' === $commentaire ) {
        wp_safe_redirect( add_query_arg( 'avis', 'error', $redirect ) );
        exit;
    }

    global $wpdb;
    $wpdb->insert( rando_nono_avis_table_name(), array(
        'rando_id'    => $post_id,
        'nom'         => $nom,
        'note'        => $note,
        'commentaire' => $commentaire,
        'date_avis'   => current_time( 'mysql' ),
        'statut'      => 'en_attente',
    ) );

    wp_safe_redirect( add_query_arg( 'avis', 'merci', $redirect ) );
    exit;
}
add_action( 'template_redirect', 'rando_nono_handle_avis_form' );

/**
 * Page d'administration — modération des avis (approuver / supprimer).
 */
function rando_nono_avis_pending_count() {
    global $wpdb;
    $table = rando_nono_avis_table_name();
    return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE statut = 'en_attente'" );
}

function rando_nono_avis_menu() {
    $pending    = rando_nono_avis_pending_count();
    $menu_label = 'Avis';
    if ( $pending > 0 ) {
        $menu_label .= ' <span class="awaiting-mod"><span class="pending-count">' . intval( $pending ) . '</span></span>';
    }
    add_menu_page( 'Avis lecteurs', $menu_label, 'moderate_comments', 'rando-nono-avis', 'rando_nono_avis_page', 'dashicons-star-half', 27 );
}
add_action( 'admin_menu', 'rando_nono_avis_menu' );

function rando_nono_avis_page() {
    if ( ! current_user_can( 'moderate_comments' ) ) return;

    global $wpdb;
    $table = rando_nono_avis_table_name();
    $avis  = $wpdb->get_results( "SELECT a.*, p.post_title FROM $table a LEFT JOIN {$wpdb->posts} p ON p.ID = a.rando_id ORDER BY (a.statut = 'en_attente') DESC, a.date_avis DESC" );
    ?>
    <div class="wrap">
        <h1>Avis lecteurs</h1>
        <p>Chaque avis déposé sur une fiche randonnée apparaît ici en attente de validation avant d'être visible publiquement.</p>
        <?php if ( empty( $avis ) ) : ?>
            <p><em>Aucun avis pour le moment.</em></p>
        <?php else : ?>
        <table class="widefat striped">
            <thead><tr><th>Randonnée</th><th>Nom</th><th>Note</th><th>Commentaire</th><th>Date</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ( $avis as $a ) : ?>
                <tr>
                    <td><?php echo esc_html( $a->post_title ? $a->post_title : '(rando supprimée)' ); ?></td>
                    <td><?php echo esc_html( $a->nom ); ?></td>
                    <td><?php echo esc_html( str_repeat( '★', (int) $a->note ) . str_repeat( '☆', 5 - (int) $a->note ) ); ?></td>
                    <td><?php echo esc_html( wp_trim_words( $a->commentaire, 20 ) ); ?></td>
                    <td><?php echo esc_html( mysql2date( 'd/m/Y', $a->date_avis ) ); ?></td>
                    <td><?php echo ( 'publie' === $a->statut ) ? '<span style="color:#2E5E3B">Publié</span>' : '<span style="color:#D97706">En attente</span>'; ?></td>
                    <td>
                        <?php if ( 'publie' !== $a->statut ) : ?>
                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=rando_nono_avis_approve&id=' . $a->id ), 'rando_nono_avis_action_' . $a->id ) ); ?>" class="button button-small">Approuver</a>
                        <?php endif; ?>
                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=rando_nono_avis_delete&id=' . $a->id ), 'rando_nono_avis_action_' . $a->id ) ); ?>" class="button button-small" onclick="return confirm('Supprimer cet avis ?');">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
}

function rando_nono_avis_approve() {
    if ( ! current_user_can( 'moderate_comments' ) ) wp_die( 'Accès refusé' );
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    check_admin_referer( 'rando_nono_avis_action_' . $id );
    global $wpdb;
    $wpdb->update( rando_nono_avis_table_name(), array( 'statut' => 'publie' ), array( 'id' => $id ) );
    wp_safe_redirect( admin_url( 'admin.php?page=rando-nono-avis' ) );
    exit;
}
add_action( 'admin_post_rando_nono_avis_approve', 'rando_nono_avis_approve' );

function rando_nono_avis_delete() {
    if ( ! current_user_can( 'moderate_comments' ) ) wp_die( 'Accès refusé' );
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    check_admin_referer( 'rando_nono_avis_action_' . $id );
    global $wpdb;
    $wpdb->delete( rando_nono_avis_table_name(), array( 'id' => $id ) );
    wp_safe_redirect( admin_url( 'admin.php?page=rando-nono-avis' ) );
    exit;
}
add_action( 'admin_post_rando_nono_avis_delete', 'rando_nono_avis_delete' );

/* ──────────────────────────────────────────
   11. PWA HORS-LIGNE — service worker servi à la racine du site
   Permet de consulter hors-ligne (sur le terrain) les randonnées déjà
   visitées : récit, carte, trace GPX. /sw.js et /hors-ligne/ sont de fausses
   routes WordPress (aucun fichier physique à cet endroit) pour que le
   service worker ait le scope '/', quel que soit le dossier du thème.
   ────────────────────────────────────────── */
function rando_nono_pwa_rewrite_rules() {
    add_rewrite_rule( '^sw\.js$', 'index.php?rando_nono_sw=1', 'top' );
    add_rewrite_rule( '^hors-ligne/?$', 'index.php?rando_nono_offline=1', 'top' );
}
add_action( 'init', 'rando_nono_pwa_rewrite_rules' );

add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'rando_nono_sw';
    $vars[] = 'rando_nono_offline';
    return $vars;
} );

// Les nouvelles règles de réécriture doivent être prises en compte une fois sur
// les sites où le thème était déjà actif avant leur ajout (after_switch_theme
// ne se déclenche que lors d'une activation). Priorité 20 : après l'ajout des
// règles ci-dessus (priorité par défaut 10), pour qu'elles soient incluses au flush.
function rando_nono_pwa_maybe_flush_rewrites() {
    if ( get_transient( 'rando_nono_pwa_rewrite_flushed' ) ) return;
    flush_rewrite_rules();
    set_transient( 'rando_nono_pwa_rewrite_flushed', 1, DAY_IN_SECONDS );
}
add_action( 'init', 'rando_nono_pwa_maybe_flush_rewrites', 20 );

function rando_nono_serve_sw() {
    if ( ! get_query_var( 'rando_nono_sw' ) ) return;

    $theme_uri = get_template_directory_uri();
    $version   = wp_get_theme()->get( 'Version' );
    $offline_url = home_url( '/hors-ligne/' );

    $app_shell = array(
        home_url( '/' ),
        $offline_url,
        get_post_type_archive_link( 'randonnee' ),
        $theme_uri . '/style.css',
        $theme_uri . '/assets/css/fonts.css',
        $theme_uri . '/assets/js/main.js',
        $theme_uri . '/assets/js/components/favoris.js',
    );

    $sw_js = file_get_contents( get_template_directory() . '/assets/js/sw.js' );
    $sw_js = str_replace(
        array( '__CACHE_VERSION__', '__OFFLINE_URL__', '__APP_SHELL_JSON__' ),
        array(
            esc_js( $version ),
            wp_json_encode( $offline_url ),
            wp_json_encode( array_values( $app_shell ) ),
        ),
        $sw_js
    );

    nocache_headers();
    header( 'Content-Type: application/javascript; charset=utf-8' );
    header( 'Service-Worker-Allowed: /' );
    echo $sw_js;
    exit;
}
add_action( 'template_redirect', 'rando_nono_serve_sw' );

function rando_nono_serve_offline_page() {
    if ( ! get_query_var( 'rando_nono_offline' ) ) return;
    nocache_headers();
    header( 'Content-Type: text/html; charset=utf-8' );
    ?>
<!DOCTYPE html>
<html lang="fr-FR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hors ligne — Les Randos de Nono</title>
<style>
  body { font-family: Georgia, serif; background:#FAF8F3; color:#1A2E1F; text-align:center; padding:4rem 1.5rem; }
  h1 { color:#2E5E3B; font-size:1.6rem; margin-bottom:1rem; }
  p { color:#5E5E52; max-width:32em; margin:0 auto 1.5rem; }
  a { display:inline-block; padding:0.7rem 1.3rem; background:#D97706; color:#fff; border-radius:5px; text-decoration:none; font-weight:600; }
</style>
</head>
<body>
  <h1>Pas de connexion</h1>
  <p>Cette page n'est pas disponible hors ligne. Reconnecte-toi pour la consulter, ou retourne sur une randonnée déjà visitée pendant que tu avais du réseau.</p>
  <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Retour à l'accueil</a>
</body>
</html>
    <?php
    exit;
}
add_action( 'template_redirect', 'rando_nono_serve_offline_page' );
