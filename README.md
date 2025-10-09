# Notation JLG v5.0

Le dépôt regroupe la version 5.0 du plugin WordPress **Notation JLG**, un système complet de notation destiné aux sites de tests de jeux vidéo. Il fournit un rendu professionnel pour vos reviews avec des shortcodes prêts à l'emploi, un widget et des helpers PHP pour intégrer la note partout dans votre thème.

## Présentation rapide
- **Fonctionnalités clés :** 6 catégories de notes personnalisables avec badge « Coup de cœur » éditorial, notation lecteurs avec histogramme dynamique, remplissage RAWG, validation PEGI/date/nom du jeu, Game Explorer filtrable, Score Insights (moyenne, médiane, histogramme, top plateformes, tendance & consensus), tableau récapitulatif triable, widget des derniers tests, indicateur de statut éditorial (brouillon/mise à jour/final) avec automatisation configurable pour repasser les reviews en « Version finale » après X jours sans patch, et guides associés configurables pour orienter les lecteurs vers des contenus complémentaires.
- **Prérequis techniques :** WordPress 5.0 minimum et PHP 7.4 ou supérieur, vérifiés automatiquement à l’activation du plugin.
- **Architecture :** le cœur du plugin charge dynamiquement les composants admin et front-office, inclut un widget et expose des fonctions helper globales (`jlg_notation()`, `jlg_get_post_rating()`, `jlg_display_post_rating()`).

## Installation et configuration initiale
1. **Installer le plugin** depuis ce dépôt (copier `plugin-notation-jeux_V4` dans `wp-content/plugins/`) puis l’activer depuis le menu *Extensions* de WordPress.
2. **Remplir les metaboxes** dédiées aux notes et aux détails du test dans l’éditeur d’articles : les six catégories sont notées sur 10 et la metabox principale capture fiche technique, plateformes, taglines bilingues, points forts/faibles, etc.
3. **Configurer l’onglet Réglages** (`Notation – JLG > Réglages`) pour ajuster libellés, présentation de la note globale, thèmes clair/sombre, couleurs sémantiques, effets neon/pulsation et modules optionnels. La section *Contenus* permet désormais de sélectionner les types de publications (articles, CPT publics…) autorisés pour la notation ; au besoin, un développeur peut toujours compléter ou restreindre cette liste via le filtre PHP `jlg_rated_post_types`. Le module *Statut de review* propose un toggle « Finalisation auto du statut » ainsi qu’un champ « Délai avant finalisation » (en jours) pour laisser le cron `jlg_review_status_auto_finalize` ramener automatiquement les tests en « Version finale » après vérification des patchs.
4. **Gérer les plateformes** dans l’onglet dédié afin d’ajouter, trier, supprimer ou réinitialiser la liste proposée dans les metaboxes.
5. **Saisir la clé RAWG (facultatif)** dans la section *API* des réglages pour activer le remplissage automatique des données de jeu.

- **Shortcodes principaux** :
  - `[jlg_bloc_complet]` (alias `[bloc_notation_complet]`) pour afficher en une seule fois notation, points forts/faibles et tagline avec de nombreux attributs (`post_id`, `style`, `couleur_accent`, etc.).
  - `[bloc_notation_jeu]`, `[jlg_fiche_technique]`, `[jlg_points_forts_faibles]`, `[tagline_notation_jlg]`, `[notation_utilisateurs_jlg]`, `[jlg_tableau_recap]`, `[jlg_game_explorer]`, `[jlg_score_insights]`, `[jlg_platform_breakdown]` pour construire des mises en page modulaires ; le module de vote affiche désormais un histogramme dynamique accessible (barres ARIA, rafraîchies en direct). Lorsque le badge « Coup de cœur » est activé et que la note atteint le seuil défini dans les réglages, le bloc de notation met en avant la sélection de la rédaction, affiche la moyenne des lecteurs ainsi que l'écart avec la rédaction, expose l’état éditorial du test (brouillon, mise à jour en cours, version finale) et propose une liste de guides liés lorsque l’option est activée. La nouvelle carte verdict respecte les critères WCAG 2.2 : résumé, statut et CTA sont restitués avec focus visibles, libellés ARIA et overrides par shortcode/bloc pour adapter le message aux différents canaux.
- **Blocs Gutenberg** :
  - `notation-jlg/rating-block` pour gérer format du score (texte/cercle), animations, thème clair/sombre, visibilité de la carte verdict et son contenu.
  - `notation-jlg/all-in-one` pour activer/désactiver chaque sous-composant (taglines, verdict, vidéo, points forts/faibles) et personnaliser style, titres et couleur d’accent sans sacrifier l’accessibilité clavier.
  - `notation-jlg/game-info`, `notation-jlg/pros-cons`, `notation-jlg/tagline`, `notation-jlg/user-rating` pour afficher automatiquement les métadonnées saisies.
  - `notation-jlg/summary-display`, `notation-jlg/game-explorer`, `notation-jlg/score-insights` pour proposer tableau, explorateur filtrable et tableau de bord analytique directement depuis Gutenberg.
  - `notation-jlg/platform-breakdown` pour restituer le comparatif multi-plateformes avec navigation en onglets, badge « Meilleure expérience » configurable, titre personnalisé et message de fallback lorsque la metabox est vide.
