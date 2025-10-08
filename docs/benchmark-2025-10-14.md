# Benchmark & Code Review – 14 octobre 2025

## Résumé exécutif
- **Objectif produit :** vérifier que l’expérience de notation JLG reste compétitive face aux sites de référence (IGN, OpenCritic) et identifier les écarts fonctionnels prioritaires.
- **Portée du test :** focus sur le module Score Insights (vue synthétique des notes) et sur la restitution des statistiques de consensus côté front.

## Benchmark fonctionnel
| Plateforme | Fonctionnalités pertinentes | Impact utilisateur | Constat pour Notation JLG |
| --- | --- | --- | --- |
| **IGN.com – Review Template** | Carte verdict compacte : tagline, note, label « Editor’s Choice », nombre de reviewers, CTA vers la conclusion. | Résumé immédiat avec contexte (combien de journalistes ont contribué) rassurant sur la fiabilité de la note. | Le bloc verdict JLG s’aligne sur le format (taglines, badge « Coup de cœur »), mais n’affiche pas le volume d’articles/notes agrégés. |
| **OpenCritic – Game Page** | Bandeau statistique listant moyenne, écart-type et compteur « Based on N Critic Reviews », colorimétrie par niveau de consensus. | L’utilisateur saisit d’emblée la dispersion et la taille de l’échantillon, ce qui renforce la crédibilité et la comparaison rapide. | Le module Score Insights calcule déjà moyenne/médiane, histogramme et label de consensus via `Helpers::build_consensus_summary()`, mais n’expose pas la taille d’échantillon au rédacteur ni au lecteur. |

### Principales opportunités
1. **Indiquer la taille d’échantillon** directement dans le panneau consensus pour s’aligner sur IGN/OpenCritic et clarifier la fiabilité du verdict agrégé.
2. **Mettre en avant les cas d’usage « badge divergence »** (écart forte entre rédaction et lecteurs) dans la documentation pour faciliter le storytelling éditorial.

## Revue de code ciblée
- `Helpers::build_consensus_summary()` calcule déjà `$count`, l’écart-type et la fourchette mais ne retourne pas d’objet décrivant la taille d’échantillon. Le template `templates/shortcode-score-insights.php` ne peut donc pas informer les lecteurs sur le nombre de tests agrégés.
- Les tests `HelpersScoreInsightsAggregationTest` et `ShortcodeScoreInsightsTemplateTest` valident la logique actuelle, mais n’échoueraient pas si un affichage du volume manquait, ce qui laisse un angle mort UX.

## Actions recommandées
- Étendre la structure de retour de `build_consensus_summary()` avec un bloc `sample` (nombre + libellé i18n) et l’exposer dans le template + CSS.
- Couvrir ce nouveau champ par des assertions PHPUnit pour sécuriser la régression.
- Mettre à jour la documentation (README + fiche Score Insights) afin que les rédactions comprennent l’usage du compteur d’articles.
