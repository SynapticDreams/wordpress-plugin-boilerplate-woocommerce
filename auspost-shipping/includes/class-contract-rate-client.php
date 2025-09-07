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
         * Contract account number.
         *
         * @var string
         */
        protected $account_number;

        /**
         * API key for basic auth.
         *
         * @var string
         */
        protected $api_key;

        /**
         * API secret for basic auth.
         *
         * @var string
         */
        protected $api_secret;

        /**
         * Service codes to request. Includes both eParcel and StarTrack codes.
         *
         * @var array
         */
        protected $service_codes = array(
            'AUS_PARCEL_REGULAR',
            'AUS_PARCEL_EXPRESS',
            'EXP',
            'EXP_PLAT',
        );

        /**
         * Setup credentials.
         *
         * @param string|null $account_number Contract account number.
         * @param string|null $api_key        API key.
         * @param string|null $api_secret     API secret.
         */
        public function __construct( $account_number = null, $api_key = null, $api_secret = null ) {
            if ( null === $account_number ) {
                $account_number = get_option( 'auspost_shipping_contract_account_number' );
            }
            if ( null === $api_key ) {
                $api_key = get_option( 'auspost_shipping_contract_api_key' );
            }
            if ( null === $api_secret ) {
                $api_secret = get_option( 'auspost_shipping_contract_api_secret' );
            }

            $this->account_number = (string) $account_number;
            $this->api_key        = (string) $api_key;
            $this->api_secret     = (string) $api_secret;
        }

        /**
         * Retrieve contracted rates.
         *
         * @param array $shipment Shipment details.
         * @return array List of rates or empty array on failure.
         */
        public function get_rates( array $shipment ): array {
            if ( empty( $this->account_number ) || empty( $this->api_key ) || empty( $this->api_secret ) ) {
                if ( class_exists( 'Auspost_Shipping_Logger' ) ) {
                    Auspost_Shipping_Logger::log( $shipment, 'Missing contract credentials.' );
                }
                return array();
            }

            $cache_key = 'auspost_contract_' . md5( $this->account_number . wp_json_encode( $shipment ) );
            $cached    = get_transient( $cache_key );
            if ( false !== $cached ) {
                return $cached;
            }

            $payload = array(
                'account-number' => $this->account_number,
                'from'           => array( 'postcode' => $shipment['from_postcode'] ),
                'to'             => array( 'postcode' => $shipment['to_postcode'] ),
                'items'          => array(
                    array( 'weight' => $shipment['weight'] ),
                ),
                'service-codes'  => $this->service_codes,
            );

            $args = array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( $this->api_key . ':' . $this->api_secret ),
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode( $payload ),
                'timeout' => 15,
            );

            $response = wp_remote_post( $this->endpoint, $args );

            if ( is_wp_error( $response ) ) {
                if ( class_exists( 'Auspost_Shipping_Logger' ) ) {
                    Auspost_Shipping_Logger::log( $payload, $response->get_error_message() );
                }
                return array();
            }

            $code     = wp_remote_retrieve_response_code( $response );
            $body_raw = wp_remote_retrieve_body( $response );

            if ( class_exists( 'Auspost_Shipping_Logger' ) ) {
                Auspost_Shipping_Logger::log( $payload, array( 'code' => $code, 'body' => $body_raw ) );
            }

            if ( 401 === $code || 403 === $code ) {
                if ( class_exists( 'Auspost_Shipping_Logger' ) ) {
                    Auspost_Shipping_Logger::log( $payload, 'Invalid credentials' );
                }
                return array();
            }

            if ( 503 === $code ) {
                if ( class_exists( 'Auspost_Shipping_Logger' ) ) {
                    Auspost_Shipping_Logger::log( $payload, 'Service unavailable' );
                }
                return array();
            }

            if ( 200 !== $code ) {
                return array();
            }

            $body  = json_decode( $body_raw, true );
            $error = json_last_error();
            if ( JSON_ERROR_NONE !== $error || empty( $body['prices'] ) ) {
                if ( class_exists( 'Auspost_Shipping_Logger' ) ) {
                    Auspost_Shipping_Logger::log( $payload, $body_raw );
                }
                return array();
            }

            $rates = array();
            foreach ( $body['prices'] as $price ) {
                $code  = isset( $price['service-code'] ) ? $price['service-code'] : ( $price['product']['code'] ?? '' );
                $name  = isset( $price['service-name'] ) ? $price['service-name'] : ( $price['product']['description'] ?? '' );
                $cost  = isset( $price['total-cost'] ) ? $price['total-cost'] : ( $price['total_price'] ?? 0 );
                $rates[] = array(
                    'code'  => $code,
                    'name'  => $name,
                    'price' => (float) $cost,
                );
            }

            set_transient( $cache_key, $rates, HOUR_IN_SECONDS );

            return $rates;
        }
    }
}

