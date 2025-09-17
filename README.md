# Notation JLG v5.0

Le dépôt contient la version 5.0 du plugin **Notation JLG**, un système complet de notation dédié aux tests de jeux vidéo sur WordPress. Le plugin offre une notation sur plusieurs critères, une mise en avant des points forts/faibles, des fiches techniques automatisables et des intégrations SEO pour présenter vos reviews avec un rendu professionnel.

## Présentation rapide

- **Fonctionnalités clés** : bloc de notation combiné, fiches techniques, points forts/faibles, tagline bilingue, tableau récapitulatif, widget « derniers tests », notation utilisateurs et support de l'API RAWG pour préremplir vos contenus.
- **Personnalisation visuelle** : thèmes clair/sombre, couleurs d'accentuation, effet « glow », dégradés et CSS personnalisé.
- **Compatibilité SEO** : génération de données structurées schema.org.
- **Prérequis** : WordPress 5.0+ et PHP 7.4+ (testé jusqu'à WordPress 6.4).

## Installation et activation

1. Téléchargez l'archive du plugin puis décompressez-la.
2. Copiez le dossier `plugin-notation-jeux` dans `wp-content/plugins/` sur votre site WordPress.
3. Activez « Notation – JLG » depuis **Extensions > Extensions installées**.
4. Rendez-vous dans le menu d'administration **Notation – JLG** pour effectuer la configuration initiale :
   - **Metaboxes** : sur vos articles de tests, remplissez la metabox de notation (catégories, sous-notes, résumé, points forts/faibles, tagline).
   - **Onglet Réglages** : définissez les libellés de catégories, choisissez le thème (clair/sombre), les couleurs, les dégradés et activez/désactivez les modules (notation utilisateurs, animations, schema SEO, tagline, etc.).
   - **Plateformes** : gérez la liste des plateformes jouables via l'onglet dédié (ajout, tri, suppression, réinitialisation).
   - **Clé RAWG (optionnelle)** : saisissez votre clé API dans les réglages pour alimenter automatiquement les fiches techniques.

## Utilisation au quotidien

### Shortcodes disponibles

- `[jlg_bloc_complet]` (alias `[bloc_notation_complet]`) : bloc tout-en-un regroupant notation, points forts/faibles et tagline avec options `post_id`, `style` (`moderne`, `classique`, `compact`), `afficher_notation`, `afficher_points`, `afficher_tagline`, `couleur_accent`, `titre_points_forts`, `titre_points_faibles`.
- `[bloc_notation_jeu]` : bloc de notation principal (score global et sous-notes).
- `[jlg_fiche_technique]` : fiche technique du jeu (développeur, éditeur, plateformes, etc.).
- `[tagline_notation_jlg]` : phrase d'accroche bilingue.
- `[jlg_points_forts_faibles]` : liste des points positifs et négatifs.
- `[notation_utilisateurs_jlg]` : module de notation communautaire.
- `[jlg_tableau_recap]` : tableau récapitulatif de tous vos tests avec tri et filtrage.

### Widget

Un widget « Derniers tests notés » est disponible dans l'administration WordPress pour mettre en avant vos dernières publications notées.

### Fonctions helpers

- `jlg_get_post_rating( $post_id )` pour récupérer la note moyenne d'un test.
- `jlg_display_thumbnail_score( $post_id, $echo = true )` pour afficher la vignette avec le score dans vos templates.

### Templates

Les rendus front (shortcodes, widget, résumé, etc.) sont configurables via les fichiers du dossier [`templates/`](plugin-notation-jeux_V4/templates) et les écrans d'administration s'appuient sur [`admin/templates/`](plugin-notation-jeux_V4/admin/templates).

## Personnalisation avancée

- **Thèmes clair/sombre** : choisissez le mode par défaut et autorisez le basculement utilisateur.
- **Couleurs et effets** : personnalisez couleurs d'accent, dégradés, effets « glow » des scores et options du tableau récapitulatif.
- **Modules optionnels** : activez/désactivez la notation utilisateurs, les taglines, les animations et le balisage schema.org selon vos besoins.
- **CSS personnalisé** : injectez vos propres règles depuis l'onglet Réglages tout en respectant les protections intégrées contre le CSS invalide.

## Outils pour développeurs

- [`composer.json`](plugin-notation-jeux_V4/composer.json) : dépendances PHP 7.4+, PHPUnit, WordPress Coding Standards et installeur phpcs.
- Scripts Composer : `composer test`, `composer cs` et `composer cs-fix` pour lancer respectivement la suite de tests, l'analyse de code et les corrections automatiques.
- [`uninstall.php`](plugin-notation-jeux_V4/uninstall.php) : suppression propre des données lors de la désinstallation.
- [`languages/notation-jlg.pot`](plugin-notation-jeux_V4/languages/notation-jlg.pot) : modèle de traduction pour localiser le plugin.
- Assets front/back-office disponibles dans [`assets/`](plugin-notation-jeux_V4/assets) et partials d'administration dans [`admin/templates/partials`](plugin-notation-jeux_V4/admin/templates/partials).

## Synchronisation de la documentation

Le fichier WordPress [`plugin-notation-jeux_V4/README.txt`](plugin-notation-jeux_V4/README.txt) doit rester synchronisé avec [`plugin-notation-jeux_V4/README.md`](plugin-notation-jeux_V4/README.md) ainsi qu'avec le présent `README.md`. Toute mise à jour de la documentation doit être répliquée dans les trois emplacements.

