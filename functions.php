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

/**
 * Rendu du menu WordPress dans le tiroir mobile.
 *
 * Le tiroir affiche des liens à plat (pas de <ul>/<li>), avec les entrées de
 * second niveau simplement décalées sous leur parent — même présentation que
 * la liste écrite en dur qu'il utilisait avant. Ce walker produit ce balisage
 * depuis un menu configuré dans l'administration, pour que les deux
 * affichages ne puissent plus diverger.
 */
class Rando_Nono_Drawer_Walker extends Walker_Nav_Menu {
    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $output .= '<div class="nav-drawer-sub">';
    }
    public function end_lvl( &$output, $depth = 0, $args = null ) {
        $output .= '</div>';
    }
    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $courant = in_array( 'current-menu-item', (array) $item->classes, true )
                || in_array( 'current_page_item', (array) $item->classes, true );
        $output .= '<a href="' . esc_url( $item->url ) . '"'
                 . ( $courant ? ' class="is-current" aria-current="page"' : '' )
                 . '>' . esc_html( $item->title ) . '</a>';
    }
    public function end_el( &$output, $item, $depth = 0, $args = null ) {}
}

/* ──────────────────────────────────────────
   1. SETUP DU THÈME
   ────────────────────────────────────────── */
function rando_nono_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'menus' );
    register_nav_menus( array( 'primary' => __( 'Menu principal', 'rando-nono' ) ) );

    // Tailles d'images dédiées, pour servir des fichiers de la bonne
    // définition (au lieu du "large" générique) et permettre un vrai srcset
    // responsive sur les cartes, les héros et les galeries.
    add_image_size( 'rando-card', 640, 420, true );
    // Seconde taille au MÊME rapport que rando-card : sans elle, WordPress ne
    // peut construire aucun srcset pour les vignettes (il ne mélange pas les
    // proportions), et le même fichier de 640 px partait vers tous les écrans.
    // Rappel : add_image_size() ne vaut que pour l'avenir — il faut régénérer
    // les miniatures pour que les images déjà en ligne en profitent.
    add_image_size( 'rando-card-sm', 320, 210, true );
    add_image_size( 'rando-hero', 1600, 900, true );
    add_image_size( 'rando-gallery', 1000, 0, false );
}
add_action( 'after_setup_theme', 'rando_nono_setup' );

/**
 * Génère automatiquement les tailles d'images (thumbnail, medium, rando-card…)
 * au format WebP plutôt que JPEG/PNG pour tout nouvel envoi dans la médiathèque
 * (fichier original conservé tel quel). Gain moyen constaté : -25 à -35 % de
 * poids par image, sans réglage à faire depuis l'administration.
 *
 * Ne force WebP que si la bibliothèque d'images du serveur (GD/Imagick) sait
 * réellement l'encoder — sinon la génération des tailles échoue en silence
 * et certaines photos (souvent les plus récentes) n'apparaissent plus nulle
 * part sur le site. Fréquent sur les hébergements mutualisés bas de gamme
 * (GD compilé sans le support WebP).
 */
