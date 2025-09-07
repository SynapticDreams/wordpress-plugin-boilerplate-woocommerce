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
                    $settings = array(                              
                            array()
                    );
                    break;
                default:
                    include 'partials/auspost-shipping-settings-main.php';

                    $settings = array_merge(
                        $settings,
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