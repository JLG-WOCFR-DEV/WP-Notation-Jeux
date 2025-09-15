<?php
if (!defined('ABSPATH')) exit;
?>

<div class="jlg-pros-cons-wrapper">
    <?php if (!empty($pros_list)): ?>
    <div class="jlg-pros-cons-col">
        <h3><span class="icon icon-pros">+</span> <?php
            /* translators: Section title for the list of advantages in the pros and cons block. */
            esc_html_e('Points Forts', 'notation-jlg');
        ?></h3>
        <ul><?php foreach ($pros_list as $pro) echo '<li>' . esc_html(trim($pro)) . '</li>'; ?></ul>
    </div>
    <?php endif; ?>
    <?php if (!empty($cons_list)): ?>
    <div class="jlg-pros-cons-col">
        <h3><span class="icon icon-cons">-</span> <?php
            /* translators: Section title for the list of drawbacks in the pros and cons block. */
            esc_html_e('Points Faibles', 'notation-jlg');
        ?></h3>
        <ul><?php foreach ($cons_list as $con) echo '<li>' . esc_html(trim($con)) . '</li>'; ?></ul>
    </div>
    <?php endif; ?>
</div>
