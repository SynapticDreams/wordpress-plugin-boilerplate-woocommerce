<?php
/**
 * Client for the public AusPost Postage Assessment Calculator (PAC) API.
 *
 * Utilises the existing Auspost_API helper for making requests and
 * parsing responses.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Pac_Rate_Client' ) ) {
    /**
     * PAC rate client implementing the Rate_Client_Interface.
     */
    class Pac_Rate_Client extends Auspost_API implements Rate_Client_Interface {
        /**
         * Constructor.
         *
         * @param string|null $api_key API key for PAC requests.
         */
        public function __construct( $api_key = null ) {
            if ( null === $api_key ) {
                $api_key = get_option( 'auspost_shipping_pac_api_key' );
            }

            parent::__construct( $api_key );
        }

        /**
         * Retrieve rates from the PAC service.
         *
         * Logs any WP_Error using the Auspost_Shipping_Logger and
         * normalises the response to always be an array.
         *
         * @param array $shipment Shipment details for the request.
         * @return array List of rate arrays or empty array on failure.
         */
        public function get_rates( array $shipment ): array {
            if ( empty( $this->api_key ) ) {
                if ( class_exists( 'Auspost_Shipping_Logger' ) ) {
                    Auspost_Shipping_Logger::log( $shipment, 'Missing PAC API key.' );
                }
                return array();
            }

            $rates = parent::get_rates( $shipment );

            if ( is_wp_error( $rates ) ) {
                return array();
            }

            return $rates;
        }
    }
}
