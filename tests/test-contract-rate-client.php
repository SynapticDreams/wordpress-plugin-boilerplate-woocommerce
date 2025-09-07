<?php
use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data ) {
        return json_encode( $data );
    }
}

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        public function __construct( $code = '', $message = '' ) {}
        public function get_error_message() { return ''; }
    }
}

if ( ! class_exists( 'Auspost_Shipping_Logger' ) ) {
    class Auspost_Shipping_Logger {
        public static function log( $request, $response ) {}
    }
}

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ContractRateClientTest extends TestCase {
    protected function setUp(): void {
        \WP_Mock::setUp();
        require_once __DIR__ . '/../auspost-shipping/includes/class-contract-rate-client.php';
    }

    protected function tearDown(): void {
        \WP_Mock::tearDown();
    }

    public function test_get_rates_sends_basic_auth_and_service_codes() {
        $shipment = [
            'from_postcode' => '3000',
            'to_postcode'   => '4000',
            'weight'        => 1,
        ];

        $payload = wp_json_encode([
            'account-number' => 'ACC123',
            'from' => ['postcode' => '3000'],
            'to' => ['postcode' => '4000'],
            'items' => [ ['weight' => 1] ],
            'service-codes' => ['AUS_PARCEL_REGULAR','AUS_PARCEL_EXPRESS','EXP','EXP_PLAT'],
        ]);

        $response_body = json_encode([
            'prices' => [
                [
                    'service-code' => 'AUS_PARCEL_REGULAR',
                    'service-name' => 'Parcel Post',
                    'total-cost'   => 9.95,
                ],
            ],
        ]);
        $response = ['body' => $response_body, 'response' => ['code' => 200]];

        \WP_Mock::userFunction( 'get_transient', [ 'return' => false ] );
        \WP_Mock::userFunction( 'set_transient' );
        \WP_Mock::userFunction( 'wp_remote_post', [
            'args' => [
                'https://digitalapi.auspost.com.au/shipping/v1/prices/parcel/domestic',
                [
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode( 'key:secret' ),
                        'Content-Type'  => 'application/json',
                    ],
                    'body'    => $payload,
                    'timeout' => 15,
                ],
            ],
            'return' => $response,
        ] );
        \WP_Mock::userFunction( 'is_wp_error', [ 'args' => [ $response ], 'return' => false ] );
        \WP_Mock::userFunction( 'wp_remote_retrieve_response_code', [ 'args' => [ $response ], 'return' => 200 ] );
        \WP_Mock::userFunction( 'wp_remote_retrieve_body', [ 'args' => [ $response ], 'return' => $response_body ] );

        $client = new Contract_Rate_Client( 'ACC123', 'key', 'secret' );
        $rates  = $client->get_rates( $shipment );

        $this->assertSame([
            ['code' => 'AUS_PARCEL_REGULAR', 'name' => 'Parcel Post', 'price' => 9.95],
        ], $rates);
    }

    public function test_get_rates_returns_empty_on_invalid_credentials() {
        $shipment = [
            'from_postcode' => '3000',
            'to_postcode'   => '4000',
            'weight'        => 1,
        ];

        $payload = wp_json_encode([
            'account-number' => 'ACC123',
            'from' => ['postcode' => '3000'],
            'to' => ['postcode' => '4000'],
            'items' => [ ['weight' => 1] ],
            'service-codes' => ['AUS_PARCEL_REGULAR','AUS_PARCEL_EXPRESS','EXP','EXP_PLAT'],
        ]);

        $response = ['body' => '{}', 'response' => ['code' => 401]];

        \WP_Mock::userFunction( 'get_transient', [ 'return' => false ] );
        \WP_Mock::userFunction( 'wp_remote_post', [
            'args' => [
                'https://digitalapi.auspost.com.au/shipping/v1/prices/parcel/domestic',
                [
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode( 'key:secret' ),
                        'Content-Type'  => 'application/json',
                    ],
                    'body'    => $payload,
                    'timeout' => 15,
                ],
            ],
            'return' => $response,
        ] );
        \WP_Mock::userFunction( 'is_wp_error', [ 'args' => [ $response ], 'return' => false ] );
        \WP_Mock::userFunction( 'wp_remote_retrieve_response_code', [ 'args' => [ $response ], 'return' => 401 ] );
        \WP_Mock::userFunction( 'wp_remote_retrieve_body', [ 'args' => [ $response ], 'return' => '{}' ] );
        \WP_Mock::userFunction( 'Auspost_Shipping_Logger::log' );

        $client = new Contract_Rate_Client( 'ACC123', 'key', 'secret' );
        $rates  = $client->get_rates( $shipment );

        $this->assertSame( [], $rates );
    }
}

