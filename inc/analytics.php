<?php
/**
 * Google Analytics (GA4) et Pixel Facebook — configurables depuis l'admin,
 * chargés uniquement après consentement (bandeau cookies RGPD/CNIL).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

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
        <?php echo esc_html( $texte ); ?> Ces cookies ne sont déposés qu'avec votre accord.
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
