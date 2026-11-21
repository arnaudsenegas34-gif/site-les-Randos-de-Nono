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
    <p>
      Le site est édité à titre personnel et non professionnel. Conformément à l'article 1-1, II de la loi
      n° 2004-575 du 21 juin 2004 pour la confiance dans l'économie numérique (LCEN), les éléments
      d'identification de l'éditeur ont été communiqués à l'hébergeur.
    </p>

    <hr>

    <h2>Hébergement</h2>
    <p>
      <strong>InfinityFree</strong>, société immatriculée aux Pays-Bas<br>
      Kwikstaartlaan 42, Unit G1517, 3704 GS Zeist, Pays-Bas<br>
      Téléphone : non communiqué par l'hébergeur ; contact : <a href="https://forum.infinityfree.com" target="_blank" rel="noopener">forum.infinityfree.com</a> — <a href="https://www.infinityfree.com/terms/" target="_blank" rel="noopener">conditions d'utilisation</a>
    </p>

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

    <h2 id="donnees-personnelles">Données personnelles</h2>
    <p>
      <strong>Responsable du traitement :</strong> Arnaud SENEGAS, éditeur du site, joignable via le
      <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">formulaire de contact</a>.
    </p>
    <p>Ce site propose un <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">formulaire de contact</a>. Les informations que vous y transmettez (nom, e-mail, message) sont utilisées uniquement pour répondre à votre demande et ne sont pas transmises à des tiers.
      Elles ne sont pas enregistrées sur le site : votre message est envoyé par e-mail dans la boîte de réception
      de l'éditeur (messagerie Gmail, Google), où il est conservé au maximum <strong>douze mois</strong> après
      le dernier échange, puis supprimé.</p>
    <p><strong>Bases légales :</strong></p>
    <ul>
      <li>formulaire de contact : intérêt légitime de l'éditeur à répondre aux messages qui lui sont adressés ;</li>
      <li>newsletter : votre consentement, donné en confirmant votre inscription, et retirable à tout moment par le lien de désabonnement ;</li>
      <li>avis lecteurs : votre consentement, donné en envoyant l'avis ;</li>
      <li>mesure d'audience (Google Analytics) : votre consentement, donné dans le bandeau cookies et retirable à tout moment.</li>
    </ul>
    <p>
      Le site propose également une <a href="#newsletter">newsletter</a> et un espace d'<a href="#avis">avis lecteurs</a>,
      décrits ci-dessous, qui impliquent la collecte de données personnelles (adresse e-mail pour la newsletter ;
      nom et commentaire pour les avis).
    </p>
    <p>
      Avec votre consentement, des données de mesure d'audience (Google Analytics) peuvent également être
      collectées ; voir la section <a href="#cookies">Cookies</a> ci-dessous. Les données
      de géolocalisation utilisées par le suivi GPS en direct, ainsi que vos favoris et le contenu mis en cache pour
      la consultation hors ligne, restent quant à eux uniquement sur votre appareil (voir <a href="#stockage-local">Stockage local</a>).
    </p>
    <p>
      Conformément au Règlement général sur la protection des données (RGPD) et à la loi « Informatique et
      Libertés », vous disposez d'un droit d'accès, de rectification, d'effacement, de limitation du traitement,
      d'opposition et de portabilité des données vous concernant, ainsi que du droit de retirer votre consentement
      à tout moment.
    </p>
    <p>Pour exercer ces droits, utilisez le <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">formulaire de contact</a> ; une réponse vous sera apportée dans un délai d'un mois.
      Si vous estimez, après nous avoir contactés, que vos droits ne sont pas respectés, vous pouvez adresser une
      réclamation à la CNIL (<a href="https://www.cnil.fr/fr/plaintes" target="_blank" rel="noopener">www.cnil.fr/fr/plaintes</a>).</p>
    <p>Aucune décision automatisée ni aucun profilage n'est réalisé à partir de vos données.</p>

    <hr>

    <h2 id="newsletter">Newsletter</h2>
    <p>
      Le site propose une inscription par e-mail permettant de recevoir une notification à chaque publication
      d'une nouvelle randonnée. L'adresse e-mail fournie est enregistrée dans la base de données du site et
      utilisée exclusivement à cette fin ; elle n'est ni revendue ni transmise à des tiers.
    </p>
    <p>
      L'inscription se fait en <strong>deux temps</strong> : après la saisie de l'adresse, un e-mail contenant un
      lien de confirmation est envoyé. Tant que ce lien n'a pas été suivi, aucune newsletter n'est adressée, et
      l'inscription non confirmée est automatiquement effacée au bout de <strong>trente jours</strong>.
    </p>
    <p>
      Les adresses confirmées sont conservées jusqu'à votre désinscription. Si le site cessait de publier,
      la liste des abonnés serait supprimée.
    </p>
    <p>
      Vous pouvez vous désabonner à tout moment, sans justification, en cliquant sur le lien de désabonnement
      présent au bas de chaque e-mail reçu. Votre adresse est alors supprimée de la liste des abonnés.
    </p>

    <hr>

    <h2 id="avis">Avis et commentaires sur les randonnées</h2>
    <p>
      Le site permet de déposer un avis (note et commentaire, accompagnés du prénom renseigné) sur chaque fiche
      randonnée. Ces avis sont enregistrés dans la base de données du site puis vérifiés manuellement avant
      d'être publiés : ils ne sont donc visibles publiquement qu'après validation.
      Seuls le prénom, la note et le commentaire sont enregistrés — <strong>ni adresse e-mail, ni adresse IP</strong>.
    </p>
    <p>
      Un avis publié est conservé tant que la randonnée l'est. Un avis refusé à la modération est
      automatiquement supprimé au bout de <strong>six mois</strong>.
    </p>
    <p>
      Le prénom et le commentaire associés à un avis publié sont visibles par tous les visiteurs du site.
      Pour demander la modification ou la suppression d'un avis, utilisez le
      <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">formulaire de contact</a>.
    </p>

    <hr>

    <h2 id="stockage-local">Stockage local (favoris et mode hors ligne)</h2>
    <p>
      Le site propose une fonctionnalité de favoris (« Mes randos à faire ») ainsi qu'un mode de consultation
      hors ligne des randonnées déjà visitées. Ces fonctionnalités reposent uniquement sur le stockage local
      de votre navigateur (localStorage et cache du navigateur) : la liste de vos favoris et les pages mises
      en cache pour une consultation hors ligne restent sur votre appareil et ne sont jamais transmises au
      serveur du site. Vous pouvez les supprimer à tout moment en vidant les données de navigation de votre
      navigateur pour ce site.
    </p>

    <hr>

    <h2 id="cookies">Cookies</h2>
    <p>
      Avec votre accord, le site utilise <strong>Google Analytics</strong> pour mesurer son audience (pages
      consultées, provenance des visites, type d'appareil). Ce service n'est chargé, et ne dépose de cookie, que si
      vous cliquez sur « Accepter » dans le bandeau proposé lors de votre première visite. Refuser est aussi simple
      qu'accepter, et votre choix est conservé six mois. Le site n'utilise aucun traceur publicitaire.
    </p>
    <p>Vous pouvez à tout moment revenir sur votre choix, ici ou par le lien « Cookies » en bas de chaque page :</p>
    <p><button type="button" id="cookie-consent-manage" class="btn-nav js-cookie-manage">Gérer mes préférences cookies</button></p>
    <p>Cookies et stockages utilisés :</p>
    <ul>
      <li><strong>__test</strong> : cookie technique déposé par l'hébergeur pour protéger le site contre les robots ; indispensable au fonctionnement, il ne nécessite pas votre consentement ;</li>
      <li><strong>rando_nono_ga_consent</strong> (cookie et stockage local) : mémorise votre choix sur les cookies, pendant six mois ;</li>
      <li><strong>_ga</strong> et <strong>_ga_…</strong> (Google Analytics) : uniquement si vous acceptez, pour une durée de treize mois au plus ; ils sont effacés si vous retirez votre accord ;</li>
      <li>favoris, mode hors ligne et suivi GPS : stockage local de votre navigateur, voir ci-dessus.</li>
    </ul>
    <p>
      Les données de Google Analytics peuvent être transférées aux États-Unis. Google LLC est certifiée au titre
      du cadre de protection des données UE–États-Unis (Data Privacy Framework), reconnu par la Commission européenne
      le 10 juillet 2023 comme offrant un niveau de protection adéquat.
    </p>
    <p>Vous pouvez aussi configurer votre navigateur afin de refuser tout ou partie des cookies.</p>

    <hr>

    <h2>Géolocalisation et suivi GPS en direct</h2>
    <p>
      Le site propose une fonctionnalité de suivi GPS en direct permettant, avec l'autorisation explicite du
      navigateur, de suivre sa position pendant une randonnée (distance parcourue, allure, dénivelé, tracé sur la carte).
    </p>
    <p>
      Cette géolocalisation est traitée uniquement dans le navigateur de l'utilisateur : les positions GPS sont
      stockées localement sur son appareil (stockage local du navigateur) et ne sont ni transmises ni conservées
      sur les serveurs du site. Elles sont supprimées en vidant le stockage du navigateur ou en arrêtant le suivi.
    </p>

    <hr>

    <h2>Services tiers</h2>
    <p>Le site utilise des services externes pour certaines fonctionnalités :</p>
    <ul>
      <li>OpenStreetMap pour le fond des cartes interactives (la bibliothèque Leaflet est hébergée sur le site) ;</li>
      <li>Open-Meteo pour l'affichage des prévisions météo ;</li>
      <li>Suunto / Sports Tracker, qui héberge la trace GPX de certaines randonnées ;</li>
      <li>Google Analytics pour les statistiques de fréquentation (avec votre consentement uniquement) ;</li>
      <li>Instagram, Google Maps et Suunto via de simples liens, qui ne transmettent rien tant que vous ne cliquez pas.</li>
    </ul>
    <p>
      Lorsqu'une carte, la météo ou une trace GPX hébergée chez Suunto s'affiche, votre navigateur contacte
      directement ces services, qui reçoivent alors votre adresse IP, comme pour toute page web. Ils ne
      déposent pas de cookie sur ce site. Les polices de caractères et les scripts du site sont hébergés
      sur le site lui-même.
    </p>
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
