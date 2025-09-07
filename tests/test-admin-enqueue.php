<?php
use PHPUnit\Framework\TestCase;

class AdminEnqueueTest extends TestCase
{
    private Auspost_Shipping_Admin $admin;

    protected function setUp(): void
    {
        \WP_Mock::setUp();
        require_once __DIR__ . '/../auspost-shipping/admin/class-auspost-shipping-admin.php';
        $this->admin = new Auspost_Shipping_Admin('auspost-shipping', '1.0.0');
    }

    protected function tearDown(): void
    {
        \WP_Mock::tearDown();
    }

    /**
     * @dataProvider relevantScreens
     */
    public function test_enqueue_styles_on_relevant_screens(string $screen_id)
    {
        \WP_Mock::userFunction('get_current_screen', [
            'return' => (object) ['id' => $screen_id],
            'times'  => 1,
        ]);
        \WP_Mock::userFunction('wp_enqueue_style', [
            'times' => 1,
        ]);

        $this->admin->enqueue_styles();
    }

    /**
     * @dataProvider irrelevantScreens
     */
    public function test_enqueue_styles_not_on_other_screens(string $screen_id)
    {
        \WP_Mock::userFunction('get_current_screen', [
            'return' => (object) ['id' => $screen_id],
            'times'  => 1,
        ]);
        \WP_Mock::userFunction('wp_enqueue_style', [
            'times' => 0,
        ]);

        $this->admin->enqueue_styles();
    }

    /**
     * @dataProvider relevantScreens
     */
    public function test_enqueue_scripts_on_relevant_screens(string $screen_id)
    {
        \WP_Mock::userFunction('get_current_screen', [
            'return' => (object) ['id' => $screen_id],
            'times'  => 1,
        ]);
        \WP_Mock::userFunction('wp_enqueue_script', [
            'times' => 1,
        ]);

        $this->admin->enqueue_scripts();
    }

    /**
     * @dataProvider irrelevantScreens
     */
    public function test_enqueue_scripts_not_on_other_screens(string $screen_id)
    {
        \WP_Mock::userFunction('get_current_screen', [
            'return' => (object) ['id' => $screen_id],
            'times'  => 1,
        ]);
        \WP_Mock::userFunction('wp_enqueue_script', [
            'times' => 0,
        ]);

        $this->admin->enqueue_scripts();
    }

    public function relevantScreens(): array
    {
        return [
            ['woocommerce_page_wc-settings'],
            ['shop_order'],
            ['edit-shop_order'],
        ];
    }

    public function irrelevantScreens(): array
    {
        return [
            ['dashboard'],
            ['plugins'],
        ];
    }
}
