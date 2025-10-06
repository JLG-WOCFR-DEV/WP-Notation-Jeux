# Benchmark produit – 6 octobre 2025

## Périmètre et sources
- **Plugin étudié :** Notation JLG v5.0 (fonctionnalités décrites dans la documentation actuelle).
- **Références pro :** IGN (template Review 2024), GameSpot (Review hub 2024), OpenCritic (aggregator dashboards 2024).
- **Focus demandé :** options de configuration, UX/UI, navigation mobile, accessibilité, parité front ↔ éditeur WordPress.

## Synthèse rapide
| Thème | Niveau actuel du plugin | Référence pro | Gap constaté | Opportunités prioritaires |
| --- | --- | --- | --- | --- |
| Options de mise en page | Styles préconfigurés (moderne/classique/compact), réglages couleurs, seuil coup de cœur, bascule animations, grille Game Explorer personnalisable. | IGN/ GameSpot proposent des layouts modulaires avec sections réarrangeables, options spoiler/collapse, CTA affiliées, widgets cross-media. | Manque de contrôle section par section (ordre, visibilité), absence d’intégration achat/CTA, pas de prise en charge des avertissements spoiler. | Introduire un concepteur de layout par drag-and-drop, modules CTA affiliés, options d’avertissement spoiler et encadrés contextuels. |
| UX/UI desktop | Bloc tout-en-un et shortcodes offrent synthèse note + pros/cons + tagline, histogramme lecteurs, Game Explorer accessible. | IGN affiche un *review verdict* sticky, résumés chiffrés, carrousels médias, timeline post-launch. GameSpot ajoute badges (Must Play), modules « Where to Buy ». | Scoreboard manque de résumé persistant, absence de carrousel média et timeline, pas de badges événementiels. | Ajouter carte verdict épinglée, carrousel média responsive avec annotation, badges dynamiques (mise à jour live service). |
| Navigation mobile | Filtres Game Explorer repliables, histogramme responsive, tableaux convertibles en cartes. | IGN et GameSpot utilisent sommaires ancrés, en-têtes sticky, carrousels horizontaux, boutons d’action flottants. | Manque de sommaire in-page mobile, navigation sticky, CTA flottants. | Ajouter table des matières mobile, headers collants pour sections clés, boutons flottants (vote, partage, achat). |
| Accessibilité | Respect `prefers-reduced-motion`, focus visibles, ARIA pour histogramme et navigation Game Explorer, support clavier complet. | OpenCritic fournit bascule contraste élevé, résumés textuels alternatifs pour graphiques, navigation skip links. | Pas de mode contraste renforcé, graphiques limités aux barres, absence de skip links et de légendes textuelles. | Ajouter toggle contraste, descriptifs textuels de données, skip links, contrôle granularité ARIA (ex : `aria-live` sur votes). |
| Apparence éditeur WordPress | Blocs Gutenberg mirror du rendu front (thème clair/sombre, animations on/off), chargement auto des assets lors du rendu. | Pro tools internes affichent variations live (multi-device preview), checklists de contenu, placeholders médias. | Pas d’aperçu multi-device, pas de guides inline, prévisualisation limitée aux thèmes clair/sombre. | Ajouter panneau de prévisualisation responsive (desktop/tablette/mobile), onboarding inline (tips), placeholders média/CTA dans l’éditeur visuel. |

## Détails & recommandations

### 1. Options de configuration
- **Concepteur de layout** : proposer un module permettant de réordonner les sections (note, verdict, pros/cons, fiche technique, médias, CTA) via un système de glisser-déposer avec sauvegarde par défaut et overrides par article. Les styles actuels (« moderne/classique/compact ») pourraient devenir des presets.
- **Modules CTA & monétisation** : ajouter un sous-panneau pour configurer des boutons « Où acheter » (libellé, URL, suivi affilié, icônes store). Possibilité d’injecter automatiquement les CTA dans le bloc tout-en-un et le Game Explorer.
- **Cartouche spoiler / avertissements** : offrir un bloc « Spoiler alert » repliable avec message personnalisé et icône, inspiré des collapsibles utilisés par IGN.
- **Personnalisation médias** : permettre la sélection d’une galerie/capture clé et d’une vidéo héro, avec option d’autoplay sur desktop uniquement et fallback image accessible.
- **Timeline post-lancement** : introduire un champ structuré pour documenter les mises à jour majeures (patches, DLC), aligné sur ce que GameSpot/IGN affichent pour les jeux service.

