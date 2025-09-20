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

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé.');
        }

        $tabs = $this->get_tabs();
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'reglages';
        if (!array_key_exists($active_tab, $tabs)) {
            $active_tab = 'reglages';
        }

        $tab_navigation = $this->get_tab_navigation_html($tabs, $active_tab);
        $tab_content = $this->get_tab_content($active_tab);

        JLG_Template_Loader::display_admin_template('admin-page', [
            'page_title' => '⭐ Notation JLG v5.0',
            'tab_navigation' => $tab_navigation,
            'tab_content' => $tab_content,
        ]);
    }

    private function get_tabs() {
        return [
            'reglages' => '⚙️ Réglages',
            'articles_notes' => '📊 Articles Notés',
            'plateformes' => '🎮 Plateformes',
            'shortcodes' => '📝 Shortcodes',
            'tutoriels' => '📚 Tutoriels',
        ];
    }

    private function get_tab_navigation_html(array $tabs, $active_tab) {
        return JLG_Template_Loader::get_admin_template('partials/tab-navigation', [
            'tabs' => $tabs,
            'active_tab' => $active_tab,
            'page_slug' => $this->page_slug,
        ]);
    }

    private function get_tab_content($active_tab) {
        switch ($active_tab) {
            case 'articles_notes':
                return $this->get_posts_list_tab_content();
            case 'plateformes':
                return $this->get_platforms_tab_content();
            case 'shortcodes':
                return $this->get_shortcodes_tab_content();
            case 'tutoriels':
                return $this->get_tutorials_tab_content();
            case 'reglages':
            default:
                return $this->get_settings_tab_content();
        }
    }

    private function get_settings_tab_content() {
        return JLG_Template_Loader::get_admin_template('tabs/settings', [
            'settings_page' => 'notation_jlg_page',
        ]);
    }

    private function get_posts_list_tab_content() {
        $rated_posts = JLG_Helpers::get_rated_post_ids();
        $empty_state = [
            'create_post_url' => admin_url('post-new.php'),
        ];

        if (empty($rated_posts)) {
            return JLG_Template_Loader::get_admin_template('tabs/posts-list', [
                'has_rated_posts' => false,
                'empty_state' => $empty_state,
            ]);
        }

        $per_page = 30;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'date';
        $order = isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC'], true) ? strtoupper($_GET['order']) : 'DESC';

        $args = [
            'post_type' => 'post',
            'post__in' => $rated_posts,
            'posts_per_page' => $per_page,
            'paged' => $current_page,
            'orderby' => $orderby === 'score' ? 'meta_value_num' : $orderby,
            'order' => $order,
        ];

        if ($orderby === 'score') {
            $args['meta_key'] = '_jlg_average_score';
        }

        $query = new WP_Query($args);

        $posts = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $score_data = JLG_Helpers::get_resolved_average_score($post_id);
                $score_value = $score_data['value'];

                $score_color = '#0073aa';
                if ($score_value !== null) {
                    if ($score_value >= 8) {
                        $score_color = '#22c55e';
                    } elseif ($score_value >= 5) {
                        $score_color = '#f97316';
                    } else {
                        $score_color = '#ef4444';
                    }
                }

                $categories = get_the_category($post_id);
                $cat_names = array_map(function ($cat) {
                    return $cat->name;
                }, $categories);

                $posts[] = [
                    'title' => get_the_title(),
                    'edit_link' => get_edit_post_link($post_id),
                    'view_link' => get_permalink($post_id),
                    'date' => get_the_date('d/m/Y', $post_id),
                    'score_display' => $score_data['formatted'] ?? 'N/A',
                    'score_color' => $score_color,
                    'categories' => $cat_names,
                ];
            }
        }

        wp_reset_postdata();

        $total_items = count($rated_posts);
        $total_pages = (int) ceil($total_items / $per_page);
        $pagination = '';

        if ($total_pages > 1) {
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
                'before_page_number' => '<span class="screen-reader-text">Page </span>',
            ];

            if (isset($_GET['orderby'])) {
                $pagination_args['add_args'] = [
                    'orderby' => $orderby,
                    'order' => $order,
                ];
            }

            $pagination = paginate_links($pagination_args);
        }

        return JLG_Template_Loader::get_admin_template('tabs/posts-list', [
            'has_rated_posts' => true,
            'empty_state' => $empty_state,
            'stats' => [
                'total_items' => $total_items,
                'current_page' => $current_page,
                'total_pages' => $total_pages,
                'display_count' => count($posts),
            ],
            'columns' => $this->get_sortable_columns($orderby, $order),
            'posts' => $posts,
            'pagination' => $pagination,
            'print_button_label' => '🖨️ Imprimer cette liste',
        ]);
    }

    private function get_sortable_columns($current_orderby, $current_order) {
        $columns = [
            ['label' => 'Titre', 'key' => 'title'],
            ['label' => 'Date', 'key' => 'date'],
            ['label' => 'Note', 'key' => 'score'],
        ];

        $results = [];
        foreach ($columns as $column) {
            $column_key = $column['key'];
            $new_order = ($current_orderby === $column_key && $current_order === 'ASC') ? 'DESC' : 'ASC';

            $class = 'manage-column column-' . $column_key;
            $aria_sort = 'none';
            if ($current_orderby === $column_key) {
                $class .= ' sorted ' . strtolower($current_order);
                $aria_sort = ($current_order === 'ASC') ? 'ascending' : 'descending';
            } else {
                $class .= ' sortable desc';
            }

            $url = add_query_arg([
                'page' => $this->page_slug,
                'tab' => 'articles_notes',
                'orderby' => $column_key,
                'order' => $new_order,
                'paged' => 1,
            ], admin_url('admin.php'));

            $results[] = [
                'label' => $column['label'],
                'class' => $class,
                'url' => $url,
                'aria_sort' => $aria_sort,
            ];
        }

        return $results;
    }

    private function get_platforms_tab_content() {
        ob_start();
        $this->render_platforms_tab();
        return ob_get_clean();
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

    private function get_shortcodes_tab_content() {
        return JLG_Template_Loader::get_admin_template('tabs/shortcodes');
    }

    private function get_tutorials_tab_content() {
        $tutorials = [
            [
                'title' => '⚡ Démarrage rapide avec le Bloc Complet',
                'content' => 'La méthode la plus simple pour créer un test professionnel.',
                'steps' => [
                    'Créer un nouvel article',
                    'Remplir les notes dans la metabox (colonne droite)',
                    'Ajouter tagline et points forts/faibles',
                    'Insérer [jlg_bloc_complet] dans le contenu',
                    'C\'est tout ! Publiez votre test',
                ],
            ],
            [
                'title' => '🎮 Créer un test détaillé (méthode classique)',
                'content' => 'Guide pas-à-pas pour un contrôle total.',
                'steps' => [
                    'Créer un nouvel article',
                    'Remplir la metabox "Notation" (colonne droite)',
                    'Ajouter les détails du jeu (metabox principale)',
                    'Intégrer les shortcodes séparés si besoin',
                    'Publier et vérifier l\'affichage',
                ],
            ],
            [
                'title' => '🎨 Personnalisation du Bloc Complet',
                'content' => 'Créez un rendu unique.',
                'steps' => [
                    'Choisir le style (moderne/classique/compact)',
                    'Définir une couleur d\'accent personnalisée',
                    'Activer/désactiver les sections',
                    'Personnaliser les titres des sections',
                    'Combiner avec d\'autres shortcodes si besoin',
                ],
            ],
            [
                'title' => '🎨 Personnalisation visuelle globale',
                'content' => 'Ajuster l\'apparence générale.',
                'steps' => [
                    'Choisir le thème (clair/sombre)',
                    'Activer les effets Neon/Glow',
                    'Configurer la pulsation',
                    'Personnaliser les couleurs',
                    'Ajouter du CSS personnalisé',
                ],
            ],
            [
                'title' => '📊 Tableau récapitulatif avancé',
                'content' => 'Maîtriser le shortcode tableau.',
                'steps' => [
                    'Choisir entre table et grille',
                    'Sélectionner les colonnes à afficher',
                    'Filtrer par catégorie',
                    'Ajuster la pagination',
                    'Personnaliser les couleurs dans Réglages',
                ],
            ],
            [
                'title' => '⚡ Optimisations',
                'content' => 'Améliorer les performances.',
                'steps' => [
                    'Utiliser [jlg_bloc_complet] au lieu de 3 shortcodes',
                    'Activer un plugin de cache',
                    'Optimiser les images de couverture',
                    'Limiter le nombre d\'articles affichés',
                    'Désactiver les animations si non nécessaires',
                ],
            ],
            [
                'title' => '🔧 Intégration dans le thème',
                'content' => 'Pour les développeurs.',
                'steps' => [
                    'Ajouter jlg_display_thumbnail_score() dans les templates',
                    'Utiliser jlg_get_post_rating() pour récupérer la note',
                    'Personnaliser les templates dans /templates/',
                    'Créer des hooks personnalisés',
                    'Surcharger les styles CSS du plugin',
                ],
            ],
            [
                'title' => '❓ Dépannage',
                'content' => 'Résoudre les problèmes courants.',
                'steps' => [
                    'Vérifier les conflits de plugins',
                    'Vider le cache navigateur et site',
                    'Vérifier les permissions utilisateur',
                    'Consulter les logs d\'erreur',
                    'Réinitialiser les réglages si nécessaire',
                ],
            ],
        ];

        return JLG_Template_Loader::get_admin_template('tabs/tutorials', [
            'tutorials' => $tutorials,
            'platforms_url' => admin_url('admin.php?page=' . $this->page_slug . '&tab=plateformes'),
        ]);
    }
}

