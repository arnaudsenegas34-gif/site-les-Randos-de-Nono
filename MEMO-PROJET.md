# Mémo projet — Les Randos de Nono

Carte du projet : ce qu'il est, comment il est fait, ce qui lui est arrivé, et
ce qui reste ouvert. À lire en premier pour reprendre le travail après une
pause.

**`CLAUDE.md` est l'autre fichier à connaître** : il contient les règles à ne
pas enfreindre — les pièges déjà rencontrés, les contraintes d'hébergement, les
décisions à ne pas défaire. Ce mémo-ci raconte, `CLAUDE.md` interdit.

Dernière mise à jour : 15 septembre 2026 · thème v6.2

---

## 1. Le projet en trois phrases

Carnet de randonnée personnel d'Arnaud (« Nono »), centré sur l'Hérault et le
Languedoc mais pas seulement. Chaque sortie a sa fiche : récit, photos, chiffres
clés, trace GPX téléchargeable, carte, profil altimétrique, météo en temps réel
et conseils pratiques. Le site propose aussi un catalogue du matériel utilisé,
des guides thématiques, une newsletter et des avis de lecteurs.

**Public** : des randonneurs qui préparent une sortie, souvent sur téléphone,
parfois sur le terrain.

---

## 2. Comment c'est fait

Thème WordPress **classique** — pas de build, pas de npm, pas de composer. On
édite le PHP, le CSS et le JS directement, et c'est ce qui part en production.

| Couche | Choix | Pourquoi |
|---|---|---|
| Hébergement | InfinityFree (mutualisé gratuit) | contrainte forte : connexions simultanées limitées, envoi de courriel restreint |
| Cache | W3 Total Cache | écrit lui-même dans `.htaccess` — voir `CLAUDE.md` |
| Cartes | Leaflet 1.9.4 + leaflet-gpx 1.7.0 | auto-hébergés, pas de CDN |
| Graphiques | Chart.js 4.4.0 | profil altimétrique |
| Polices | Merriweather + Abril Fatface, auto-hébergées | fabriquées par sous-ensemble, voir `CLAUDE.md` |
| Base | MySQL + deux tables maison | `wp_rando_nono_newsletter`, `wp_rando_nono_avis` |

**Aucune dépendance externe pour le code du site.** Les seuls appels sortants
sont : tuiles OpenStreetMap, météo Open-Meteo, traces GPX Suunto, et — si
configurés — Google Analytics et le pixel Facebook.

### Volumétrie

| | |
|---|---|
| Fichiers PHP | 44 (≈ 5 900 lignes) — `functions.php` n'est qu'un chargeur de 54 lignes, la logique vit dans 25 modules `inc/` de 27 à 354 lignes |
| CSS | 6 fichiers (≈ 2 900 lignes) |
| JS | 9 fichiers (≈ 2 100 lignes) |
| Poids d'une page simple | 224 Ko · fiche de randonnée 765 Ko |

---

## 3. Structure du contenu

### Types de contenu

- **`randonnee`** (CPT) — le cœur du site. Gabarits : `single-randonnee.php`,
  `archive-randonnee.php`.
- **`matos`** (CPT) — l'équipement, présenté en mosaïque sur l'accueil avec une
  modale de détail.
- **`post`** — les guides et articles, regroupés par la page hub
  « Guides & Sélections ».
- **Pages à gabarit** : contact, mentions légales, favoris, hub guides,
  article-guide.

### Taxonomies

