<?php
if (! defined('ABSPATH')) {
    exit;
}

$metrics      = isset($variables['metrics']) && is_array($variables['metrics']) ? $variables['metrics'] : array();
$rawg_status  = isset($variables['rawg_status']) && is_array($variables['rawg_status']) ? $variables['rawg_status'] : array();
$ajax_action  = isset($variables['ajax_action']) ? (string) $variables['ajax_action'] : '';
$reset_action = isset($variables['reset_action']) ? (string) $variables['reset_action'] : '';
$nonce        = isset($variables['nonce']) ? (string) $variables['nonce'] : '';
$onboarding_summary = isset($variables['onboarding_summary']) && is_array($variables['onboarding_summary']) ? $variables['onboarding_summary'] : array();
$onboarding_steps = isset($onboarding_summary['steps']) && is_array($onboarding_summary['steps']) ? $onboarding_summary['steps'] : array();
$onboarding_submission = isset($onboarding_summary['submission']) && is_array($onboarding_summary['submission']) ? $onboarding_summary['submission'] : array(
    'attempts' => 0,
    'success' => 0,
    'errors' => 0,
    'last_feedback_code' => '',
    'last_feedback_message' => '',
    'last_attempt_at' => 0,
);

$rawg_configured = ! empty($rawg_status['configured']);
$masked_key      = isset($rawg_status['masked_key']) ? (string) $rawg_status['masked_key'] : '';
?>