- **Widget « Derniers tests »** : activé automatiquement, il peut être ajouté depuis *Apparence > Widgets* grâce au registre `JLG_Latest_Reviews_Widget`.
- **Game Explorer & Score Insights** : `[jlg_game_explorer]` propose une navigation filtrable accessible (GET), un nouveau filtre « Note minimale » pour isoler les tests les mieux notés, des panneaux responsives et un focus géré ; `[jlg_score_insights]` calcule moyenne/médiane, histogramme, podium plateformes, indicateur de tendance et label de consensus (écart-type + fourchette) sur une période configurée. Le consensus mentionne désormais explicitement le volume analysé (« Basé sur N tests publiés ») pour calquer la transparence d’IGN ou OpenCritic.
- **Presets visuels** : sélectionnez directement depuis l’onglet Réglages ou le bloc Gutenberg l’un des trois styles prêts à l’emploi (« Signature », « Minimal », « Éditorial ») pour appliquer en un clic palettes, ombres et bordures cohérentes avec vos chartes.
- **Onglet Diagnostics** : un tableau de bord intégré affiche la latence des flux critiques (Game Explorer, RAWG, votes lecteurs), permet de réinitialiser les métriques et de tester en un clic la connectivité de votre clé API RAWG.
- **Intégration vidéo enrichie** : les helpers détectent automatiquement YouTube, Vimeo, Twitch et Dailymotion pour générer un lecteur embarqué respectant les paramètres recommandés.
- **Fonctions helper** :
  - `jlg_get_post_rating()` retourne la moyenne /10 pour un article donné ; `jlg_display_post_rating()` affiche la note formatée ; `jlg_display_thumbnail_score()` injecte la note dans vos templates de vignettes ; `JLG_Frontend::mark_shortcode_rendered()` gère le chargement conditionnel des assets.
- **Templates front** : surchargez les fichiers du dossier [`templates/`](plugin-notation-jeux_V4/templates) ou utilisez les gabarits d’admin disponibles dans [`admin/templates/`](plugin-notation-jeux_V4/admin/templates) pour personnaliser le rendu des blocs et onglets.

- **Thèmes clair/sombre et palettes complètes** pour adapter l’apparence du bloc de notation, y compris les couleurs sémantiques des notes et les gradients sécurisés (filtrage anti-injection).
- **Effets Glow / Neon** configurables pour les modes texte ou cercle (intensité, pulsation, couleur dynamique ou fixe).
- **Modules optionnels** : activer/désactiver la notation utilisateurs, le badge « Coup de cœur », les taglines, les animations de barres, le schema SEO JSON-LD, les sons d’interface ou le remplissage RAWG directement depuis l’onglet Réglages.
- **CSS personnalisé** et réglages précis pour le tableau récapitulatif ou les vignettes (espacements, bordures, alternance de lignes) ainsi qu’un sélecteur couleur acceptant la valeur `transparent` lorsque pertinent.
- **Notation des lecteurs** : personnalisez couleurs et textes du module dédié et profitez d'un histogramme accessible mis à jour en direct, avec verrouillage automatique des interactions pendant le traitement AJAX pour éviter les doubles clics. Les votes peuvent, au besoin, être réservés aux membres connectés via l'option *Connexion obligatoire avant le vote* dans les réglages.
- **Gestion dynamique des plateformes** : ajoutez, triez, supprimez ou réinitialisez les plateformes proposées dans les metaboxes pour conserver des fiches homogènes.

## Comparatif plateformes & recommandations

- **Nouvelle metabox dédiée** : la section « Détails du test » accueille un comparatif par plateforme permettant de saisir performance/mode, recommandation éditoriale et badge « Meilleure expérience ». Chaque ligne peut recevoir un libellé personnalisé pour distinguer les éditions spéciales ou configurations PC.
- **Shortcode `[jlg_platform_breakdown]`** : restitue ces informations sous forme de tableau accessible (badge, colonnes filtrables, structure responsive). Le rendu reprend automatiquement le libellé des plateformes enregistrées dans l’onglet *Notation – JLG > Plateformes* et propose un message de repli configurable lorsqu’aucune donnée n’est renseignée.
- **Bloc Gutenberg `notation-jlg/platform-breakdown`** : propose le même rendu tabulé directement dans l’éditeur, préremplit l’ID du test courant, expose titre/badge/message vide comme attributs et conserve la parité front/aperçu REST.

