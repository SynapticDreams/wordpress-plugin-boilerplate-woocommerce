<?php
use PHPUnit\Framework\TestCase;

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private $code;
        private $message;

        public function __construct( $code = '', $message = '' ) {
            $this->code    = $code;
            $this->message = $message;
        }

        public function get_error_code() {
            return $this->code;
        }

        public function get_error_message() {
            return $this->message;
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
        \WP_Mock::userFunction('__', [ 'return_arg' => 1 ]);
        \WP_Mock::userFunction('wp_json_decode', [
            'return' => function ( $data, $assoc = false ) {
                return json_decode( $data, $assoc );
            },
        ]);

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

    public function test_parse_response_returns_wp_error_on_malformed_json()
    {
        $api    = new Auspost_API();
        $result = $api->parse_response('{"invalid"');

        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertSame('auspost_api_invalid', $result->get_error_code());
        $this->assertSame('Unable to decode response from AusPost API.', $result->get_error_message());
    }
}