add_filter( 'image_editor_output_format', function( $formats ) {
    if ( wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
        $formats['image/jpeg'] = 'image/webp';
        $formats['image/png']  = 'image/webp';
    }
    return $formats;
} );

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

    // La carte interactive n'existe plus que sur la fiche complète d'une randonnée
    // et sur la carte d'ensemble de l'archive : inutile de charger Leaflet sur
    // l'accueil, les mentions légales, le 404, etc.
    $needs_leaflet = is_singular( 'randonnee' ) || is_post_type_archive( 'randonnee' );
    // Le profil altimétrique (Chart.js) n'est affiché que sur la fiche complète d'une randonnée.
    $needs_chart   = is_singular( 'randonnee' );
    // La grille de cartes (animations au scroll, filtre Matos, "voir plus") vit
    // sur l'accueil, l'archive, les résultats de recherche et les pages
    // "Article — Guide de randos" (elles réutilisent .rando-card, qui reste
    // invisible tant que ce script n'ajoute pas .is-visible au scroll).
    $needs_randos_js = is_front_page() || is_post_type_archive( 'randonnee' ) || is_search()
        || is_page_template( 'page-article-guide.php' );
    $main_deps     = array();

    // ── Leaflet (carte interactive) — hébergé localement dans assets/vendor/ ──
    // (plus de dépendance à unpkg/cdnjs : évite une requête réseau externe à chaque
    // visite et le risque d'un CDN tiers compromis servant du JS modifié)
    if ( $needs_leaflet ) {
        wp_enqueue_style( 'leaflet', $theme_uri . '/assets/vendor/leaflet/leaflet.css', array(), '1.9.4' );
        wp_enqueue_script( 'leaflet', $theme_uri . '/assets/vendor/leaflet/leaflet.js', array(), '1.9.4', true );
        wp_enqueue_script( 'leaflet-gpx', $theme_uri . '/assets/vendor/leaflet-gpx/gpx.min.js', array( 'leaflet' ), '1.7.0', true );
    }

    // ── Chart.js (profil altimétrique) — hébergé localement ──
    if ( $needs_chart ) {
        wp_enqueue_script( 'chartjs', $theme_uri . '/assets/vendor/chartjs/chart.umd.min.js', array(), '4.4.0', true );
    }

    if ( $needs_randos_js ) {
        wp_enqueue_script( 'rando-nono-randos', $theme_uri . '/assets/js/pages/randos.js', array(), rando_nono_asset_ver( '/assets/js/pages/randos.js' ), true );
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
        wp_enqueue_script( 'rando-nono-single', $theme_uri . '/assets/js/pages/single-randonnee.js', array( 'leaflet', 'leaflet-gpx', 'chartjs' ), rando_nono_asset_ver( '/assets/js/pages/single-randonnee.js' ), true );

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

/**
 * Repli quand une taille d'image du thème n'a pas été générée.
 *
 * `add_image_size()` ne vaut que pour les images téléversées APRÈS son ajout.
 * Pour les plus anciennes, la taille demandée n'existe pas et WordPress
 * retombe sur le FICHIER D'ORIGINE. Concrètement, sur la page d'accueil du
 * 01/09/2026 : des PNG de 1 172 px servis pour des vignettes affichées à
 * 250 px, et la grille « Matos » qui en réclamait 25 d'un coup — assez pour
 * qu'un hébergement mutualisé refuse une partie des requêtes. Symptôme :
 * des images cassées qui se chargent parfaitement une par une au clic droit.
 *
 * On retombe donc sur la plus petite taille intermédiaire réellement
 * générée qui reste assez grande, au lieu de l'original.
 *
 * Ce filet ne remplace PAS une régénération des miniatures (qui, elle,
 * produit la vraie taille recadrée) : il évite seulement qu'une image
 * ancienne coûte plusieurs fois son poids utile. En cas de doute, la
 * fonction rend la valeur de WordPress inchangée.
 */
function rando_nono_repli_taille_image( $image, $attachment_id, $size, $icon ) {
    // Site public UNIQUEMENT. L'administration doit voir les vraies tailles :
    // l'éditeur d'image, la médiathèque et le sélecteur de vignette
    // travaillent sur ces valeurs, il serait faux de leur en substituer
    // d'autres. Cela met aussi ce filtre hors de cause pour tout problème
    // survenant dans l'admin.
    if ( is_admin() ) {
        return $image;
    }

    // rando-hero est volontairement exclu : y retomber sur « large » (1024 px)
    // dégraderait visiblement la grande image d'en-tête.
    $largeurs_cibles = array(
        'rando-card'    => 640,
        'rando-gallery' => 1000,
    );

    if ( $icon || ! is_string( $size ) || ! isset( $largeurs_cibles[ $size ] ) ) {
        return $image;
    }

    // La taille demandée a bien été générée : on ne touche à rien.
    if ( image_get_intermediate_size( $attachment_id, $size ) ) {
        return $image;
    }

    $mini = $largeurs_cibles[ $size ] * 0.6;

    foreach ( array( 'medium', 'medium_large', 'large' ) as $repli ) {
        $inter = image_get_intermediate_size( $attachment_id, $repli );
        if ( ! empty( $inter['url'] ) && ! empty( $inter['width'] ) && $inter['width'] >= $mini ) {
            return array( $inter['url'], (int) $inter['width'], (int) $inter['height'], true );
        }
    }

    // Aucune taille intermédiaire assez grande : on garde le choix de WordPress.
    return $image;
}
add_filter( 'wp_get_attachment_image_src', 'rando_nono_repli_taille_image', 10, 4 );

/**
 * Allègement du site public — deux ressources chargées pour rien.
 *
 * Constaté sur la page d'accueil en ligne (PageSpeed, 01/09/2026) :
 * 2 080 ms de requêtes bloquant le rendu sur mobile, dont une bonne part
 * imputable à ces deux-là.
 */
function rando_nono_alleger_front() {
    if ( is_admin() ) {
        return;
    }
    // Dashicons est la police d'icônes de l'ADMINISTRATION. Un plugin la
    // charge aussi sur le site public, où elle bloque le rendu pour ~45 Ko
    // dont aucune page publique ne se sert. Les visiteurs connectés la
    // gardent : la barre d'admin en a besoin.
    if ( ! is_user_logged_in() ) {
        wp_dequeue_style( 'dashicons' );
    }
}
add_action( 'wp_enqueue_scripts', 'rando_nono_alleger_front', 100 );

/**
 * CSS des blocs Gutenberg — chargé à la demande plutôt qu'en bloc.
 *
 * wp-includes/css/dist/block-library/style.css pesait 122 Ko sur CHAQUE page,
 * soit 36 % du poids d'une page simple, en ressource bloquant le rendu — pour
 * un thème classique qui n'utilise presque aucun bloc. Ce filtre fait charger
 * à WordPress le style des seuls blocs réellement présents dans le contenu :
 * aucune mise en forme n'est perdue, y compris sur une page rédigée avec des
 * colonnes ou une galerie.
 */
add_filter( 'should_load_separate_core_block_assets', '__return_true' );

/**
 * Émojis WordPress — script inutile ici, et bruyant.
 *
 * Le thème n'affiche pas d'émoji dans son contenu, mais le chargeur de
 * WordPress tente de créer un worker depuis une URL `blob:` que la CSP du
 * site refuse : chaque visiteur récolte une erreur dans sa console (relevée
 * par Lighthouse dans « Bonnes pratiques »). On retire le tout.
 */
function rando_nono_desactiver_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', 'rando_nono_desactiver_emojis' );

/* ──────────────────────────────────────────
   3. CUSTOM POST TYPE "RANDONNÉE"
   ────────────────────────────────────────── */
/* ──────────────────────────────────────────
   ÉCHELLE DE DIFFICULTÉ — un ordre, enfin

   Les noms sont des formules maison (« Simpliste », « Ça se corse », « Tu vas
   t'en souvenir ») : c'est l'identité du site et il faut les garder. Mais rien
   n'indiquait comment les ordonner. Le sélecteur de l'archive les listait par
   ordre alphabétique — get_terms() sans orderby — avec « Ça » rejeté en fin de
   liste par sa cédille : « Balade tranquille, Simpliste, Tu vas t'en souvenir,
   Ça se corse ». Un visiteur qui arrive ne peut pas deviner lequel est le plus
   facile, donc le filtre le plus utile du site demande de connaître le site.

   Chaque terme porte maintenant un rang (métadonnée de terme, champ « Niveau »
   dans l'administration). Le rang sert à trier les listes ET à afficher un
   repère « 2/4 » à côté du nom : le nom garde sa personnalité, le chiffre
   donne l'échelle.
   ────────────────────────────────────────── */

/**
 * Rang d'un terme de difficulté (1 = le plus facile), 0 s'il n'est pas défini.
 */
function rando_nono_difficulte_rang( $terme ) {
    if ( is_numeric( $terme ) ) $terme = get_term( (int) $terme, 'difficulte' );
    if ( ! $terme || is_wp_error( $terme ) ) return 0;
    return (int) get_term_meta( $terme->term_id, 'rando_difficulte_rang', true );
}

/**
 * Nombre de niveaux de l'échelle — le dénominateur du repère « 2/4 ».
 */
function rando_nono_difficulte_total() {
    $termes = get_terms( array( 'taxonomy' => 'difficulte', 'hide_empty' => false ) );
    return ( $termes && ! is_wp_error( $termes ) ) ? count( $termes ) : 0;
}

/**
 * Termes de difficulté triés par rang croissant, les non classés à la fin.
 */
function rando_nono_difficultes_ordonnees( $hide_empty = true ) {
    $termes = get_terms( array( 'taxonomy' => 'difficulte', 'hide_empty' => $hide_empty ) );
    if ( ! $termes || is_wp_error( $termes ) ) return array();
    usort( $termes, function( $a, $b ) {
        $ra = rando_nono_difficulte_rang( $a );
        $rb = rando_nono_difficulte_rang( $b );
        // Un terme sans rang passe après ceux qui en ont un.
        if ( 0 === $ra ) $ra = PHP_INT_MAX;
        if ( 0 === $rb ) $rb = PHP_INT_MAX;
        if ( $ra === $rb ) return strnatcasecmp( $a->name, $b->name );
        return $ra <=> $rb;
    } );
    return $termes;
}

/**
 * Libellé du repère d'échelle : « 2/4 », ou chaîne vide si non classé.
 */
function rando_nono_difficulte_repere( $terme ) {
    $rang  = rando_nono_difficulte_rang( $terme );
    $total = rando_nono_difficulte_total();
    return ( $rang && $total ) ? $rang . '/' . $total : '';
}

/* Champ « Niveau » dans l'administration des termes de difficulté. */
add_action( 'difficulte_add_form_fields', function() {
    ?>
    <div class="form-field">
      <label for="rando_difficulte_rang">Niveau sur l'échelle</label>
      <input type="number" name="rando_difficulte_rang" id="rando_difficulte_rang" min="1" max="20" step="1" value="">
      <p>1 = le plus facile. Ce nombre sert à ordonner le filtre de l'archive et à afficher un repère « 2/4 » à côté du nom — le nom lui-même ne change pas.</p>
    </div>
    <?php
} );

add_action( 'difficulte_edit_form_fields', function( $terme ) {
    $rang = rando_nono_difficulte_rang( $terme );
    ?>
    <tr class="form-field">
      <th scope="row"><label for="rando_difficulte_rang">Niveau sur l'échelle</label></th>
      <td>
        <input type="number" name="rando_difficulte_rang" id="rando_difficulte_rang" min="1" max="20" step="1" value="<?php echo $rang ? esc_attr( $rang ) : ''; ?>">
        <p class="description">1 = le plus facile. Sert à ordonner le filtre de l'archive et à afficher le repère « <?php echo esc_html( rando_nono_difficulte_repere( $terme ) ?: '2/4' ); ?> » à côté du nom.</p>
      </td>
    </tr>
    <?php
} );

function rando_nono_difficulte_save_rang( $term_id ) {
    if ( ! current_user_can( 'manage_categories' ) ) return;
    if ( ! isset( $_POST['rando_difficulte_rang'] ) ) return;
    $rang = (int) $_POST['rando_difficulte_rang'];
    if ( $rang > 0 ) {
        update_term_meta( $term_id, 'rando_difficulte_rang', $rang );
    } else {
        delete_term_meta( $term_id, 'rando_difficulte_rang' );
    }
}
add_action( 'created_difficulte', 'rando_nono_difficulte_save_rang' );
add_action( 'edited_difficulte', 'rando_nono_difficulte_save_rang' );

/* Colonne « Niveau » dans la liste des difficultés, pour voir l'ordre d'un
   coup d'œil et repérer les termes non classés. */
add_filter( 'manage_edit-difficulte_columns', function( $colonnes ) {
    $colonnes['rando_rang'] = 'Niveau';
    return $colonnes;
} );
add_filter( 'manage_difficulte_custom_column', function( $contenu, $colonne, $term_id ) {
    if ( 'rando_rang' !== $colonne ) return $contenu;
    $repere = rando_nono_difficulte_repere( get_term( $term_id, 'difficulte' ) );
    return $repere ? esc_html( $repere ) : '<span style="color:#A85504">non classé</span>';
}, 10, 3 );

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
        'rando_lieu', 'rando_lieu_court', 'rando_lat', 'rando_lon', 'rando_distance',
        'rando_denivele', 'rando_denivele_neg', 'rando_duree', 'rando_date',
        'rando_meilleure_saison', 'rando_maps_url', 'rando_gpx_url', 'rando_conseils',
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

/**
 * Autorise l'upload de fichiers .gpx dans la médiathèque : WordPress ne les
 * reconnaît pas par défaut (absents de la liste blanche des extensions
 * autorisées), ce qui fait échouer l'ajout d'une trace en tant que média
 * malgré le champ "URL du fichier GPX (upload média)" ci-dessus.
 */
add_filter( 'upload_mimes', function( $mimes ) {
    $mimes['gpx'] = 'application/gpx+xml';
    return $mimes;
} );

/**
 * Sans ce filtre, la détection réelle du type de fichier (fileinfo) ne
 * reconnaît pas un GPX — un simple XML — comme correspondant à l'extension
 * .gpx, et wp_handle_upload rejette quand même le fichier malgré le filtre
 * upload_mimes ci-dessus.
 */
add_filter( 'wp_check_filetype_and_ext', function( $data, $file, $filename, $mimes ) {
    if ( ! $data['ext'] && preg_match( '/\.gpx$/i', $filename ) ) {
        $data['ext']  = 'gpx';
        $data['type'] = 'application/gpx+xml';
    }
    return $data;
}, 10, 4 );

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
        'rando_lieu'         => 'Lieu complet (ex: Mourèze, Hérault)',
        'rando_lieu_court'   => 'Lieu court — affiché sur les cartes (facultatif)',
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

    echo '<p id="rando-nono-lieu-apercu" style="margin-top:0.75rem;padding:0.6rem 0.8rem;background:#F4F2E8;border-left:3px solid #D97706;font-size:13px;color:#3A3A32"></p>';
    echo '<p id="rando-nono-effort-suggestion" style="margin-top:0.75rem;padding:0.6rem 0.8rem;background:#F4F2E8;border-left:3px solid #2E5E3B;font-size:13px;color:#3A3A32"></p>';
    ?>
    <script>
    ( function () {
        // Aperçu du libellé qui apparaîtra sur les cartes. Reproduit la règle
        // de rando_nono_lieu_court() en PHP : le champ court gagne, sinon la
        // portion avant la première virgule, coupée sur un espace si besoin.
        // Purement indicatif — c'est toujours le PHP qui fait foi à l'affichage.
        var lieuEl      = document.getElementById( 'rando_lieu' );
        var lieuCourtEl = document.getElementById( 'rando_lieu_court' );
        var apercu      = document.getElementById( 'rando-nono-lieu-apercu' );

        if ( lieuEl && lieuCourtEl && apercu ) {
            var MAX = 34;

            function lieuCourt() {
                var court = ( lieuCourtEl.value || '' ).trim();
                if ( court ) return { texte: court, auto: false };

                var lieu = ( lieuEl.value || '' ).trim();
                if ( ! lieu ) return { texte: '', auto: true };

                court = lieu.split( ',' )[0].trim();
                if ( court.length <= MAX ) return { texte: court, auto: true };

                var coupe  = court.slice( 0, MAX );
                var espace = coupe.lastIndexOf( ' ' );
                if ( espace > 8 ) coupe = coupe.slice( 0, espace );
                return { texte: coupe.replace( /[\s,;:-]+$/, '' ) + '…', auto: true, tronque: true };
            }

            function majApercu() {
                var r = lieuCourt();
                if ( ! r.texte ) { apercu.textContent = ''; return; }
                apercu.textContent = 'Sur les cartes, ce lieu s\'affichera : « ' + r.texte + ' »'
                    + ( r.tronque
                        ? ' — coupé faute de place. Renseigne « Lieu court » pour choisir toi-même (ex : « Val-d\'Isère »).'
                        : ( r.auto ? ' (déduit automatiquement du lieu complet).' : ' (libellé choisi à la main).' ) );
            }

            lieuEl.addEventListener( 'input', majApercu );
            lieuCourtEl.addEventListener( 'input', majApercu );
            majApercu();
        }
    } )();

    ( function () {
        // Suggestion indicative (distance + dénivelé/100, façon indice d'effort) —
        // n'écrit jamais la case à cocher "Difficulté" toute seule, c'est toujours
        // Nono qui tranche : le terrain (technicité, exposition...) compte aussi,
        // pas seulement les chiffres.
        var distanceEl = document.getElementById( 'rando_distance' );
        var deniveleEl = document.getElementById( 'rando_denivele' );
        var out        = document.getElementById( 'rando-nono-effort-suggestion' );
        if ( ! distanceEl || ! deniveleEl || ! out ) return;

        function extractNumber( text ) {
            var m = ( text || '' ).match( /-?[\d]+(?:[.,]\d+)?/ );
            return m ? parseFloat( m[0].replace( ',', '.' ) ) : 0;
        }

        function update() {
            var distance = extractNumber( distanceEl.value );
            var denivele = Math.abs( extractNumber( deniveleEl.value ) );
            if ( ! distance && ! denivele ) { out.textContent = ''; return; }
            var score  = distance + denivele / 100;
            var niveau = score < 8 ? 'Facile' : ( score < 16 ? 'Moyen' : 'Difficile' );
            out.textContent = 'Suggestion d\'après distance + dénivelé (indicatif, score ' + score.toFixed( 1 ) + ') : ' + niveau + ' — coche la difficulté qui te semble juste dans le bloc à droite.';
        }

        distanceEl.addEventListener( 'input', update );
        deniveleEl.addEventListener( 'input', update );
        update();
    } )();
    </script>
    <?php
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
            $fields = array( 'rando_lieu', 'rando_lieu_court', 'rando_lat', 'rando_lon', 'rando_distance', 'rando_denivele', 'rando_denivele_neg', 'rando_duree', 'rando_date', 'rando_meilleure_saison', 'rando_maps_url', 'rando_gpx_url', 'rando_photos', 'rando_sac', 'rando_conseils' );
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

/**
 * Colonne « Lieu (cartes) » dans Randonnées → Toutes les randonnées.
 *
 * Sans elle, rien ne signale qu'un lieu est trop long pour les cartes : il
 * faut ouvrir chaque randonnée une par une. La colonne affiche le libellé
 * réellement rendu et marque en orange ceux qui ont dû être coupés — la
 * liste des fiches à reprendre se lit alors d'un coup d'œil.
 */
add_filter( 'manage_randonnee_posts_columns', function( $columns ) {
    $nouvelles = array();
    foreach ( $columns as $cle => $libelle ) {
        $nouvelles[ $cle ] = $libelle;
        if ( 'title' === $cle ) {
            $nouvelles['rando_lieu_court'] = 'Lieu (cartes)';
        }
    }
    return $nouvelles;
} );

add_action( 'manage_randonnee_posts_custom_column', function( $colonne, $post_id ) {
    if ( 'rando_lieu_court' !== $colonne ) {
        return;
    }

    $court   = rando_nono_lieu_court( $post_id );
    $manuel  = '' !== trim( (string) get_post_meta( $post_id, 'rando_lieu_court', true ) );
    $tronque = ! $manuel && '' !== $court && '…' === mb_substr( $court, -1 );

    if ( '' === $court ) {
        echo '<span style="color:#8C8F94">—</span>';
        return;
    }

    echo esc_html( $court );
    if ( $tronque ) {
        echo '<br><span style="color:#A85504;font-size:11px">coupé — à raccourcir à la main</span>';
    } elseif ( ! $manuel ) {
        echo '<br><span style="color:#8C8F94;font-size:11px">déduit du lieu complet</span>';
    }
}, 10, 2 );

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

    // Quatre situations distinctes menaient toutes à `contact=error`, avec le
    // même message « vérifiez les champs » — y compris quand les champs étaient
    // corrects et que c'était l'envoi qui avait échoué. Chacune a désormais son
    // code, et page-contact.php affiche le message qui correspond.
    //
    // Le cas du nonce périmé n'est pas théorique : W3 Total Cache sert
    // /contact/ depuis son cache disque avec le nonce figé dans le HTML, et un
    // nonce WordPress expire au bout de 12 à 24 h. Passé ce délai, tout
    // visiteur anonyme échouait, et le message l'envoyait corriger des champs
    // qui n'avaient rien de faux.
    if ( ! isset( $_POST['rando_nono_contact_nonce'] ) || ! wp_verify_nonce( $_POST['rando_nono_contact_nonce'], 'rando_nono_contact_form' ) ) {
        wp_safe_redirect( add_query_arg( 'contact', 'expire', $redirect ) );
        exit;
    }

    if ( ! rando_nono_throttle_submission( 'contact' ) ) {
        wp_safe_redirect( add_query_arg( 'contact', 'attente', $redirect ) );
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
        wp_safe_redirect( add_query_arg( 'contact', 'champs', $redirect ) );
        exit;
    }

    // L'adresse de destination suit le réglage WordPress (Réglages > Général),
    // au lieu d'être écrite en dur dans le thème : la changer ne demande plus
    // de livrer une nouvelle version.
    $to      = get_option( 'admin_email' );
    $subject = 'Nouveau message depuis Les Randos de Nono';
    $body    = "Nom : {$nom}\nEmail : {$email}\n\nMessage :\n{$message}";
    $headers = array( 'Reply-To: ' . $nom . ' <' . $email . '>' );

    $sent = wp_mail( $to, $subject, $body, $headers );

    wp_safe_redirect( add_query_arg( 'contact', $sent ? 'ok' : 'envoi', $redirect ) );
    exit;
}
add_action( 'template_redirect', 'rando_nono_handle_contact_form' );

