# Benchmark – 2024-06-02

> _Mise à jour 2025-10-14 : ce document a été relu lors de l’audit des fichiers Markdown. Les actions détaillées sont centralisées dans [`documentation-audit-2025-10-14.md`](documentation-audit-2025-10-14.md)._

## Références étudiées
- **IGN** : hub de tests avec filtres multiplateformes, segmentation par genre et indicateurs d'évolution des notes.
- **GameSpot** : fiches détaillées mêlant critique, notation lecteur et recommandations croisées (« Best Of », jeux similaires) avec mise à jour continue.

## Synthèse des écarts majeurs
| Axe | État du plugin | Référence pro | Risque produit |
| --- | --- | --- | --- |
| Insights de notation | Agrégats statiques (moyenne, médiane, histogramme) sans tendance ni comparaison temporelle dans `Helpers::get_posts_score_insights()` et le rendu `ScoreInsights`. 【F:includes/Helpers.php†L2133-L2236】【F:includes/Shortcodes/ScoreInsights.php†L20-L83】 | IGN suit l'évolution des notes par plateforme et sur 30/90 jours ; GameSpot met en avant les variations entre rédactions et lecteurs. | Décisions éditoriales prises avec une vision partielle ; difficile de repérer les baisses de qualité.
| Découverte / Game Explorer | Filtres unitaires (lettre, catégorie, plateforme, etc.) et tri limité aux combinaisons pré-définies dans `GameExplorer`. 【F:includes/Shortcodes/GameExplorer.php†L16-L120】【F:includes/Shortcodes/GameExplorer.php†L212-L311】 | IGN propose filtres combinables, favoris et tags personnalisés ; GameSpot enregistre des vues (Most Popular, Upcoming) et recommandations croisées. | Les rédactions peinent à reproduire des parcours éditoriaux avancés et à segmenter l'audience.
| Modération des votes | API REST se limite à bannir/autoriser un jeton sans workflow (raisons, historique, seuils) dans `Frontend::register_user_rating_rest_routes()` et `rest_handle_user_rating_token_status()`. 【F:includes/Frontend.php†L1056-L1178】 | IGN/GameSpot intègrent modération multi-rôle, alertes automatiques et règles anti-abus (scores suspects, IP). | Gestion communautaire manuelle et lente, risque de dérive des votes.

## Fonctions à renforcer en priorité
### 1. `Helpers::get_posts_score_insights()`
- Ajouter des séries temporelles (rolling 7/30 jours) et une comparaison rédactions vs lecteurs pour refléter les tableaux dynamiques des concurrents. 【F:includes/Helpers.php†L2133-L2236】
- Étendre `build_score_distribution()` pour générer 10 segments (ou un mode 0-100) et prendre en charge les percentiles utilisés par IGN. 【F:includes/Helpers.php†L2238-L2299】

### 2. `Shortcodes\ScoreInsights::render()`
- Préparer le contexte avec plusieurs périodes (N-1, N-2) et exposer des deltas prêts à être consommés par le template ou Gutenberg. 【F:includes/Shortcodes/ScoreInsights.php†L24-L83】
- Prévoir un mode API/headless (JSON) afin d'alimenter des dashboards internes comme ceux de GameSpot.

### 3. `Shortcodes\GameExplorer` (contexte + filtres)
- Permettre la sélection multiple (plates-formes, genres) et la sauvegarde de presets éditoriaux dans `get_default_atts()` / `normalize_filters()`. 【F:includes/Shortcodes/GameExplorer.php†L212-L284】
- Exposer des hooks pour injecter des jeux similaires (« More Like This ») et des vues dynamiques (upcoming, top series) à la manière d'IGN/GameSpot. 【F:includes/Shortcodes/GameExplorer.php†L272-L311】

### 4. `Frontend::register_user_rating_rest_routes()`
- Ajouter des statuts intermédiaires (sous surveillance, en litige) et un historique d'actions afin de refléter un vrai back-office de modération. 【F:includes/Frontend.php†L1056-L1178】
- Intégrer des heuristiques (seuil d'alertes, ratio bannissements) et logs pour audit.

## Plan de débogage et de tests
### Tests automatisés
- **Nouveau** `HelpersScoreInsightsAggregationTest` : vérifie l'agrégation par plateforme, la mise en forme i18n et les valeurs de distribution pour préparer l'évolution analytics. 【F:tests/HelpersScoreInsightsAggregationTest.php†L1-L104】
- Étendre ensuite les tests `ShortcodeScoreInsightsTemplateTest` pour couvrir l'affichage de deltas temporels lorsque les données seront disponibles. 【F:tests/ShortcodeScoreInsightsTemplateTest.php†L1-L44】

### Tests manuels suggérés
1. Vérifier dans Game Explorer la combinaison de plusieurs filtres (lettre + plateforme + développeur) et mesurer le temps de réponse vs IGN.
2. Simuler un pic de votes lecteurs (50+ en 5 minutes) et observer si l'API REST actuelle permet une modération efficace.
3. Comparer la mise en avant des jeux similaires avec GameSpot pour identifier les données manquantes (tags, séries, trending).
