<?php
/**
 * AusPost API helper class.
 *
 * Handles building requests to the AusPost API and parsing responses
 * into a format consumable by the shipping method.
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

if ( ! class_exists( 'Auspost_API' ) ) {

    /**
     * Minimal AusPost API client.
     */
    class Auspost_API {

        /**
         * API endpoint for requesting domestic parcel rates.
         *
         * @var string
         */
        protected $endpoint = 'https://digitalapi.auspost.com.au/postage/parcel/domestic/service.json';

        /**
         * API key for authenticating requests.
         *
         * @var string
         */
        protected $api_key;

        /**
         * Constructor.
         *
         * @param string|null $api_key API key for the AusPost API.
         */
        public function __construct( $api_key = null ) {
            if ( null === $api_key ) {
                $api_key = get_option( 'auspost_shipping_auspost_api_key' );
            }

            $this->api_key = $api_key;
        }

        /**
         * Build a request URL for the given arguments.
         *
         * @param array $args Request arguments.
         * @return string|WP_Error Request URL or WP_Error when required args are missing.
         */
        public function build_request_url( $args ) {
            if ( ! isset( $args['from_postcode'] ) ) {
                return new WP_Error( 'auspost_api_missing_from_postcode', __( 'Missing required from_postcode.', 'auspost-shipping' ) );
            }

            if ( ! isset( $args['to_postcode'] ) ) {
                return new WP_Error( 'auspost_api_missing_to_postcode', __( 'Missing required to_postcode.', 'auspost-shipping' ) );
            }

            if ( ! isset( $args['weight'] ) ) {
                return new WP_Error( 'auspost_api_missing_weight', __( 'Missing required weight.', 'auspost-shipping' ) );
            }

            $query = http_build_query(
                array(
                    'from_postcode' => $args['from_postcode'],
                    'to_postcode'   => $args['to_postcode'],
                    'weight'        => $args['weight'],
                ),
                '',
                '&',
                PHP_QUERY_RFC3986
            );

            return $this->endpoint . '?' . $query;
        }

        /**
         * Request rates from the AusPost API.
         *
         * Responses are cached using WordPress transients to avoid
         * hitting the API repeatedly with the same request.
         *
         * @param array $args Request arguments.
         * @return array|WP_Error Array of rate data or WP_Error on failure.
         */
        public function get_rates( $args ) {
            $url = $this->build_request_url( $args );
            if ( is_wp_error( $url ) ) {
                return $url;
            }

            $cache_key = 'auspost_rate_' . md5( $url );

            $cached = get_transient( $cache_key );
            if ( false !== $cached ) {
                return $cached;
            }

            try {
                $response = wp_remote_get(
                    $url,
                    array(
                        'headers' => array(
                            'auth-key' => $this->api_key,
                        ),
                    )
                );
            } catch ( Exception $e ) {
                if ( class_exists( 'Auspost_Shipping_Logger' ) ) {
                    Auspost_Shipping_Logger::log( $url, $e->getMessage() );
                }
                return new WP_Error( 'auspost_api_http_error', $e->getMessage() );
            }

            if ( class_exists( 'Auspost_Shipping_Logger' ) ) {
                if ( is_wp_error( $response ) ) {
                    Auspost_Shipping_Logger::log( $url, $response->get_error_message() );
                } else {
                    $code_log = wp_remote_retrieve_response_code( $response );
                    $body_log = wp_remote_retrieve_body( $response );
                    Auspost_Shipping_Logger::log( $url, array( 'code' => $code_log, 'body' => $body_log ) );
                }
            }

            if ( is_wp_error( $response ) ) {
                return $response;
            }

            $code = wp_remote_retrieve_response_code( $response );
            if ( 200 !== $code ) {
                return new WP_Error( 'auspost_api_error', __( 'Unexpected response from AusPost API.', 'auspost-shipping' ) );
            }

            $rates = $this->parse_response( wp_remote_retrieve_body( $response ) );
            if ( is_wp_error( $rates ) ) {
                return $rates;
            }

            set_transient( $cache_key, $rates, HOUR_IN_SECONDS );

            return $rates;
        }

        /**
         * Parse a JSON API response into a simplified rate array.
         *
         * @param string $body Response body from wp_remote_get().
         * @return array|WP_Error Array of rates or WP_Error.
         */
        public function parse_response( $body ) {
            $data = json_decode( $body, true );

            if ( json_last_error() !== JSON_ERROR_NONE || empty( $data['services']['service'] ) ) {
                return new WP_Error( 'auspost_api_invalid', __( 'Invalid response from AusPost API.', 'auspost-shipping' ) );
            }

            $rates = array();
            foreach ( $data['services']['service'] as $service ) {
                $rates[] = array(
                    'code'  => $service['code'],
                    'name'  => $service['name'],
                    'price' => isset( $service['price'] ) ? (float) $service['price'] : 0,
                );
            }

            return $rates;
        }
    }
}
