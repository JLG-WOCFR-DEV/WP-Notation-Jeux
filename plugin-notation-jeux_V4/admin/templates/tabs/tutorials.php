<?php
if (!defined('ABSPATH')) exit;

$tutorials = isset($variables['tutorials']) && is_array($variables['tutorials']) ? $variables['tutorials'] : [];
$platforms_url = $variables['platforms_url'] ?? '';
?>
<h2>📚 Guide d'Utilisation</h2>
<p>Tutoriels et guides pour tirer le meilleur parti du plugin.</p>

<section class="components-card jlg-hero-card jlg-admin-section" role="region" aria-labelledby="jlg-hero-card-title">
    <div class="components-card__body">
        <h2 id="jlg-hero-card-title" class="jlg-hero-card__title">🚀 Nouveauté : Bloc Complet Tout-en-Un</h2>
        <p class="jlg-hero-card__lead">Simplifiez votre workflow avec le nouveau shortcode <code class="jlg-code">[jlg_bloc_complet]</code> qui combine automatiquement :</p>
        <div class="jlg-feature-grid" role="list">
            <div class="jlg-feature-pill" role="listitem">✅ Tagline bilingue</div>
            <div class="jlg-feature-pill" role="listitem">✅ Notation détaillée</div>
            <div class="jlg-feature-pill" role="listitem">✅ Points forts/faibles</div>
        </div>
        <p class="jlg-hero-card__foot"><strong>Un seul shortcode pour tout afficher de manière élégante et cohérente !</strong></p>
    </div>
</section>

<section class="jlg-admin-section" aria-label="Tutoriels guidés">
    <div class="jlg-card-grid" role="list">
        <?php foreach ($tutorials as $tutorial) :
            $steps = isset($tutorial['steps']) && is_array($tutorial['steps']) ? $tutorial['steps'] : [];
            ?>
            <article class="components-card jlg-tutorial-card" role="listitem">
                <div class="components-card__body">
                    <h3 class="jlg-tutorial-card__title"><?php echo esc_html($tutorial['title'] ?? ''); ?></h3>
                    <p class="jlg-tutorial-card__description"><?php echo esc_html($tutorial['content'] ?? ''); ?></p>
                    <?php if (!empty($steps)) : ?>
                        <ol class="jlg-tutorial-card__steps">
                            <?php foreach ($steps as $step) : ?>
                                <li><?php echo esc_html($step); ?></li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="components-card jlg-section-card jlg-section-card--info jlg-admin-section" role="region" aria-labelledby="jlg-examples-title">
    <div class="components-card__body">
        <h3 id="jlg-examples-title">📝 Exemples d'utilisation du Bloc Complet</h3>
        <p>Voici différentes configurations possibles pour le shortcode <code class="jlg-code">[jlg_bloc_complet]</code> :</p>
        <div class="jlg-example-list">
            <div class="jlg-example-card">
                <h4>Configuration minimale (recommandée pour débuter) :</h4>
                <pre class="jlg-code-block">[jlg_bloc_complet]</pre>
            </div>
            <div class="jlg-example-card">
                <h4>Style compact sans tagline :</h4>
                <pre class="jlg-code-block">[jlg_bloc_complet style="compact" afficher_tagline="non"]</pre>
            </div>
            <div class="jlg-example-card">
                <h4>Personnalisation complète :</h4>
                <pre class="jlg-code-block">[jlg_bloc_complet style="moderne" couleur_accent="#e91e63" titre_points_forts="Les +" titre_points_faibles="Les -"]</pre>
            </div>
        </div>
    </div>
</section>

