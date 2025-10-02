# Notation JLG - Système de notation pour tests de jeux vidéo

[![License: GPL v2](https://img.shields.io/badge/License-GPL_v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

**Contributors:** jeromelegousse  \
**Tags:** rating, review, games, notation, gaming  \
**Requires at least:** 5.0  \
**Tested up to:** 6.4  \
**Stable tag:** 5.0  \
**Requires PHP:** 7.4  \
**License:** GPLv2 or later  \
**License URI:** [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)

Système de notation complet et personnalisable pour les tests de jeux vidéo avec multiples shortcodes et widgets.

> Cette documentation est également disponible en version WordPress Plugin dans `README.txt`. Veillez à maintenir les deux fichiers synchronisés.

## Description

Le plugin Notation JLG est un système complet de notation spécialement conçu pour les sites de tests de jeux vidéo. Il offre une solution professionnelle pour noter et présenter vos reviews de manière attrayante et structurée.

### Fonctionnalités principales

- **Système de notation flexible** : 6 catégories personnalisables notées sur 10
- **Multiples shortcodes** : bloc de notation, fiche technique, points forts/faibles, taglines bilingues
- **Notation utilisateurs** : Permettez à vos lecteurs de voter
- **Tableau récapitulatif** : Vue d'ensemble de tous vos tests avec tri et filtrage
- **Nom de jeu personnalisé** : Remplacez le titre WordPress dans les tableaux, widgets et données structurées
- **Widget** : Affichez vos derniers tests notés
- **API RAWG** : Remplissage automatique des informations de jeu
- **SEO optimisé** : Support schema.org pour les rich snippets Google
- **Thèmes visuels** : Mode clair et sombre avec personnalisation complète
- **Sélecteur de couleurs enrichi** : Profitez du color picker WordPress avec saisie libre (y compris `transparent` lorsque pris en charge)
- **Accessibilité renforcée** : Les animations respectent la préférence système *réduire les mouvements*
- **Gestion dynamique des plateformes** : Ajoutez, triez et réinitialisez vos plateformes depuis l'onglet Plateformes
- **Responsive** : Parfaitement adapté mobile et tablette

### Gestion des plateformes

Accédez à l'onglet **Plateformes** depuis le menu d'administration **Notation – JLG** > **Plateformes**. Vous pouvez y :

- ajouter de nouvelles plateformes pour enrichir vos fiches de test ;
- réordonner et supprimer les plateformes existantes selon vos besoins ;
- réinitialiser la liste pour revenir à la configuration par défaut grâce à l'option **Reset**.

### Validation des métadonnées

- La **date de sortie** est vérifiée avec `DateTime::createFromFormat('Y-m-d')`. Une valeur invalide est rejetée, la méta correspondante est supprimée et une alerte d'administration liste les erreurs détectées.
- Le champ **PEGI** n'accepte que les mentions officielles `PEGI 3`, `PEGI 7`, `PEGI 12`, `PEGI 16` et `PEGI 18`. Toute autre valeur est ignorée et signalée via la même notice.
- Le champ **Nom du jeu** est nettoyé (espaces superflus, longueur maximale) avant sauvegarde pour garantir un affichage cohérent.
- Les formulaires d'édition utilisent un champ HTML `type="date"` et les réponses de l'API RAWG sont normalisées pour renvoyer le format `AAAA-MM-JJ` ainsi qu'une valeur PEGI conforme lorsque disponible, garantissant une expérience cohérente.

### Shortcodes disponibles

- `[jlg_bloc_complet]` (alias `[bloc_notation_complet]`) — Bloc tout-en-un combinant notation, points forts/faibles et tagline. Principaux attributs : `post_id` (ID de l'article ciblé), `style` (`moderne`, `classique`, `compact`), `afficher_notation`, `afficher_points`, `afficher_tagline` (valeurs `oui`/`non`), `couleur_accent`, `titre_points_forts`, `titre_points_faibles`. Remplace l'utilisation combinée des shortcodes `[bloc_notation_jeu]`, `[jlg_points_forts_faibles]` et `[tagline_notation_jlg]` pour un rendu unifié.
- `[bloc_notation_jeu]` - Bloc de notation principal
- `[jlg_fiche_technique]` - Fiche technique du jeu. Attributs : `post_id` (optionnel, ID d'un test publié à afficher, utilise l'article courant sinon), `champs` (liste de champs séparés par des virgules) et `titre`.
- `[tagline_notation_jlg]` - Phrase d'accroche bilingue
- `[jlg_points_forts_faibles]` - Points positifs et négatifs
- `[notation_utilisateurs_jlg]` - Système de vote pour les lecteurs
- `[jlg_tableau_recap]` - Tableau/grille récapitulatif. Les en-têtes permettent désormais de trier par titre, date, note moyenne ainsi que par métadonnées développeur/éditeur via les paramètres `orderby=title`, `orderby=average_score`, `orderby=meta__jlg_developpeur` ou `orderby=meta__jlg_editeur`.
- `[jlg_game_explorer]` - Game Explorer interactif affichant vos tests sous forme de cartes. Attributs disponibles : `posts_per_page` (nombre d'entrées par page), `columns` (2 à 4 colonnes), `filters` (liste séparée par des virgules parmi `letter`, `category`, `platform`, `availability`), `categorie`, `plateforme` et `lettre` pour préfiltrer le rendu. La navigation (lettres, filtres, tri et pagination) fonctionne désormais également sans JavaScript via des requêtes GET accessibles.

### Utilisation dans les widgets et blocs

- Les shortcodes du plugin peuvent être insérés dans les widgets classiques (Texte, Code) ou via le bloc **Shortcode** de
  l'éditeur. Dès qu'un shortcode est exécuté, le plugin déclenche un indicateur global et charge automatiquement les
  feuilles de styles ainsi que les scripts nécessaires, même lorsque le contenu principal ne contient pas de shortcode ou
  que la page n'est pas un article classique.
- Pour vérifier ce comportement, ajoutez par exemple le bloc **Shortcode** dans un gabarit ou un widget de barre latérale
  avec `[jlg_tableau_recap]`, puis affichez une page ou une archive : les assets `jlg-frontend` et `jlg-user-rating`
  sont maintenant chargés dès le rendu du bloc, garantissant le même affichage que dans le contenu principal.

### Blocs Gutenberg

Le plugin propose désormais une collection complète de blocs dynamiques pour l'éditeur moderne :

- **Bloc de notation** (`notation-jlg/rating-block`) : sélectionnez un test publié ou laissez vide pour utiliser l'article
  courant.
- **Points forts / faibles** (`notation-jlg/pros-cons`) et **Tagline bilingue** (`notation-jlg/tagline`) : affichent
  automatiquement les métadonnées du test.
- **Fiche technique** (`notation-jlg/game-info`) : choisissez les champs à afficher, personnalisez le titre et ciblez un
  autre article via le sélecteur de contenu.
- **Notation utilisateurs** (`notation-jlg/user-rating`) : insère le module de vote interactif.
- **Tableau récapitulatif** (`notation-jlg/summary-display`) : contrôlez le nombre d'éléments, la disposition (table ou
  grille), les colonnes et les filtres par défaut.
- **Bloc tout-en-un** (`notation-jlg/all-in-one`) : activez ou non chaque sous-section, choisissez le style et la couleur
  d'accent pour un rendu cohérent.
- **Game Explorer** (`notation-jlg/game-explorer`) : définissez le tri initial, les filtres disponibles et les paramètres de
  préfiltrage (catégorie, plateforme, lettre).

Chaque bloc repose sur le rendu PHP historique (shortcodes) et marque automatiquement l'exécution via
`JLG_Frontend::mark_shortcode_rendered()` afin que les assets nécessaires soient chargés, y compris dans l'éditeur.

### Surcharge des templates

Pour personnaliser le HTML rendu par un shortcode ou un widget, copiez le fichier correspondant depuis
`plugin-notation-jeux/templates/` dans votre thème (ou thème enfant) sous `notation-jlg/{template}.php`.
Lors de l'appel, le plugin cherche d'abord `notation-jlg/shortcode-rating-block.php`, `notation-jlg/widget-latest-reviews.php`,
etc., avant de revenir au fichier interne. Deux filtres sont disponibles pour aller plus loin :

- `jlg_frontend_template_candidates` pour modifier la liste des chemins passés à `locate_template()` ;
- `jlg_frontend_template_path` pour ajuster le chemin final utilisé.

Ces mécanismes vous permettent de conserver vos surcharges lors des mises à jour tout en offrant des points d'ancrage
programmatiques pour les intégrations avancées.

## Installation

1. Téléchargez le plugin et décompressez l'archive
2. Uploadez le dossier `plugin-notation-jeux` dans `/wp-content/plugins/`
3. Activez le plugin depuis le menu 'Extensions' de WordPress
4. Configurez le plugin dans 'Notation - JLG' > 'Réglages'
5. Créez votre premier test avec notation !

## Tests manuels de sécurité CSS

Pour vérifier que les options ne peuvent pas injecter de CSS invalide :

1. Dans l'administration WordPress, rendez-vous dans **Notation – JLG > Réglages**.
2. Dans la section *Tableau Récapitulatif*, utilisez le nouveau sélecteur pour saisir `transparent` dans **Fond des lignes** (le champ accepte la saisie directe) et `#123456; background:red;` pour **Gradient 1**.
3. Enregistrez les réglages puis affichez un article utilisant les shortcodes du plugin.
4. Inspectez le bloc `<style id="jlg-frontend-inline-css">` dans l'entête de la page :
   - La variable `--jlg-table-row-bg-color` doit conserver la valeur sûre `transparent` sans ajouter d'autre règle.
   - La variable `--jlg-score-gradient-1` ne doit contenir aucun morceau comme `background:red;` et revient à la couleur par défaut du plugin (la valeur malicieuse est neutralisée).
5. Réinitialisez ensuite les couleurs avec des valeurs hexadécimales légitimes pour confirmer que l'affichage redevient normal.

## Frequently Asked Questions

### Comment personnaliser les catégories de notation ?

Allez dans Notation - JLG > Réglages et modifiez les libellés des 6 catégories selon vos besoins.

### Le plugin est-il compatible avec mon thème ?

Le plugin est conçu pour être compatible avec tous les thèmes WordPress standards. Des options de personnalisation CSS sont disponibles.

### Puis-je désactiver certains modules ?

Oui, vous pouvez activer/désactiver individuellement : notation utilisateurs, taglines, animations, schema SEO.

### Comment obtenir une clé API RAWG ?

Créez un compte gratuit sur [rawg.io/apidocs](https://rawg.io/apidocs) et copiez votre clé dans les réglages du plugin.

## Screenshots

1. Bloc de notation principal avec barres de progression
2. Interface d'administration - métabox de notation
3. Fiche technique et points forts/faibles
4. Tableau récapitulatif des tests
5. Réglages et personnalisation
6. Widget derniers tests

## Changelog

### 5.0
- Refactorisation complète du code
- Architecture modulaire optimisée
- Performance améliorée
- Interface admin modernisée
- Sécurité renforcée
- Support PHP 7.4+

### 4.0
- Version initiale publique

## Upgrade Notice

### 5.0
Mise à jour majeure avec refactorisation complète. Sauvegardez avant mise à jour. Migration automatique des données.

---

**Note:** Ce fichier `README.md` est synchronisé avec `README.txt`. Pensez à mettre à jour les deux fichiers.

