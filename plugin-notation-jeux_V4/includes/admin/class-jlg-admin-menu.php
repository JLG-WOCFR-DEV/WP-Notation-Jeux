<?php
/**
 * Gestion du menu admin et des pages
 * 
 * @package JLG_Notation
 * @version 5.0
 */

if (!defined('ABSPATH')) exit;

class JLG_Admin_Menu {
    private $page_slug = 'notation_jlg_settings';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Notation JLG',
            'Notation - JLG',
            'manage_options',
            $this->page_slug,
            [$this, 'render_admin_page'],
            'dashicons-star-filled',
            30
        );
    }
    
    private function render_platforms_tab() {
        // Utiliser l'instance singleton de la classe Platforms
        if (class_exists('JLG_Admin_Platforms')) {
            $platforms_manager = JLG_Admin_Platforms::get_instance();
            $platforms_manager->render_platforms_page();
        } else {
            // Si la classe n'existe pas, essayer de la charger
            $path = JLG_NOTATION_PLUGIN_DIR . 'includes/admin/class-jlg-admin-platforms.php';
            if (file_exists($path)) {
                require_once $path;
                if (class_exists('JLG_Admin_Platforms')) {
                    $platforms_manager = JLG_Admin_Platforms::get_instance();
                    $platforms_manager->render_platforms_page();
                } else {
                    echo '<div class="notice notice-error"><p>La classe de gestion des plateformes n\'a pas pu être chargée.</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>Fichier class-jlg-admin-platforms.php introuvable.</p></div>';
            }
        }
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé.');
        }

        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'reglages';
        
        echo '<div class="wrap">';
        echo '<h1>⭐ Notation JLG v5.0</h1>';
        
        // Navigation
        $this->render_tab_navigation($active_tab);
        
        // Contenu
        echo '<div style="background:#fff; padding:20px; margin-top:20px; border-radius:8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
        switch ($active_tab) {
            case 'reglages':
                $this->render_settings_tab();
                break;
            case 'articles_notes':
                $this->render_posts_list_tab();
                break;
            case 'plateformes':
                $this->render_platforms_tab();
                break;
            case 'shortcodes':
                $this->render_shortcodes_tab();
                break;
            case 'tutoriels':
                $this->render_tutorials_tab();
                break;
            default:
                $this->render_settings_tab();
        }
        echo '</div>';
        
        echo '</div>';
    }

    private function render_tab_navigation($active_tab) {
        $tabs = [
            'reglages' => '⚙️ Réglages',
            'articles_notes' => '📊 Articles Notés',
            'plateformes' => '🎮 Plateformes',
            'shortcodes' => '📝 Shortcodes',
            'tutoriels' => '📚 Tutoriels'
        ];

        echo '<h2 class="nav-tab-wrapper">';
        foreach ($tabs as $tab_key => $tab_label) {
            $active_class = ($active_tab === $tab_key) ? 'nav-tab-active' : '';
            $url = add_query_arg(['page' => $this->page_slug, 'tab' => $tab_key], admin_url('admin.php'));
            
            printf(
                '<a href="%s" class="nav-tab %s">%s</a>',
                esc_url($url),
                esc_attr($active_class),
                esc_html($tab_label)
            );
        }
        echo '</h2>';
    }

    private function render_settings_tab() {
        echo '<h2>🎨 Configuration du Plugin</h2>';
        echo '<p>Personnalisez l\'apparence et le comportement du système de notation.</p>';
        echo '<form action="options.php" method="post">';
        settings_fields('notation_jlg_page');
        do_settings_sections('notation_jlg_page');
        submit_button('💾 Enregistrer les modifications');
        echo '</form>';
    }

    private function render_posts_list_tab() {
        $rated_posts = JLG_Helpers::get_rated_post_ids();
        
        echo '<h2>📊 Vos Articles avec Notation</h2>';
        
        if (empty($rated_posts)) {
            echo '<div style="text-align:center; padding:40px; background:#f9f9f9; border-radius:8px; margin-top:20px;">';
            echo '<h3>🎮 Aucun test trouvé</h3>';
            echo '<p>Créez votre premier article avec notation !</p>';
            echo '<a href="' . admin_url('post-new.php') . '" class="button button-primary">✏️ Créer un Test</a>';
            echo '</div>';
            return;
        }

        // Configuration de la pagination
        $per_page = 30;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $total_items = count($rated_posts);
        $total_pages = ceil($total_items / $per_page);
        $offset = ($current_page - 1) * $per_page;
        
        // Tri des articles
        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'date';
        $order = isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC']) ? strtoupper($_GET['order']) : 'DESC';
        
        // Récupération des posts pour cette page avec tri
        $args = [
            'post_type' => 'post',
            'post__in' => $rated_posts,
            'posts_per_page' => $per_page,
            'paged' => $current_page,
            'orderby' => $orderby === 'score' ? 'meta_value_num' : $orderby,
            'order' => $order
        ];
        
        if ($orderby === 'score') {
            $args['meta_key'] = '_jlg_average_score';
        }
        
        $query = new WP_Query($args);
        
        // Affichage des statistiques
        echo '<div style="background:#f0f6fc; padding:15px; border-radius:4px; margin-bottom:20px;">';
        printf(
            '<strong>%d</strong> articles avec notation trouvés • Page <strong>%d</strong> sur <strong>%d</strong> • Affichage de <strong>%d</strong> articles',
            $total_items, 
            $current_page, 
            $total_pages,
            min($per_page, $total_items - $offset)
        );
        echo '</div>';
        
        // Tableau des articles
        echo '<table class="wp-list-table widefat striped">';
        echo '<thead><tr>';
        
        // En-têtes avec tri
        $this->render_sortable_column('Titre', 'title', $orderby, $order);
        $this->render_sortable_column('Date', 'date', $orderby, $order);
        $this->render_sortable_column('Note', 'score', $orderby, $order);
        echo '<th>Catégories</th>';
        echo '<th>Actions</th>';
        
        echo '</tr></thead><tbody>';
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $score = get_post_meta($post_id, '_jlg_average_score', true);
                
                // Récupération des catégories
                $categories = get_the_category($post_id);
                $cat_names = array_map(function($cat) { return $cat->name; }, $categories);
                
                echo '<tr>';
                printf('<td><strong><a href="%s">%s</a></strong></td>', 
                    get_edit_post_link($post_id), 
                    esc_html(get_the_title())
                );
                printf('<td>%s</td>', get_the_date('d/m/Y', $post_id));
                
                // Note avec couleur selon valeur
                $score_color = '#0073aa';
                if ($score !== '' && is_numeric($score)) {
                    if ($score >= 8) $score_color = '#22c55e';
                    elseif ($score >= 5) $score_color = '#f97316';
                    else $score_color = '#ef4444';
                }
                printf('<td><strong style="color:%s;">%s</strong>/10</td>', 
                    $score_color,
                    esc_html($score ?: 'N/A')
                );
                
                printf('<td>%s</td>', implode(', ', $cat_names) ?: '-');
                printf(
                    '<td><a href="%s" target="_blank">👁 Voir</a> | <a href="%s">✏️ Modifier</a></td>', 
                    get_permalink($post_id), 
                    get_edit_post_link($post_id)
                );
                echo '</tr>';
            }
        }
        
        echo '</tbody></table>';
        
        wp_reset_postdata();
        
        // Pagination
        if ($total_pages > 1) {
            echo '<div class="tablenav bottom">';
            echo '<div class="tablenav-pages">';
            
            $pagination_args = [
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'total' => $total_pages,
                'current' => $current_page,
                'show_all' => false,
                'end_size' => 1,
                'mid_size' => 2,
                'type' => 'plain',
                'before_page_number' => '<span class="screen-reader-text">Page </span>'
            ];
            
            // Conserver les paramètres de tri dans la pagination
            if (isset($_GET['orderby'])) {
                $pagination_args['add_args'] = ['orderby' => $orderby, 'order' => $order];
            }
            
            echo paginate_links($pagination_args);
            echo '</div>';
            echo '</div>';
        }
        
        // Bouton d'export (optionnel)
        echo '<div style="margin-top:20px;">';
        echo '<p><a href="#" class="button" onclick="window.print(); return false;">🖨️ Imprimer cette liste</a></p>';
        echo '</div>';
    }

    /**
     * Helper pour créer une colonne triable
     */
    private function render_sortable_column($label, $column, $current_orderby, $current_order) {
        $new_order = ($current_orderby === $column && $current_order === 'ASC') ? 'DESC' : 'ASC';
        $class = 'manage-column column-' . $column;
        
        if ($current_orderby === $column) {
            $class .= ' sorted ' . strtolower($current_order);
        } else {
            $class .= ' sortable desc';
        }
        
        $url = add_query_arg([
            'page' => $this->page_slug,
            'tab' => 'articles_notes',
            'orderby' => $column,
            'order' => $new_order,
            'paged' => 1 // Retour à la première page lors du tri
        ], admin_url('admin.php'));
        
        printf(
            '<th scope="col" class="%s"><a href="%s"><span>%s</span><span class="sorting-indicator" aria-hidden="true"></span></a></th>',
            esc_attr($class),
            esc_url($url),
            esc_html($label)
        );
    }

    private function render_shortcodes_tab() {
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
                </ul>
                
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
        <?php
    }

    private function render_tutorials_tab() {
        echo '<h2>📚 Guide d\'Utilisation</h2>';
        echo '<p>Tutoriels et guides pour tirer le meilleur parti du plugin.</p>';
        
        // Nouveau bloc en vedette pour le shortcode tout-en-un
        echo '<div style="background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; padding:30px; border-radius:12px; margin:30px 0; box-shadow: 0 10px 25px rgba(102,126,234,0.3);">';
        echo '<h2 style="color:white; margin-top:0;">🚀 Nouveauté : Bloc Complet Tout-en-Un</h2>';
        echo '<p style="font-size:18px; margin-bottom:20px;">Simplifiez votre workflow avec le nouveau shortcode <code style="background:rgba(255,255,255,0.2); padding:3px 8px; border-radius:4px;">[jlg_bloc_complet]</code> qui combine automatiquement :</p>';
        echo '<div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:15px;">';
        echo '<div style="background:rgba(255,255,255,0.1); padding:15px; border-radius:8px;">✅ Tagline bilingue</div>';
        echo '<div style="background:rgba(255,255,255,0.1); padding:15px; border-radius:8px;">✅ Notation détaillée</div>';
        echo '<div style="background:rgba(255,255,255,0.1); padding:15px; border-radius:8px;">✅ Points forts/faibles</div>';
        echo '</div>';
        echo '<p style="margin-top:20px;"><strong>Un seul shortcode pour tout afficher de manière élégante et cohérente !</strong></p>';
        echo '</div>';
        
        echo '<div class="jlg-tutorial-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px; margin-top:30px;">';
        
        $tutorials = [
            [
                'title' => '⚡ Démarrage rapide avec le Bloc Complet',
                'content' => 'La méthode la plus simple pour créer un test professionnel.',
                'steps' => [
                    'Créer un nouvel article',
                    'Remplir les notes dans la metabox (colonne droite)',
                    'Ajouter tagline et points forts/faibles',
                    'Insérer [jlg_bloc_complet] dans le contenu',
                    'C\'est tout ! Publiez votre test'
                ]
            ],
            [
                'title' => '🎮 Créer un test détaillé (méthode classique)',
                'content' => 'Guide pas-à-pas pour un contrôle total.',
                'steps' => [
                    'Créer un nouvel article',
                    'Remplir la metabox "Notation" (colonne droite)', 
                    'Ajouter les détails du jeu (metabox principale)',
                    'Intégrer les shortcodes séparés si besoin',
                    'Publier et vérifier l\'affichage'
                ]
            ],
            [
                'title' => '🎨 Personnalisation du Bloc Complet',
                'content' => 'Adapter le nouveau bloc à vos besoins.',
                'steps' => [
                    'Choisir le style (moderne/classique/compact)',
                    'Définir une couleur d\'accent personnalisée',
                    'Activer/désactiver les sections',
                    'Personnaliser les titres des sections',
                    'Combiner avec d\'autres shortcodes si besoin'
                ]
            ],
            [
                'title' => '🎨 Personnalisation visuelle globale',
                'content' => 'Ajuster l\'apparence générale.',
                'steps' => [
                    'Choisir le thème (clair/sombre)',
                    'Activer les effets Neon/Glow',
                    'Configurer la pulsation',
                    'Personnaliser les couleurs',
                    'Ajouter du CSS personnalisé'
                ]
            ],
            [
                'title' => '📊 Tableau récapitulatif avancé',
                'content' => 'Maîtriser le shortcode tableau.',
                'steps' => [
                    'Choisir entre table et grille',
                    'Sélectionner les colonnes à afficher',
                    'Filtrer par catégorie',
                    'Ajuster la pagination',
                    'Personnaliser les couleurs dans Réglages'
                ]
            ],
            [
                'title' => '⚡ Optimisations',
                'content' => 'Améliorer les performances.',
                'steps' => [
                    'Utiliser [jlg_bloc_complet] au lieu de 3 shortcodes',
                    'Activer un plugin de cache',
                    'Optimiser les images de couverture',
                    'Limiter le nombre d\'articles affichés',
                    'Désactiver les animations si non nécessaires'
                ]
            ],
            [
                'title' => '🔧 Intégration dans le thème',
                'content' => 'Pour les développeurs.',
                'steps' => [
                    'Ajouter jlg_display_thumbnail_score() dans les templates',
                    'Utiliser jlg_get_post_rating() pour récupérer la note',
                    'Personnaliser les templates dans /templates/',
                    'Créer des hooks personnalisés',
                    'Surcharger les styles CSS du plugin'
                ]
            ],
            [
                'title' => '❓ Dépannage',
                'content' => 'Résoudre les problèmes courants.',
                'steps' => [
                    'Vérifier les conflits de plugins',
                    'Vider le cache navigateur et site',
                    'Vérifier les permissions utilisateur',
                    'Consulter les logs d\'erreur',
                    'Réinitialiser les réglages si nécessaire'
                ]
            ]
        ];

        foreach ($tutorials as $tutorial) {
            echo '<div style="background:#f9f9f9; padding:20px; border-radius:8px; border-left:4px solid #0073aa;">';
            echo '<h3>' . esc_html($tutorial['title']) . '</h3>';
            echo '<p>' . esc_html($tutorial['content']) . '</p>';
            echo '<ol style="margin-left:20px;">';
            foreach ($tutorial['steps'] as $step) {
                echo '<li>' . esc_html($step) . '</li>';
            }
            echo '</ol>';
            echo '</div>';
        }

        echo '</div>';
        
        // Section exemples d'utilisation
        echo '<div style="background:#e3f2fd; padding:20px; border-radius:8px; margin-top:30px; border-left:4px solid #2196f3;">';
        echo '<h3>📝 Exemples d\'utilisation du Bloc Complet</h3>';
        echo '<p>Voici différentes configurations possibles pour le shortcode <code>[jlg_bloc_complet]</code> :</p>';
        echo '<div style="background:white; padding:15px; border-radius:4px; margin-top:15px;">';
        echo '<h4>Configuration minimale (recommandée pour débuter) :</h4>';
        echo '<pre style="background:#f5f5f5; padding:10px; border-left:3px solid #4caf50;">[jlg_bloc_complet]</pre>';
        echo '</div>';
        echo '<div style="background:white; padding:15px; border-radius:4px; margin-top:15px;">';
        echo '<h4>Style compact sans tagline :</h4>';
        echo '<pre style="background:#f5f5f5; padding:10px; border-left:3px solid #ff9800;">[jlg_bloc_complet style="compact" afficher_tagline="non"]</pre>';
        echo '</div>';
        echo '<div style="background:white; padding:15px; border-radius:4px; margin-top:15px;">';
        echo '<h4>Personnalisation complète :</h4>';
        echo '<pre style="background:#f5f5f5; padding:10px; border-left:3px solid #9c27b0;">[jlg_bloc_complet style="moderne" couleur_accent="#e91e63" titre_points_forts="Les +" titre_points_faibles="Les -"]</pre>';
        echo '</div>';
        echo '</div>';
        
        // Section migration
        echo '<div style="background:#fce4ec; padding:20px; border-radius:8px; margin-top:30px; border-left:4px solid #e91e63;">';
        echo '<h3>🔄 Migration vers le Bloc Complet</h3>';
        echo '<p><strong>Vous utilisez déjà les shortcodes séparés ?</strong> Voici comment migrer :</p>';
        echo '<table style="width:100%; background:white; border-radius:4px; overflow:hidden; margin-top:15px;">';
        echo '<tr style="background:#f5f5f5;">';
        echo '<th style="padding:10px; text-align:left;">Avant (3 shortcodes)</th>';
        echo '<th style="padding:10px; text-align:left;">Après (1 shortcode)</th>';
        echo '</tr>';
        echo '<tr>';
        echo '<td style="padding:10px; border-top:1px solid #ddd;"><pre>[tagline_notation_jlg]
[bloc_notation_jeu]
[jlg_points_forts_faibles]</pre></td>';
        echo '<td style="padding:10px; border-top:1px solid #ddd;"><pre>[jlg_bloc_complet]</pre></td>';
        echo '</tr>';
        echo '</table>';
        echo '<p style="margin-top:15px;"><em>✅ Plus simple, plus cohérent, même résultat en mieux !</em></p>';
        echo '</div>';
        
        echo '<div style="background:#fff3cd; padding:20px; border-radius:8px; margin-top:30px; border-left:4px solid #ffc107;">';
        echo '<h3>💡 Astuce Pro</h3>';
        echo '<p><strong>Pour une intégration optimale :</strong> Créez un template d\'article dédié aux tests dans votre thème avec le shortcode <code>[jlg_bloc_complet]</code> pré-intégré. Ainsi, vous n\'aurez plus qu\'à remplir les métadonnées !</p>';
        echo '<p style="margin-top:10px;">Exemple de template personnalisé :</p>';
        echo '<pre style="background:#f5f5f5; padding:10px; border-radius:4px;">&lt;?php
// Dans votre template single-test.php
if (have_posts()) : while (have_posts()) : the_post(); ?&gt;
    &lt;article&gt;
        &lt;h1&gt;&lt;?php the_title(); ?&gt;&lt;/h1&gt;
        
        &lt;!-- Bloc de notation complet automatique --&gt;
        &lt;?php echo do_shortcode(\'[jlg_bloc_complet]\'); ?&gt;
        
        &lt;!-- Contenu de l\'article --&gt;
        &lt;?php the_content(); ?&gt;
        
        &lt;!-- Notation des utilisateurs --&gt;
        &lt;?php echo do_shortcode(\'[notation_utilisateurs_jlg]\'); ?&gt;
    &lt;/article&gt;
&lt;?php endwhile; endif; ?&gt;</pre>';
        echo '</div>';
        
        // Section FAQ
        echo '<div style="background:#f3e5f5; padding:20px; border-radius:8px; margin-top:30px; border-left:4px solid #9c27b0;">';
        echo '<h3>❓ Questions Fréquentes sur le Bloc Complet</h3>';
        echo '<details style="margin:10px 0; background:white; padding:10px; border-radius:4px;">';
        echo '<summary style="cursor:pointer; font-weight:bold;">Puis-je utiliser le bloc complet ET les shortcodes séparés ?</summary>';
        echo '<p style="margin-top:10px;">Oui, mais évitez la duplication. Utilisez soit le bloc complet, soit les shortcodes individuels, pas les deux ensemble.</p>';
        echo '</details>';
        echo '<details style="margin:10px 0; background:white; padding:10px; border-radius:4px;">';
        echo '<summary style="cursor:pointer; font-weight:bold;">Comment changer l\'ordre des sections ?</summary>';
        echo '<p style="margin-top:10px;">L\'ordre est fixe (Tagline → Notation → Points), mais vous pouvez masquer des sections avec les paramètres afficher_*="non".</p>';
        echo '</details>';
        echo '<details style="margin:10px 0; background:white; padding:10px; border-radius:4px;">';
        echo '<summary style="cursor:pointer; font-weight:bold;">Le bloc complet est-il plus lourd en performance ?</summary>';
        echo '<p style="margin-top:10px;">Non, au contraire ! Un seul shortcode est plus performant que trois shortcodes séparés.</p>';
        echo '</details>';
        echo '<details style="margin:10px 0; background:white; padding:10px; border-radius:4px;">';
        echo '<summary style="cursor:pointer; font-weight:bold;">Puis-je avoir plusieurs blocs complets sur la même page ?</summary>';
        echo '<p style="margin-top:10px;">Oui, en utilisant le paramètre post_id pour cibler différents articles : [jlg_bloc_complet post_id="123"]</p>';
        echo '</details>';
        echo '</div>';
        
        // Section Gestion des Plateformes
        echo '<div style="background:#e8f5e9; padding:20px; border-radius:8px; margin-top:30px; border-left:4px solid #4caf50;">';
        echo '<h3>🎮 Gestion des Plateformes</h3>';
        echo '<p><strong>Nouveau système de plateformes dynamiques !</strong></p>';
        echo '<ul style="margin-left:20px;">';
        echo '<li>Ajoutez vos propres plateformes depuis l\'onglet "Plateformes"</li>';
        echo '<li>Réorganisez l\'ordre d\'affichage par glisser-déposer</li>';
        echo '<li>Les plateformes personnalisées apparaissent automatiquement dans les metaboxes</li>';
        echo '<li>Supprimez les plateformes obsolètes en un clic</li>';
        echo '<li>Compatibilité totale avec le shortcode [jlg_fiche_technique]</li>';
        echo '</ul>';
        echo '<p style="margin-top:15px;">';
        echo '<a href="' . admin_url('admin.php?page=' . $this->page_slug . '&tab=plateformes') . '" class="button button-primary">Gérer les plateformes →</a>';
        echo '</p>';
        echo '</div>';
    }
}