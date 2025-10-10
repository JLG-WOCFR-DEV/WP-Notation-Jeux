# Benchmark – Feedback dynamique filtres (2025-03-10)

| Solution | Points observés | Opportunités pour Notation Jeux |
| --- | --- | --- |
| **OpenCritic** | Mise à jour instantanée des listes avec annonce visuelle « Updated » et compteur d’entrées, aria-live partagé pour l’annonce de résultats, toast discret en bas de page. | Reproduire un toast accessible synchronisé avec une région aria-live afin d’annoncer le volume de résultats après chaque filtrage. |
| **IGN** | Filtres dynamiques avec confirmation textuelle (« Showing X of Y ») et animation légère. Navigation clavier conservée lors des rafraîchissements AJAX. | Ajouter un résumé synthétique dans le toast (nombre de résultats) et conserver le focus dans la zone filtrée. |
| **Notation Jeux (avant)** | Mise à jour AJAX silencieuse : seul le compteur change, aucune annonce vocale, pas de signal visuel. | Créer un utilitaire commun (aria-live + toast), brancher Game Explorer et Score Insights dessus, prévoir un canal d’événements réutilisable par les futurs modules. |

## Décisions
- Factoriser un utilitaire `live-announcer` chargé de gérer la région aria-live et le toast visuel.
- Déclencher des annonces côté Game Explorer à chaque réponse AJAX et exposer un événement `jlg:score-insights:updated` pour les modules statistiques.
- Prévoir un message différencié pour 0, 1 et N résultats pour coller aux standards observés chez OpenCritic et IGN.
