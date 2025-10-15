# Priorités produit – 19 octobre 2025

## Synthèse de l'itération précédente
- Les priorités P0/P1/P2 identifiées le 17 octobre restent pertinentes : mode « Simple vs Expert », bloc « Notation express », feedback dynamique/ARIA, carte verdict premium, design tokens, monitoring, mode dégradé, design system industrialisé et renforcement accessibilité.【F:docs/benchmark-2025-10-17.md†L33-L135】
- Les étapes opérationnelles recommandent de séquencer les développements P0, de lancer une CI front (build, lint, Playwright) et de préparer la gouvernance design avant d'entamer les lots P1.【F:docs/benchmark-2025-10-17.md†L137-L158】

## Compléments de priorisation (P0)
1. **Livraison progressive du mode Simple/Expert**
   - Découper le contrôleur et l'UI en sous-tâches (repository, REST, composant React) afin de livrer une V1 avec lecture seule des options critiques avant d'activer la persistance différenciée. Ajouter un test PHPUnit ciblant la sérialisation partielle du `SettingsRepository`.
   - Prévoir une tâche de migration pour stocker la préférence utilisateur (onglet par défaut) dans la table `usermeta` et un hook de nettoyage lors de la désinstallation (`uninstall.php`).
   - ✅ Les préférences sont désormais initialisées lors de l'activation et pour chaque nouvel utilisateur, puis nettoyées à la désinstallation via `Helpers::seed_settings_view_mode()` / `ensure_user_settings_view_mode()` et `Helpers::rollback_settings_view_mode()`.【F:plugin-notation-jeux_V4/plugin-notation-jeux.php†L133-L174】【F:plugin-notation-jeux_V4/includes/Helpers.php†L1910-L1980】【F:plugin-notation-jeux_V4/uninstall.php†L17-L74】
2. **Notation express & parité mobile**
   - Créer des maquettes responsive (small/medium) pour anticiper la densité d'informations et documenter les breakpoints dans `docs/responsive-testing.md`. Étendre les tests E2E pour couvrir l'insertion du bloc dans un article existant et la sauvegarde via clavier uniquement.
3. **Feedback dynamique & télémétrie**
   - Définir la convention de nommage des événements (`notation.feedback.updated`, `notation.feedback.error`) et la fréquence maximale d'envoi pour éviter la saturation réseau. Préparer un rapport hebdomadaire agrégé (JSON) stocké en option transitoire pour alimenter le futur dashboard monitoring.
   - ✅ Les événements front respectent désormais cette convention avec un throttle de 500 ms, et `Telemetry::get_weekly_report_json()` fournit un rapport hebdomadaire mis en cache (`jlg_notation_weekly_report`).【F:plugin-notation-jeux_V4/assets/js/user-rating.js†L168-L520】【F:plugin-notation-jeux_V4/includes/Telemetry.php†L13-L323】

## Ajustements P1
- **Carte verdict premium** : ajouter un critère d'acceptation sur les CTA multiples (jusqu'à 3) avec ordre personnalisable et fallback automatique en liste simple si JavaScript est désactivé.【F:docs/benchmark-2025-10-17.md†L79-L99】
- **Design tokens** : intégrer une vérification de contraste (`npm run tokens:contrast`) pour garantir AA minimum lors de la création des variantes, et prévoir un export JSON synchronisé avec Figma pour préparer P2.【F:docs/benchmark-2025-10-17.md†L100-L117】
- **Monitoring** : spécifier la politique de rétention des métriques (30 jours en option sérialisée) et documenter la procédure d'escalade dans `docs/operations/monitoring.md`.

## P2 – Préparation
- Orchestrer un atelier inter-équipes pour définir les scénarios de bascule en mode dégradé et les composants exclus lorsque le flag est actif.【F:docs/benchmark-2025-10-17.md†L118-L135】
- Mettre en place une maquette du mode haute lisibilité et une checklist accessibilité éditoriale (voix off, contrastes, animations) avant de coder les styles dédiés.【F:docs/benchmark-2025-10-17.md†L126-L135】

## Écarts majeurs vs roadmap publique
Les éléments suivants sont toujours absents du code malgré leur présence dans la roadmap/README :
- Assistant de configuration guidée (wizard post-activation).【F:docs/feature-gap-review-2025-10-17.md†L8-L12】
- Notation multi-contributeurs pondérée (stockage et UI).【F:docs/feature-gap-review-2025-10-17.md†L12-L15】
- Timeline des patchs et suivi post-lancement pour historiser les mises à jour d'un jeu.【F:docs/feature-gap-review-2025-10-17.md†L15-L18】
- Flux d'exports partenaires enrichis au-delà de l'endpoint `/jlg/v1/ratings` et de la commande WP-CLI actuelle.【F:docs/feature-gap-review-2025-10-17.md†L18-L21】
- Mode rédaction collaborative en temps réel (coédition multi-utilisateur).【F:docs/feature-gap-review-2025-10-17.md†L21-L24】
- Tableau de bord analytics éditorial dédié (KPI, alertes).【F:docs/feature-gap-review-2025-10-17.md†L24-L27】

## Écarts vs concurrence directe
- **IGN** : absence de navigation secondaire/onglets pour segmenter test, vidéos et guides ; pas de CTA marchands intégrés dans la carte verdict.【F:docs/benchmark-2025-10-17.md†L14-L26】
- **OpenCritic** : manque d'agrégation multi-articles avec indicateur de confiance consolidé et feedback filtrage live (annonce ARIA après mise à jour).【F:docs/benchmark-2025-10-17.md†L27-L34】
- **GameSpot** : pas de bascule simple/détaillée directement côté front ni composant de statut « Updated » visible en surface avec historique associé.【F:docs/benchmark-2025-10-17.md†L35-L42】

## Prochaines actions recommandées
1. Planifier un sprint dédié aux lots P0 avec instrumentation de la durée de configuration (objectif <5 min) pour valider la promesse produit.【F:docs/benchmark-2025-10-17.md†L49-L60】
2. Documenter les flux manquants (assistant, co-notation, exports partenaires) sous forme de spécifications fonctionnelles courtes dans `docs/product-roadmap/` afin d'alimenter les futures itérations.【F:docs/feature-gap-review-2025-10-17.md†L29-L36】
3. Monter une veille concurrentielle trimestrielle avec mesure des gaps clés (CTA, filtrage live, mode « Updated ») pour réajuster la roadmap en continu.【F:docs/benchmark-2025-10-17.md†L14-L42】
