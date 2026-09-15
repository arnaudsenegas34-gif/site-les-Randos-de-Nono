# Les Randos de Nono — règles de travail

Thème WordPress classique : pas de build, pas de npm, pas de composer. On édite
le PHP, le CSS et le JS directement.

**Ce fichier est lu à chaque session : il ne contient que ce qui doit être vrai
en permanence.** Les récits détaillés vivent ailleurs :

| Fichier | Contenu | Quand l'ouvrir |
|---|---|---|
| [`MEMO-PROJET.md`](MEMO-PROJET.md) | Le projet, sa structure, son historique, ce qui reste ouvert | Pour comprendre le site |
| [`docs/incidents.md`](docs/incidents.md) | Le récit de chaque panne et la décision prise | Quand on reconnaît un symptôme |
| Ce fichier | Les règles à ne pas enfreindre | Toujours |

---

## Carte du code — quel fichier pour quoi

`functions.php` n'est qu'un **chargeur** : 54 lignes qui incluent `inc/` dans
l'ordre. On n'y ajoute pas de code, on crée ou on modifie un module.

### Logique (`inc/`)

| Sujet | Fichier |
|---|---|
| Déclaration du thème, tailles d'images | `setup.php` |
| Chargement CSS/JS, repli de taille d'image | `assets.php` |
| CPT « randonnee », taxonomie « difficulte », échelle de niveau | `cpt-randonnee.php` |
| Champs d'une randonnée (distance, dénivelé, GPX, lieu…) | `meta-randonnee.php` |
| CPT « matos » | `cpt-matos.php` |
| Formulaire de contact, journal des échecs d'envoi | `formulaire-contact.php` |
| En-têtes de sécurité, CSP, verrou de connexion, permaliens | `securite.php` |
| Title, meta description, Open Graph, canonique, robots | `seo-meta.php` |
| Données structurées JSON-LD | `seo-schema.php` |
| robots.txt virtuel | `robots-txt.php` |
| Préconnexions, préchargement et srcset du hero | `head-performance.php` |
| Randos similaires, statistiques d'accueil, lieu court | `helpers-randos.php` |
| Favicon, fil d'Ariane, alt automatique | `affichage.php` |
| GA4, Pixel Facebook, Search Console, bandeau cookies | `reglages-tracking.php` |
| Newsletter (double opt-in, envois, export) | `newsletter.php` |
| Avis et notes des lecteurs | `avis.php` |
| Purge des données arrivées à échéance | `rgpd-purge.php` |
| Service worker, page hors-ligne | `pwa.php` |
| Pages « Article / Guide », filtres de sitemap | `pages-guides.php` |
| Création automatique des pages du thème | `pages-auto.php` |
| Menu du tiroir mobile (walker) | `nav-walker.php` |
| Association article ↔ randonnée | `lien-article-rando.php` |
| Réglage « Prochain projet » | `reglage-projet.php` |
| Icônes SVG en ligne | `icons.php` |
| Jeu de données de test (chargé si `WP_DEBUG`) | `data-seeder.php` |

### Gabarits et façade

| Écran | Fichiers |
|---|---|
| Accueil | `front-page.php` |
| Fiche d'une rando | `single-randonnee.php` · `assets/css/single-randonnee.css` · `assets/js/pages/single-randonnee.js` |
| Liste des randos + carte | `archive-randonnee.php` · `assets/js/pages/archive-map.js` |
| Archive d'un niveau de difficulté | `taxonomy-difficulte.php` |
| En-tête, menus, tiroir mobile | `header.php` |
| Vignette de rando | `template-parts/card-rando.php` |
| Styles généraux | `style.css` — bandeaux `══ NOM ══`, repérables par `grep -n` |
| Matos | `assets/css/components/matos.css` · `assets/js/components/matos.js` |

## Fichiers à ne jamais ouvrir en entier

`assets/vendor/` (Leaflet, Chart.js, leaflet-gpx) et `assets/fonts/` sont
**bloqués en lecture** par `.claude/settings.json` : bibliothèques tierces non
modifiées et fichiers binaires, les lire coûte cher et n'apprend rien. À elles
seules, `leaflet.js` et `chart.umd.min.js` représentent une centaine de
milliers de jetons. Pour une question sur Leaflet ou Chart.js, consulter leur
documentation, pas le fichier minifié.

