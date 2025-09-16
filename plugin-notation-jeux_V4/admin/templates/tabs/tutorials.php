<?php
if (!defined('ABSPATH')) exit;

$tutorials = isset($variables['tutorials']) && is_array($variables['tutorials']) ? $variables['tutorials'] : [];
$platforms_url = $variables['platforms_url'] ?? '';
?>
<h2>📚 Guide d'Utilisation</h2>
<p>Tutoriels et guides pour tirer le meilleur parti du plugin.</p>

<div style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; padding:30px; border-radius:12px; margin:30px 0; box-shadow: 0 10px 25px rgba(102,126,234,0.3);">
    <h2 style="color:white; margin-top:0;">🚀 Nouveauté : Bloc Complet Tout-en-Un</h2>
    <p style="font-size:18px; margin-bottom:20px;">Simplifiez votre workflow avec le nouveau shortcode <code style="background:rgba(255,255,255,0.2); padding:3px 8px; border-radius:4px;">[jlg_bloc_complet]</code> qui combine automatiquement :</p>
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:15px;">
        <div style="background:rgba(255,255,255,0.1); padding:15px; border-radius:8px;">✅ Tagline bilingue</div>
        <div style="background:rgba(255,255,255,0.1); padding:15px; border-radius:8px;">✅ Notation détaillée</div>
        <div style="background:rgba(255,255,255,0.1); padding:15px; border-radius:8px;">✅ Points forts/faibles</div>
    </div>
    <p style="margin-top:20px;"><strong>Un seul shortcode pour tout afficher de manière élégante et cohérente !</strong></p>
</div>

<div class="jlg-tutorial-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px; margin-top:30px;">
    <?php foreach ($tutorials as $tutorial) :
        $steps = isset($tutorial['steps']) && is_array($tutorial['steps']) ? $tutorial['steps'] : [];
        ?>
        <div style="background:#f9f9f9; padding:20px; border-radius:8px; border-left:4px solid #0073aa;">
            <h3><?php echo esc_html($tutorial['title'] ?? ''); ?></h3>
            <p><?php echo esc_html($tutorial['content'] ?? ''); ?></p>
            <?php if (!empty($steps)) : ?>
                <ol style="margin-left:20px;">
                    <?php foreach ($steps as $step) : ?>
                        <li><?php echo esc_html($step); ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<div style="background:#e3f2fd; padding:20px; border-radius:8px; margin-top:30px; border-left:4px solid #2196f3;">
    <h3>📝 Exemples d'utilisation du Bloc Complet</h3>
    <p>Voici différentes configurations possibles pour le shortcode <code>[jlg_bloc_complet]</code> :</p>
    <div style="background:white; padding:15px; border-radius:4px; margin-top:15px;">
        <h4>Configuration minimale (recommandée pour débuter) :</h4>
        <pre style="background:#f5f5f5; padding:10px; border-left:3px solid #4caf50;">[jlg_bloc_complet]</pre>
    </div>
    <div style="background:white; padding:15px; border-radius:4px; margin-top:15px;">
        <h4>Style compact sans tagline :</h4>
        <pre style="background:#f5f5f5; padding:10px; border-left:3px solid #ff9800;">[jlg_bloc_complet style="compact" afficher_tagline="non"]</pre>
    </div>
    <div style="background:white; padding:15px; border-radius:4px; margin-top:15px;">
        <h4>Personnalisation complète :</h4>
        <pre style="background:#f5f5f5; padding:10px; border-left:3px solid #9c27b0;">[jlg_bloc_complet style="moderne" couleur_accent="#e91e63" titre_points_forts="Les +" titre_points_faibles="Les -"]</pre>
    </div>
</div>

<div style="background:#fce4ec; padding:20px; border-radius:8px; margin-top:30px; border-left:4px solid #e91e63;">
    <h3>🔄 Migration vers le Bloc Complet</h3>
    <p><strong>Vous utilisez déjà les shortcodes séparés ?</strong> Voici comment migrer :</p>
    <table style="width:100%; background:white; border-radius:4px; overflow:hidden; margin-top:15px;">
        <tr style="background:#f5f5f5;">
            <th style="padding:10px; text-align:left;">Avant (3 shortcodes)</th>
            <th style="padding:10px; text-align:left;">Après (1 shortcode)</th>
        </tr>
        <tr>
            <td style="padding:10px; border-top:1px solid #ddd;"><pre>[tagline_notation_jlg]
