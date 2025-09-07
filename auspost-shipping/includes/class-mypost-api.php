<?php
/**
 * MyPost Business API helper class.
 *
 * Handles authentication and label creation requests to the
 * MyPost Business API.
 *
 * @link       https://paulmiller3000.com
 * @since      1.0.0
 *
 * @package    Auspost_Shipping
 * @subpackage Auspost_Shipping/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'MyPost_API' ) ) {
    /**
     * Minimal MyPost Business API client.
     */
    class MyPost_API {

        /**
         * API endpoint for creating labels.
         *
         * @var string
         */
        protected $endpoint = 'https://digitalapi.auspost.com.au/shipping/v1/labels';

        /**
         * API key provided by MyPost Business.
         *
         * @var string
         */
        protected $api_key;

        /**
         * API secret provided by MyPost Business.
         *
         * @var string
         */
        protected $api_secret;

        /**
         * Setup client with credentials.
         *
         * @param string $api_key    API key.
         * @param string $api_secret API secret.
         */
        public function __construct( $api_key, $api_secret ) {
            $this->api_key    = $api_key;
            $this->api_secret = $api_secret;
        }

        /**
         * Build the request headers for API calls.
         *
         * @return array
         */
        protected function get_headers() {
            return array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic ' . base64_encode( $this->api_key . ':' . $this->api_secret ),
            );
        }

        /**
         * Request creation of a shipping label.
         *
         * @param array $data Label request payload.
         * @return array|WP_Error Label URL and tracking number on success or WP_Error on failure.
         */
        public function create_label( $data ) {
            $args = array(
                'headers' => $this->get_headers(),
                'body'    => wp_json_encode( $data ),
            );

            $response = wp_remote_post( $this->endpoint, $args );

            if ( is_wp_error( $response ) ) {
                Auspost_Shipping_Logger::log( $data, $response->get_error_message() );
                return $response;
            }

            $code  = wp_remote_retrieve_response_code( $response );
            $body  = json_decode( wp_remote_retrieve_body( $response ), true );
            $error = json_last_error();
            Auspost_Shipping_Logger::log( $data, array( 'code' => $code, 'body' => $body ) );

            if ( JSON_ERROR_NONE !== $error ) {
                return new WP_Error( 'mypost_api_json_error', __( 'Unable to decode response from MyPost API.', 'auspost-shipping' ) );
            }

            if ( 201 !== $code && 200 !== $code ) {
                return new WP_Error( 'mypost_api_error', __( 'Unexpected response from MyPost API.', 'auspost-shipping' ) );
            }

            if ( empty( $body['labelUrl'] ) || empty( $body['trackingNumber'] ) ) {
                return new WP_Error( 'mypost_api_invalid', __( 'Invalid response from MyPost API.', 'auspost-shipping' ) );
            }

            return array(
                'label_url'       => $body['labelUrl'],
                'tracking_number' => $body['trackingNumber'],
            );
        }
    }
}
