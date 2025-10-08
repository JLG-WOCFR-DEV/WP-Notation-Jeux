# Benchmark du 2025-10-10 – Consensus & Confiance des scores

> _Mise à jour 2025-10-14 : ce document a été relu lors de l’audit des fichiers Markdown. Les actions détaillées sont centralisées dans [`documentation-audit-2025-10-14.md`](documentation-audit-2025-10-14.md)._

| Solution | Comportement observé | Impact utilisateur | Opportunité d'amélioration |
| --- | --- | --- | --- |
| **Metacritic – Critic Reviews** | Affiche un label de consensus (« Generally Favorable », « Mixed », etc.) dérivé de l'écart entre les notes des critiques et expose le nombre de critiques pour donner un niveau de confiance. | Les rédactions comme les lecteurs comprennent en un coup d'œil si le verdict est homogène ou polarisé et peuvent décider s'il faut approfondir les écarts. | Ajouter un indicateur de consensus basé sur la dispersion des scores éditoriaux (écart-type + fourchette) pour donner le même niveau de lecture rapide. |
| **OpenCritic – Badge « Strong/Mighty/Weak »** | Les agrégateurs de notes calculent un score de confiance en fonction de la variance entre critiques et affichent un badge coloré accompagné d'une explication textuelle. | Les équipes marketing identifient rapidement les jeux aux avis incertains et priorisent les contenus complémentaires (guides, mises à jour). | Fournir un message éditorial (texte et ARIA) qui explique la stabilité des scores et suggère les actions suivantes lorsqu'ils sont divergents. |

## Décision

Étendre le module `jlg_score_insights` pour calculer l'écart-type des notes éditoriales et restituer :

1. Un label de consensus (« Consensus fort », « Consensus partagé », « Avis divisés ») aligné sur les seuils observés chez Metacritic/OpenCritic.
2. La fourchette des notes (min/max) afin de contextualiser les extrêmes et d'aider les rédactions à investiguer les cas atypiques.
3. Un message accessible qui explique la situation aux lecteurs et suggère une action (ex. consulter les badges de divergence quand les avis sont divisés).

Ces ajouts rapprochent notre module des standards pro tout en restant actionnables pour les équipes éditoriales.
