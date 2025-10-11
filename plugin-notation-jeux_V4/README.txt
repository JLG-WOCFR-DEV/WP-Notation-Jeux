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

* **Assistant de configuration multi-étapes** : déclenché automatiquement après l’activation, il guide la sélection des types de contenus notés, des modules essentiels, du preset visuel et de la clé RAWG. Un rappel apparaît tant que l’option `jlg_onboarding_completed` n’est pas validée et l’assistant reste accessible depuis `wp-admin/admin.php?page=jlg-notation-onboarding`.
* **Système de notation flexible** : 6 catégories personnalisables avec barème ajustable (par défaut sur 10), badge « Coup de cœur » éditorial déclenché par seuil et indicateur de statut (brouillon, mise à jour, version finale).
* **Statut éditorial & guides associés** : affichez en un coup d’œil l’état du test et proposez automatiquement une liste de guides liés configurables depuis les réglages.
* **Multiples shortcodes** : bloc de notation, fiche technique, points forts/faibles, taglines bilingues, Game Explorer, Score Insights (tendance, consensus et indice de confiance) et tableau récapitulatif ; le bloc principal met en avant badge, moyenne lecteurs, écart rédaction, statut éditorial et guides liés lorsque les modules sont activés.
* **Blocs Gutenberg** : neuf blocs dynamiques (notation, tout-en-un, fiche technique, points forts/faibles, tagline, notation lecteurs, tableau récapitulatif, Game Explorer, Score Insights) garantissant la parité éditeur/front.
* **Notation utilisateurs** : votes AJAX, histogramme accessible rafraîchi en direct, verrouillage anti double clic et option *Connexion obligatoire avant le vote*.
* **Tableau récapitulatif & Game Explorer** : vues triables/filtrables avec navigation accessible sans JavaScript et panneaux responsives.
* **Score Insights** : tableau de bord statistique (moyenne, médiane, histogramme, plateformes dominantes) filtrable par période et plateforme, agrémenté d'un indicateur de tendance comparant la moyenne à la période précédente, d'un label de consensus basé sur l'écart-type et d'un compteur de tests (« Basé sur N tests publiés ») pour contextualiser la fiabilité de l'échantillon. Un indice de confiance (limité/modéré/élevé) aide les rédactions à prioriser les prochaines publications.
* **API REST partenaires** : l’endpoint `/jlg/v1/ratings` expose scores rédaction/lecteurs, histogrammes, statuts éditoriaux et plateformes pour chaque review avec pagination, filtres (`platform`, `search`, `status`, `from`, `to`, `orderby`) et agrégats globaux (voir la documentation `docs/rest-api-ratings.md`).
* **Commande WP-CLI** : `wp jlg export:ratings` produit un CSV (colonnes note rédaction, moyenne lecteurs, delta, badge, plateformes) filtrable par statut, période, plateforme ou recherche (`--status`, `--from`, `--to`, `--platform`, `--search`).
* **Presets visuels** : appliquez instantanément l’un des styles fournis (Signature, Minimal, Éditorial) depuis les réglages ou le bloc Gutenberg pour harmoniser palettes, ombres et bordures.
* **Onglet Diagnostics** : surveillez la latence des flux (Game Explorer, RAWG, votes lecteurs), réinitialisez les métriques et testez la connexion RAWG directement depuis l’administration.
* **Nom de jeu personnalisé** : remplace le titre WordPress dans tableaux, widgets et données structurées.
* **Widgets « Derniers tests » & « Deals & disponibilités »** : mettez en avant vos dernières reviews notées et les offres affiliées configurées dans les metaboxes (tri par prix, CTA, disclaimer et attributs `rel` personnalisés).
* **Intégration vidéo enrichie** : détection automatique YouTube, Vimeo, Twitch, Dailymotion pour un embed conforme.
* **API RAWG** : remplissage automatique des informations de jeu avec validation (dates, PEGI, nom normalisé).
* **SEO optimisé** : schema.org (JSON-LD) activable et métadonnées cohérentes.
* **Thèmes visuels & effets** : mode clair/sombre, palettes complètes, effets Glow/Neon et sélecteur couleur acceptant `transparent`.
* **Accessibilité renforcée** : respect de `prefers-reduced-motion`, focus visibles, aria-current sur la navigation et boutons de filtres annotés.
* **Gestion dynamique des plateformes** : ajoutez, triez, supprimez ou réinitialisez depuis l'onglet Plateformes.
* **Responsive** : design adapté mobile/tablette et chargement conditionnel des assets via `JLG_Frontend::mark_shortcode_rendered()`.
* **Sous-bloc verdict éditorial** : affichez un résumé court, le statut du test, la date de mise à jour et un CTA dédié dans le bloc tout-en-un.

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

