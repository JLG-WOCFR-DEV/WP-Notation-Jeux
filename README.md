# Notation JLG v5.0

Le dépôt regroupe la version 5.0 du plugin WordPress **Notation JLG**, un système complet de notation destiné aux sites de tests de jeux vidéo. Il fournit un rendu professionnel pour vos reviews avec des shortcodes prêts à l'emploi, un widget et des helpers PHP pour intégrer la note partout dans votre thème.

## Présentation rapide
- **Fonctionnalités clés :** 6 catégories de notes personnalisables, bloc complet avec points forts/faibles, fiche technique, widget des derniers tests, tableau récapitulatif, prise en charge de la notation des lecteurs, intégration de l'API RAWG et schema.org pour les rich snippets.
- **Prérequis techniques :** WordPress 5.0 minimum et PHP 7.4 ou supérieur, vérifiés automatiquement à l’activation du plugin.
- **Architecture :** le cœur du plugin charge dynamiquement les composants admin et front-office, inclut un widget et expose des fonctions helper globales (`jlg_notation()`, `jlg_get_post_rating()`, `jlg_display_post_rating()`).

## Installation et configuration initiale
1. **Installer le plugin** depuis ce dépôt (copier `plugin-notation-jeux_V4` dans `wp-content/plugins/`) puis l’activer depuis le menu *Extensions* de WordPress.
2. **Remplir les metaboxes** dédiées aux notes et aux détails du test dans l’éditeur d’articles : les six catégories sont notées sur 10 et la metabox principale capture fiche technique, plateformes, taglines bilingues, points forts/faibles, etc.
3. **Configurer l’onglet Réglages** (`Notation – JLG > Réglages`) pour ajuster libellés, présentation de la note globale, thèmes clair/sombre, couleurs sémantiques, effets neon/pulsation et modules optionnels.
4. **Gérer les plateformes** dans l’onglet dédié afin d’ajouter, trier, supprimer ou réinitialiser la liste proposée dans les metaboxes.
5. **Saisir la clé RAWG (facultatif)** dans la section *API* des réglages pour activer le remplissage automatique des données de jeu.

## Utilisation au quotidien
- **Shortcodes principaux** :
  - `[jlg_bloc_complet]` (alias `[bloc_notation_complet]`) pour afficher en une seule fois notation, points forts/faibles et tagline avec de nombreux attributs (`post_id`, `style`, `couleur_accent`, etc.).
  - `[bloc_notation_jeu]`, `[jlg_fiche_technique]`, `[jlg_points_forts_faibles]`, `[tagline_notation_jlg]`, `[notation_utilisateurs_jlg]`, `[jlg_tableau_recap]` pour construire des mises en page modulaires.
- **Widget « Derniers tests »** : activé automatiquement, il peut être ajouté depuis *Apparence > Widgets* grâce au registre `JLG_Latest_Reviews_Widget`.
- **Fonctions helper** :
  - `jlg_get_post_rating()` retourne la moyenne /10 pour un article donné ; `jlg_display_post_rating()` affiche la note formatée ; `jlg_display_thumbnail_score()` injecte la note dans vos templates de vignettes.
- **Templates front** : surchargez les fichiers du dossier [`templates/`](plugin-notation-jeux_V4/templates) ou utilisez les gabarits d’admin disponibles dans [`admin/templates/`](plugin-notation-jeux_V4/admin/templates) pour personnaliser le rendu des blocs et onglets.

## Personnalisation avancée
- **Thèmes clair/sombre et palettes complètes** pour adapter l’apparence du bloc de notation, y compris les couleurs sémantiques des notes.
- **Effets Glow / Neon** configurables pour les modes texte ou cercle (intensité, pulsation, couleur dynamique ou fixe).
- **Modules optionnels** : activer/désactiver la notation utilisateurs, les taglines, les animations de barres ou le schema SEO JSON-LD directement depuis l’onglet Réglages.
- **CSS personnalisé** et réglages précis pour le tableau récapitulatif ou les vignettes (espacements, bordures, alternance de lignes).
- **Notation des lecteurs** : personnalisez couleurs et textes du module dédié lorsque `Notation utilisateurs` est actif.

## Ressources développeur
- **Composer** : `composer.json` définit PHP >=7.4 et fournit les scripts `composer test`, `composer cs`, `composer cs-fix` pour lancer PHPUnit et PHPCS (WPCS).
- **Gestion des traductions** : le modèle `languages/notation-jlg.pot` est prêt pour générer les fichiers `.po/.mo`.
- **Routine de désinstallation** : `uninstall.php` supprime proprement options, métadonnées et transients si l’utilisateur choisit de purger les données lors de la désactivation définitive.
- **Assets & templates** :
  - [`assets/`](plugin-notation-jeux_V4/assets) regroupe styles, scripts et images front/back.
  - [`includes/`](plugin-notation-jeux_V4/includes) contient le cœur PHP (helpers, frontend, admin, utils).
  - [`admin/templates/`](plugin-notation-jeux_V4/admin/templates) centralise les vues des onglets d’administration.

## Intégration continue
Un workflow GitHub Actions (`CI`) s’exécute sur chaque `push` et `pull_request`. Il installe les dépendances Composer du dossier [`plugin-notation-jeux_V4`](plugin-notation-jeux_V4), puis enchaîne deux commandes clés pour garantir la qualité :

- `composer test` pour lancer la suite PHPUnit.
- `composer cs` pour appliquer les vérifications de style via PHPCS.

Assurez-vous que ces commandes passent en local avant de soumettre une contribution.

## Synchronisation du README WordPress
La documentation WordPress (`plugin-notation-jeux_V4/README.txt`) doit rester alignée avec ce README Markdown. Toute modification de l’un doit être répercutée sur l’autre pour garantir des informations cohérentes dans l’interface WordPress.org.

Bonne contribution ! Pensez à suivre les scripts Composer avant toute PR et à conserver la parité de contenu entre les deux README.

### Vérification du style
- Exécutez `composer install` pour récupérer les dépendances de développement (WordPress Coding Standards).
- Lancez `composer cs` avant chaque commit afin de vérifier que vos modifications respectent le standard défini dans `phpcs.xml.dist`.
- Si besoin, `composer cs-fix` peut corriger automatiquement une partie des avertissements avant un passage manuel.