/**
 * Échecs d'envoi de courriel — journalisés plutôt que silencieux.
 *
 * Rien ne remontait une panne d'envoi : ni au visiteur (qui lisait « vérifiez
 * les champs »), ni à l'administrateur. Sur un mutualisé où l'envoi PHP est
 * souvent restreint, c'est la panne la plus probable — et la plus coûteuse,
 * puisqu'elle fait perdre des messages sans laisser de trace.
 *
 * La dernière erreur est conservée en option (consultable dans Réglages >
 * Vérification SEO, ou via get_option) et écrite dans le journal PHP quand
 * WP_DEBUG_LOG est actif.
 */
add_action( 'wp_mail_failed', function( $erreur ) {
    if ( ! is_wp_error( $erreur ) ) return;
    $message = $erreur->get_error_message();
    update_option( 'rando_nono_dernier_echec_mail', array(
        'date'    => current_time( 'mysql' ),
        'message' => $message,
    ), false );
    if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
        error_log( '[rando-nono] Échec wp_mail : ' . $message );
    }
} );

/* ──────────────────────────────────────────
   6. NETTOYAGE — sécurité & performance de base
   ────────────────────────────────────────── */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'xmlrpc_enabled', '__return_false' );

/**
 * En-têtes de sécurité HTTP de base, posés au niveau PHP (fonctionnent même si
 * mod_headers n'est pas disponible côté serveur — les règles équivalentes du
 * .htaccess servent de première ligne, celles-ci de filet de sécurité).
 */