<div class="jlg-diagnostics">
    <section class="jlg-diagnostics__panel jlg-diagnostics__panel--rawg">
        <header class="jlg-diagnostics__header">
            <h2><?php esc_html_e('Connexion RAWG.io', 'notation-jlg'); ?></h2>
            <p class="jlg-diagnostics__subtitle">
                <?php esc_html_e('Vérifiez en un clic si la clé API RAWG configurée répond correctement.', 'notation-jlg'); ?>
            </p>
        </header>
        <div class="jlg-diagnostics__content">
            <p class="jlg-diagnostics__status">
                <?php if ($rawg_configured) : ?>
                    <span class="jlg-diagnostics__status-indicator is-configured"></span>
                    <?php
                    printf(
                        esc_html__('Clé configurée : %s', 'notation-jlg'),
                        '<code>' . esc_html($masked_key) . '</code>'
                    );
                    ?>
                <?php else : ?>
                    <span class="jlg-diagnostics__status-indicator is-missing"></span>
                    <?php esc_html_e('Aucune clé RAWG enregistrée.', 'notation-jlg'); ?>
                <?php endif; ?>
            </p>

            <div class="jlg-diagnostics__actions">
                <button
                    type="button"
                    class="button button-primary"
                    data-rawg-ping
                    data-action="<?php echo esc_attr($ajax_action); ?>"
                    data-nonce="<?php echo esc_attr($nonce); ?>"
                    data-progress-label="<?php esc_attr_e('Test en cours…', 'notation-jlg'); ?>"
                    <?php disabled(! $rawg_configured); ?>
                >
                    <?php esc_html_e('Tester la connexion', 'notation-jlg'); ?>
                </button>
                <span class="jlg-diagnostics__ping-result" aria-live="polite"></span>
            </div>
        </div>
    </section>

    <section class="jlg-diagnostics__panel jlg-diagnostics__panel--metrics">
        <header class="jlg-diagnostics__header">
            <h2><?php esc_html_e('Temps de réponse des modules', 'notation-jlg'); ?></h2>
            <p class="jlg-diagnostics__subtitle">
                <?php esc_html_e('Surveillez la disponibilité des flux critiques (Game Explorer, RAWG, votes lecteurs).', 'notation-jlg'); ?>
            </p>
        </header>

        <?php if (empty($metrics)) : ?>
            <p class="jlg-diagnostics__empty">
                <?php esc_html_e('Aucune donnée collectée pour le moment. Interagissez avec les modules pour générer des mesures.', 'notation-jlg'); ?>
            </p>
        <?php else : ?>
            <div class="jlg-diagnostics__grid">
                <?php foreach ($metrics as $channel => $data) :
                    $channel_label = $channel;
                    switch ($channel) {
                        case 'game_explorer':
                            $channel_label = __('Game Explorer', 'notation-jlg');
                            break;
                        case 'rawg_ping':
                            $channel_label = __('Tests RAWG', 'notation-jlg');
                            break;
                        case 'user_rating':
                            $channel_label = __('Votes lecteurs', 'notation-jlg');
                            break;
                    }

                    $avg_duration = isset($data['avg_duration']) ? (float) $data['avg_duration'] : 0.0;
                    $max_duration = isset($data['max_duration']) ? (float) $data['max_duration'] : 0.0;
                    $count        = isset($data['count']) ? (int) $data['count'] : 0;
                    $success      = isset($data['success']) ? (int) $data['success'] : 0;
                    $failures     = isset($data['failures']) ? (int) $data['failures'] : 0;
                    $last_status  = isset($data['last_status']) ? (string) $data['last_status'] : 'unknown';
                    $last_event   = isset($data['last_event']) && is_array($data['last_event']) ? $data['last_event'] : array();
                    ?>
                    <article class="jlg-diagnostics__card" data-channel="<?php echo esc_attr($channel); ?>">
                        <h3 class="jlg-diagnostics__card-title"><?php echo esc_html($channel_label); ?></h3>
                        <dl class="jlg-diagnostics__stats">
                            <div>
                                <dt><?php esc_html_e('Requêtes', 'notation-jlg'); ?></dt>
                                <dd><?php echo esc_html(number_format_i18n($count)); ?></dd>
                            </div>
                            <div>
                                <dt><?php esc_html_e('Réussites', 'notation-jlg'); ?></dt>
                                <dd><?php echo esc_html(number_format_i18n($success)); ?></dd>
                            </div>
                            <div>
                                <dt><?php esc_html_e('Échecs', 'notation-jlg'); ?></dt>
                                <dd><?php echo esc_html(number_format_i18n($failures)); ?></dd>
                            </div>
                            <div>
                                <dt><?php esc_html_e('Temps moyen', 'notation-jlg'); ?></dt>
                                <dd><?php echo esc_html(number_format_i18n($avg_duration, 3)); ?>s</dd>
                            </div>
                            <div>
                                <dt><?php esc_html_e('Pic', 'notation-jlg'); ?></dt>
                                <dd><?php echo esc_html(number_format_i18n($max_duration, 3)); ?>s</dd>
                            </div>
                        </dl>
                        <?php if (! empty($last_event)) :
                            $last_time = isset($last_event['timestamp']) ? (int) $last_event['timestamp'] : 0;
                            ?>
                            <p class="jlg-diagnostics__last">
                                <strong><?php esc_html_e('Dernier statut :', 'notation-jlg'); ?></strong>
                                <span class="jlg-diagnostics__status-pill <?php echo $last_status === 'success' ? 'is-success' : 'is-error'; ?>">
                                    <?php echo esc_html($last_status === 'success' ? __('OK', 'notation-jlg') : __('Erreur', 'notation-jlg')); ?>
                                </span>
                                <?php if ($last_time > 0) : ?>
                                    <span class="jlg-diagnostics__timestamp"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_time)); ?></span>
                                <?php endif; ?>
                            </p>
                            <?php if (! empty($data['last_error'])) : ?>
                                <p class="jlg-diagnostics__error"><?php echo esc_html($data['last_error']); ?></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="jlg-diagnostics__reset">
            <?php if ($reset_action !== '') : ?>
                <input type="hidden" name="action" value="<?php echo esc_attr($reset_action); ?>" />
            <?php endif; ?>
            <?php wp_nonce_field($reset_action); ?>
            <button type="submit" class="button button-secondary">
                <?php esc_html_e('Réinitialiser les métriques', 'notation-jlg'); ?>
            </button>
        </form>
    </section>

    <section class="jlg-diagnostics__panel jlg-diagnostics__panel--onboarding">
        <header class="jlg-diagnostics__header">
            <h2><?php esc_html_e('Assistant de démarrage', 'notation-jlg'); ?></h2>
            <p class="jlg-diagnostics__subtitle">
                <?php esc_html_e('Analysez la progression des étapes de configuration et les validations récentes.', 'notation-jlg'); ?>
            </p>
        </header>

        <?php if (empty($onboarding_steps)) : ?>
            <p class="jlg-diagnostics__empty">
                <?php esc_html_e('Aucun parcours de configuration n’a encore été enregistré.', 'notation-jlg'); ?>
            </p>
        <?php else : ?>
            <div class="jlg-diagnostics__grid jlg-diagnostics__grid--onboarding">
                <?php foreach ($onboarding_steps as $step_data) :
                    $step_number = isset($step_data['step']) ? (int) $step_data['step'] : 0;
                    $entries = isset($step_data['entries']) ? (int) $step_data['entries'] : 0;
                    $exits = isset($step_data['exits']) ? (int) $step_data['exits'] : 0;
                    $avg_time = isset($step_data['avg_time']) ? (float) $step_data['avg_time'] : 0.0;
                    $total_time = isset($step_data['total_time']) ? (float) $step_data['total_time'] : 0.0;
                    $valid_success = isset($step_data['validation_success']) ? (int) $step_data['validation_success'] : 0;
                    $valid_errors = isset($step_data['validation_errors']) ? (int) $step_data['validation_errors'] : 0;
                    $last_feedback_code = isset($step_data['last_feedback_code']) ? (string) $step_data['last_feedback_code'] : '';
                    $last_feedback_message = isset($step_data['last_feedback_message']) ? (string) $step_data['last_feedback_message'] : '';
                    $last_event_at = isset($step_data['last_event_at']) ? (int) $step_data['last_event_at'] : 0;
                    ?>
                    <article class="jlg-diagnostics__card jlg-diagnostics__card--onboarding" data-step="<?php echo esc_attr($step_number); ?>">
                        <h3 class="jlg-diagnostics__card-title">
                            <?php
                            if ($step_number > 0) {
                                printf(esc_html__('Étape %d', 'notation-jlg'), $step_number);
                            } else {
                                esc_html_e('Étape', 'notation-jlg');
                            }
                            ?>
                        </h3>
                        <dl class="jlg-diagnostics__stats">
                            <div>
                                <dt><?php esc_html_e('Entrées', 'notation-jlg'); ?></dt>
                                <dd><?php echo esc_html(number_format_i18n($entries)); ?></dd>
                            </div>
                            <div>
                                <dt><?php esc_html_e('Sorties', 'notation-jlg'); ?></dt>
                                <dd><?php echo esc_html(number_format_i18n($exits)); ?></dd>
                            </div>
                            <div>
                                <dt><?php esc_html_e('Validations OK', 'notation-jlg'); ?></dt>
                                <dd><?php echo esc_html(number_format_i18n($valid_success)); ?></dd>
                            </div>
                            <div>
                                <dt><?php esc_html_e('Validations en erreur', 'notation-jlg'); ?></dt>
                                <dd><?php echo esc_html(number_format_i18n($valid_errors)); ?></dd>
                            </div>
                            <div>
                                <dt><?php esc_html_e('Temps moyen', 'notation-jlg'); ?></dt>
                                <dd><?php echo esc_html(number_format_i18n($avg_time, 2)); ?>s</dd>
                            </div>
                            <div>
                                <dt><?php esc_html_e('Temps cumulé', 'notation-jlg'); ?></dt>
                                <dd><?php echo esc_html(number_format_i18n($total_time, 2)); ?>s</dd>
                            </div>
                        </dl>
                        <?php if ($last_feedback_code !== '' || $last_feedback_message !== '' || $last_event_at > 0) : ?>
                            <p class="jlg-diagnostics__last">
                                <strong><?php esc_html_e('Dernier feedback', 'notation-jlg'); ?></strong>
                                <?php if ($last_feedback_code !== '') : ?>
                                    <code class="jlg-diagnostics__feedback-code"><?php echo esc_html($last_feedback_code); ?></code>
                                <?php endif; ?>
                                <?php if ($last_event_at > 0) : ?>
                                    <span class="jlg-diagnostics__timestamp"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $last_event_at)); ?></span>
                                <?php endif; ?>
                            </p>
                            <?php if ($last_feedback_message !== '') : ?>
                                <p class="jlg-diagnostics__feedback-message"><?php echo esc_html($last_feedback_message); ?></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (! empty($onboarding_submission['attempts'])) :
            $submission_feedback_code = isset($onboarding_submission['last_feedback_code']) ? (string) $onboarding_submission['last_feedback_code'] : '';
            $submission_feedback_message = isset($onboarding_submission['last_feedback_message']) ? (string) $onboarding_submission['last_feedback_message'] : '';
            $submission_last_at = isset($onboarding_submission['last_attempt_at']) ? (int) $onboarding_submission['last_attempt_at'] : 0;
            ?>
            <div class="jlg-diagnostics__submission-summary">
                <h3><?php esc_html_e('Tentatives de finalisation', 'notation-jlg'); ?></h3>
                <dl class="jlg-diagnostics__stats">
                    <div>
                        <dt><?php esc_html_e('Tentatives', 'notation-jlg'); ?></dt>
                        <dd><?php echo esc_html(number_format_i18n((int) $onboarding_submission['attempts'])); ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Réussites', 'notation-jlg'); ?></dt>
                        <dd><?php echo esc_html(number_format_i18n((int) $onboarding_submission['success'])); ?></dd>
                    </div>
                    <div>
                        <dt><?php esc_html_e('Échecs', 'notation-jlg'); ?></dt>
                        <dd><?php echo esc_html(number_format_i18n((int) $onboarding_submission['errors'])); ?></dd>
                    </div>
                </dl>
                <?php if ($submission_feedback_code !== '' || $submission_feedback_message !== '' || $submission_last_at > 0) : ?>
                    <p class="jlg-diagnostics__last">
                        <strong><?php esc_html_e('Dernière tentative', 'notation-jlg'); ?></strong>
                        <?php if ($submission_feedback_code !== '') : ?>
                            <code class="jlg-diagnostics__feedback-code"><?php echo esc_html($submission_feedback_code); ?></code>
                        <?php endif; ?>
                        <?php if ($submission_last_at > 0) : ?>
                            <span class="jlg-diagnostics__timestamp"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $submission_last_at)); ?></span>
                        <?php endif; ?>
                    </p>
                    <?php if ($submission_feedback_message !== '') : ?>
                        <p class="jlg-diagnostics__feedback-message"><?php echo esc_html($submission_feedback_message); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