= Carte verdict & statut éditorial =

* Une section **📝 Verdict de la rédaction** est disponible dans la metabox principale : saisissez un résumé court (HTML léger autorisé), un libellé et une URL pour le bouton de verdict.
* Le statut éditorial (`Brouillon`, `Mise à jour en cours`, `Version finale`) reste géré via le sélecteur existant et s'affiche dans la carte verdict avec la date de dernière mise à jour calculée automatiquement.
* Le champ « Dernier patch vérifié » pilote désormais une finalisation automatique : après le délai configuré, le cron `jlg_review_status_auto_finalize` repasse la review en « Version finale » et déclenche le hook `jlg_review_status_transition` pour vos intégrations.
* Si l'URL est vide, le permalien de l'article est utilisé pour proposer un CTA « Lire le test complet ».

= Comparatif plateformes & recommandations =

* La metabox **Détails du test** propose une grille « Comparatif plateformes » pour détailler performance/mode, recommandation éditoriale et marquage « Meilleure expérience » plateforme par plateforme. Chaque ligne accepte un libellé personnalisé afin de distinguer éditions spéciales ou configurations PC.
* Le shortcode `[jlg_platform_breakdown]` restitue ces informations dans un tableau accessible : badge mis en avant, colonnes lisibles sur mobile grâce aux libellés `data-label` et message personnalisable lorsqu’aucune donnée n’est disponible.
* Le bloc Gutenberg `notation-jlg/platform-breakdown` offre le même rendu tabulé dans l’éditeur : navigation en onglets, badge « Meilleure expérience », titre et message vide configurables, parité complète avec l’aperçu REST.

= Shortcodes disponibles =

