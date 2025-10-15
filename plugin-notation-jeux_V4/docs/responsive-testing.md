# Contrôle manuel du rendu mobile

> _Mise à jour 2025-10-14 : ce document a été relu lors de l’audit des fichiers Markdown. Les actions détaillées sont centralisées dans [`documentation-audit-2025-10-14.md`](documentation-audit-2025-10-14.md)._

Pour valider l'affichage responsive du tableau récapitulatif :

1. Ouvrez une page WordPress contenant le shortcode `[jlg_tableau_recap]` dans votre navigateur.
2. Activez les outils de développement (F12) puis activez le mode d'émulation mobile avec une largeur maximale de 768 px.
3. Faites défiler le tableau horizontalement pour confirmer l'apparition de la barre de défilement lorsque nécessaire.
4. Vérifiez que chaque cellule affiche son libellé via `data-label` au-dessus de la valeur et que les badges conservent une taille lisible.
5. Parcourez la pagination pour confirmer que les boutons restent accessibles et qu'ils disposent d'un espacement suffisant au toucher.

En cas d'ajustements ultérieurs, répétez ce scénario pour garantir que l'expérience mobile reste optimale.

## Widget « Derniers Tests » en sidebar

1. Placez le widget « Notation JLG : Derniers Tests » dans une sidebar active puis affichez une page contenant cette zone sur desktop (≥ 1280 px).
2. Vérifiez que le titre reprend bien la valeur configurée dans le widget et que la liste des tests reste limitée au nombre attendu (par défaut : 5) sans duplication lors du rafraîchissement.
3. Réduisez la largeur à 768 px puis à 360 px : la liste doit conserver des espacements suffisants, les liens rester accessibles au toucher et la typographie rester lisible.
4. Naviguez au clavier (Tab/Shift+Tab) depuis le contenu principal vers la sidebar : le focus doit suivre l'ordre logique, chaque lien doit afficher un état `:focus-visible` net et l'annonce d'absence de tests (« Aucun test trouvé ») doit être lisible si la liste est vide.
5. Actualisez la page après avoir ajouté un nouveau test noté et vérifiez que le widget se met à jour après quelques minutes (cache court) ou immédiatement après purge manuelle via le hook `jlg_latest_reviews_widget_cache_flushed`.

## Bloc de notation – Statut éditorial et guides associés

Pour contrôler le nouveau bandeau de statut et la colonne des guides dans le bloc `[jlg_rating_block]` :

1. Créez un article avec le bloc de notation configuré pour afficher un statut éditorial (« Brouillon », « Mise à jour », etc.) et au moins deux guides liés (dont un sans URL).
2. Ouvrez la prévisualisation front dans un navigateur puis activez le mode d'émulation mobile (largeur entre 360 px et 768 px).
3. Vérifiez que le bandeau de statut s'affiche au-dessus des catégories, que le libellé et le message complémentaire restent lisibles et qu'aucune troncature ne survient.
4. Faites défiler jusqu'à la section des guides : la liste doit passer sous le bloc de notes, conserver des espacements suffisants et afficher les guides sans lien comme du texte simple.
5. Placez le focus clavier successivement sur les liens de guides : assurez-vous que le focus est visible et que la navigation ne provoque pas de défilement inattendu.
6. Repassez en largeur ≥ 1024 px : le corps du bloc doit se réorganiser en deux colonnes (catégories à gauche, deals/guides à droite). Vérifiez que la colonne latérale reste collée à l'écran lors du défilement (comportement `position: sticky`) sans masquer le contenu principal et qu'un espacement constant subsiste entre les deux panneaux.

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

## Bloc « Notation express »

Pour garantir la cohérence du nouveau bloc express (score, badge, CTA) :

1. Insérez le bloc « Notation express » avec une note de 9.2/10, un badge « Coup de cœur » et un bouton affilié.
2. En mode bureau (≥ 1280 px), vérifiez que le nombre, le badge et le CTA restent alignés sur une seule ligne, que la jauge progresse en douceur et que le focus clavier sur le bouton affiche un anneau visible.
3. Passez à 768 px : le badge doit se repositionner sous la note sans casser l’espacement, la jauge conserve une largeur pleine et le CTA garde un padding confortable.
4. À 360 px, contrôlez que la note reste lisible (taille de police ≥ 2.2 rem), que le message de placeholder apparaît si la note est vide et que le CTA s’étire en pleine largeur sans dépasser du conteneur.
5. Activez `prefers-reduced-motion` via DevTools et confirmez que la jauge ne déclenche plus d’animation brusque tout en conservant le pourcentage statique.
