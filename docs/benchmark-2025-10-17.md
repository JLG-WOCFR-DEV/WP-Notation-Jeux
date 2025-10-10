# Benchmark UX – 17 octobre 2025

## Cible de comparaison
- **IGN** – fiche test standard avec carte verdict condensée, rubriques claires, CTA affiliés et navigation secondaire (onglets).
- **OpenCritic** – agrégateur pro mettant l’accent sur lisibilité des moyennes, filtres rapides (plateforme/critique), résumé visuel compact et composants accessibles clavier.
- **GameSpot** – mix rédactionnel + data (score, pros/cons, vidéo) avec personnalisation light/dark et gestion poussée des états "Updated".

## Forces actuelles du plugin
- Palette de composants complète (shortcodes + blocs) couvrant note, verdict, histogrammes, Game Explorer et Score Insights.
- Automatisations avancées (finalisation auto du statut, remplissage RAWG, modules optionnels) dépassant l’offre de base IGN/GameSpot.
- Préoccupation accessibilité déjà présente (histogramme ARIA, focus visibles, parity éditeur/front).

## Écarts observés vs apps pro
### IGN
- Navigation secondaire (onglets) pour segmenter test, vidéos, guides annexes ; notre Game Explorer/Score Insights sont puissants mais nécessitent configuration manuelle des shortcodes.
- Carte verdict IGN montre résumé + CTA marchand + contexte version ; notre bloc verdict expose contexte mais pas de CTA configurable ni badges de disponibilité.

### OpenCritic
- Filtrage instantané (plateforme, review type) avec feedback visuel clair ; notre Game Explorer nécessite rechargement complet des shortcodes dans l’article.
- Score global accompagné d’un indicateur de confiance et label qualitatif ; déjà présent mais calcul sur un seul test, manque agrégation multi-articles.
- Accessibilité exemplaire : sélecteurs filtrables au clavier et narration ARIA ; nos filtres GET sont accessibles mais absence de feedback "résultats mis à jour".

### GameSpot
- Mode simple (score + résumé) et mode détaillé (déployer sections) directement dans la page sans repasser par les réglages ; dans le plugin, il faut choisir shortcode/bloc différents.
- Gestion du statut "Updated" visible dès la hero card avec lien vers changelog ; notre statut auto est puissant mais la surface front manque d’un micro-composant pour afficher l’historique.

## Recommandations stratégiques
### Roadmap de développement priorisée (0-6 mois)

| Priorité | Fenêtre cible | Objectif produit | Mesure de succès |
| --- | --- | --- | --- |
| **P0 – Critique** | 0-1 mois | Simplifier la configuration initiale et garantir un feedback instantané | Temps de mise en ligne d’une critique <5 min, taux d’usage du mode Essentiel >70 % |
| **P1 – Maîtrise** | 1-3 mois | Augmenter la conversion et la cohérence visuelle | +20 % de clics CTA verdict, dette de design réduite (tokens unifiés) |
| **P2 – Excellence** | 3-6 mois | Offrir une expérience premium résiliente et accessible | Score Lighthouse Accessibilité ≥95, MTTR incidents front <30 min |

### Backlog codé par priorité

#### P0 – Critique : sécuriser l’adoption immédiate

**User story principale** – En tant que rédacteur pressé, je veux configurer une critique complète en quelques minutes sans fouiller tous les réglages afin de respecter ma deadline.

1. **Organisation Simple vs Expert**
   - Implémenter `src/Admin/Settings/SimpleExpertController.php` gérant deux onglets : « Essentiel » et « Expert ». Le contrôleur doit charger les options existantes via `SettingsRepository` et ne persister que les options visibles en mode Essentiel.
   - Ajouter un composant React dans `assets/js/admin/settings-simple-mode.js` qui rend les champs critiques (note globale, pros/cons, verdict, statut review) et un toggle `Afficher les réglages experts` (sauvegardé en meta utilisateur pour retenir le dernier état).
   - Étendre `templates/admin/settings-general.php` avec un `<section aria-labelledby="settings-simple">` et inclure un aperçu live via `wp.data.select( 'core/editor' )` pour réduire les aller-retours.
   - QA : scénario Cypress `tests/e2e/settings-simple-mode.spec.js` couvrant activation, sauvegarde, bascule Expert, et temps moyen mesuré via instrumentation (`performance.mark`).
2. **Bloc Gutenberg « Notation express »**
   - Créer `assets/js/blocks/notation-express/edit.js` et `save.js` pour regrouper note + pros/cons + verdict. S’appuyer sur les hooks existants (`useNotationFields`) afin de partager la logique avec le bloc verdict.
   - Définir dans `block.json` des presets `style` (clair/sombre) et ajouter des validations synchrones (message d’erreur si champs critiques manquants). Prévoir un `supports.inserter` forcé à `true`.
   - Implémenter le rendu serveur dans `src/Blocks/NotationExpressBlock.php` en réutilisant `VerdictPresenter`. Couvrir par des tests PHPUnit (`tests/Blocks/NotationExpressBlockTest.php`) et un test de snapshot éditeur via `@wordpress/scripts`.
