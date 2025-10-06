# Benchmark produit – 2025-10-05

## Contexte
- **Produit analysé :** Notation JLG v5.0 (shortcodes, blocs Gutenberg, modules Game Explorer & Score Insights, remplissage RAWG, histogramme lecteurs, badge « Coup de cœur », thèmes clair/sombre, validation PEGI/date, template overrides).
- **Objectif :** comparer l’expérience offerte avec trois références du marché (IGN, GameSpot, OpenCritic) afin d’identifier les écarts et priorités d’évolution.

## Synthèse des écarts majeurs
| Axe | Notation JLG | IGN | GameSpot | OpenCritic | Opportunités |
| --- | --- | --- | --- | --- | --- |
| **Structure de review** | Bloc tout-en-un, shortcodes modulaires, badge éditorial, tagline bilingue, histogramme lecteurs | Présentation hiérarchisée (score en tête, « Verdict », encarts « + / - », vidéo review synchronisée) | Mise en avant de sections thématiques (Gameplay, Graphismes, Conclusion), modules « Review in Progress » et timelines | Agrégation multi-critiques avec indicateurs « Top Critic Average », « Critics Recommend », résumé automatique | Ajouter un template « Verdict » enrichi (résumé + CTA), prise en charge d’un mode « Review en cours » et mise en page multi-sections paramétrable. |
| **Couverture multi-plateforme** | Métadonnées plateforme + Game Explorer filtrable | Comparatifs par plateforme (performance, visuels, mode 60 FPS) | Encadrés « Platform Differences » et recommandations matérielles | Regroupement par plateforme + lien vers versions spécifiques | Ajouter un composant de comparatif plateformes (tableau performances, patches, recommandations). |
| **Engagement & communautés** | Votes lecteurs, histogramme en temps réel, Score Insights (moyenne, médiane, top plateformes) | Systèmes de commentaires, intégration réseaux sociaux, suggestions d’articles liés | Highlights communautaires, carrousel de guides/astuces, statistiques de temps de jeu | Suivi d’activité critique (Trending, Hype Meter) | Enrichir Score Insights avec segmentation (lecteurs vs rédaction), ajouter modules « Guides associés » et badges sociaux partageables. |
| **Monétisation & conversion** | Pas de module dédié | Encarts affiliés (Amazon, partenaires), bannières deals dynamiques | Boutons « Buy Now » multi-boutiques, alertes prix | Agrégateur de prix & disponibilité, wishlist | Créer un widget « Deals & disponibilités » intégrable dans bloc complet (affiliation, stocks). |
| **Automation & data** | Remplissage RAWG, validation PEGI/date, Score Insights | Lien API interne (IGN Playlist), systèmes de recommandations personnalisés | Réutilisation back-office (planning éditorial, notes partagées), intégrations CMS custom | API publique, export CSV, webhooks pour notifs | Étendre API REST du plugin (exposition des moyennes, insights, votes) et prévoir exports CSV automatiques. |

## Recommandations priorisées
1. **Template Verdict & mode « Review en cours » (P0)**
   - Ajouter un sous-bloc optionnel dans `[jlg_bloc_complet]`/bloc tout-en-un pour afficher résumé, verdict, date de mise à jour, statut (En cours / Final / Mise à jour patch). 
   - Permettre la planification d’un rappel (cron) pour transformer automatiquement le statut « En cours » en « Final » après publication d’une mise à jour.

2. **Comparateur multi-plateformes (P0)**
   - Nouveau shortcode/bloc `jlg_platform_breakdown` utilisant les métadonnées existantes pour afficher performances (fps, résolution, mode) + champs personnalisés.
   - Intégrer des microdonnées supplémentaires (`isBasedOn`, `gamePlatform`) pour aligner sur les fiches IGN/GameSpot.

3. **Widget Deals & disponibilités (P1)**
   - Module optionnel alimenté par des champs d’affiliation (lien, prix, vendeur) et compatible avec l’API RAWG pour récupérer la disponibilité régionale.
   - Prise en charge de liens trackés (Amazon, FNAC, PS Store) et possibilité de trier par meilleur prix.

4. **Segmentation Insights & recommandations croisées (P1)**
   - Étendre `[jlg_score_insights]` avec comparaison rédaction/lecteurs, évolution temporelle (sparkline) et surfaces de points forts/faibles les plus cités.
   - Ajouter un panneau « Guides associés / articles connexes » alimenté par taxonomies (catégories, plateformes) pour rivaliser avec les carrousels GameSpot.

5. **API & exports avancés (P2)**
   - Exposer via REST `/jlg/v1/ratings` les moyennes par jeu, la distribution des votes et les notes par plateforme.
   - Ajouter une commande WP-CLI `jlg export:ratings` pour générer un CSV consolidé (notes, plateformes, badge coup de cœur, moyenne lecteurs) facilitant les usages marketing.

## Suivi
- Documenter toute implémentation future dans `docs/` (sous dossier `product-roadmap/` à créer si nécessaire).
- Prévoir une nouvelle passe benchmark dans 6 mois pour mesurer l’écart après implémentation des priorités P0/P1.
