<?php
/**
 * Écran d administration « Prochain projet »
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 472-539 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

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

