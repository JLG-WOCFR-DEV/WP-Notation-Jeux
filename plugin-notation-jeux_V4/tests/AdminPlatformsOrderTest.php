<?php

use PHPUnit\Framework\TestCase;

class AdminPlatformsOrderTest extends TestCase
{
    private JLG_Admin_Platforms $platforms;

    protected function setUp(): void
    {
        parent::setUp();

        $this->platforms = new JLG_Admin_Platforms();

        $GLOBALS['jlg_test_options'] = [
            'jlg_platforms_list' => [
                'custom-platform' => [
                    'name'  => 'Custom Platform',
                    'icon'  => '🎮',
                    'order' => 99,
                ],
            ],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['jlg_test_options'], $_POST['platform_order']);
    }

    public function test_update_platform_order_handles_malformed_payload(): void
    {
        $_POST['platform_order'] = [
            ['nested' => 'value'],
            new stdClass(),
            123,
        ];

        $platforms = $GLOBALS['jlg_test_options']['jlg_platforms_list'];

        $reflection = new ReflectionClass($this->platforms);
        $method = $reflection->getMethod('update_platform_order');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->platforms, [&$platforms]);

        $this->assertFalse($result['success']);
        $this->assertSame("Ordre soumis invalide.", $result['message']);
    }
}
