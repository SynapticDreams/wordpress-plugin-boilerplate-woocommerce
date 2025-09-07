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
         * Rate client instance.
         *
         * @var Rate_Client_Interface|null
         */
        protected $rate_client = null;

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
                'pac_api_key' => array(
                    'title'       => __( 'PAC API Key', 'auspost-shipping' ),
                    'type'        => 'text',
                    'description' => __( 'Key for accessing the AusPost PAC API.', 'auspost-shipping' ),
                    'default'     => get_option( 'auspost_shipping_pac_api_key', '' ),
                    'desc_tip'    => true,
                ),
            );
        }

        /**
         * Validate and save PAC API key field.
         *
         * @param string $key   Field key.
         * @param string $value Submitted value.
         * @return string Sanitized value.
         */
        public function validate_pac_api_key_field( $key, $value ) {
            $value = sanitize_text_field( $value );

            if ( empty( $value ) ) {
                \WC_Admin_Settings::add_error( __( 'PAC API key is required.', 'auspost-shipping' ) );
            } elseif ( ! preg_match( '/^[a-zA-Z0-9]+$/', $value ) ) {
                \WC_Admin_Settings::add_error( __( 'PAC API key appears invalid.', 'auspost-shipping' ) );
            } else {
                \WC_Admin_Settings::add_message( __( 'PAC API key saved.', 'auspost-shipping' ) );
            }

            update_option( 'auspost_shipping_pac_api_key', $value );

            return $value;
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
            $boxes               = get_option( 'auspost_shipping_boxes', array() );

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

            if ( ! empty( $boxes ) && ! empty( $package['contents'] ) ) {
                $items = array();
                foreach ( $package['contents'] as $item ) {
                    $qty = isset( $item['quantity'] ) ? (int) $item['quantity'] : 1;
                    if ( isset( $item['data'] ) && is_object( $item['data'] ) ) {
                        $product = $item['data'];
                        $length  = wc_get_dimension( method_exists( $product, 'get_length' ) ? $product->get_length() : 0, 'cm' );
                        $width   = wc_get_dimension( method_exists( $product, 'get_width' ) ? $product->get_width() : 0, 'cm' );
                        $height  = wc_get_dimension( method_exists( $product, 'get_height' ) ? $product->get_height() : 0, 'cm' );
                        $w       = wc_get_weight( method_exists( $product, 'get_weight' ) ? $product->get_weight() : 0, 'kg' );
                    } else {
                        $length = wc_get_dimension( isset( $item['length'] ) ? $item['length'] : 0, 'cm' );
                        $width  = wc_get_dimension( isset( $item['width'] ) ? $item['width'] : 0, 'cm' );
                        $height = wc_get_dimension( isset( $item['height'] ) ? $item['height'] : 0, 'cm' );
                        $w      = wc_get_weight( isset( $item['weight'] ) ? $item['weight'] : 0, 'kg' );
                    }
                    $items[] = array(
                        'length' => (float) $length,
                        'width'  => (float) $width,
                        'height' => (float) $height,
                        'weight' => (float) $w,
                        'qty'    => $qty,
                    );
                }

                $packer   = new Box_Packer( $boxes );
                $packages = $packer->pack( $items );
                if ( $packer->get_unpacked_items() ) {
                    return; // Unable to pack all items
                }

                $totals = array();
                foreach ( $packages as $pkg ) {
                    $shipment = array(
                        'from_postcode' => $from_postcode,
                        'to_postcode'   => $to_postcode,
                        'weight'        => wc_get_weight( $pkg['weight'], 'kg' ),
                        'length'        => $pkg['length'],
                        'width'         => $pkg['width'],
                        'height'        => $pkg['height'],
                    );
                    $rates = $this->get_rate_client()->get_rates( $shipment );
                    foreach ( $rates as $rate ) {
                        if ( isset( $totals[ $rate['code'] ] ) ) {
                            $totals[ $rate['code'] ]['price'] += $rate['price'];
                        } else {
                            $totals[ $rate['code'] ] = $rate;
                        }
                    }
                }

                if ( empty( $totals ) ) {
                    return;
                }

                foreach ( $totals as $rate ) {
                    $this->add_rate(
                        array(
                            'id'      => $this->id . ':' . $rate['code'],
                            'label'   => $rate['name'],
                            'cost'    => $rate['price'],
                            'package' => $package,
                        )
                    );
                }
                return;
            }

            if ( ! is_numeric( $weight ) || $weight <= 0 ) {
                return;
            }

            $weight = wc_get_weight( $weight, 'kg' );

            $rates = $this->get_rate_client()->get_rates(
                array(
                    'from_postcode' => $from_postcode,
                    'to_postcode'   => $to_postcode,
                    'weight'        => $weight,
                )
            );

            if ( empty( $rates ) ) {
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

        /**
         * Set a custom rate client instance.
         *
         * Primarily used for unit testing to inject a mock client.
         *
         * @param Rate_Client_Interface $client Rate client instance.
         */
        public function set_rate_client( Rate_Client_Interface $client ) {
            $this->rate_client = $client;
        }

        /**
         * Retrieve the rate client based on plugin settings.
         *
         * @return Rate_Client_Interface
         */
        protected function get_rate_client() {
            if ( $this->rate_client ) {
                return $this->rate_client;
            }

            $contract_account = get_option( 'auspost_shipping_contract_account_number' );
            $contract_key     = get_option( 'auspost_shipping_contract_api_key' );
            $contract_secret  = get_option( 'auspost_shipping_contract_api_secret' );

            if ( $contract_account && $contract_key && $contract_secret ) {
                $this->rate_client = new Contract_Rate_Client( $contract_account, $contract_key, $contract_secret );
            } else {
                $this->rate_client = new Pac_Rate_Client();
            }

            return $this->rate_client;
        }
    }
}