### 2. UX/UI
- **Carte verdict sticky** : afficher un panneau flottant résumant note globale, badge, CTA et liens vers sections clés. Le plugin gère déjà le badge « Coup de cœur », ce panneau pourrait l’exploiter et ajouter un comparatif note rédaction/lecteurs.【F:plugin-notation-jeux_V4/README.md†L24-L76】
- **Carrousel média** : intégrer un composant slider accessible (contrôles clavier, pagination ARIA) alimenté par la médiathèque ou les URL vidéos détectées par les helpers actuels.【F:plugin-notation-jeux_V4/README.md†L33-L44】
- **Badges dynamiques** : étendre le système de badge pour couvrir plusieurs statuts (ex : « Must Play », « Editor’s Choice », « En attente de patch »), gérables depuis les réglages.
- **Mise en avant des insights** : enrichir `[jlg_score_insights]` avec graphiques additionnels (courbe d’évolution des notes, nuage de plateformes) et exports image/CSV pour partage.【F:plugin-notation-jeux_V4/README.md†L107-L135】

### 3. Navigation mobile
- **Sommaire ancré** : générer automatiquement une liste des sections principales (Résumé, Note, Pros/Cons, Verdict, CTA, Timeline) avec ancrage smooth compatible `prefers-reduced-motion`.
- **En-têtes sticky** : fixer la note globale et les actions clé en haut de l’écran lors du scroll.
- **Navigation gestuelle** : offrir un carrousel horizontal pour passer d’une section à l’autre (ex : « Résumé », « Détails », « Avis lecteurs »), avec pagination et support clavier.
- **Actions flottantes** : bouton flottant pour voter (lorsque `[notation_utilisateurs_jlg]` est actif) ou partager, aligné sur les patterns mobile d’IGN/GameSpot.

### 4. Accessibilité
- **Mode contraste élevé** : ajouter un toggle global qui ajuste la palette (fond, texte, bordures) et force un ratio AA/AAA. Les couleurs personnalisables actuelles pourraient inclure un duo « couleurs standard / contraste ».
- **Descriptions alternatives** : générer des résumés textuels automatiques pour les histogrammes de votes et futures visualisations, inspirés des bonnes pratiques d’OpenCritic.
- **Skip links & ordre de tabulation** : injecter des liens « Aller au verdict », « Aller aux avis lecteurs », etc. au début du bloc tout-en-un.
- **Feedback ARIA enrichi** : lors du vote lecteurs, annoncer le changement de moyenne via `aria-live="polite"` et fournir un message personnalisé en cas d’erreur d’envoi.【F:plugin-notation-jeux_V4/README.md†L29-L44】

### 5. Apparence sur WordPress (éditeur visuel)
- **Prévisualisation responsive** : ajouter un panneau latéral dans chaque bloc Gutenberg permettant de switcher entre vues Desktop/Tablette/Mobile (simples wrappers CSS) pour refléter ce que proposent les éditeurs pro.
- **Guides inline & checklists** : afficher des hints contextuels (ex : « Pensez à ajouter 3 captures », « Remplissez la timeline post-lancement ») directement dans le panneau Inspector.
- **Placeholders dynamiques** : lorsque certaines métadonnées sont manquantes, montrer des placeholders stylés (logo, gradient) plutôt qu’un simple vide, afin d’approcher la qualité visuelle d’IGN en mode brouillon.
- **Simulation multi-thèmes** : étendre les options actuelles `preview_theme` pour inclure des presets « High Contrast », « Dark Neon », etc., facilitant les tests avant publication.【F:plugin-notation-jeux_V4/README.md†L80-L105】

