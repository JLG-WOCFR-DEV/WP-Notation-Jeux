# Benchmark – 16 octobre 2025

## Objectif
Comparer l'expérience utilisateur, l'accessibilité, la fiabilité perçue et la qualité visuelle du plugin **Notation Jeux** avec des références professionnelles du secteur des tests de jeux vidéo. L'objectif est d'identifier les écarts critiques pour prioriser les prochaines itérations produit (éditeur Gutenberg + front).

## Panel analysé
| Plateforme | UI / UX | Accessibilité | Fiabilité & réassurance | Dimension visuelle | Écart principal pour Notation Jeux |
| --- | --- | --- | --- | --- | --- |
| **IGN – Template Review 2025** | Parcours de rédaction très guidé : champs structurés, rappels contextuels (plateformes testées, build utilisé) et encarts pré-remplis pour les points forts/faibles. | Focus visibles, navigation clavier fluide, ratio de contraste > 4.5:1 sur les blocs clés, badges ARIA pour les verdicts. | Bloc « Verdict » indiquant la plateforme testée, le nombre de testeurs, l'état de patch et un indicateur « Review in progress » jusqu'à validation finale. | Palette sombre maîtrisée avec assets illustrés (icônes custom, micro-animations) renforçant la hiérarchie. | Notre formulaire Gutenberg reste générique : pas de rappels contextuels, focus encore discret sur certains contrôles, aucune mise en avant du statut de validation. |
| **GameSpot – Review Hub** | Fiches modulaires (résumé, pros/cons, breakdown par critères) avec aperçu live synchronisé sur la page front. | Titres balisés, aria-describedby sur les scores partiels, compatibilité lecteur d'écran confirmée sur les boutons de tri. | Historique des mises à jour de la review (patch notes) et indicateur temporel « Updated X days ago ». | Photographies plein écran avec overlay textuel lisible et variations de background selon la plateforme. | Le bloc `rating-block` ne trace pas l'historique ni les mises à jour. Les visuels restent statiques et peu différenciés par plateforme. |
| **OpenCritic – Widget Embeddable** | Configuration très rapide (sélecteur de jeu + skin) avec preview responsive immédiate. | Roles ARIA explicites sur les jauges, description textuelle alternative des scores et des labels. | Badge « Rating Confidence » basé sur le volume de critiques, et message contextualisé lorsque la confiance est faible. | Thèmes clair/sombre cohérents, avec ombrages doux et typographie propre à la marque. | Notre widget manque de preview responsive, ne propose pas de rôle ARIA custom ni d'indicateurs de confiance. |

## Enseignements
1. **Guidage éditorial insuffisant** : les rédactions attendent des rappels contextuels (plateforme testée, statut de patch, validation interne) et des warnings lorsque des champs clés sont absents.
2. **Accessibilité partielle** : focus et aria-labels ne sont pas systématiques sur les contrôles avancés (onglets d'analyse, jauges). Les références pro documentent explicitement chaque score pour les lecteurs d'écran.
3. **Fiabilité peu matérialisée** : l'absence d'indicateur synthétique (confiance, fraicheur de la note, historique des modifications) réduit la crédibilité face aux agrégateurs.
4. **Traitement visuel limité** : palette et assets actuels manquent de variations selon les plateformes ou modes (clair/sombre), et aucune micro-animation ne renforce la hiérarchie de lecture.

## Actions priorisées
- **UI / UX** :
  - Ajouter un panneau « Contexte de test » dans l'inspector Gutenberg avec champs dédiés (plateformes, build, statut de validation) et rappels dynamiques en front.
  - Fournir un preview instantané (iframe ou `BlockPreview`) pour les variations du widget, en affichant les skins clair/sombre.
- **Accessibilité** :
  - Étendre les attributs `aria-labelledby`/`aria-describedby` sur les jauges et les boutons de tri, et renforcer les styles de focus (`:focus-visible`) dans `assets/css`.
  - Ajouter une description textuelle des scores agrégés pour les lecteurs d'écran, alignée sur l'approche OpenCritic.
- **Fiabilité** :
  - Implémenter un badge de confiance (faible / modéré / élevé) basé sur le volume de critiques et l'âge de la note, exposé via un filtre PHP pour personnalisation.
  - Historiser automatiquement les mises à jour d'une review (date de patch, modifications majeures) et les afficher sous forme de timeline.
- **Visuel** :
  - Décliner la charte en modes clair/sombre avec contrastes contrôlés, icônes de plateforme et micro-animations légères (opacity/scale) au survol.
  - Préparer un set d'illustrations ou badges thématiques (RPG, FPS, Indie) pour différencier visuellement les reviews.

## Suivi
- Documenter la déclinaison visuelle (styles, tokens) dans `docs/ui-styleguide.md` lors de la prochaine itération.
- Ajouter aux tests manuels une checklist accessibilité (focus, lecteur d'écran) afin de valider chaque release.
