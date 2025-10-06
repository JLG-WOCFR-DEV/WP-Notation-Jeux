# Benchmark produit – 2025-02-14

## Contexte
- **Produit analysé :** Notation JLG v5.0 (shortcodes/blocs complets, histogramme lecteurs, Game Explorer, Score Insights, remplissage RAWG, thèmes clair/sombre, badge « Coup de cœur », validation PEGI/date, surcharge de templates).
- **Objectif :** comparer l'expérience à trois références éditoriales (IGN, GameSpot, OpenCritic) pour identifier les écarts critiques et opportunités d'amélioration.

## Diagnostic comparatif
| Axe | Notation JLG | IGN | GameSpot | OpenCritic | Opportunités clés |
| --- | --- | --- | --- | --- | --- |
| **Narration & mise en scène** | Bloc tout-en-un + taglines bilingues, sections points forts/faibles, badge coup de cœur. | « Review at a Glance » avec résumé, note, verdict visuel, vidéo intégrée, citations clé. | Mise en page multi-sections (Gameplay, Graphismes, Conclusion) + module « Review in Progress ». | Synthèse automatique + agrégation multi-critiques, visuel agrégé (Top Critic, % recommandent). | Créer un sous-bloc « Verdict » configurable (résumé + CTA + date de MAJ) et un statut « Review en cours » surfacés dans le bloc complet et le schema JSON-LD.
| **Comparatif plateformes & performances** | Métadonnées plateformes, filtres Game Explorer. Pas de comparatif visuel dédié. | Encarts « Performance » (FPS, 4K/60), différenciation PS5/PC/Xbox, recommandations techniques. | Section « Platform Differences » + encadrés patch notes. | Tri par plateforme mais focalisé sur agrégation critique. | Ajouter un module `jlg_platform_breakdown` (tableau responsive + badges « Meilleure expérience ») avec champs FPS/résolution/modes et support JSON-LD `gamePlatform`.
| **Engagement & communautés** | Votes lecteurs + histogramme, Score Insights (moyenne, médiane, plateformes dominantes). | Commentaires riches, recommandations de guides, carrousel vidéo, intégrations sociales. | Highlights communautaires, cartes guides, modules « Review in Progress » commentés. | Statuts « Trending », « Hype Meter », suivis d'évolution. | Étendre Score Insights avec segmentation Rédaction vs Lecteurs, tendances (sparkline) et modules de contenus associés (« Guides & astuces » liés par taxonomie).
| **Monétisation & conversion** | Aucun bloc prix/déals natif. | Boutons « Buy » multi-boutiques, widgets d'affiliation dynamiques. | Boutons achat par plateforme + alertes promos. | Comparateur de prix/stock, wishlist. | Créer un widget/section « Deals & disponibilités » (liens trackés, prix, stock, CTA custom). Prévoir configuration par jeu + options d'affiliation.
| **Automation & APIs** | Remplissage RAWG, validation PEGI/date, marquage assets auto, Score Insights. | API internes (IGN Playlist), recommandations personnalisées. | Planning éditorial unifié, exports internes, intégrations vidéo propriétaires. | API publique, exports CSV, webhooks éditeurs. | Étendre l'API REST/RAWG bridge : endpoint `/jlg/v1/ratings` (moyennes, histogramme, statut review), commande WP-CLI `jlg export:ratings` pour CSV planifiables.
| **Accessibilité & performance** | Respect `prefers-reduced-motion`, focus visibles, mobile-first. | Ajout transcripts vidéo, contrôles clavier sur carrousels, audits Lighthouse > 90. | Optimisation lazy-loading vidéo, pagination infinie accessible. | UI légère, filtrage rapide via React. | Mesurer via Lighthouse, compléter doc responsive. Ajouter un mode « images légères » pour Game Explorer (lazy + placeholders) et audits périodiques.

## Quick wins (≤ 4 semaines)
| Action | Description | Impact | Prérequis | KPI ciblé |
| --- | --- | --- | --- | --- |
| **Verdict express** | Étendre `[jlg_bloc_complet]`/bloc All-in-one avec sous-section « Verdict » (titre, résumé, CTA, statut, date MAJ). | Améliore la lisibilité et aligne avec IGN. | Nouveau champ métadonnée `jlg_review_status`, mise à jour schema (`reviewStatus`). | Taux de clic sur CTA > 10 %, adoption > 80 % des nouvelles reviews. |
| **Segmentation Insights** | Ajouter carte « Rédaction vs Lecteurs » dans `[jlg_score_insights]` (écart absolu, badges divergences). | Permet d'identifier les écarts de perception (feature type OpenCritic). | Réutiliser données existantes + calcul delta. | % d'articles avec badge divergence, engagement module. |
| **Guides connexes** | Intégrer dans bloc complet/shortcode un panneau optionnel listant 3-4 guides liés (taxonomies `guide`, `astuce`, tags). | Augmente la session time et imite GameSpot. | WP_Query filtré + réglage global (on/off). | CTR > 8 % sur guides proposés. |
| **Audit Lighthouse automatisé** | Ajouter script npm/composer pour lancer Lighthouse CI sur Game Explorer & Score Insights. | Maintient l'écart de perf/accessibilité vs normes pro. | Docker/CI ou script local + doc. | Score > 90 en Accessibilité et Performance. |

## Roadmap priorisée (3-6 mois)
1. **Comparateur plateformes & deals (P0)**
   - Shortcode/bloc `jlg_platform_breakdown` + section Deals.
   - Ajout champs méta répétables (prix, vendeur, lien, disponibilité) + hooks filtres.
2. **Insights avancés & API (P1)**
   - REST `/jlg/v1/ratings` (GET + authentification) exposant moyenne rédaction, moyenne lecteurs, histogramme, statut review, écarts plateforme.
   - Commande WP-CLI `jlg export:ratings` (CSV) + planification CRON.
   - Sparklines (SVG) dans Score Insights pour tendance 30/90/365 jours.
3. **Expérience review évolutive (P1)**
   - Statut review (En cours / Final / Mise à jour patch), notifications admin, rappel CRON.
   - Templates Gutenberg pour sections thématiques (Gameplay, Graphismes, Performances, Multijoueur) avec toggles.
4. **Performance & accessibilité renforcées (P2)**
   - Mode « assets légers » (lazy images Game Explorer, skeleton accessible, ARIA live sur filtres).
   - Documentation responsive actualisée + check-list Lighthouse.

## Points de vigilance
- **Template overrides** : prévoir filtres/flags pour désactiver verdict, deals, comparateur afin de ne pas casser les thèmes enfants existants.
- **Internationalisation** : toutes les nouvelles chaînes via `__()`/`_x()` + mise à jour `languages/notation-jlg.pot`.
- **RGPD & affiliation** : baliser les liens deals en `rel="sponsored"`, prévoir notice consentement si tracking.
- **Performances Game Explorer** : anticiper la charge des nouveaux modules (comparateur/deals) via cache transitoire + lazy loading ciblé.

## Suivi
- Programmer une nouvelle session benchmark après implémentation P0/P1 pour mesurer l'alignement vs IGN/GameSpot/OpenCritic.
- Ajouter les nouveaux KPI (CTA verdict, CTR guides, usage deals) dans le tableau de bord Score Insights.
- Synchroniser ces conclusions avec la feuille de route `docs/product-roadmap/`.
