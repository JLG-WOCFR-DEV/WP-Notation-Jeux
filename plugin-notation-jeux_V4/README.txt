=== Notation JLG - Système de notation pour tests de jeux vidéo ===
Contributors: jeromelegousse
Tags: rating, review, games, notation, gaming
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 5.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Système de notation complet et personnalisable pour les tests de jeux vidéo avec multiples shortcodes et widgets.

**Note :** Cette documentation est également disponible en version Markdown dans `README.md`. Veillez à maintenir les deux fichiers synchronisés.

== Description ==

Le plugin Notation JLG est un système complet de notation spécialement conçu pour les sites de tests de jeux vidéo. Il offre une solution professionnelle pour noter et présenter vos reviews de manière attrayante et structurée.

= Fonctionnalités principales =

* **Système de notation flexible** : 6 catégories personnalisables avec un barème ajustable (par défaut sur 10)
* **Multiples shortcodes** : bloc de notation, fiche technique, points forts/faibles, taglines bilingues
* **Notation utilisateurs** : Permettez à vos lecteurs de voter, visualisez la répartition des notes dans un histogramme accessible mis à jour en direct et laissez le script AJAX empêcher les doubles soumissions. L'option *Connexion obligatoire avant le vote* autorise au besoin la restriction aux membres connectés.
* **Badge coup de cœur** : Activez un badge éditorial lorsque la note dépasse un seuil configurable et affichez en parallèle la moyenne des lecteurs ainsi que l'écart avec la rédaction
* **Tableau récapitulatif** : Vue d'ensemble de tous vos tests avec tri et filtrage
* **Nom de jeu personnalisé** : Remplacez le titre WordPress dans les tableaux, widgets et données structurées
* **Widget** : Affichez vos derniers tests notés
* **Intégration vidéo enrichie** : Les helpers détectent désormais automatiquement YouTube, Vimeo, Twitch et Dailymotion pour générer un lecteur embarqué respectant les paramètres recommandés
* **API RAWG** : Remplissage automatique des informations de jeu
* **SEO optimisé** : Support schema.org pour les rich snippets Google
* **Thèmes visuels** : Mode clair et sombre avec personnalisation complète
* **Sélecteur de couleurs enrichi** : Profitez du color picker WordPress avec saisie libre (y compris `transparent` lorsque pris en charge)
* **Accessibilité renforcée** : Les animations respectent la préférence système "réduire les mouvements" et la navigation du Game Explorer annonce désormais la page active (aria-current) tout en proposant des repères de focus visibles, y compris sur mobile. Sur smartphone, un bouton « Filtres » accessible (aria-expanded/aria-controls) ouvre un panneau coulissant qui se referme automatiquement après application et replace le focus sur la liste de résultats.
* **Gestion dynamique des plateformes** : Ajoutez, triez et réinitialisez vos plateformes depuis l'onglet Plateformes
* **Responsive** : Parfaitement adapté mobile et tablette

= Histogramme des votes lecteurs =

Le shortcode `[notation_utilisateurs_jlg]` affiche désormais un histogramme 1→5 étoiles accessible (ARIA) afin de détailler la répartition des votes. Chaque participation déclenche un rafraîchissement instantané des barres, respecte la préférence `prefers-reduced-motion` et bloque l'interface pendant l'appel AJAX pour éviter tout double envoi.

= Gestion des plateformes =

Accédez à l'onglet **Plateformes** depuis le menu d'administration **Notation – JLG** > **Plateformes**. Vous pouvez y :

* Ajouter de nouvelles plateformes pour enrichir vos fiches de test ;
* Réordonner et supprimer les plateformes existantes selon vos besoins ;
* Réinitialiser la liste pour revenir à la configuration par défaut grâce à l'option **Reset**.

= Validation des métadonnées =

* La **date de sortie** est vérifiée avec `DateTime::createFromFormat('Y-m-d')`. Une valeur invalide est rejetée, la méta concernée est supprimée et une notice d'administration affiche les erreurs repérées.
* Le champ **PEGI** n'accepte que les mentions officielles `PEGI 3`, `PEGI 7`, `PEGI 12`, `PEGI 16` et `PEGI 18`. Toute autre valeur est ignorée et signalée via la même notice.
* Le champ **Nom du jeu** est normalisé (espaces superflus, longueur maximale) avant sauvegarde pour garantir un affichage homogène.
* Les formulaires d'édition utilisent un champ HTML `type="date"` et les réponses de l'API RAWG sont normalisées pour renvoyer le format `AAAA-MM-JJ` ainsi qu'une valeur PEGI conforme lorsque disponible, garantissant une expérience cohérente.

= Shortcodes disponibles =

