#!/usr/bin/env bash
#
# Contrôles du thème « Les Randos de Nono ».
#
# Ce script tourne à l'identique en local (`bash .github/verifier.sh`) et sur
# GitHub à chaque envoi de code : même code, donc pas de dérive possible entre
# ce qui est vérifié ici et ce qui est vérifié là-bas.
#
# Chaque contrôle correspond à une panne réellement survenue sur ce site. Ce
# ne sont pas des règles de style : ce sont des règles écrites dans CLAUDE.md,
# transformées en vérifications automatiques pour ne plus dépendre de la
# mémoire de qui que ce soit.

set -uo pipefail
cd "$(dirname "$0")/.."

echecs=0
ok()     { printf '  \033[32mOK\033[0m     %s\n' "$1"; }
echec()  { printf '  \033[31mÉCHEC\033[0m  %b\n' "$1"; echecs=$((echecs + 1)); }
titre()  { printf '\n\033[1m%s\033[0m\n' "$1"; }

# ── 1. Syntaxe PHP ─────────────────────────────────────────────────────────
# Une erreur de syntaxe dans un module ne se voit pas avant l'installation du
# zip : le site répond alors une page blanche, sans message.
titre "1. Syntaxe PHP"
erreurs_php=""
while IFS= read -r f; do
  sortie=$(php -l "$f" 2>&1)
  case "$sortie" in
    *"No syntax errors"*) ;;
    *) erreurs_php="${erreurs_php}\n    ${sortie}" ;;
  esac
done < <(find . -name '*.php' -not -path './.git/*' | sort)
nb_php=$(find . -name '*.php' -not -path './.git/*' | wc -l)
if [ -z "$erreurs_php" ]; then
  ok "$nb_php fichiers PHP sans erreur de syntaxe"
else
  echec "erreur(s) de syntaxe PHP :$(printf "$erreurs_php")"
fi

# ── 2. Syntaxe JavaScript ──────────────────────────────────────────────────
# `assets/vendor/` est exclu : bibliothèques tierces non modifiées.
titre "2. Syntaxe JavaScript"
erreurs_js=""
while IFS= read -r f; do
  sortie=$(node --check "$f" 2>&1) || erreurs_js="${erreurs_js}\n    ${f} : ${sortie}"
done < <(find assets/js -name '*.js' | sort)
nb_js=$(find assets/js -name '*.js' | wc -l)
if [ -z "$erreurs_js" ]; then
  ok "$nb_js fichiers JS sans erreur de syntaxe"
else
  echec "erreur(s) de syntaxe JS :$(printf "$erreurs_js")"
fi

# ── 3. Modules déclarés = modules présents ─────────────────────────────────
# functions.php ne fait que charger inc/. Un nom mal orthographié ou un
# fichier oublié dans un commit produit une erreur fatale sur TOUTES les
# pages, front et admin compris.
titre "3. Modules du thème"
manquants=""
nb_modules=0
while IFS= read -r module; do
  nb_modules=$((nb_modules + 1))
  [ -f "inc/${module}.php" ] || manquants="${manquants} inc/${module}.php"
done < <(sed -n "/\$rando_nono_modules = array(/,/^);/p" functions.php \
         | grep -oE "^\s+'[a-z0-9-]+'" | tr -d " '")
if [ "$nb_modules" -eq 0 ]; then
  echec "aucun module trouvé dans functions.php — la liste a-t-elle changé de forme ?"
elif [ -n "$manquants" ]; then
  echec "module(s) déclaré(s) mais absent(s) :${manquants}"
else
  ok "$nb_modules modules déclarés, tous présents"
fi

# ── 4. Polices : des fichiers DISTINCTS ────────────────────────────────────
# Panne du 01/09/2026 : les 4 fichiers Light/Regular/Light-Italic/Italic
# étaient quatre copies du même fichier italique. Tout le texte courant du
# site s'affichait en italique, sauf le gras — personne ne l'avait identifié
# comme un bug.
titre "4. Intégrité des polices"
nb_polices=$(find assets/fonts -name '*.woff2' | wc -l)
nb_distinctes=$(find assets/fonts -name '*.woff2' -exec md5sum {} \; | awk '{print $1}' | sort -u | wc -l)
if [ "$nb_polices" -eq "$nb_distinctes" ]; then
  ok "$nb_polices polices, $nb_distinctes empreintes distinctes"
else
  echec "$nb_polices polices mais seulement $nb_distinctes empreintes : des fichiers sont identiques"
