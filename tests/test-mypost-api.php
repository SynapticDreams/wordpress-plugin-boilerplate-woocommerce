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

if ( ! class_exists( 'Auspost_Shipping_Logger' ) ) {
    class Auspost_Shipping_Logger {
        public static function log( $request, $response ) {}
    }
}

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MyPostAPITest extends TestCase
{
    protected function setUp(): void
    {
        \WP_Mock::setUp();
        \WP_Mock::userFunction('__', [ 'return_arg' => 1 ]);
        \WP_Mock::userFunction('wp_json_encode', [
            'return' => function ( $data ) {
                return json_encode( $data );
            },
        ]);
        \WP_Mock::userFunction('wp_json_decode', [
            'return' => function ( $data, $assoc = false ) {
                return json_decode( $data, $assoc );
            },
        ]);

        require_once __DIR__ . '/../auspost-shipping/includes/class-mypost-api.php';
    }

    protected function tearDown(): void
    {
        \WP_Mock::tearDown();
    }

    public function test_create_label_returns_wp_error_on_malformed_json()
    {
        $response = [
            'response' => ['code' => 200],
            'body'     => 'not json',
        ];

        \WP_Mock::userFunction('wp_remote_post', [ 'return' => $response ]);
        \WP_Mock::userFunction('is_wp_error', [ 'return' => false ]);
        \WP_Mock::userFunction('wp_remote_retrieve_response_code', [
            'args'   => [$response],
            'return' => 200,
        ]);
        \WP_Mock::userFunction('wp_remote_retrieve_body', [
            'args'   => [$response],
            'return' => 'not json',
        ]);

        $api    = new MyPost_API( 'key', 'secret' );
        $result = $api->create_label( [] );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( 'mypost_api_json_error', $result->get_error_code() );
        $this->assertSame( 'Unable to decode response from MyPost API.', $result->get_error_message() );
    }
}
