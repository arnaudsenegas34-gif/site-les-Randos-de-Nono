# Les Randos de Nono — notes de déploiement

Thème WordPress classique (pas de build, pas de dépendances npm/composer).
Ces notes couvrent ce qui n'est *pas* visible en lisant le code : l'environnement
d'hébergement réel, découvert le 31/08/2026 en corrigeant un bug d'affichage GPX.

## Hébergement & déploiement — aucune automatisation

- Hébergeur : InfinityFree.
- **Pas de CI/CD** (pas de workflow GitHub Actions) : rien ne se déploie tout
  seul quand on pousse sur `main`. Toute mise à jour doit être remontée
  manuellement par l'utilisateur, en **deux étapes séparées** :
  1. **Le thème** (tout le dépôt sauf `.htaccess`) → zip avec un dossier
     racine `rando-nono/` (le Text Domain de `style.css`), à téléverser
     depuis l'admin WordPress : *Apparence → Thèmes → Ajouter → Téléverser
     un thème*.
  2. **`.htaccess`** → ne passe PAS par WordPress. Va à la racine du site
     (à côté de `wp-config.php`), via FTP ou le gestionnaire de fichiers de
     l'hébergeur.
- Avant de livrer un correctif touchant `.htaccess` ou la sécurité, préparer
  ces deux livrables séparément plutôt qu'un seul zip — c'est ce que
  l'utilisateur attend.

## `.htaccess` — le dépôt n'est pas la seule source de vérité

Le site utilise le plugin **W3 Total Cache**, qui écrit lui-même ses blocs
`# BEGIN/END W3TC Browser Cache` et `# BEGIN/END W3TC Page Cache core`
directement dans le `.htaccess` en production — WordPress/le plugin peut les
réécrire à tout moment depuis l'admin. Le `.htaccess` du dépôt les inclut
maintenant (copiés depuis la prod le 31/08/2026), mais s'ils redivergent un
jour : **redemander à l'utilisateur un copier-coller du `.htaccess` actuellement
en ligne avant de fusionner un nouveau correctif** — ne jamais écraser à
l'aveugle avec la version du dépôt, au risque de désactiver son cache.

Ordre des blocs figé par une contrainte technique (pas cosmétique) : Force
HTTPS → W3TC Browser Cache → W3TC Page Cache core → WordPress → Durcissement
sécurité → Anti-hotlink images (thème) → Cache navigateur (thème) →
Compression (thème). Le cache W3TC doit être évalué avant le routeur
WordPress, sinon les pages ne sont jamais servies depuis le cache disque.

Le bloc W3TC Browser Cache pose sa propre valeur de `Referrer-Policy`, plus
permissive que celle voulue ici — le bloc Durcissement sécurité la retire
explicitement (`Header always unset`) avant de reposer la sienne, pour ne pas
dépendre de l'ordre des blocs si celui-ci change un jour.

### Anti-hotlink : panne du 01/09/2026, ne pas refaire

Un bloc « Anti-hotlink images » a renvoyé **403 sur toutes les images du
site** (hero, cartes, matos, médiathèque) jusqu'à sa suppression. La règle
fautive :

```apache
RewriteCond %{HTTP_REFERER} !^https?://([^/]+\.)?%{HTTP_HOST}/ [NC]
```

Dans un `RewriteCond`, **la partie droite est une expression régulière : les
variables serveur n'y sont pas développées**. Apache cherchait le texte
littéral `%{HTTP_HOST}`, qu'aucun referer ne contient — condition toujours
vraie, donc `[F]` sur chaque image chargée depuis une page.

Signature à reconnaître : **l'image s'affiche si on ouvre son URL
directement** (pas de Referer) mais jamais dans la page. Devant ce symptôme,
regarder `.htaccess` avant de suspecter le thème ou le CSS.

Le domaine ne peut passer que par la partie GAUCHE (TestString), seule où les
variables sont développées, puis être rappelé par une référence arrière dans
le motif. Aucune protection anti-hotlink n'est en place aujourd'hui : ne pas
en remettre sans vérifier le site image par image derrière.

## CSS : toujours un filet sous une propriété moderne

Le site tourne sur un mutualisé avec W3 Total Cache (minification possible)
et des navigateurs anciens. Toute propriété récente doit être précédée d'une
déclaration classique équivalente, pour que sa perte dégrade au lieu de
casser :

