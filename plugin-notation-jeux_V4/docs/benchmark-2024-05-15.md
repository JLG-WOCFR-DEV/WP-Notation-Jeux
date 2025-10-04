# Benchmark Game Explorer — 2024-05-15

## Références analysées
- **IGN.com** – Les fiches jeux affichent la note de la rédaction et la moyenne des lecteurs dans deux encarts visuellement proches mais sans liaison explicite pour les lecteurs d'écran.
- **OpenCritic.com** – Les pages synthèse mêlent score "Top Critic" et appréciation des joueurs via un histogramme, mais la densité d'information rend la hiérarchie peu lisible sur mobile.

## Enseignements
- Les deux plateformes placent les scores éditoriaux et communautaires dans la même zone visuelle, ce qui facilite la comparaison immédiate.
- L'accessibilité reste perfectible : absence d'attributs ARIA contextualisant les deux notes, et contraste parfois insuffisant sur les badges secondaires.

## Décisions pour WP Notation Jeux
- Juxtaposer la note rédactionnelle et la moyenne des lecteurs au sein du Game Explorer, avec `role="group"` et libellés ARIA explicites pour annoncer la comparaison.
- Afficher le volume d'avis afin d'éviter toute interprétation trompeuse d'une moyenne basée sur peu de votes.
- Conserver une mise en page compacte et responsive pour éviter la surcharge visuelle observée sur OpenCritic, tout en réutilisant la palette du plugin.
