# Code review – 2025-10-16

## Zone auditée

- `includes/Shortcodes/PlatformBreakdown.php`
- `templates/shortcode-platform-breakdown.php`
- `includes/Admin/Metaboxes.php` (section « Comparatif plateformes »)
- `includes/Helpers.php` (`get_platform_breakdown_for_post()` et helpers associés)
- `tests/HelpersPlatformBreakdownTest.php`
- `tests/ShortcodePlatformBreakdownTest.php`

## Constats principaux

1. **Comportement vide dans l’éditeur Gutenberg** : `PlatformBreakdown::resolve_target_post_id()` retourne `0` lorsque le shortcode est prévisualisé via REST (mode Aperçu / block editor), car `is_singular()` renvoie `false`. Résultat : le rendu du bloc dynamique ou d’un shortcode inséré via Gutenberg est entièrement vide, ce qui empêche la rédaction de valider sa mise en page dans l’éditeur. ✅ Reproduit en simulant un rendu via `do_shortcode()` avec un contexte REST (défini `REST_REQUEST` à `true`).
2. **Absence de garde sur les chaînes longues en sortie** : le template limite correctement la longueur via la metabox, mais si un filtre tiers (`jlg_platform_breakdown_entries`) renvoie des chaînes plus longues ou du HTML, aucun `wp_kses_post()` n’est appliqué dans la vue. Les tests PHPUnit couvrent l’input depuis la metabox, pas la voie « filter ». ⚠️ Risque d’injection HTML involontaire si un partenaire enrichit la description.
3. **Manque de bloc Gutenberg dédié** : la roadmap évoque déjà un futur bloc, mais rien ne prépare la parité front/éditeur (pas de helper JSON, pas de prévisualisation). Les rédactions devront continuer à utiliser un shortcode brut pour le comparatif, ce qui va à l’encontre des guidelines UI/Gutenberg définies dans `AGENTS.md`.

## Actions recommandées

- Ajuster `resolve_target_post_id()` pour tolérer les appels depuis l’éditeur (ex. ignorer `is_singular()` lorsque `REST_REQUEST` est défini et qu’un post global est disponible, ou permettre l’attribut `post_id` auto-injecté par le bloc). Ajouter un test couvrant ce cas.
- Sécuriser la vue en normalisant les entrées post-filtre : `wp_kses_post()` (ou `esc_html()` fallback) sur `performance` et `comment` après application des filtres, et limiter la longueur côté sortie pour éviter la casse responsive.
- Planifier un bloc Gutenberg `notation-jlg/platform-breakdown` : props exposées depuis PHP (helpers JSON), attributs pour titre/message vide/badge, prévisualisation fidèle et tests de parité. L’aligner avec la checklist responsive existante.

## Analyse détaillée

| Cas | Étapes de reproduction | Résultat attendu | Résultat observé |
| --- | --- | --- | --- |
| Aperçu Gutenberg vide | 1. Créer un post avec des données plateforme. 2. Ajouter `[jlg_platform_breakdown]` dans un bloc « Shortcode ». 3. Cliquer sur *Prévisualiser* dans l’éditeur. | Tableau identique au front. | Sortie vide car `resolve_target_post_id()` retourne `0` sous `REST_REQUEST`. |
| Filtre tiers bavard | 1. Ajouter un filtre `jlg_platform_breakdown_entries` qui injecte `<script>alert(1)</script>`. 2. Rendre le shortcode. | La chaîne est échappée et non exécutée. | Le script est rendu tel quel (risque XSS). |
| Bloc inexistant | 1. Chercher un bloc dédié « Comparatif plateformes » dans l’éditeur. | Bloc dynamique disponible dans la catégorie Notation JLG. | Aucun bloc, seule l’insertion du shortcode est possible. |

## Plan d’implémentation

- **Compatibilité REST** : Introduire un nouveau chemin dans `PlatformBreakdown::resolve_target_post_id()` acceptant l’attribut `post_id` du bloc et utilisant `get_post()` quand `REST_REQUEST` est vrai. Couvrir ce comportement dans `ShortcodePlatformBreakdownTest` via un stub REST.
- **Sanitisation renforcée** : Déplacer la logique de formatage dans un helper dédié et appliquer `wp_kses_post()` + troncature après les filtres. Ajouter un test régressif injectant du HTML hostile.
- **Bloc Gutenberg** : S’appuyer sur le plan détaillé dans [`platform-breakdown-block-plan.md`](platform-breakdown-block-plan.md) pour livrer un bloc miroir du shortcode. Ce bloc doit appeler un helper JSON (exposé via endpoint) et reprendre les styles front pour garantir la parité.

## Tests à introduire

- PHPUnit :
  - `ShortcodePlatformBreakdownTest::test_renders_in_rest_context()` (prévisualisation REST, ID injecté).
  - `HelpersPlatformBreakdownTest::test_filters_are_sanitized()` (filtre injectant du HTML).
- Tests manuels :
  - Vérifier le bloc Gutenberg en mode clair/sombre et `prefers-reduced-motion`.
  - Contrôler la grille responsive ≤ 480 px et ≥ 1280 px (cf. section dédiée à ajouter dans `docs/responsive-testing.md`).
- QA documentaire :
  - Ajouter captures d’écran (éditeur/front) dans un futur mémo `docs/platform-breakdown-block-preview.md`.

## Suivi

- Mettre à jour la roadmap (`docs/product-roadmap/2025-10-roadmap.md`) pour intégrer les chantiers ci-dessus et informer les équipes produit/QA.
- Étendre la couverture de tests (`ShortcodePlatformBreakdownTest`) pour vérifier le comportement REST/Gutenberg et la sanitisation post-filtre.
