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

## Quick wins (sous 4 semaines)

| Action | Description | Impact utilisateur | Pré-requis | Indicateurs de succès |
| --- | --- | --- | --- | --- |
| **Amplifier les insights existants** | Ajouter dans `[jlg_score_insights]` un encart « Focus rédaction vs lecteurs » avec calcul d'écart absolu et badges d'écart (>1,5 pts). | Permet aux rédactions de repérer instantanément les divergences fortes avec le public. | Réutiliser les métriques déjà calculées en PHP, pas de nouvelle table. | Taux de clics sur les badges dans les tests A/B ; adoption par au moins 3 rédactions pilotes. |
| **Préparer le mode « review en cours »** | Introduire une métadonnée `jlg_review_status` (Draft / In progress / Final) gérée via un sélecteur dans la metabox actuelle et affichée discrètement dans les shortcodes existants. | Rassure sur la fraîcheur de la review sans attendre la refonte complète du template. | Simple migration de métadonnées, compatibilité ascendante. | 100 % des nouveaux tests utilisent le statut ; diminution des retours « review outdated ». |
| **Guides associés via taxonomies** | Ajouter un bloc latéral optionnel qui interroge les articles partageant la même taxonomie `guide` ou `astuce` et expose jusqu’à 4 liens. | Offre des contenus contextuels type GameSpot avec un effort limité. | Requêtes WP_Query existantes + paramétrage dans l'onglet Réglages. | CTR sur les guides > 8 % sur les pages tests. |

## Recommandations priorisées (vision 3-6 mois)
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

## Plan d'implémentation détaillé

| Lot | Périmètre technique | Impacts UX & contenus | Dépendances | Points de vigilance |
| --- | --- | --- | --- | --- |
| **L1 – Verdict & statut** | Nouveau sous-bloc Gutenberg + extension du shortcode `[jlg_bloc_complet]` ; champ personnalisé `jlg_review_status`; tâche cron `jlg_update_review_status`. | Mise en avant d’un encart verdict (titre, résumé, CTA « Lire le verdict complet ») ; badge de statut en haut du bloc. | Synchronisation i18n (strings `__`/`_x`), mise à jour Schema JSON-LD (`reviewStatus`). | Assurer rétrocompatibilité avec thèmes surchargés ; ajouter tests PHPUnit sur le mapping statut → rendu. |
| **L2 – Comparateur plateformes** | Nouveau shortcode/bloc `jlg_platform_breakdown`; extension de la metabox pour saisir fps, résolution, mode ; option toggle dans Réglages. | Tableau responsive 3 colonnes (Plateforme / Performance / Commentaire), badges « Meilleure expérience ». | Données d'entrée : champs par plateforme ; potentiellement API tierces (Digital Foundry) en futur. | Gérer cas sans données (fallback); limiter largeur pour mobile (scroll horizontal ARIA). |
| **L3 – Deals & disponibilités** | CPT ou métadonnées répétables `jlg_deal_*`; widget/shortcode; intégration future API prix. | Modules deals dans bloc complet + widget dédié; boutons trackés (rel="sponsored"). | UX : charte couleurs cohérente; compliance RGPD si tracking. | Sécuriser entrée URLs/prix; prévoir hook pour devs (filtre `jlg_deals_data`). |
| **L4 – Insights & reco** | Extension `[jlg_score_insights]`; rapport CSV; panneau guides. | Graphiques sparkline (SVG), segmentation; carrousel guides. | Bibliothèque sparkline (SVG natif) ou CSS; dépend de Quick win #3. | Performance requêtes agrégées; fallback quand peu de votes. |
| **L5 – API & exports** | Endpoint REST `/jlg/v1/ratings`; commande WP-CLI; potentielle pagination. | Permet intégrations CRM/marketing; export planifié. | Authentification (nonce, clés); doc API. | RGPD (anonymisation), limiter coût DB. |

## Suivi
- Documenter toute implémentation future dans `docs/` (sous dossier `product-roadmap/` créé pour structurer la feuille de route).
- Prévoir une nouvelle passe benchmark dans 6 mois pour mesurer l’écart après implémentation des priorités P0/P1.
- Mettre à jour les KPIs (CTR guides, usage statut, exports API) dans un tableau de bord partagé avec les rédactions partenaires.
