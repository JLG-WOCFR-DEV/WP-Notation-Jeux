# AGENTS – WP-Notation-Jeux

## Portée
Ces règles couvrent l’intégralité du dépôt `WP-Notation-Jeux`, incluant le plugin `plugin-notation-jeux_V4`, sa documentation et ses assets front/back.

## Flux de travail et qualité de base
- Travaillez sur des branches dédiées et laissez une trace claire de vos intentions dans les messages de commit.
- Depuis `plugin-notation-jeux_V4/`, exécutez systématiquement `composer install` (si nécessaire) puis `composer test` et `composer cs` avant tout commit ; corrigez les écarts éventuels via `composer cs-fix` si besoin.​:codex-file-citation[codex-file-citation]{line_range_start=33 line_range_end=58 path=README.md git_url="https://github.com/JLG-WOCFR-DEV/WP-Notation-Jeux/blob/main/README.md#L33-L58"}​​:codex-file-citation[codex-file-citation]{line_range_start=31 line_range_end=34 path=plugin-notation-jeux_V4/composer.json git_url="https://github.com/JLG-WOCFR-DEV/WP-Notation-Jeux/blob/main/plugin-notation-jeux_V4/composer.json#L31-L34"}​
- Maintenez la cohérence documentaire : toute modification du `README.md` principal doit être répercutée dans `plugin-notation-jeux_V4/README.txt` et inversement.​:codex-file-citation[codex-file-citation]{line_range_start=10 line_range_end=53 path=README.md git_url="https://github.com/JLG-WOCFR-DEV/WP-Notation-Jeux/blob/main/README.md#L10-L53"}​
- Documentez les décisions structurantes (architecture, conventions de nommage, migrations) dans `docs/` pour faciliter la montée en compétence.

## Benchmark produit & feuille de route
- Avant d’ajouter ou de modifier une fonctionnalité, comparez l’expérience proposée avec au moins deux sites / plugins de notation professionnels (ex. IGN, GameSpot, OpenCritic). Relevez les écarts notables (fonctionnalités, vitesse d’usage, lisibilité) et consignez vos conclusions dans un fichier de notes sous `docs/benchmark-<AAA-MM-JJ>.md`. Basez vos priorités sur l’éventail de fonctionnalités décrites dans la présentation du plugin (shortcodes, widget, API RAWG, modules optionnels, etc.).​:codex-file-citation[codex-file-citation]{line_range_start=5 line_range_end=40 path=README.md git_url="https://github.com/JLG-WOCFR-DEV/WP-Notation-Jeux/blob/main/README.md#L5-L40"}​
- Évaluez à chaque itération si la proposition de valeur reste claire pour les rédactions (workflow de notation complet, taglines bilingues, etc.) et si des automatisations supplémentaires sont nécessaires.

## Tests automatisés & débogage
- Toute correction de bug commence par un test PHPUnit rouge dans `plugin-notation-jeux_V4/tests/` (utilisez la bootstrap légère pour stubber l’environnement WP). Ajoutez des cas de régression ciblant les shortcodes, blocs et helpers concernés avant d’appliquer un correctif.​:codex-file-citation[codex-file-citation]{line_range_start=1 line_range_end=120 path=plugin-notation-jeux_V4/tests/bootstrap.php git_url="https://github.com/JLG-WOCFR-DEV/WP-Notation-Jeux/blob/main/plugin-notation-jeux_V4/tests/bootstrap.php#L1-L120"}​
- Enrichissez la couverture dès qu’un nouveau flux métier apparaît : 
  - Shortcodes/blocs → tests de rendu et d’attributs.
  - Helpers et utils → tests unitaires isolés.
  - Migrations/options → tests de scénarios d’upgrade.
- Si vous introduisez des scripts ou composants JS, accompagnez-les d’un test Jest/Vitest (à créer) ou, a minima, d’un test PHP qui valide les attributs exposés côté serveur.