3. **Feedback dynamique & accessibilité immédiate**
   - Introduire un module JS partagé `assets/js/utils/aria-status.js` exportant `announceUpdate(message)` avec un `<div role="status" aria-live="polite">` injecté côté front et admin.
   - Brancher ce module dans `Game Explorer` (`assets/js/front/game-explorer.js`) et `Score Insights` (`assets/js/front/score-insights.js`) pour annoncer les résultats filtrés et erreurs API.
   - Mettre à jour `templates/shortcodes/game-explorer.php` pour inclure des ancres `href="#results"` et réordonner les `tabindex`. Ajouter des tests de navigation clavier via Playwright (`tests/e2e/accessibility-feedback.spec.js`).
   - Instrumenter un script de télémétrie anonyme (`assets/js/utils/telemetry.js`) enregistrant les interactions clavier (événements `keyup`, `focus`) et envoyant une requête `wp-json/notation/v1/telemetry`. Créer un endpoint minimal dans `src/Rest/TelemetryController.php` stockant les compteurs (option transitoire). Respecter le RGPD (pas de données personnelles, consentement via opt-in settings).

#### P1 – Maîtrise : augmenter la valeur commerciale et la robustesse

**User story principale** – En tant que responsable monétisation, je veux optimiser la carte verdict pour augmenter les clics affiliés et disposer d’une interface cohérente.

1. **Carte verdict premium**
   - Étendre `assets/js/blocks/verdict/edit.js` avec un panneau `InspectorControls` dédié aux CTA : textes localisés (`__`), URL validée (`wp.url`), icône choisie via `@wordpress/components` `<IconPicker>`, et champ `ariaLabel`.
   - Mettre à jour `src/Blocks/VerdictBlock.php` pour rendre un `<nav>` contenant les CTA, avec gestion fallback (texte-only) et badges synchronisés à partir des métadonnées RAWG (utiliser `RawgGameRepository`).
   - Tests : PHPUnit pour vérifier la présence des CTA et badges (`tests/Blocks/VerdictBlockTest.php`) + test E2E Playwright pour vérifier le clic (événement `data-layer`).
2. **Variants de carte & design cohérent**
   - Créer un registre de design tokens `assets/styles/tokens.scss` compilé en CSS custom properties (`:root`). Brancher les variables dans `assets/css/blocks-verdict.css` et `blocks-editor.css`.
   - Ajouter trois variants dans `block.json` (`style` presets) avec prévisualisation via `BlockPreview`. Documenter l’usage dans `docs/design-system/variants.md`.
   - Mettre en place un script de vérification (`npm run tokens:lint`) qui s’assure que chaque token est documenté. Ajouter un test Jest simple pour vérifier la présence des classes variant.
3. **Monitoring & fiabilité**
   - Développer `src/Admin/Monitoring/DashboardController.php` affichant le statut API, cron et latence votes. Récupérer les données via des services `RawgHealthCheck`, `CronHealthCheck`, `VotesHealthCheck`.
   - Configurer des notifications email/Slack via `wp_mail` et un webhook configurable (option `notation_monitoring_webhook`).
   - Écrire des tests de résilience dans `tests/Integration/HealthCheckTest.php` simulant indisponibilité API (`WP_Error`). Vérifier l’affichage de messages fallback.

#### P2 – Excellence : différenciation éditoriale et accessibilité avancée

**User story principale** – En tant que chef de rédaction, je veux garantir une continuité de service et un confort de lecture avancé même lors d’incidents.

1. **Mode dégradé contrôlé**
   - Implémenter un flag global `notation_degraded_mode` stocké via `SettingsRepository`. Lorsqu’il est actif, `src/Presentation/ViewModels` doit servir des composants simplifiés (note + résumé statique) et désactiver les appels API.
   - Ajouter un toggle dans l’admin avec prévisualisation (panel `DangerZone`). Documenter le flux dans `docs/operations/degraded-mode.md`.
   - Créer des tests `tests/Integration/DegradedModeTest.php` vérifiant la continuité du rendu et les logs (`wc_get_logger`).
2. **Design system industrialisé**
   - Synchroniser les tokens vers Figma via un export JSON (`npm run tokens:export`). Conserver la source de vérité dans `assets/styles/tokens.json`.
   - Ajouter un mode « édition print » : stylesheet `assets/css/print.css` chargée conditionnellement (`wp_enqueue_style` avec `media="print"`). Assurer l’absence d’animations (`prefers-reduced-motion`).
   - Documenter la gouvernance design dans `docs/design-system/README.md` (process de contribution, naming, revues).
3. **Accessibilité éditoriale renforcée**
   - Rédiger `docs/accessibilite/checklist-voix-off.md` couvrant la narration audio et le paramétrage de synthèse vocale.
   - Développer un mode haute lisibilité : option `notation_high_legibility` ajoutant une classe `notation--high-legibility` sur le wrapper, ajustant typo/interlignage (`assets/css/high-legibility.css`).
   - Ajouter des tests Lighthouse automatisés (`npm run lighthouse`) sur un storybook ou site de démonstration, avec seuil ≥95.

## Prochaines étapes opérationnelles
1. Finaliser les spécifications détaillées du mode Simple/Expert et du bloc « Notation express » (wireframes, matrice d’autorisations, protocoles de tests utilisateurs).
2. Séquencer les développements P0 en tickets Jira (ou équivalent) et allouer une itération complète incluant QA automatisée et manuelle.
3. Lancer l’intégration continue dédiée aux bundles JS/CSS (build + lint + tests Playwright) avant d’attaquer P1.
4. Préparer le comité design pour valider les tokens communs et les variants de cartes avant implémentation.
5. Mettre à jour la documentation utilisateur avec la checklist accessibilité, les presets de blocs et le protocole de monitoring dès la fin du palier P1.