<section class="components-card jlg-section-card jlg-section-card--migration jlg-admin-section" role="region" aria-labelledby="jlg-migration-title">
    <div class="components-card__body">
        <h3 id="jlg-migration-title">🔄 Migration vers le Bloc Complet</h3>
        <p><strong>Vous utilisez déjà les shortcodes séparés ?</strong> Voici comment migrer :</p>
        <div class="jlg-table-wrapper" role="group" aria-label="Comparaison avant après">
            <table class="jlg-compare-table">
                <thead>
                    <tr>
                        <th scope="col">Avant (3 shortcodes)</th>
                        <th scope="col">Après (1 shortcode)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><pre class="jlg-code-block">[tagline_notation_jlg]
[bloc_notation_jeu]
[jlg_points_forts_faibles]</pre></td>
                        <td><pre class="jlg-code-block">[jlg_bloc_complet]</pre></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="jlg-migration-note"><em>✅ Plus simple, plus cohérent, même résultat en mieux !</em></p>
    </div>
</section>

<section class="components-card jlg-section-card jlg-section-card--tip jlg-admin-section" role="region" aria-labelledby="jlg-pro-tip-title">
    <div class="components-card__body">
        <h3 id="jlg-pro-tip-title">💡 Astuce Pro</h3>
        <p><strong>Pour une intégration optimale :</strong> Créez un template d'article dédié aux tests dans votre thème avec le shortcode <code class="jlg-code">[jlg_bloc_complet]</code> pré-intégré. Ainsi, vous n'aurez plus qu'à remplir les métadonnées !</p>
        <p>Exemple de template personnalisé :</p>
        <pre class="jlg-code-block">&lt;?php
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
</section>

<section class="components-card jlg-section-card jlg-section-card--faq jlg-admin-section" role="region" aria-labelledby="jlg-faq-title">
    <div class="components-card__body">
        <h3 id="jlg-faq-title">❓ Questions Fréquentes sur le Bloc Complet</h3>
        <div class="jlg-faq-list">
            <details class="jlg-faq" data-level="0">
                <summary>Puis-je utiliser le bloc complet ET les shortcodes séparés ?</summary>
                <p>Oui, mais évitez la duplication. Utilisez soit le bloc complet, soit les shortcodes individuels, pas les deux ensemble.</p>
            </details>
            <details class="jlg-faq" data-level="0">
                <summary>Comment changer l'ordre des sections ?</summary>
                <p>L'ordre est fixe (Tagline → Notation → Points), mais vous pouvez masquer des sections avec les paramètres afficher_*="non".</p>
            </details>
            <details class="jlg-faq" data-level="0">
                <summary>Le bloc complet est-il plus lourd en performance ?</summary>
                <p>Non, au contraire ! Un seul shortcode est plus performant que trois shortcodes séparés.</p>
            </details>
            <details class="jlg-faq" data-level="0">
                <summary>Puis-je avoir plusieurs blocs complets sur la même page ?</summary>
                <p>Oui, en utilisant le paramètre post_id pour cibler différents articles : [jlg_bloc_complet post_id="123"]</p>
            </details>
        </div>
    </div>
</section>

<section class="components-card jlg-section-card jlg-section-card--success jlg-admin-section" role="region" aria-labelledby="jlg-platforms-title">
    <div class="components-card__body">
        <h3 id="jlg-platforms-title">🎮 Gestion des Plateformes</h3>
        <p><strong>Nouveau système de plateformes dynamiques !</strong></p>
        <ul class="jlg-bullet-list">
            <li>Ajoutez vos propres plateformes depuis l'onglet « Plateformes »</li>
            <li>Réorganisez l'ordre d'affichage par glisser-déposer</li>
            <li>Associez des tags WordPress à chaque plateforme pour créer des passerelles éditoriales</li>
            <li>Les plateformes personnalisées apparaissent automatiquement dans les metaboxes</li>
            <li>Supprimez les plateformes obsolètes en un clic</li>
            <li>Compatibilité totale avec le shortcode [jlg_fiche_technique]</li>
        </ul>
        <p class="jlg-admin-actions jlg-admin-actions--start">
            <a href="<?php echo esc_url($platforms_url); ?>" class="button button-primary">Gérer les plateformes →</a>
        </p>
    </div>
</section>