function rando_nono_security_headers() {
    // JAMAIS dans l'administration. WordPress y dessine la médiathèque, la
    // liste des thèmes et l'éditeur avec underscore.js, qui compile ses
    // gabarits via new Function() — ce que `script-src` sans 'unsafe-eval'
    // interdit. Résultat : ces écrans restaient blancs, sans autre indice
    // qu'une EvalError dans la console. La CSP protège les pages publiques ;
    // l'admin est derrière authentification et n'a rien à y gagner.
    if ( is_admin() ) {
        return;
    }

    if ( headers_sent() ) return;

    // PHP annonce sa version dans X-Powered-By. Le .htaccess la retire via
    // `Header unset`, mais cela suppose mod_headers actif — or ce bloc existe
    // justement pour le cas contraire. header_remove() agit côté PHP, donc
    // quel que soit le serveur.
    header_remove( 'X-Powered-By' );

    header( 'X-Content-Type-Options: nosniff' );
    header( 'X-Frame-Options: SAMEORIGIN' );
    header( 'Referrer-Policy: strict-origin-when-cross-origin' );
    header( 'Permissions-Policy: geolocation=(self), camera=(), microphone=(), payment=(), browsing-topics=()' );
    header( 'Cross-Origin-Opener-Policy: same-origin' );
    // HSTS : présent dans le .htaccess, il manquait ici. Conditionné à une
    // connexion chiffrée — un HSTS servi sur HTTP est ignoré par les
    // navigateurs, et poserait problème sur un environnement de test local.
    if ( is_ssl() ) {
        header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
    }
    // Doit rester identique à la CSP posée par mod_headers dans .htaccess
    // (celle-ci ne sert que de filet de sécurité si mod_headers est absent :
    // avec "Header set", la valeur du .htaccess écrase celle-ci côté navigateur).
    // connect-src inclut api.sports-tracker.com : c'est le domaine réel de
    // l'export GPX de l'app Suunto utilisé dans le champ "URL du fichier GPX"
    // des randonnées (ex: .../apiserver/v2/routes/export/...?format=gpx-route),
    // appelé par Leaflet-GPX (carte + profil altimétrique) et par la fiche imprimable.
    header( "Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.googletagmanager.com https://connect.facebook.net; style-src 'self' 'unsafe-inline'; img-src 'self' data: https://*.tile.openstreetmap.org https://www.facebook.com; font-src 'self' data:; connect-src 'self' https://api.open-meteo.com https://api.sports-tracker.com https://*.google-analytics.com https://*.analytics.google.com https://www.facebook.com; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'; upgrade-insecure-requests" );
}
add_action( 'send_headers', 'rando_nono_security_headers' );

