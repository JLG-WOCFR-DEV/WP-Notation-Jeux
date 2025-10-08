# Histogramme de la notation utilisateurs

> _Mise à jour 2025-10-14 : ce document a été relu lors de l’audit des fichiers Markdown. Les actions détaillées sont centralisées dans [`documentation-audit-2025-10-14.md`](documentation-audit-2025-10-14.md)._

## Objectifs
- Exposer la répartition 1→5 étoiles directement sous les étoiles interactives.
- Annoncer l'évolution des votes via des attributs ARIA (`role="meter"`, `aria-live`, `aria-valuenow`).
- Empêcher les doubles soumissions lors des mises à jour AJAX et garder un retour visuel clair.

## Structure HTML
```
.jlg-user-rating-breakdown
└── .jlg-user-rating-breakdown-list (role="list")
    ├── .jlg-user-rating-breakdown-item (role="listitem", data-stars="5" → 1)
    │   ├── .jlg-user-rating-breakdown-label
    │   │   ├── .jlg-user-rating-breakdown-star (libellé « 5 étoiles »)
    │   │   └── .jlg-user-rating-breakdown-count (texte et data-count mis à jour)
    │   └── .jlg-user-rating-breakdown-meter (role="meter", aria-valuenow / max)
    │       └── .jlg-user-rating-breakdown-track > .jlg-user-rating-breakdown-fill
```

Le conteneur principal porte `aria-live="polite"` et des attributs `data-*` avec les gabarits de traduction (`%s vote(s)`, `%1$s : %2$s (%3$s%%)`) utilisés par `assets/js/user-rating.js`.

## Accessibilité
- Les boutons étoilés conservent le rôle `radiogroup` avec gestion des états `aria-checked`.
- Chaque ligne de l'histogramme expose `role="meter"` avec `aria-valuemin`, `aria-valuemax`, `aria-valuenow` et un libellé complet.
- Le bloc parent reçoit `aria-busy="true"` durant les requêtes afin d'informer les lecteurs d'écran.
- Les transitions respectent `prefers-reduced-motion` (suppression de l'animation sur `.jlg-user-rating-breakdown-fill`).

## Interaction JS
- Le script désactive les boutons (`disabled` + `aria-disabled`) et ajoute `is-loading` pour empêcher les doubles soumissions.
- La réponse AJAX renvoie `new_average`, `new_count` et `new_breakdown`. `updateBreakdown()` redistribue les valeurs et met à jour `aria-label`/`aria-valuemax`.
- Les nombres sont formatés avec `toLocaleString` lorsque disponible, sinon en fallback simple.

## Vérifications manuelles
1. Ouvrir un article avec `[notation_utilisateurs_jlg]`, voter 5 ★ : vérifier l'apparition immédiate du segment « 5 étoiles » et le message de confirmation.
2. Rafraîchir la page : confirmer la persistance de l'histogramme et l'état « has-voted » (étoiles désactivées).
3. Forcer `prefers-reduced-motion` (outil développeur ou OS) : les barres doivent se mettre à jour sans animation.
4. Tester au clavier (Tab + Espace/Entrée) : les focus sont visibles et le compteur se met à jour.