## Vérifications manuelles
- Pour chaque fonctionnalité front, suivez et mettez à jour les checklists manuelles de `docs/`. Complétez `docs/responsive-testing.md` quand un nouveau comportement responsive est introduit et assurez-vous que les scénarios décrits restent valides après vos changements.​:codex-file-citation[codex-file-citation]{line_range_start=1 line_range_end=21 path=plugin-notation-jeux_V4/docs/responsive-testing.md git_url="https://github.com/JLG-WOCFR-DEV/WP-Notation-Jeux/blob/main/plugin-notation-jeux_V4/docs/responsive-testing.md#L1-L21"}​
- Ajoutez une fiche de test manuel lorsque vous touchez à des flux critiques (Game Explorer, notation lecteurs, schémas SEO).

## UX / UI & accessibilité
- Lors de toute évolution d’interface, inspectez les assets sous `assets/css` et `assets/js` : gardez les animations, palettes et effets cohérents avec l’identité du plugin et respectez l’accessibilité (contraste, focus visible, navigation clavier).​:codex-file-citation[codex-file-citation]{line_range_start=26 line_range_end=32 path=README.md git_url="https://github.com/JLG-WOCFR-DEV/WP-Notation-Jeux/blob/main/README.md#L26-L32"}​​:codex-file-citation[codex-file-citation]{line_range_start=1 line_range_end=34 path=plugin-notation-jeux_V4/assets/css/blocks-editor.css git_url="https://github.com/JLG-WOCFR-DEV/WP-Notation-Jeux/blob/main/plugin-notation-jeux_V4/assets/css/blocks-editor.css#L1-L34"}​
- Vérifiez que chaque composant interactif expose des attributs ARIA pertinents, propose un focus management explicite et fonctionne sans souris.
- Lorsque vous introduisez une nouvelle option visuelle, fournissez :
  - Une prévisualisation dans l’éditeur Gutenberg.
  - Des captures « mode clair/sombre » dans les notes de version.
  - Des guidelines d’usage (ex. jeux d’icônes, limites d’animation) dans `docs/`.

## Gutenberg & parité front/éditeur
- Chaque bloc inscrit sous `assets/js/blocks/` doit conserver une parité stricte entre l’éditeur et le front. Utilisez les composants `BlockPreview`, `InspectorControls` et le CSS d’éditeur pour s’assurer que l’aperçu reflète fidèlement le rendu final.​:codex-file-citation[codex-file-citation]{line_range_start=1 line_range_end=144 path=plugin-notation-jeux_V4/assets/js/blocks/rating-block.js git_url="https://github.com/JLG-WOCFR-DEV/WP-Notation-Jeux/blob/main/plugin-notation-jeux_V4/assets/js/blocks/rating-block.js#L1-L144"}​​:codex-file-citation[codex-file-citation]{line_range_start=1 line_range_end=34 path=plugin-notation-jeux_V4/assets/css/blocks-editor.css git_url="https://github.com/JLG-WOCFR-DEV/WP-Notation-Jeux/blob/main/plugin-notation-jeux_V4/assets/css/blocks-editor.css#L1-L34"}​
- Testez visuellement :
  - Vue éditeur (mode par défaut + mode sans animation).
  - Vue front (thèmes clair/sombre, options de mise en page, traductions).
  - Variantes responsive et comportement des panneaux de filtres.
- Documentez tout nouvel attribut de bloc dans `README.md` et, si applicable, dans le manuel utilisateur du shortcode correspondant.

## Accessibilité & performance
- Effectuez un audit rapide (Lighthouse ou équivalent) lors de l’ajout de composants lourds (grids, filtres). Ciblez 90+ sur les sections Accessibilité et Performance.
- Optimisez les assets (taille des bundles JS/CSS, lazy-loading) tout en conservant les hooks et helpers existants.

## Localisation & internationalisation
- Gardez les chaînes prêtes à la traduction (`__`, `_x`, etc.) et synchronisez `languages/notation-jlg.pot` dès qu’un texte évolue.
- Ajoutez des tests pour garantir que les fonctions helper acceptent les locales variées (formats de date/monnaie si introduits).

## Livraison & validation finale
- Avant toute PR :
  1. Résumez l’impact utilisateur, les tests automatisés/manuels exécutés et les points de comparaison avec les apps pro.
  2. Joignez, si pertinent, des captures Gutenberg et front pour illustrer les changements.
  3. Vérifiez que la désinstallation reste propre (`uninstall.php`) lorsque de nouvelles options sont créées.

Respectez ces garde-fous pour conserver une expérience de niveau professionnel et un pipeline de qualité fiable.
