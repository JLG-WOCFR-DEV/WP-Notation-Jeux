# Tests manuels – Options de prévisualisation du bloc de notation

## Préparation
- Activer un test de jeu avec des notes complètes (catégories renseignées, badge coup de cœur optionnel).
- Ouvrir l’éditeur Gutenberg et insérer le bloc **Bloc de notation (notation-jlg/rating-block)**.
- Vérifier que les assets d’éditeur sont bien chargés (palette couleur, panneau latéral Notation JLG).

## Scénarios à valider
1. **Comportement par défaut**  
   - Laisser "Thème de prévisualisation" sur *Selon les réglages du site* et "Animations (aperçu)" sur *Suivre la configuration globale*.  
   - Confirmer que le rendu correspond au front (même palette, animations activées/désactivées selon le réglage global).
2. **Forçage du thème sombre**  
   - Sélectionner *Forcer le thème sombre*.  
   - Les couleurs du bloc doivent passer sur le fond sombre (`--jlg-bg-color: #18181b`), le texte principal en clair et la bordure sombre, sans altérer les options enregistrées du site.
3. **Forçage du thème clair**  
   - Sélectionner *Forcer le thème clair*.  
   - Vérifier la bascule inverse (fond blanc, texte sombre) et la persistance du rendu quel que soit le thème global.
4. **Animations forcées**  
   - Placer "Animations (aperçu)" sur *Toujours activer* avec les réglages globaux désactivés.  
   - S’assurer que les classes `jlg-animate` et `is-in-view` sont appliquées automatiquement après le chargement de la prévisualisation.
5. **Animations désactivées**  
   - Choisir *Toujours désactiver*.  
   - Confirmer l’absence de la classe `jlg-animate` et la largeur immédiate des barres de progression.
6. **Placeholders (aucune note)**  
   - Tester avec un article sans notes : la carte de prévisualisation vide doit reprendre les mêmes classes et couleurs que les thèmes forcés.
7. **Rendu front**  
   - Insérer le bloc dans un article publié et prévisualiser côté front : vérifier que l’affichage reste cohérent (les options `preview_*` n’influent pas sur le rendu final par défaut).

## Points d’attention
- Vérifier le contraste du texte (≥ 4.5:1) dans les deux thèmes.
- Tester la navigation clavier : les boutons et liens restent focusables malgré les variations visuelles.
- Confirmer que les préférences `preview_theme` / `preview_animations` ne modifient pas la sauvegarde des réglages globaux.
