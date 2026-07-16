<?php
$rando_nono_nl_status = isset( $_GET['newsletter'] ) ? sanitize_key( $_GET['newsletter'] ) : '';
?>
<section class="newsletter-band" aria-labelledby="newsletter-heading">
  <div class="newsletter-inner">
    <div class="newsletter-text">
      <h2 id="newsletter-heading" class="newsletter-title">Ne rate aucune rando</h2>
      <p class="newsletter-sub">Reçois un e-mail à chaque nouvelle randonnée publiée, avec le récit et la trace GPX.</p>
    </div>
    <?php if ( 'ok' === $rando_nono_nl_status ) : ?>
      <p class="newsletter-msg newsletter-msg-ok">Merci, ton inscription est confirmée !</p>
    <?php elseif ( 'desabonne' === $rando_nono_nl_status ) : ?>
      <p class="newsletter-msg newsletter-msg-ok">Tu as bien été désabonné.</p>
    <?php elseif ( 'error' === $rando_nono_nl_status ) : ?>
      <p class="newsletter-msg newsletter-msg-error">Adresse e-mail invalide, réessaie.</p>
    <?php else : ?>
      <form class="newsletter-form" method="post" action="<?php echo esc_url( home_url( add_query_arg( null, null ) ) ); ?>">
        <?php wp_nonce_field( 'rando_nono_newsletter_form', 'rando_nono_newsletter_nonce' ); ?>
        <div class="contact-honeypot" aria-hidden="true">
          <label for="site_web_nl">Site web</label>
          <input type="text" id="site_web_nl" name="site_web_nl" tabindex="-1" autocomplete="off">
        </div>
        <input type="email" id="newsletter_email" name="newsletter_email" aria-label="Adresse e-mail" placeholder="ton@email.fr" required>
        <button type="submit" name="rando_nono_newsletter_submit" value="1">S'abonner</button>
      </form>
    <?php endif; ?>
  </div>
</section>

<footer class="site-footer">
  <div class="logo">Les Randos de <span>Nono</span></div>
  <p>Traces GPX & récits de rando · Hérault, France</p>
  <p>
    © <?php echo date( 'Y' ); ?> — Les Randos de Nono
    <?php
    $ml_page = get_page_by_path( 'mentions-legales' );
    if ( ! $ml_page ) $ml_page = get_page_by_path( 'mention-legale' );
    $ml_url = $ml_page ? get_permalink( $ml_page ) : home_url( '/mentions-legales/' );
    ?>
    · <a href="<?php echo esc_url( $ml_url ); ?>">Mentions légales</a>
  </p>
  <a class="social-instagram" href="https://www.instagram.com/a._.sng?igsh=MWpyYWVyazh6NWJ6dw==" target="_blank" rel="noopener noreferrer" aria-label="Suivez-nous sur Instagram">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="22" height="22" fill="currentColor" aria-hidden="true">
      <path d="M12 2c2.717 0 3.056.01 4.122.06 1.065.048 1.79.218 2.428.465a4.9 4.9 0 0 1 1.771 1.153 4.9 4.9 0 0 1 1.153 1.771c.247.637.417 1.363.465 2.428.05 1.066.06 1.405.06 4.122s-.01 3.056-.06 4.122c-.048 1.065-.218 1.79-.465 2.428a4.9 4.9 0 0 1-1.153 1.771 4.9 4.9 0 0 1-1.771 1.153c-.637.247-1.363.417-2.428.465-1.066.05-1.405.06-4.122.06s-3.056-.01-4.122-.06c-1.065-.048-1.79-.218-2.428-.465a4.9 4.9 0 0 1-1.771-1.153 4.9 4.9 0 0 1-1.153-1.771c-.247-.637-.417-1.363-.465-2.428C2.01 15.056 2 14.717 2 12s.01-3.056.06-4.122c.048-1.065.218-1.79.465-2.428a4.9 4.9 0 0 1 1.153-1.771A4.9 4.9 0 0 1 5.45 2.525c.637-.247 1.363-.417 2.428-.465C8.944 2.01 9.283 2 12 2zm0 1.802c-2.67 0-2.986.01-4.04.058-.976.045-1.505.207-1.858.344-.467.182-.8.399-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.858-.048 1.054-.058 1.37-.058 4.04s.01 2.986.058 4.04c.045.976.207 1.505.344 1.858.182.467.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.858.344 1.054.048 1.37.058 4.04.058s2.986-.01 4.04-.058c.976-.045 1.505-.207 1.858-.344.467-.182.8-.399 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.858.048-1.054.058-1.37.058-4.04s-.01-2.986-.058-4.04c-.045-.976-.207-1.505-.344-1.858a3.09 3.09 0 0 0-.748-1.15 3.09 3.09 0 0 0-1.15-.748c-.353-.137-.882-.3-1.858-.344-1.054-.048-1.37-.058-4.04-.058zm0 3.063a5.135 5.135 0 1 1 0 10.27 5.135 5.135 0 0 1 0-10.27zm0 1.802a3.333 3.333 0 1 0 0 6.666 3.333 3.333 0 0 0 0-6.666zm5.338-1.802a1.2 1.2 0 1 1-2.4 0 1.2 1.2 0 0 1 2.4 0z"/>
    </svg>
  </a>
</footer>

<?php wp_footer(); ?>
</body>
</html>
