# Code review – 2025-10-15

## Zone auditée

- `includes/Shortcodes/GameExplorer.php`
- `templates/shortcode-game-explorer.php`
- `assets/js/game-explorer.js`
- `assets/css/game-explorer.css`
- Tests PHPUnit associés au Game Explorer.

## Constats principaux

1. **UX filtre score absent** : les benchmarks (IGN/OpenCritic) exposent un seuil de note, absent ici. Décision : ajouter un filtre "Note minimale" côté PHP/JS pour améliorer le tri éditorial sans perdre la persistance GET.
2. **Cohérence cache & snapshot** : vérification du cache de requête (`QUERY_CACHE_KEY_PREFIX`) et des snapshots. Le nouveau filtre devait incrémenter la version de cache (`score_min` ajouté au payload) pour éviter les collisions.
3. **Synchronisation documentations** : README et README.txt ne mentionnaient pas `note_min` ni le filtre `score`. Mise à jour effectuée + checklist responsive enrichie.
4. **Test coverage** : ajout d'un test Ajax pour `score` et d'un test de template dédié afin de verrouiller le rendu (sélecteur, hints, config exportée).

## Actions complémentaires proposées

- Centraliser le formatage des valeurs de score côté front/back dans un helper partagé afin d'éviter les divergences d'affichage (actuellement logique dupliquée entre PHP et JS).
- Étudier l'ajout d'un slider de période (comme OpenCritic) pour un filtrage temporel plus fin.

## Suivi 2025-10-16

- Correction appliquée sur l'AJAX du Game Explorer pour renvoyer l'état `score` et éviter la régression signalée par `FrontendGameExplorerAjaxTest`.
- Mise à jour des valeurs par défaut injectées dans les templates afin d'exposer `scores_list`, `scores_meta` et les autres listes de filtres côté PHP, ce qui garantit l'affichage du sélecteur « Note minimale » dans `shortcode-game-explorer`.
