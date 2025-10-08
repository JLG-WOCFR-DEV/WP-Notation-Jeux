# Presets graphiques modulaires – 11 octobre 2025

## Contexte & objectifs
Le plugin Notation JLG doit proposer des styles clés en main alignés avec des bibliothèques UI modernes afin de réduire le temps d'intégration côté rédactions. Les presets ci-dessous s'inspirent de Headless UI, Shadcn UI, Radix UI, Bootstrap, Semantic UI et d'approches motion type Anime.js. Chaque preset décrit :

- Les tokens (couleurs, typos, rayons, ombres) prêts à intégrer dans `assets/css/_tokens.scss`.
- Les comportements interactifs à harmoniser entre l'éditeur Gutenberg et le front.
- Les composants prioritaires : bloc notation, cartes review, filtres Game Explorer, modales et toasts.
- Les guidelines motion pour conserver une cohérence avec les préférences utilisateurs (`prefers-reduced-motion`).

## Synthèse comparative
| Preset | Bibliothèque de référence | Palette dominante | Typographie | Rayons/ombres | Motion & feedback |
| --- | --- | --- | --- | --- | --- |
| **Atlas** | Headless UI | Neutres doux + accent bleu #2563EB | Inter, 16/24, titres 600 | Rayons 12px, ombres diffuses | Transitions 150ms, fade/scale léger |
| **Nordic Slate** | Shadcn UI | Gris bleutés, accent violet #8B5CF6 | Satoshi/Geist, 15/24, titres 500 | Rayons 8px, border 1px | Hover translation 2px, micro-delay focus |
| **Pulse Grid** | Radix UI | Palette Radix Olive + Lime | IBM Plex Sans, 15/22 | Rayons 6px, ombres directionnelles | Survol avec tokens `--shadow-2`, feedback ARIA |
| **Press Start** | Bootstrap 5 | Primary #0D6EFD, neutrals #F8F9FA | Source Sans 16/24 | Rayons 0 ou 4px, shadow-sm/md | Boutons 200ms ease-in-out |
| **Agora** | Semantic UI | Palette teal/orange, gradients légers | Lato 16/26 | Rayons 16px, doubles ombres | Etats loading progressifs |
| **Aero Motion** | Anime.js | Dégradés profonds + accent rose #EC4899 | Urbanist 17/26 | Rayons 10px | Sequences 120-240ms, easing `easeOutQuad` |

## Détails par preset

### 1. Preset « Atlas » – esprit Headless UI
- **Tokens couleur.**
  - `--color-bg-primary: #F9FAFB`, `--color-bg-surface: #FFFFFF`, `--color-border: #E5E7EB`.
  - `--color-accent: #2563EB`, `--color-accent-hover: #1D4ED8`, `--color-accent-muted: #DBEAFE`.
  - Jeux d'états (succès #22C55E, avertissement #FACC15, erreur #EF4444) alignés sur Tailwind/Headless.
- **Typo & hiérarchie.** Inter/Roboto, base 16px, titres 24/32 pour h2, 20/28 pour h3, `font-weight:600`.
- **Composants ciblés.**
  - Header sticky review : background translucide `rgba(249,250,251,0.85)` + blur 12px.
  - Accordéons (points forts/faibles) : border-left accentuée (3px) lors du focus.
  - Modales Game Explorer : overlay `rgba(15,23,42,0.4)` + transition `opacity 120ms` / `scale 120% -> 100%`.
