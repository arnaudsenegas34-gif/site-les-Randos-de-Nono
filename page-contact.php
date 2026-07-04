<?php
/**
 * Template Name: Contact
 * Slug: contact
 */
get_header();

$statut = isset( $_GET['contact'] ) ? sanitize_key( $_GET['contact'] ) : '';
?>

<main class="simple-page">
  <article>
    <h1>Contact</h1>
    <p>Une question sur une rando, une trace GPX à signaler, une suggestion ? Écris-moi via ce formulaire, je réponds dès que possible.</p>

    <?php if ( 'ok' === $statut ) : ?>
      <div class="card contact-alert contact-alert-ok">Message envoyé, merci ! Je te répondrai dès que possible.</div>
    <?php elseif ( 'error' === $statut ) : ?>
      <div class="card contact-alert contact-alert-error">Une erreur est survenue. Merci de vérifier les champs et de réessayer.</div>
    <?php endif; ?>

    <form class="contact-form" method="post" action="<?php echo esc_url( get_permalink() ); ?>">
      <?php wp_nonce_field( 'rando_nono_contact_form', 'rando_nono_contact_nonce' ); ?>

      <div class="contact-honeypot" aria-hidden="true">
        <label for="site_web">Site web</label>
        <input type="text" id="site_web" name="site_web" tabindex="-1" autocomplete="off">
      </div>

      <label for="contact_nom">Nom</label>
      <input type="text" id="contact_nom" name="contact_nom" required>

      <label for="contact_email">E-mail</label>
      <input type="email" id="contact_email" name="contact_email" required>

      <label for="contact_message">Message</label>
      <textarea id="contact_message" name="contact_message" rows="6" required></textarea>

      <button type="submit" name="rando_nono_contact_submit" value="1" class="btn-nav btn-nav-solid">Envoyer</button>
    </form>
  </article>
</main>

<?php get_footer(); ?>
