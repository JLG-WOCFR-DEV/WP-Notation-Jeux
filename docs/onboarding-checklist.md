# Checklist Onboarding Notation JLG

Cette checklist décrit le déroulé de l’assistant multi-étapes introduit avec Notation JLG v5.0. Elle sert de guide pour les tests manuels et pour l’accompagnement des rédactions qui découvrent le plugin.

## Pré-requis

- WordPress 5.0+ avec un compte administrateur.
- Plugin Notation JLG activé (l’assistant se déclenche automatiquement si l’option `jlg_onboarding_completed` est absente ou vaut `0`).
- Navigateur moderne avec JavaScript activé.

## Étapes de l’assistant

1. **Sélection des types de contenus**
   - ✅ Au moins un post type public doit être coché avant de pouvoir continuer.
   - ✅ Les intitulés proviennent de `get_post_types( [ 'public' => true ], 'objects' )` et sont traduits.
   - ✅ Les choix sont persistés dans `notation_jlg_settings['allowed_post_types']`.

2. **Activation des modules optionnels**
   - ✅ Au moins un module parmi `review_status_enabled`, `verdict_module_enabled`, `related_guides_enabled`, `deals_enabled`, `user_rating_enabled` doit être sélectionné.
   - ✅ Chaque carte rappelle brièvement l’impact du module côté front.
   - ✅ Les cases cochées enregistrent `1`, les autres `0` dans `notation_jlg_settings`.

3. **Préréglage visuel & thème**
   - ✅ Un preset (`signature`, `minimal`, `contrast`) doit être choisi avant de passer à l’étape suivante.
   - ✅ Le thème par défaut (`dark` ou `light`) doit être sélectionné ; il correspond à l’option `visual_theme`.
   - ✅ La validation bloque la progression en cas d’absence de choix.

4. **Clé API RAWG**
   - ✅ L’administrateur renseigne une clé de 10 caractères minimum ou coche la case « Je fournirai la clé plus tard ».
   - ✅ Lorsque la case est cochée, le champ clé est désactivé côté interface pour éviter les entrées accidentelles.
   - ✅ La clé est stockée dans `notation_jlg_settings['rawg_api_key']`. Si l’option de report est cochée, la valeur sauvegardée reste vide.

## Comportements attendus

- L’assistant reste accessible via `wp-admin/admin.php?page=jlg-notation-onboarding` tant que l’option `jlg_onboarding_completed` n’est pas à `1`.
- Un rappel est affiché sur la page principale **Notation – JLG** pour inciter à terminer l’onboarding.
- Après validation finale, l’assistant affiche une notification de succès et redirige vers la même URL avec `completed=1`.
- Les caches d’options sont purgés via `Helpers::flush_plugin_options_cache()` afin que les nouveaux réglages soient immédiatement pris en compte.

## Tests manuels rapides

- [ ] Activer/désactiver chaque module dans l’assistant et vérifier la mise à jour correspondante dans les réglages.
- [ ] Lancer l’assistant, passer à l’étape 4, cocher « Je fournirai la clé plus tard » puis terminer : l’option `rawg_api_key` doit rester vide.
- [ ] Réinitialiser `jlg_onboarding_completed` à `0`, recharger l’admin et vérifier la redirection automatique vers l’assistant.
- [ ] Tester la navigation clavier : les boutons « Précédent » et « Continuer » doivent être focusables et respecter l’ordre logique.

Pour tout ajustement futur (nouvelle étape, validations supplémentaires), mettre à jour cette checklist ainsi que la documentation utilisateur.
