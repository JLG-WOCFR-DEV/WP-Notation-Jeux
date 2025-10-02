<?php
if (!defined('ABSPATH')) exit;
?>
<h2>📝 Documentation des Shortcodes</h2>
<p>Référence complète de tous les shortcodes disponibles avec leurs paramètres.</p>

<section class="components-card jlg-section-card jlg-section-card--success jlg-admin-section" role="region" aria-labelledby="jlg-shortcodes-hero">
    <div class="components-card__body">
        <h3 id="jlg-shortcodes-hero" class="jlg-section-card__title">🆕 NOUVEAU : Bloc Complet Tout-en-Un</h3>
        <p><strong>Le shortcode le plus complet qui combine notation, points forts/faibles et tagline en un seul bloc élégant !</strong></p>
    </div>
</section>

<section class="jlg-admin-section" aria-labelledby="jlg-shortcodes-featured">
    <div class="components-card jlg-section-card jlg-section-card--highlight" role="region" aria-labelledby="jlg-shortcodes-featured">
        <div class="components-card__body">
            <h3 id="jlg-shortcodes-featured">⭐ 1. Bloc Complet Tout-en-Un (RECOMMANDÉ)</h3>
            <div class="jlg-badge-group" role="group" aria-label="Alias du shortcode">
                <code class="jlg-code jlg-code--inline">[jlg_bloc_complet]</code>
                <span class="jlg-code-separator" aria-hidden="true">ou</span>
                <code class="jlg-code jlg-code--inline">[bloc_notation_complet]</code>
            </div>
            <p class="jlg-highlight-text">✨ Combine en un seul bloc : Tagline + Notation complète + Points forts/faibles</p>

            <h4>Paramètres :</h4>
            <ul class="jlg-bullet-list">
                <li><strong>post_id</strong> : ID de l'article (défaut : article actuel)</li>
                <li><strong>afficher_notation</strong> : "oui" ou "non" (défaut : "oui")</li>
                <li><strong>afficher_points</strong> : "oui" ou "non" (défaut : "oui")</li>
                <li><strong>afficher_tagline</strong> : "oui" ou "non" (défaut : "oui")</li>
                <li><strong>style</strong> : "moderne", "classique" ou "compact" (défaut : "moderne")</li>
                <li><strong>couleur_accent</strong> : Code couleur hex (ex: "#60a5fa")</li>
                <li><strong>titre_points_forts</strong> : Titre personnalisé (défaut : "Points Forts")</li>
                <li><strong>titre_points_faibles</strong> : Titre personnalisé (défaut : "Points Faibles")</li>
            </ul>

            <h4>Exemples d'utilisation :</h4>
            <pre class="jlg-code-block"><?php echo esc_html(implode("\n\n", array(
                '// Bloc complet avec tous les éléments (recommandé)\n[jlg_bloc_complet]',
                '// Sans la tagline du haut\n[jlg_bloc_complet afficher_tagline="non"]',
                '// Style compact pour économiser l\'espace\n[jlg_bloc_complet style="compact"]',
                '// Avec couleur personnalisée\n[jlg_bloc_complet couleur_accent="#ff6b6b"]',
                '// Seulement notation et points (sans tagline)\n[jlg_bloc_complet afficher_tagline="non"]',
                '// Configuration complète personnalisée\n[jlg_bloc_complet style="moderne" couleur_accent="#8b5cf6" titre_points_forts="Les +" titre_points_faibles="Les -"]'
            ))); ?></pre>

            <div class="jlg-callout jlg-callout--success" role="note">
                <strong>💡 Conseil :</strong> Ce shortcode est idéal pour remplacer les 3 shortcodes séparés et avoir une présentation unifiée et professionnelle.
            </div>
        </div>
    </div>
</section>

<hr class="jlg-divider" aria-hidden="true">

<section class="jlg-admin-section" aria-label="Shortcodes individuels">
    <h3 class="jlg-section-heading">Shortcodes individuels (si vous préférez les utiliser séparément)</h3>

    <article class="components-card jlg-section-card" role="region" aria-labelledby="jlg-shortcode-notation">
        <div class="components-card__body">
            <h3 id="jlg-shortcode-notation">2. Bloc de Notation Principal (seul)</h3>
            <code class="jlg-code jlg-code--inline">[bloc_notation_jeu]</code>

            <h4>Paramètres :</h4>
            <ul class="jlg-bullet-list">
                <li><strong>post_id</strong> (optionnel) : ID d'un article spécifique. Par défaut : article actuel</li>
            </ul>

            <h4>Exemples :</h4>
            <pre class="jlg-code-block">[bloc_notation_jeu]
