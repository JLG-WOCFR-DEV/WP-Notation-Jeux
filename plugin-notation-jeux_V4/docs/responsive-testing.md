# Contrôle manuel du rendu mobile

Pour valider l'affichage responsive du tableau récapitulatif :

1. Ouvrez une page WordPress contenant le shortcode `[jlg_tableau_recap]` dans votre navigateur.
2. Activez les outils de développement (F12) puis activez le mode d'émulation mobile avec une largeur maximale de 768 px.
3. Faites défiler le tableau horizontalement pour confirmer l'apparition de la barre de défilement lorsque nécessaire.
4. Vérifiez que chaque cellule affiche son libellé via `data-label` au-dessus de la valeur et que les badges conservent une taille lisible.
5. Parcourez la pagination pour confirmer que les boutons restent accessibles et qu'ils disposent d'un espacement suffisant au toucher.

En cas d'ajustements ultérieurs, répétez ce scénario pour garantir que l'expérience mobile reste optimale.

## Game Explorer – Panneau de filtres mobile

Pour vérifier l'interaction mobile du Game Explorer (`[jlg_game_explorer]`) :

1. Affichez le shortcode sur une page publique, activez le mode d'émulation mobile (largeur ≤ 767 px).
2. Vérifiez que le bouton « Filtres » est visible et que son attribut `aria-expanded` alterne entre `true` et `false` lors de l'ouverture/fermeture.
3. Cliquez sur le bouton : un panneau coulissant doit apparaître depuis le bas de l'écran, avec un fond semi-transparent masquant l'arrière-plan.
4. Modifiez un filtre (catégorie, plateforme, studio, éditeur, disponibilité ou recherche) puis validez. Le panneau doit se refermer automatiquement, les résultats se mettre à jour et le focus clavier passer sur le premier élément des résultats.
5. Testez le bouton « Réinitialiser » : il doit fermer le panneau, remettre les filtres par défaut et replacer le focus sur les résultats.