- **`difficulte`** — les noms sont des **formules maison** (« Simpliste »,
  « Ça se corse », « Tu vas t'en souvenir »), pas une échelle standard. Depuis
  la v6.0, chaque terme porte un **rang** (`rando_difficulte_rang`) qui donne
  l'ordre et le repère « 2/4 » affiché à côté du nom.
- **`categorie_matos`** — pour filtrer la mosaïque d'équipement.

### Champs personnalisés notables

`rando_lieu` (complet) et `rando_lieu_court` (facultatif, pour les surfaces où
la place est comptée), `rando_lat`/`rando_lon`, `rando_distance`,
`rando_denivele`, `rando_duree`, `rando_gpx_url`, `rando_sac`,
`rando_conseils`.

---

## 4. Fonctionnalités à connaître

| Fonction | Où | Remarque |
|---|---|---|
| Trace GPX + carte + profil | `single-randonnee.js` | un seul chargement GPX alimente les trois |
| Suivi GPS en direct | `live-tracking.js` | géolocalisation navigateur, données locales |
| Favoris | `favoris.js` | `localStorage`, pas de compte |
| Mode sombre | `main.js` + jetons CSS | persisté, sans problème de contraste |
| Newsletter | `functions.php` | **double opt-in** depuis la v6.0 |
| Avis lecteurs | `functions.php` | modérés par défaut, alimentent `aggregateRating` |
| Mode hors-ligne | `sw.js` servi à `/sw.js` | ne fonctionne que depuis la v6.0 — voir §6 |
| Données de test | `inc/data-seeder.php` | actif uniquement si `WP_DEBUG` |

---

## 5. Historique

| Version | Date | Objet |
|---|---|---|
| — | 31/08/2026 | Bug d'affichage GPX ; découverte de l'environnement réel |
| — | 01/09/2026 | Panne anti-hotlink (403 sur toutes les images) ; CSP bloquant `/wp-admin/` ; polices toutes identiques ; tailles d'images |
| — | 07/09/2026 | Débordement horizontal mobile ; séparation lieu complet / lieu court |
| **6.0** | 15/09/2026 | 48 constats d'audit corrigés |
| **6.1** | 15/09/2026 | Répare les contenus invisibles introduits en 6.0 |
| **6.2** | 15/09/2026 | Rétablit la mosaïque Matos sans libellé sur grand écran |
| **6.3** | 15/09/2026 | Découpe `functions.php` en modules ; documentation réorganisée. Aucun changement de comportement (HTML rendu identique à l'octet près) |

Les règles qui en découlent sont dans `CLAUDE.md` ; le récit détaillé de
chaque panne, avec sa signature, est dans `docs/incidents.md`. **Les relire
avant de toucher au `.htaccess`, aux polices, aux tailles d'images ou aux
pastilles de difficulté.**

---

## 6. Les trois audits du 15/09/2026

Le site a été reconstruit à l'identique en local (WordPress 6.8.2, PHP 8.4,
base SQLite) puis passé au crible sous trois angles. **60 constats**, dont 48
corrigés en v6.0–6.2.

### Ce qui était cassé sans que rien ne le signale

Trois défauts n'avaient aucun symptôme visible, et c'est ce qui les rendait
coûteux :

1. **Le mode hors-ligne n'a jamais fonctionné.** `/sw.js` répondait 301 vers
   `/sw.js/` — la spécification Service Worker interdit toute redirection sur
   le script d'enregistrement. Cause : `redirect_canonical()` de WordPress
   passait avant la fonction du thème. Corrigé par la priorité 0.
2. **Le formulaire de contact renvoyait « vérifiez les champs » pour quatre
   causes différentes**, dont l'échec d'envoi et le nonce périmé servi depuis
   le cache. Des messages pouvaient être perdus sans que personne ne le sache.
3. **L'identifiant administrateur était public** : `/?author=1` redirigeait
   vers `/author/nono/`, et le sitemap des auteurs publiait la même URL — les
   deux protections en place (REST users bloqué, erreurs de connexion
   masquées) étaient contournées.

### Ce que les audits ont mesuré

| Mesure | Avant | Après |
|---|---|---|
| Violations axe-core (13 pages) | 31 | **0** |
| LCP mobile — accueil | 2 820 ms | **2 068 ms** |
| LCP mobile — archive | 3 436 ms | **1 704 ms** |
| CLS accueil | 0,013 | **0,021** |
| Blocs invisibles sans JavaScript | 31 / 39 | **0** |
| CSS chargé par page simple | 182 Ko | **66 Ko** |
| Pages à plusieurs H1 | 2 | **0** |
| URL de filtres offertes au crawl | 6 480 | **0** (noindex + robots.txt) |

### Rapports détaillés

Trois rapports publiés le 15/09/2026, conservés comme référence :

- **Audit technique** — 20 constats (bugs, accessibilité, performance)
- **Audit utilisateur** — 5 parcours mesurés en défilement réel, 14 frictions
- **Conformité** — référencement, sécurité, RGPD, exploitation, 26 constats

---

## 7. État actuel

### Ce qui est solide

- Aucune XSS sur 8 charges × 2 vecteurs, aucune injection SQL
- Nonce, pot de miel et limitation sur les trois formulaires publics
- Verrou de connexion (5 échecs par IP, quinze minutes)
- CSP complète, `/wp-admin/` exclu, en-têtes de sécurité doublés PHP + Apache
- Minimisation RGPD réelle : ni IP, ni agent utilisateur, ni e-mail avec les avis
- Zéro violation d'accessibilité, zéro débordement horizontal
- Données structurées sur chaque type de contenu

### Ce qui reste ouvert

**Hors du code — demande une action sur le site ou l'hébergement :**

1. **Rapatrier les traces GPX.** Elles pointent toutes vers l'API
   Sports-Tracker. Si elle change, la carte, le profil, le téléchargement et la
   fiche imprimable tombent ensemble, rétroactivement sur toutes les fiches. Le
   filtre `upload_mimes` autorise déjà le `.gpx` dans la médiathèque.
2. **Sauvegarder la base entière**, pas l'export WordPress : les deux tables
   maison n'y figurent pas, et le thème les recrée vides — la perte est
   silencieuse.
3. **Exclure `/contact/` du cache de page W3TC**, sinon le correctif du nonce
   périmé reste théorique.
4. **Renseigner le niveau de chaque difficulté** (*Randonnées → Difficulté*,
   colonne « Niveau », les termes non classés sont signalés en orange).
5. **Régénérer les miniatures** pour la taille `rando-card-sm`.
6. **Vérifier en production** : `/readme.html` doit répondre 403, l'article
   `hello-world` ne doit plus exister, `WP_DEBUG` doit valoir `false`.

**Dans le code — non traité, par choix ou par manque d'information :**

- `categorie_matos` n'a pas de gabarit dédié et retombe sur `index.php` (comme
  `difficulte` avant la v6.0). Soit lui en écrire un, soit passer la taxonomie
  en `publicly_queryable => false`.
