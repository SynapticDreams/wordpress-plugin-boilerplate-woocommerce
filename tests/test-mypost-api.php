<?php
if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data ) {
        return json_encode( $data );
    }
}

if ( ! function_exists( '__' ) ) {
    function __( $text ) {
        return $text;
    }
}

if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        protected $code;
        protected $message;
        protected $data;

        public function __construct( $code = '', $message = '', $data = '' ) {
            $this->code    = $code;
            $this->message = $message;
            $this->data    = $data;
        }

        public function get_error_message() {
            return $this->message;
        }

        public function get_error_data() {
            return $this->data;
        }
    }
}

if ( ! class_exists( 'Auspost_Shipping_Logger' ) ) {
    class Auspost_Shipping_Logger {
        public static function log( $request, $response ) {}
    }
}

use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MyPostAPITest extends TestCase
{
    protected function setUp(): void
    {
        \WP_Mock::setUp();

        require_once __DIR__ . '/../auspost-shipping/includes/class-mypost-api.php';
    }

    protected function tearDown(): void
    {
        \WP_Mock::tearDown();
    }

    public function test_create_label_returns_wp_error_on_timeout()
    {
        $api_key    = 'key';
        $api_secret = 'secret';
        $api        = new MyPost_API( $api_key, $api_secret );
        $data       = array( 'foo' => 'bar' );
        $args       = array(
            'headers'     => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $api_secret ),
            ),
            'body'        => wp_json_encode( $data ),
            'timeout'     => 15,
            'redirection' => 5,
        );
        $error      = new WP_Error( 'timeout', 'timeout' );

        \WP_Mock::userFunction( 'wp_remote_post', array(
            'args'   => array( 'https://digitalapi.auspost.com.au/shipping/v1/labels', $args ),
            'return' => $error,
        ) );
        \WP_Mock::userFunction( 'is_wp_error', array( 'args' => array( $error ), 'return' => true ) );

        $result = $api->create_label( $data );

        $this->assertSame( $error, $result );
    }

    public function test_create_label_includes_error_body_for_non_200()
    {
        $api_key    = 'key';
        $api_secret = 'secret';
        $api        = new MyPost_API( $api_key, $api_secret );
        $data       = array( 'foo' => 'bar' );
        $args       = array(
            'headers'     => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $api_secret ),
            ),
            'body'        => wp_json_encode( $data ),
            'timeout'     => 15,
            'redirection' => 5,
        );
        $body     = json_encode( array( 'message' => 'fail' ) );
        $response = array( 'body' => $body, 'response' => array( 'code' => 500 ) );

        \WP_Mock::userFunction( 'wp_remote_post', array(
            'args'   => array( 'https://digitalapi.auspost.com.au/shipping/v1/labels', $args ),
            'return' => $response,
        ) );
        \WP_Mock::userFunction( 'is_wp_error', array( 'args' => array( $response ), 'return' => false ) );
        \WP_Mock::userFunction( 'wp_remote_retrieve_response_code', array( 'args' => array( $response ), 'return' => 500 ) );
        \WP_Mock::userFunction( 'wp_remote_retrieve_body', array( 'args' => array( $response ), 'return' => $body ) );

        $result = $api->create_label( $data );

        $this->assertInstanceOf( WP_Error::class, $result );
        $this->assertSame( $body, $result->get_error_data() );
    }
}