* `[jlg_bloc_complet]` (alias `[bloc_notation_complet]`) — Bloc tout-en-un combinant notation, points forts/faibles, tagline et carte verdict. Principaux attributs : `post_id` (ID de l'article ciblé), `style` (`moderne`, `classique`, `compact`), `afficher_notation`, `afficher_points`, `afficher_tagline`, `afficher_verdict` (valeurs `oui`/`non`), `couleur_accent`, `titre_points_forts`, `titre_points_faibles`, `display_mode` (`absolute` ou `percent`). Remplace l'utilisation combinée des shortcodes `[bloc_notation_jeu]`, `[jlg_points_forts_faibles]` et `[tagline_notation_jlg]` pour un rendu unifié.
* `[bloc_notation_jeu]` - Bloc de notation principal. Attributs : `post_id` (ID du test), `score_layout` (`text` ou `circle`), `animations` (`oui`/`non`), `accent_color`, `display_mode` (`absolute` ou `percent`) pour choisir entre une note affichée en valeur absolue ou en pourcentage, ainsi que `preview_theme` (`light`, `dark` ou `auto`) et `preview_animations` (`inherit`, `enabled`, `disabled`) pour forcer un thème et simuler l’état des animations dans les aperçus (éditeur, shortcodes dans Gutenberg, etc.). Le panneau « Contexte de test » ajoute `test_platforms` (plateformes couvertes), `test_build` (build ou patch vérifié) et `validation_status` (`in_review`, `needs_retest`, `validated`) afin d’exposer en front un rappel dynamique sur la fiabilité de la note (avertissement, re-test planifié, validation confirmée).
  La carte verdict associée est désormais accessible (focus visibles, rôles ARIA) et peut être personnalisée depuis le shortcode ou les blocs Gutenberg pour adapter résumé, CTA et statut à chaque campagne.
* `[jlg_fiche_technique]` - Fiche technique du jeu. Attributs : `post_id` (optionnel, ID d'un test publié à afficher ; sinon l'article courant est utilisé), `champs` (liste de champs séparés par des virgules) et `titre`.
* `[tagline_notation_jlg]` - Phrase d'accroche bilingue
* `[jlg_points_forts_faibles]` - Points positifs et négatifs
* `[notation_utilisateurs_jlg]` - Système de vote pour les lecteurs avec histogramme dynamique (barres accessibles ARIA, mise à jour en temps réel après chaque vote)
* `[jlg_tableau_recap]` - Tableau/grille récapitulatif. Les entêtes sont triables par titre, date, note moyenne et métadonnées développeur/éditeur via `orderby=title`, `orderby=average_score`, `orderby=meta__jlg_developpeur` ou `orderby=meta__jlg_editeur`.
* `[jlg_game_explorer]` - Game Explorer interactif avec cartes et filtres dynamiques. Attributs : `posts_per_page` (nombre d'articles par page), `columns` (2 à 4 colonnes), `filters` (liste séparée par des virgules parmi `letter`, `category`, `platform`, `developer`, `publisher`, `availability`, `year`, `score`, `search`), `categorie`, `plateforme`, `lettre`, `note_min` et `recherche` pour forcer un filtrage initial. La navigation (lettres, filtres, tri et pagination) reste pleinement fonctionnelle sans JavaScript grâce à des requêtes GET accessibles. Sur mobile, les filtres — y compris le sélecteur « Note minimale » — se replient dans un panneau masquable pour libérer l'écran tout en conservant l'accessibilité sans JavaScript.
* `[jlg_score_insights]` - Tableau de bord statistique mettant en avant moyenne, médiane, histogramme et plateformes dominantes sur une période donnée, avec badges signalant tout écart supérieur à ±1,5 point entre rédaction et lecteurs. Attributs : `time_range` (`all`, `last_30_days`, `last_90_days`, `last_365_days`), `platform` (slug défini dans Notation → Plateformes), `platform_limit` (1 à 10 plateformes) et `title` pour personnaliser l'entête.
* `[jlg_platform_breakdown]` - Comparatif plateformes affichant performance/mode, recommandation éditoriale et badge « Meilleure expérience ». Attributs : `post_id` (ID du test ciblé), `title` (titre optionnel), `show_best_badge` (`yes`/`no`), `highlight_badge_label` (libellé du badge) et `empty_message` pour définir le texte affiché lorsqu’aucune donnée n’est disponible.

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
  contenu courant, définissez la disposition (`texte` ou `cercle`), activez/désactivez les animations, sélectionnez le format du score (valeur absolue ou pourcentage), contrôlez la visibilité de la carte verdict et ajustez son résumé/CTA directement depuis la barre latérale de l’éditeur. Un panneau « Contexte de test » permet de renseigner plateformes, build et statut de validation tout en affichant un double aperçu clair/sombre instantané via `BlockPreview`.
* **Points forts / faibles** (`notation-jlg/pros-cons`) et **Tagline bilingue** (`notation-jlg/tagline`) — affichent
  automatiquement les métadonnées saisies dans la fiche test.
* **Fiche technique** (`notation-jlg/game-info`) — sélection des champs via cases à cocher, titre personnalisable et
  ciblage d'un autre test.
* **Notation utilisateurs** (`notation-jlg/user-rating`) — intègre le module de vote AJAX pour les lecteurs, avec option pour restreindre le vote aux membres connectés.
* **Tableau récapitulatif** (`notation-jlg/summary-display`) — réglage du nombre d'entrées, du layout (table ou grille), des
  colonnes visibles et des filtres par défaut.
* **Bloc tout-en-un** (`notation-jlg/all-in-one`) — activez/désactivez chaque sous-bloc (taglines, verdict, vidéo, points forts/faibles), modifiez le style, la couleur d'accent, les titres et le format du score (valeur absolue ou pourcentage) tout en respectant l’accessibilité clavier.
* **Game Explorer** (`notation-jlg/game-explorer`) — configurez le tri initial, les filtres proposés et les paramètres de
  préfiltrage (catégorie, plateforme, lettre).
* **Score Insights** (`notation-jlg/score-insights`) — sélectionnez la période analysée, filtrez par plateforme et limitez le nombre de plateformes listées pour générer une synthèse accessible (moyenne, médiane, histogramme, top plateformes) accompagnée d'un indicateur de tendance vs la période précédente, d'un label de consensus (écart-type + fourchette des notes) et d'un indice de confiance inspiré d'OpenCritic. Le panneau affiche aussi le volume agrégé (« Basé sur N tests publiés ») pour s’aligner sur les benchmarks IGN/OpenCritic.
* **Comparatif plateformes** (`notation-jlg/platform-breakdown`) — affiche le comparatif multi-plateformes configuré dans la metabox, avec badge « Meilleure expérience » optionnel, message de fallback et titre personnalisable directement depuis la barre latérale de Gutenberg.
 

Chaque bloc délègue le rendu à la logique PHP historique (shortcodes) tout en appelant `JLG_Frontend::mark_shortcode_rendered()`
afin de charger automatiquement les scripts et feuilles de style requis dans l'éditeur.

== Surcharge des templates ==

Pour modifier le HTML rendu, copiez le fichier souhaité depuis `plugin-notation-jeux/templates/` vers votre thème (ou thème
enfant) sous `notation-jlg/{template}.php`. Le plugin tente d'abord de charger cette version (par exemple
`notation-jlg/shortcode-rating-block.php`, `notation-jlg/widget-latest-reviews.php`) via `locate_template()` avant de revenir à la
version interne. Deux filtres permettent des personnalisations supplémentaires :

* `jlg_frontend_template_candidates` pour ajuster la liste des chemins passés à `locate_template()` ;
* `jlg_frontend_template_path` pour modifier le chemin final inclus.
* `jlg_latest_reviews_widget_query_args` pour personnaliser la requête `WP_Query` du widget « Derniers Tests » (filtrage, tri, pagination avancée).

Ces points d'extension facilitent la conservation de vos surcharges lors des mises à jour du plugin.

== Documentation & feuille de route ==

* Consultez le dossier `docs/` pour retrouver les benchmarks concurrents, la checklist responsive, les guides fonctionnels (Game Explorer, histogramme, Score Insights) ainsi que le guide d’onboarding détaillé `docs/onboarding-checklist.md` pour valider chaque étape de la configuration.
* Le sous-dossier `docs/product-roadmap/` regroupe la feuille de route détaillée (quick wins, comparateur plateformes, deals) avec estimations, KPIs et dépendances pour organiser les prochaines releases.

== Installation ==

1. Téléchargez le plugin et décompressez l'archive
2. Uploadez le dossier `plugin-notation-jeux` dans `/wp-content/plugins/`
3. Activez le plugin depuis le menu 'Extensions' de WordPress
4. Suivez l’assistant multi-étapes qui s’ouvre automatiquement pour définir les types de contenus autorisés, activer les modules essentiels, choisir un preset visuel et renseigner la clé RAWG (vous pourrez le relancer via `wp-admin/admin.php?page=jlg-notation-onboarding`).
5. Configurez le plugin dans 'Notation - JLG' > 'Réglages' (modules, finalisation automatique du statut, délai avant retour en version finale et ajustements fins de la sélection de contenus)
6. Créez votre premier test avec notation !

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

Oui, vous pouvez activer/désactiver individuellement : notation utilisateurs, badge « Coup de cœur », taglines, animations, schema SEO ainsi que la finalisation automatique du statut de review.

= Comment obtenir une clé API RAWG ? =

Créez un compte gratuit sur rawg.io/apidocs et copiez votre clé dans les réglages du plugin.

== Pistes d’amélioration proposées ==

* **Optimisation de l’assistant** : instrumenter le parcours multi-étapes (statistiques de complétion, abandons par écran, tests A/B sur les messages) afin de détecter les frictions restantes et d’afficher des recommandations contextuelles (modules populaires, presets suggérés selon le thème actif).
* **Timeline de mises à jour du jeu** : ajouter un module optionnel à la fiche technique recensant les patchs majeurs et leurs impacts sur la note (delta, points forts/faibles révisés), synchronisable avec RAWG ou saisi manuellement pour suivre la vie du jeu.
* **Exports & intégrations partenaires** : étendre la commande WP-CLI (`wp jlg export:ratings`) et proposer un flux JSON orienté syndication (résumé, verdict, liens CTA configurables) afin de diffuser facilement la note vers des sites partenaires ou newsletters sans ressaisie.
* **Mode rédaction collaborative en temps réel** : concevoir une interface multi-utilisateurs (WebSocket) afin que plusieurs rédacteurs puissent remplir les catégories, points forts/faibles et taglines simultanément, avec historisation des contributions pour validation éditoriale.
* **Tableau de bord analytics éditorial** : intégrer un panneau côté administration retraçant la progression des tests, la couverture par plateforme/genre, le délai entre publication et mises à jour ainsi que des alertes suggérant une révision du verdict.

== Audit de la documentation – 14 octobre 2025 ==

* **Synthèse disponible** : consultez `docs/documentation-audit-2025-10-14.md` pour retrouver les priorités produit (verdict enrichi, comparateur plateformes `jlg_platform_breakdown`, module deals & disponibilités, extension Score Insights) et les chantiers de refactoring (architecture vidéo, segmentation du `Frontend`, schéma de sanitisation).
* **Actions transverses** : maintenir la parité README/README.txt, enrichir les checklists QA (responsive, blocs Gutenberg) et planifier un audit T1 2026 pour confronter la roadmap aux benchmarks IGN/GameSpot/OpenCritic.

== Screenshots ==

1. Bloc de notation principal avec barres de progression
2. Interface d'administration - métabox de notation
3. Fiche technique et points forts/faibles
4. Tableau récapitulatif des tests
5. Réglages et personnalisation
6. Widgets deals & derniers tests

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
