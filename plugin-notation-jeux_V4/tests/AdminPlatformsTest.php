<?php

use PHPUnit\Framework\TestCase;

class AdminPlatformsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['jlg_test_options'] = [];
        $GLOBALS['jlg_test_transients'] = [];
        $_POST = [];
        $_GET = [];

        $instanceProperty = new ReflectionProperty(\JLG\Notation\Admin\Platforms::class, 'instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null);

        $debugProperty = new ReflectionProperty(\JLG\Notation\Admin\Platforms::class, 'debug_messages');
        $debugProperty->setAccessible(true);
        $debugProperty->setValue(null, []);
    }

    private function invokePrivateMethod($object, string $method, array $args = [])
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $args);
    }

    private function setDefaultPlatforms(\JLG\Notation\Admin\Platforms $admin, array $platforms): void
    {
        $property = new ReflectionProperty(\JLG\Notation\Admin\Platforms::class, 'default_platforms');
        $property->setAccessible(true);
        $property->setValue($admin, $platforms);
    }

    public function test_add_platform_persists_custom_definition_and_order(): void
    {
        $admin = new \JLG\Notation\Admin\Platforms();

        $_POST['new_platform_name'] = 'Amiga 500';
        $_POST['new_platform_icon'] = '🕹️';

        $storage = $this->invokePrivateMethod($admin, 'get_stored_platform_data');
        $result = $this->invokePrivateMethod($admin, 'add_platform', [&$storage]);

        $this->assertTrue($result['success']);

        $option = get_option('jlg_platforms_list');
        $this->assertArrayHasKey('custom_platforms', $option);
        $this->assertArrayHasKey('amiga-500', $option['custom_platforms']);
        $this->assertSame('Amiga 500', $option['custom_platforms']['amiga-500']['name']);
        $this->assertSame('🕹️', $option['custom_platforms']['amiga-500']['icon']);
        $this->assertArrayHasKey('order', $option);
        $this->assertArrayHasKey('amiga-500', $option['order']);

        $platforms = $admin->get_platforms();
        $this->assertArrayHasKey('amiga-500', $platforms);
        $this->assertSame('Amiga 500', $platforms['amiga-500']['name']);
        $this->assertSame($option['order']['amiga-500'], $platforms['amiga-500']['order']);
    }

    public function test_delete_platform_removes_custom_definition_and_order(): void
    {
        $admin = new \JLG\Notation\Admin\Platforms();

        $_POST['new_platform_name'] = 'Amiga 500';
        $_POST['new_platform_icon'] = '🕹️';
        $storage = $this->invokePrivateMethod($admin, 'get_stored_platform_data');
        $this->invokePrivateMethod($admin, 'add_platform', [&$storage]);

        $_POST = ['platform_key' => 'amiga-500'];
        $storage = $this->invokePrivateMethod($admin, 'get_stored_platform_data');
        $result = $this->invokePrivateMethod($admin, 'delete_platform', [&$storage]);

        $this->assertTrue($result['success']);

        $option = get_option('jlg_platforms_list');
        $this->assertArrayNotHasKey('amiga-500', $option['custom_platforms'] ?? []);
        $this->assertArrayNotHasKey('amiga-500', $option['order'] ?? []);
    }

    public function test_update_platform_order_saves_only_order_map(): void
    {
        $admin = new \JLG\Notation\Admin\Platforms();

        $_POST['new_platform_name'] = 'Amiga 500';
        $_POST['new_platform_icon'] = '🕹️';
        $storage = $this->invokePrivateMethod($admin, 'get_stored_platform_data');
        $this->invokePrivateMethod($admin, 'add_platform', [&$storage]);

        $_POST = [
            'platform_order' => ['playstation-5', 'pc', 'amiga-500'],
        ];
        $storage = $this->invokePrivateMethod($admin, 'get_stored_platform_data');
        $result = $this->invokePrivateMethod($admin, 'update_platform_order', [&$storage]);

        $this->assertTrue($result['success']);

        $option = get_option('jlg_platforms_list');
        $this->assertArrayHasKey('custom_platforms', $option);
        $this->assertArrayHasKey('amiga-500', $option['custom_platforms']);
        $this->assertArrayNotHasKey('pc', $option['custom_platforms']);
        $this->assertSame(1, $option['order']['playstation-5']);
        $this->assertSame(2, $option['order']['pc']);
        $this->assertSame(3, $option['order']['amiga-500']);

        $platforms = $admin->get_platforms();
        $this->assertSame('PC', $platforms['pc']['name']);
    }

    public function test_reordered_platforms_reflect_updated_default_definitions(): void
    {
        update_option('jlg_platforms_list', [
            'custom_platforms' => [],
            'order' => [
                'playstation-5' => 1,
                'pc' => 2,
            ],
        ]);

        $admin = new \JLG\Notation\Admin\Platforms();

        $this->setDefaultPlatforms($admin, [
            'pc' => ['name' => 'PC Nouveau', 'icon' => '🖥️', 'order' => 2, 'custom' => false],
            'playstation-5' => ['name' => 'PlayStation 5', 'icon' => '🕹️', 'order' => 1, 'custom' => false],
            'mega-drive' => ['name' => 'Mega Drive', 'icon' => '🕹️', 'order' => 3, 'custom' => false],
        ]);

        $platforms = $admin->get_platforms();

        $this->assertSame(['playstation-5', 'pc', 'mega-drive'], array_slice(array_keys($platforms), 0, 3));
        $this->assertSame('PC Nouveau', $platforms['pc']['name']);
        $this->assertSame('🕹️', $platforms['playstation-5']['icon']);
        $this->assertArrayHasKey('mega-drive', $platforms);
        $this->assertFalse($platforms['mega-drive']['custom']);
    }

    public function test_sanitize_platforms_falls_back_to_helper_defaults_when_singleton_missing(): void
    {
        $instanceProperty = new ReflectionProperty(\JLG\Notation\Admin\Platforms::class, 'instance');
        $instanceProperty->setAccessible(true);
        $originalInstance = $instanceProperty->getValue();

        try {
            $instanceProperty->setValue(null, false);

            $sanitized = \JLG\Notation\Utils\Validator::sanitize_platforms([
                'Steam Deck',
                'Invalid Console',
            ]);
        } finally {
            $instanceProperty->setValue(null, $originalInstance);
        }

        $this->assertSame(['Steam Deck'], $sanitized);
    }

    public function test_sanitize_platforms_filters_obsolete_defaults_when_manager_unavailable(): void
    {
        $instanceProperty = new ReflectionProperty(\JLG\Notation\Admin\Platforms::class, 'instance');
        $instanceProperty->setAccessible(true);
        $originalInstance = $instanceProperty->getValue();

        try {
            $instanceProperty->setValue(null, false);

            $sanitized = \JLG\Notation\Utils\Validator::sanitize_platforms([
                'Steam Deck',
                'Nintendo Switch 2',
                'Invalid Console',
            ]);
        } finally {
            $instanceProperty->setValue(null, $originalInstance);
        }

        $this->assertSame(['Steam Deck'], $sanitized);
    }

    public function test_sanitize_platforms_preserves_custom_platforms(): void
    {
        $admin = \JLG\Notation\Admin\Platforms::get_instance();

        $_POST['new_platform_name'] = 'Amiga 600';
        $_POST['new_platform_icon'] = '🕹️';

        $storage = $this->invokePrivateMethod($admin, 'get_stored_platform_data');
        $result = $this->invokePrivateMethod($admin, 'add_platform', [&$storage]);

        $this->assertTrue($result['success']);

        $sanitized = \JLG\Notation\Utils\Validator::sanitize_platforms([
            'Amiga 600',
            'Imaginary Console',
        ]);

        $this->assertSame(['Amiga 600'], $sanitized);
    }
}
