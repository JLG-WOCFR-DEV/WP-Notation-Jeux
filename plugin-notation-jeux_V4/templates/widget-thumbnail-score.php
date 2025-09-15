<?php
if (!defined('ABSPATH')) exit;

// Variables disponibles : $post_id
$post_id = $post_id ?? get_the_ID();
if (!$post_id) return;

$average_score = JLG_Helpers::get_average_score_for_post($post_id);
if ($average_score === null) return;

$options = JLG_Helpers::get_plugin_options();
$score_color = JLG_Helpers::calculate_color_from_note($average_score, $options);
?>

<div class="jlg-thumbnail-score" style="
    background-color: <?php echo esc_attr($score_color); ?>;
    color: <?php echo esc_attr($options['thumb_text_color'] ?? '#ffffff'); ?>;
    font-size: <?php echo intval($options['thumb_font_size'] ?? 14); ?>px;
    padding: <?php echo intval($options['thumb_padding'] ?? 8); ?>px;
    border-radius: <?php echo intval($options['thumb_border_radius'] ?? 4); ?>px;
    font-weight: bold;
    line-height: 1;
    display: inline-block;
    min-width: 35px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
    position: relative;
    z-index: 10;
">
    <?php echo esc_html(number_format($average_score, 1)); ?>
    <span style="font-size: 0.8em; opacity: 0.9;">
        <?php
        printf(
            /* translators: %s: Maximum rating value displayed with the thumbnail score. */
            esc_html__('/%s', 'notation-jlg'),
            10
        );
        ?>
    </span>
</div>
