<?php

use PHPUnit\Framework\TestCase;

class HelpersReviewVideoEmbedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_test_home_url_base'] = 'https://tests.example';
    }

    public function test_returns_embed_data_for_twitch_video(): void
    {
        $data = \JLG\Notation\Helpers::get_review_video_embed_data('https://www.twitch.tv/videos/123456789');

        $this->assertTrue($data['has_embed']);
        $this->assertSame('twitch', $data['provider']);
        $this->assertStringContainsString('https://player.twitch.tv/', $data['iframe_src']);

        $parts = wp_parse_url($data['iframe_src']);
        parse_str($parts['query'] ?? '', $query);

        $this->assertSame('v123456789', $query['video'] ?? null);
        $this->assertSame('tests.example', $query['parent'] ?? null);
        $this->assertSame('false', $query['autoplay'] ?? null);
    }

    public function test_returns_embed_data_for_dailymotion_video(): void
    {
        $data = \JLG\Notation\Helpers::get_review_video_embed_data('https://www.dailymotion.com/video/x7tg9gk');

        $this->assertTrue($data['has_embed']);
        $this->assertSame('dailymotion', $data['provider']);
        $this->assertStringContainsString('https://www.dailymotion.com/embed/video/x7tg9gk', $data['iframe_src']);

        $parts = wp_parse_url($data['iframe_src']);
        parse_str($parts['query'] ?? '', $query);

        $this->assertSame('0', $query['autoplay'] ?? null);
    }

    public function test_returns_fallback_when_provider_cannot_generate_embed(): void
    {
        $data = \JLG\Notation\Helpers::get_review_video_embed_data('https://www.twitch.tv/videos/notanid');

        $this->assertFalse($data['has_embed']);
        $this->assertSame('', $data['provider']);
        $this->assertSame('', $data['iframe_src']);
        $this->assertSame(
            sprintf(__('Impossible de préparer le lecteur vidéo pour %s.', 'notation-jlg'), 'Twitch'),
            $data['fallback_message']
        );
    }
}
