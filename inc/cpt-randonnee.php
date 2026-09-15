<?php
/**
 * CPT « randonnee », taxonomie « difficulte » et son échelle de niveau
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 285-471 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

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

