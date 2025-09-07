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
         * Builds a request to the AusPost API based on package
         * information and maps the returned rates to WooCommerce
         * shipping rate objects.
         *
         * @param array $package Shipping package data.
         */
        public function calculate_shipping( $package = array() ) {
            $from_postcode       = WC()->countries->get_base_postcode();
            $destination_country = isset( $package['destination']['country'] ) ? $package['destination']['country'] : WC()->countries->get_base_country();
            $to_postcode         = isset( $package['destination']['postcode'] ) ? wc_format_postcode( $package['destination']['postcode'], $destination_country ) : '';
            $weight              = isset( $package['contents_weight'] ) ? $package['contents_weight'] : 0;

            if ( ! WC_Validation::is_postcode( $to_postcode, $destination_country ) ) {
                if ( class_exists( 'Auspost_Shipping_Logger' ) ) {
                    Auspost_Shipping_Logger::log(
                        array(
                            'from_postcode' => $from_postcode,
                            'to_postcode'   => $to_postcode,
                            'weight'        => $weight,
                        ),
                        'Missing or invalid destination postcode.'
                    );
                }
                return;
            }

            if ( ! is_numeric( $weight ) || $weight <= 0 ) {
                return;
            }

            $weight = wc_get_weight( $weight, 'kg' );

            $api   = new Auspost_API();
            $rates = $api->get_rates(
                array(
                    'from_postcode' => $from_postcode,
                    'to_postcode'   => $to_postcode,
                    'weight'        => $weight,
                )
            );

            if ( is_wp_error( $rates ) || empty( $rates ) ) {
                return;
            }

            foreach ( $rates as $rate ) {
                $this->add_rate(
                    array(
                        'id'      => $this->id . ':' . $rate['code'],
                        'label'   => $rate['name'],
                        'cost'    => $rate['price'],
                        'package' => $package,
                    )
                );
            }
        }
    }
}

