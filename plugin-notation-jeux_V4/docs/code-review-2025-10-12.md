# Revue de code – 12 octobre 2025

## Objectifs
- Cartographier les zones de complexité qui freinent les évolutions (helpers vidéo, front controller, sanitisation des réglages).
- Identifier les opportunités de refactoring structurants et de nettoyage des dépendances.
- Proposer des pistes d’outillage pour fiabiliser les changements (tests, séparation des responsabilités).

## Constats principaux
### Points positifs
- Les helpers fournissent une couverture fonctionnelle riche (gestion des migrations d’échelle, métadonnées, embeds vidéo) sans dépendre d’extensions tierces.【F:plugin-notation-jeux_V4/includes/Helpers.php†L17-L205】【F:plugin-notation-jeux_V4/includes/Helpers.php†L2183-L2294】
- Le contrôleur `Frontend` concentre tous les hooks nécessaires pour les shortcodes, AJAX et REST, ce qui évite la dispersion des points d’entrée WordPress.【F:plugin-notation-jeux_V4/includes/Frontend.php†L23-L200】
- Les blocs Gutenberg partagent une configuration homogène (scripts, traductions, callbacks) centralisée dans `Blocks`.【F:plugin-notation-jeux_V4/includes/Blocks.php†L13-L192】

### Risques / dettes
1. **Helpers monolithique** : `Helpers` mélange plusieurs domaines (options, migrations, formats vidéo, métadonnées) dans une même classe statique. La méthode `get_review_video_embed_data` dépasse 100 lignes et s’appuie sur plusieurs helpers privés spécifiques par fournisseur, ce qui complique l’ajout d’une nouvelle plateforme ou la couverture par tests unitaires ciblés.【F:plugin-notation-jeux_V4/includes/Helpers.php†L44-L205】
2. **Frontend stateful** : `Frontend` maintient de nombreux états statiques (`$instance`, `$assets_enqueued`, `$rendered_shortcodes`, throttling user rating). Cette approche couplée au hook global rend difficile l’injection de dépendances (ex. logger, service user rating) et favorise les effets de bord lors des tests ou des évolutions (p. ex. exécuter plusieurs suites dans la même requête).【F:plugin-notation-jeux_V4/includes/Frontend.php†L23-L200】
3. **Sanitisation des réglages** : `Admin\Settings::sanitize_options` traite tous les champs dans une méthode de plus de 150 lignes, mélangeant normalisation, validation, déclenchement de migrations et traitement de cas particuliers (checkbox, listes, seuils). Le flux conditionnel est difficile à étendre et masque les règles métier (ex. arrondi du badge, clamp des post types).【F:plugin-notation-jeux_V4/includes/Admin/Settings.php†L32-L197】

## Recommandations de refactoring
1. **Isoler la logique vidéo**
   - Créer une classe dédiée (`VideoEmbedFactory` ou service par fournisseur) instanciée via `Helpers`. Chaque fournisseur (YouTube, Vimeo, Twitch, Dailymotion) exposerait `supports_url()` et `build_embed()` pour limiter les branches conditionnelles et faciliter les tests unitaires par provider.
   - Déplacer les messages d’erreur et libellés dans un tableau de configuration pour réduire les doublons et ouvrir la voie à un mapping dynamique.
2. **Segmenter le Frontend**
   - Extraire trois responsabilités :
     - `ShortcodeRegistry` (chargement / suivi des shortcodes et erreurs admin).
     - `AssetController` (gestion des assets différés et de la parité front/éditeur).
     - `UserRatingController` (AJAX, REST, throttling, persistance).
   - Injecter ces services dans `Frontend` via le constructeur ou un container léger afin de supprimer les propriétés statiques et améliorer la testabilité.
3. **Refactorer la sanitisation**
   - Définir un schéma déclaratif (tableau associatif) décrivant type, contraintes et callbacks par champ ; itérer sur ce schéma pour appliquer les transformations. Cela rend la logique plus lisible et évite les `if` imbriqués.
   - Extraire les traitements spécifiques (migrations d’échelle, arrondi du badge, filtres Game Explorer) dans des méthodes ou services dédiés, appelés après validation pour clarifier l’ordre des opérations.

## Opportunités de tests & outillage
- Ajouter des tests unitaires ciblés pour chaque fournisseur vidéo une fois la logique extraite (mock `Validator`, vérifier l’URL iframe générée selon les options).
- Couvrir les services `UserRating` et `AssetController` avec des tests qui vérifient les hooks enregistrés et l’invalidation des caches.
- Introduire des tests de sanitisation basés sur des jeux de données pour valider les règles déclaratives (formats, valeurs limites) sans devoir instancier tout WordPress.

## Nettoyage des dépendances
- Aucune dépendance Composer inutile détectée, mais l’extraction des services ci-dessus permettrait d’introduire progressivement des namespaces dédiés (`JLG\Notation\Video`, `JLG\Notation\Frontend`) sans impacter l’autoload PSR-4 existant.

Ces chantiers graduels réduisent la complexité cyclomatique, facilitent l’ajout de nouvelles plateformes et fiabilisent le workflow de tests avant de livrer de nouvelles fonctionnalités.
