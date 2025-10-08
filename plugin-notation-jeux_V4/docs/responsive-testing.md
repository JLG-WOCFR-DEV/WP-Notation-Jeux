# Contrôle manuel du rendu mobile

> _Mise à jour 2025-10-14 : ce document a été relu lors de l’audit des fichiers Markdown. Les actions détaillées sont centralisées dans [`documentation-audit-2025-10-14.md`](documentation-audit-2025-10-14.md)._

Pour valider l'affichage responsive du tableau récapitulatif :

1. Ouvrez une page WordPress contenant le shortcode `[jlg_tableau_recap]` dans votre navigateur.
2. Activez les outils de développement (F12) puis activez le mode d'émulation mobile avec une largeur maximale de 768 px.
3. Faites défiler le tableau horizontalement pour confirmer l'apparition de la barre de défilement lorsque nécessaire.
4. Vérifiez que chaque cellule affiche son libellé via `data-label` au-dessus de la valeur et que les badges conservent une taille lisible.
5. Parcourez la pagination pour confirmer que les boutons restent accessibles et qu'ils disposent d'un espacement suffisant au toucher.

En cas d'ajustements ultérieurs, répétez ce scénario pour garantir que l'expérience mobile reste optimale.

## Bloc de notation – Statut éditorial et guides associés

Pour contrôler le nouveau bandeau de statut et la colonne des guides dans le bloc `[jlg_rating_block]` :

1. Créez un article avec le bloc de notation configuré pour afficher un statut éditorial (« Brouillon », « Mise à jour », etc.) et au moins deux guides liés (dont un sans URL).
2. Ouvrez la prévisualisation front dans un navigateur puis activez le mode d'émulation mobile (largeur entre 360 px et 768 px).
3. Vérifiez que le bandeau de statut s'affiche au-dessus des catégories, que le libellé et le message complémentaire restent lisibles et qu'aucune troncature ne survient.
4. Faites défiler jusqu'à la section des guides : la liste doit passer sous le bloc de notes, conserver des espacements suffisants et afficher les guides sans lien comme du texte simple.
5. Placez le focus clavier successivement sur les liens de guides : assurez-vous que le focus est visible et que la navigation ne provoque pas de défilement inattendu.

## Game Explorer – Panneau de filtres mobile

Pour vérifier l'interaction mobile du Game Explorer (`[jlg_game_explorer]`) :

1. Affichez le shortcode sur une page publique, activez le mode d'émulation mobile (largeur ≤ 767 px).
2. Vérifiez que le bouton « Filtres » est visible et que son attribut `aria-expanded` alterne entre `true` et `false` lors de l'ouverture/fermeture.
3. Cliquez sur le bouton : un panneau coulissant doit apparaître depuis le bas de l'écran, avec un fond semi-transparent masquant l'arrière-plan.
4. Modifiez un filtre (catégorie, plateforme, studio, éditeur, disponibilité ou recherche) puis validez. Le panneau doit se refermer automatiquement, les résultats se mettre à jour et le focus clavier passer sur le premier élément des résultats.
5. Testez le bouton « Réinitialiser » : il doit fermer le panneau, remettre les filtres par défaut et replacer le focus sur les résultats.
6. Utilisez le nouveau champ « Note minimale » pour sélectionner plusieurs seuils (ex. ≥ 8) et confirmez que seuls les tests correspondants restent visibles, que la valeur sélectionnée est persistée dans l’URL et que le panneau mobile se referme après validation.

## Carte verdict du bloc tout-en-un

Pour vérifier le rendu responsive de la carte verdict du shortcode/bloc `[jlg_bloc_complet]` :

1. Créez un test avec les champs **Résumé court**, **Texte du bouton verdict** et **URL du bouton verdict** renseignés, puis sélectionnez un statut « Mise à jour en cours ».
2. Affichez la page contenant le bloc tout-en-un sur un écran ≤ 768 px. Vérifiez que le statut reste lisible et que la date de mise à jour s’affiche sous forme de phrase.
3. Réduisez à 320 px : le titre « Verdict de la rédaction » et le bouton doivent passer sur deux lignes sans chevauchement. Le focus clavier sur le bouton doit être visible.
4. Agrandissez la fenêtre (>1024 px) et contrôlez que le résumé conserve une largeur raisonnable, que le statut ne se déforme pas et que le CTA aligne correctement son ombre portée.

## Comparatif plateformes `[jlg_platform_breakdown]`

1. Préparez un article avec au moins trois entrées plateformes, dont une marquée « Meilleure expérience » et une recommandation contenant un long texte (>180 caractères).
2. Affichez le shortcode/bloc sur un écran ≤ 480 px : chaque ligne doit se transformer en carte verticale avec les libellés `data-label` visibles au-dessus des valeurs et sans débordement horizontal. Le badge doit rester centré et lisible.
3. Passez en mode tablette (768 px – 1024 px) : vérifier que les colonnes se réorganisent en grille deux colonnes, que la colonne commentaire se limite à trois lignes max et que les ellipses sont présentes si le texte est tronqué.
4. Agrandissez à ≥ 1280 px : la table doit afficher toutes les colonnes sur une ligne, conserver une largeur maximale cohérente et aligner les badges sur la colonne plateforme.
5. Activez `prefers-reduced-motion` (outil dev) et confirmez qu’aucune transition superflue ne persiste lors du survol des lignes.
6. Injectez un filtre `jlg_platform_breakdown_entries` renvoyant du HTML (balise `<strong>` autorisée, `<script>` bloqué) et validez que seul le HTML autorisé est rendu.
