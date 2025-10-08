# Audit de la documentation Markdown – 14 octobre 2025

Ce mémo centralise la revue des fichiers Markdown du dépôt et synthétise les fonctionnalités à livrer, les refactorings planifiés ainsi que les besoins de tests/documents complémentaires.

## Synthèse globale
- La vision produit est alignée autour d’un **parcours review premium** (badge éditorial, verdict enrichi, statut automatisé) mais nécessite encore l’ajout d’un comparateur plateformes, d’un module deals et de KPIs plus dynamiques.
- Les **benchmarks 2024-2025** convergent vers les mêmes attentes : mettre en avant les écarts lecteurs vs rédaction, offrir des filtres combinés dans Game Explorer et proposer des intégrations partenaires (API REST, exports CSV, widgets affiliés).
- Les **revues de code d’octobre 2025** pointent trois chantiers structurants : découper la logique vidéo (`VideoEmbedFactory`), segmenter `Frontend` en services ciblés et introduire un schéma déclaratif pour la sanitisation des réglages.
- Les **guides fonctionnels/tests manuels** restent pertinents mais doivent intégrer les nouvelles options (statut automatisé, prévisualisation des blocs, overlay Game Explorer) et prévoir des checklists responsive + accessibilité actualisées.

## Priorités transverses identifiées
### Backlog fonctionnalités
- Finaliser le sous-bloc « Verdict » et le mode « Review en cours » pour les blocs/shortcodes tout-en-un (`[jlg_bloc_complet]`) en s’appuyant sur les recommandations des benchmarks récents (cf. [`benchmark-2025-10-05.md`](benchmark-2025-10-05.md) et [`product-roadmap/2025-10-roadmap.md`](product-roadmap/2025-10-roadmap.md)).
- Livrer le **comparateur multi-plateformes** (`jlg_platform_breakdown`) et le **widget Deals & disponibilités**, récurrents dans l’ensemble des benchmarks 2025 (cf. [`benchmark-2025-02-14.md`](benchmark-2025-02-14.md), [`benchmark-2025-10-05.md`](benchmark-2025-10-05.md), [`benchmark-2025-10-10.md`](benchmark-2025-10-10.md)).
- Étendre `Score Insights` avec badges d’écart, tendances temporelles et segmentation rédaction/lecteurs, comme recommandé par les benchmarks du 2 juin 2024 et du 9 octobre 2025 (cf. [`benchmark-2024-06-02.md`](benchmark-2024-06-02.md), [`benchmark-2025-10-09.md`](benchmark-2025-10-09.md)).
- Mettre en place un **assistant d’onboarding** (guide en 4 étapes) et des options de **notation multi-contributeurs**, listés comme pistes d’amélioration stratégiques dans le README et la documentation WordPress.

### Refactorings & qualité du code
- Ajouter le garde `ABSPATH`, un filtre d’enregistrement des fournisseurs et des tests unitaires dédiés pour l’architecture vidéo (cf. [`code-review-2025-10-13.md`](code-review-2025-10-13.md)).
- Segmenter `Frontend` en registres/services dédiés et refondre `Admin\Settings::sanitize_options()` autour d’un schéma déclaratif pour clarifier la logique de validation (cf. [`code-review-2025-10-12.md`](code-review-2025-10-12.md)).
- Finaliser le caching du widget « Derniers Tests », documenter les hooks d’invalidation et renforcer les tests d’intégration pour garantir un rendu performant (cf. [`code-review-2025-10-08.md`](code-review-2025-10-08.md)).

### Tests et documentation
- Compléter `docs/responsive-testing.md` avec des scénarios Game Explorer + widget, en cohérence avec les actions recommandées le 8 octobre 2025.
- Documenter l’audit Lighthouse/contraste lors de l’ajout de nouveaux presets UI (`ui-presets-prescription-2025-10-11.md`) et intégrer des captures avant/après lorsque les presets seront implémentés.
- Alignement systématique entre `README.md`, `plugin-notation-jeux_V4/README.md` et `plugin-notation-jeux_V4/README.txt` pour refléter les mêmes pistes d’amélioration et la roadmap à jour.
- Ajouter un plan d’implémentation dédié au bloc « Comparatif plateformes » (`platform-breakdown-block-plan.md`) et référencer la check-list responsive associée.

