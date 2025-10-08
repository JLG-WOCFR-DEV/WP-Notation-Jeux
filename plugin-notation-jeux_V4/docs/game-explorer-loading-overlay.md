# Game Explorer – Surcouche de chargement

> _Mise à jour 2025-10-14 : ce document a été relu lors de l’audit des fichiers Markdown. Les actions détaillées sont centralisées dans [`documentation-audit-2025-10-14.md`](documentation-audit-2025-10-14.md)._

## Contexte
- La grille met désormais à disposition l'attribut `data-loading-text` côté PHP afin que le message "Chargement…" reste disponible même sans initialisation JavaScript.
- Le script `game-explorer.js` synchronise ce texte avec la localisation front (`strings.loading`) et maintient `aria-busy` à jour pour les lecteurs d'écran. Un fallback commun (`Chargement…`) est partagé entre PHP et JavaScript via `DEFAULT_LOADING_TEXT` pour éviter les désynchronisations.

## Vérification visuelle
1. Forcer la classe `is-loading` sur `.jlg-game-explorer` (ou déclencher une requête AJAX via les filtres) et confirmer l'apparition de la bulle centrée avec le message traduit.
2. En mode sombre (palette par défaut), contrôler le contraste : le fond semi-opaque `rgba(15, 23, 42, 0.92)` reste lisible avec la couleur de texte `var(--jlg-ge-text)`.
3. En mode clair (thème hôte), ajuster les variables CSS du conteneur (`--jlg-ge-card-bg`, `--jlg-ge-text`, etc.) et s'assurer que la pseudo-surcouche hérite bien de ces valeurs, conservant un contraste suffisant.
4. Vérifier que la grille repasse automatiquement en `aria-busy="false"` une fois le fragment AJAX injecté.

## Notes d'accessibilité
- `role="status"` + `aria-live="polite"` sont conservés : l'annonce "Chargement…" est relayée pendant la requête puis le contenu final est vocalisé.
- Ne pas retirer `aria-busy` côté DOM : le script alterne explicitement entre `true` et `false` pour éviter les annonces fantômes.
- Le focus est recentré sur le premier élément interactif disponible après chaque rafraîchissement pour réduire la désorientation clavier.
