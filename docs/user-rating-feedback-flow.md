# Flux de feedback – notation lecteurs

## Résumé
Ce flux décrit comment la notation lecteurs remonte désormais les feedbacks côté front et back. Les scripts JavaScript publient des événements normalisés et s’appuient sur un lecteur d’écran, tandis que le serveur agrège chaque réponse via la télémétrie interne.

## Événements front `notation.feedback.*`
- `notation.feedback.success` est déclenché après un vote validé. Le détail comprend l’identifiant du post, la note, le `feedbackCode` (`vote_recorded`) et le message affiché à l’utilisateur.【F:plugin-notation-jeux_V4/assets/js/user-rating.js†L6-L121】【F:plugin-notation-jeux_V4/assets/js/user-rating.js†L248-L272】
- `notation.feedback.error` est publié pour toute réponse en échec (login requis, doublon, erreur réseau…). Le détail expose `feedbackCode`, `message`, `postId`, `rating` et un horodatage. Les écouteurs peuvent ainsi différencier les sous-cas (`login_required`, `already_voted`, `network_error`, etc.).【F:plugin-notation-jeux_V4/assets/js/user-rating.js†L248-L313】
- Chaque évènement bulle depuis le bloc de notation, ce qui permet d’observer les feedbacks à l’échelle de la page (analytics, bannière d’état, etc.).【F:plugin-notation-jeux_V4/assets/js/user-rating.js†L64-L101】

## Annonces vocales et fallback
- Lorsque `window.jlgLiveAnnouncer` est disponible, le script relaie le message de succès/erreur via `announce()` en mode `polite` ou `assertive`.
- Un fallback `aria-live` (`#jlg-user-rating-live-region`) est injecté pour couvrir les thèmes qui n’initialisent pas l’annonceur global, garantissant l’accessibilité clavier/lecteur d’écran.【F:plugin-notation-jeux_V4/assets/js/user-rating.js†L6-L55】

## Agrégation via `Telemetry::record_event`
- Chaque réponse AJAX alimente le canal `user_rating` : code de feedback, statut HTTP, identifiant du contenu, poids du vote et, côté erreur, contexte additionnel (IP hash, throttling, token banni…).【F:plugin-notation-jeux_V4/includes/Frontend.php†L1359-L1497】【F:plugin-notation-jeux_V4/includes/Frontend.php†L1501-L1568】
- Les données sont stockées dans l’option `jlg_notation_metrics_v1` et restent limitées aux 25 derniers événements, conformément au helper Telemetry existant.【F:plugin-notation-jeux_V4/includes/Telemetry.php†L13-L75】

## Fenêtres de throttling et agrégats
- Le throttle des votes repose sur une fenêtre de 120 s et un TTL de 900 s pour l’activité récente ; ces constantes servent d’abaques lors de l’analyse des feedbacks (ex. pics de `throttled`).【F:plugin-notation-jeux_V4/includes/Frontend.php†L34-L41】
- Les agrégats renvoyés aux clients (`new_average`, `new_count`, `new_breakdown`, `new_weight`, etc.) sont sérialisés avec leur `feedbackCode` afin de corréler l’UX et la télémétrie.【F:plugin-notation-jeux_V4/includes/Frontend.php†L1551-L1568】

## Exploitation côté produit
- Les tests automatisés valident la présence des `feedbackCode` et l’enregistrement télémétrique, ce qui facilite le suivi de la feuille de route (remontée de feedbacks editors vs lecteurs).【F:plugin-notation-jeux_V4/tests/FrontendUserRatingTest.php†L18-L95】【F:plugin-notation-jeux_V4/tests/FrontendUserRatingTest.php†L275-L334】

