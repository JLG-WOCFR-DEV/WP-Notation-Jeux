# Benchmark – 2025-10-07

> _Mise à jour 2025-10-14 : ce document a été relu lors de l’audit des fichiers Markdown. Les actions détaillées sont centralisées dans [`documentation-audit-2025-10-14.md`](documentation-audit-2025-10-14.md)._

## Références analysées
- **IGN** – Portails tests & fiches jeux : filtrage avancé (plateformes, genres, notes), timelines d'actualités, intégration vidéo systématique.
- **GameSpot** – Hub tests avec résumé en tuiles, différenciation claire pro / lecteurs, filtres dynamiques et cartes plates-formes.
- **OpenCritic** – Agrégateur orienté data : indicateurs de tendance (« Mighty », « Weak »), segmentation par plateformes/éditions, API publique performante.

## Évaluation de la base actuelle
| Critère | Référence pro | Plugin WP Notation |
| --- | --- | --- |
| **Fiabilité des métadonnées** | OpenCritic expose des timestamps unifiés (UTC) et des API cohérentes | ✨ Amélioration : remplacement des `current_time('timestamp')` par une récupération normalisée pour éviter les dérives de fuseau |
| **Transparence debug** | GameSpot/IGN disposent de consoles internes exportables (JSON) | ✨ Amélioration : logs admin convertis en JSON lisible + sanitation renforcée |
| **Internationalisation** | IGN/GameSpot déclinent accents/uppercase correctement pour l'index alphabétique | ✨ Amélioration : suppression des silencieux `@iconv` et gestion Unicode durcie |
| **Qualité des métaboxes** | GameSpot admin distingue visuellement plateformes & CTA, support multi-plateforme strict | ✨ Amélioration : sélection stricte des plateformes + contrôles nonce maintenus |

## Chantiers complémentaires identifiés
1. **Parité « pro vs lecteurs »** : implémenter une vue côte à côte style GameSpot combinant score rédaction, score lecteurs et historique (sparkline) – prévoir stockage statistique.
2. **Badges de tendance** : inspiré d'OpenCritic, ajouter des badges dynamiques (« À surveiller », « Coup de cœur ») basés sur poids des catégories et variance des notes.
3. **Comparateur multi-jeux** : IGN propose des comparatifs directs (ex. « vs »). Ajouter un shortcode pour sélectionner deux jeux et comparer critères pondérés.
4. **Streaming & vidéo** : automatiser l’enrichissement des fiches via API YouTube/Twitch avec fallback poster, car IGN/GameSpot multiplient les formats.
5. **API partenaire** : documenter un endpoint JSON read-only (score + métadonnées) pour intégrer le plugin dans des apps mobiles, à l’image de l’API OpenCritic.

Ces améliorations guideront les prochaines itérations produit pour se rapprocher des expériences professionnelles identifiées.