[bloc_notation_jeu]
[jlg_points_forts_faibles]</pre></td>
            <td style="padding:10px; border-top:1px solid #ddd;"><pre>[jlg_bloc_complet]</pre></td>
        </tr>
    </table>
    <p style="margin-top:15px;"><em>✅ Plus simple, plus cohérent, même résultat en mieux !</em></p>
</div>

<div style="background:#fff3cd; padding:20px; border-radius:8px; margin-top:30px; border-left:4px solid #ffc107;">
    <h3>💡 Astuce Pro</h3>
    <p><strong>Pour une intégration optimale :</strong> Créez un template d'article dédié aux tests dans votre thème avec le shortcode <code>[jlg_bloc_complet]</code> pré-intégré. Ainsi, vous n'aurez plus qu'à remplir les métadonnées !</p>
    <p style="margin-top:10px;">Exemple de template personnalisé :</p>
    <pre style="background:#f5f5f5; padding:10px; border-radius:4px;">&lt;?php
// Dans votre template single-test.php
if (have_posts()) : while (have_posts()) : the_post(); ?&gt;
    &lt;article&gt;
        &lt;h1&gt;&lt;?php the_title(); ?&gt;&lt;/h1&gt;

        &lt;!-- Bloc de notation complet automatique --&gt;
        &lt;?php echo do_shortcode('[jlg_bloc_complet]'); ?&gt;

        &lt;!-- Contenu de l'article --&gt;
        &lt;?php the_content(); ?&gt;

        &lt;!-- Notation des utilisateurs --&gt;
        &lt;?php echo do_shortcode('[notation_utilisateurs_jlg]'); ?&gt;
    &lt;/article&gt;
&lt;?php endwhile; endif; ?&gt;</pre>
</div>

<div style="background:#f3e5f5; padding:20px; border-radius:8px; margin-top:30px; border-left:4px solid #9c27b0;">
    <h3>❓ Questions Fréquentes sur le Bloc Complet</h3>
    <details style="margin:10px 0; background:white; padding:10px; border-radius:4px;">
        <summary style="cursor:pointer; font-weight:bold;">Puis-je utiliser le bloc complet ET les shortcodes séparés ?</summary>
        <p style="margin-top:10px;">Oui, mais évitez la duplication. Utilisez soit le bloc complet, soit les shortcodes individuels, pas les deux ensemble.</p>
    </details>
    <details style="margin:10px 0; background:white; padding:10px; border-radius:4px;">
        <summary style="cursor:pointer; font-weight:bold;">Comment changer l'ordre des sections ?</summary>
        <p style="margin-top:10px;">L'ordre est fixe (Tagline → Notation → Points), mais vous pouvez masquer des sections avec les paramètres afficher_*="non".</p>
    </details>
    <details style="margin:10px 0; background:white; padding:10px; border-radius:4px;">
        <summary style="cursor:pointer; font-weight:bold;">Le bloc complet est-il plus lourd en performance ?</summary>
        <p style="margin-top:10px;">Non, au contraire ! Un seul shortcode est plus performant que trois shortcodes séparés.</p>
    </details>
    <details style="margin:10px 0; background:white; padding:10px; border-radius:4px;">
        <summary style="cursor:pointer; font-weight:bold;">Puis-je avoir plusieurs blocs complets sur la même page ?</summary>
        <p style="margin-top:10px;">Oui, en utilisant le paramètre post_id pour cibler différents articles : [jlg_bloc_complet post_id="123"]</p>
    </details>
</div>

<div style="background:#e8f5e9; padding:20px; border-radius:8px; margin-top:30px; border-left:4px solid #4caf50;">
    <h3>🎮 Gestion des Plateformes</h3>
    <p><strong>Nouveau système de plateformes dynamiques !</strong></p>
    <ul style="margin-left:20px;">
        <li>Ajoutez vos propres plateformes depuis l'onglet "Plateformes"</li>
        <li>Réorganisez l'ordre d'affichage par glisser-déposer</li>
        <li>Les plateformes personnalisées apparaissent automatiquement dans les metaboxes</li>
        <li>Supprimez les plateformes obsolètes en un clic</li>
        <li>Compatibilité totale avec le shortcode [jlg_fiche_technique]</li>
    </ul>
    <p style="margin-top:15px;">
        <a href="<?php echo esc_url($platforms_url); ?>" class="button button-primary">Gérer les plateformes →</a>
    </p>
</div>

