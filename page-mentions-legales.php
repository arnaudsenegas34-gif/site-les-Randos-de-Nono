<?php
/**
 * Template Name: Mentions légales
 * Slug: mentions-legales
 */
get_header();
?>

<main class="simple-page">
  <article>
    <h1>Mentions légales</h1>

    <h2>Éditeur du site</h2>
    <p>
      Le site <strong>Les Randos de Nono</strong> est un site personnel, non commercial,
      édité par une personne physique dans le cadre de son activité de loisir.<br>
      Contact : <a href="mailto:<?php echo antispambot( get_option( 'admin_email' ) ); ?>"><?php echo antispambot( get_option( 'admin_email' ) ); ?></a>
    </p>

    <h2>Hébergement</h2>
    <p>
      Ce site est hébergé par :<br>
      <?php echo esc_html( get_option( 'rando_nono_hebergeur', 'À compléter dans Réglages > Mentions légales' ) ); ?>
    </p>

    <h2>Propriété intellectuelle</h2>
    <p>
      L'ensemble des contenus (textes, photographies, traces GPX, illustrations)
      publiés sur ce site est la propriété de l'éditeur, sauf mention contraire.
      Toute reproduction, même partielle, est interdite sans autorisation préalable.
    </p>

    <h2>Données personnelles</h2>
    <p>
      Ce site ne collecte aucune donnée personnelle. Aucun cookie de traçage
      n'est déposé. Seuls les cookies techniques indispensables au fonctionnement
      de WordPress peuvent être utilisés.
    </p>

    <h2>Responsabilité</h2>
    <p>
      Les informations fournies (tracés, durées, dénivelés) le sont à titre
      indicatif. L'éditeur décline toute responsabilité en cas d'accident
      survenu lors de la pratique d'une randonnée décrite sur ce site.
      Chaque randonneur reste responsable de sa propre sécurité.
    </p>

    <h2>Crédits</h2>
    <p>
      Cartographie : <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> &amp; Leaflet.<br>
      Données météo : OpenWeatherMap (si applicable).
    </p>
  </article>
</main>

<?php get_footer(); ?>
