# Shortcode & bloc « Score Insights »

Ce module affiche une synthèse statistique des notes publiées : moyenne, médiane, histogramme par tranches et classement des plateformes les plus performantes. Il repose sur le helper `Helpers::get_posts_score_insights()`.

## Shortcode `[jlg_score_insights]`

Attributs disponibles :

- `time_range` : période analysée. Valeurs prises en charge : `all`, `last_30_days`, `last_90_days`, `last_365_days`. Les intégrateurs peuvent ajouter leurs propres périodes via le filtre `jlg_score_insights_time_ranges`.
- `platform` : slug d'une plateforme enregistrée dans **Notation → Plateformes** (utiliser `sans-plateforme` pour les tests sans plateforme). Laisser vide pour inclure tous les supports.
- `platform_limit` : nombre maximum de plateformes affichées dans le classement (1 à 10, défaut : 5).
- `title` : titre personnalisé affiché dans l'en-tête du composant.

Le template `templates/shortcode-score-insights.php` expose une structure accessible : région ARIA avec résumé, `<progress>` pour les barres de l'histogramme et `<ol>` pour le classement. En l'absence de données, un message `role="status"` invite à ajuster les filtres.

## Bloc `notation-jlg/score-insights`

Dans Gutenberg, le bloc reprend ces attributs :

- Panneau **Données affichées** : sélection de la période, filtre par plateforme (slug) et limite du classement.
- Panneau **Présentation** : titre personnalisé visible sur le front.

Le bloc réutilise le shortcode côté serveur, garantissant la parité front/éditeur. L'aperçu dynamique respecte également les attributs pour vérifier le rendu avant publication.

## Bonnes pratiques

- Préparez vos slugs de plateforme dans l'administration avant de configurer le bloc afin de disposer de libellés cohérents.
- Pour analyser une période personnalisée, utilisez le filtre `jlg_score_insights_time_ranges` dans votre thème ou plugin compagnon et ajoutez une option dans le bloc via un script personnalisé si nécessaire.
- Pensez à compléter `docs/responsive-testing.md` si vous introduisez de nouvelles variations visuelles autour de ce module.
