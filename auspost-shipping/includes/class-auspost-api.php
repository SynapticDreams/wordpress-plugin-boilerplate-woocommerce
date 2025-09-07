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
         * Build a request URL for the given arguments.
         *
         * @param array $args Request arguments.
         * @return string
         */
        public function build_request_url( $args ) {
            $query = http_build_query( array(
                'from_postcode' => $args['from_postcode'],
                'to_postcode'   => $args['to_postcode'],
                'weight'        => $args['weight'],
            ), '', '&', PHP_QUERY_RFC3986 );

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
            $url       = $this->build_request_url( $args );
            $cache_key = 'auspost_rate_' . md5( $url );

            $cached = get_transient( $cache_key );
            if ( false !== $cached ) {
                return $cached;
            }

            $response = wp_remote_get( $url );

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