fi

# ── 5. Apparition au défilement : :where(.js) obligatoire ──────────────────
# `.js .section-title` pèse (0,2,0) et écrase `.is-revealed` (0,1,0) :
# la rando à la une, les statistiques et tous les titres de section
# deviennent DÉFINITIVEMENT invisibles. `:where(.js)` conditionne la règle
# sans peser sur la spécificité.
titre "5. Spécificité de la bascule no-js → js"
fautifs=$(grep -rnE '^[[:space:]]*\.js[[:space:]]+\.' style.css assets/css/ 2>/dev/null)
if [ -z "$fautifs" ]; then
  ok "aucun sélecteur « .js .xxx » — la forme :where(.js) est respectée"
else
  echec "sélecteur « .js .xxx » trouvé, il écrasera .is-revealed :\n$fautifs"
fi

# ── 6. CSP : le domaine des traces GPX, aux DEUX endroits ──────────────────
# La CSP est déclarée dans .htaccess ET dans inc/securite.php. Retirer
# api.sports-tracker.com de l'une des deux casse la carte, le profil
# altimétrique et la fiche imprimable — silencieusement.
titre "6. CSP — domaine des traces GPX"
dans_php=$(grep -c 'api\.sports-tracker\.com' inc/securite.php)
dans_htaccess=$(grep -c 'api\.sports-tracker\.com' .htaccess)
if [ "$dans_php" -gt 0 ] && [ "$dans_htaccess" -gt 0 ]; then
  ok "api.sports-tracker.com présent dans inc/securite.php et dans .htaccess"
else
  echec "api.sports-tracker.com manque (securite.php : $dans_php, .htaccess : $dans_htaccess)"
fi

# ── 7. CSP : jamais appliquée à /wp-admin/ ─────────────────────────────────
# Sans exclusion, la médiathèque, la liste des thèmes et l'éditeur restent
# BLANCS : underscore.js compile ses gabarits par new Function(), donc
# 'unsafe-eval'. Aucun message à l'écran, seulement une EvalError en console.
titre "7. CSP — exclusion de l'administration"
garde_php=$(awk '/^function rando_nono_security_headers/,/^}/' inc/securite.php | grep -c 'is_admin()')
garde_htaccess=$(grep -c 'RANDO_ZONE_ADMIN' .htaccess)
if [ "$garde_php" -gt 0 ] && [ "$garde_htaccess" -gt 0 ]; then
  ok "garde-fou présent côté PHP (is_admin) et côté Apache (RANDO_ZONE_ADMIN)"
else
  echec "garde-fou admin manquant (PHP : $garde_php, Apache : $garde_htaccess)"
fi

# ── 8. Interdiction de 'unsafe-eval' sur le site public ────────────────────
# Les commentaires sont retirés avant l'examen : les deux fichiers EXPLIQUENT
# longuement pourquoi 'unsafe-eval' est nécessaire dans l'admin et interdit
# ailleurs. Chercher la chaîne brute ferait échouer le contrôle sur sa propre
# documentation.
titre "8. CSP — pas d'unsafe-eval global"
sans_commentaires() { grep -vE '^[[:space:]]*(//|#|\*|/\*)' "$1"; }
if { sans_commentaires inc/securite.php; sans_commentaires .htaccess; } | grep -q "unsafe-eval"; then
  echec "'unsafe-eval' présent dans une directive CSP : cela affaiblirait le site public"
else
  ok "aucun 'unsafe-eval' dans une directive CSP (commentaires exclus)"
fi

# ── 9. Version du thème ────────────────────────────────────────────────────
# Doit être incrémentée à chaque livraison, pour vérifier après upload que la
# bonne version est bien en ligne.
titre "9. Version du thème"
version=$(grep -m1 '^Version:' style.css | sed 's/^Version:[[:space:]]*//')
if printf '%s' "$version" | grep -qE '^[0-9]+\.[0-9]+$'; then
  ok "style.css déclare la version $version"
else
  echec "version absente ou mal formée dans l'en-tête de style.css : « $version »"
fi

# ── Bilan ──────────────────────────────────────────────────────────────────
printf '\n'
if [ "$echecs" -eq 0 ]; then
  printf '\033[32m● Tous les contrôles passent.\033[0m\n'
  exit 0
fi
printf '\033[31m● %s contrôle(s) en échec.\033[0m\n' "$echecs"
exit 1