[bloc_notation_jeu post_id="123"]</pre>
        </div>
    </article>

    <article class="components-card jlg-section-card" role="region" aria-labelledby="jlg-shortcode-fiche-technique">
        <div class="components-card__body">
            <h3 id="jlg-shortcode-fiche-technique">3. Fiche Technique</h3>
            <code class="jlg-code jlg-code--inline">[jlg_fiche_technique]</code>

            <h4>Paramètres :</h4>
            <ul class="jlg-bullet-list">
                <li><strong>titre</strong> : Titre du bloc (défaut : "Fiche Technique")</li>
                <li><strong>champs</strong> : Champs à afficher, séparés par des virgules</li>
            </ul>

            <h4>Champs disponibles :</h4>
            <ul class="jlg-bullet-list jlg-bullet-list--columns">
                <li>developpeur</li>
                <li>editeur</li>
                <li>date_sortie</li>
                <li>version</li>
                <li>pegi</li>
                <li>temps_de_jeu</li>
                <li>plateformes</li>
            </ul>

            <h4>Exemples :</h4>
            <pre class="jlg-code-block">[jlg_fiche_technique]
[jlg_fiche_technique titre="Informations"]
[jlg_fiche_technique champs="developpeur,editeur,date_sortie"]
[jlg_fiche_technique titre="Info Rapide" champs="plateformes,pegi"]</pre>
        </div>
    </article>

    <article class="components-card jlg-section-card" role="region" aria-labelledby="jlg-shortcode-tableau">
        <div class="components-card__body">
            <h3 id="jlg-shortcode-tableau">4. Tableau Récapitulatif</h3>
            <code class="jlg-code jlg-code--inline">[jlg_tableau_recap]</code>

            <h4>Paramètres :</h4>
            <ul class="jlg-bullet-list">
                <li><strong>posts_per_page</strong> : Nombre d'articles par page (défaut : 12)</li>
                <li><strong>layout</strong> : "table" ou "grid" (défaut : "table")</li>
                <li><strong>categorie</strong> : Slug de catégorie à filtrer</li>
                <li><strong>colonnes</strong> : Colonnes à afficher (table uniquement)</li>
            </ul>

            <h4>Colonnes disponibles :</h4>
            <ul class="jlg-bullet-list">
                <li><strong>titre</strong> : Titre du jeu</li>
                <li><strong>date</strong> : Date de publication</li>
                <li><strong>note</strong> : Note moyenne</li>
                <li><strong>developpeur</strong> : Développeur</li>
                <li><strong>editeur</strong> : Éditeur</li>
            </ul>

            <p><strong>Tri :</strong> les en-têtes sont cliquables pour ordonner les résultats par titre (<code class="jlg-code jlg-code--inline">orderby=title</code>), date (<code class="jlg-code jlg-code--inline">orderby=date</code>), note moyenne (<code class="jlg-code jlg-code--inline">orderby=average_score</code>) ainsi que par métadonnées développeur ou éditeur (<code class="jlg-code jlg-code--inline">orderby=meta__jlg_developpeur</code>, <code class="jlg-code jlg-code--inline">orderby=meta__jlg_editeur</code>).</p>

            <h4>Exemples :</h4>
            <pre class="jlg-code-block">[jlg_tableau_recap]
[jlg_tableau_recap layout="grid"]
[jlg_tableau_recap posts_per_page="20"]
[jlg_tableau_recap categorie="fps"]
[jlg_tableau_recap colonnes="titre,note,developpeur"]
[jlg_tableau_recap layout="grid" posts_per_page="16" categorie="rpg"]
[jlg_tableau_recap colonnes="titre,date,note,editeur" posts_per_page="30"]</pre>
        </div>
    </article>

    <article class="components-card jlg-section-card" role="region" aria-labelledby="jlg-shortcode-points">
        <div class="components-card__body">
            <h3 id="jlg-shortcode-points">5. Points Forts et Faibles (seuls)</h3>
            <code class="jlg-code jlg-code--inline">[jlg_points_forts_faibles]</code>
            <p>Affiche automatiquement les points forts et faibles définis dans les métadonnées de l'article.</p>
            <p><em>Pas de paramètres - utilise les données de l'article actuel.</em></p>

            <h4>Exemple :</h4>
            <pre class="jlg-code-block">[jlg_points_forts_faibles]</pre>
        </div>
    </article>

    <article class="components-card jlg-section-card" role="region" aria-labelledby="jlg-shortcode-tagline">
        <div class="components-card__body">
            <h3 id="jlg-shortcode-tagline">6. Tagline Bilingue (seule)</h3>
            <code class="jlg-code jlg-code--inline">[tagline_notation_jlg]</code>
            <p>Affiche la phrase d'accroche avec switch de langue FR/EN.</p>
            <p><em>Pas de paramètres - utilise les données de l'article actuel.</em></p>

            <h4>Exemple :</h4>
            <pre class="jlg-code-block">[tagline_notation_jlg]</pre>
        </div>
    </article>

    <article class="components-card jlg-section-card" role="region" aria-labelledby="jlg-shortcode-users">
        <div class="components-card__body">
            <h3 id="jlg-shortcode-users">7. Notation Utilisateurs</h3>
            <code class="jlg-code jlg-code--inline">[notation_utilisateurs_jlg]</code>
            <p>Permet aux visiteurs de voter (système 5 étoiles).</p>
            <p><em>Pas de paramètres - utilise l'article actuel.</em></p>

            <h4>Exemple :</h4>
            <pre class="jlg-code-block">[notation_utilisateurs_jlg]</pre>
        </div>
    </article>
</section>