## Suivi par groupe de documents
### READMEs & documentation WordPress
- `README.md`, `plugin-notation-jeux_V4/README.md` et `plugin-notation-jeux_V4/README.txt` listent déjà les modules livrés (shortcodes, Score Insights, automation) ainsi que les pistes d’amélioration prioritaires (assistant onboarding, multi-contributeurs, timeline patchs, exports/API, collaboration temps réel, dashboard analytics). Ces éléments restent cohérents mais doivent intégrer les futures livraisons (comparateur plateformes, deals, onboarding guidé).

### Benchmarks (2024-2025)
- `benchmark-2024-05-19.md` : badge éditorial, affichage simultané moyenne lecteurs + delta.
- `benchmark-2024-06-02.md` : besoins en insights temporels, filtres combinés Game Explorer, workflow de modération avancé.
- `benchmark-2025-02-14.md` : comparateur plateformes, widget deals, segmentation analytics, API/export.
- `benchmark-2025-10-05.md`, `benchmark-2025-10-06.md`, `benchmark-2025-10-07.md`, `benchmark-2025-10-09.md`, `benchmark-2025-10-10.md` : convergent vers un parcours premium (verdict sticky, modules guides, deals, comparateur plateformes, segmentation Score Insights, API partenaires). Ces documents forment la base de la roadmap 2025-2026.

### Roadmap & presets UI
- `product-roadmap/2025-10-roadmap.md` structure les vagues (quick wins, parcours review premium, monétisation, API). Les prochaines itérations doivent confirmer les KPIs associés (CTR guides, usage statut, clics deals, consommation API).
- `ui-presets-prescription-2025-10-11.md` détaille 4 presets visuels avec tokens, typographies et animation. Prévoir une implémentation progressive et des tests contraste/accessibilité.

### Guides fonctionnels et checklists
- `game-explorer-loading-overlay.md` (non listé dans le backlog initial mais présent) insiste sur l’overlay de chargement responsive : vérifier son intégration lors des évolutions Game Explorer.
- `rating-block-preview-options.md`, `user-rating-histogram.md`, `score-insights.md`, `responsive-testing.md` et `review-status-automation.md` couvrent respectivement les tests manuels, l’accessibilité des modules et les checklists à maintenir.
- `platform-breakdown-block-plan.md` formalise les livrables nécessaires au bloc Gutenberg et renvoie vers les tests automatiques/manuels attendus.

### Revues de code
- `code-review-2025-10-08.md` : actions immédiates autour du widget « Derniers Tests ».
- `code-review-2025-10-12.md` : refactoring helpers/Frontend/sanitisation.
- `code-review-2025-10-13.md` : sécurisation et extensibilité de la nouvelle architecture vidéo.

## Prochaines étapes recommandées
1. Prioriser le lot « Verdict & statut » et le comparateur plateformes (release 5.1) tout en préparant le module deals.
2. Lancer les refactorings structurants (vidéo, Frontend, sanitisation) avant d’étendre les APIs afin de sécuriser la base.
3. Mettre à jour les checklists (`responsive-testing.md`, `rating-block-preview-options.md`) après chaque nouvelle fonctionnalité pour conserver la traçabilité QA.
4. Planifier un nouvel audit documentation au 1er trimestre 2026 pour confronter l’avancement aux benchmarks et ajuster la roadmap.

## Suivi – 16 octobre 2025

- Première version du comparateur plateformes livrée : saisie dans la metabox « Comparatif plateformes » et restitution front via le shortcode `[jlg_platform_breakdown]`. Les tests responsives ont été ajoutés dans `docs/responsive-testing.md`.
- Revue du 16/10 : document `code-review-2025-10-16.md` ouvert pour suivre la compatibilité Gutenberg du comparatif et le durcissement de la sanitisation (`wp_kses_post` côté template, tests REST à prévoir).
- Plan Gutenberg : `platform-breakdown-block-plan.md` détaille le backlog technique (helper JSON, endpoint REST, bloc, sanitisation) ainsi que les tests PHPUnit/manuels et la documentation à produire.
