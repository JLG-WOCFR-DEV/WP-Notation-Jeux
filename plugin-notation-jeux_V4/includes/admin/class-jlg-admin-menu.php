<?php
/**
 * Gestion du menu admin et des pages
 *
 * @package JLG_Notation
 * @version 5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class JLG_Admin_Menu {
    private $page_slug = 'notation_jlg_settings';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
    }

    public function add_admin_menu() {
        add_menu_page(
            _x( 'Notation JLG', 'Admin page title', 'notation-jlg' ),
            _x( 'Notation - JLG', 'Admin menu title', 'notation-jlg' ),
            'manage_options',
            $this->page_slug,
            array( $this, 'render_admin_page' ),
            'dashicons-star-filled',
            30
        );
    }

    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Accès refusé.', 'notation-jlg' ) );
        }

        $tabs       = $this->get_tabs();
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'reglages';
        if ( ! array_key_exists( $active_tab, $tabs ) ) {
            $active_tab = 'reglages';
        }

        $tab_navigation = $this->get_tab_navigation_html( $tabs, $active_tab );
        $tab_content    = $this->get_tab_content( $active_tab );

        JLG_Template_Loader::display_admin_template(
            'admin-page',
            array(
				'page_title'     => __( '⭐ Notation JLG v5.0', 'notation-jlg' ),
				'tab_navigation' => $tab_navigation,
				'tab_content'    => $tab_content,
			)
        );
    }

    private function get_tabs() {
        return array(
            'reglages'       => __( '⚙️ Réglages', 'notation-jlg' ),
            'articles_notes' => __( '📊 Articles Notés', 'notation-jlg' ),
            'plateformes'    => __( '🎮 Plateformes', 'notation-jlg' ),
            'shortcodes'     => __( '📝 Shortcodes', 'notation-jlg' ),
            'tutoriels'      => __( '📚 Tutoriels', 'notation-jlg' ),
        );
    }

    private function get_tab_navigation_html( array $tabs, $active_tab ) {
        return JLG_Template_Loader::get_admin_template(
            'partials/tab-navigation',
            array(
				'tabs'       => $tabs,
				'active_tab' => $active_tab,
				'page_slug'  => $this->page_slug,
			)
        );
    }

    private function get_tab_content( $active_tab ) {
        switch ( $active_tab ) {
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
        return JLG_Template_Loader::get_admin_template(
            'tabs/settings',
            array(
				'settings_page' => 'notation_jlg_page',
			)
        );
    }

    private function get_posts_list_tab_content() {
        $rated_posts = array_values( array_unique( array_filter( array_map( 'intval', JLG_Helpers::get_rated_post_ids() ) ) ) );
        $empty_state = array(
            'create_post_url' => admin_url( 'post-new.php' ),
        );

        if ( empty( $rated_posts ) ) {
            return JLG_Template_Loader::get_admin_template(
                'tabs/posts-list',
                array(
					'has_rated_posts' => false,
					'empty_state'     => $empty_state,
				)
            );
        }

        $per_page     = 30;
        $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $orderby      = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'date';
        $order        = isset( $_GET['order'] ) && in_array( strtoupper( $_GET['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $_GET['order'] ) : 'DESC';

        $args = array(
            'post_type'      => JLG_Helpers::get_allowed_post_types(),
            'post__in'       => $rated_posts,
            'posts_per_page' => $per_page,
            'paged'          => $current_page,
            'orderby'        => $orderby === 'score' ? 'meta_value_num' : $orderby,
            'order'          => $order,
        );

        if ( $orderby === 'score' ) {
            $args['meta_key'] = '_jlg_average_score';
        }

        $query = new WP_Query( $args );

        $posts = array();
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id     = get_the_ID();
                $score_data  = JLG_Helpers::get_resolved_average_score( $post_id );
                $score_value = $score_data['value'];

                $score_color = '#0073aa';
                if ( $score_value !== null ) {
                    if ( $score_value >= 8 ) {
                        $score_color = '#22c55e';
                    } elseif ( $score_value >= 5 ) {
                        $score_color = '#f97316';
                    } else {
                        $score_color = '#ef4444';
                    }
                }

                $categories = get_the_category( $post_id );
                $cat_names  = array_map(
                    function ( $cat ) {
						return $cat->name;
					},
                    $categories
                );

                $posts[] = array(
                    'title'         => JLG_Helpers::get_game_title( $post_id ),
                    'edit_link'     => get_edit_post_link( $post_id ),
                    'view_link'     => get_permalink( $post_id ),
                    'date'          => get_the_date( 'd/m/Y', $post_id ),
                    'score_display' => $score_data['formatted'] ?? __( 'N/A', 'notation-jlg' ),
                    'score_color'   => $score_color,
                    'categories'    => $cat_names,
                );
            }
        }

        wp_reset_postdata();

        $total_items = count( $rated_posts );
        $total_pages = (int) ceil( $total_items / $per_page );
        $pagination  = '';

        if ( $total_pages > 1 ) {
            $pagination_args = array(
                'base'               => add_query_arg( 'paged', '%#%' ),
                'format'             => '',
                'prev_text'          => '&laquo;',
                'next_text'          => '&raquo;',
                'total'              => $total_pages,
                'current'            => $current_page,
                'show_all'           => false,
                'end_size'           => 1,
                'mid_size'           => 2,
                'type'               => 'plain',
                'before_page_number' => '<span class="screen-reader-text">' . esc_html__( 'Page ', 'notation-jlg' ) . '</span>',
            );

            if ( isset( $_GET['orderby'] ) ) {
                $pagination_args['add_args'] = array(
                    'orderby' => $orderby,
                    'order'   => $order,
                );
            }

            $pagination = paginate_links( $pagination_args );
        }

        return JLG_Template_Loader::get_admin_template(
            'tabs/posts-list',
            array(
				'has_rated_posts'    => true,
				'empty_state'        => $empty_state,
				'stats'              => array(
					'total_items'   => $total_items,
					'current_page'  => $current_page,
					'total_pages'   => $total_pages,
					'display_count' => count( $posts ),
				),
				'columns'            => $this->get_sortable_columns( $orderby, $order ),
				'posts'              => $posts,
				'pagination'         => $pagination,
				'print_button_label' => __( '🖨️ Imprimer cette liste', 'notation-jlg' ),
			)
        );
    }

    private function get_sortable_columns( $current_orderby, $current_order ) {
        $columns = array(
            array(
				'label' => __( 'Titre', 'notation-jlg' ),
				'key'   => 'title',
			),
            array(
				'label' => __( 'Date', 'notation-jlg' ),
				'key'   => 'date',
			),
            array(
				'label' => __( 'Note', 'notation-jlg' ),
				'key'   => 'score',
			),
        );

        $results = array();
        foreach ( $columns as $column ) {
            $column_key = $column['key'];
            $new_order  = ( $current_orderby === $column_key && $current_order === 'ASC' ) ? 'DESC' : 'ASC';

            $class     = 'manage-column column-' . $column_key;
            $aria_sort = 'none';
            if ( $current_orderby === $column_key ) {
                $class    .= ' sorted ' . strtolower( $current_order );
                $aria_sort = ( $current_order === 'ASC' ) ? 'ascending' : 'descending';
            } else {
                $class .= ' sortable desc';
            }

            $url = add_query_arg(
                array(
					'page'    => $this->page_slug,
					'tab'     => 'articles_notes',
					'orderby' => $column_key,
					'order'   => $new_order,
					'paged'   => 1,
                ),
                admin_url( 'admin.php' )
            );

            $results[] = array(
                'label'     => $column['label'],
                'class'     => $class,
                'url'       => $url,
                'aria_sort' => $aria_sort,
            );
        }

        return $results;
    }

    private function get_platforms_tab_content() {
        ob_start();
        echo '<h2 class="title">' . esc_html__( '🎮 Gestion des Plateformes', 'notation-jlg' ) . '</h2>';
        $this->render_platforms_tab();
        return ob_get_clean();
    }

    private function render_platforms_tab() {
        // Utiliser l'instance singleton de la classe Platforms
        if ( class_exists( 'JLG_Admin_Platforms' ) ) {
            $platforms_manager = JLG_Admin_Platforms::get_instance();
            $platforms_manager->render_platforms_page();
        } else {
            // Si la classe n'existe pas, essayer de la charger
            $path = JLG_NOTATION_PLUGIN_DIR . 'includes/admin/class-jlg-admin-platforms.php';
            if ( file_exists( $path ) ) {
                require_once $path;
                if ( class_exists( 'JLG_Admin_Platforms' ) ) {
                    $platforms_manager = JLG_Admin_Platforms::get_instance();
                    $platforms_manager->render_platforms_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'La classe de gestion des plateformes n\'a pas pu être chargée.', 'notation-jlg' ) . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Fichier class-jlg-admin-platforms.php introuvable.', 'notation-jlg' ) . '</p></div>';
            }
        }
    }

    private function get_shortcodes_tab_content() {
        return JLG_Template_Loader::get_admin_template( 'tabs/shortcodes' );
    }

    private function get_tutorials_tab_content() {
        $tutorials = array(
            array(
                'title'   => __( '⚡ Démarrage rapide avec le Bloc Complet', 'notation-jlg' ),
                'content' => __( 'La méthode la plus simple pour créer un test professionnel.', 'notation-jlg' ),
                'steps'   => array(
                    __( 'Créer un nouvel article', 'notation-jlg' ),
                    __( 'Remplir les notes dans la metabox (colonne droite)', 'notation-jlg' ),
                    __( 'Ajouter tagline et points forts/faibles', 'notation-jlg' ),
                    __( 'Insérer [jlg_bloc_complet] dans le contenu', 'notation-jlg' ),
                    __( 'C\'est tout ! Publiez votre test', 'notation-jlg' ),
                ),
            ),
            array(
                'title'   => __( '🎮 Créer un test détaillé (méthode classique)', 'notation-jlg' ),
                'content' => __( 'Guide pas-à-pas pour un contrôle total.', 'notation-jlg' ),
                'steps'   => array(
                    __( 'Créer un nouvel article', 'notation-jlg' ),
                    __( 'Remplir la metabox "Notation" (colonne droite)', 'notation-jlg' ),
                    __( 'Ajouter les détails du jeu (metabox principale)', 'notation-jlg' ),
                    __( 'Intégrer les shortcodes séparés si besoin', 'notation-jlg' ),
                    __( 'Publier et vérifier l\'affichage', 'notation-jlg' ),
                ),
            ),
            array(
                'title'   => __( '🎨 Personnalisation du Bloc Complet', 'notation-jlg' ),
                'content' => __( 'Créez un rendu unique.', 'notation-jlg' ),
                'steps'   => array(
                    __( 'Choisir le style (moderne/classique/compact)', 'notation-jlg' ),
                    __( 'Définir une couleur d\'accent personnalisée', 'notation-jlg' ),
                    __( 'Activer/désactiver les sections', 'notation-jlg' ),
                    __( 'Personnaliser les titres des sections', 'notation-jlg' ),
                    __( 'Combiner avec d\'autres shortcodes si besoin', 'notation-jlg' ),
                ),
            ),
            array(
                'title'   => __( '🎨 Personnalisation visuelle globale', 'notation-jlg' ),
                'content' => __( 'Ajuster l\'apparence générale.', 'notation-jlg' ),
                'steps'   => array(
                    __( 'Choisir le thème (clair/sombre)', 'notation-jlg' ),
                    __( 'Activer les effets Neon/Glow', 'notation-jlg' ),
                    __( 'Configurer la pulsation', 'notation-jlg' ),
                    __( 'Personnaliser les couleurs', 'notation-jlg' ),
                    __( 'Ajouter du CSS personnalisé', 'notation-jlg' ),
                ),
            ),
            array(
                'title'   => __( '📊 Tableau récapitulatif avancé', 'notation-jlg' ),
                'content' => __( 'Maîtriser le shortcode tableau.', 'notation-jlg' ),
                'steps'   => array(
                    __( 'Choisir entre table et grille', 'notation-jlg' ),
                    __( 'Sélectionner les colonnes à afficher', 'notation-jlg' ),
                    __( 'Filtrer par catégorie', 'notation-jlg' ),
                    __( 'Ajuster la pagination', 'notation-jlg' ),
                    __( 'Personnaliser les couleurs dans Réglages', 'notation-jlg' ),
                ),
            ),
            array(
                'title'   => __( '⚡ Optimisations', 'notation-jlg' ),
                'content' => __( 'Améliorer les performances.', 'notation-jlg' ),
                'steps'   => array(
                    __( 'Utiliser [jlg_bloc_complet] au lieu de 3 shortcodes', 'notation-jlg' ),
                    __( 'Activer un plugin de cache', 'notation-jlg' ),
                    __( 'Optimiser les images de couverture', 'notation-jlg' ),
                    __( 'Limiter le nombre d\'articles affichés', 'notation-jlg' ),
                    __( 'Désactiver les animations si non nécessaires', 'notation-jlg' ),
                ),
            ),
            array(
                'title'   => __( '🔧 Intégration dans le thème', 'notation-jlg' ),
                'content' => __( 'Pour les développeurs.', 'notation-jlg' ),
                'steps'   => array(
                    __( 'Ajouter jlg_display_thumbnail_score() dans les templates', 'notation-jlg' ),
                    __( 'Utiliser jlg_get_post_rating() pour récupérer la note', 'notation-jlg' ),
                    __( 'Personnaliser les templates dans /templates/', 'notation-jlg' ),
                    __( 'Créer des hooks personnalisés', 'notation-jlg' ),
                    __( 'Surcharger les styles CSS du plugin', 'notation-jlg' ),
                ),
            ),
            array(
                'title'   => __( '❓ Dépannage', 'notation-jlg' ),
                'content' => __( 'Résoudre les problèmes courants.', 'notation-jlg' ),
                'steps'   => array(
                    __( 'Vérifier les conflits de plugins', 'notation-jlg' ),
                    __( 'Vider le cache navigateur et site', 'notation-jlg' ),
                    __( 'Vérifier les permissions utilisateur', 'notation-jlg' ),
                    __( 'Consulter les logs d\'erreur', 'notation-jlg' ),
                    __( 'Réinitialiser les réglages si nécessaire', 'notation-jlg' ),
                ),
            ),
        );

        return JLG_Template_Loader::get_admin_template(
            'tabs/tutorials',
            array(
				'tutorials'     => $tutorials,
				'platforms_url' => admin_url( 'admin.php?page=' . $this->page_slug . '&tab=plateformes' ),
			)
        );
    }
}
