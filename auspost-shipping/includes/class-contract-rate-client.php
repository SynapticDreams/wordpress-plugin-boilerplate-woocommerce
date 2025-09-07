<?php
/**
 * Client for AusPost Shipping & Tracking API contracted account rates.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Contract_Rate_Client' ) ) {
    /**
     * Rate client for contracted accounts using basic authentication.
     */
    class Contract_Rate_Client implements Rate_Client_Interface {
        /**
         * Endpoint for requesting domestic parcel prices.
         *
         * @var string
         */
        protected $endpoint = 'https://digitalapi.auspost.com.au/shipping/v1/prices/parcel/domestic';

        /**
         * Username for basic auth.
         *
         * @var string
         */
        protected $username;

        /**
         * Password for basic auth.
         *
         * @var string
         */
        protected $password;

        /**
         * Setup credentials.
         *
         * @param string|null $username API username.
         * @param string|null $password API password.
         */
        public function __construct( $username = null, $password = null ) {
            if ( null === $username ) {
                $username = get_option( 'auspost_shipping_mypost_business_api_key' );
            }
            if ( null === $password ) {
                $password = get_option( 'auspost_shipping_mypost_business_api_secret' );
            }
            $this->username = (string) $username;
            $this->password = (string) $password;
        }

        /**
         * Retrieve contracted rates.
         *
         * @param array $shipment Shipment details.
         * @return array List of rates or empty array on failure.
         */
        public function get_rates( array $shipment ): array {
            $cache_key = 'auspost_contract_' . md5( wp_json_encode( $shipment ) );
            $cached = get_transient( $cache_key );
            if ( false !== $cached ) {
                return $cached;
            }

            $args = array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( $this->username . ':' . $this->password ),
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode( $shipment ),
                'timeout' => 15,
            );

            $response = wp_remote_post( $this->endpoint, $args );

            if ( is_wp_error( $response ) ) {
                if ( class_exists( 'Auspost_Shipping_Logger' ) ) {
                    Auspost_Shipping_Logger::log( $shipment, $response->get_error_message() );
                }
                return array();
            }

            $code = wp_remote_retrieve_response_code( $response );
            if ( 200 !== $code ) {
                if ( class_exists( 'Auspost_Shipping_Logger' ) ) {
                    Auspost_Shipping_Logger::log( $shipment, array( 'code' => $code, 'body' => wp_remote_retrieve_body( $response ) ) );
                }
                return array();
            }

            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( json_last_error() !== JSON_ERROR_NONE || empty( $body['prices'] ) ) {
                if ( class_exists( 'Auspost_Shipping_Logger' ) ) {
                    Auspost_Shipping_Logger::log( $shipment, wp_remote_retrieve_body( $response ) );
                }
                return array();
            }

            $rates = array();
            foreach ( $body['prices'] as $price ) {
                $rates[] = array(
                    'code'  => isset( $price['product']['code'] ) ? $price['product']['code'] : '',
                    'name'  => isset( $price['product']['description'] ) ? $price['product']['description'] : '',
                    'price' => isset( $price['total_price'] ) ? (float) $price['total_price'] : 0,
                );
            }

            set_transient( $cache_key, $rates, HOUR_IN_SECONDS );

            return $rates;
        }
    }
}