`assets/img/` n'est pas bloqué — ouvrir une photo reste utile pour diagnostiquer
un problème visuel — mais ne pas le faire par simple curiosité.

Pour trouver quelque chose : `grep -rn "motif" --include="*.php" .` puis
`sed -n 'DEB,FINp' fichier` — pas de lecture intégrale d'un gros fichier.

## Index des symptômes — reconnaître avant de chercher

| Symptôme observé | Cause déjà rencontrée | Détail |
|---|---|---|
| Un élément porte `is-revealed` mais reste à `opacity: 0` | `.js` écrit au lieu de `:where(.js)` — bataille de spécificité | [incidents](docs/incidents.md) |
| Toutes les images en 403, mais l'URL directe fonctionne | Bloc anti-hotlink dans `.htaccess` | [incidents](docs/incidents.md) |
| Écran blanc dans `/wp-admin/` (médiathèque, thèmes, éditeur) | CSP appliquée à l'admin — il faut `'unsafe-eval'` | § CSP ci-dessous |
| Images cassées en grille, parfaites une par une | Refus de connexions simultanées : miniatures non générées | § Tailles d'images |
| La page défile latéralement sur mobile | Enfant de grille sans `min-width: 0` | [incidents](docs/incidents.md) |
| Un élément `position: fixed` sort de l'écran sur mobile | Le document déborde, pas l'élément | [incidents](docs/incidents.md) |
| Tout le texte du site s'affiche en italique | Les `.woff2` sont des copies du même fichier | [incidents](docs/incidents.md) |
| Une pastille de difficulté reste incolore | Classe CSS dérivée du nom brut au lieu de `sanitize_title()` | § Difficulté |
| Une seule pastille colorée après une modification | W3TC sert l'ancien HTML avec le nouveau CSS | § Cache |
| `/sw.js` répond 301 et le mode hors-ligne ne s'installe pas | `redirect_canonical()` passe avant : priorité 0 obligatoire | [incidents](docs/incidents.md) |
| « Vérifie les champs » alors que tout est rempli | Nonce périmé servi depuis le cache de page | [incidents](docs/incidents.md) |

---

## Hébergement et livraison — aucune automatisation

Hébergeur : **InfinityFree**. **Pas de CI/CD** : pousser sur `main` ne déploie
rien. Toute mise à jour est remontée à la main, en **deux étapes séparées** :

1. **Le thème** (tout le dépôt sauf `.htaccess`) → zip avec un dossier racine
   `rando-nono/`, téléversé depuis *Apparence → Thèmes → Ajouter → Téléverser*.
2. **`.htaccess`** → ne passe pas par WordPress. Racine du site, par FTP.

Avant de livrer un correctif touchant `.htaccess` ou la sécurité, préparer ces
deux livrables **séparément** plutôt qu'un seul zip.

Le numéro de `Version:` dans l'en-tête de `style.css` doit être **incrémenté à
chaque livraison** : c'est le seul moyen de vérifier après upload que la bonne
version est en ligne.

### Cache

Le site utilise **W3 Total Cache**. Purger le cache après toute modification du
balisage — sinon l'ancien HTML est servi avec le nouveau CSS, ce qui produit
des bugs fantômes (déjà arrivé le 01/09/2026).

`/contact/` doit être **exclu du cache de page** (W3TC → Page Cache → Never
cache the following pages) : le nonce du formulaire y est figé et expire en
12 à 24 h.

## `.htaccess` — le dépôt n'est pas la seule source de vérité

W3 Total Cache écrit lui-même ses blocs `# BEGIN/END W3TC …` dans le
`.htaccess` en production, et peut les réécrire à tout moment. Le fichier du
dépôt les inclut (copiés de la prod le 31/08/2026), mais s'ils redivergent :
**redemander à l'utilisateur un copier-coller du `.htaccess` en ligne avant de
fusionner**. Ne jamais écraser à l'aveugle — cela désactiverait son cache.

Ordre des blocs figé par une contrainte technique, pas cosmétique : Force
HTTPS → W3TC Browser Cache → W3TC Page Cache core → WordPress → Durcissement
sécurité → Anti-hotlink → Cache navigateur → Compression. Le cache W3TC doit
être évalué **avant** le routeur WordPress, sinon rien n'est servi depuis le
cache disque.