- La CSP garde `'unsafe-inline'` sur `script-src` : sans étape de build, il n'y
  a ni nonce ni empreinte à régénérer. À reconsidérer seulement si le thème
  gagne un jour un processus de compilation.
- Chart.js (67 Ko gzip) est chargé sur chaque fiche alors que le profil
  altimétrique est en bas de page. Un chargement différé au premier passage
  gagnerait autant sans rien changer d'autre.
- Les bibliothèques auto-hébergées n'ont aucun mécanisme de veille : rien
  n'avertira d'une faille. Prévoir un contrôle semestriel.

---

## 8. Travailler sur le projet

### Monter un site local

Il n'y a pas de Docker ni de script d'installation. La méthode qui a servi aux
audits, reproductible en quelques minutes :

```
# WordPress + base SQLite (pas de MySQL à installer)
git clone --depth 1 --branch 6.8.2 https://github.com/WordPress/WordPress.git wordpress
git clone --depth 1 --branch v2.1.13 \
    https://github.com/WordPress/sqlite-database-integration.git sqlite-di
cp -r sqlite-di wordpress/wp-content/plugins/sqlite-database-integration
# db.copy → wp-content/db.php, en remplaçant les deux jetons de chemin

# le thème
cp -r <ce dépôt> wordpress/wp-content/themes/rando-nono

# servir
php -S 127.0.0.1:8080 -t wordpress router.php
```

Le seeder intégré (*Outils → Données de test*, visible si `WP_DEBUG`) crée un
jeu de contenu. **Attention** : il utilise `facile/moyen/difficile` alors que la
production utilise les formules maison — un test passé dessus ne rencontre
jamais les caractères accentués qui ont causé le bug des pastilles.

### Vérifier une modification

Chromium et Playwright sont disponibles dans l'environnement de travail. Les
contrôles qui ont servi aux audits, par ordre d'utilité :

| Contrôle | Comment |
|---|---|
| Syntaxe | `php -l` sur chaque fichier, `node --check` sur chaque JS |
| Débordement horizontal | comparer `scrollWidth` et `clientWidth` à 320/360/390/430/768/1024/1440 px |
| Accessibilité | axe-core injecté dans la page, règles wcag2a + wcag2aa |
| Contraste | recalculer le ratio à la formule WCAG — **ne jamais l'estimer à l'œil** |
| Sans JavaScript | `javaScriptEnabled: false`, compter les éléments sous `opacity: 0.15` |
| Core Web Vitals | `PerformanceObserver` sur `largest-contentful-paint` et `layout-shift`, réseau bridé à 1,6 Mb/s et CPU ×4 |

### Livrer

Voir `CLAUDE.md` pour la procédure complète. En résumé : zip du dépôt **sauf
`.htaccess`**, avec un dossier racine `rando-nono/`, téléversé depuis
*Apparence → Thèmes → Ajouter*. Le `.htaccess` part séparément par FTP, et
seulement s'il a changé.

**Incrémenter `Version:` dans `style.css` à chaque livraison** — c'est ce qui
permet de vérifier après coup que la bonne version est en ligne.

**Purger le cache W3TC** après toute modification du balisage : un cache de page
peut servir l'ancien HTML avec le nouveau CSS, ce qui produit des symptômes
incompréhensibles.

---

## 9. Deux erreurs commises pendant les correctifs

Gardées ici parce qu'elles sont instructives, et que rien n'empêche de les
refaire.

**Une spécificité CSS qui a rendu la moitié du site invisible.** Pour ajouter un
repli sans JavaScript, la v6.0 écrivait `.js .section-title { opacity: 0 }`. Ce
sélecteur pèse (0,2,0) et écrasait `.is-revealed { opacity: 1 }` (0,1,0),
déclaré quelques lignes plus bas. Le JavaScript posait bien la classe — on la
voyait dans l'inspecteur — mais l'opacité restait à zéro : rando à la une,
statistiques, titres de section, filtres, bandeau newsletter disparaissaient sur
toutes les pages. Corrigé par `:where(.js)`, qui conditionne sans changer la
spécificité.

*Leçon* : un élément qui porte `is-revealed` et reste à `opacity: 0` est une
bataille de spécificité, pas un problème de JavaScript.

**Une couleur posée sans calculer son contraste.** La mention RGPD ajoutée sous
le formulaire newsletter héritait de `--gris` et `--orange-texte`, deux couleurs
prévues pour une surface claire — posées sur le vert du bandeau, elles donnaient
1,15:1 et 1,21:1. `CLAUDE.md` le disait déjà : calculer le ratio plutôt que de
l'estimer.

*Leçon annexe* : souligner un lien lève le critère « usage de la couleur », pas
celui du contraste du texte. Les deux sont distincts.