- `padding-inline: max(...)` est systématiquement précédé du raccourci
  `padding` correspondant.
- `aspect-ratio` sur une image doit s'accompagner d'un `max-height` : sans
  lui, la propriété perdue rend la main à la hauteur naturelle et une photo
  en portrait étire la carte sur toute la page.
- **Ne jamais faire d'`aspect-ratio` l'unique source de hauteur d'un
  conteneur dont l'enfant est en `height: 100%`** : s'il saute, le conteneur
  tombe à 0 et l'image disparaît. Hauteur fixe dans ce cas.

La CSP (Content-Security-Policy) est déclarée à **deux endroits qui doivent
rester identiques** : dans `.htaccess` (mod_headers, prioritaire si le module
est actif) et dans `rando_nono_security_headers()` de `functions.php` (filet
de secours si mod_headers est indisponible).

**Elle ne doit jamais s'appliquer à `/wp-admin/`** (corrigé le 01/09/2026).
WordPress y dessine la médiathèque, la liste des thèmes et l'éditeur avec
underscore.js, qui compile ses gabarits par `new Function()` — donc
`'unsafe-eval'`. Sans exclusion, ces écrans restent **blancs**, sans message
à l'écran : la seule trace est une `EvalError` dans la console du navigateur.
Le même en-tête bloquait aussi les avatars Gravatar (`img-src`) et le worker
des émojis (`worker-src`) dans l'admin.

L'exclusion se fait côté PHP par `if ( is_admin() ) return;`, et côté Apache
par `SetEnvIf Request_URI "^/wp-admin/" RANDO_ZONE_ADMIN` puis
`env=!RANDO_ZONE_ADMIN` sur la directive `Header`. On n'autorise surtout pas
`'unsafe-eval'` globalement : cela affaiblirait le site public pour un besoin
qui n'existe que derrière authentification.

## Tailles d'images — `add_image_size()` ne vaut que pour l'avenir

Les tailles déclarées (`rando-card` 640×420, `rando-hero`, `rando-gallery`)
ne sont générées que pour les images téléversées **après** leur déclaration.
Pour les plus anciennes, WordPress retombe silencieusement sur le **fichier
d'origine** : au 01/09/2026, l'accueil servait des PNG de 1 172 px pour des
vignettes affichées à 250 px, soit 5,8 Mo de page.

Conséquence visible le 01/09/2026 : la grille « Matos » réclamant 25 de ces
fichiers d'un coup, InfinityFree refusait une partie des requêtes. Signature
à reconnaître : **des images cassées qui se chargent parfaitement une par une
au clic droit → « Charger l'image »**. C'est un refus de connexions
simultanées, pas un fichier manquant.

