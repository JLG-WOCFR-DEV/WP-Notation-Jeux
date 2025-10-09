# Benchmark – 15 octobre 2025

## Objectif
Comparer la restitution des indicateurs de fiabilité des notes éditoriales sur des références professionnelles afin de guider l'évolution du module **Score Insights**.

## Panel analysé
| Plateforme | Fonctionnalités pertinentes | Impact utilisateur | Écart constaté pour Notation JLG |
| --- | --- | --- | --- |
| **OpenCritic – Game Page** | Bandeau « Rating Confidence » gradué (Weak / Fair / Strong) basé sur le volume de critiques agrégées. Le libellé est accompagné d'un message pédagogique expliquant la solidité de l'échantillon. | L'utilisateur sait immédiatement si la moyenne affichée repose sur un large panel. | `Score Insights` affiche la taille de l'échantillon mais n'en déduit aucun indice de confiance exploitable par la rédaction. |
| **IGN – Review Template 2025** | Cartouche « Verdict » listant le nombre de testeurs, un rappel du contexte (« Reviewed on PC, PS5, Xbox ») et un encart indiquant si suffisamment d'articles sont publiés pour considérer la note finale. | Les rédactions peuvent communiquer en externe le niveau de certitude associé au verdict ; les lecteurs perçoivent le sérieux du processus éditorial. | Notre module ne propose qu'un compteur brut : aucune graduation « échantillon limité / robuste » n'est affichée, ni message d'action pour planifier des retours. |

## Enseignements
1. La simple mention « Basé sur N tests » n'est pas suffisante pour des rédactions pro : un indicateur synthétique doit qualifier la fiabilité statistique.
2. Le message associé doit être actionnable (ex. suggérer de planifier d'autres reviews) afin d'alimenter le workflow éditorial.

## Décision produit
- Étendre `Helpers::build_consensus_summary()` afin que le module `Score Insights` expose un **indice de confiance** basé sur des seuils configurables (faible / modéré / élevé).
- Ajouter un message contextualisé dans le template pour guider les équipes (continuer à publier, communiquer le verdict, etc.).
- Exposer un filtre PHP (`jlg_score_insights_confidence_thresholds`) pour que les sites à gros volume puissent personnaliser les seuils.
