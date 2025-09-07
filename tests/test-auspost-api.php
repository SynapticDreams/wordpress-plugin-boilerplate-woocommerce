<?php
use PHPUnit\Framework\TestCase;

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        public $errors = [];

        public function __construct( $code = '', $message = '', $data = null ) {
            if ( $code ) {
                $this->errors[ $code ] = [ $message ];
            }
        }
    }
}

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class AuspostAPITest extends TestCase
{
    protected function setUp(): void
    {
        \WP_Mock::setUp();
        require_once __DIR__ . '/../auspost-shipping/includes/class-auspost-api.php';
    }

    protected function tearDown(): void
    {
        \WP_Mock::tearDown();
    }

    public function test_get_rates_sends_auth_key_header()
    {
        $args = [
            'from_postcode' => '3000',
            'to_postcode'   => '4000',
            'weight'        => 1,
        ];

        $url = 'https://digitalapi.auspost.com.au/postage/parcel/domestic/service.json?from_postcode=3000&to_postcode=4000&weight=1';

        $body = json_encode([
            'services' => [
                'service' => [
                    [
                        'code'  => 'EXP',
                        'name'  => 'Express',
                        'price' => '10.00',
                    ],
                ],
            ],
        ]);

        $response = [
            'body'     => $body,
            'response' => ['code' => 200],
        ];

        \WP_Mock::userFunction('get_option', [
            'args'   => ['auspost_shipping_auspost_api_key'],
            'return' => 'APIKEY',
        ]);
        \WP_Mock::userFunction('get_transient', [
            'return' => false,
        ]);
        \WP_Mock::userFunction('set_transient');
        \WP_Mock::userFunction('is_wp_error', [
            'return' => false,
        ]);
        \WP_Mock::userFunction('wp_remote_get', [
            'args'   => [$url, ['headers' => ['auth-key' => 'APIKEY']]],
            'return' => $response,
        ]);
        \WP_Mock::userFunction('wp_remote_retrieve_response_code', [
            'args'   => [$response],
            'return' => 200,
        ]);
        \WP_Mock::userFunction('wp_remote_retrieve_body', [
            'args'   => [$response],
            'return' => $body,
        ]);

        $api   = new Auspost_API();
        $rates = $api->get_rates($args);

        $this->assertSame([
            ['code' => 'EXP', 'name' => 'Express', 'price' => 10.0],
        ], $rates);
    }

    public function test_build_request_url_missing_from_postcode_returns_error()
    {
        $api    = new Auspost_API();
        $result = $api->build_request_url([
            'to_postcode' => '4000',
            'weight'      => 1,
        ]);

        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_build_request_url_missing_to_postcode_returns_error()
    {
        $api    = new Auspost_API();
        $result = $api->build_request_url([
            'from_postcode' => '3000',
            'weight'        => 1,
        ]);

        $this->assertInstanceOf(WP_Error::class, $result);
    }

    public function test_build_request_url_missing_weight_returns_error()
    {
        $api    = new Auspost_API();
        $result = $api->build_request_url([
            'from_postcode' => '3000',
            'to_postcode'   => '4000',
        ]);

        $this->assertInstanceOf(WP_Error::class, $result);
    }
}
