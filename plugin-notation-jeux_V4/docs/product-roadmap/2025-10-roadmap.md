# Feuille de route produit – Octobre 2025

Ce document décline les opportunités identifiées dans le benchmark du 5 octobre 2025 et propose une trajectoire d'exécution en trois vagues. Chaque lot inclut portée, jalons, impacts techniques et critères de succès.

## Vision
- **Objectif principal :** aligner l'expérience Notation JLG sur les standards pro (IGN, GameSpot, OpenCritic) pour renforcer la confiance éditoriale et générer des pistes de monétisation.
- **Hypothèse clé :** en livrant un verdict éditorial riche, un comparatif plateformes et des modules de conversion, nous augmentons le temps passé sur page et les clics sortants d'au moins 15 %.

## Vague 0 – Quick wins (S-4 à S)
| Deliverable | Description | Responsable | Estimation | Dépendances | KPI visé |
| --- | --- | --- | --- | --- | --- |
| Badge d'écart lecteurs vs rédaction | Ajouter dans `[jlg_score_insights]` des badges d'écart > ±1,5 points, calculés côté PHP. | Produit + Dev PHP | 2 j | Données existantes | 60 % des tests récents affichent au moins un badge. |
| Statut review | Nouveau champ `jlg_review_status` (Draft/In progress/Final) affiché sous la note globale. | Produit + Dev PHP | 1 j | Metabox actuelle | 100 % des nouvelles reviews renseignent le statut. |
| Guides associés | Bloc latéral optionnel listant 4 guides (WP_Query filtrée). | Produit + Dev PHP | 2 j | Taxonomies existantes | CTR > 8 % en analytics. |

## Vague 1 – Parcours review premium (S à S+8)
| Deliverable | Description | Piliers UX | Estimation | Notes |
| --- | --- | --- | --- | --- |
| Sous-bloc Verdict | Encapsule résumé, verdict, CTA vers review complète, statut et date de mise à jour. | Hiérarchie info, crédibilité | 6 j | Inclure toggle Gutenberg + attributs shortcode. |
| Mode « Review en cours » automatisé | Cron qui vérifie les métadonnées `last_patch_date` et bascule statut → Final après X jours. (✅ livré via `jlg_review_status_auto_finalize` + hook `jlg_review_status_transition`) | Fraîcheur du contenu | 3 j | Ajouter tests unitaires + hook `jlg_review_status_transition`. |
| Comparateur plateformes | Shortcode/bloc `jlg_platform_breakdown` avec colonnes performances, recommandations. | Guidance d'achat | 8 j | Requiert extension metabox plateformes + data structure. |

## Vague 2 – Monétisation & insights (S+8 à S+16)
| Deliverable | Description | Estimation | Dépendances | KPI |
| --- | --- | --- | --- | --- |
| Widget Deals & disponibilités | Module optionnel (shortcode + widget) avec boutons affiliés triés. | 10 j | Champs répétables, design UI dédié | +12 % clics sortants. |
| Segmentation Score Insights | Comparaison rédaction/lecteurs, sparkline, top sentiments. | 7 j | Nécessite Quick win badges | +10 % consultation onglet Insights. |
| API REST `/jlg/v1/ratings` | Expose moyennes par jeu, distribution votes, filtrable par plateforme. | 6 j | Auth WP (nonce), doc swagger simple | 3 intégrations partenaires pilotes. |
| Commande WP-CLI export CSV | `wp jlg export:ratings --from=2024-01-01` → CSV (notes, plateformes, badge). | 3 j | Endpoint REST ou requêtes directes | Utilisation mensuelle par équipes marketing. |

## Vague 3 – Extensions (S+16 et +)
- **Intégration dynamique de deals** (API partenaires, alerte baisse de prix).
- **Webhooks notifications** pour CRM / Discord.
- **Personnalisation avancée Game Explorer** (carrousels guides, onglets thématiques).

## Checkpoints & pilotage
1. **Comité produit bi-hebdo** : revue de l'avancement vs estimations, décisions go/no-go sur intégrations externes.
2. **Suivi métriques** : CTR guides, taux de remplissage statut, clics deals, usage API.
3. **Tests & QA** : chaque lot dispose d'une campagne PHPUnit + checklists manuelles (mise à jour `docs/responsive-testing.md` au besoin).
4. **Communication** : préparer changelog détaillé + captures (mode clair/sombre) à chaque release.

## Prochaines étapes
- Valider la feuille de route avec 2 rédactions partenaires et prioriser la Vague 1 pour la release 5.1.
- Évaluer l'effort design pour les nouveaux modules (maquettes Figma, guidelines). 
- Planifier un benchmark de suivi en avril 2026 pour mesurer les gains face aux concurrents.
