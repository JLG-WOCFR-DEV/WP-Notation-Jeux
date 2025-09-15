<?php
if (!defined('ABSPATH')) exit;
?>

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
