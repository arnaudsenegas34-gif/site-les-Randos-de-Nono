<?php
/**
 * Invitation à s'abonner, à la fin d'une fiche ou d'un article.
 *
 * Le formulaire existe déjà en bas de chaque page (footer.php), mais il
 * arrive après les randos similaires : peu de lecteurs le voient. Ce bloc le
 * rappelle au moment où le lecteur vient de finir sa lecture, et le bouton
 * descend jusqu'au formulaire (pas de second formulaire à maintenir).
 */
$rn_type = isset( $args['type'] ) ? $args['type'] : 'rando';
?>
<aside class="cta-newsletter" aria-label="Recevoir les prochaines randos">
  <div class="cta-newsletter-icone" aria-hidden="true"><?php echo rando_nono_icon( 'mail' ); ?></div>
  <div class="cta-newsletter-texte">
    <p class="cta-newsletter-titre"><?php echo 'article' === $rn_type ? 'Ce récit t’a plu ?' : 'Cette rando t’a plu ?'; ?></p>
    <p>Reçois la prochaine par e-mail, avec sa trace GPX. Un message par nouvelle rando, pas plus.</p>
  </div>
  <a class="btn btn-sm cta-newsletter-btn" href="#newsletter-heading">Je m’abonne</a>
</aside>
