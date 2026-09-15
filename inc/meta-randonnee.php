<?php
/**
 * Champs personnalisés d'une randonnée et colonnes de la liste
 *
 * Module extrait de functions.php le 15/09/2026 (lignes 540-750 de la v6.2).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

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

