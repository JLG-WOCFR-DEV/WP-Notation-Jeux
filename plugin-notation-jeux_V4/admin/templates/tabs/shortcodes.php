<?php
if (!defined('ABSPATH')) exit;
?>
<h2>📝 Documentation des Shortcodes</h2>
<p>Référence complète de tous les shortcodes disponibles avec leurs paramètres.</p>

<!-- NOUVEAU : Bloc tout-en-un en premier -->
<div style="background:#e8f5e9; padding:20px; margin:20px 0; border-left:4px solid #4caf50; border-radius:4px;">
    <h3 style="color:#2e7d32; margin-top:0;">🆕 NOUVEAU : Bloc Complet Tout-en-Un</h3>
    <p><strong>Le shortcode le plus complet qui combine notation, points forts/faibles et tagline en un seul bloc élégant !</strong></p>
</div>

<div style="margin-top:30px;">

    <!-- NOUVEAU SHORTCODE : Bloc Complet -->
    <div style="background:#f0f8ff; padding:20px; margin-bottom:20px; border-left:4px solid #4caf50; border-radius:4px; border:2px solid #4caf50;">
        <h3>⭐ 1. Bloc Complet Tout-en-Un (RECOMMANDÉ)</h3>
        <code style="background:#fff; padding:5px 10px; display:inline-block; border-radius:3px; font-size:16px;">[jlg_bloc_complet]</code>
        <span style="margin-left:10px;">ou</span>
        <code style="background:#fff; padding:5px 10px; display:inline-block; border-radius:3px; font-size:16px;">[bloc_notation_complet]</code>

        <p style="color:#2e7d32; font-weight:bold;">✨ Combine en un seul bloc : Tagline + Notation complète + Points forts/faibles</p>

        <h4>Paramètres :</h4>
        <ul>
            <li><strong>post_id</strong> : ID de l'article (défaut : article actuel)</li>
            <li><strong>afficher_notation</strong> : "oui" ou "non" (défaut : "oui")</li>
            <li><strong>afficher_points</strong> : "oui" ou "non" (défaut : "oui")</li>
            <li><strong>afficher_tagline</strong> : "oui" ou "non" (défaut : "oui")</li>
            <li><strong>style</strong> : "moderne", "classique" ou "compact" (défaut : "moderne")</li>
            <li><strong>couleur_accent</strong> : Code couleur hex (ex: "#60a5fa")</li>
            <li><strong>titre_points_forts</strong> : Titre personnalisé (défaut : "Points Forts")</li>
            <li><strong>titre_points_faibles</strong> : Titre personnalisé (défaut : "Points Faibles")</li>
            <li><strong>cta_label</strong> : Texte du bouton d'appel à l'action (défaut : valeur de la métadonnée)</li>
            <li><strong>cta_url</strong> : URL absolue du bouton (défaut : valeur de la métadonnée)</li>
            <li><strong>cta_role</strong> : Attribut <code>role</code> du lien (défaut : <code>button</code>)</li>
            <li><strong>cta_rel</strong> : Attribut <code>rel</code> (défaut : <code>nofollow sponsored</code>)</li>
        </ul>

        <p style="margin-top:10px;">Les options <code>cta_label</code> et <code>cta_url</code> remplacent celles saisies dans la section <em>Taglines &amp; CTA</em> de la métabox.</p>

        <h4>Exemples d'utilisation :</h4>
        <pre style="background:#fff; padding:10px; border:1px solid #ddd; border-radius:3px;">
<span style="color:#666;">// Bloc complet avec tous les éléments (recommandé)</span>
[jlg_bloc_complet]

<span style="color:#666;">// Sans la tagline du haut</span>
[jlg_bloc_complet afficher_tagline="non"]

<span style="color:#666;">// Style compact pour économiser l'espace</span>
[jlg_bloc_complet style="compact"]

<span style="color:#666;">// Avec couleur personnalisée</span>
[jlg_bloc_complet couleur_accent="#ff6b6b"]

<span style="color:#666;">// Seulement notation et points (sans tagline)</span>
[jlg_bloc_complet afficher_tagline="non"]

<span style="color:#666;">// Bouton personnalisé avec texte et URL dédiés</span>
[jlg_bloc_complet cta_label="Acheter le jeu" cta_url="https://exemple.com/boutique" cta_rel="nofollow noopener"]

<span style="color:#666;">// Configuration complète personnalisée</span>
[jlg_bloc_complet style="moderne" couleur_accent="#8b5cf6" titre_points_forts="Les +" titre_points_faibles="Les -"]</pre>

        <div style="background:#e8f5e9; padding:10px; margin-top:10px; border-radius:4px;">
            <strong>💡 Conseil :</strong> Ce shortcode est idéal pour remplacer les 3 shortcodes séparés et avoir une présentation unifiée et professionnelle.
        </div>
    </div>

    <hr style="margin: 30px 0; border:none; border-top:2px solid #e0e0e0;">

    <h3 style="color:#666; margin-bottom:20px;">Shortcodes individuels (si vous préférez les utiliser séparément)</h3>

    <!-- Bloc de notation principal -->
    <div style="background:#f9f9f9; padding:20px; margin-bottom:20px; border-left:4px solid #0073aa; border-radius:4px;">
        <h3>2. Bloc de Notation Principal (seul)</h3>
        <code style="background:#fff; padding:5px 10px; display:inline-block; border-radius:3px;">[bloc_notation_jeu]</code>

        <h4>Paramètres :</h4>
        <ul>
            <li><strong>post_id</strong> (optionnel) : ID d'un article spécifique. Par défaut : article actuel</li>
        </ul>

        <h4>Exemples :</h4>
        <pre style="background:#fff; padding:10px; border:1px solid #ddd; border-radius:3px;">
