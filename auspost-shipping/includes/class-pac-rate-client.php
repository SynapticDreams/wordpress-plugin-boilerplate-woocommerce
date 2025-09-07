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
         * Retrieve rates from the PAC service.
         *
         * Logs any WP_Error using the Auspost_Shipping_Logger and
         * normalises the response to always be an array.
         *
         * @param array $shipment Shipment details for the request.
         * @return array List of rate arrays or empty array on failure.
         */
        public function get_rates( array $shipment ): array {
            $rates = parent::get_rates( $shipment );

            if ( is_wp_error( $rates ) ) {
                if ( class_exists( 'Auspost_Shipping_Logger' ) ) {
                    Auspost_Shipping_Logger::log( $shipment, $rates->get_error_message() );
                }
                return array();
            }

            return $rates;
        }
    }
}
