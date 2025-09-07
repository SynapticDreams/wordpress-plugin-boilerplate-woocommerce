<?php
use PHPUnit\Framework\TestCase;

class RequirementsTest extends TestCase
{
    public function setUp(): void
    {
        \WP_Mock::setUp();

        if (!defined('WPINC')) {
            define('WPINC', 'wpinc');
        }
        if (!defined('ABSPATH')) {
            define('ABSPATH', __DIR__);
        }
        if (!function_exists('plugin_dir_path')) {
            function plugin_dir_path($file)
            {
                return __DIR__ . '/stubs/';
            }
        }
        \WP_Mock::userFunction('register_activation_hook');
        \WP_Mock::userFunction('register_deactivation_hook');

        \WP_Mock::expectActionAdded('plugins_loaded', 'ausps_check_requirements');

        require_once __DIR__ . '/../auspost-shipping/auspost-shipping.php';
    }

    public function tearDown(): void
    {
        \WP_Mock::tearDown();
    }

    public function test_returns_false_and_hooks_notice_when_woocommerce_inactive()
    {
        \WP_Mock::expectActionAdded('admin_notices', 'ausps_missing_wc_notice');

        $this->assertFalse(ausps_check_requirements());
    }

    public function test_returns_true_when_woocommerce_active()
    {
        if (!class_exists('WooCommerce')) {
            class WooCommerce {}
        }

        $this->assertTrue(ausps_check_requirements());
    }
}
