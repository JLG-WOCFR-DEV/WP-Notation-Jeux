# Benchmark — 2026-09-15 (Phase 2 WP 7.1 / charte admin)

Comparaison ciblée avant l’alignement Gutenberg iframe + chrome wp-admin de Notation JLG.

## Références

- **IGN** — fiche test : note globale très lisible, métadonnées (plateformes, date) collées au contenu, pas de chrome d’édition parallèle.
- **OpenCritic** — agrégat + contexte (nombre de tests, tendance) sans détourner les contrôles natifs de la page.

## Écarts relevés (avant correctif)

- Aperçu Gutenberg : CSS front chargé via `enqueue_block_editor_assets` (document parent). En WP 7.1 l’éditeur d’articles est toujours iframé → preview de notation absente ou fausse, contrairement aux fiches IGN/OpenCritic toujours stylées dans le document du contenu.
- Blocs encore en `apiVersion: 2` avec repli `wp.editor` : dette iframe, pas un plus produit.
- Admin : ossature WP déjà là (`wrap`, `h1`, `nav-tab`, Settings API) mais carte custom + restyle de `.form-table` / `.button-primary` s’éloignent du modèle Motion / écrans Réglages WP.

## Décision

Priorité à la parité éditeur/front (CSS canvas + pas de JS votes/animations dans l’iframe) puis chrome admin natif. Pas de nouvelle brique métier type IGN/OpenCritic dans cette itération.