/**
 * Durcissement WordPress standard, sans dépendre de wp-config.php (souvent
 * hors dépôt) : désactive l'éditeur de fichiers thème/plugin dans l'admin
 * (réduit ce qu'un compte admin compromis peut modifier), masque quel
 * identifiant est en cause dans un échec de connexion (évite l'énumération
 * de comptes), et retire l'endpoint REST /wp/v2/users pour les visiteurs
 * non connectés (évite d'exposer le nom d'utilisateur de l'admin).
 */
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
    define( 'DISALLOW_FILE_EDIT', true );
}

add_filter( 'login_errors', function() {
    return 'Identifiants incorrects.';
} );

add_filter( 'rest_endpoints', function( $endpoints ) {
    if ( is_user_logged_in() ) return $endpoints;
    unset( $endpoints['/wp/v2/users'] );
    unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
    // Les commentaires WordPress ne sont ni affichés ni utilisés (le thème a
    // son propre système d'avis) : on ferme aussi leur porte d'entrée REST.
    unset( $endpoints['/wp/v2/comments'] );
    unset( $endpoints['/wp/v2/comments/(?P<id>[\d]+)'] );
    return $endpoints;
} );

/**
 * Énumération d'utilisateur — la porte laissée ouverte par les deux mesures
 * ci-dessus.
 *
 * Retirer /wp/v2/users et masquer le message d'erreur de connexion empêche de
 * découvrir l'identifiant de l'administrateur… sauf par deux autres chemins que
 * WordPress ouvre par défaut :
 *   - /?author=1 répond 301 vers /author/<login>/ : le login est dans l'URL ;
 *   - wp-sitemap-users-1.xml publie cette même URL, et l'annonce à Google.
 * Connaître l'identifiant transforme une attaque par force brute en une
 * recherche de mot de passe seul.
 *
 * Le site n'a qu'un auteur et n'affiche jamais de page d'auteur : les deux
 * peuvent disparaître sans rien perdre.
 */
function rando_nono_bloquer_enumeration_auteur() {
    if ( is_admin() || is_user_logged_in() ) return;
    if ( is_author() || isset( $_GET['author'] ) ) {
        wp_safe_redirect( home_url( '/' ), 301 );
        exit;
    }
}
add_action( 'template_redirect', 'rando_nono_bloquer_enumeration_auteur', 0 );

add_filter( 'wp_sitemaps_add_provider', function( $provider, $name ) {
    return 'users' === $name ? false : $provider;
}, 10, 2 );

/**
 * Commentaires WordPress — fermés partout.
 *
 * Le thème ne fournit pas de comments.php et n'appelle jamais
 * comments_template() : aucun commentaire n'a jamais été affiché. Mais
 * default_comment_status valait 'open', donc /wp-comments-post.php et l'API
 * REST les acceptaient quand même : un dépôt de spam invisible, qui gonfle
 * wp_comments sans que personne ne le voie. Les avis de randonnée
 * (rando_nono_avis_*) remplissent déjà ce rôle, avec modération.
 */
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_filter( 'feed_links_show_comments_feed', '__return_false' );

/**
 * Tentatives de connexion — WordPress n'en limite aucune.
 *
 * Cinq essais consécutifs sur wp-login.php passent sans blocage ni délai. Ce
 * verrou minimal compte les échecs par IP et refuse l'accès au formulaire
 * au-delà du seuil, le temps de la fenêtre. Il ne remplace pas une extension
 * dédiée (pas de liste noire durable, pas d'alerte e-mail) mais ferme la porte
 * ouverte par le couple « identifiant connu + essais illimités ».
 *
 * Les transients servent de compteur : ils expirent seuls, sans table ni cron.
 */
function rando_nono_login_cle_tentatives() {
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
    return $ip ? 'rando_nono_login_' . md5( $ip ) : '';
}

function rando_nono_login_verrou() {
    $cle = rando_nono_login_cle_tentatives();
    if ( ! $cle ) return;
    $tentatives = (int) get_transient( $cle );
    if ( $tentatives < 5 ) return;
    wp_die(
        '<h1>Trop de tentatives</h1><p>Trop d\'essais de connexion depuis cette adresse. Réessaie dans quinze minutes.</p>',
        'Connexion bloquée',
        array( 'response' => 429 )
    );
}
add_action( 'login_init', 'rando_nono_login_verrou' );

add_action( 'wp_login_failed', function() {
    $cle = rando_nono_login_cle_tentatives();
    if ( ! $cle ) return;
    $tentatives = (int) get_transient( $cle );
    set_transient( $cle, $tentatives + 1, 15 * MINUTE_IN_SECONDS );
} );

add_action( 'wp_login', function() {
    $cle = rando_nono_login_cle_tentatives();
    if ( $cle ) delete_transient( $cle );
} );

/**
 * Version de WordPress — retirée des URL de ressources.
 *
 * remove_action('wp_head','wp_generator') efface la balise <meta generator>,
 * mais les scripts et styles du cœur gardent ?ver=6.8.2 : la version reste
 * lisible dans le code source. Ce n'est pas une protection en soi — seules les
 * mises à jour le sont — mais cela évite de figurer dans les listes de cibles
 * constituées par scan automatique.
 */
add_filter( 'script_loader_src', 'rando_nono_retirer_version_core', 15 );
add_filter( 'style_loader_src', 'rando_nono_retirer_version_core', 15 );
function rando_nono_retirer_version_core( $src ) {
    if ( ! $src || false === strpos( $src, 'ver=' ) ) return $src;
    // Uniquement pour les ressources du cœur : celles du thème utilisent la
    // date de modification du fichier comme version (rando_nono_asset_ver),
    // indispensable pour invalider le cache après une mise à jour.
    if ( false === strpos( $src, '/wp-includes/' ) && false === strpos( $src, '/wp-admin/' ) ) {
        return $src;
    }
    return remove_query_arg( 'ver', $src );
}

/**
 * Anti-flood minimal sur les formulaires publics (contact, newsletter, avis) :
 * refuse une nouvelle soumission du même formulaire depuis la même IP avant
 * $seconds, en complément du nonce et du champ piège à robots déjà en place
 * sur chacun. Ralentit un script de spam basique sans CAPTCHA ni dépendance
 * externe ; best-effort (REMOTE_ADDR peut être partagé derrière un proxy),
 * mais sans coût pour un visiteur normal qui ne soumet pas deux fois de suite.
 */