- **Accessibilité.** Contraste accent 4.5:1 sur fond clair, focus visible (#1D4ED8, 2px inset).
- **Guidelines motion.** Utiliser `transition: opacity 120ms ease, transform 150ms ease;` et désactiver scale si `prefers-reduced-motion`.

### 2. Preset « Nordic Slate » – inspiration Shadcn UI
- **Tokens couleur.** Gris #0F172A -> #CBD5F5, accent violet #8B5CF6 + gradient `linear-gradient(120deg,#8B5CF6,#6366F1)` pour CTA premium.
- **Typo.** Satoshi ou Geist, `font-size:15px`, `line-height:24px`, titres uppercase légère (`letter-spacing:0.04em`).
- **Composants.**
  - Tabs filtres Game Explorer : pills avec border 1px `rgba(99,102,241,0.4)` + `backdrop-filter:saturate(180%) blur(6px)`.
  - Boutons primaires : `background:var(--accent-gradient)`, `box-shadow:0 10px 25px rgba(139,92,246,0.25)`.
  - Toasts succès/erreur : layout carte (icon left 32px, text wrap) + close accessible.
- **Motion.** Hover translation Y `-2px`, `transition 180ms cubic-bezier(0.16,1,0.3,1)`, focus ring double (1px blanc + 2px violet semi opaque).
- **Dark mode.** Basculer surfaces `#0B1220`, accent gradient saturé, border `rgba(255,255,255,0.08)`.

### 3. Preset « Pulse Grid » – influence Radix UI
- **Tokens couleur.** Utiliser palette Radix `olive` (neutral) + `lime` (accent). Ex. `--color-bg: hsl(110 20% 11%)`, `--color-surface: hsl(110 15% 15%)`, `--color-accent: hsl(86 70% 52%)`.
- **Typo.** IBM Plex Sans, base 15px, `font-feature-settings: "ss01" on` pour style technique.
- **Composants.**
  - Sliders sous-notes : rails `background:hsl(110 15% 24%)`, handle accent `box-shadow:0 0 0 1px #1B1D17, 0 2px 6px rgba(172,255,74,0.45)`.
  - Dropdown filtres : alignement left, menu `border-radius:6px`, `box-shadow:var(--shadow-4)` issu tokens Radix.
  - Badges comparatifs : uppercase `font-weight:500`, `background:rgba(172,255,74,0.16)`, `border:1px solid rgba(172,255,74,0.35)`.
- **Motion & états.** Utiliser primitives Radix (Collapsible, Dialog) avec `data-state` pour transitions CSS `transform: translateY(4px)` -> `0`.
- **Accessibilité.** S'assurer que `aria-controls`, `aria-expanded` alignés avec la structure HTML générée.

### 4. Preset « Press Start » – ADN Bootstrap 5
- **Tokens couleur.** Palette Bootstrap (Primary #0D6EFD, Success #198754, Danger #DC3545, Gray #6C757D). Background `#F8F9FA`, surfaces `#FFFFFF`.
- **Typo.** Source Sans Pro 16px/24px, titres bold 700, uppercase pour meta.
- **Composants.**
  - Boutons : `border-radius:0.375rem`, `box-shadow:0 0.125rem 0.25rem rgba(13,110,253,0.3)` sur focus.
  - Alertes score : alignement flex, icône `bi-emoji-smile` etc., background `rgba(13,110,253,0.08)`.
  - Pagination Game Explorer : `.pagination` stylisée avec `gap:0.25rem`, active `background:#0D6EFD`, `color:#fff`.
- **Motion.** `transition: color .15s ease-in-out, background-color .15s ease-in-out, box-shadow .15s`. Respecter tokens existants.
- **Responsivité.** Breakpoints alignés (576/768/992/1200px) pour grille cartes review.

### 5. Preset « Agora » – esprit Semantic UI
- **Tokens couleur.** Base grise (#1B1C1D) + accent teal (#00B5AD) et orange (#F2711C). Gradients doux `linear-gradient(135deg,#00B5AD,#21BA45)`.
- **Typo.** Lato 16/26, `font-weight:400-700`, `letter-spacing:0.015em` sur headings.
- **Composants.**
  - Cards : coins arrondis 16px, `box-shadow:0 12px 30px rgba(0,0,0,0.12)`.
  - Steps progression (workflow review) : segments horizontaux, `:after` progress `width` animé 200ms.
  - Labels statut : `border-radius:999px`, uppercase, `background:rgba(33,186,69,0.12)`.
- **Motion.** Utiliser `transition: transform 220ms ease, opacity 180ms` sur modales, overlay blur `8px`.
- **Accessibilité.** Couleurs teal/orange testées WCAG AA (contraste ≥ 4.5:1). Fournir alternative `border-left` sur success en mode haute visibilité.

### 6. Preset « Aero Motion » – touches Anime.js
- **Tokens couleur.** Fond sombre dégradé `linear-gradient(160deg,#0F172A,#1F2937)`, accent rose #EC4899 et cyan #22D3EE.
- **Typo.** Urbanist 17/26, `font-weight:500`, `text-transform:uppercase` partielle pour CTA.
- **Composants.**
  - Intro block : `background:radial-gradient(circle at top,#22D3EE33,#0F172A)` + titre neon (shadow 0 0 20px #22D3EE88).
  - Timeline score : barres verticales animées via `Anime.js` (stagger 40ms, `opacity` + `scaleY`).
  - CTA communauté : bouton `background:linear-gradient(120deg,#22D3EE,#EC4899)`, `filter:drop-shadow(0 10px 25px rgba(236,72,153,0.35))`.
- **Motion guidelines.**
  - Séquences d'apparition orchestrées avec `Anime.timeline({ duration: 240, easing: 'easeOutQuad'})`.
  - Prévoir fallback CSS `transition` et limiter translations à 12px.
  - Respect `prefers-reduced-motion`: bypass timeline -> `opacity:1` instantané.
- **Instrumentation.** Émettre événements `notation.motion.start` et `notation.motion.complete` pour suivre l'impact sur engagement.

## Intégration technique
1. **Fichier tokens.** Ajouter un fichier `_tokens-presets.scss` exposant les maps Sass `map-get($preset-atlas, 'accent')` pour alimentation rapide.
2. **Configuration Gutenberg.** Étendre les `theme.json` / `editor-styles-wrapper` pour charger un preset par défaut via attribut bloc (`data-notation-theme`).
3. **Switcher front.** Proposer un helper PHP `notation_get_preset_classes( $preset_slug )` qui injecte classes BEM (`notation-theme--atlas`).
4. **Docs & screenshots.**
   - Prévoir maquettes Figma (clair/sombre) pour chaque preset.
   - Ajouter captures 1440px/768px/375px dans `docs/ui-presets` lors de l'implémentation.
5. **QA.** Mettre à jour `docs/responsive-testing.md` après chaque preset validé et intégrer tests Cypress ou Playwright si un carrousel animé est introduit.

## Roadmap d'adoption
- **Semaine 1.** Valider tokens + prototypes Atlas / Nordic Slate, écrire tests snapshot pour blocs Gutenberg.
- **Semaine 2.** Implémenter Pulse Grid & Press Start, ajouter options côté shortcode `[notation_review preset="press-start"]`.
- **Semaine 3.** Finaliser Agora & Aero Motion, mesurer l'impact sur temps de chargement (Lighthouse) et conversions CTA lecteurs.
- **Semaine 4.** Collecter feedbacks rédactions, documenter bonnes pratiques dans `docs/ui-styleguide.md`, préparer release notes bilingues.
