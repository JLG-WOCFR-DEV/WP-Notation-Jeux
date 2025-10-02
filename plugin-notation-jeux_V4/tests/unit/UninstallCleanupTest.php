<?php

use PHPUnit\Framework\TestCase;

class UninstallCleanupTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['wpdb'], $GLOBALS['jlg_test_options'], $GLOBALS['jlg_test_transients']);

        parent::tearDown();
    }

    public function test_game_title_meta_key_is_deleted_on_uninstall(): void
    {
        update_option('jlg_notation_delete_data_on_uninstall', true);

        $wpdb_stub = new class() {
            public string $postmeta = 'wp_postmeta';
            public string $options = 'wp_options';
            /** @var string[] */
            public array $deleted_meta_keys = [];
            /** @var string[] */
            public array $queries = [];

            public function delete($table, $where)
            {
                if ($table === $this->postmeta && isset($where['meta_key'])) {
                    $this->deleted_meta_keys[] = (string) $where['meta_key'];
                }

                return true;
            }

            public function query($sql)
            {
                $this->queries[] = (string) $sql;

                return 1;
            }
        };

        $GLOBALS['wpdb'] = $wpdb_stub;

        if (!defined('WP_UNINSTALL_PLUGIN')) {
            define('WP_UNINSTALL_PLUGIN', true);
        }

        require dirname(__DIR__, 2) . '/uninstall.php';

        $this->assertContains('_jlg_game_title', $wpdb_stub->deleted_meta_keys);
    }
}