Le bloc W3TC pose sa propre `Referrer-Policy`, plus permissive : le bloc
Durcissement la retire (`Header always unset`) avant de reposer la sienne.

**Aucune protection anti-hotlink n'est en place, et c'est volontaire** — celle
du 01/09/2026 a renvoyé 403 sur toutes les images du site. Ne pas en remettre
sans vérifier le site image par image derrière ([détail](docs/incidents.md)).

## CSP — deux déclarations, et jamais dans l'admin

La Content-Security-Policy est déclarée à **deux endroits qui doivent rester
identiques** : `.htaccess` (mod_headers, prioritaire) et
`rando_nono_security_headers()` dans `inc/securite.php` (filet si mod_headers
est absent).

**Elle ne doit jamais s'appliquer à `/wp-admin/`.** WordPress y dessine la
médiathèque, la liste des thèmes et l'éditeur avec underscore.js, qui compile
ses gabarits par `new Function()` — donc `'unsafe-eval'`. Sans exclusion, ces
écrans restent **blancs**, sans message : la seule trace est une `EvalError`
dans la console. L'exclusion se fait par `if ( is_admin() ) return;` côté PHP
et `SetEnvIf Request_URI "^/wp-admin/" RANDO_ZONE_ADMIN` + `env=!RANDO_ZONE_ADMIN`
côté Apache. **Ne jamais autoriser `'unsafe-eval'` globalement.**

Le domaine `api.sports-tracker.com` doit rester dans `connect-src` (traces GPX,
voir plus bas) : l'en retirer casse silencieusement la carte, le profil
altimétrique et la fiche imprimable d'un coup.

## CSS — toujours un filet sous une propriété moderne

Mutualisé + minification W3TC + navigateurs anciens : toute propriété récente
doit être précédée d'une déclaration classique équivalente, pour que sa perte
**dégrade au lieu de casser**.

- `padding-inline: max(...)` toujours précédé du raccourci `padding`.
- `aspect-ratio` sur une image toujours accompagné d'un `max-height` : sans
  lui, une photo en portrait étire la carte sur toute la page.
- **Jamais `aspect-ratio` comme unique source de hauteur d'un conteneur dont
  l'enfant est en `height: 100%`** : s'il saute, le conteneur tombe à 0 et
  l'image disparaît. Hauteur fixe dans ce cas.
- Les enfants de grille et de flex portent `min-width: 0`, sinon un contenu
  large (un lieu en `nowrap`) élargit toute la page ([détail](docs/incidents.md)).
- `overflow-x: clip` sur `body` — **jamais `hidden`**, qui créerait un
  conteneur de défilement et casserait le `position: sticky` du header.
- **Ne jamais remplacer `:where(.js)` par `.js`** dans les blocs d'apparition
  au défilement : `.js .section-title` pèse (0,2,0) et écrase
  `.is-revealed` (0,1,0). Toute la page d'accueil devient invisible.
- Toute nouvelle règle d'apparition doit être conditionnée à `.js`, sinon le
  contenu reste invisible quand JavaScript ne s'exécute pas.
- La bascule `<html class="no-js">` → `js`, premier script du `<head>` de
  `header.php`, ne doit pas être retirée : 31 des 39 blocs de l'accueil en
  dépendent.

## Couleurs — trois oranges, pas un

