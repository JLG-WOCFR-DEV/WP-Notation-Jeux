# Benchmark – 16 février 2025

## Méthodologie
- Audit du plugin Notation JLG v5.0 (documentation fonctionnelle, modules courts, widgets, Game Explorer, Score Insights, statut éditorial, presets visuels et automatisations RAWG).
- Analyse comparative manuelle avec les expériences de notation professionnelles d’IGN, GameSpot et OpenCritic (parcours review, rendu front, back-office, monétisation et analytics visibles publiquement au 16/02/2025).
- Évaluation des écarts sur 4 axes : **workflow éditorial**, **expérience lecteur**, **couverture data & services tiers**, **pilotage business**.

## Synthèse rapide
| Axe | Forces actuelles | Gaps vs. pros | Opportunités priorisées |
| --- | --- | --- | --- |
| Workflow éditorial | Métadonnées complètes, automation du statut final, modules optionnels désactivables. | Pas de co-rédaction ni de versions/suivi d’historique, peu d’aide à la configuration initiale. | Assistant de setup guidé, timeline des patches, multi-rédacteurs pondérés. |
| Expérience lecteur | Badge Coup de cœur, Score Insights, Game Explorer filtrable, presets visuels. | Absence de médias riches (carrousels, résumés vidéo), pas de comparateur interactif par plateforme, pas de résumé TL;DR dynamique. | Bloc comparatif plateformes, intégration vidéo enrichie dans le bloc principal, résumé auto + deals/CTA. |
| Data & services | Remplissage RAWG, widgets, histogramme lecteurs, diagnostics latence. | Pas d’agrégation externe (OpenCritic/Metacritic), pas de deals affiliés, API d’export limitée. | Flux JSON/GraphQL partenaires, connecteurs agrégateurs critiques, deals automatisés. |
| Pilotage business | Score Insights avec tendance et consensus, widget derniers tests. | Manque d’analytics rédaction (coverage, performances), pas de reporting par auteur. | Dashboard analytics éditorial, alertes patchs/précommande, intégration newsletters. |

## Analyse détaillée
### IGN
- **Points remarquables** : mises en avant multi-supports (tabs PS5/PC), encadrés vidéos, section "Verdict" condensée, CTA d’achat affiliés par retailer, mise à jour des notes dans le temps.
- **Écarts** : le plugin ne propose pas encore de comparateur cross-plateformes ou de timeline des mises à jour pour contextualiser un changement de verdict. Les modules vidéo doivent être insérés manuellement via Gutenberg plutôt qu’un slot dédié dans `[jlg_bloc_complet]`. Il manque aussi un système de deals/affiliation automatisé.
- **Améliorations suggérées** :
  1. Étendre le shortcode `[jlg_platform_breakdown]` en bloc Gutenberg pour proposer une navigation tabulée et un badge "Meilleure expérience" dynamique (parité avec IGN) tout en conservant l’accessibilité annoncée pour le shortcode.【F:README.md†L55-L108】
  2. Ajouter un encart multimédia optionnel au bloc/all-in-one afin d’intégrer vidéo ou carrousel natif sans quitter l’écosystème du plugin.【F:README.md†L36-L54】
  3. Créer un module "Offres & DLC" reliant les champs guide existants à des liens partenaires (gestion via onglet Réglages) pour rapprocher l’approche e-commerce d’IGN.【F:README.md†L18-L32】

### GameSpot
- **Points remarquables** : notation par catégorie affichée en ligne, "The Good / The Bad" très courts, section "About the Author" pour la crédibilité, et dossiers comparatifs (sidebars, reprises dans newsletters).
- **Écarts** : bien que le plugin dispose d’un module points forts/faibles, il n’impose pas de format concis ou d’accroches automatiques. Il n’y a pas de mise en avant de l’auteur ou de la rédaction, ni d’intégration native dans des newsletters.
- **Améliorations suggérées** :
  1. Introduire un réglage pour limiter/valider la longueur des points forts/faibles et proposer des templates d’accroches (microcopy) pour harmoniser la tonalité rédactionnelle à la GameSpot.【F:README.md†L36-L108】
  2. Ajouter une section "Signature auteur" (photo, rôle, liens) dans le bloc/all-in-one pour renforcer la crédibilité et préparer des exports newsletters.【F:README.md†L36-L54】
  3. Fournir un export WP-CLI/JSON pour alimenter newsletters et pages dossiers sans ressaisie (score, tags, auteur, CTA), en écho aux reprises éditoriales GameSpot.【F:README.md†L123-L170】

### OpenCritic
- **Points remarquables** : agrégation de multiples critiques, badge "Mighty/Weak", histogrammes interactifs, suivi des tendances et alertes patchs, comparatif de notes par plateforme.
- **Écarts** : malgré Score Insights, le plugin n’agrège pas encore de données externes ni d’autres médias. Le badge Coup de cœur est interne et ne reflète pas un consensus global. Le suivi de tendance ne couvre que le site.
- **Améliorations suggérées** :
  1. Implémenter un connecteur OpenCritic (API publique) pour récupérer note moyenne globale, statut "Mighty/Weak" et afficher un encart comparatif dans Score Insights.【F:README.md†L36-L108】
  2. Étendre Score Insights pour suivre l’écart entre note interne et moyenne externe, avec alertes dans le dashboard Diagnostics en cas de divergence notable.【F:README.md†L36-L108】
  3. Ajouter un widget "Consensus externe" alimenté par les données agrégées et exportable vers partenaires via la future API/flux JSON.【F:README.md†L36-L108】【F:README.md†L123-L170】

## Roadmap proposée (12 mois)
1. **T1 – Onboarding & productivité rédaction**
   - Assistant de configuration en 4 étapes (types de contenus, modules, import démos, API RAWG).
   - Module multi-rédacteurs pondérés avec historique de modifications.
   - Limiteur de longueur + suggestions microcopy sur points forts/faibles.
2. **T2 – Expérience front enrichie**
   - Bloc Gutenberg `notation-jlg/platform-breakdown` avec navigation tabulée et badge dynamique.
   - Slot multimédia optionnel (vidéo/carrousel) dans `[jlg_bloc_complet]` et `all-in-one`.
   - Résumé TL;DR automatique (génération à partir des taglines) avec CTA configurables.
3. **T3 – Data & monétisation**
   - Connecteurs OpenCritic/Metacritic pour Score Insights + widget Consensus.
   - Module Offres & DLC (affiliation) avec suivi clics dans Diagnostics.
   - Flux JSON/WP-CLI export pour newsletters, partenaires et intégrations custom.
4. **T4 – Pilotage & alertes**
   - Dashboard analytics éditorial (couverture plateformes/genres, délais de mise à jour, alertes patchs).
   - Timeline des patchs (manuel + synchronisation RAWG) avec notification des changements de note.
   - Intégration newsletters : template MJML + API pour pousser derniers tests/notes.

## Impacts attendus
- **Adoption** : onboarding plus rapide, réduction des erreurs de configuration, motivation des rédactions multi-auteurs.
- **Engagement lecteur** : navigation cross-plateforme, médias riches, badges consensus renforcent la valeur perçue face aux références du marché.
- **Monétisation & partenariats** : deals affiliés, flux exportable et API d’agrégation ouvrent des opportunités de revenus et de syndication.
- **Pilotage qualité** : analytics éditorial et alertes patchs permettent de maintenir des reviews à jour et d’aligner l’éditorial sur les attentes des lecteurs et partenaires.
