<?php
/**
 * Simple logger for Auspost Shipping plugin.
 *
 * Stores API request and response data in a WordPress option so that it can be
 * reviewed from the plugin settings screen.
 *
 * @package    Auspost_Shipping
 * @subpackage Auspost_Shipping/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Auspost_Shipping_Logger' ) ) {
    /**
     * Basic logger class.
     */
    class Auspost_Shipping_Logger {
        /**
         * Option name used to persist the log entries.
         */
        const OPTION_NAME = 'auspost_shipping_log';

        /**
         * Append a log entry.
         *
         * @param mixed $request  Data sent to the API.
         * @param mixed $response Data received from the API.
         */
        public static function log( $request, $response ) {
            $logs   = get_option( self::OPTION_NAME, array() );
            $logs[] = array(
                'time'     => current_time( 'mysql' ),
                'request'  => $request,
                'response' => $response,
            );

            if ( count( $logs ) > 50 ) {
                array_shift( $logs );
            }

            update_option( self::OPTION_NAME, $logs, false );
        }

        /**
         * Retrieve all log entries.
         *
         * @return array
         */
        public static function get_logs() {
            return get_option( self::OPTION_NAME, array() );
        }

        /**
         * Remove all log entries.
         */
        public static function clear() {
            delete_option( self::OPTION_NAME );
        }
    }
}
