# Suggestions UX / UI – 8 octobre 2025

> _Mise à jour 2025-10-14 : ce document a été relu lors de l’audit des fichiers Markdown. Les actions détaillées sont centralisées dans [`documentation-audit-2025-10-14.md`](documentation-audit-2025-10-14.md)._

## Contexte benchmark
En observant IGN, GameSpot et OpenCritic, on note trois attentes récurrentes :
1. **Synthèse décisionnelle immédiate** – un header dense combine note globale, badge d'édition et principaux arguments.
2. **Comparaison multi-plateformes fluide** – tableaux compressés et filtres rapides pour basculer entre PS5/Xbox/PC.
3. **Parcours lecteur engageant** – call-to-action visibles pour donner son avis et signaux de confiance (modération, badges).

Ces constats éclairent les optimisations UX/UI ci-dessous pour Notation JLG.

## 1. Header de review « décision instantanée »
**Objectif UX.** Offrir une synthèse actionnable en 3 secondes pour accélérer la prise de décision des lecteurs pressés.

**Livrables design.**
- Wireframes desktop/tablette précisant la hauteur max du bandeau sticky (≤96 px), les états de statut (Draft / In progress / Final) et les règles de troncature du verdict (120 caractères max).
- Bibliothèque de 12 tags pré-remplis (thématiques gameplay, technique, narration) avec code couleur subtil afin d’alimenter le micro-carousel.
- Prototype Figma micro-interactions (auto-scroll + pause au survol) pour validation accessibilité et confort visuel.

**Implémentation.**
- Exposer un nouveau réglage bloc `review-header-sticky` dans Gutenberg (toggle + champ texte verdict court) stocké via `wp.data.dispatch( 'core/editor' )`.
- Intégrer un composant React `StickyVerdictBar` réutilisable côté front et éditeur, en s’assurant que le sticky ne masque pas les ancres internes (offset configurable via option thème).
- Déclencher un événement `notation.header.cta_platforms` lors du clic sur le CTA « Voir comparatif plateformes » pour instrumentation analytics.

**Suivi & QA.**
- Tests manuels sur Safari iPad (scroll + orientation) et Chrome desktop pour valider le comportement sticky.
- Ajout d’un scénario Lighthouse ciblant le focus du CTA secondaire et la lisibilité 400 % zoom.

## 2. Carte verdict responsive dans le bloc Gutenberg
**Objectif UX.** Humaniser l’avis rédaction et guider les lecteurs vers les points clés sans friction.

