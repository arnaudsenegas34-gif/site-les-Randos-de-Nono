<?php
/**
 * Template Name: Contact
 * Slug: contact
 */
get_header();

$statut = isset( $_GET['contact'] ) ? sanitize_key( $_GET['contact'] ) : '';
?>

<main class="simple-page" id="main-content">
  <article>
    <h1>Contact</h1>
    <p>Une question sur une rando, une trace GPX à signaler, une suggestion ? Écris-moi via ce formulaire, je réponds dès que possible.</p>

    <?php
    // Un message par cause réelle : renvoyer tout le monde vers « vérifie tes
    // champs » faisait chercher une faute là où il n'y en avait pas — le cas le
    // plus fréquent étant un nonce périmé servi depuis le cache de page.
    $messages = array(
      'ok'      => array( 'ok',    'Message envoyé, merci ! Je te réponds dès que possible.' ),
      'expire'  => array( 'error', 'Ta session a expiré pendant que la page était ouverte. Recharge la page (Ctrl+R) et renvoie ton message — rien n\'est perdu, il suffit de le recoller.' ),
      'attente' => array( 'error', 'Doucement — patiente une vingtaine de secondes avant de renvoyer un message.' ),
      'champs'  => array( 'error', 'Il manque quelque chose : vérifie que le nom, l\'adresse e-mail et le message sont tous remplis, et que l\'adresse est valide.' ),
      'envoi'   => array( 'error', 'Ton message n\'a pas pu partir — le problème vient du serveur, pas de toi. Réessaie dans quelques minutes, ou écris directement à ' . esc_html( get_option( 'admin_email' ) ) . '.' ),
      // Conservé pour les liens déjà en circulation vers l'ancien code d'erreur.
      'error'   => array( 'error', 'Une erreur est survenue. Recharge la page et réessaie.' ),
    );
    if ( isset( $messages[ $statut ] ) ) :
      list( $type, $texte ) = $messages[ $statut ];
    ?>
      <div class="card contact-alert contact-alert-<?php echo esc_attr( $type ); ?>" role="status"><?php echo wp_kses_post( $texte ); ?></div>
    <?php endif; ?>

    <form class="contact-form" method="post" action="<?php echo esc_url( get_permalink() ); ?>">
      <?php wp_nonce_field( 'rando_nono_contact_form', 'rando_nono_contact_nonce' ); ?>

      <div class="contact-honeypot" aria-hidden="true">
        <label for="site_web" aria-hidden="true">Site web</label>
        <input type="text" id="site_web" name="site_web" tabindex="-1" autocomplete="off" aria-hidden="true">
      </div>

      <label for="contact_nom">Nom</label>
      <input type="text" id="contact_nom" name="contact_nom" autocomplete="name" required>

      <label for="contact_email">E-mail</label>
      <input type="email" id="contact_email" name="contact_email" autocomplete="email" required>

      <label for="contact_message">Message</label>
      <textarea id="contact_message" name="contact_message" rows="6" required></textarea>

      <button type="submit" name="rando_nono_contact_submit" value="1" class="btn-nav btn-nav-solid">Envoyer</button>

      <?php
      // L'article 13 du RGPD demande d'informer AU MOMENT de la collecte. Le
      // détail figurait bien dans les mentions légales, mais à un autre
      // endroit du site : une ligne ici, avec le lien, suffit à lever le point.
      ?>
      <p class="form-legal">
        Ton nom et ton adresse servent uniquement à te répondre. Ils ne sont transmis à personne
        et ne sont pas conservés au-delà de l'échange —
        <a href="<?php echo esc_url( home_url( '/mentions-legales/' ) ); ?>">en savoir plus</a>.
      </p>
    </form>
  </article>
</main>

<?php get_footer(); ?>
