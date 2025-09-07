<?php
/**
 * Extends the WC_Settings_Page class
 *
 * @link        https://paulmiller3000.com
 * @since       1.0.0
 *
 * @package     Auspost_Shipping
 * @subpackage  Auspost_Shipping/admin
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'Auspost_Shipping_WC_Settings' ) ) {

    /**
     * Settings class
     *
     * @since 1.0.0
     */
    class Auspost_Shipping_WC_Settings extends WC_Settings_Page {

        /**
         * Constructor
         * @since  1.0
         */
        public function __construct() {
                
            $this->id    = 'auspost-shipping';
            $this->label = __( 'Auspost Shipping', 'auspost-shipping' );

            // Define all hooks instead of inheriting from parent                    
            add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
            add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );
            add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
            add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
            
        }


        /**
         * Get sections.
         *
         * @return array
         */
        public function get_sections() {
            $sections = array(
                '' => __( 'Settings', 'auspost-shipping' ),
                'log' => __( 'Log', 'auspost-shipping' ),
                'results' => __( 'Sale Results', 'auspost-shipping' )
            );

            return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
        }


        /**
         * Get settings array
         *
         * @return array
         */
        public function get_settings() {

            global $current_section;
            $prefix = 'auspost_shipping_';
            $settings = array();
            
            switch ($current_section) {
                case 'log':
                    $settings = array();
                    break;
                default:
                    $main_settings = require 'partials/auspost-shipping-settings-main.php';

                    $settings = array_merge(
                        $main_settings,
                        array(
                            array(
                                'name' => __( 'API Credentials', 'auspost-shipping' ),
                                'type' => 'title',
                                'id'   => $prefix . 'api_credentials',
                            ),
                            array(
                                'id'       => $prefix . 'auspost_api_key',
                                'name'     => __( 'AusPost API Key', 'auspost-shipping' ),
                                'type'     => 'text',
                                'desc_tip' => __( 'Key for accessing the AusPost API.', 'auspost-shipping' ),
                            ),
                            array(
                                'id'       => $prefix . 'mypost_business_api_key',
                                'name'     => __( 'MyPost Business API Key', 'auspost-shipping' ),
                                'type'     => 'text',
                                'desc_tip' => __( 'Key for accessing the MyPost Business API.', 'auspost-shipping' ),
                            ),
                            array(
                                'id'       => $prefix . 'mypost_business_api_secret',
                                'name'     => __( 'MyPost Business API Secret', 'auspost-shipping' ),
                                'type'     => 'password',
                                'desc_tip' => __( 'Secret for accessing the MyPost Business API.', 'auspost-shipping' ),
                            ),
                            array(
                                'id'       => $prefix . 'contract_account_number',
                                'name'     => __( 'Contract Account Number', 'auspost-shipping' ),
                                'type'     => 'text',
                                'desc_tip' => __( 'Account number for contracted AusPost accounts.', 'auspost-shipping' ),
                            ),
                            array(
                                'id'       => $prefix . 'contract_api_key',
                                'name'     => __( 'Contract API Key', 'auspost-shipping' ),
                                'type'     => 'text',
                                'desc_tip' => __( 'API key for contracted AusPost accounts.', 'auspost-shipping' ),
                            ),
                            array(
                                'id'       => $prefix . 'contract_api_secret',
                                'name'     => __( 'Contract API Secret', 'auspost-shipping' ),
                                'type'     => 'password',
                                'desc_tip' => __( 'API secret for contracted AusPost accounts.', 'auspost-shipping' ),
                            ),
                            array(
                                'name' => __( 'API Credentials', 'auspost-shipping' ),
                                'type' => 'sectionend',
                                'id'   => $prefix . 'api_credentials',
                            ),
                        )
                    );
            }

            return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );                   
        }

        /**
         * Output the settings
         */
        public function output() {
            global $current_section;

            switch ($current_section) {
                case 'log':
                    if ( isset( $_GET['auspost_shipping_clear_log'] ) && check_admin_referer( 'auspost_shipping_clear_log' ) ) {
                        if ( current_user_can( 'manage_woocommerce' ) ) {
                            Auspost_Shipping_Logger::clear();
                            echo '<div class="updated"><p>' . esc_html__( 'Log cleared.', 'auspost-shipping' ) . '</p></div>';
                        } else {
                            echo '<div class="error"><p>' . esc_html__( 'You do not have permission to clear the log.', 'auspost-shipping' ) . '</p></div>';
                        }
                    }
                    $logs = Auspost_Shipping_Logger::get_logs();
                    echo '<h2>' . esc_html__( 'API Request Log', 'auspost-shipping' ) . '</h2>';
                    if ( $logs ) {
                        echo '<table class="widefat"><thead><tr><th>' . esc_html__( 'Time', 'auspost-shipping' ) . '</th><th>' . esc_html__( 'Request', 'auspost-shipping' ) . '</th><th>' . esc_html__( 'Response', 'auspost-shipping' ) . '</th></tr></thead><tbody>';
                        foreach ( array_reverse( $logs ) as $log ) {
                            echo '<tr><td>' . esc_html( $log['time'] ) . '</td><td><pre>' . esc_html( print_r( $log['request'], true ) ) . '</pre></td><td><pre>' . esc_html( print_r( $log['response'], true ) ) . '</pre></td></tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p>' . esc_html__( 'No log entries found.', 'auspost-shipping' ) . '</p>';
                    }
                    $clear_url = wp_nonce_url( add_query_arg( array( 'auspost_shipping_clear_log' => 1 ) ), 'auspost_shipping_clear_log' );
                    echo '<p><a class="button" href="' . esc_url( $clear_url ) . '">' . esc_html__( 'Clear Log', 'auspost-shipping' ) . '</a></p>';
                    break;
                case 'results':
                    include 'partials/auspost-shipping-settings-results.php';
                    break;
                default:
                    $settings = $this->get_settings();
                    WC_Admin_Settings::output_fields( $settings );
            }
            
        }

        /**
         * Save settings
         *
         * @since 1.0
         */
        public function save() {                    
            $settings = $this->get_settings();

            WC_Admin_Settings::save_fields( $settings );
        }

    }

}


return new Auspost_Shipping_WC_Settings();