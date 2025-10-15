# Test manuel – jlgLiveAnnouncer

## Objectif
Garantir que le module `jlgLiveAnnouncer` diffuse correctement les retours vocaux, respecte les préférences d’accessibilité et reste pilotable au clavier.

## Pré-requis
- Page de test contenant le bloc/shortcode de notation lecteurs.
- Possibilité d’activer/désactiver les options « Annonces live » dans les réglages du plugin.
- Lecteur d’écran NVDA ou VoiceOver (facultatif mais recommandé).

## Scénario principal
1. **Activation**
   - Activer l’option « Annonces live » dans l’admin.
   - Recharger la page front et vérifier la présence de la zone `aria-live="polite"` et du bouton de fermeture.
2. **Interaction clavier**
   - Naviguer au clavier jusqu’au bouton d’activation/désactivation.
   - Vérifier les styles `:focus-visible` et l’annonce de l’état (`aria-pressed`).
3. **Diffusion des messages**
   - Soumettre une note lecteurs et confirmer que le message de succès/erreur est annoncé une seule fois.
   - Valider que le message est également écrit dans la zone cachée `.screen-reader-text`.
4. **Fermeture / reset**
   - Déclencher le bouton de fermeture, vérifier que le focus est renvoyé sur l’élément déclencheur et que l’annonce cesse.
   - Réactiver l’annonce et confirmer que les messages sont de nouveau diffusés.
5. **Préférence de mouvement réduit**
   - Simuler `prefers-reduced-motion: reduce` et vérifier qu’aucune animation superflue n’est déclenchée lors des annonces.

## Critères de validation
- Les boutons sont accessibles via Tab / Shift+Tab et leurs états sont vocalisés.
- Les messages ne sont jamais répétés ni perdus lors d’une activation/désactivation successive.
- Aucune erreur JavaScript en console durant le scénario.
- La zone `.screen-reader-text` reste masquée visuellement (fallback CSS vérifié).

## Journalisation
- Noter le résultat du test dans `docs/responsive-testing.md` (section Accessibilité) et archiver les captures/vidéos associées.
