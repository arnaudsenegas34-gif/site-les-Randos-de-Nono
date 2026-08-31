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
sécurité → Cache navigateur (thème) → Compression (thème). Le cache W3TC doit
être évalué avant le routeur WordPress, sinon les pages ne sont jamais
servies depuis le cache disque.

La CSP (Content-Security-Policy) est déclarée à **deux endroits qui doivent
rester identiques** : dans `.htaccess` (mod_headers, prioritaire si le module
est actif) et dans `rando_nono_security_headers()` de `functions.php` (filet
de secours si mod_headers est indisponible).

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
