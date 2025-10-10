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
### Plan d’action priorisé (0-6 mois)

| Priorité | Fenêtre cible | Livrables clés | Impact attendu |
| --- | --- | --- | --- |
| **P0 – Critique** | 0-1 mois | Mode Simple/Expert refondu, bloc "Notation express", feedback dynamique d’état | Adoption accrue par les rédactions pressées, parité UX avec IGN/OpenCritic |
| **P1 – Maîtrise** | 1-3 mois | CTA verdict configurables, variants de cartes, monitoring de santé des données | Conversion affiliée améliorée, cohérence UI renforcée, diminution du temps de diagnostic |
| **P2 – Excellence** | 3-6 mois | Kit de design tokens, mode dégradé front, checklist voix-off & mode haute lisibilité | Expérience éditoriale premium, conformité accessibilité, résilience accrue |

### Détail par priorité

#### P0 – Critique : sécuriser l’adoption immédiate
1. **Organisation Simple vs Expert**
   - Implémenter une vue "Essentiel" dans l’onglet Réglages activant par défaut note globale, pros/cons, verdict et statut review, avec aperçu instantané.
   - Ajouter un basculement "Afficher les réglages experts" qui révèle les paramètres fins (animations, gradients, réglages RAWG).
   - Créer un rapport d’audit UX post-déploiement (10 rédacteurs test) pour confirmer la réduction du temps de configuration (<5 min).
2. **Bloc Gutenberg "Notation express"**
   - Concevoir un bloc regroupant note + pros/cons + verdict, préconfiguré selon les meilleures pratiques IGN.
   - Prévoir dans Inspector des presets visuels (clair/sombre) et des avertissements en cas d’absence de champs critiques.
   - Couvrir par des tests E2E (render + sauvegarde) afin d’assurer la parité éditeur/front.
3. **Feedback dynamique & accessibilité immédiate**
   - Intégrer un composant toast/alert (`role="status"`) pour Game Explorer et Score Insights indiquant les résultats filtrés et les mises à jour.
   - Ajouter des ancres "Aller à" et revoir l’ordre `tabindex` pour permettre une navigation clavier fluide.
   - Déployer un petit script de télémétrie (anonyme) pour vérifier l’usage des interactions clavier et itérer rapidement.

#### P1 – Maîtrise : augmenter la valeur commerciale et la robustesse
1. **Carte verdict premium**
   - Ajouter des CTA personnalisables (texte, URL, icône, ARIA-label) avec prévisualisation live et fallback texte-only.
   - Permettre l’insertion de badges de disponibilité (plateforme, version testée) synchronisés avec RAWG.
2. **Variants de carte & design cohérent**
   - Définir trois variants (compacte, détaillée, multi-colonne) sélectionnables dans l’inspector avec description d’usage.
   - Introduire un registre de tokens (couleurs, radius, ombres, espacements) partagé entre front et Gutenberg via SCSS/CSS custom properties.
3. **Monitoring & fiabilité**
   - Construire un dashboard santé : statut API RAWG, erreurs de cron, latence votes lecteurs, avec notifications email/slack.
   - Écrire des tests de résilience simulant indisponibilité API pour garantir des messages fallback élégants.

#### P2 – Excellence : différenciation éditoriale et accessibilité avancée
1. **Mode dégradé contrôlé**
   - Introduire un mode front "Données temporaires indisponibles" qui affiche une version simplifiée des blocs sans casser la mise en page.
   - Documenter le comportement dans les guides QA et prévoir un toggle de test dans les réglages.
2. **Design system industrialisé**
   - Publier un design kit (Figma + docs) avec tokens synchronisés et checklists de contribution.
   - Ajouter un mode "édition print" sans animations pour export PDF.
3. **Accessibilité éditoriale renforcée**
   - Rédiger une checklist voix-off pour les rédacteurs et la synthèse vocale.
   - Déployer un mode haute lisibilité (typo renforcée, interlignage accru, désactivation des effets néon) activable globalement ou par shortcode.

## Prochaines étapes
1. Formaliser les spécifications détaillées du mode Simple/Expert et du bloc "Notation express" (wireframes, matrice d’autorisations, tests utilisateurs internes).
2. Planifier un sprint dédié aux feedbacks dynamiques et au monitoring minimal (alertes API + toasts) pour sécuriser la parité avec les apps pro.
3. Lancer la conception du kit de design tokens et aligner les équipes (dev + design) sur la gouvernance.
4. Préparer les tests QA (automatisés + manuels) couvrant mode dégradé, CTA verdict et variants de carte.
5. Mettre à jour la documentation utilisateur avec la checklist accessibilité, les presets de blocs et le protocole de monitoring.

