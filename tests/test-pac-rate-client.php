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
        public function get_error_message() {
            $codes = array_keys( $this->errors );
            return $this->errors[ $codes[0] ][0];
        }
    }
}

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PacRateClientTest extends TestCase {
    protected function setUp(): void {
        \WP_Mock::setUp();
        require_once __DIR__ . '/../auspost-shipping/includes/class-auspost-api.php';
        require_once __DIR__ . '/../auspost-shipping/includes/class-pac-rate-client.php';
    }

    protected function tearDown(): void {
        \WP_Mock::tearDown();
    }

    public function test_get_rates_uses_pac_api_key_header() {
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
            'args'   => ['auspost_shipping_pac_api_key'],
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

        $client = new Pac_Rate_Client();
        $rates  = $client->get_rates( $args );

        $this->assertSame([
            ['code' => 'EXP', 'name' => 'Express', 'price' => 10.0],
        ], $rates);
    }

    public function test_get_rates_returns_empty_when_missing_key() {
        $args = [
            'from_postcode' => '3000',
            'to_postcode'   => '4000',
            'weight'        => 1,
        ];

        \WP_Mock::userFunction('get_option', [
            'args'   => ['auspost_shipping_pac_api_key'],
            'return' => '',
        ]);

        $client = new Pac_Rate_Client();
        $rates  = $client->get_rates( $args );

        $this->assertSame([], $rates);
    }

    public function test_get_rates_returns_empty_on_wp_error() {
        $args = [
            'from_postcode' => '3000',
            'to_postcode'   => '4000',
            'weight'        => 1,
        ];

        $error = new WP_Error( 'http_error', 'fail' );

        \WP_Mock::userFunction( 'get_option', [ 'args' => ['auspost_shipping_pac_api_key'], 'return' => 'APIKEY' ] );
        \WP_Mock::userFunction( 'get_transient', [ 'return' => false ] );
        \WP_Mock::userFunction( 'wp_remote_get', [ 'return' => $error ] );
        \WP_Mock::userFunction( 'is_wp_error', [ 'args' => [ $error ], 'return' => true ] );
        \WP_Mock::userFunction( 'Auspost_Shipping_Logger::log' );

        $client = new Pac_Rate_Client( 'APIKEY' );
        $rates  = $client->get_rates( $args );

        $this->assertSame( [], $rates );
    }
}