function rando_nono_throttle_submission( $form_key, $seconds = 20 ) {
    $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
    if ( ! $ip ) return true;
    $key = 'rando_nono_throttle_' . $form_key . '_' . md5( $ip );
    if ( get_transient( $key ) ) return false;
    set_transient( $key, 1, $seconds );
    return true;
}

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
    // Les combinaisons de filtres de l'archive : des milliers d'URL pour un
    // seul contenu. Le noindex (voir rando_nono_robots) les sort de l'index ;
    // ces lignes évitent en plus de les faire explorer.
    $output .= "Disallow: /*?recherche=\n";
    $output .= "Disallow: /*&recherche=\n";
    $output .= "Disallow: /*?difficulte=\n";
    $output .= "Disallow: /*&difficulte=\n";
    $output .= "Disallow: /*?distance_max=\n";
    $output .= "Disallow: /*&distance_max=\n";
    $output .= "Disallow: /*?denivele_max=\n";
    $output .= "Disallow: /*&denivele_max=\n";
    $output .= "Disallow: /*?s=\n";
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
    $ga_id         = rando_nono_ga_valid_id();
    $fb_id         = rando_nono_fb_valid_id();

    if ( $needs_map ) {
        echo '<link rel="preconnect" href="https://tile.openstreetmap.org">' . "\n";
        echo '<link rel="dns-prefetch" href="https://tile.openstreetmap.org">' . "\n";
    }
    if ( $needs_weather ) {
        echo '<link rel="preconnect" href="https://api.open-meteo.com" crossorigin>' . "\n";
        echo '<link rel="dns-prefetch" href="https://api.open-meteo.com">' . "\n";
    }
    // GA4/pixel Facebook ne se chargent qu'après consentement (voir
    // rando_nono_ga_assets()), mais la préconnexion peut démarrer plus tôt
    // sans rien télécharger : elle ne fait qu'accélérer la négociation
    // DNS/TLS si le visiteur accepte les cookies.
    if ( $ga_id ) {
        echo '<link rel="preconnect" href="https://www.googletagmanager.com">' . "\n";
        echo '<link rel="dns-prefetch" href="https://www.googletagmanager.com">' . "\n";
    }
    if ( $fb_id ) {
        echo '<link rel="preconnect" href="https://connect.facebook.net">' . "\n";
        echo '<link rel="dns-prefetch" href="https://connect.facebook.net">' . "\n";
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
 * Le résultat (juste les ID, moins lourd à stocker qu'une liste de WP_Post)
 * est mis en cache : ce calcul relit tout le post type à chaque appel, coûteux
 * à répéter à chaque visite d'une fiche randonnée. Pas d'invalidation ciblée
 * (le classement d'UNE fiche peut changer quand N'IMPORTE QUELLE autre est
 * modifiée) : une expiration de quelques heures suffit, un léger retard sur
 * une suggestion "similaire" n'étant pas gênant pour l'utilisateur.
 *
 * @return WP_Post[]
 */
function rando_nono_get_related_randos( $post_id, $limit = 4 ) {
    $cache_key = 'rando_nono_related_' . $post_id . '_' . $limit;
    $cached_ids = get_transient( $cache_key );
    if ( false !== $cached_ids ) {
        return array_filter( array_map( 'get_post', $cached_ids ) );
    }

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

    $ids = wp_list_pluck( $scored, 'id' );
    set_transient( $cache_key, $ids, 12 * HOUR_IN_SECONDS );

    return array_map( 'get_post', $ids );
}

/**
 * Tronque un texte libre ("12 km", "+380 m"...) à sa première valeur
 * numérique. Utilisé par les statistiques d'accueil ci-dessous.
 */
function rando_nono_extract_number( $text ) {
    if ( ! $text ) return 0;
    preg_match( '/-?[\d]+(?:[.,]\d+)?/', $text, $matches );
    if ( empty( $matches ) ) return 0;
    return (float) str_replace( ',', '.', $matches[0] );
}

/**
 * Statistiques agrégées de l'accueil (km, dénivelé, régions parcourues),
 * calculées à partir de toutes les randonnées publiées. Mises en cache — la
 * page d'accueil est la plus visitée du site, inutile de relire tout le post
 * type et ses meta à chaque affichage — et invalidées dès qu'une randonnée
 * est publiée, modifiée ou supprimée, pour que "X randonnées documentées"
 * reflète immédiatement une action dans l'admin plutôt qu'une fenêtre de
 * cache aveugle.
 */
function rando_nono_get_homepage_stats() {
    $cached = get_transient( 'rando_nono_homepage_stats' );
    if ( false !== $cached ) return $cached;

    $regions         = array();
    $total_km        = 0;
    $total_deniv_pos = 0;
    $total_deniv_neg = 0;

    $stats_query = new WP_Query( array( 'post_type' => 'randonnee', 'posts_per_page' => -1, 'no_found_rows' => true ) );
    if ( $stats_query->have_posts() ) {
        while ( $stats_query->have_posts() ) {
            $stats_query->the_post();
            $sid = get_the_ID();

            $lieu_rando = get_post_meta( $sid, 'rando_lieu', true );
            if ( $lieu_rando ) {
                $parts  = explode( ',', $lieu_rando );
                $region = trim( end( $parts ) );
                if ( $region ) $regions[ strtolower( $region ) ] = true;
            }

            $total_km        += abs( rando_nono_extract_number( get_post_meta( $sid, 'rando_distance', true ) ) );
            $total_deniv_pos += abs( rando_nono_extract_number( get_post_meta( $sid, 'rando_denivele', true ) ) );
            $total_deniv_neg += abs( rando_nono_extract_number( get_post_meta( $sid, 'rando_denivele_neg', true ) ) );
        }
        wp_reset_postdata();
    }

    $stats = array(
        'total_km'      => $total_km,
        'deniv_pos'     => $total_deniv_pos,
        'deniv_neg'     => $total_deniv_neg,
        'regions_count' => count( $regions ),
    );
    set_transient( 'rando_nono_homepage_stats', $stats, DAY_IN_SECONDS );
    return $stats;
}

/**
 * Bornes maximales (distance, dénivelé positif) pour les curseurs de filtre
 * de l'archive — calculées sur les randonnées publiées et arrondies pour un
 * curseur confortable à manipuler (pas de borne du type "17.3 km"). Mise en
 * cache comme les autres statistiques agrégées de la page (voir
 * rando_nono_bust_homepage_stats_cache, qui invalide aussi ce transient).
 */
function rando_nono_get_archive_filter_bounds() {
    $cached = get_transient( 'rando_nono_archive_filter_bounds' );
    if ( false !== $cached ) return $cached;

    $max_distance = 0;
    $max_denivele = 0;

    $bounds_query = new WP_Query( array( 'post_type' => 'randonnee', 'posts_per_page' => -1, 'no_found_rows' => true ) );
    if ( $bounds_query->have_posts() ) {
        while ( $bounds_query->have_posts() ) {
            $bounds_query->the_post();
            $sid = get_the_ID();
            $max_distance = max( $max_distance, abs( rando_nono_extract_number( get_post_meta( $sid, 'rando_distance', true ) ) ) );
            $max_denivele = max( $max_denivele, abs( rando_nono_extract_number( get_post_meta( $sid, 'rando_denivele', true ) ) ) );
        }
        wp_reset_postdata();
    }

    $bounds = array(
        'distance_max' => max( 5, (int) ( ceil( $max_distance / 5 ) * 5 ) ),
        'denivele_max' => max( 100, (int) ( ceil( $max_denivele / 100 ) * 100 ) ),
    );
    set_transient( 'rando_nono_archive_filter_bounds', $bounds, DAY_IN_SECONDS );
    return $bounds;
}

function rando_nono_bust_homepage_stats_cache() {
    delete_transient( 'rando_nono_homepage_stats' );
    delete_transient( 'rando_nono_archive_filter_bounds' );
}
add_action( 'save_post_randonnee', 'rando_nono_bust_homepage_stats_cache' );
add_action( 'before_delete_post', function( $post_id ) {
    if ( 'randonnee' === get_post_type( $post_id ) ) {
        rando_nono_bust_homepage_stats_cache();
    }
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
        <?php echo esc_html( $texte ); ?> Ces cookies ne sont déposés qu'avec ton accord.
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

    if ( ! rando_nono_throttle_submission( 'newsletter' ) ) {
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

    // ── Double opt-in ────────────────────────────────────────────────────
    // L'inscription passait directement en `actif`, sans confirmation :
    // n'importe qui pouvait inscrire l'adresse d'un tiers, qui recevait un
    // e-mail qu'il n'avait pas demandé. La CNIL recommande fortement le double
    // opt-in pour la prospection par courriel — sans lui, aucune preuve du
    // consentement en cas de réclamation.
    //
    // La colonne `token` existait déjà pour le désabonnement : elle sert
    // maintenant aussi au lien de confirmation. La checklist PDF récompense la
    // confirmation plutôt que la simple saisie, ce qui améliore au passage la
    // qualité de la liste.
    global $wpdb;
    $table    = rando_nono_newsletter_table_name();
    $existant = $wpdb->get_row( $wpdb->prepare( "SELECT id, statut, token FROM $table WHERE email = %s", $email ) );

    if ( ! $existant ) {
        $token = wp_generate_password( 32, false );
        $wpdb->insert( $table, array(
            'email'            => $email,
            'token'            => $token,
            'date_inscription' => current_time( 'mysql' ),
            'statut'           => 'en_attente',
        ) );
        rando_nono_send_newsletter_confirmation_email( $email, $token );
        wp_safe_redirect( add_query_arg( 'newsletter', 'confirme', $redirect ) );
        exit;
    }

    // Déjà inscrit mais jamais confirmé : on renvoie le lien plutôt que de
    // laisser la personne devant un « c'est fait » qui ne l'est pas.
    if ( 'en_attente' === $existant->statut ) {
        rando_nono_send_newsletter_confirmation_email( $email, $existant->token );
        wp_safe_redirect( add_query_arg( 'newsletter', 'confirme', $redirect ) );
        exit;
    }

    // Déjà actif : rien à faire, et on ne le dit pas (savoir qu'une adresse
    // est inscrite est déjà une information sur la personne).
    wp_safe_redirect( add_query_arg( 'newsletter', 'ok', $redirect ) );
    exit;
}
add_action( 'template_redirect', 'rando_nono_handle_newsletter_form' );

/**
 * Confirmation d'inscription (double opt-in) — c'est ce clic qui vaut
 * consentement, et lui seul déclenche l'envoi de la checklist.
 */
function rando_nono_confirmer_newsletter() {
    if ( ! isset( $_GET['newsletter_confirmer'] ) ) return;

    $token = sanitize_text_field( wp_unslash( $_GET['newsletter_confirmer'] ) );
    if ( '' === $token ) return;

    global $wpdb;
    $table = rando_nono_newsletter_table_name();
    $ligne = $wpdb->get_row( $wpdb->prepare( "SELECT id, email, statut FROM $table WHERE token = %s", $token ) );

    if ( ! $ligne ) {
        wp_safe_redirect( add_query_arg( 'newsletter', 'lien_invalide', home_url( '/' ) ) );
        exit;
    }

    if ( 'actif' !== $ligne->statut ) {
        $wpdb->update( $table, array( 'statut' => 'actif' ), array( 'id' => $ligne->id ) );
        rando_nono_send_newsletter_welcome_email( $ligne->email );
    }

    wp_safe_redirect( add_query_arg( 'newsletter', 'confirme_ok', home_url( '/' ) ) );
    exit;
}
add_action( 'template_redirect', 'rando_nono_confirmer_newsletter', 1 );

/**
 * E-mail de confirmation — un lien, une phrase, rien d'autre.
 */
function rando_nono_send_newsletter_confirmation_email( $email, $token ) {
    $lien = add_query_arg( 'newsletter_confirmer', rawurlencode( $token ), home_url( '/' ) );

    $subject  = 'Confirme ton inscription aux Randos de Nono';
    $message  = "Tu viens de demander à recevoir la newsletter des Randos de Nono.\n\n";
    $message .= "Clique sur ce lien pour confirmer — c'est la dernière étape :\n";
    $message .= $lien . "\n\n";
    $message .= "Tu recevras alors ta checklist du sac à dos, puis un e-mail à chaque nouvelle randonnée publiée.\n\n";
    $message .= "Si tu n'es à l'origine d'aucune inscription, ignore simplement ce message : sans ce clic, ton adresse ne sera pas utilisée.\n\n";
    $message .= get_bloginfo( 'name' );

    $domain  = wp_parse_url( home_url(), PHP_URL_HOST );
    $headers = array( 'From: ' . get_bloginfo( 'name' ) . ' <no-reply@' . $domain . '>' );

    return wp_mail( $email, $subject, $message, $headers );
}

/**
 * E-mail de bienvenue envoyé à chaque nouvel inscrit — contient le lien vers
 * la checklist PDF offerte en échange de l'inscription (lead magnet).
 */
function rando_nono_send_newsletter_welcome_email( $email ) {
    $checklist_url = get_template_directory_uri() . '/assets/downloads/checklist-sac-a-dos-randonnee.pdf';
    $subject = 'Bienvenue — ta checklist du sac à dos est prête';
    $message  = "Merci de t'être inscrit à la newsletter des Randos de Nono !\n\n";
    $message .= "Comme promis, voici ta checklist gratuite à consulter ou imprimer avant chaque départ :\n";
    $message .= $checklist_url . "\n\n";
    $message .= "Tu recevras désormais un e-mail à chaque nouvelle randonnée publiée, avec le récit complet et la trace GPX.\n\n";
    $message .= "Bonne rando !\n" . get_bloginfo( 'name' );

    $domain  = wp_parse_url( home_url(), PHP_URL_HOST );
    $headers = array( 'From: ' . get_bloginfo( 'name' ) . ' <newsletter@' . $domain . '>' );

    $sent = wp_mail( $email, $subject, $message, $headers );
    if ( ! $sent ) {
        error_log( 'Rando Nono newsletter : échec de l\'e-mail de bienvenue pour ' . $email );
    }
}

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
 * Dès qu'une randonnée passe en "publié" pour la première fois, envoie un
 * e-mail à tous les abonnés. Le flag _rando_nono_newsletter_sent évite les
 * envois en double si l'article est ensuite modifié et republié.
 */
function rando_nono_schedule_newsletter_send( $post_id ) {
    update_post_meta( $post_id, '_rando_nono_newsletter_sent', current_time( 'mysql' ) );

    // Envoi immédiat plutôt que différé via WP-Cron : sur beaucoup
    // d'hébergeurs, WP-Cron dépend d'une requête "loopback" (le serveur
    // s'appelle lui-même) qui est bloquée par le pare-feu/l'hébergeur, et
    // l'e-mail programmé ne part alors jamais. On envoie donc tout de suite,
    // pendant la même requête que la publication/le renvoi manuel.
    rando_nono_send_newsletter_event( $post_id );
}

function rando_nono_newsletter_notify_new_rando( $new_status, $old_status, $post ) {
    if ( 'randonnee' !== $post->post_type ) return;
    if ( 'publish' !== $new_status || 'publish' === $old_status ) return;
    if ( get_post_meta( $post->ID, '_rando_nono_newsletter_sent', true ) ) return;

    rando_nono_schedule_newsletter_send( $post->ID );
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

    // Expéditeur générique du site plutôt que l'adresse e-mail personnelle de
    // l'administrateur (celle utilisée pour Réglages > Général), pour ne pas
    // l'exposer à chaque abonné.
    $domain  = wp_parse_url( home_url(), PHP_URL_HOST );
    $headers = array( 'From: ' . get_bloginfo( 'name' ) . ' <newsletter@' . $domain . '>' );

    // wp_mail() peut échouer silencieusement (SMTP mal configuré, hébergeur qui
    // bloque l'envoi...) ; on journalise l'erreur pour pouvoir la diagnostiquer.
    $log_failure = function( $wp_error ) {
        error_log( 'Rando Nono newsletter : échec wp_mail — ' . $wp_error->get_error_message() );
    };
    add_action( 'wp_mail_failed', $log_failure );

    foreach ( $subs as $sub ) {
        $unsub  = add_query_arg( 'newsletter_desabonner', $sub->token, home_url( '/' ) );
        $body   = "Une nouvelle randonnée vient d'être publiée sur Les Randos de Nono !\n\n";
        $body  .= $titre . ( $lieu ? ' — ' . $lieu : '' ) . ( $distance ? ' (' . $distance . ')' : '' ) . "\n\n";
        $body  .= "Découvrir le récit et la trace GPX :\n" . $url . "\n\n";
        $body  .= "---\nSe désabonner en un clic :\n" . $unsub . "\n";
        wp_mail( $sub->email, $subject, $body, $headers );
    }

    remove_action( 'wp_mail_failed', $log_failure );
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

    // Neutralise une éventuelle injection de formule si le fichier est ouvert
    // dans Excel/Google Sheets (une valeur commençant par =, +, -, @ ou une
    // tabulation peut sinon être interprétée comme une formule par le tableur).
    $csv_safe = function( $value ) {
        $value = (string) $value;
        if ( preg_match( '/^[=+\-@\t]/', $value ) ) {
            $value = "'" . $value;
        }
        return $value;
    };

    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, array( 'email', 'date_inscription' ) );
    foreach ( $subscribers as $sub ) {
        fputcsv( $out, array( $csv_safe( $sub->email ), $csv_safe( $sub->date_inscription ) ) );
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

/**
 * Moyenne et nombre total d'avis publiés, toutes randonnées confondues —
 * utilisé pour la preuve sociale affichée en page d'accueil. Mis en cache
 * (l'accueil est la page la plus visitée du site : évite de recalculer cette
 * agrégation SQL à chaque chargement) et invalidé dès qu'un avis est
 * approuvé ou supprimé (voir rando_nono_avis_approve/delete plus bas).
 */
function rando_nono_get_avis_stats_global() {
    $cached = get_transient( 'rando_nono_avis_stats_global' );
    if ( false !== $cached ) return $cached;

    global $wpdb;
    $table = rando_nono_avis_table_name();
    $row   = $wpdb->get_row( "SELECT COUNT(*) as total, AVG(note) as moyenne FROM $table WHERE statut = 'publie'" );
    $stats = array(
        'total'   => $row ? (int) $row->total : 0,
        'moyenne' => ( $row && $row->total > 0 ) ? round( (float) $row->moyenne, 1 ) : 0,
    );
    set_transient( 'rando_nono_avis_stats_global', $stats, DAY_IN_SECONDS );
    return $stats;
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

    if ( ! rando_nono_throttle_submission( 'avis' ) ) {
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
        <p>
          <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=rando_nono_avis_export' ), 'rando_nono_avis_export' ) ); ?>" class="button">Exporter en CSV</a>
          <span class="description" style="margin-left:.6rem">
            Les avis vivent dans une table propre au thème : un export WordPress (Outils&nbsp;→&nbsp;Exporter) ne les contient pas.
            Cet export, ou une sauvegarde de la base entière, est le seul moyen de les conserver.
          </span>
        </p>
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
    delete_transient( 'rando_nono_avis_stats_global' );
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
    delete_transient( 'rando_nono_avis_stats_global' );
    wp_safe_redirect( admin_url( 'admin.php?page=rando-nono-avis' ) );
    exit;
}
add_action( 'admin_post_rando_nono_avis_delete', 'rando_nono_avis_delete' );

/**
 * Export CSV des avis — l'équivalent de celui des abonnés.
 *
 * Les avis n'étaient accessibles qu'en base : répondre à une demande d'accès
 * ou d'effacement demandait une intervention SQL, et ils échappaient à toute
 * sauvegarde qui ne porte pas sur la base entière (l'export WordPress ne
 * couvre pas les tables créées par un thème).
 */
function rando_nono_avis_export() {
    if ( ! current_user_can( 'moderate_comments' ) ) wp_die( 'Accès refusé' );
    check_admin_referer( 'rando_nono_avis_export' );

    global $wpdb;
    $table = rando_nono_avis_table_name();
    $avis  = $wpdb->get_results( "SELECT id, rando_id, nom, note, commentaire, date_avis, statut FROM $table ORDER BY date_avis ASC" );

    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=avis-randos-de-nono.csv' );

    // Même protection que l'export des abonnés : une valeur commençant par
    // =, +, - ou @ serait interprétée comme une formule par un tableur.
    $csv_safe = function( $value ) {
        $value = (string) $value;
        if ( preg_match( '/^[=+\-@\t]/', $value ) ) {
            $value = "'" . $value;
        }
        return $value;
    };

    $out = fopen( 'php://output', 'w' );
    fputcsv( $out, array( 'id', 'randonnee', 'url', 'prenom', 'note', 'commentaire', 'date', 'statut' ) );
    foreach ( $avis as $a ) {
        fputcsv( $out, array(
            (int) $a->id,
            $csv_safe( get_the_title( $a->rando_id ) ),
            $csv_safe( get_permalink( $a->rando_id ) ),
            $csv_safe( $a->nom ),
            (int) $a->note,
            $csv_safe( $a->commentaire ),
            $csv_safe( $a->date_avis ),
            $csv_safe( $a->statut ),
        ) );
    }
    fclose( $out );
    exit;
}
add_action( 'admin_post_rando_nono_avis_export', 'rando_nono_avis_export' );

/* ──────────────────────────────────────────
   DURÉES DE CONSERVATION — annoncées ET tenues

   Les mentions légales disaient « ni conservées au-delà du nécessaire » : une
   formulation honnête, mais la CNIL attend une durée précise, et rien ne
   purgeait quoi que ce soit. Ces deux règles suffisent au périmètre du site :

   - un avis refusé n'a plus de raison d'être après six mois ;
   - une inscription jamais confirmée (double opt-in) n'est pas un
     consentement : elle ne doit pas rester en base.

   Les abonnés désinscrits ne sont pas concernés : leur ligne est supprimée
   immédiatement au clic de désabonnement, pas seulement marquée.
   ────────────────────────────────────────── */
function rando_nono_purger_donnees() {
    global $wpdb;

    $avis = rando_nono_avis_table_name();
    $wpdb->query( $wpdb->prepare(
        "DELETE FROM $avis WHERE statut = %s AND date_avis < %s",
        'refuse',
        gmdate( 'Y-m-d H:i:s', time() - 6 * MONTH_IN_SECONDS )
    ) );

    $nl = rando_nono_newsletter_table_name();
    $wpdb->query( $wpdb->prepare(
        "DELETE FROM $nl WHERE statut = %s AND date_inscription < %s",
        'en_attente',
        gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS )
    ) );
}
rando_nono_run_once_daily( 'rando_nono_purge_faite', 'rando_nono_purger_donnees' );

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
// Priorité 0 : redirect_canonical() est accroché à template_redirect en
// priorité 10 depuis default-filters.php, donc AVANT cette fonction si elle
// utilise la même priorité. Il ajoutait une barre finale à /sw.js — et la
// spécification Service Worker interdit toute redirection sur le script
// d'enregistrement : l'installation échouait systématiquement
// (« The script resource is behind a redirect, which is disallowed »),
// rendant tout le mode hors-ligne inopérant sans le moindre message.
add_action( 'template_redirect', 'rando_nono_serve_sw', 0 );

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
add_action( 'template_redirect', 'rando_nono_serve_offline_page', 0 );
