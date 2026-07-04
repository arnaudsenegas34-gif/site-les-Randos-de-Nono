<?php
/**
 * Template Name: Mentions légales
 * Slug: mentions-legales
 */
get_header();
?>

<main class="simple-page" id="main-content">
  <article>
    <h1>Mentions légales</h1>

    <h2>Éditeur du site</h2>
    <p>
      <strong>Nom du site :</strong> Les Randos de Nono<br>
      <strong>Responsable de la publication :</strong> Arnaud SENEGAS<br>
      <strong>Contact :</strong> via le <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">formulaire de contact</a>
    </p>
    <p>Le site est édité à titre personnel et non professionnel.</p>

    <hr>

    <h2>Hébergement</h2>
    <p>Le site est hébergé par InfinityFree.</p>
    <p>Pour plus d'informations sur l'hébergeur, consultez leur site officiel : <a href="https://www.infinityfree.com" target="_blank" rel="noopener">https://www.infinityfree.com</a></p>

    <hr>

    <h2>Propriété intellectuelle</h2>
    <p>
      L'ensemble des contenus présents sur le site Les Randos de Nono, notamment les textes, photographies,
      traces GPS/GPX, cartes, logos et éléments graphiques, est protégé par les dispositions du Code de la propriété intellectuelle.
    </p>
    <p>
      Toute reproduction, représentation, modification, publication ou adaptation de tout ou partie des éléments du site,
      quel que soit le moyen ou le procédé utilisé, est interdite sans autorisation préalable de l'auteur.
    </p>

    <hr>

    <h2>Responsabilité</h2>
    <p>Les informations, itinéraires, traces GPX, cartes et conseils publiés sur ce site sont fournis à titre informatif.</p>
    <p>
      La randonnée est une activité comportant des risques. Chaque utilisateur est seul responsable de sa sécurité,
      de son équipement, de sa condition physique, de son orientation et du respect de la réglementation en vigueur.
    </p>
    <p>Les sentiers, conditions météorologiques, accès et réglementations peuvent évoluer après la publication des informations.</p>
    <p>
      Le responsable du site ne saurait être tenu responsable des accidents, blessures, pertes, dommages matériels
      ou immatériels résultant de l'utilisation des informations diffusées sur ce site.
    </p>

    <hr>

    <h2>Données personnelles</h2>
    <p>Ce site propose un <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">formulaire de contact</a>. Les informations que vous y transmettez (nom, e-mail, message) sont utilisées uniquement pour répondre à votre demande et ne sont ni conservées au-delà du nécessaire, ni transmises à des tiers.</p>
    <p>
      Conformément au Règlement Général sur la Protection des Données (RGPD), vous disposez d'un droit d'accès,
      de rectification et de suppression des données vous concernant.
    </p>
    <p>Pour toute demande relative à vos données personnelles, utilisez le <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">formulaire de contact</a>.</p>

    <hr>

    <h2 id="cookies">Cookies</h2>
    <p>Le site peut utiliser des cookies techniques nécessaires à son bon fonctionnement.</p>
    <p>
      Avec votre accord, le site utilise également <strong>Google Analytics</strong> pour mesurer son audience
      (pages consultées, provenance des visites, type d'appareil). Ce service ne dépose un cookie de mesure
      d'audience que si vous cliquez sur « Accepter » dans le bandeau proposé lors de votre première visite.
      Vous pouvez à tout moment revenir sur votre choix :
    </p>
    <p><button type="button" id="cookie-consent-manage" class="btn-nav">Gérer mes préférences cookies</button></p>
    <p>Des services tiers intégrés au site peuvent également déposer des cookies, notamment :</p>
    <ul>
      <li>les services de cartographie OpenStreetMap ;</li>
      <li>les services d'affichage des données météorologiques ;</li>
      <li>les boutons ou liens de partage vers les réseaux sociaux.</li>
    </ul>
    <p>L'utilisateur peut configurer son navigateur afin de refuser tout ou partie des cookies.</p>

    <hr>

    <h2>Services tiers</h2>
    <p>Le site utilise des services externes pour certaines fonctionnalités :</p>
    <ul>
      <li>OpenStreetMap pour l'affichage des cartes interactives ;</li>
      <li>des fournisseurs de données météorologiques pour l'affichage des conditions météo ;</li>
      <li>des plateformes de réseaux sociaux via des liens de partage.</li>
    </ul>
    <p>Ces services disposent de leurs propres politiques de confidentialité et conditions d'utilisation.</p>

    <hr>

    <h2>Liens externes</h2>
    <p>
      Le site peut contenir des liens vers des sites externes. Le responsable du site ne peut être tenu responsable
      du contenu, des modifications ou du fonctionnement de ces sites tiers.
    </p>

    <hr>

    <h2>Droit applicable</h2>
    <p>Le présent site est soumis au droit français.</p>
    <p>Tout litige relatif à son utilisation relève de la compétence des juridictions françaises.</p>
  </article>
</main>

<?php get_footer(); ?>
