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