**Livrables design.**
- Déclinaison carte (mode clair/sombre) avec options avatar rond/carré et placeholders lorsque la rédaction est collective.
- Styles typographiques alignés avec la charte (titre 20 px, interlignage 1.4, icônes 20 px) et palette tokens (succès #1B8A5A, attention #E69F17, alerte #D64545).
- Maquettes mobile présentant l’accordéon accessible pour les points forts/faibles (icônes +/- et animations ≤150 ms).

**Implémentation.**
- Créer le sous-bloc `notation/verdict-card` avec `InnerBlocks` verrouillés (slots avatar, meta, note, points +/−) et attributs JSON pour les 3 points forts/faibles.
- Ajouter un système de tokens SCSS partagé (`_tokens.scss`) pour garantir la cohérence des couleurs et permettre un theming rapide.
- Mettre en place un hook `useReducedMotion` afin de désactiver les transitions accordéon si `prefers-reduced-motion` est activé.

**Suivi & QA.**
- Tests unitaires PHP pour vérifier la sérialisation du sous-bloc et la présence des attributs ARIA (`aria-controls`, `aria-expanded`).
- Checklist responsive : affichage 320 px, 768 px, 1280 px + capture à intégrer dans les release notes.

## 3. Tableau comparatif plateformes enrichi
**Objectif UX.** Permettre une comparaison rapide tout en évitant la surcharge cognitive.

**Livrables design.**
- Tableaux responsive avec pagination horizontale (max 4 colonnes visibles) et indicateurs de scroll (ombre + flèches).
- Kit d’icônes vectorielles (FPS, DualSense, Cross-save, Ray-tracing) avec guidelines taille min 24 px.
- Badges dynamiques (ex. « Meilleure expérience », « Édition la plus stable ») avec codes couleurs neutres pour conserver l’impartialité.

**Implémentation.**
- Introduire un composant `PlatformComparisonCarousel` utilisant `aria-roledescription="carousel"` et navigation clavier (flèches, Home/End).
- Ajouter un calcul automatique des badges basé sur les sous-notes existantes (poids : technique 40 %, gameplay 30 %, stabilité 30 %).
- Charger les pictogrammes via sprites SVG et fournir des alternatives texte (`aria-label`) pour les lecteurs d’écran.

**Suivi & QA.**
- Tests de performance (Lighthouse) pour vérifier que la pagination n’alourdit pas le LCP (>2,5 s).
- Cas de test analytics : `notation.platforms.badge_view` + `notation.platforms.swipe`.

## 4. Score Insights plus actionnable
**Objectif UX.** Renforcer la confiance dans la note communautaire et encourager la participation.

**Livrables design.**
- Bandeau « Confiance des lecteurs » avec jauge 0–100 % (code couleurs : rouge <40, orange 40-69, vert ≥70) et infobulle expliquant la méthodologie.
- Timeline sparklines (30 jours) avec tooltips jour par jour et marqueur d’événements (patchs, DLC) pour contextualiser les variations.
- Popover CTA « Inviter la rédaction » décrivant le partage (copie lien + suggestions diffusion Slack/email).

**Implémentation.**
- Étendre l’API REST interne pour exposer l’historique des notes lecteurs (endpoint `/notation/v1/scores/history?post=<id>` avec cache 15 min).
- Intégrer une librairie de mini-charts légère (Sparkline custom ou micro D3) avec fallback HTML pour navigateurs sans JS.
- Ajouter un utilitaire `copyShareLink()` utilisant l’API Clipboard et un message toast ARIA-live « Lien copié ».

**Suivi & QA.**
- Tests unitaires REST pour la route d’historique + mock données.
- Mesure post-déploiement : progression du taux de participation (objectif +15 %) et temps moyen passé sur la section insights.

## 5. Accessibilité et modes d'affichage
**Objectif UX.** Garantir l’inclusivité et la flexibilité de lecture sur l’ensemble des modules.

**Livrables design.**
- Variantes High Contrast (ratio ≥7:1 pour texte principal, ≥4.5:1 pour UI) et mode sombre documentés dans un guide PDF.
- Spécification des focus states (couleur, épaisseur 2 px, offset 3 px) homogène sur tous les CTA et accordéons.
- Table d’équivalence des tokens couleurs pour chaque mode (clair/sombre/contraste élevé) afin de faciliter la maintenance.

**Implémentation.**
- Ajouter un toggle accessibilité persistant (`localStorage` + `prefers-contrast`) avec annonce via `aria-live`.
- Factoriser les styles via CSS custom properties (`--nj-color-primary`, etc.) pour basculer dynamiquement les thèmes.
- Auditer les modules existants afin de corriger les ordres de tabulation et injecter des `skip-links` lorsque nécessaire.

**Suivi & QA.**
- Audit Lighthouse Accessibilité (>90) en mode clair/sombre/contraste.
- Tests clavier complets (Tab, Shift+Tab, Enter, Space) + revue lecteur d’écran NVDA/VoiceOver.

## 6. Parcours de conversion affiliée
**Objectif UX.** Fluidifier la transition entre la consultation du test et l’acte d’achat tout en conservant la transparence éditoriale.

**Livrables design.**
- Bloc « Où acheter » modulable (3–5 marchands) avec boutons taille 48 px, logos vectorisés et affichage du meilleur prix.
- Template d’état vide (illustration, message empathique, CTA vers wishlist) pour maintenir la cohérence visuelle.
- Bandeau informatif sur la politique d’affiliation (texte court + lien vers page de transparence) positionné sous les deals.

**Implémentation.**
- Étendre le schéma ACF/Meta pour stocker les partenaires affiliés (nom, URL taguée, prix, devise, disponibilité).
- Implémenter un composant `AffiliateGrid` avec tri automatique (prix croissant, fallback alphabétique) et tracking `notation.deals.click`.
- Prévoir un CRON journalier rafraîchissant les prix via API marchands (timeout 3 s + gestion des erreurs avec messages utilisateur).

**Suivi & QA.**
- Vérifier la conformité RGPD (cookies, consentement) si des scripts externes sont nécessaires.
- Mesurer le taux de clics affiliés (objectif +10 % sur 3 mois) et l’absence de churn (rebonds suite à transparence).

## 7. Métriques & suivi
**Objectif.** S’assurer que chaque piste produit un impact mesurable et documenté.

**Roadmap & instrumentation.**
- Créer un tableau de bord dédié dans l’outil analytics (Looker / Matomo) regroupant les événements `notation.header.*`, `notation.platforms.*`, `notation.deals.*`.
- Définir des checkpoints mensuels pour suivre les KPI : scroll depth, participation lecteurs, CTR deals, activation du mode contraste élevé.
- Associer chaque livraison à une entrée dans `docs/product-roadmap` avec date, owner et statut (Discovery / Delivery / Measure).

**Qualité documentaire.**
- Mettre à jour `docs/responsive-testing.md` avec les nouveaux scénarios (sticky header, accordéons mobile, toggle accessibilité, carousel plateformes).
- Ajouter des captures (clair/sombre/contraste) dans les release notes et documenter les tokens dans `assets/css/_tokens.scss`.
- Prévoir un post-mortem à 90 jours pour consolider les enseignements (ce qui a fonctionné, à itérer, à abandonner).
