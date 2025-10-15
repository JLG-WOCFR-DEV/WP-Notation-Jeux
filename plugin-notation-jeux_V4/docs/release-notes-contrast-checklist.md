# Checklist contraste pour notes de version

## Objectif
Documenter, dans chaque note de version, la procédure de vérification du contraste lorsque les variables de couleurs personnalisables du plugin sont modifiées.

## Étapes à inclure dans les notes de version
1. **Lister les variables impactées** : mentionner toute variable CSS `--jlg-color-*`, `--jlg-score-gradient-*` ou `--jlg-focus-*` modifiée.
2. **Contrôle automatique** :
   - Lancer Lighthouse (ou axe DevTools) sur un modèle de page intégrant le bloc de notation.
   - Capturer les scores Accessibilité et Contraste, puis intégrer la capture d'écran dans les notes de version.
3. **Contrôle manuel** :
   - Vérifier au moins deux combinaisons : thème clair et thème sombre.
   - Mesurer le contraste des paires texte/fond avec l'outil https://color.review/ ou https://webaim.org/resources/contrastchecker/.
   - Confirmer que les ratios atteignent **4.5:1 pour le texte normal** et **3:1 pour les éléments larges** (critère RGAA 3.2 / WCAG 1.4.3).
4. **Valeurs recommandées** :
   - Garder une différence de luminosité (ΔL) supérieure à 45 entre `--jlg-main-text-color` et `--jlg-bg-color`.
   - Limiter la saturation des dégradés `--jlg-score-gradient-*` pour conserver un ratio ≥ 3:1 sur les titres superposés.
   - Ajouter dans la note de version un tableau « Couleurs personnalisées » listant les hexadécimaux finaux et les ratios mesurés.
5. **Checklist finale** : clôturer la note de version par un encart « Accessibilité validée » cochant : navigation clavier re-testée, contraste vérifié, mode réduit de mouvement confirmé.

## Modèle de bloc à réutiliser
```markdown
### Accessibilité
- [x] Tests clavier (navigation, focus visible)
- [x] Contraste vérifié (clair/sombre) – ratios ≥ 4.5:1 / 3:1
- [x] Préférence « réduire les animations » respectée
- [ ] Remarques complémentaires : _à compléter_
```
