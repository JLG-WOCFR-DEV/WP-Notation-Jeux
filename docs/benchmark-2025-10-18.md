# Benchmark UX – 18 octobre 2025

## Cible de comparaison
- **IGN** – Fiches test modulaires avec navigation par onglets (Review / Guides / Deals), carte verdict condensée, CTA affiliés contextualisés, modes clair/sombre cohérents.
- **OpenCritic** – Agrégateur temps réel avec filtres instantanés (plateforme, type de critique), affichage de la confiance statistique, design system maîtrisé et accessibilité irréprochable.
- **GameSpot** – Mix éditorial + data : score éditorial, vidéo intégrée, timeline de mises à jour, micro-interactions riches mais maîtrisées, statut « Updated » omniprésent.

## Synthèse comparative par pilier

| Pilier | Référence pro | Plugin aujourd’hui | Constat |
| --- | --- | --- | --- |
| **UX / parcours lecteur** | IGN met en avant un résumé + CTA dès le hero, OpenCritic affiche filtres persistants et état actif. | Shortcodes riches mais nécessitant plusieurs blocs pour couvrir résumé, CTA et filtres ; feedback de filtrage AJAX limité. | Densité fonctionnelle élevée mais parcours multi-étapes, manque de feedback vocal/visuel après interaction.
| **UI & design system** | GameSpot décline un design system strict (typographies, icônes, badges cohérents) ; OpenCritic assure parité light/dark parfaite. | Trois presets visuels et options personnalisables mais tokens éparpillés (SCSS + PHP) et prévisualisation Gutenberg partielle. | Identité forte mais dette de cohérence (espacements, hiérarchie typographique) et frictions lors du theming.
| **Ergonomie rédaction / admin** | IGN propose un mode édition express (checklist « Ready to publish »), GameSpot affiche un statut « Needs Update » centralisé. | Réglages exhaustifs, modules optionnels, auto-finalisation mais absence de vue synthétique et de mode « Essentiel ». | Puissance reconnue mais onboarding et vue d’ensemble perfectibles pour des rédactions sous contrainte temps.
| **Fiabilité & confiance** | OpenCritic signale latence API, statut serveurs et qualité des reviews ; GameSpot conserve un historique visible des mises à jour. | Diagnostics intégrés (latence API RAWG, votes) mais pas de monitoring proactif ni de timeline de verdict. | Base solide mais manque d’alertes anticipées et d’éléments publics inspirant confiance longue durée.

## Opportunités d’amélioration ciblées

### UX / UI & ergonomie
1. **Vue « Notation express » dans Gutenberg** : regrouper note globale, verdict, CTA affilié et points clés dans un seul bloc, avec validation temps réel et prévisualisation live. Réduire la configuration à deux panneaux (Contenu / Style) et mémoriser les derniers choix pour la rédaction.
2. **Assistant de configuration guidée** : dès l’activation, afficher un wizard en 4 étapes (types de contenus, modules, palette, import d’exemples). Proposer un mode « Essentiel vs Expert » persistant par utilisateur.
3. **Feedback dynamique des filtres** : injecter un `aria-live` partagé et un toast visuel (« 5 résultats mis à jour ») pour Game Explorer et Score Insights afin de reproduire la fluidité d’OpenCritic.
4. **CTA verdict premium** : intégrer dans la carte verdict un slot CTA multi-boutons (achat, guide, vidéo) avec gestion d’icônes et du focus clavier, inspiré de la carte IGN.

### Fiabilité & design
1. **Timeline de mises à jour du test** : ajouter un composant affichant les dates de patchs et changements de note, comparable à GameSpot, avec badges « Updated » et historique public.
2. **Monitoring proactif** : tableau de bord admin consolidant statut API RAWG, cron, votes et erreurs PHP récentes. Notifications email/webhook en cas de latence > seuil.
3. **Design tokens centralisés** : créer un registre `tokens.json` -> SCSS/CSS custom properties pour unifier couleurs, espacements, radius et ombres. Synchroniser avec les blocs (éditeur + front) pour garantir la parité visuelle.
4. **Mode haute lisibilité** : alternative design (typo plus large, interlignage augmenté, suppression d’animations) activable par shortcode/bloc pour aligner l’accessibilité sur OpenCritic.

## Plan d’action priorisé

### Quick wins (0-1 mois)
- Développer un module `aria-status` commun pour annoncer les mises à jour AJAX (filtres, votes), avec toast visuel léger.
- Ajouter dans la carte verdict un champ CTA simple (libellé + URL + icône) et un badge « Updated » automatique lorsque le statut éditorial change.
- Créer des patrons de mise en page Gutenberg (pattern « Review express ») combinant bloc all-in-one + section CTA.

### Initiatives structurantes (1-3 mois)
- Implémenter le mode « Essentiel / Expert » côté réglages, avec sauvegarde par utilisateur et télémetrie anonymisée (temps de configuration, champs utilisés).
- Introduire le composant « Notation express » (bloc + shortcode) et aligner les presets visuels via un set de tokens partagés.
- Déployer un tableau de bord Monitoring listant latence API, statut des votes, santé cron, avec alertes email/webhook.

### Pari différenciant (3-6 mois)
- Livrer la timeline de verdict (front + admin) avec historique consultable, notifications pour les lecteurs abonnés, et intégration avec RAWG pour importer les patch notes majeurs.
- Étendre le design system : documentation Figma + guide d’usage dans `docs/design-system/`, tests de non régression visuelle (Playwright screenshot) et automatisation Lighthouse (>95 accessibilité/perf).
- Proposer un mode haute lisibilité et un preset contrasté validé WCAG AA (paramètre global + switch côté lecteur).

## Métriques de suivi proposées
- **Taux d’usage du mode Essentiel** (objectif >70 % des rédacteurs actifs dans le mois suivant la sortie).
- **Temps moyen de publication d’un test complet** (target <5 minutes depuis l’ouverture de l’éditeur jusqu’à la publication).
- **CTR des CTA verdict** (objectif +20 % vs baseline actuelle sur 30 jours).
- **Score Lighthouse Accessibilité** sur la page de démonstration (>95) et **taux d’erreurs critiques** remontées par le monitoring (<2 incidents / trimestre).
- **Adoption du mode haute lisibilité** (≥15 % des sessions lectures utilisant le switch dans les 3 mois).

## Next steps
1. Valider les wireframes « Notation express » & Monitoring avec un panel de rédacteurs (prototype Figma + tests modérés).
2. Prioriser les quick wins dans la prochaine itération et planifier des tests utilisateurs dédiés (5 rédacteurs, 5 minutes chacun).
3. Mettre à jour la roadmap produit avec les jalons tokens/design system et documenter la gouvernance dans `docs/design-system/`.
4. Préparer une communication produit (release notes, captures, vidéo) mettant en avant le mode Essentiel, le monitoring et la timeline de verdict.
