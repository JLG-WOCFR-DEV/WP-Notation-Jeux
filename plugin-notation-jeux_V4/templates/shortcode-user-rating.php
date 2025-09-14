<?php
if (!defined('ABSPATH')) exit;

$palette = JLG_Helpers::get_color_palette();
$options = JLG_Helpers::get_plugin_options();
?>
<style>
    .jlg-user-rating-block {
        max-width: 650px;
        margin: 32px auto;
        text-align: center;
        color: <?php echo esc_attr($options['user_rating_text_color']); ?>;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    .jlg-user-rating-block.is-loading {
        opacity: 0.6;
        cursor: wait;
        pointer-events: none;
    }
    .jlg-user-rating-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: <?php echo esc_attr($palette['main_text_color']); ?>;
        margin-bottom: 12px;
    }
    .jlg-user-rating-stars {
        display: inline-flex;
        gap: 5px;
        cursor: pointer;
        margin-bottom: 12px;
    }
    .jlg-user-rating-block.has-voted .jlg-user-rating-stars {
        cursor: default;
    }
    .jlg-user-star {
        font-size: 28px;
        color: #52525b;
        transition: color 0.2s, transform 0.2s;
    }
    .jlg-user-rating-block.has-voted .jlg-user-star:hover,
    .jlg-user-rating-block.has-voted .jlg-user-star.hover {
        color: #52525b;
        transform: none;
    }
    .jlg-user-star:hover,
    .jlg-user-star.hover {
        color: <?php echo esc_attr($options['user_rating_star_color']); ?>;
        transform: scale(1.15);
    }
    .jlg-user-star.selected {
        color: <?php echo esc_attr($options['user_rating_star_color']); ?>;
    }
    .jlg-user-rating-summary {
        font-size: 0.9rem;
    }
    .jlg-rating-message {
        margin-top: 10px;
        color: <?php echo esc_attr($options['color_high']); ?>;
        font-weight: 500;
        min-height: 20px;
    }
</style>
<div class="jlg-user-rating-block <?php if ($has_voted) echo 'has-voted'; ?>">
    <div class="jlg-user-rating-title">Votre avis nous intéresse !</div>
    <div class="jlg-user-rating-stars" data-post-id="<?php echo esc_attr($post_id); ?>">
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <span class="jlg-user-star <?php if($has_voted && $i <= $user_vote) echo 'selected'; ?>" data-value="<?php echo $i; ?>">★</span>
        <?php endfor; ?>
    </div>
    <div class="jlg-user-rating-summary">
        Note moyenne : <strong class="jlg-user-rating-avg-value"><?php echo !empty($avg_rating) ? number_format(floatval($avg_rating), 2) : 'N/A'; ?></strong> 
        sur 5 (<span class="jlg-user-rating-count-value"><?php echo !empty($count) ? intval($count) : 0; ?></span> votes)
    </div>
    <div class="jlg-rating-message"><?php if($has_voted) echo 'Merci pour votre vote !'; ?></div>
</div>