`--orange` (#D97706) ne passe le contraste WCAG AA **nulle part** : 3,00:1 en
texte sur fond clair, 3,19:1 pour du blanc sur bouton orange. Réservé au
décoratif (filets, bordures, anneaux de focus) et aux fonds sombres.

| Jeton | Valeur | Usage | Ratio |
|---|---|---|---|
| `--orange-texte` | #974D03 | Texte orange sur surface claire | 5,86:1 sur blanc |
| `--orange-fond` | #A85504 | Fond orange portant du texte blanc | 5,29:1 |
| `--orange-clair` | #F0A44A | Orange sur le vert des statistiques | 3,64:1 |
| `--beige` | — | Texte sur le vert du bandeau newsletter | 5,69:1 |

`--orange-texte` est éclairci à #E8912B en mode sombre, sans quoi il tomberait
à 2,59:1.

**Avant d'introduire une couleur, en calculer le ratio** plutôt que de
l'estimer à l'œil. Un soulignement ne compense pas un contraste insuffisant :
il lève le critère « usage de la couleur » (1.4.1), pas celui du contraste du
texte (1.4.3).

## Taxonomie « difficulté » — les noms sont libres

Les termes ne sont pas `facile/moyen/difficile` mais des formules maison
(« Simpliste », « Ça se corse », « Tu vas t'en souvenir »). Conséquences :

- La classe CSS d'une pastille se dérive par `sanitize_title()`, **jamais du
  nom brut** : `class="badge-diff-ça se corse"` produisait trois classes
  invalides.
- Ne jamais passer ces noms à `strtolower()`/`ucfirst()` : ces fonctions
  travaillent octet par octet et cassent le « Ç ».
- Deux filets : `:where(.card-badges .badge):first-child` habille la pastille
  sans rien savoir de son nom, et `.badge-diff` fait de même sur le HTML à
  jour. Les classes nommées ne font qu'affiner la couleur.
- Ces trois règles pèsent (0,1,0) : c'est l'**ordre dans le fichier** qui
  tranche. Le filet de position doit rester APRÈS `.badge` et AVANT les
  couleurs nommées. **Ne pas réordonner ce bloc.**
- L'ordre d'affichage vient de la métadonnée `rando_difficulte_rang` (champ
  « Niveau » dans l'administration). Un terme sans rang passe en fin de liste
  et n'affiche pas de repère « 2/4 » — la colonne le signale en orange.

## Tailles d'images — `add_image_size()` ne vaut que pour l'avenir

Les tailles déclarées ne sont générées que pour les images téléversées
**après** leur déclaration. Pour les plus anciennes, WordPress retombe
silencieusement sur le **fichier d'origine** : au 01/09/2026, l'accueil servait
des PNG de 1 172 px pour des vignettes de 250 px, soit 5,8 Mo de page.

Signature à reconnaître : **des images cassées qui se chargent parfaitement une
par une au clic droit → « Charger l'image »**. C'est un refus de connexions
simultanées, pas un fichier manquant.

`rando_nono_repli_taille_image()` amortit le problème, mais **le vrai correctif
reste de régénérer les miniatures** (extension *Regenerate Thumbnails*, sur
toute la médiathèque) après tout ajout ou modification d'`add_image_size()`.

## Traces GPX — source externe, pas la médiathèque

Les traces viennent de l'app **Suunto** ; le champ « URL du fichier GPX »
pointe vers l'export brut de l'API Sports-Tracker :
`https://api.sports-tracker.com/apiserver/v2/routes/export/<id>?brand=SUUNTOAPP&format=gpx-route`

Ce domaine doit rester dans `connect-src` de la CSP, aux deux endroits. La
carte, le profil altimétrique et la fiche imprimable partagent le même
chargement (`L.GPX()` dans `single-randonnee.js`) et échouent tous ensemble.

Un filtre `upload_mimes` autorise déjà le `.gpx` dans la médiathèque, pour un
rapatriement futur — l'usage actuel reste le lien externe.

## Polices

Les 5 `.woff2` de `assets/fonts/` sont **fabriqués** (sous-ensemble de
`@fontsource/merriweather`), pas téléchargés tels quels. Ne pas les remplacer
par un fichier récupéré au hasard. Procédure de fabrication et contrôle
d'intégrité : [`docs/incidents.md`](docs/incidents.md).

Vérification rapide après toute mise à jour :
`md5sum assets/fonts/merriweather-*.woff2` doit donner **5 empreintes
distinctes**. Le 01/09/2026, quatre fichiers étaient identiques et tout le
texte du site s'affichait en italique.

---

## Ce qui reste ouvert

Les actions qui ne peuvent pas être réglées depuis le thème (traces GPX à
rapatrier, sauvegarde complète de la base, exclusion de `/contact/` du cache,
niveaux de difficulté à renseigner, miniatures à régénérer, vérifications de
production) sont listées et tenues à jour dans
[`MEMO-PROJET.md` § 7](MEMO-PROJET.md).