[bloc_notation_jeu]
[bloc_notation_jeu post_id="123"]</pre>
    </div>

    <!-- Fiche technique -->
    <div style="background:#f9f9f9; padding:20px; margin-bottom:20px; border-left:4px solid #0073aa; border-radius:4px;">
        <h3>3. Fiche Technique</h3>
        <code style="background:#fff; padding:5px 10px; display:inline-block; border-radius:3px;">[jlg_fiche_technique]</code>

        <h4>Paramètres :</h4>
        <ul>
            <li><strong>titre</strong> : Titre du bloc (défaut : "Fiche Technique")</li>
            <li><strong>champs</strong> : Champs à afficher, séparés par des virgules</li>
        </ul>

        <h4>Champs disponibles :</h4>
        <ul style="columns:2;">
            <li>developpeur</li>
            <li>editeur</li>
            <li>date_sortie</li>
            <li>version</li>
            <li>pegi</li>
            <li>temps_de_jeu</li>
            <li>plateformes</li>
        </ul>

        <h4>Exemples :</h4>
        <pre style="background:#fff; padding:10px; border:1px solid #ddd; border-radius:3px;">
[jlg_fiche_technique]
[jlg_fiche_technique titre="Informations"]
[jlg_fiche_technique champs="developpeur,editeur,date_sortie"]
[jlg_fiche_technique titre="Info Rapide" champs="plateformes,pegi"]</pre>
    </div>

    <!-- Tableau récapitulatif -->
    <div style="background:#f9f9f9; padding:20px; margin-bottom:20px; border-left:4px solid #0073aa; border-radius:4px;">
        <h3>4. Tableau Récapitulatif</h3>
        <code style="background:#fff; padding:5px 10px; display:inline-block; border-radius:3px;">[jlg_tableau_recap]</code>

        <h4>Paramètres :</h4>
        <ul>
            <li><strong>posts_per_page</strong> : Nombre d'articles par page (défaut : 12)</li>
            <li><strong>layout</strong> : "table" ou "grid" (défaut : "table")</li>
            <li><strong>categorie</strong> : Slug de catégorie à filtrer</li>
            <li><strong>colonnes</strong> : Colonnes à afficher (table uniquement)</li>
        </ul>

        <h4>Colonnes disponibles :</h4>
        <ul>
            <li><strong>titre</strong> : Titre du jeu</li>
            <li><strong>date</strong> : Date de publication</li>
            <li><strong>note</strong> : Note moyenne</li>
            <li><strong>developpeur</strong> : Développeur</li>
            <li><strong>editeur</strong> : Éditeur</li>
        </ul>

        <p><strong>Tri :</strong> les en-têtes sont cliquables pour ordonner les résultats par titre (`orderby=title`), date (`orderby=date`), note moyenne (`orderby=average_score`) ainsi que par métadonnées développeur ou éditeur (`orderby=meta__jlg_developpeur`, `orderby=meta__jlg_editeur`).</p>

        <h4>Exemples :</h4>
        <pre style="background:#fff; padding:10px; border:1px solid #ddd; border-radius:3px;">
[jlg_tableau_recap]
[jlg_tableau_recap layout="grid"]
[jlg_tableau_recap posts_per_page="20"]
[jlg_tableau_recap categorie="fps"]
[jlg_tableau_recap colonnes="titre,note,developpeur"]
[jlg_tableau_recap layout="grid" posts_per_page="16" categorie="rpg"]
[jlg_tableau_recap colonnes="titre,date,note,editeur" posts_per_page="30"]</pre>
    </div>

    <!-- Points forts/faibles -->
    <div style="background:#f9f9f9; padding:20px; margin-bottom:20px; border-left:4px solid #0073aa; border-radius:4px;">
        <h3>5. Points Forts et Faibles (seuls)</h3>
        <code style="background:#fff; padding:5px 10px; display:inline-block; border-radius:3px;">[jlg_points_forts_faibles]</code>

        <p>Affiche automatiquement les points forts et faibles définis dans les métadonnées de l'article.</p>
        <p><em>Pas de paramètres - utilise les données de l'article actuel.</em></p>

        <h4>Exemple :</h4>
        <pre style="background:#fff; padding:10px; border:1px solid #ddd; border-radius:3px;">[jlg_points_forts_faibles]</pre>
    </div>

    <!-- Tagline -->
    <div style="background:#f9f9f9; padding:20px; margin-bottom:20px; border-left:4px solid #0073aa; border-radius:4px;">
        <h3>6. Tagline Bilingue (seule)</h3>
        <code style="background:#fff; padding:5px 10px; display:inline-block; border-radius:3px;">[tagline_notation_jlg]</code>

        <p>Affiche la phrase d'accroche avec switch de langue FR/EN.</p>
        <p><em>Pas de paramètres - utilise les données de l'article actuel.</em></p>

        <h4>Exemple :</h4>
        <pre style="background:#fff; padding:10px; border:1px solid #ddd; border-radius:3px;">[tagline_notation_jlg]</pre>
    </div>

    <!-- Notation utilisateurs -->
    <div style="background:#f9f9f9; padding:20px; margin-bottom:20px; border-left:4px solid #0073aa; border-radius:4px;">
        <h3>7. Notation Utilisateurs</h3>
        <code style="background:#fff; padding:5px 10px; display:inline-block; border-radius:3px;">[notation_utilisateurs_jlg]</code>

        <p>Permet aux visiteurs de voter (système 5 étoiles).</p>
        <p><em>Pas de paramètres - utilise l'article actuel.</em></p>

        <h4>Exemple :</h4>
        <pre style="background:#fff; padding:10px; border:1px solid #ddd; border-radius:3px;">[notation_utilisateurs_jlg]</pre>
    </div>
</div>

