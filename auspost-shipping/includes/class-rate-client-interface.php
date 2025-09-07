<?php
/**
 * Interface for rate clients.
 *
 * Provides a consistent method for fetching shipping rates from
 * different AusPost services.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! interface_exists( 'Rate_Client_Interface' ) ) {
    /**
     * Defines a standard contract for rate clients.
     */
    interface Rate_Client_Interface {
        /**
         * Retrieve rates for a given shipment.
         *
         * @param array $shipment Shipment details.
         * @return array List of rates or empty array on failure.
         */
        public function get_rates( array $shipment ): array;
    }
}
