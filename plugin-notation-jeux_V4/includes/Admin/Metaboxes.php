<?php

namespace JLG\Notation\Admin;

use JLG\Notation\Admin\Platforms;
use JLG\Notation\Helpers;
use JLG\Notation\Utils\Validator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Metaboxes {
    private $error_transient_key = '';

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'register_metaboxes' ), 10, 2 );
        add_action( 'save_post', array( $this, 'save_meta_data' ) );
        add_action( 'admin_notices', array( $this, 'display_validation_errors' ) );
    }

    private function get_allowed_post_types() {
        if ( class_exists( Helpers::class ) && method_exists( Helpers::class, 'get_allowed_post_types' ) ) {
            $post_types = Helpers::get_allowed_post_types();

            if ( is_array( $post_types ) ) {
                $post_types = array_values(
                    array_unique(
                        array_filter(
                            array_map( 'sanitize_key', $post_types )
                        )
                    )
                );

                if ( ! empty( $post_types ) ) {
                    return $post_types;
                }
            }
        }

        return array( 'post' );
    }

    private function get_error_transient_key() {
        if ( $this->error_transient_key === '' ) {
            $user_id                   = get_current_user_id();
            $this->error_transient_key = 'jlg_metabox_errors_' . (int) $user_id;
        }

        return $this->error_transient_key;
    }

    public function display_validation_errors() {
        $errors = get_transient( $this->get_error_transient_key() );

        if ( empty( $errors ) || ! is_array( $errors ) ) {
            return;
        }

        delete_transient( $this->get_error_transient_key() );

        echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Notation JLG', 'notation-jlg' ) . ' :</strong></p><ul>';
        foreach ( $errors as $error ) {
            echo '<li>' . esc_html( $error ) . '</li>';
        }
        echo '</ul></div>';
    }

    public function register_metaboxes( $post_type, $post = null ) {
        // Vérifier qu'on est bien sur un post
        $allowed_post_types = $this->get_allowed_post_types();

        if ( ! in_array( $post_type, $allowed_post_types, true ) ) {
            return;
        }

        add_meta_box(
            'notation_jlg_metabox',
            esc_html__( '⭐ Notation du Jeu Vidéo', 'notation-jlg' ),
            array( $this, 'render_notation_metabox' ),
            $post_type,
            'side',
            'high'
        );

        add_meta_box(
            'jlg_details_metabox',
            esc_html__( '🎮 Détails du Test', 'notation-jlg' ),
            array( $this, 'render_details_metabox' ),
            $post_type,
            'normal',
            'high'
        );
    }

    public function render_notation_metabox( $post ) {
        // Vérification de sécurité
        if ( ! $post ) {
            return;
        }

        $allowed_post_types = $this->get_allowed_post_types();

        if ( ! in_array( $post->post_type, $allowed_post_types, true ) ) {
            return;
        }

        wp_nonce_field( 'jlg_save_notes_data', 'jlg_notation_nonce' );

        $definitions     = Helpers::get_rating_category_definitions();
        $average_score   = Helpers::get_average_score_for_post( $post->ID );
        $score_max       = Helpers::get_score_max();
        $score_max_label = number_format_i18n( $score_max );

        echo '<div class="jlg-metabox-notation">';
        printf(
            '<p>%s</p>',
            sprintf(
                esc_html__( 'Entrez les notes sur %s. Laissez vide si non pertinent.', 'notation-jlg' ),
                esc_html( $score_max_label )
            )
        );

        foreach ( $definitions as $definition ) {
            $label    = isset( $definition['label'] ) ? $definition['label'] : '';
            $meta_key = isset( $definition['meta_key'] ) ? $definition['meta_key'] : '';

            if ( $meta_key === '' ) {
                continue;
            }

            $value = Helpers::resolve_category_meta_value( $post->ID, $definition, true );
            $value = $value !== null ? number_format( (float) $value, 1, '.', '' ) : '';

            echo '<div style="margin-bottom:10px;">';
            echo '<label><strong>' . esc_html( $label ) . ' :</strong></label><br>';
            echo '<input type="number" step="0.1" min="0" max="' . esc_attr( $score_max ) . '" name="' . esc_attr( $meta_key ) . '" value="' . esc_attr( $value ) . '" style="width:80px;" /> ';
            printf(
                '%s',
                sprintf(
                    /* translators: %s: Maximum possible rating value. */
                    esc_html_x( '/ %s', 'score input suffix', 'notation-jlg' ),
                    esc_html( $score_max_label )
                )
            );
            echo '</div>';
        }

        if ( $average_score !== null ) {
            echo '<div style="background:#f0f6fc; padding:10px; margin-top:15px; border-radius:4px;">';
            $average_display = sprintf(
                /* translators: 1: Average score value. 2: Maximum possible rating value. */
                __( '%1$s / %2$s', 'notation-jlg' ),
                number_format_i18n( $average_score, 1 ),
                $score_max_label
            );
            echo '<strong>' . esc_html__( 'Note moyenne :', 'notation-jlg' ) . '</strong> <span style="color:#0073aa; font-size:16px;">' . esc_html( $average_display ) . '</span>';
            echo '</div>';
        }

        echo '</div>';
    }

    public function render_details_metabox( $post ) {
        // Vérification de sécurité
        if ( ! $post ) {
            return;
        }

        $allowed_post_types = $this->get_allowed_post_types();

        if ( ! in_array( $post->post_type, $allowed_post_types, true ) ) {
            return;
        }

        wp_nonce_field( 'jlg_save_details_data', 'jlg_details_nonce' );

        // Récupérer les métadonnées
        $meta = array();
        $keys = array( 'game_title', 'tagline_fr', 'tagline_en', 'points_forts', 'points_faibles', 'developpeur', 'editeur', 'date_sortie', 'version', 'pegi', 'temps_de_jeu', 'plateformes', 'cover_image_url', 'cta_label', 'cta_url', 'review_video_url', 'review_video_provider' );
        foreach ( $keys as $key ) {
            $meta[ $key ] = get_post_meta( $post->ID, '_jlg_' . $key, true );
        }

        echo '<div class="jlg-metabox-details">';

        echo '<div style="margin-bottom:20px;">';
        echo '<label for="jlg_game_title"><strong>' . esc_html__( 'Nom du jeu', 'notation-jlg' ) . ' :</strong></label><br>';
        echo '<input type="text" id="jlg_game_title" name="jlg_game_title" value="' . esc_attr( $meta['game_title'] ?? '' ) . '" style="width:100%;">';
        echo '<p class="description" style="margin:5px 0 0;">' . esc_html__( 'Cette valeur est utilisée dans les tableaux, widgets et données structurées lorsque renseignée.', 'notation-jlg' ) . '</p>';
        echo '</div>';

        // Fiche technique
        echo '<h3>' . esc_html__( '📋 Fiche Technique', 'notation-jlg' ) . '</h3>';
        echo '<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:15px; margin-bottom:20px;">';

        $fields = array(
            'developpeur'     => __( 'Développeur(s)', 'notation-jlg' ),
            'editeur'         => __( 'Éditeur(s)', 'notation-jlg' ),
            'date_sortie'     => __( 'Date de sortie', 'notation-jlg' ),
            'version'         => __( 'Version testée', 'notation-jlg' ),
            'pegi'            => __( 'PEGI', 'notation-jlg' ),
            'temps_de_jeu'    => __( 'Temps de jeu', 'notation-jlg' ),
            'cover_image_url' => __( 'URL de la jaquette', 'notation-jlg' ),
        );

        foreach ( $fields as $key => $label ) {
            $type = ( $key === 'date_sortie' ) ? 'date' : 'text';
            echo '<div>';
            echo '<label><strong>' . esc_html( $label ) . ' :</strong></label><br>';
            $id_attribute = ( $key === 'cover_image_url' ) ? ' id="jlg_cover_image_url"' : '';
            echo '<input type="' . $type . '" name="jlg_' . esc_attr( $key ) . '"' . $id_attribute . ' value="' . esc_attr( $meta[ $key ] ?? '' ) . '" style="width:100%;">';
            echo '</div>';
        }

        echo '</div>';

        // Plateformes
        echo '<div style="margin-bottom:20px;">';
        echo '<p><strong>' . esc_html__( 'Plateformes :', 'notation-jlg' ) . '</strong></p>';

        // Récupérer les plateformes depuis la classe Platforms
        $platforms_list = array(
            'PC'              => __( 'PC', 'notation-jlg' ),
            'PlayStation 5'   => __( 'PlayStation 5', 'notation-jlg' ),
            'Xbox Series S/X' => __( 'Xbox Series S/X', 'notation-jlg' ),
            'Nintendo Switch' => __( 'Nintendo Switch', 'notation-jlg' ),
            'PlayStation 4'   => __( 'PlayStation 4', 'notation-jlg' ),
            'Xbox One'        => __( 'Xbox One', 'notation-jlg' ),
        );

        if ( class_exists( Platforms::class ) ) {
            $platforms_manager = Platforms::get_instance();
            $all_platforms     = $platforms_manager->get_platform_names();
            if ( ! empty( $all_platforms ) ) {
                $platforms_list = array_values( $all_platforms );
            }
        }

        $selected = is_array( $meta['plateformes'] ) ? $meta['plateformes'] : array();

        foreach ( $platforms_list as $platform_value => $platform_label ) {
            if ( is_int( $platform_value ) ) {
                $platform_value = $platform_label;
            }

            $display_label = $platform_label;
            $checked       = in_array( $platform_value, $selected ) ? 'checked' : '';
            echo '<label style="margin-right:15px;">';
            echo '<input type="checkbox" name="jlg_plateformes[]" value="' . esc_attr( $platform_value ) . '" ' . $checked . '> ';
            echo esc_html( $display_label );
            echo '</label>';
        }
        echo '</div>';

        // Taglines
        echo '<h3>' . esc_html__( '💬 Taglines & CTA', 'notation-jlg' ) . '</h3>';
        echo '<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">';
        echo '<div>';
        echo '<label><strong>' . esc_html__( 'Française :', 'notation-jlg' ) . '</strong></label><br>';
        echo '<textarea name="jlg_tagline_fr" rows="3" style="width:100%;">' . esc_textarea( $meta['tagline_fr'] ?? '' ) . '</textarea>';
        echo '</div>';
        echo '<div>';
        echo '<label><strong>' . esc_html__( 'Anglaise :', 'notation-jlg' ) . '</strong></label><br>';
        echo '<textarea name="jlg_tagline_en" rows="3" style="width:100%;">' . esc_textarea( $meta['tagline_en'] ?? '' ) . '</textarea>';
        echo '</div>';
        echo '<div style="grid-column: 1 / -1; display:grid; grid-template-columns:1fr 1fr; gap:15px;">';
        echo '<div>';
        echo '<label for="jlg_cta_label"><strong>' . esc_html__( 'Texte du bouton CTA', 'notation-jlg' ) . ' :</strong></label><br>';
        echo '<input type="text" id="jlg_cta_label" name="jlg_cta_label" value="' . esc_attr( $meta['cta_label'] ?? '' ) . '" style="width:100%;" placeholder="' . esc_attr__( 'Découvrir le jeu', 'notation-jlg' ) . '">';
        echo '<p class="description" style="margin:5px 0 0;">' . esc_html__( 'Obligatoire si vous renseignez une URL.', 'notation-jlg' ) . '</p>';
        echo '</div>';
        echo '<div>';
        echo '<label for="jlg_cta_url"><strong>' . esc_html__( 'URL du bouton CTA', 'notation-jlg' ) . ' :</strong></label><br>';
        echo '<input type="url" id="jlg_cta_url" name="jlg_cta_url" value="' . esc_attr( $meta['cta_url'] ?? '' ) . '" style="width:100%;" placeholder="https://">';
        echo '<p class="description" style="margin:5px 0 0;">' . esc_html__( 'Renseignez une URL absolue (https://...).', 'notation-jlg' ) . '</p>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<h3>' . esc_html__( '🎬 Vidéo du test', 'notation-jlg' ) . '</h3>';
        echo '<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:20px;">';
        echo '<div>';
        echo '<label for="jlg_review_video_url"><strong>' . esc_html__( 'URL de la vidéo', 'notation-jlg' ) . ' :</strong></label><br>';
        echo '<input type="url" id="jlg_review_video_url" name="jlg_review_video_url" value="' . esc_attr( $meta['review_video_url'] ?? '' ) . '" style="width:100%;" placeholder="https://">';
        echo '<p class="description" style="margin:5px 0 0;">' . esc_html__( 'YouTube et Vimeo sont pris en charge. Le mode sans cookie sera appliqué automatiquement quand possible.', 'notation-jlg' ) . '</p>';
        echo '</div>';

        $provider_value   = isset( $meta['review_video_provider'] ) ? (string) $meta['review_video_provider'] : '';
        $provider_value   = Validator::sanitize_video_provider( $provider_value );
        $provider_options = Validator::get_allowed_video_providers();

        echo '<div>';
        echo '<label for="jlg_review_video_provider"><strong>' . esc_html__( 'Fournisseur', 'notation-jlg' ) . ' :</strong></label><br>';
        echo '<select id="jlg_review_video_provider" name="jlg_review_video_provider" style="width:100%;">';
        echo '<option value="">' . esc_html__( 'Détection automatique', 'notation-jlg' ) . '</option>';
        foreach ( $provider_options as $option ) {
            $option_value = Validator::sanitize_video_provider( $option );
            $label        = Validator::get_video_provider_label( $option_value );

            if ( $option_value === '' || $label === '' ) {
                continue;
            }

            $selected = selected( $provider_value, $option_value, false );
            echo '<option value="' . esc_attr( $option_value ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo '<p class="description" style="margin:5px 0 0;">' . esc_html__( 'Sélectionnez le fournisseur si vous devez forcer la détection.', 'notation-jlg' ) . '</p>';
        echo '</div>';
        echo '</div>';

        // Points forts/faibles
        echo '<h3>' . esc_html__( '⚖️ Points Forts & Faibles', 'notation-jlg' ) . '</h3>';
        echo '<p style="font-style:italic;">' . esc_html__( 'Un point par ligne', 'notation-jlg' ) . '</p>';
        echo '<div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">';
        echo '<div>';
        echo '<label><strong>' . esc_html__( 'Points Forts :', 'notation-jlg' ) . '</strong></label><br>';
        echo '<textarea name="jlg_points_forts" rows="8" style="width:100%;" placeholder="' . esc_attr__( "Gameplay addictif\nGraphismes superbes", 'notation-jlg' ) . '">' . esc_textarea( $meta['points_forts'] ?? '' ) . '</textarea>';
        echo '</div>';
        echo '<div>';
        echo '<label><strong>' . esc_html__( 'Points Faibles :', 'notation-jlg' ) . '</strong></label><br>';
        echo '<textarea name="jlg_points_faibles" rows="8" style="width:100%;" placeholder="' . esc_attr__( "Durée de vie courte\nBugs occasionnels", 'notation-jlg' ) . '">' . esc_textarea( $meta['points_faibles'] ?? '' ) . '</textarea>';
        echo '</div>';
        echo '</div>';

        echo '</div>';
    }

    public function save_meta_data( $post_id ) {
        // Vérifications de base
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Vérifier le type de post
        $allowed_post_types = $this->get_allowed_post_types();
        $post_type          = get_post_type( $post_id );

        if ( ! in_array( $post_type, $allowed_post_types, true ) ) {
            return;
        }

        // Sauvegarder les notes
        if ( isset( $_POST['jlg_notation_nonce'] ) && wp_verify_nonce( $_POST['jlg_notation_nonce'], 'jlg_save_notes_data' ) ) {
            $definitions    = Helpers::get_rating_category_definitions();
            $score_max      = Helpers::get_score_max();
            $scores_changed = false;

            foreach ( $definitions as $definition ) {
                $meta_key = isset( $definition['meta_key'] ) ? $definition['meta_key'] : '';

                if ( $meta_key === '' || ! isset( $_POST[ $meta_key ] ) ) {
                    continue;
                }

                $raw_value = wp_unslash( $_POST[ $meta_key ] );
                if ( is_string( $raw_value ) ) {
                    $raw_value = str_replace( ',', '.', $raw_value );
                }

                $value = sanitize_text_field( (string) $raw_value );

                if ( $value === '' ) {
                    $deleted        = delete_post_meta( $post_id, $meta_key );
                    $scores_changed = $scores_changed || (bool) $deleted;
                    continue;
                }

                if ( is_numeric( $value ) ) {
                    $numeric_value = round( floatval( $value ), 1 );

                    if ( $numeric_value >= 0 && $numeric_value <= $score_max ) {
                        $updated        = update_post_meta( $post_id, $meta_key, $numeric_value );
                        $scores_changed = $scores_changed || (bool) $updated;
                    }
                }
            }

            // Recalculer la moyenne si la classe Helpers existe
            if ( class_exists( Helpers::class ) ) {
                $average = Helpers::get_average_score_for_post( $post_id );
                if ( $average !== null ) {
                    update_post_meta( $post_id, '_jlg_average_score', $average );
                } else {
                    delete_post_meta( $post_id, '_jlg_average_score' );
                }

                if ( $scores_changed ) {
                    Helpers::clear_rated_post_ids_cache();
                }
            }
        }

        $validation_errors = array();

        // Sauvegarder les détails
        if ( isset( $_POST['jlg_details_nonce'] ) && wp_verify_nonce( $_POST['jlg_details_nonce'], 'jlg_save_details_data' ) ) {
            // Champs texte simples
            $text_fields = array(
                'game_title'   => __( 'Nom du jeu', 'notation-jlg' ),
                'developpeur'  => __( 'Développeur(s)', 'notation-jlg' ),
                'editeur'      => __( 'Éditeur(s)', 'notation-jlg' ),
                'date_sortie'  => __( 'Date de sortie', 'notation-jlg' ),
                'version'      => __( 'Version testée', 'notation-jlg' ),
                'pegi'         => __( 'PEGI', 'notation-jlg' ),
                'temps_de_jeu' => __( 'Temps de jeu', 'notation-jlg' ),
            );
            foreach ( $text_fields as $field => $label ) {
                if ( isset( $_POST[ 'jlg_' . $field ] ) ) {
                    $raw_value = wp_unslash( $_POST[ 'jlg_' . $field ] );
                    $value     = sanitize_text_field( $raw_value );
                    if ( $field === 'game_title' && $value !== '' ) {
                        if ( function_exists( 'mb_substr' ) ) {
                            $value = mb_substr( $value, 0, 150 );
                        } else {
                            $value = substr( $value, 0, 150 );
                        }
                    }
                    if ( $value === '' ) {
                        delete_post_meta( $post_id, '_jlg_' . $field );
                        continue;
                    }

                    if ( $field === 'date_sortie' ) {
                        $sanitized_date = Validator::sanitize_date( $value );
                        if ( $sanitized_date === null ) {
                            delete_post_meta( $post_id, '_jlg_' . $field );
                            $validation_errors[] = sprintf(
                                /* translators: %s is the field label. */
                                __( '%s : format de date invalide. Utilisez AAAA-MM-JJ.', 'notation-jlg' ),
                                $label
                            );
                            continue;
                        }

                        update_post_meta( $post_id, '_jlg_' . $field, $sanitized_date );
                        continue;
                    }

                    if ( $field === 'pegi' ) {
                        if ( ! Validator::validate_pegi( $value, false ) ) {
                            delete_post_meta( $post_id, '_jlg_' . $field );
                            $validation_errors[] = sprintf(
                                /* translators: %s is a list of allowed PEGI values */
                                __( 'PEGI invalide. Valeurs acceptées : %s.', 'notation-jlg' ),
                                implode(
                                    ', ',
                                    array_map(
                                        function ( $rating ) {
                                            return 'PEGI ' . $rating;
                                        },
                                        Validator::get_allowed_pegi_values()
                                    )
                                )
                            );
                            continue;
                        }

                        $sanitized_pegi = Validator::sanitize_pegi( $value );
                        update_post_meta( $post_id, '_jlg_' . $field, $sanitized_pegi );
                        continue;
                    }

                    update_post_meta( $post_id, '_jlg_' . $field, $value );
                }
            }

            if ( isset( $_POST['jlg_cover_image_url'] ) ) {
                $cover_image_url = esc_url_raw( wp_unslash( $_POST['jlg_cover_image_url'] ) );
                if ( ! empty( $cover_image_url ) ) {
                    update_post_meta( $post_id, '_jlg_cover_image_url', $cover_image_url );
                } else {
                    delete_post_meta( $post_id, '_jlg_cover_image_url' );
                }
            }

            // Champs textarea
            $textarea_fields = array( 'tagline_fr', 'tagline_en', 'points_forts', 'points_faibles' );
            foreach ( $textarea_fields as $field ) {
                if ( isset( $_POST[ 'jlg_' . $field ] ) ) {
                    $raw_value = wp_unslash( $_POST[ 'jlg_' . $field ] );
                    $value     = sanitize_textarea_field( $raw_value );
                    if ( ! empty( $value ) ) {
                        update_post_meta( $post_id, '_jlg_' . $field, $value );
                    } else {
                        delete_post_meta( $post_id, '_jlg_' . $field );
                    }
                }
            }

            $cta_label = isset( $_POST['jlg_cta_label'] ) ? sanitize_text_field( wp_unslash( $_POST['jlg_cta_label'] ) ) : '';
            $cta_label = is_string( $cta_label ) ? trim( $cta_label ) : '';
            $cta_url   = isset( $_POST['jlg_cta_url'] ) ? esc_url_raw( wp_unslash( $_POST['jlg_cta_url'] ) ) : '';
            $cta_url   = is_string( $cta_url ) ? trim( $cta_url ) : '';

            if ( $cta_label === '' && $cta_url === '' ) {
                delete_post_meta( $post_id, '_jlg_cta_label' );
                delete_post_meta( $post_id, '_jlg_cta_url' );
            } elseif ( $cta_label === '' || $cta_url === '' ) {
                delete_post_meta( $post_id, '_jlg_cta_label' );
                delete_post_meta( $post_id, '_jlg_cta_url' );

                $validation_errors[] = __( 'Bouton CTA : le texte et l\'URL doivent être renseignés ensemble.', 'notation-jlg' );
            } else {
                if ( ! Validator::is_valid_http_url( $cta_url ) ) {
                    delete_post_meta( $post_id, '_jlg_cta_label' );
                    delete_post_meta( $post_id, '_jlg_cta_url' );

                    $validation_errors[] = __( 'Bouton CTA : l\'URL doit être absolue et commencer par http ou https.', 'notation-jlg' );
                } else {
                    update_post_meta( $post_id, '_jlg_cta_label', $cta_label );
                    update_post_meta( $post_id, '_jlg_cta_url', esc_url_raw( $cta_url ) );
                }
            }

            $video_url_input      = isset( $_POST['jlg_review_video_url'] ) ? wp_unslash( $_POST['jlg_review_video_url'] ) : '';
            $video_provider_input = isset( $_POST['jlg_review_video_provider'] ) ? wp_unslash( $_POST['jlg_review_video_provider'] ) : '';
            $video_data           = Validator::sanitize_review_video_data( $video_url_input, $video_provider_input );

            if ( isset( $video_data['error'] ) && $video_data['error'] !== null ) {
                delete_post_meta( $post_id, '_jlg_review_video_url' );
                delete_post_meta( $post_id, '_jlg_review_video_provider' );

                $validation_errors[] = $video_data['error'];
            } else {
                $sanitized_video_url = isset( $video_data['url'] ) ? (string) $video_data['url'] : '';
                $sanitized_provider  = isset( $video_data['provider'] ) ? (string) $video_data['provider'] : '';

                if ( $sanitized_video_url === '' ) {
                    delete_post_meta( $post_id, '_jlg_review_video_url' );
                    delete_post_meta( $post_id, '_jlg_review_video_provider' );
                } else {
                    update_post_meta( $post_id, '_jlg_review_video_url', $sanitized_video_url );

                    if ( $sanitized_provider !== '' ) {
                        update_post_meta( $post_id, '_jlg_review_video_provider', $sanitized_provider );
                    } else {
                        delete_post_meta( $post_id, '_jlg_review_video_provider' );
                    }
                }
            }

            // Plateformes (checkboxes)
            if ( isset( $_POST['jlg_plateformes'] ) && is_array( $_POST['jlg_plateformes'] ) ) {
                $raw_platforms = wp_unslash( $_POST['jlg_plateformes'] );
                $raw_platforms = is_array( $raw_platforms ) ? $raw_platforms : array();
                $platforms     = Validator::sanitize_platforms( $raw_platforms );
                update_post_meta( $post_id, '_jlg_plateformes', $platforms );
            } else {
                delete_post_meta( $post_id, '_jlg_plateformes' );
            }
        }

        if ( ! empty( $validation_errors ) ) {
            set_transient( $this->get_error_transient_key(), $validation_errors, MINUTE_IN_SECONDS );
        }
    }
}
