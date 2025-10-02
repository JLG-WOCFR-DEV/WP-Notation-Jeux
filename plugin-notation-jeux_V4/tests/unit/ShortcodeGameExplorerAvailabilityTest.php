<?php

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/includes/shortcodes/class-jlg-shortcode-game-explorer.php';

class ShortcodeGameExplorerAvailabilityTest extends TestCase
{
    public function test_same_day_release_is_available_in_custom_timezone(): void
    {
        $previous_timezone = get_option('timezone_string');
        update_option('timezone_string', 'Europe/Paris');

        try {
            $timezone = wp_timezone();
            $release_iso = (new DateTimeImmutable('now', $timezone))->format('Y-m-d');

            $method = new ReflectionMethod(JLG_Shortcode_Game_Explorer::class, 'determine_availability');
            $method->setAccessible(true);

            $availability = $method->invoke(null, $release_iso);

            $this->assertIsArray($availability, 'Availability response should be an array.');
            $this->assertSame('available', $availability['status'] ?? null, 'Release on the current day should be available.');
            $this->assertSame(
                esc_html__('Disponible', 'notation-jlg'),
                $availability['label'] ?? '',
                'Availability label should match the available status.'
            );
        } finally {
            if (is_string($previous_timezone) && $previous_timezone !== '') {
                update_option('timezone_string', $previous_timezone);
            } else {
                delete_option('timezone_string');
            }
        }
    }
}
