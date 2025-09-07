<?php
/**
 * Box packing utility.
 *
 * Accepts items with dimensions and weight and packs them into
 * configured boxes while respecting box limits. The final
 * package weight is the greater of the actual and dimensional
 * weights.
 *
 * @package Auspost_Shipping\includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Box_Packer' ) ) {
    class Box_Packer {
        /**
         * Available boxes.
         *
         * Each box is an associative array with keys:
         * length, width, height, max_weight, padding
         *
         * @var array
         */
        protected $boxes = array();

        /**
         * Items that could not be packed.
         *
         * @var array
         */
        protected $unpacked = array();

        /**
         * Constructor.
         *
         * @param array $boxes List of available boxes.
         */
        public function __construct( array $boxes ) {
            // sort boxes from smallest to largest volume
            usort( $boxes, function( $a, $b ) {
                $vol_a = $a['length'] * $a['width'] * $a['height'];
                $vol_b = $b['length'] * $b['width'] * $b['height'];
                return $vol_a <=> $vol_b;
            } );
            $this->boxes = $boxes;
        }

        /**
         * Pack items into boxes.
         *
         * @param array $items Items to pack. Each item should have
         *                     length, width, height, weight, qty.
         * @return array Packed packages with length, width, height, weight.
         */
        public function pack( array $items ) {
            $packages = array();
            $this->unpacked = array();

            foreach ( $items as $item ) {
                $qty = isset( $item['qty'] ) ? (int) $item['qty'] : 1;
                for ( $i = 0; $i < $qty; $i++ ) {
                    $single = $item;
                    $single['qty'] = 1;
                    if ( ! $this->place_item_in_packages( $single, $packages ) ) {
                        if ( ! $this->start_new_package( $single, $packages ) ) {
                            $this->unpacked[] = $single;
                        }
                    }
                }
            }

            // Finalise packages and compute dimensional weight
            foreach ( $packages as &$pkg ) {
                $box = $pkg['box'];
                $pkg['length'] = $box['length'];
                $pkg['width']  = $box['width'];
                $pkg['height'] = $box['height'];
                $dim_weight = ( $box['length'] * $box['width'] * $box['height'] ) / 5000; // cm^3 to kg
                $pkg['weight'] = max( $pkg['weight'], $dim_weight );
                unset( $pkg['box'], $pkg['used_volume'] );
            }

            return $packages;
        }

        /**
         * Get unpacked items.
         *
         * @return array
         */
        public function get_unpacked_items() {
            return $this->unpacked;
        }

        /**
         * Try to place an item into existing packages.
         *
         * @param array $item     Item data.
         * @param array &$packages Current packages.
         * @return bool True if placed.
         */
        protected function place_item_in_packages( $item, &$packages ) {
            foreach ( $packages as &$pkg ) {
                $box = $pkg['box'];
                $inner_length = $box['length'] - 2 * $box['padding'];
                $inner_width  = $box['width']  - 2 * $box['padding'];
                $inner_height = $box['height'] - 2 * $box['padding'];
                $item_vol = $item['length'] * $item['width'] * $item['height'];
                $box_vol  = $inner_length * $inner_width * $inner_height;

                if ( $item['length'] <= $inner_length &&
                     $item['width']  <= $inner_width &&
                     $item['height'] <= $inner_height &&
                     $pkg['weight'] + $item['weight'] <= $box['max_weight'] &&
                     $pkg['used_volume'] + $item_vol <= $box_vol ) {
                    $pkg['weight']      += $item['weight'];
                    $pkg['used_volume'] += $item_vol;
                    return true;
                }
            }
            return false;
        }

        /**
         * Start a new package with the smallest fitting box.
         *
         * @param array $item Item data.
         * @param array &$packages Packages array.
         * @return bool True if package created.
         */
        protected function start_new_package( $item, &$packages ) {
            foreach ( $this->boxes as $box ) {
                $inner_length = $box['length'] - 2 * $box['padding'];
                $inner_width  = $box['width']  - 2 * $box['padding'];
                $inner_height = $box['height'] - 2 * $box['padding'];

                if ( $item['length'] <= $inner_length &&
                     $item['width']  <= $inner_width &&
                     $item['height'] <= $inner_height &&
                     $item['weight'] <= $box['max_weight'] ) {
                    $pkg = array(
                        'box'         => $box,
                        'weight'      => $item['weight'],
                        'used_volume' => $item['length'] * $item['width'] * $item['height'],
                    );
                    $packages[] = $pkg;
                    return true;
                }
            }
            return false;
        }
    }
}

