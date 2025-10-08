# Benchmark du 2025-10-09 – Synthèse Score Insights

> _Mise à jour 2025-10-14 : ce document a été relu lors de l’audit des fichiers Markdown. Les actions détaillées sont centralisées dans [`documentation-audit-2025-10-14.md`](documentation-audit-2025-10-14.md)._

| Solution | Comportement observé | Impact utilisateur | Opportunité d'amélioration |
| --- | --- | --- | --- |
| **IGN – Guides & Reviews** | Les tableaux de review mettent en avant une mini-synthèse « Trending Score » affichant l'évolution de la moyenne sur 30 jours avec une pastille couleur (vert/rouge). | La rédaction identifie immédiatement si la perception monte ou baisse et peut ajuster la ligne éditoriale. | Ajouter un indicateur de tendance chiffré et visuel sur notre module Score Insights pour suivre la dynamique récente. |
| **GameSpot – Reviews Hub** | Le bloc « Top Games Right Now » précise la variation vs la période précédente (« +0.4 this week ») et liste le volume de tests pris en compte. | Les équipes disposent d'un contexte temporel clair pour mesurer la performance de chaque segment de plateforme. | Introduire une comparaison automatique avec la période précédente (même durée) et mentionner le volume de tests utilisés pour contextualiser la donnée. |
| **OpenCritic – Hall of Fame** | OpenCritic affiche un indicateur « Trending » basé sur les sorties récentes et signale quand une moyenne est stable. | Les lecteurs peuvent facilement détecter une stagnation et chercher d'autres signaux (notes lecteurs). | Prévoir un libellé « Tendance stable » lorsque la variation est négligeable pour éviter les interprétations erronées. |

## Décision

Mettre à jour le shortcode `jlg_score_insights` pour :

1. Calculer automatiquement la variation de la moyenne par rapport à la période glissante précédente (même durée).
2. Afficher la variation avec un signe explicite, un indicateur d'orientation (hausse/baisse/stable) et le volume de tests précédemment comptabilisés.
3. Fournir un message d'état accessible (« Tendance en hausse », etc.) afin de rester conforme à nos objectifs d'accessibilité.

Ces améliorations répondent aux attentes de visibilité temporelle relevées dans les solutions concurrentes.
