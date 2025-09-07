<?php
use PHPUnit\Framework\TestCase;

if (!class_exists('WC_Shipping_Method')) {
    class WC_Shipping_Method {
        public $id;
        public $form_fields = [];
        public function init_settings() {}
        public function get_option($key, $default = '') { return $default; }
        public function add_rate($rate) {}
    }
}

if (!interface_exists('Rate_Client_Interface')) {
    interface Rate_Client_Interface {
        public function get_rates(array $shipment): array;
    }
}

if (!class_exists('Dummy_Rate_Client')) {
    class Dummy_Rate_Client implements Rate_Client_Interface {
        public static $rates = [];
        public static $calls = 0;
        public function get_rates(array $shipment): array {
            self::$calls++;
            return self::$rates;
        }
    }
}

class ShippingMethodTest extends TestCase
{
    protected function setUp(): void
    {
        \WP_Mock::setUp();

        \WP_Mock::userFunction('__', [
            'return_arg' => 1,
        ]);
        \WP_Mock::userFunction('wc_get_weight', [
            'return_arg' => 1,
        ]);
        \WP_Mock::userFunction('wc_format_postcode', [
            'return_arg' => 0,
        ]);
        \WP_Mock::userFunction('is_wp_error', [
            'return' => false,
        ]);
        \WP_Mock::userFunction('get_option', [
            'return' => '',
        ]);
        \WP_Mock::userFunction('WC_Validation::is_postcode', [
            'return' => true,
        ]);
        \WP_Mock::userFunction('WC', [
            'return' => new class {
                public $countries;
                public function __construct() {
                    $this->countries = new class {
                        public function get_base_postcode() {
                            return '3000';
                        }
                    };
                }
            }
        ]);

        require_once __DIR__ . '/../auspost-shipping/includes/class-auspost-shipping-method.php';

        Dummy_Rate_Client::$rates = [];
        Dummy_Rate_Client::$calls = 0;
    }

    protected function tearDown(): void
    {
        \WP_Mock::tearDown();
    }

    public function test_init_form_fields_contains_enabled_and_title_defaults()
    {
        $reflection = new ReflectionClass(Auspost_Shipping_Method::class);
        $method = $reflection->newInstanceWithoutConstructor();
        $method->init_form_fields();

        $this->assertArrayHasKey('enabled', $method->form_fields);
        $this->assertSame('yes', $method->form_fields['enabled']['default']);
        $this->assertArrayHasKey('title', $method->form_fields);
        $this->assertSame('AusPost Shipping', $method->form_fields['title']['default']);
        $this->assertArrayHasKey('pac_api_key', $method->form_fields);
        $this->assertSame('', $method->form_fields['pac_api_key']['default']);
    }

    public function test_calculate_shipping_adds_rates_from_api()
    {
        Dummy_Rate_Client::$rates = [
            ['code' => 'EXP', 'name' => 'Express', 'price' => 10.0],
            ['code' => 'STD', 'name' => 'Standard', 'price' => 5.0],
        ];

        $package = [
            'destination' => ['postcode' => '4000'],
            'contents_weight' => 2,
        ];

        $method = $this->getMockBuilder(Auspost_Shipping_Method::class)
            ->onlyMethods(['add_rate'])
            ->disableOriginalConstructor()
            ->getMock();
        $method->id = 'auspost_shipping';
        $method->set_rate_client(new Dummy_Rate_Client());

        $method->expects($this->exactly(2))
            ->method('add_rate')
            ->withConsecutive(
                [$this->equalTo([
                    'id' => 'auspost_shipping:EXP',
                    'label' => 'Express',
                    'cost' => 10.0,
                    'package' => $package,
                ])],
                [$this->equalTo([
                    'id' => 'auspost_shipping:STD',
                    'label' => 'Standard',
                    'cost' => 5.0,
                    'package' => $package,
                ])]
            );

        $method->calculate_shipping($package);
    }

    public function test_calculate_shipping_skips_api_call_when_postcode_missing()
    {
        $package = [
            'destination' => [],
            'contents_weight' => 2,
        ];

        $method = $this->getMockBuilder(Auspost_Shipping_Method::class)
            ->onlyMethods(['add_rate'])
            ->disableOriginalConstructor()
            ->getMock();

        $method->expects($this->never())
            ->method('add_rate');

        $method->calculate_shipping($package);

        $this->assertSame(0, Dummy_Rate_Client::$calls);
    }

    public function test_calculate_shipping_skips_api_call_when_weight_zero()
    {
        $package = [
            'destination' => ['postcode' => '4000'],
            'contents_weight' => 0,
        ];

        $method = $this->getMockBuilder(Auspost_Shipping_Method::class)
            ->onlyMethods(['add_rate'])
            ->disableOriginalConstructor()
            ->getMock();

        $method->expects($this->never())
            ->method('add_rate');

        $method->calculate_shipping($package);

        $this->assertSame(0, Dummy_Rate_Client::$calls);
    }
}
