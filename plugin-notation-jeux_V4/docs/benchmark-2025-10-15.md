# Benchmark – 2025-10-15

## Références étudiées

- **IGN – Game Reviews Hub**
  - Filtrage principal par plateforme, genre et note critique (paliers ≥ 6, ≥ 8, ≥ 9).
  - Indique systématiquement la moyenne critique (Metacritic) et propose un tri "Editor's Choice" sur mobile.
  - Absence de panneau responsive dédié : les filtres occupent beaucoup d'espace sur smartphone et ne se referment pas automatiquement.

- **OpenCritic – Discover Page**
  - Barre latérale persistante avec curseur "Score Minimum" et curseur de date de sortie.
  - Propose des badges "Mighty"/"Strong" basés sur des seuils de notes mais ne permet pas de mémoriser la sélection dans l'URL.
  - Navigation clavier correcte mais absence d'indication visuelle de focus sur les boutons de validation en mode sombre.

## Opportunités pour Notation JLG

- Ajouter un filtre "Note minimale" natif rapproche l'expérience de la Discover Page d'OpenCritic tout en conservant un panneau mobile refermable, plus fluide que la sidebar fixe observée chez IGN/OpenCritic.
- Les benchmarks soulignent l'intérêt de persister la valeur du seuil dans l'URL pour partager une vue filtrée : la mise à jour implémente ce comportement.
- Pistes futures : introduire un aperçu rapide des badges (type "Editor's Choice") et un curseur de période comme sur OpenCritic pour enrichir la découverte.
