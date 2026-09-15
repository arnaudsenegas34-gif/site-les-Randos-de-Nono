<?php
/**
 * Les Randos de Nono — chargeur des modules du thème
 *
 * Ce fichier ne contient plus de logique. Il charge les modules de `inc/`
 * dans l'ordre exact où leur code figurait auparavant ici, ce qui garantit
 * un comportement identique (ordre de déclaration des hooks compris).
 *
 * La liste ci-dessous est la CARTE DU CODE : pour intervenir sur un sujet,
 * ouvrir le module correspondant plutôt que de parcourir tout le thème.
 * Version longue et commentée : CLAUDE.md, § « Carte du code ».
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once get_template_directory() . '/inc/icons.php';

// Seeder de données de test (admin uniquement, et seulement quand WP_DEBUG est
// actif — ainsi il est automatiquement inactif sur un site en production sans
// dépendre d'un oubli de suppression manuelle du fichier).
if ( is_admin() && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    require_once get_template_directory() . '/inc/data-seeder.php';
}

$rando_nono_modules = array(
    'nav-walker',          // Walker du tiroir de navigation mobile
    'setup',               // Déclaration du thème, tailles d'images
    'assets',              // Chargement CSS/JS, repli de taille d'image
    'cpt-randonnee',       // CPT « randonnee » + taxonomie « difficulte » et son échelle
    'reglage-projet',      // Écran d'administration « Prochain projet »
    'meta-randonnee',      // Champs personnalisés d'une randonnée
    'cpt-matos',           // CPT « matos »
    'lien-article-rando',  // Association d'un article à une randonnée
    'pages-auto',          // Création automatique des pages du thème
    'formulaire-contact',  // Formulaire de contact + journal des échecs d'envoi
    'securite',            // En-têtes, verrou de connexion, nettoyage, permaliens
    'seo-meta',            // Title, meta description, Open Graph, canonique, robots
    'seo-schema',          // Données structurées JSON-LD
    'pages-guides',        // Pages « Article / Guide » + filtres de sitemap
    'robots-txt',          // robots.txt virtuel
    'head-performance',    // Préconnexions, préchargement et srcset du hero
    'helpers-randos',      // Durées, distances, randos similaires, statistiques
    'affichage',           // Favicon, fil d'Ariane, alt automatique
    'reglages-tracking',   // Search Console, GA4, Pixel Facebook, bandeau cookies
    'newsletter',          // Inscription double opt-in, envois, export
    'avis',                // Avis et notes des lecteurs, modération, export
    'rgpd-purge',          // Purge des données arrivées à échéance
    'pwa',                 // Service worker et page hors-ligne
);

foreach ( $rando_nono_modules as $rando_nono_module ) {
    require_once get_template_directory() . '/inc/' . $rando_nono_module . '.php';
}
unset( $rando_nono_modules, $rando_nono_module );
