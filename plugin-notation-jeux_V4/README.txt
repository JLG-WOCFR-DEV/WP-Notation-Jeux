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

* **Système de notation flexible** : 6 catégories personnalisables notées sur 10
* **Multiples shortcodes** : bloc de notation, fiche technique, points forts/faibles, taglines bilingues
* **Notation utilisateurs** : Permettez à vos lecteurs de voter
* **Tableau récapitulatif** : Vue d'ensemble de tous vos tests avec tri et filtrage
* **Widget** : Affichez vos derniers tests notés
* **API RAWG** : Remplissage automatique des informations de jeu
* **SEO optimisé** : Support schema.org pour les rich snippets Google
* **Thèmes visuels** : Mode clair et sombre avec personnalisation complète
* **Gestion dynamique des plateformes** : Ajoutez, triez et réinitialisez vos plateformes depuis l'onglet Plateformes
* **Responsive** : Parfaitement adapté mobile et tablette

= Gestion des plateformes =

Accédez à l'onglet **Plateformes** depuis le menu d'administration **Notation – JLG** > **Plateformes**. Vous pouvez y :

* Ajouter de nouvelles plateformes pour enrichir vos fiches de test ;
* Réordonner et supprimer les plateformes existantes selon vos besoins ;
* Réinitialiser la liste pour revenir à la configuration par défaut grâce à l'option **Reset**.

= Validation des métadonnées =

* La **date de sortie** est vérifiée avec `DateTime::createFromFormat('Y-m-d')`. Une valeur invalide est rejetée, la méta concernée est supprimée et une notice d'administration affiche les erreurs repérées.
* Le champ **PEGI** n'accepte que les mentions officielles `PEGI 3`, `PEGI 7`, `PEGI 12`, `PEGI 16` et `PEGI 18`. Toute autre valeur est ignorée et signalée via la même notice.
* Les formulaires d'édition utilisent un champ HTML `type="date"` et les réponses de l'API RAWG sont normalisées pour renvoyer le format `AAAA-MM-JJ` ainsi qu'une valeur PEGI conforme lorsque disponible, garantissant une expérience cohérente.

= Shortcodes disponibles =

* `[jlg_bloc_complet]` (alias `[bloc_notation_complet]`) — Bloc tout-en-un combinant notation, points forts/faibles et tagline. Principaux attributs : `post_id` (ID de l'article ciblé), `style` (`moderne`, `classique`, `compact`), `afficher_notation`, `afficher_points`, `afficher_tagline` (valeurs `oui`/`non`), `couleur_accent`, `titre_points_forts`, `titre_points_faibles`. Remplace l'utilisation combinée des shortcodes `[bloc_notation_jeu]`, `[jlg_points_forts_faibles]` et `[tagline_notation_jlg]` pour un rendu unifié.
* `[bloc_notation_jeu]` - Bloc de notation principal
* `[jlg_fiche_technique]` - Fiche technique du jeu
* `[tagline_notation_jlg]` - Phrase d'accroche bilingue
* `[jlg_points_forts_faibles]` - Points positifs et négatifs
* `[notation_utilisateurs_jlg]` - Système de vote pour les lecteurs
* `[jlg_tableau_recap]` - Tableau/grille récapitulatif

== Utilisation dans les widgets et blocs ==

* Les shortcodes du plugin peuvent être insérés dans les widgets classiques (Texte, Code) ou via le bloc **Shortcode** de
  l'éditeur. Dès que l'un d'eux est exécuté, un indicateur global est levé et déclenche le chargement des feuilles de style
  et scripts requis, même si la page affichée n'est pas un article singulier ou que le contenu principal ne contient aucun
  shortcode.
* Pour valider le comportement, placez par exemple `[jlg_tableau_recap]` dans un widget ou un bloc de barre latérale et
  affichez une page ou une archive : les assets `jlg-frontend` et `jlg-user-rating` sont chargés automatiquement dès le
  rendu du widget, assurant la même mise en forme que dans un article.

== Installation ==

1. Téléchargez le plugin et décompressez l'archive
2. Uploadez le dossier `plugin-notation-jeux` dans `/wp-content/plugins/`
3. Activez le plugin depuis le menu 'Extensions' de WordPress
4. Configurez le plugin dans 'Notation - JLG' > 'Réglages'
5. Créez votre premier test avec notation !

== Tests manuels de sécurité CSS ==

Pour valider que des options malicieuses ne génèrent pas de CSS invalide :

1. Dans l'administration WordPress, ouvrez **Notation – JLG > Réglages**.
2. Dans la section *Tableau Récapitulatif*, saisissez `transparent` dans **Fond des lignes** et `#123456; background:red;` dans **Gradient 1**.
3. Enregistrez les réglages puis affichez un article utilisant les shortcodes du plugin.
4. Dans le code source de la page, repérez le bloc `<style id="jlg-frontend-inline-css">` :
   * Vérifiez que `--jlg-table-row-bg-color` reste à `transparent` sans aucune règle supplémentaire.
   * Vérifiez que `--jlg-score-gradient-1` ne contient pas de fragment `background:red;` et qu'elle revient à la couleur par défaut du plugin (la portion malveillante est supprimée).
5. Restaurez ensuite des couleurs hexadécimales valides pour confirmer que l'affichage retrouve ses couleurs.

== Frequently Asked Questions ==

= Comment personnaliser les catégories de notation ? =

Allez dans Notation - JLG > Réglages et modifiez les libellés des 6 catégories selon vos besoins.

= Le plugin est-il compatible avec mon thème ? =

Le plugin est conçu pour être compatible avec tous les thèmes WordPress standards. Des options de personnalisation CSS sont disponibles.

= Puis-je désactiver certains modules ? =

Oui, vous pouvez activer/désactiver individuellement : notation utilisateurs, taglines, animations, schema SEO.

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
