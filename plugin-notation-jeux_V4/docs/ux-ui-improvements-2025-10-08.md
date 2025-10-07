# Suggestions UX / UI – 8 octobre 2025

## Contexte benchmark
En observant IGN, GameSpot et OpenCritic, on note trois attentes récurrentes :
1. **Synthèse décisionnelle immédiate** – un header dense combine note globale, badge d'édition et principaux arguments.
2. **Comparaison multi-plateformes fluide** – tableaux compressés et filtres rapides pour basculer entre PS5/Xbox/PC.
3. **Parcours lecteur engageant** – call-to-action visibles pour donner son avis et signaux de confiance (modération, badges).

Ces constats éclairent les optimisations UX/UI ci-dessous pour Notation JLG.

## 1. Header de review « décision instantanée »
- Introduire un bandeau sticky (desktop/tablette) affichant note rédaction, statut (Draft/In progress/Final) et verdict en une phrase.
- Ajouter un micro-carousel de tags clés (ex. « Campagne solide », « Technique instable ») inspiré des "Verdict" d'IGN pour faciliter la lecture diagonale.
- Prévoir un CTA secondaire « Voir comparatif plateformes » aligné à droite pour les utilisateurs en phase d'achat.

## 2. Carte verdict responsive dans le bloc Gutenberg
- Créer un sous-bloc optionnel `VerdictCard` avec mise en page cartes : avatar du rédacteur, date de mise à jour, note, 3 points forts/faibles.
- Utiliser un système de tokens couleur (succès/attention/alerte) inspiré de GameSpot pour améliorer la hiérarchie visuelle.
- En mode mobile, basculer les points forts/faibles en accordéon accessible (focus visible, icône ARIA).

## 3. Tableau comparatif plateformes enrichi
- Déployer un layout en colonnes compressées (max 4 visibles) avec pagination horizontale façon OpenCritic.
- Intégrer indicateurs de compatibilité (FPS, DualSense, cross-save) via pictogrammes vectoriels pour limiter le texte.
- Ajouter des badges dynamiques ("Meilleure expérience", "Édition la plus stable") calculés sur les sous-notes existantes.

## 4. Score Insights plus actionnable
- Avant la distribution des notes, afficher un bandeau "Confiance des lecteurs" avec jauge (niveaux IGN Reader Reviews).
- Proposer une timeline interactive (sparklines) des notes lecteurs sur les 30 derniers jours pour visualiser la tendance.
- Ajouter un CTA "Inviter la rédaction" qui génère un lien partageable (copie dans le presse-papiers) pour stimuler les retours communautaires.

## 5. Accessibilité et modes d'affichage
- Introduire un mode "Contraste élevé" (toggle) en s'inspirant des options d'accessibilité GameSpot : couleurs renforcées, bordures accentuées.
- Garantir une navigation clavier complète des modules (ordres de tab, focus outlines, aria-expanded cohérents).
- Préparer des variantes "mode sombre" et "mode clair" pour la carte verdict et le comparatif plateformes avec captures à intégrer dans les release notes.

## 6. Parcours de conversion affiliée
- Ajouter, sous le comparatif plateformes, un bloc "Où acheter" avec boutons de taille uniforme, logos marchands et mention du suivi de prix (pattern IGN Deals).
- Mettre en place un état vide documenté (texte + illustration légère) lorsque aucun deal n'est disponible, évitant le « trou visuel » actuel.
- Prévoir un bandeau informatif sur la politique d'affiliation (transparence, comme OpenCritic) pour rassurer sur l'indépendance éditoriale.

## 7. Métriques & suivi
- Instrumenter les interactions clés (clics CTA verdict, onglet plateformes, deals) dans l'outil analytics pour mesurer l'adoption.
- Fixer un objectif de **+12 %** sur le scroll depth moyen et **+15 %** de participation lecteurs dans les 3 mois suivant le déploiement.
- Mettre à jour la checklist QA `docs/responsive-testing.md` avec scénarios header sticky, toggles accessibilité et accordéons mobiles.