## Prochaines étapes recommandées
1. **Atelier UX** avec rédaction/testeurs pour prioriser les modules (CTA, verdict sticky, sommaire mobile).
2. **Spécifications techniques** pour le designer de layout et la bascule contraste élevé (impact CSS/JS, stockage réglages).
3. **Prototype éditeur** (Storybook ou Storybook-like dans Gutenberg) pour valider les previews multi-device.
4. **Itération accessibilité** : audit supplémentaire (Lighthouse + axe DevTools) après implémentation des nouveaux contrôles.
5. **Documentation** : mettre à jour README et guides utilisateurs lors de chaque livraison pour refléter les nouvelles options.

---

## Complément comparatif – focus monétisation & data (IGN, OpenCritic, Metacritic)

### Constats terrain
- **IGN & GameSpot (2024)** segmentent leurs reviews avec un *verdict module* riche (note, résumé, CTA affiliés, liens vers guides) et déclinent ces blocs dans des *franchises hubs*. Les CTA sont contextualisés par plateforme et prix.
- **OpenCritic** expose un agrégateur de scores avec filtres dynamiques (critics tiers, split par plateformes, distribution temporelle) et options d’export pour la presse.
- **Metacritic** combine la note métascore, la note utilisateurs et un historique d’updates (patches, DLC) tout en affichant des badges (Essential, Must Play) et des citations courtes de la rédaction.

### Gaps vs plugin
- Le plugin propose déjà le badge « Coup de cœur », la moyenne lecteurs et l’écart rédaction/lecteurs dans le bloc principal, mais n’automatise pas la création d’un module verdict autonome pour les pages listes ou hubs.【F:plugin-notation-jeux_V4/README.md†L24-L76】
- Les helpers vidéo (YouTube, Vimeo, Twitch, Dailymotion) et l’API RAWG apportent du rich media, toutefois aucune intégration commerce (prix, boutiques) n’est prévue dans les shortcodes/blocs.【F:plugin-notation-jeux_V4/README.md†L31-L44】
- `[jlg_score_insights]` offre déjà moyenne/médiane/histogramme, sans comparer différentes plateformes ni fournir d’export brut pour rédactions partenaires.【F:plugin-notation-jeux_V4/README.md†L67-L100】

### Pistes d’amélioration
1. **Module Verdict réutilisable**
   - Créer un nouveau shortcode/bloc (`notation-jlg/verdict-module`) reprenant note, résumé, badge, CTA et liens vers articles connexes.
   - Autoriser la duplication automatique de ce module dans les archives et Game Explorer pour approcher les *review hubs* IGN/GameSpot.

2. **Intégration commerce & monétisation**
   - Ajouter une table de métadonnées pour stocker prix par plateforme, liens affiliés et disponibilités.
   - Étendre le bloc tout-en-un et le Game Explorer pour afficher ces CTA dynamiques avec suivi analytique (ex : dataLayer, UTM).

3. **Insights multi-sources**
   - Enrichir `[jlg_score_insights]` avec des comparatifs par plateforme (barres empilées), timeline des mises à jour et possibilité d’export CSV/PNG pour la communication presse.
   - Offrir une option « intégrer des scores externes » (Metacritic/OpenCritic) afin d’afficher un agrégat et situer la note rédaction dans l’écosystème.

4. **Badges et citations éditoriales**
   - Étendre la configuration des badges (ex : « Must Play », « À surveiller », « Patch en attente ») et permettre d’ajouter une courte citation synthétique comme sur Metacritic.
   - Synchroniser ces badges avec le widget derniers tests et le Game Explorer pour conserver une cohérence visuelle.

5. **Automatisation RAWG → Hubs franchise**
   - Tirer parti de l’API RAWG pour créer automatiquement des hubs (saga, développeur) avec agrégation des notes, badges et timeline, à la manière des pages franchise d’IGN.
   - Prévoir une mise en cache et une tâche CRON pour actualiser les données (covers, sorties DLC) sans solliciter manuellement l’équipe édito.
