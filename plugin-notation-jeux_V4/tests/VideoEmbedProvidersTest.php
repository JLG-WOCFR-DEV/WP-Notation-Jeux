<?php

use JLG\Notation\Video\Providers\DailymotionProvider;
use JLG\Notation\Video\Providers\TwitchProvider;
use JLG\Notation\Video\Providers\VimeoProvider;
use JLG\Notation\Video\Providers\YouTubeProvider;
use PHPUnit\Framework\TestCase;

class VideoEmbedProvidersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_test_home_url_base'] = 'https://tests.example';
    }

    public function test_youtube_provider_normalizes_various_formats(): void
    {
        $provider = new YouTubeProvider();
        $urls      = array(
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://youtu.be/dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'https://www.youtube.com/shorts/dQw4w9WgXcQ',
        );

        foreach ($urls as $url) {
            $embed = $provider->build_embed_src($url);
            $this->assertNotSame('', $embed, 'Embed URL should not be empty for ' . $url);

            $parts = wp_parse_url($embed);
            $this->assertSame('/embed/dQw4w9WgXcQ', $parts['path'] ?? null);

            parse_str($parts['query'] ?? '', $query);
            $this->assertSame('0', $query['rel'] ?? null);
            $this->assertSame('1', $query['modestbranding'] ?? null);
            $this->assertSame('1', $query['enablejsapi'] ?? null);
        }

        $this->assertSame('', $provider->build_embed_src('https://www.youtube.com/watch?v='));
    }

    public function test_vimeo_provider_extracts_numeric_identifier(): void
    {
        $provider = new VimeoProvider();

        $embed = $provider->build_embed_src('https://vimeo.com/123456789');
        $this->assertNotSame('', $embed);
        $parts = wp_parse_url($embed);
        $this->assertSame('/video/123456789', $parts['path'] ?? null);
        parse_str($parts['query'] ?? '', $query);
        $this->assertSame('1', $query['dnt'] ?? null);

        $this->assertSame('', $provider->build_embed_src('https://vimeo.com/channels/staffpicks/'));
    }

    public function test_twitch_provider_handles_video_channel_and_clip(): void
    {
        $provider = new TwitchProvider();

        $video_embed = $provider->build_embed_src('https://www.twitch.tv/videos/987654321');
        $this->assertNotSame('', $video_embed);
        $parts = wp_parse_url($video_embed);
        $this->assertSame('/', $parts['path'] ?? '/');
        parse_str($parts['query'] ?? '', $video_query);
        $this->assertSame('v987654321', $video_query['video'] ?? null);
        $this->assertSame('false', $video_query['autoplay'] ?? null);
        $this->assertSame('false', $video_query['muted'] ?? null);
        $this->assertSame('tests.example', $video_query['parent'] ?? null);

        $channel_embed = $provider->build_embed_src('https://www.twitch.tv/JLGChannel');
        $this->assertNotSame('', $channel_embed);
        $channel_parts = wp_parse_url($channel_embed);
        parse_str($channel_parts['query'] ?? '', $channel_query);
        $this->assertSame('jlgchannel', $channel_query['channel'] ?? null);

        $clip_embed = $provider->build_embed_src('https://clips.twitch.tv/ImportantClipName');
        $this->assertNotSame('', $clip_embed);
        $clip_parts = wp_parse_url($clip_embed);
        $this->assertSame('/embed', $clip_parts['path'] ?? null);
        parse_str($clip_parts['query'] ?? '', $clip_query);
        $this->assertSame('ImportantClipName', $clip_query['clip'] ?? null);
        $this->assertSame('false', $clip_query['autoplay'] ?? null);
        $this->assertSame('tests.example', $clip_query['parent'] ?? null);

        $this->assertSame('', $provider->build_embed_src('https://www.twitch.tv/videos/not-valid'));
    }

    public function test_dailymotion_provider_strips_noise_from_slug(): void
    {
        $provider = new DailymotionProvider();

        $embed = $provider->build_embed_src('https://www.dailymotion.com/video/x7tg9gk');
        $this->assertNotSame('', $embed);
        $parts = wp_parse_url($embed);
        $this->assertSame('/embed/video/x7tg9gk', $parts['path'] ?? null);
        parse_str($parts['query'] ?? '', $query);
        $this->assertSame('0', $query['autoplay'] ?? null);

        $slug_embed = $provider->build_embed_src('https://dai.ly/x7tg9gk_extra');
        $this->assertNotSame('', $slug_embed);
        $slug_parts = wp_parse_url($slug_embed);
        $this->assertSame('/embed/video/x7tg9gk', $slug_parts['path'] ?? null);

        $this->assertSame('', $provider->build_embed_src('https://www.dailymotion.com/video/'));
    }
}
