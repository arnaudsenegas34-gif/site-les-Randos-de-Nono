<?php
/**
 * Walker du tiroir de navigation mobile
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 16-42 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;


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

