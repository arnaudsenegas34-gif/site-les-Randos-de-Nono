<?php
/**
 * Script de téléchargement des polices Google Fonts en local.
 * Les Randos de Nono — à exécuter UNE SEULE FOIS depuis le navigateur.
 *
 * UTILISATION :
 * 1. Uploade ce fichier à la racine de ton WordPress (pas dans le thème)
 * 2. Ouvre dans le navigateur : https://tonsite.com/download-fonts.php
 * 3. Attends que tous les fichiers soient téléchargés (message de succès)
 * 4. SUPPRIME ce fichier immédiatement après (sécurité)
 *
 * SÉCURITÉ : ce script ne fait que des téléchargements HTTP GET vers Google.
 * Il n'exécute aucun code externe et ne modifie pas ta base de données.
 */

// Empêcher l'exécution depuis la CLI (sécurité basique)
if ( php_sapi_name() === 'cli' ) {
    die( 'Exécute ce script depuis le navigateur.' );
}

$fonts_dir = __DIR__ . '/wp-content/themes/les-rando-de-nono/assets/fonts/';

// Créer le dossier si nécessaire
if ( ! is_dir( $fonts_dir ) ) {
    mkdir( $fonts_dir, 0755, true );
}

// Liste des polices à télécharger : [nom_fichier => url_google]
$fonts = array(
    'abril-fatface.woff2' => 'https://fonts.gstatic.com/s/abrilfatface/v23/zOL54pLDaKK60mme0o_V5dKua8eguQn3.woff2',

    // Merriweather — utilise le User-Agent desktop pour obtenir les woff2
    'merriweather-light.woff2'        => 'https://fonts.gstatic.com/s/merriweather/v30/u-4n0qyriQwlOrhSvowK_l52xwNZWMf6hFo.woff2',
    'merriweather-regular.woff2'      => 'https://fonts.gstatic.com/s/merriweather/v30/u-440qyriQwlOrhSvowK_l5-fCZMdeX3rsHo.woff2',
    'merriweather-bold.woff2'         => 'https://fonts.gstatic.com/s/merriweather/v30/u-4m0qyriQwlOrhSvowK_l5-eCZMdeX3rg.woff2',
    'merriweather-light-italic.woff2' => 'https://fonts.gstatic.com/s/merriweather/v30/u-4l0qyriQwlOrhSvowK_l5-fCZMdef6ulNpqA.woff2',
    'merriweather-italic.woff2'       => 'https://fonts.gstatic.com/s/merriweather/v30/u-4n0qyriQwlOrhSvowK_l5-fCZMdeX3rsHo.woff2',
);

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Téléchargement des polices</title>';
echo '<style>body{font-family:sans-serif;max-width:600px;margin:2rem auto;line-height:1.6}';
echo '.ok{color:#2E5E3B;font-weight:bold}.err{color:#c0392b;font-weight:bold}</style></head><body>';
echo '<h1>Téléchargement des polices — Les Randos de Nono</h1>';

$all_ok = true;

foreach ( $fonts as $filename => $url ) {
    $dest = $fonts_dir . $filename;

    if ( file_exists( $dest ) && filesize( $dest ) > 1000 ) {
        echo '<p class="ok">✓ ' . htmlspecialchars( $filename ) . ' — déjà présent (' . round( filesize( $dest ) / 1024 ) . ' Ko)</p>';
        continue;
    }

    // Téléchargement avec contexte HTTP (User-Agent moderne requis pour obtenir woff2)
    $context = stream_context_create( array(
        'http' => array(
            'header'  => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
            'timeout' => 15,
        )
    ) );

    $data = @file_get_contents( $url, false, $context );

    if ( $data === false || strlen( $data ) < 1000 ) {
        echo '<p class="err">✗ ' . htmlspecialchars( $filename ) . ' — échec du téléchargement. Essaie manuellement depuis <a href="https://gwfh.mranftl.com/fonts" target="_blank">gwfh.mranftl.com</a></p>';
        $all_ok = false;
        continue;
    }

    file_put_contents( $dest, $data );
    echo '<p class="ok">✓ ' . htmlspecialchars( $filename ) . ' — téléchargé (' . round( strlen( $data ) / 1024 ) . ' Ko)</p>';
}

echo '<hr>';
if ( $all_ok ) {
    echo '<p class="ok">✅ Toutes les polices sont en place. <strong>Supprime maintenant ce fichier download-fonts.php</strong> depuis le gestionnaire de fichiers de ton hébergeur.</p>';
} else {
    echo '<p class="err">⚠️ Certaines polices ont échoué. Consulte <a href="https://gwfh.mranftl.com/fonts/abril-fatface?subsets=latin" target="_blank">gwfh.mranftl.com</a> pour les télécharger manuellement et les uploader dans <code>/wp-content/themes/les-rando-de-nono/assets/fonts/</code></p>';
    echo '<p>Si le script échoue complètement, reviens dans le fichier <code>assets/css/fonts.css</code> du thème et remplace le contenu par :<br><code>@import url(\'https://fonts.googleapis.com/css2?family=Abril+Fatface&family=Merriweather:ital,wght@0,300;0,400;0,700;1,300;1,400&display=swap\');</code></p>';
}

echo '</body></html>';
