# Plan de livraison – Bloc Gutenberg «\u202fComparatif plateformes\u202f»

## Objectifs

- Offrir un bloc `notation-jlg/platform-breakdown` fournissant une alternative visuelle au shortcode `[jlg_platform_breakdown]`.
- Garantir la parité front/éditeur : aperçus Gutenberg cohérents, messages vides, badge «\u202fMeilleure expérience\u202f» rendus de façon identique.
- Durcir la sanitisation des contenus injectés par filtres tiers (`jlg_platform_breakdown_entries`) sans régresser la personnalisation.

## Backlog technique

1. **Helper de données**
   - Exposer un helper PHP `JLG\Helpers\PlatformBreakdown::serialize_for_block( $post_id )` retournant un tableau normalisé (plateforme, recommandation, performance, badge, commentaire).
   - Ajouter un `wp_json_encode()` avec `wp_kses_post()` sur `comment` et `performance` après passage des filtres.
   - Couvrir ce helper par des tests unitaires (`tests/HelpersPlatformBreakdownTest.php`).

2. **Compatibilité Gutenberg**
   - Étendre `PlatformBreakdown::resolve_target_post_id()` pour accepter les aperçus REST :
     - Si `REST_REQUEST` est défini et qu'un `postId` est fourni (bloc), le privilégier.
     - Fallback sur `post` global si présent.
     - Couvrir ce cas par un test (`ShortcodePlatformBreakdownTest::test_renders_in_rest_preview`).
   - Ajouter un endpoint `notation-jlg/v1/platform-breakdown/<post_id>` si nécessaire pour l'éditeur (utilise le helper JSON).

3. **Bloc Gutenberg**
   - Créer `assets/js/blocks/platform-breakdown/` avec :
     - `edit.js` utilisant `useEntityProp` ou un fetch du helper REST pour récupérer les données.
     - `InspectorControls` pour configurer titre personnalisé, message vide, affichage badge, tri (ordre original vs alphabétique).
     - `BlockControls` proposant un bouton de rafraîchissement (re-fetch) quand la metabox change.
   - Rendre la grille dans l'éditeur avec les mêmes classes CSS que le shortcode.
   - Ajouter styles dédiés `assets/css/blocks-editor-platform-breakdown.css`.

4. **Templates & sanitisation**
   - Mettre à jour `templates/shortcode-platform-breakdown.php` pour appliquer `wp_kses_post()` et une `mb_substr()` (ex. 140 caractères) post-filtre.
   - Documenter les hooks `jlg_platform_breakdown_entries` et `jlg_platform_breakdown_empty_message`.

5. **Tests & QA**
   - Tests PHPUnit couvrant :
     - Résolution d'ID en REST.
     - Sanitisation des champs quand un filtre injecte HTML.
     - Helper JSON.
   - Tests manuels :
     - Aperçu Gutenberg (avec/ sans données, mode sombre, `prefers-reduced-motion`).
     - Responsive (≤768 px, >1024 px) ; mise à jour de `docs/responsive-testing.md`.
   - Ajouter un test de screenshot manuel dans `docs/` avec captures clair/sombre.

## Dépendances & risques

- **Dépendances** : refactoring de sanitisation (`Admin\Settings::sanitize_options()`), endpoints REST existants (`notation-jlg/v1`).
- **Risques** : divergence entre données metabox et REST si le cache n'est pas invalidé ; prévoir un `wp_send_json_success()` recalculant les données à chaque fetch.
- **Mitigations** :
  - Hook `save_post` purge le cache du helper.
  - Documenter le flux dans `docs/review-status-automation.md` si le statut éditorial est utilisé pour filtrer l'affichage.

## Livrables de documentation

- Mise à jour de `README.md` et `README.txt` (section «\u202fBlocs Gutenberg\u202f» et «\u202fComparatif plateformes\u202f»).
- Ajout d'un tutoriel rapide dans `docs/` expliquant l'utilisation du bloc (vidéo GIF ou captures).
- Mise à jour de `docs/product-roadmap/2025-10-roadmap.md` : critères d'acceptation du bloc et tests associés.

