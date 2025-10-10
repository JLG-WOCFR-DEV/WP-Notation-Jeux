# Revue des fonctionnalités manquantes – 17 octobre 2025

## Contexte
La feuille de route publique (README et documentation WordPress) mentionne plusieurs évolutions prévues pour les prochaines itérations du plugin. Après revue du code PHP, JS et des templates de `plugin-notation-jeux_V4`, ces éléments ne sont pas encore livrés : aucun hook, classe ou test ne couvre les fonctionnalités listées ci-dessous.

## Fonctionnalités encore absentes
- **Assistant de configuration guidée** – Aucun module d’onboarding n’est présent dans le code alors que le README et la documentation WordPress l’identifient comme priorité pour accélérer la mise en route après activation. 【F:README.md†L167-L189】【F:plugin-notation-jeux_V4/docs/documentation-audit-2025-10-14.md†L12-L34】
- **Notation multi-contributeurs pondérée** – Aucun schéma de stockage ou helper ne permet de saisir plusieurs contributeurs et pondérations de catégorie malgré l’engagement produit décrit dans la documentation. 【F:README.md†L167-L189】【F:plugin-notation-jeux_V4/docs/documentation-audit-2025-10-14.md†L12-L34】
- **Timeline des patchs / suivi post-lancement** – Le plugin expose la date du dernier patch vérifié pour automatiser le statut, mais aucun module ne capitalise sur une timeline détaillant l’historique des mises à jour comme annoncé dans la vision produit. 【F:README.md†L167-L189】【F:plugin-notation-jeux_V4/docs/documentation-audit-2025-10-14.md†L12-L34】
- **Exports partenaires enrichis** – L’API REST `/jlg/v1/ratings` et la commande WP-CLI existent, mais le flux JSON/CSV dédié aux partenariats médias mentionné dans le README n’a pas encore été décliné (pas de contrôleur spécifique ni de script d’export complémentaire). 【F:README.md†L167-L189】【F:plugin-notation-jeux_V4/docs/documentation-audit-2025-10-14.md†L12-L34】
- **Mode rédaction collaborative temps réel** – Aucun composant JS (WebSocket, polling) ni point d’entrée PHP ne gère la coédition simultanée promise pour les rédactions. 【F:README.md†L167-L189】【F:plugin-notation-jeux_V4/docs/documentation-audit-2025-10-14.md†L12-L34】
- **Tableau de bord analytics éditorial** – Le back-office ne propose pas encore de panneau dédié aux KPI (progression des tests, couverture plateforme/genre, alertes de mise à jour) pourtant listé comme axe stratégique. 【F:README.md†L167-L189】【F:plugin-notation-jeux_V4/docs/documentation-audit-2025-10-14.md†L12-L34】

## Points d’attention complémentaires
- **Extensibilité vidéo** : la factory d’embed vidéo reste fermée. Ajouter un filtre de configuration reste à l’ordre du jour pour permettre l’ajout de fournisseurs sans patcher le cœur. 【F:plugin-notation-jeux_V4/docs/code-review-2025-10-13.md†L9-L20】
- **Benchmarks récents** : les audits UX/UI réclament un panneau de prévisualisation multi-device et des triggers analytics supplémentaires côté blocs Gutenberg. Une première itération vient d’être intégrée via `assets/js/blocks/shared.js` (panneau dédié + instrumentation `sendAnalyticsEvent`), à valider sur l’ensemble des blocs dans l’éditeur. 【F:plugin-notation-jeux_V4/docs/benchmark-2025-10-06.md†L15-L56】【F:plugin-notation-jeux_V4/assets/js/blocks/shared.js†L196-L336】

## Prochaines étapes suggérées
1. Prioriser un lot « onboarding + analytics » en sprint court pour sécuriser l’adoption rédactions (wizard d’activation, tableau de bord synthétique, instrumentation de base).
2. Préparer un design technique pour la co-notation pondérée (structure de données, UI, recalcul des moyennes) et la timeline patchs (CPT ou métadonnées répétables + rendu front/back).
3. Spécifier le format du flux partenaire (JSON/CSV) attendu afin d’étendre l’API/CLI existante sans dupliquer la logique d’agrégation.
4. Ajouter les points d’extension identifiés (vidéo, analytics) pour ouvrir le plugin aux intégrations tierces avant de lancer les développements lourds.
