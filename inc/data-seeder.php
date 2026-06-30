<?php
/**
 * Seeder de données de test
 * Accessible via Outils > Données de test dans l'admin WordPress
 * À retirer avant mise en production.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', function () {
    add_management_page(
        'Données de test',
        'Données de test',
        'manage_options',
        'rando-nono-seeder',
        'rando_nono_seeder_page'
    );
} );

function rando_nono_seeder_page() {
    $message = '';

    if ( isset( $_POST['rando_action'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'rando_nono_seed' ) ) {
        if ( $_POST['rando_action'] === 'seed' ) {
            $count = rando_nono_do_seed();
            $message = '<div class="notice notice-success is-dismissible"><p><strong>' . $count . ' éléments de test créés</strong> (statut : Privé).</p></div>';
        } elseif ( $_POST['rando_action'] === 'delete' ) {
            $count = rando_nono_do_delete();
            $message = '<div class="notice notice-warning is-dismissible"><p><strong>' . $count . ' éléments de test supprimés.</strong></p></div>';
        }
    }

    ?>
    <div class="wrap">
      <h1>Données de test — Les Randos de Nono</h1>
      <?php echo $message; ?>
      <p>Insère <strong>5 randonnées</strong> et <strong>5 matériels</strong> en statut <strong>Privé</strong> pour tester toutes les fonctionnalités du thème (single page, nav, similaires, météo, carte…).</p>
      <p style="color:#856404;background:#fff3cd;padding:.75rem 1rem;border-radius:4px">⚠️ Toutes les entrées de test sont préfixées <code>[TEST]</code> et peuvent être supprimées d'un clic.</p>

      <form method="post" style="display:inline-block;margin-right:1rem">
        <?php wp_nonce_field( 'rando_nono_seed' ); ?>
        <input type="hidden" name="rando_action" value="seed">
        <button type="submit" class="button button-primary button-large">▶ Insérer les données de test</button>
      </form>

      <form method="post" style="display:inline-block">
        <?php wp_nonce_field( 'rando_nono_seed' ); ?>
        <input type="hidden" name="rando_action" value="delete">
        <button type="submit" class="button button-secondary button-large"
                onclick="return confirm('Supprimer toutes les données [TEST] ?')">✕ Supprimer les données de test</button>
      </form>
    </div>
    <?php
}

function rando_nono_do_seed() {
    $count = 0;

    // ── Termes de taxonomie ──────────────────────────────────────────────
    foreach ( array( 'Facile' => 'facile', 'Moyen' => 'moyen', 'Difficile' => 'difficile' ) as $name => $slug ) {
        if ( ! term_exists( $slug, 'difficulte' ) ) {
            wp_insert_term( $name, 'difficulte', array( 'slug' => $slug ) );
        }
    }
    foreach ( array(
        'Sac et portage' => 'sac-portage',
        'Chaussures'     => 'chaussures',
        'Hydratation'    => 'hydratation',
        'Accessoires'    => 'accessoires',
        'Vêtements'      => 'vetements',
    ) as $name => $slug ) {
        if ( ! term_exists( $slug, 'categorie_matos' ) ) {
            wp_insert_term( $name, 'categorie_matos', array( 'slug' => $slug ) );
        }
    }

    // ── Randonnées ───────────────────────────────────────────────────────
    $randonnees = array(
        array(
            'titre'   => '[TEST] Pic Saint-Loup par l\'arête nord',
            'contenu' => "Belle ascension du Pic Saint-Loup (658 m) par l'arête nord, départ depuis le village de Saint-Mathieu-de-Tréviers. La montée est soutenue avec quelques passages techniques en fin d'arête mais offre une vue à 360° sur les Cévennes, la Méditerranée et même les Pyrénées par temps clair.\n\nLa descente par le versant est est plus douce mais plus longue. Le sentier traverse d'abord une pinède puis débouche sur des zones calcaires ouvertes. Un passage sous le prieuré de Saint-Étienne-de-Murles vaut le détour.",
            'diff'    => 'difficile',
            'meta'    => array(
                'rando_lieu'             => 'Saint-Mathieu-de-Tréviers, Hérault',
                'rando_lat'              => '43.7789',
                'rando_lon'              => '3.8356',
                'rando_distance'         => '14 km',
                'rando_denivele'         => '+650 m',
                'rando_denivele_neg'     => '-650 m',
                'rando_duree'            => '5h30',
                'rando_date'             => '15 mars 2025',
                'rando_meilleure_saison' => 'Printemps / Automne',
                'rando_maps_url'         => 'https://maps.google.com/maps?q=43.7789,3.8356',
                'rando_sac'              => "Eau 2L minimum\nCasse-croûte et fruits secs\nCrème solaire et lunettes\nK-Way coupe-vent\nBâtons de marche",
                'rando_conseils'         => "Départ tôt le matin pour éviter la chaleur estivale\nPrévoir des chaussures à semelles rigides\nNe pas y aller par vent fort — crête très exposée\nPossibilité de circuit plus court via le versant sud",
            ),
        ),
        array(
            'titre'   => '[TEST] Gorges de l\'Hérault et Pont du Diable',
            'contenu' => "Randonnée en boucle longeant les gorges de l'Hérault depuis Saint-Jean-de-Fos jusqu'au célèbre Pont du Diable, classé au patrimoine de l'UNESCO. Les sentiers serpentent entre garrigues et falaises calcaires avec plusieurs points de vue saisissants sur la rivière.\n\nPossibilité de se baigner dans les vasques turquoise en été. La section entre le pont et la falaise de la Guilhaumarde offre les meilleurs panoramas.",
            'diff'    => 'moyen',
            'meta'    => array(
                'rando_lieu'             => 'Saint-Jean-de-Fos, Hérault',
                'rando_lat'              => '43.6780',
                'rando_lon'              => '3.5920',
                'rando_distance'         => '9.5 km',
                'rando_denivele'         => '+280 m',
                'rando_denivele_neg'     => '-280 m',
                'rando_duree'            => '3h30',
                'rando_date'             => '8 avril 2025',
                'rando_meilleure_saison' => 'Printemps / Été',
                'rando_maps_url'         => 'https://maps.google.com/maps?q=43.6780,3.5920',
                'rando_sac'              => "Eau 1.5L\nMaillot de bain et serviette\nCrème solaire\nAppareil photo",
                'rando_conseils'         => "Idéal pour se baigner en été — prévoir le maillot\nAttention aux rochers glissants au bord de l'eau\nÉviter en période de crue — rivière dangereuse\nSentier balisé en jaune",
            ),
        ),
        array(
            'titre'   => '[TEST] Tour du Lac du Salagou',
            'contenu' => "Balade accessible autour du lac du Salagou et de ses paysages lunaires de ruffe rouge. Ce terrain volcanique unique en Europe donne aux paysages une couleur ocre-rouge spectaculaire, encore plus belle au coucher du soleil.\n\nOn croise les ruines du village englouti de Celles, abandonné lors de la mise en eau du lac en 1969. Un panneau retrace l'histoire de ce village sacrifié pour l'agriculture irriguée de la plaine.",
            'diff'    => 'facile',
            'meta'    => array(
                'rando_lieu'             => 'Clermont-l\'Hérault, Hérault',
                'rando_lat'              => '43.6420',
                'rando_lon'              => '3.3860',
                'rando_distance'         => '6 km',
                'rando_denivele'         => '+120 m',
                'rando_denivele_neg'     => '-120 m',
                'rando_duree'            => '2h',
                'rando_date'             => '22 mai 2025',
                'rando_meilleure_saison' => 'Toute l\'année',
                'rando_maps_url'         => 'https://maps.google.com/maps?q=43.6420,3.3860',
                'rando_sac'              => "Eau 1L\nAppareil photo (paysage unique)\nPique-nique",
                'rando_conseils'         => "La couleur rouge de la ruffe est unique — prévoir appareil photo\nMagnifique en fin de journée avec la lumière rasante\nSentier peu ombragé — chapeau obligatoire en été\nAccessible à tous, même avec des enfants",
            ),
        ),
        array(
            'titre'   => '[TEST] Sommet du Caroux depuis Douch',
            'contenu' => "Grande randonnée dans le Parc Naturel Régional du Haut-Languedoc depuis le hameau de Douch jusqu'au sommet du Caroux (1091 m). Le plateau sommital offre un panorama exceptionnel sur la plaine languedocienne et les Pyrénées.\n\nLa descente par les gorges d'Héric est spectaculaire avec ses parois verticales et ses cascades. Le torrent d'Héric se franchit à gué plusieurs fois en bas des gorges.",
            'diff'    => 'difficile',
            'meta'    => array(
                'rando_lieu'             => 'Douch, Haut-Languedoc',
                'rando_lat'              => '43.5611',
                'rando_lon'              => '2.9744',
                'rando_distance'         => '16 km',
                'rando_denivele'         => '+780 m',
                'rando_denivele_neg'     => '-780 m',
                'rando_duree'            => '6h',
                'rando_date'             => '3 mai 2025',
                'rando_meilleure_saison' => 'Printemps / Automne',
                'rando_maps_url'         => 'https://maps.google.com/maps?q=43.5611,2.9744',
                'rando_sac'              => "Eau 3L (peu de sources)\nRavitaillement complet\nCoupe-vent (plateau très exposé)\nCarte IGN 2543 OT\nTrousse de premiers secours",
                'rando_conseils'         => "Ne pas partir si ciel menaçant — terrain très exposé au sommet\nDépart impératif avant 8h en été\nLes gorges d'Héric peuvent être glissantes après la pluie\nEngagement physique réel — bon niveau requis",
            ),
        ),
        array(
            'titre'   => '[TEST] Boucle autour de Saint-Guilhem-le-Désert',
            'contenu' => "Randonnée en boucle autour de l'un des plus beaux villages de France, niché au fond des gorges de l'Hérault. La boucle monte sur les crêtes pour offrir une vue plongeante sur le village médiéval et son abbatiale romane.\n\nLa descente passe par le lit du Verdus, ruisseau encaissé avec de petits passages aquatiques. La visite de l'abbaye de Gellone en complément de la rando vaut vraiment le détour.",
            'diff'    => 'facile',
            'meta'    => array(
                'rando_lieu'             => 'Saint-Guilhem-le-Désert, Hérault',
                'rando_lat'              => '43.7266',
                'rando_lon'              => '3.5489',
                'rando_distance'         => '7.5 km',
                'rando_denivele'         => '+200 m',
                'rando_denivele_neg'     => '-200 m',
                'rando_duree'            => '2h30',
                'rando_date'             => '10 juin 2025',
                'rando_meilleure_saison' => 'Printemps / Automne',
                'rando_maps_url'         => 'https://maps.google.com/maps?q=43.7266,3.5489',
                'rando_sac'              => "Eau 1.5L\nAppareil photo\nVieilles chaussures (passage à gué possible)",
                'rando_conseils'         => "Village très fréquenté — préférer semaine ou hors-saison\nVisiter l'abbaye de Gellone en début ou fin de balade\nLe lit du Verdus peut nécessiter de se mouiller les pieds\nParking gratuit à 1 km du village",
            ),
        ),
    );

    // ── Matériel ─────────────────────────────────────────────────────────
    $matos_items = array(
        array(
            'titre'    => '[TEST] Sac à dos Osprey Stratos 36',
            'contenu'  => 'Sac à dos technique 36L avec système de dos ventilé AirSpeed. Idéal pour les randonnées à la journée chargées ou les nuits en refuge. Le dos ventilé fait une vraie différence dans l\'Hérault en été — on ne rentre plus trempé.',
            'categorie' => 'sac-portage',
            'meta'     => array(
                'matos_lien'       => 'https://www.osprey.com/fr/fr/',
                'matos_pourquoi'   => 'Volume idéal pour une journée chargée. Le dos ventilé AirSpeed évite la transpiration dans le dos, indispensable en été dans le Midi.',
                'matos_largeur_cm' => '30',
                'matos_hauteur_cm' => '65',
                'matos_poids_g'    => '1420',
                'matos_essentiel'  => '1',
            ),
        ),
        array(
            'titre'    => '[TEST] Chaussures Salomon X Ultra 4 GTX',
            'contenu'  => 'Chaussures de randonnée basses imperméables avec membrane Gore-Tex intégrée. La semelle Contagrip MA offre une accroche exceptionnelle sur les terrains secs calcaires de garrigue comme sur les sentiers humides de montagne.',
            'categorie' => 'chaussures',
            'meta'     => array(
                'matos_lien'       => 'https://www.salomon.com/fr-fr/',
                'matos_pourquoi'   => 'Accroche parfaite sur la garrigue sèche et les rochers calcaires. Légères mais solides, je les mets pour 80% de mes sorties dans l\'Hérault.',
                'matos_largeur_cm' => '12',
                'matos_hauteur_cm' => '10',
                'matos_poids_g'    => '420',
                'matos_essentiel'  => '1',
            ),
        ),
        array(
            'titre'    => '[TEST] Gourde filtrante Katadyn BeFree 1L',
            'contenu'  => 'Gourde souple avec filtre à fibre creuse intégré. Permet de se ravitailler directement à une source ou un ruisseau sur le terrain. Le filtre dure 1000 litres et se nettoie en agitant la gourde — ultra simple.',
            'categorie' => 'hydratation',
            'meta'     => array(
                'matos_lien'       => 'https://www.katadyn.com/fr/',
                'matos_pourquoi'   => 'Me permet de partir plus léger en eau et de me ravitailler aux sources. Essentiel sur les longues sorties estivales où il fait 35°C dans l\'Hérault.',
                'matos_largeur_cm' => '8',
                'matos_hauteur_cm' => '22',
                'matos_poids_g'    => '55',
                'matos_essentiel'  => '1',
            ),
        ),
        array(
            'titre'    => '[TEST] Bâtons Black Diamond Distance Z',
            'contenu'  => 'Bâtons de trail pliables en 3 sections avec poignées en liège. Ultra-compacts repliés (35 cm), ils entrent facilement dans le sac quand le terrain ne les nécessite pas. Réglage rapide par le système Z-pole.',
            'categorie' => 'accessoires',
            'meta'     => array(
                'matos_lien'       => 'https://www.blackdiamondequipment.com/fr_FR/',
                'matos_pourquoi'   => 'Pliables et légers, je les sors uniquement dans les passages en dénivelé. Économisent les genoux en descente sur les parcours chargés comme le Caroux.',
                'matos_largeur_cm' => '35',
                'matos_hauteur_cm' => '6',
                'matos_poids_g'    => '240',
                'matos_essentiel'  => '',
            ),
        ),
        array(
            'titre'    => '[TEST] Veste imperméable Patagonia Torrentshell 3L',
            'contenu'  => 'Veste imperméable 3 couches légère et packable. Construction Gore-Tex 3L qui tient les averses prolongées sans transpirer. Se roule en boule dans sa propre poche ventrale — prend moins de place qu\'une baguette.',
            'categorie' => 'vetements',
            'meta'     => array(
                'matos_lien'       => 'https://www.patagonia.com/fr-fr/',
                'matos_pourquoi'   => 'Toujours dans le sac même en été — les orages éclatent vite en montagne. Légère (380 g) et packable, elle ne prend presque pas de place.',
                'matos_largeur_cm' => '20',
                'matos_hauteur_cm' => '15',
                'matos_poids_g'    => '380',
                'matos_essentiel'  => '',
            ),
        ),
    );

    // ── Insertion randonnées ─────────────────────────────────────────────
    foreach ( $randonnees as $r ) {
        if ( rando_nono_test_post_exists( $r['titre'], 'randonnee' ) ) continue;

        $post_id = wp_insert_post( array(
            'post_title'   => $r['titre'],
            'post_content' => $r['contenu'],
            'post_type'    => 'randonnee',
            'post_status'  => 'private',
        ) );

        if ( is_wp_error( $post_id ) ) continue;

        $term = get_term_by( 'slug', $r['diff'], 'difficulte' );
        if ( $term ) wp_set_post_terms( $post_id, array( $term->term_id ), 'difficulte' );

        foreach ( $r['meta'] as $key => $val ) {
            update_post_meta( $post_id, $key, $val );
        }
        $count++;
    }

    // ── Insertion matos ──────────────────────────────────────────────────
    foreach ( $matos_items as $m ) {
        if ( rando_nono_test_post_exists( $m['titre'], 'matos' ) ) continue;

        $post_id = wp_insert_post( array(
            'post_title'   => $m['titre'],
            'post_content' => $m['contenu'],
            'post_type'    => 'matos',
            'post_status'  => 'private',
        ) );

        if ( is_wp_error( $post_id ) ) continue;

        $term = get_term_by( 'slug', $m['categorie'], 'categorie_matos' );
        if ( $term ) wp_set_post_terms( $post_id, array( $term->term_id ), 'categorie_matos' );

        foreach ( $m['meta'] as $key => $val ) {
            update_post_meta( $post_id, $key, $val );
        }
        $count++;
    }

    return $count;
}

function rando_nono_do_delete() {
    $count = 0;
    foreach ( array( 'randonnee', 'matos' ) as $post_type ) {
        $posts = get_posts( array(
            'post_type'   => $post_type,
            'post_status' => array( 'private', 'publish', 'draft' ),
            'numberposts' => -1,
        ) );
        foreach ( $posts as $p ) {
            if ( strpos( $p->post_title, '[TEST]' ) === 0 ) {
                wp_delete_post( $p->ID, true );
                $count++;
            }
        }
    }
    return $count;
}

function rando_nono_test_post_exists( $title, $post_type ) {
    global $wpdb;
    return (bool) $wpdb->get_var( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = %s LIMIT 1",
        $title,
        $post_type
    ) );
}
