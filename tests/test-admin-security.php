<?php
use PHPUnit\Framework\TestCase;

class AdminSecurityTest extends TestCase
{
    private $admin;

    protected function setUp(): void
    {
        \WP_Mock::setUp();
        require_once __DIR__ . '/../auspost-shipping/admin/class-auspost-shipping-admin.php';
        $this->admin = new Auspost_Shipping_Admin('auspost-shipping', '1.0.0');
        \WP_Mock::userFunction('__', [
            'return_arg' => 0,
        ]);
    }

    protected function tearDown(): void
    {
        \WP_Mock::tearDown();
        $_REQUEST = [];
    }

    public function test_process_mypost_create_label_requires_capability()
    {
        \WP_Mock::userFunction('current_user_can', [
            'args'   => ['manage_woocommerce'],
            'return' => false,
            'times'  => 1,
        ]);

        $order = new stdClass();
        $this->admin->process_mypost_create_label($order);
    }

    public function test_process_mypost_create_label_requires_nonce()
    {
        $_REQUEST['_wpnonce'] = 'bad';

        \WP_Mock::userFunction('current_user_can', [
            'args'   => ['manage_woocommerce'],
            'return' => true,
            'times'  => 1,
        ]);
        \WP_Mock::userFunction('wp_unslash', [
            'return_arg' => 0,
        ]);
        \WP_Mock::userFunction('sanitize_text_field', [
            'return_arg' => 0,
        ]);
        \WP_Mock::userFunction('wp_verify_nonce', [
            'args'   => ['bad', 'woocommerce-order-actions'],
            'return' => false,
            'times'  => 1,
        ]);
        \WP_Mock::userFunction('get_option', [
            'times' => 0,
        ]);

        $order = new stdClass();
        $this->admin->process_mypost_create_label($order);
    }

    public function test_process_mypost_create_label_requires_credentials()
    {
        $_REQUEST['_wpnonce'] = 'good';

        \WP_Mock::userFunction('current_user_can', [
            'args'   => ['manage_woocommerce'],
            'return' => true,
            'times'  => 1,
        ]);
        \WP_Mock::userFunction('wp_unslash', [
            'return_arg' => 0,
        ]);
        \WP_Mock::userFunction('sanitize_text_field', [
            'return_arg' => 0,
        ]);
        \WP_Mock::userFunction('wp_verify_nonce', [
            'args'   => ['good', 'woocommerce-order-actions'],
            'return' => true,
            'times'  => 1,
        ]);
        \WP_Mock::userFunction('get_option', [
            'return' => '',
            'times'  => 2,
        ]);

        $order = new class {
            public $notes = [];
            public function add_order_note( $note ) {
                $this->notes[] = $note;
            }
            public function get_id() {
                return 1;
            }
        };

        $this->admin->process_mypost_create_label( $order );

        $this->assertSame( [ 'MyPost label error: Missing API credentials.' ], $order->notes );
    }

    public function test_handle_bulk_actions_requires_nonce()
    {
        $_REQUEST['mypost_create_labels_nonce'] = 'bad';

        \WP_Mock::userFunction('wp_unslash', [
            'return_arg' => 0,
        ]);
        \WP_Mock::userFunction('sanitize_text_field', [
            'return_arg' => 0,
        ]);
        \WP_Mock::userFunction('wp_verify_nonce', [
            'args'   => ['bad', 'mypost_create_labels'],
            'return' => false,
            'times'  => 1,
        ]);
        \WP_Mock::userFunction('wc_get_order', [
            'times' => 0,
        ]);
        \WP_Mock::userFunction('add_query_arg', [
            'times' => 0,
        ]);

        $result = $this->admin->handle_bulk_actions('http://example.com', 'mypost_create_labels', [1, 2]);
        $this->assertSame('http://example.com', $result);
    }
}
