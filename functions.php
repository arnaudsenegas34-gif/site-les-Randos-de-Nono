<?php
/**
 * Les Randos de Nono — fonctions du thème
 *
 * Ce fichier ne fait que charger les modules du dossier inc/, chacun
 * responsable d'un domaine (SEO, sécurité, newsletter, avis, PWA...) —
 * voir le commentaire en tête de chaque fichier pour son périmètre.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once get_template_directory() . '/inc/icons.php';

// Seeder de données de test (admin uniquement, et seulement quand WP_DEBUG est
// actif — ainsi il est automatiquement inactif sur un site en production sans
// dépendre d'un oubli de suppression manuelle du fichier).
if ( is_admin() && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    require_once get_template_directory() . '/inc/data-seeder.php';
}

require_once get_template_directory() . '/inc/theme-setup.php';
require_once get_template_directory() . '/inc/post-types-randonnee.php';
require_once get_template_directory() . '/inc/post-types-matos.php';
require_once get_template_directory() . '/inc/pages-and-contact.php';
require_once get_template_directory() . '/inc/security-cleanup.php';
require_once get_template_directory() . '/inc/seo.php';
require_once get_template_directory() . '/inc/analytics.php';
require_once get_template_directory() . '/inc/newsletter.php';
require_once get_template_directory() . '/inc/avis.php';
require_once get_template_directory() . '/inc/pwa.php';
