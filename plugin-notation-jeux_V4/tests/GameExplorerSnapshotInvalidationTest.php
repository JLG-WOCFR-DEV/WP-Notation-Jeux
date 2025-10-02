<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/Helpers.php';
require_once __DIR__ . '/../includes/Shortcodes/GameExplorer.php';

final class GameExplorerSnapshotInvalidationTest extends TestCase
{
    private const SNAPSHOT_KEY = 'jlg_game_explorer_snapshot_v1';

    private ReflectionProperty $filtersSnapshotProperty;

    protected function setUp(): void
    {
        parent::setUp();

        $reflection = new ReflectionClass(\JLG\Notation\Shortcodes\GameExplorer::class);
        $this->filtersSnapshotProperty = $reflection->getProperty('filters_snapshot');
        $this->filtersSnapshotProperty->setAccessible(true);

        $GLOBALS['jlg_test_transients'] = [];
        $GLOBALS['jlg_test_posts'] = [];
        $GLOBALS['jlg_test_options'] = [];
        $GLOBALS['jlg_test_meta'] = [];
        $GLOBALS['jlg_test_filters'] = [];

        $this->filtersSnapshotProperty->setValue(null);
        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    protected function tearDown(): void
    {
        \JLG\Notation\Shortcodes\GameExplorer::clear_filters_snapshot();
        $this->filtersSnapshotProperty->setValue(null);

        parent::tearDown();
    }

    public function test_snapshot_cleared_for_all_invalidation_triggers(): void
    {
        $this->configurePluginOptions();
        $post = $this->registerPost(101, 'publish', 'post');

        $scenarios = [
            'relevant meta update' => function () use ($post): void {
                \JLG\Notation\Shortcodes\GameExplorer::maybe_clear_filters_snapshot_for_meta(0, $post->ID, '_jlg_developpeur', 'Studio');
            },
            'post save' => function () use ($post): void {
                \JLG\Notation\Shortcodes\GameExplorer::maybe_clear_filters_snapshot_for_post($post->ID, $post, true);
            },
            'status transition' => function () use ($post): void {
                \JLG\Notation\Shortcodes\GameExplorer::maybe_clear_filters_snapshot_for_status_change('draft', 'publish', $post);
            },
            'term assignment' => function () use ($post): void {
                \JLG\Notation\Shortcodes\GameExplorer::maybe_clear_filters_snapshot_for_terms($post->ID, [], [], 'category');
            },
            'term lifecycle event' => function (): void {
                \JLG\Notation\Shortcodes\GameExplorer::maybe_clear_filters_snapshot_for_term_event((object) ['taxonomy' => 'category']);
            },
        ];

        foreach ($scenarios as $context => $callback) {
            $this->primeSnapshotCaches();

            $callback();

            $this->assertSnapshotCleared($context);
        }
    }

    public function test_irrelevant_meta_does_not_invalidate_snapshot(): void
    {
        $this->configurePluginOptions();
        $post = $this->registerPost(202, 'publish', 'post');

        $snapshot = $this->primeSnapshotCaches();

        \JLG\Notation\Shortcodes\GameExplorer::maybe_clear_filters_snapshot_for_meta(0, $post->ID, '_irrelevant_meta_key', 'Value');

        $this->assertSnapshotIntact($snapshot, 'irrelevant meta update');
    }

    public function test_unsupported_post_type_does_not_invalidate_snapshot(): void
    {
        $this->configurePluginOptions();
        $post = $this->registerPost(303, 'publish', 'page');

        $snapshot = $this->primeSnapshotCaches();

        \JLG\Notation\Shortcodes\GameExplorer::maybe_clear_filters_snapshot_for_post($post->ID, $post, false);

        $this->assertSnapshotIntact($snapshot, 'unsupported post type save');
    }

    private function configurePluginOptions(): void
    {
        $defaults = \JLG\Notation\Helpers::get_default_settings();
        $GLOBALS['jlg_test_options']['notation_jlg_settings'] = $defaults;
        $GLOBALS['jlg_test_options']['jlg_platforms_list'] = [];
        \JLG\Notation\Helpers::flush_plugin_options_cache();
    }

    private function registerPost(int $post_id, string $status, string $type): WP_Post
    {
        $post = new WP_Post([
            'ID'          => $post_id,
            'post_type'   => $type,
            'post_status' => $status,
            'post_title'  => 'Test Post ' . $post_id,
        ]);

        $GLOBALS['jlg_test_posts'][$post_id] = $post;

        return $post;
    }

    /**
     * @return array<string, mixed>
     */
    private function primeSnapshotCaches(): array
    {
        $snapshot = ['primed' => uniqid('snapshot_', true)];

        set_transient(self::SNAPSHOT_KEY, $snapshot);
        $this->filtersSnapshotProperty->setValue($snapshot);

        return $snapshot;
    }

    private function assertSnapshotCleared(string $context): void
    {
        $this->assertFalse(
            get_transient(self::SNAPSHOT_KEY),
            sprintf('Transient cache should be cleared after %s.', $context)
        );

        $this->assertNull(
            $this->filtersSnapshotProperty->getValue(),
            sprintf('Static snapshot cache should be reset after %s.', $context)
        );
    }

    /**
     * @param array<string, mixed> $expectedSnapshot
     */
    private function assertSnapshotIntact(array $expectedSnapshot, string $context): void
    {
        $this->assertSame(
            $expectedSnapshot,
            get_transient(self::SNAPSHOT_KEY),
            sprintf('Transient cache should remain intact after %s.', $context)
        );

        $this->assertSame(
            $expectedSnapshot,
            $this->filtersSnapshotProperty->getValue(),
            sprintf('Static snapshot cache should remain intact after %s.', $context)
        );
    }
}
