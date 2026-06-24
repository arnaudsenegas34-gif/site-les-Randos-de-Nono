<?php
/**
 * Les Randos de Nono — fonctions du thème
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once get_template_directory() . '/inc/icons.php';

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

    // ── Base : variables, reset, typographie ──
    wp_enqueue_style( 'rando-nono-base', $theme_uri . '/assets/css/base.css', array( 'rando-nono-fonts' ), $theme_version );

    // ── Layout : header, nav, hero, sections, footer ──
    wp_enqueue_style( 'rando-nono-layout', $theme_uri . '/assets/css/layout.css', array( 'rando-nono-base' ), $theme_version );

    // ── Composants ──
    wp_enqueue_style( 'rando-nono-buttons', $theme_uri . '/assets/css/components/buttons.css', array( 'rando-nono-base' ), $theme_version );
    wp_enqueue_style( 'rando-nono-cards', $theme_uri . '/assets/css/components/cards-rando.css', array( 'rando-nono-base' ), $theme_version );
    wp_enqueue_style( 'rando-nono-modal', $theme_uri . '/assets/css/components/modal-rando.css', array( 'rando-nono-base' ), $theme_version );
    wp_enqueue_style( 'rando-nono-matos', $theme_uri . '/assets/css/components/matos.css', array( 'rando-nono-base' ), $theme_version );

    // ── Pages ──
    wp_enqueue_style( 'rando-nono-home', $theme_uri . '/assets/css/pages/home.css', array( 'rando-nono-base' ), $theme_version );
    wp_enqueue_style( 'rando-nono-archive', $theme_uri . '/assets/css/pages/archive.css', array( 'rando-nono-base' ), $theme_version );
    wp_enqueue_style( 'rando-nono-404', $theme_uri . '/assets/css/pages/404.css', array( 'rando-nono-base' ), $theme_version );
    wp_enqueue_style( 'rando-nono-page', $theme_uri . '/assets/css/pages/page.css', array( 'rando-nono-base' ), $theme_version );

    // ── Style WordPress (requis, vide — sert de feuille parente) ──
    wp_enqueue_style( 'rando-nono-style', get_stylesheet_uri(), array( 'rando-nono-base' ), $theme_version );

    // ── Scripts — ordre strict ──
    wp_enqueue_script( 'rando-nono-modal-js', $theme_uri . '/assets/js/components/modal.js', array(), $theme_version, true );
    wp_enqueue_script( 'rando-nono-matos-js', $theme_uri . '/assets/js/components/matos.js', array(), $theme_version, true );
    wp_enqueue_script( 'rando-nono-randos-js', $theme_uri . '/assets/js/pages/randos.js', array( 'rando-nono-modal-js' ), $theme_version, true );
    wp_enqueue_script( 'rando-nono-main-js', $theme_uri . '/assets/js/main.js', array( 'rando-nono-modal-js', 'rando-nono-randos-js' ), $theme_version, true );

    // Données PHP → JS (URLs dynamiques)
    wp_localize_script( 'rando-nono-modal-js', 'randoNono', array(
        'placeholderUrl' => $theme_uri . '/assets/img/placeholder-rando.jpg',
        'themeUri'       => $theme_uri,
    ) );
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
            $fields = array( 'rando_lieu', 'rando_lat', 'rando_lon', 'rando_distance', 'rando_denivele', 'rando_denivele_neg', 'rando_duree', 'rando_date', 'rando_maps_url', 'rando_gpx_url', 'rando_photos', 'rando_sac', 'rando_conseils' );
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
    update_post_meta( $post_id, 'matos_essentiel', isset( $_POST['matos_essentiel'] ) ? '1' : '' );
}
add_action( 'save_post_matos', 'rando_nono_matos_save' );

/* ──────────────────────────────────────────
   6. NETTOYAGE — sécurité & performance de base
   ────────────────────────────────────────── */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'xmlrpc_enabled', '__return_false' );

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
        $lieu = get_post_meta( $post->ID, 'rando_lieu', true );
        $description = wp_trim_words( get_the_content(), 28, '…' );
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
    } elseif ( is_page() ) {
        $description = wp_trim_words( get_the_content(), 28, '…' );
        $title = get_the_title() . ' | ' . get_bloginfo( 'name' );
    }

    if ( ! $description ) {
        $description = get_bloginfo( 'description' );
    }

    echo "\n" . '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<meta property="og:type" content="' . ( is_singular( 'randonnee' ) ? 'article' : 'website' ) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
}
add_action( 'wp_head', 'rando_nono_seo_meta_tags', 1 );

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
        $title['title'] = get_the_title() . ( $lieu ? ' — ' . $lieu : '' );
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