* `[jlg_bloc_complet]` (alias `[bloc_notation_complet]`) — Bloc tout-en-un combinant notation, points forts/faibles et tagline. Principaux attributs : `post_id` (ID de l'article ciblé), `style` (`moderne`, `classique`, `compact`), `afficher_notation`, `afficher_points`, `afficher_tagline` (valeurs `oui`/`non`), `couleur_accent`, `titre_points_forts`, `titre_points_faibles`, `display_mode` (`absolute` ou `percent`). Remplace l'utilisation combinée des shortcodes `[bloc_notation_jeu]`, `[jlg_points_forts_faibles]` et `[tagline_notation_jlg]` pour un rendu unifié.
* `[bloc_notation_jeu]` - Bloc de notation principal. Attributs : `post_id` (ID du test), `score_layout` (`text` ou `circle`), `animations` (`oui`/`non`), `accent_color`, `display_mode` (`absolute` ou `percent`) pour choisir entre une note affichée en valeur absolue ou en pourcentage, ainsi que `preview_theme` (`light` ou `dark`) et `preview_animations` (`inherit`, `enabled`, `disabled`) pour forcer un thème et simuler l’état des animations dans les aperçus (éditeur, shortcodes dans Gutenberg, etc.). Lorsque le badge « Coup de cœur » est activé dans les réglages et que la note atteint le seuil défini, le bloc met en avant la sélection de la rédaction et affiche la moyenne lecteurs ainsi que le delta.
* `[jlg_fiche_technique]` - Fiche technique du jeu. Attributs : `post_id` (optionnel, ID d'un test publié à afficher ; sinon l'article courant est utilisé), `champs` (liste de champs séparés par des virgules) et `titre`.
* `[tagline_notation_jlg]` - Phrase d'accroche bilingue
* `[jlg_points_forts_faibles]` - Points positifs et négatifs
* `[notation_utilisateurs_jlg]` - Système de vote pour les lecteurs avec histogramme dynamique (barres accessibles ARIA, mise à jour en temps réel après chaque vote)
* `[jlg_tableau_recap]` - Tableau/grille récapitulatif. Les entêtes sont triables par titre, date, note moyenne et métadonnées développeur/éditeur via `orderby=title`, `orderby=average_score`, `orderby=meta__jlg_developpeur` ou `orderby=meta__jlg_editeur`.
* `[jlg_game_explorer]` - Game Explorer interactif avec cartes et filtres dynamiques. Attributs : `posts_per_page` (nombre d'articles par page), `columns` (2 à 4 colonnes), `filters` (liste séparée par des virgules parmi `letter`, `category`, `platform`, `developer`, `publisher`, `availability`), `categorie`, `plateforme` et `lettre` pour forcer un filtrage initial. La navigation (lettres, filtres, tri et pagination) reste pleinement fonctionnelle sans JavaScript grâce à des requêtes GET accessibles. Sur mobile, les filtres se replient dans un panneau masquable pour libérer l'écran tout en conservant l'accessibilité sans JavaScript.
* `[jlg_score_insights]` - Tableau de bord statistique mettant en avant moyenne, médiane, histogramme et plateformes dominantes sur une période donnée. Attributs : `time_range` (`all`, `last_30_days`, `last_90_days`, `last_365_days`), `platform` (slug défini dans Notation → Plateformes), `platform_limit` (1 à 10 plateformes) et `title` pour personnaliser l'entête.

== Utilisation dans les widgets et blocs ==

* Les shortcodes du plugin peuvent être insérés dans les widgets classiques (Texte, Code) ou via le bloc **Shortcode** de
  l'éditeur. Dès que l'un d'eux est exécuté, un indicateur global est levé et déclenche le chargement des feuilles de style
  et scripts requis, même si la page affichée n'est pas un article singulier ou que le contenu principal ne contient aucun
  shortcode.
* Pour valider le comportement, placez par exemple `[jlg_tableau_recap]` dans un widget ou un bloc de barre latérale et
  affichez une page ou une archive : les assets `jlg-frontend` et `jlg-user-rating` sont chargés automatiquement dès le
  rendu du widget, assurant la même mise en forme que dans un article.

== Blocs Gutenberg ==

Le plugin expose neuf blocs dynamiques prêts à l'emploi :

* **Bloc de notation** (`notation-jlg/rating-block`) — choisissez l'article ciblé ou laissez le champ vide pour utiliser le
  contenu courant, définissez la disposition (`texte` ou `cercle`), activez/désactivez les animations, sélectionnez le format du score (valeur absolue ou pourcentage) et expérimentez avec les menus de prévisualisation (thème clair/sombre, animations forcées) directement depuis la barre latérale de l’éditeur.
* **Points forts / faibles** (`notation-jlg/pros-cons`) et **Tagline bilingue** (`notation-jlg/tagline`) — affichent
  automatiquement les métadonnées saisies dans la fiche test.
* **Fiche technique** (`notation-jlg/game-info`) — sélection des champs via cases à cocher, titre personnalisable et
  ciblage d'un autre test.
* **Notation utilisateurs** (`notation-jlg/user-rating`) — intègre le module de vote AJAX pour les lecteurs, avec option pour restreindre le vote aux membres connectés.
* **Tableau récapitulatif** (`notation-jlg/summary-display`) — réglage du nombre d'entrées, du layout (table ou grille), des
  colonnes visibles et des filtres par défaut.
* **Bloc tout-en-un** (`notation-jlg/all-in-one`) — activez/désactivez chaque sous-bloc, modifiez le style, la couleur d'accent, les titres et le format du score (valeur absolue ou pourcentage).
* **Game Explorer** (`notation-jlg/game-explorer`) — configurez le tri initial, les filtres proposés et les paramètres de
  préfiltrage (catégorie, plateforme, lettre).
* **Score Insights** (`notation-jlg/score-insights`) — sélectionnez la période analysée, filtrez par plateforme et limitez le nombre de plateformes listées pour générer une synthèse accessible (moyenne, médiane, histogramme, top plateformes).
 

Chaque bloc délègue le rendu à la logique PHP historique (shortcodes) tout en appelant `JLG_Frontend::mark_shortcode_rendered()`
afin de charger automatiquement les scripts et feuilles de style requis dans l'éditeur.

== Surcharge des templates ==

Pour modifier le HTML rendu, copiez le fichier souhaité depuis `plugin-notation-jeux/templates/` vers votre thème (ou thème
enfant) sous `notation-jlg/{template}.php`. Le plugin tente d'abord de charger cette version (par exemple
`notation-jlg/shortcode-rating-block.php`, `notation-jlg/widget-latest-reviews.php`) via `locate_template()` avant de revenir à la
version interne. Deux filtres permettent des personnalisations supplémentaires :

* `jlg_frontend_template_candidates` pour ajuster la liste des chemins passés à `locate_template()` ;
* `jlg_frontend_template_path` pour modifier le chemin final inclus.

Ces points d'extension facilitent la conservation de vos surcharges lors des mises à jour du plugin.

== Installation ==

1. Téléchargez le plugin et décompressez l'archive
2. Uploadez le dossier `plugin-notation-jeux` dans `/wp-content/plugins/`
3. Activez le plugin depuis le menu 'Extensions' de WordPress
4. Configurez le plugin dans 'Notation - JLG' > 'Réglages'. La section *Contenus* vous permet de choisir les types de publications (articles, CPT publics…) autorisés pour la notation ; si besoin, un développeur peut ajuster cette liste via le filtre PHP `jlg_rated_post_types`.
5. Créez votre premier test avec notation !

== Tests manuels de sécurité CSS ==

Pour valider que des options malicieuses ne génèrent pas de CSS invalide :

1. Dans l'administration WordPress, ouvrez **Notation – JLG > Réglages**.
2. Dans la section *Tableau Récapitulatif*, utilisez le nouveau sélecteur pour saisir `transparent` dans **Fond des lignes** (le champ accepte la saisie directe) et `#123456; background:red;` dans **Gradient 1**.
3. Enregistrez les réglages puis affichez un article utilisant les shortcodes du plugin.
4. Dans le code source de la page, repérez le bloc `<style id="jlg-frontend-inline-css">` :
   * Vérifiez que `--jlg-table-row-bg-color` reste à `transparent` sans aucune règle supplémentaire.
   * Vérifiez que `--jlg-score-gradient-1` ne contient pas de fragment `background:red;` et qu'elle revient à la couleur par défaut du plugin (la portion malveillante est supprimée).
5. Restaurez ensuite des couleurs hexadécimales valides pour confirmer que l'affichage retrouve ses couleurs.

== Vérification du sélecteur de couleurs WordPress ==

1. Depuis **Notation – JLG > Réglages**, ouvrez un champ couleur (par exemple *Fond des lignes*) et sélectionnez une nouvelle teinte avec le color picker WordPress, puis sauvegardez. La valeur hex personnalisée doit être conservée après rechargement.
2. Pour un champ autorisant la transparence, saisissez `transparent` directement dans le champ texte, enregistrez puis confirmez que l'interface conserve bien la valeur `transparent`.
3. Utilisez enfin le bouton de réinitialisation du color picker pour revenir à la couleur par défaut et vérifiez que la valeur d'origine est restaurée.

== Frequently Asked Questions ==

= Comment personnaliser les catégories de notation ? =

Allez dans Notation - JLG > Réglages et modifiez les libellés des 6 catégories selon vos besoins.

= Le plugin est-il compatible avec mon thème ? =

Le plugin est conçu pour être compatible avec tous les thèmes WordPress standards. Des options de personnalisation CSS sont disponibles.

= Puis-je désactiver certains modules ? =

Oui, vous pouvez activer/désactiver individuellement : notation utilisateurs, badge « Coup de cœur », taglines, animations, schema SEO.

= Comment obtenir une clé API RAWG ? =

Créez un compte gratuit sur rawg.io/apidocs et copiez votre clé dans les réglages du plugin.

== Screenshots ==

1. Bloc de notation principal avec barres de progression
2. Interface d'administration - métabox de notation
3. Fiche technique et points forts/faibles
4. Tableau récapitulatif des tests
5. Réglages et personnalisation
6. Widget derniers tests

== Changelog ==

= 5.0 =
* Refactorisation complète du code
* Architecture modulaire optimisée
* Performance améliorée
* Interface admin modernisée
* Sécurité renforcée
* Support PHP 7.4+

= 4.0 =
* Version initiale publique

== Upgrade Notice ==

= 5.0 =
Mise à jour majeure avec refactorisation complète. Sauvegardez avant mise à jour. Migration automatique des données.
