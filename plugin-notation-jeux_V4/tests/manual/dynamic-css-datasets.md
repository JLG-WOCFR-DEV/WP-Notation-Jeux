# Jeux de données `DynamicCssBuilderTest`

Ce document recense les jeux de données utilisés dans `tests/DynamicCssBuilderTest.php`. Chaque scénario du data provider
`provideFrontendCssRootScenarios` représente une combinaison d'options et de palette à réutiliser lors de l'ajout de cas
de régression.

| Identifiant du scénario | Objectif | Points clés |
| --- | --- | --- |
| `dark theme with explicit overrides` | Valide l'écrasement complet des options pour un thème sombre. | Toutes les couleurs sont définies manuellement, `average_score` est renseigné et les valeurs dérivées (`hover`, `link`) sont vérifiées. |
| `light theme falling back to defaults when colors missing` | Garantit les retours aux valeurs par défaut du thème clair lorsque les options/palette sont vides ou invalides. | `average_score` est nul, plusieurs couleurs invalides déclenchent les valeurs de secours, les couleurs de tableau retombent sur les réglages globaux. |
| `transparent palette entries keep permitted values` | Assure la prise en charge de `transparent` et des retours de secours côté palette. | Met l'accent sur les champs autorisant `transparent` et sur les retours vers la palette secondaire lorsque `bar_bg_color` ou la tagline sont absents. |

## Ajouter un nouveau scénario

1. **Nom explicite** : utilisez un libellé de tableau parlant dans le data provider afin que le test et la présente table
   restent synchronisés.
2. **Options et palette** : partez d'un `array_merge` avec `JLG_Helpers::get_default_settings()` et surchargez uniquement
   les valeurs pertinentes pour votre régression.
3. **Vérifications ciblées** : ajoutez dans `$expected` uniquement les variables CSS pertinentes pour le bug ciblé afin de
   conserver des assertions stables.
4. **Mettre à jour la table** : documentez dans la table ci-dessus l'objectif et les points clés du nouveau scénario pour
   faciliter la maintenance.