## Ressources développeur
- **Composer** : `composer.json` définit PHP >=7.4 et fournit les scripts `composer test`, `composer cs`, `composer cs-fix` pour lancer PHPUnit et PHPCS (WPCS).
- **Gestion des traductions** : le modèle `languages/notation-jlg.pot` est prêt pour générer les fichiers `.po/.mo`.
- **Automatisation du statut** : l’événement planifié `jlg_review_status_auto_finalize` inspecte la métadonnée `_jlg_last_patch_date` et bascule les articles éligibles vers le statut « final » ; le hook `jlg_review_status_transition` se déclenche lors de chaque bascule (manuelle ou automatique) pour journaliser l’action ou notifier des services tiers.
- **Routine de désinstallation** : `uninstall.php` supprime proprement options, métadonnées et transients si l’utilisateur choisit de purger les données lors de la désactivation définitive.
- **Assets & templates** :
  - [`assets/`](plugin-notation-jeux_V4/assets) regroupe styles, scripts et images front/back.
  - [`includes/`](plugin-notation-jeux_V4/includes) contient le cœur PHP (helpers, frontend, admin, utils).
  - [`admin/templates/`](plugin-notation-jeux_V4/admin/templates) centralise les vues des onglets d’administration.
- **Tests et documentation** : suite PHPUnit couvrant les helpers principaux, scénarios de benchmark dans [`docs/`](plugin-notation-jeux_V4/docs) (Game Explorer, histogramme, responsive, Score Insights) et checklist manuelle responsive maintenue.
- **Feuille de route produit** : le sous-dossier [`docs/product-roadmap/`](plugin-notation-jeux_V4/docs/product-roadmap) décline les lots priorisés (quick wins, comparateur plateformes, deals) et fournit estimations, KPIs et dépendances pour les prochaines releases.

## Intégration continue
Un workflow GitHub Actions (`CI`) s’exécute sur chaque `push` et `pull_request`. Il installe les dépendances Composer du dossier [`plugin-notation-jeux_V4`](plugin-notation-jeux_V4), puis enchaîne deux commandes clés pour garantir la qualité :

- `composer test` pour lancer la suite PHPUnit.
- `composer cs` pour appliquer les vérifications de style via PHPCS.

Assurez-vous que ces commandes passent en local avant de soumettre une contribution.

## Synchronisation du README WordPress
La documentation WordPress (`plugin-notation-jeux_V4/README.txt`) doit rester alignée avec ce README Markdown. Toute modification de l’un doit être répercutée sur l’autre pour garantir des informations cohérentes dans l’interface WordPress.org.

Bonne contribution ! Pensez à suivre les scripts Composer avant toute PR et à conserver la parité de contenu entre les deux README.

## Pistes d’amélioration proposées

- **Assistant de configuration guidée** : proposer, dès l’activation, un onboarding en quatre étapes (types de contenus autorisés, choix des modules, import d’exemples, connexion RAWG) afin d’accélérer la mise en route et de réduire les erreurs de paramétrage constatées lors des tests utilisateurs.
- **Notation multi-contributeurs pondérée** : permettre à plusieurs rédacteurs d’évaluer un même test (pondération des catégories, annotations individuelles, historique des modifications) puis calculer automatiquement la note éditoriale publiée, à la manière des rédactions qui mettent en avant un verdict collectif.
- **Timeline de mises à jour du jeu** : enrichir la fiche technique d’un module facultatif listant les patchs majeurs et leurs impacts sur le verdict (changement de note, mise à jour des points forts/faibles), synchronisable depuis RAWG ou saisi manuellement pour suivre la vie du jeu après publication.
- **Exports & intégrations partenaires** : ajouter une commande WP-CLI et un flux JSON dédié aux partenariats médias/affiliation (résumé, verdict, liens CTA configurables) pour faciliter la syndication de la note sur d’autres plateformes et newsletters sans ressaisie.
- **Mode rédaction collaborative en temps réel** : fournir une interface multi-utilisateurs (WebSocket) permettant aux membres d’une rédaction de compléter les catégories de notation, points forts/faibles et taglines simultanément tout en conservant un historique de contributions pour les chefs de rubrique.
- **Tableau de bord analytics éditorial** : proposer un panneau synthétique côté administration affichant progression des tests, couverture par plateforme/genre, suivi des délais entre publication et mise à jour, ainsi que des alertes sur les jeux nécessitant une révision du verdict.

## Audit de la documentation – 14 octobre 2025

- **Synthèse disponible** : le mémo [`plugin-notation-jeux_V4/docs/documentation-audit-2025-10-14.md`](plugin-notation-jeux_V4/docs/documentation-audit-2025-10-14.md) recense l’ensemble des fichiers Markdown relus, les fonctionnalités à prioriser (verdict enrichi, comparateur plateformes `jlg_platform_breakdown`, module deals & disponibilités, extension Score Insights) ainsi que les refactorings en cours (architecture vidéo, segmentation du `Frontend`, schéma de sanitisation).
- **Actions transverses** : aligner les READMEs, consolider les checklists QA (responsive, blocs Gutenberg) et programmer un nouvel audit T1 2026 pour confronter la roadmap aux benchmarks IGN/GameSpot/OpenCritic.

### Vérification du style
- Exécutez `composer install` pour récupérer les dépendances de développement (WordPress Coding Standards).
- Lancez `composer cs` avant chaque commit afin de vérifier que vos modifications respectent le standard défini dans `phpcs.xml.dist`.
- Si besoin, `composer cs-fix` peut corriger automatiquement une partie des avertissements avant un passage manuel.
