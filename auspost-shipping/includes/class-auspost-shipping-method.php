<?php
/**
 * WooCommerce AusPost Shipping Method.
 *
 * Provides shipping rate calculations using the AusPost API.
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

if ( ! class_exists( 'Auspost_Shipping_Method' ) ) {

    /**
     * AusPost shipping method class.
     */
    class Auspost_Shipping_Method extends WC_Shipping_Method {

        /**
         * Constructor.
         */
        public function __construct() {
            $this->id                 = 'auspost_shipping';
            $this->method_title       = __( 'AusPost', 'auspost-shipping' );
            $this->method_description = __( 'Calculate shipping rates using AusPost.', 'auspost-shipping' );
            $this->availability       = 'including';
            $this->countries          = array( 'AU' );

            $this->init_form_fields();
            $this->init_settings();

            $this->title   = $this->get_option( 'title', __( 'AusPost Shipping', 'auspost-shipping' ) );
            $this->enabled = $this->get_option( 'enabled', 'yes' );

            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        /**
         * Initialise form fields for the settings page.
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Enable', 'auspost-shipping' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable AusPost shipping', 'auspost-shipping' ),
                    'default' => 'yes',
                ),
                'title'   => array(
                    'title'       => __( 'Method Title', 'auspost-shipping' ),
                    'type'        => 'text',
                    'description' => __( 'Title to be shown during checkout.', 'auspost-shipping' ),
                    'default'     => __( 'AusPost Shipping', 'auspost-shipping' ),
                    'desc_tip'    => true,
                ),
            );
        }

        /**
         * Calculate shipping for a package.
         *
         * @param array $package Shipping package data.
         */
        public function calculate_shipping( $package = array() ) {
            $rates = apply_filters( 'auspost_shipping_calculate_rates', array(), $package );

            if ( empty( $rates ) ) {
                return;
            }

            foreach ( $rates as $rate ) {
                $this->add_rate( $rate );
            }
        }
    }
}