`rando_nono_repli_taille_image()` amortit le problème (repli sur
`medium_large` au lieu de l'original), mais **le vrai correctif reste de
régénérer les miniatures** après tout ajout ou modification de
`add_image_size()` — extension *Regenerate Thumbnails*, à passer sur toute la
médiathèque.

## Couleurs — trois oranges, pas un

`--orange` (#D97706) ne passe le contraste WCAG AA **nulle part** : 3,00:1 en
texte sur fond clair, 3,19:1 pour du blanc sur un bouton orange. Il reste
réservé au décoratif (filets, bordures, anneaux de focus) et aux fonds
sombres (hero, footer), où il est lisible. Pour tout le reste :

- `--orange-texte` — texte orange sur une surface claire (#974D03, 5,86:1 sur
  blanc et 4,68:1 sur beige). Éclairci à #E8912B en mode sombre, sans quoi il
  tomberait à 2,59:1.
- `--orange-fond` — fond orange portant du texte blanc (#A85504, 5,29:1).
- `--orange-clair` — orange sur le vert de la section Statistiques (#F0A44A,
  3,64:1 ; l'orange d'origine y était à 2,37:1, sous le seuil même pour du
  grand texte).

Avant d'introduire une nouvelle couleur, en calculer le ratio plutôt que de
l'estimer à l'œil.

## Taxonomie « difficulté » — les noms sont libres

Les termes ne sont pas `facile/moyen/difficile` mais des formules maison
(« Simpliste », « Ça se corse », « Tu vas t'en souvenir »). Conséquences :

- La classe CSS d'une pastille se dérive par `sanitize_title()`, jamais du nom
  brut : `class="badge-diff-ça se corse"` produisait trois classes invalides.
- Ne jamais passer ces noms à `strtolower()`/`ucfirst()` : ces fonctions
  travaillent octet par octet et cassent le « Ç ».
- Deux filets, pas un : `:where(.card-badges .badge):first-child` habille la
  pastille **sans rien savoir de son nom** (la difficulté est toujours la
  première d'une carte), et `.badge-diff` fait de même sur le HTML à jour.
  Les classes nommées ne font qu'affiner la couleur de celles qu'on
  reconnaît. Ajouter ou renommer un terme ne peut donc plus produire de
  pastille incolore.
- Ces trois règles pèsent toutes (0,1,0) — le `:where()` est là pour ça :
  c'est l'**ordre** dans le fichier qui tranche. Le filet de position doit
  rester APRÈS `.badge` (qu'il écrase) et AVANT les couleurs nommées (qui
  l'écrasent). Ne pas réordonner ce bloc.
- Un cache de page (W3TC) peut servir l'ANCIEN HTML avec le NOUVEAU CSS :
  c'est ce qui a fait croire à un bug le 01/09/2026, seul « Simpliste »
  restait coloré. Purger le cache après toute modification du balisage.

## Débordement horizontal mobile — `min-width: 0` sur les enfants de grille

Panne corrigée le 07/09/2026 : sur la page d'accueil en mobile, la page
entière était scrollable latéralement (676px de contenu pour 360px d'écran).
Symptômes rapportés : « les cookies débordent à droite », « certaines photos
débordent », « les cartes débordent ». **Une seule cause pour les trois.**

Un enfant de grille (comme un enfant de flex) a `min-width: auto` : il refuse
de descendre sous la largeur **min-content** de son contenu. Le champ « lieu »
d'une carte est en `white-space: nowrap` ; sur une adresse entière il mesure
~590px. Cette largeur remontait à la carte → à la piste de la grille → à la
grille → à la page. Le correctif est `min-width: 0` sur les enfants de
`.randos-grid` (et des autres grilles) : la troncature par points de
suspension de `.meta-text`, jusque-là **inopérante faute de conteneur borné**,
fonctionne enfin.

Pourquoi le bandeau cookies « débordait » alors qu'il est en `position:
fixed` et correctement centré : quand le document est plus large que l'écran,
les navigateurs mobiles élargissent le *layout viewport* à la largeur du
document, et les éléments fixes se calent dessus. Le bandeau était donc large
de 660px, moitié hors écran. **Devant un élément `fixed` qui sort de l'écran
sur mobile, chercher le débordement du document, pas l'élément lui-même.**

Deux filets posés en plus du correctif :

- `overflow-x: clip` sur `body` — `clip` et **pas** `hidden` : `hidden` crée
  un conteneur de défilement et casserait le `position: sticky` du header
  (vérifié). Sa perte sur un navigateur ancien ne fait que revenir au
  comportement d'avant, sans régression.
- `minmax(min(330px, 100%), 1fr)` au lieu de `minmax(330px, 1fr)` : une
  piste minimale de 330px est plus large que la colonne disponible sous
  378px d'écran.

Méthode de vérification (reproductible) : générer une réplique statique de
la page qui charge les vrais CSS, l'ouvrir dans Chromium avec Playwright, et
lister les éléments dont `getBoundingClientRect().right` dépasse la largeur
du viewport. Comparer `document.documentElement.scrollWidth` à `clientWidth`
à 320 / 360 / 390 / 430 / 600px. C'est ce qui a permis d'écarter les
suspects habituels (hero, vagues SVG, grille matos) et de remonter à la
vraie cause en une mesure.

### Deux défauts trouvés au passage, même page

- **Vague de transition dessinée en haut de la page.** `.section-wave` est en
  `position: absolute`, mais `section.site-section` n'était pas positionnée —
  seul `#statistiques` posait son `position: relative`. La vague de `#matos`
  se calait donc sur le bloc conteneur initial, c'est-à-dire par-dessus le
  hero. Corrigé en posant `position: relative` sur `section.site-section`.
- **Bandeau cookies occupant 65% de l'écran d'un iPhone SE.** `.cookie-consent p`
  porte `flex: 1 1 280px` ; sous 480px le bandeau passe en
  `flex-direction: column`, où `flex-basis` dimensionne la **hauteur** et non
  la largeur. Le paragraphe était étiré à 280px de haut pour ~110px de texte.
  `flex: 0 1 auto` dans la requête média ramène le bandeau à 37% (320px) et
  20% (390px).
- **Cartes « Matos » écrasées à 53px de haut sur mobile.** `grid-auto-rows:
  53px` est correct hors mobile, où `matos.js` fait occuper 1 à 4 rangées à
  chaque carte selon la taille réelle du matériel. Sous 900px, les spans sont
  ramenés à 1 par `!important` sans que la hauteur de rangée soit redéfinie :
  chaque photo tombait dans un bandeau de 230×53. Hauteur de rangée fixe
  (165px puis 150px) — et non `aspect-ratio`, `.matos-img` étant en
  `height: 100%` (voir la règle plus haut).

## Le champ « lieu » contient parfois une adresse entière

Exemple réel : « Cascade du Fornet Auvergne-Rhône-Alpes Savoie (73)
Val-d'Isère, hameau du Fornet, Parc national de la Vanoise ». Sans traitement,
il s'étalait sur quatre lignes et les cartes d'une même rangée n'avaient plus
la même hauteur.

**Deux champs, pas un** (depuis le 07/09/2026) : `rando_lieu` (complet) et
`rando_lieu_court` (facultatif). `rando_nono_lieu_court()` sert de source
unique aux surfaces où la place est comptée — cartes de grille, popups de la
carte d'ensemble, suggestions 404, favoris, « randonnées similaires ». Elle
renvoie le champ court s'il est rempli, sinon la portion avant la première
virgule, coupée sur un espace au-delà de 34 caractères.

La **fiche de randonnée, le schema.org et les e-mails gardent le lieu
complet** : c'est là qu'il sert au référencement local et à la précision. Ne
pas y substituer le lieu court.

Le repli automatique existe pour que les randonnées déjà publiées
s'améliorent sans être rouvertes une par une. La colonne « Lieu (cartes) »
de *Randonnées → Toutes les randonnées* affiche le libellé réellement rendu
et marque en orange ceux qui ont dû être coupés : c'est la liste des fiches
à reprendre à la main.

`mb_substr`/`mb_strlen` plutôt que `substr`/`strlen` (WordPress les fournit
lui-même si mbstring manque) : `substr` coupe au milieu d'un caractère
accentué. Le `strrpos` sur une espace, lui, est sûr en UTF-8 — 0x20
n'apparaît jamais à l'intérieur d'une séquence multi-octets.

La troncature CSS (`.meta-item-lieu .meta-text`, avec le texte complet en
`title`) reste en place comme second filet, mais elle n'est plus la première
ligne de défense — et elle ne fonctionnait de toute façon pas avant le
correctif `min-width: 0` (voir plus haut).

## Polices — provenance et fabrication des fichiers

Les 5 `.woff2` de `assets/fonts/` sont **fabriqués**, pas téléchargés tels
quels. Ne pas les remplacer par un fichier récupéré au hasard.

Origine : paquet npm `@fontsource/merriweather` (fichiers
`merriweather-latin-<300|400|700>-<normal|italic>.woff2`), puis réduits au
jeu de caractères déclaré dans `assets/css/fonts.css` :

```
pyftsubset <source>.woff2 \
  --unicodes="U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,\
U+02DC,U+2000-206F,U+20AC,U+FEFF,U+FFFD" \
  --flavor=woff2 --output-file=assets/fonts/<nom>.woff2
```

Le `unicode-range` de `fonts.css` doit rester identique à ce `--unicodes` :
il ne réduit pas le fichier, il dit seulement au navigateur quand le
télécharger. Les deux doivent bouger ensemble.

**Panne corrigée le 01/09/2026** : les 4 fichiers Light/Regular/Light-Italic/
Italic étaient **strictement identiques** (même MD5) — quatre copies de
« Merriweather Light 18pt *Italic* ». Tout le texte courant du site
s'affichait donc en italique, sauf le gras (seul vrai fichier droit), ce qui
donnait un mélange incohérent que personne n'avait identifié comme un bug.
Vérification rapide après toute mise à jour des polices :

```
md5sum assets/fonts/merriweather-*.woff2   # 5 empreintes DISTINCTES
python3 -c "from fontTools.ttLib import TTFont; \
  print(TTFont('assets/fonts/merriweather-regular.woff2')['post'].italicAngle)"
# doit valoir 0.0 pour light/regular/bold, ≈ -7.8 pour les italiques
```

## Traces GPX — source externe, pas la médiathèque WordPress

Nono récupère ses traces depuis l'app **Suunto** ; le champ "URL du fichier
GPX" de chaque randonnée pointe vers l'export brut de l'API Suunto
(infrastructure Sports-Tracker), de la forme :
`https://api.sports-tracker.com/apiserver/v2/routes/export/<id>?brand=SUUNTOAPP&format=gpx-route`

Ce domaine doit rester dans `connect-src` de la CSP (les deux endroits
ci-dessus) : la carte, le profil altimétrique et la fiche imprimable
échouent tous silencieusement s'il en est retiré, puisqu'ils partagent le
même chargement GPX (`L.GPX()` dans `single-randonnee.js`).

WordPress n'autorise pas nativement l'upload de `.gpx` dans la médiathèque —
un filtre `upload_mimes` + `wp_check_filetype_and_ext` dans `functions.php`
corrige ça pour un usage natif futur, mais l'usage actuel reste le lien
Suunto externe ci-dessus.

## Version du thème

Le numéro de version dans l'en-tête de `style.css` (`Version: X.Y`) doit être
incrémenté à chaque correctif livré, pour que l'utilisateur puisse vérifier
après upload que la bonne version est en ligne.

## Campagne de correction du 15/09/2026 — ce qui a changé et pourquoi

Trois audits (technique, utilisateur, conformité) ont produit 60 constats ;
cette version en corrige 48 dans le code. Les points ci-dessous documentent
les décisions qui ne se lisent pas dans le diff.

### Bascule `no-js` → `js` : `:where()` est obligatoire

**Correctif du 15/09/2026, v6.1 — régression introduite puis réparée le jour
même.** La première version écrivait `.js .section-title { opacity: 0 }`. Ce
sélecteur pèse (0,2,0) et écrasait `.is-revealed { opacity: 1 }` (0,1,0),
quelques lignes plus bas dans le même fichier. Le JavaScript posait bien la
classe — on la voyait dans l'inspecteur — mais **l'opacité restait à 0**.

Résultat : la rando à la une, les statistiques, tous les titres et sous-titres
de section, les filtres d'archive et le bandeau newsletter devenaient
**définitivement invisibles**, sur l'accueil, l'archive, Guides & Sélections et
les pages de contenu. Le contenu était bien dans le HTML : c'était uniquement
la cascade CSS.

Le correctif est `:where(.js)`, qui conditionne la règle **sans changer sa
spécificité** — exactement le motif déjà utilisé pour les pastilles de
difficulté. **Ne jamais remplacer `:where(.js)` par `.js` dans ces blocs.**

Symptôme à reconnaître : un élément qui porte `is-revealed` (ou `is-visible`)
et reste malgré tout à `opacity: 0`. C'est une bataille de spécificité, pas un
problème de JavaScript — inutile de chercher du côté de l'observer.

### Bascule `no-js` → `js` : ne pas la retirer

`<html class="no-js">` est remplacé par `js` par un script en ligne, première
instruction du `<head>` de `header.php`. **Tout le CSS d'apparition au
défilement est conditionné à `.js`** (`.js .rando-card`, `.js .matos-card`,
`.js .section-title`…). Sans cette bascule, 31 des 39 blocs de la page
d'accueil restaient à `opacity: 0` quand JavaScript ne s'exécutait pas — page
vide, sans message. Si on ajoute une nouvelle règle d'apparition, elle doit
être préfixée `.js`, sinon le contenu redevient invisible sans JavaScript.

### Service worker : la priorité 0 est indispensable

`add_action( 'template_redirect', 'rando_nono_serve_sw', 0 )`. En priorité 10
(le défaut), `redirect_canonical()` de WordPress passe avant et ajoute une
barre finale : `/sw.js` répondait 301 vers `/sw.js/`. La spécification
Service Worker interdit toute redirection sur le script d'enregistrement —
l'installation échouait donc systématiquement, **et le mode hors-ligne n'a
jamais fonctionné** depuis sa mise en place. Symptôme : « The script resource
is behind a redirect, which is disallowed » dans la console, sur toutes les
pages. Vérification après livraison : `curl -I https://…/sw.js` doit répondre
`200`, sans redirection.

### Énumération d'utilisateur : trois verrous, pas un

Retirer `/wp/v2/users` de l'API REST et masquer le message d'erreur de
connexion ne suffisait pas : `/?author=1` répondait 301 vers `/author/nono/`
et `wp-sitemap-users-1.xml` publiait la même URL. L'identifiant de
l'administrateur était donc public, ce qui réduit une attaque par force brute
à la recherche du seul mot de passe. Les trois verrous sont maintenant posés
(`rando_nono_bloquer_enumeration_auteur`, filtre `wp_sitemaps_add_provider`,
filtre REST). **Ne pas en retirer un en pensant que les autres couvrent.**

Le verrou de connexion (`rando_nono_login_verrou`) compte les échecs par IP
dans un transient et refuse le formulaire au-delà de 5 essais pendant quinze
minutes. Il ne remplace pas une extension dédiée — pas de liste noire
durable, pas d'alerte — mais il ferme la porte la plus évidente.

### Échelle de difficulté : un rang, pas un renommage

Les noms restent les formules maison (« Simpliste », « Ça se corse »…) — c'est
l'identité du site. Ce qui manquait était l'ordre : `get_terms()` les rendait
par ordre alphabétique, ce qui plaçait « Ça se corse » après « Tu vas t'en
souvenir » à cause de la cédille, et personne ne pouvait deviner lequel était
le plus facile.

Chaque terme porte une métadonnée `rando_difficulte_rang` (champ « Niveau »
dans l'administration de la taxonomie, colonne « Niveau » dans la liste).
`rando_nono_difficultes_ordonnees()` trie dessus,
`rando_nono_difficulte_repere()` produit le « 2/4 » affiché à côté du nom.
**Un terme sans rang passe en fin de liste et n'affiche pas de repère** — il
faut donc penser à renseigner le champ à chaque nouveau niveau ; la colonne de
la liste le signale en orange.

### Pages de taxonomie : elles ont maintenant un gabarit ET des liens

`taxonomy-difficulte.php` remplace le repli `index.php`, qui affichait jusqu'à
dix `<h1>` et le contenu intégral de chaque randonnée. Ces pages étaient
déclarées au sitemap sans recevoir le moindre lien interne ; les pastilles de
difficulté des cartes pointent désormais vers elles.

`categorie_matos` n'a toujours pas de gabarit dédié : si ces archives doivent
vivre, elles ont besoin du même traitement. Sinon, les passer en
`publicly_queryable => false`.

### CLS : les hauteurs du hero sont réservées exprès

`.hero-desc` et `.hero-actions` portent un `min-height` calculé. Ce n'est pas
cosmétique : `font-display: swap` fait basculer d'une police système vers
Merriweather, qui est plus large. Le sous-titre passait de deux à trois lignes
et les boutons d'une ligne à deux, ce qui remontait le contenu du hero de
84 px et portait le CLS à 0,107 — au-dessus du seuil de 0,1. Avec les hauteurs
réservées et `line-height: 1` sur les boutons : **0,021**.

Les `@font-face` de repli ajustés (`Abril Fatface Repli`, `Merriweather
Repli`) dans `fonts.css` visent le même but par une autre voie, mais ils
dépendent de polices système présentes (`local('Georgia')`) : **ils ne
fonctionnent pas sous Linux** et ne suffisent donc pas seuls. Les
`min-height`, eux, marchent partout.

### Formulaire de contact : quatre causes, quatre messages

`?contact=expire` (nonce périmé), `attente` (limitation 20 s), `champs`
(saisie incomplète), `envoi` (échec `wp_mail`). Toutes ces situations
renvoyaient auparavant « vérifiez les champs », y compris quand les champs
étaient corrects.

Le cas du nonce périmé n'est pas théorique : **W3 Total Cache sert `/contact/`
depuis son cache disque avec le nonce figé dans le HTML**, et un nonce
WordPress expire au bout de 12 à 24 h. À faire côté hébergement : exclure
`/contact/` du cache de page (W3TC → Page Cache → Never cache the following
pages).

Les échecs d'envoi sont désormais journalisés (`wp_mail_failed` →
option `rando_nono_dernier_echec_mail` + journal PHP).

### Newsletter : double opt-in

L'inscription crée une ligne en `statut = 'en_attente'` et envoie un lien de
confirmation portant le `token` (la colonne existait déjà pour le
désabonnement). Le passage en `actif` — et l'envoi de la checklist PDF — n'a
lieu qu'au clic. Les inscriptions non confirmées sont purgées au bout de
30 jours, les avis refusés au bout de 6 mois
(`rando_nono_purger_donnees`, une fois par jour via transient).

**Les envois filtrent déjà sur `statut = 'actif'`** : un abonné en attente ne
reçoit rien. Ne pas relâcher ce filtre.

### URL de filtres : 6 480 combinaisons, désormais hors index

Le formulaire de l'archive passe ses critères en `GET` : 5 difficultés × 16
valeurs de distance × 81 de dénivelé. La balise canonique les ramenait déjà
toutes vers `/randonnee/` (le contenu n'était donc pas dupliqué dans l'index),
mais Google les explorait quand même, sur un hébergement qui refuse déjà des
connexions simultanées. `rando_nono_archive_filtree()` pose un `noindex` dès
qu'un paramètre de filtre est présent, et le `robots.txt` virtuel les exclut
du crawl.

### Schema.org : `addressLocality` attend une commune

Y verser le champ « lieu » entier produisait des valeurs de 100 caractères,
valides mais inexploitables pour le référencement local.
`rando_nono_lieu_commune()` extrait la commune par trois voies, de la plus
sûre à la plus hasardeuse, et **ne renvoie rien plutôt qu'une valeur fausse**.
`addressRegion` n'est renseigné que si le dernier segment ne ressemble pas à
un parc ou un massif.

### Autres corrections de la v6.1

- **Nom de l'équipement affiché en permanence** sur les vignettes Matos. La v6.0
  ne le montrait qu'au survol : invisible sans souris, sans JavaScript, et à
  l'arrêt sur la page. Le dégradé sombre du bandeau assure le contraste quelle
  que soit la photo dessous.
- **Mention RGPD du bandeau newsletter** : elle héritait de `--gris` et
  `--orange-texte`, deux couleurs prévues pour une surface claire, posées sur le
  vert du bandeau — 1,15:1 et 1,21:1, illisibles. Passée en `--beige` (5,69:1).
  À noter : `--orange-clair` n'y suffit pas non plus (3,64:1) pour un texte de
  12,5 px, et un soulignement ne remplace pas le contraste — il lève le critère
  « usage de la couleur », pas celui du contraste du texte.

### Ce qui reste à faire hors du code

Ces points ne peuvent pas être réglés depuis le thème :

1. **Rapatrier les traces GPX.** Elles pointent toutes vers l'API
   Sports-Tracker : si elle change, la carte, le profil, le téléchargement et
   la fiche imprimable tombent ensemble, rétroactivement. Le filtre
   `upload_mimes` autorise déjà le `.gpx` dans la médiathèque — il suffit d'y
   téléverser les fichiers et de coller leur URL dans le champ existant.
2. **Sauvegarder la base entière**, pas l'export WordPress : les tables
   `wp_rando_nono_newsletter` et `wp_rando_nono_avis` n'y figurent pas, et le
   thème les recrée vides, ce qui rend la perte silencieuse. Les deux exports
   CSV (abonnés, avis) sont un complément, pas une sauvegarde.
3. **Exclure `/contact/` du cache W3TC** (voir plus haut).
4. **Renseigner le niveau de chaque difficulté** dans
   *Randonnées → Difficulté*.
5. **Régénérer les miniatures** — la taille `rando-card-sm` n'existe que pour
   les images téléversées après cette version.
6. **Vérifier en production** que `/readme.html` répond 403 (le `.htaccess`
   n'est pas appliqué par le serveur de test), que l'article `hello-world`
   n'existe plus, et que `WP_DEBUG` vaut `false`.
7. **Envisager une extension de limitation de connexion** si le verrou intégré
   se révèle insuffisant.
