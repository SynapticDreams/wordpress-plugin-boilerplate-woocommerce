<?php
/**
 * Admin UI for defining shipping boxes.
 *
 * @package Auspost_Shipping\admin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Auspost_Box_Settings' ) ) {
    class Auspost_Box_Settings {
        /**
         * Register menu entry.
         */
        public function register_menu() {
            add_submenu_page(
                'woocommerce',
                __( 'Shipping Boxes', 'auspost-shipping' ),
                __( 'Shipping Boxes', 'auspost-shipping' ),
                'manage_woocommerce',
                'auspost-shipping-boxes',
                array( $this, 'render_page' )
            );
        }

        /**
         * Register setting.
         */
        public function register_settings() {
            register_setting( 'auspost_shipping_boxes_group', 'auspost_shipping_boxes' );
        }

        /**
         * Render admin page.
         */
        public function render_page() {
            if ( ! current_user_can( 'manage_woocommerce' ) ) {
                return;
            }

            if ( isset( $_POST['auspost_boxes_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['auspost_boxes_nonce'] ) ), 'auspost_boxes_save' ) ) {
                $boxes = array();
                if ( isset( $_POST['boxes'] ) && is_array( $_POST['boxes'] ) ) {
                    foreach ( $_POST['boxes'] as $box ) {
                        $length = isset( $box['length'] ) ? floatval( $box['length'] ) : 0;
                        $width  = isset( $box['width'] ) ? floatval( $box['width'] ) : 0;
                        $height = isset( $box['height'] ) ? floatval( $box['height'] ) : 0;
                        $max_w  = isset( $box['max_weight'] ) ? floatval( $box['max_weight'] ) : 0;
                        $pad    = isset( $box['padding'] ) ? floatval( $box['padding'] ) : 0;
                        if ( $length && $width && $height ) {
                            $boxes[] = array(
                                'length'     => $length,
                                'width'      => $width,
                                'height'     => $height,
                                'max_weight' => $max_w,
                                'padding'    => $pad,
                            );
                        }
                    }
                }
                update_option( 'auspost_shipping_boxes', $boxes );
                echo '<div class="updated"><p>' . esc_html__( 'Boxes saved.', 'auspost-shipping' ) . '</p></div>';
            }

            $boxes = get_option( 'auspost_shipping_boxes', array() );
            ?>
            <div class="wrap">
                <h1><?php esc_html_e( 'Shipping Boxes', 'auspost-shipping' ); ?></h1>
                <form method="post" action="">
                    <?php wp_nonce_field( 'auspost_boxes_save', 'auspost_boxes_nonce' ); ?>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Length (cm)', 'auspost-shipping' ); ?></th>
                                <th><?php esc_html_e( 'Width (cm)', 'auspost-shipping' ); ?></th>
                                <th><?php esc_html_e( 'Height (cm)', 'auspost-shipping' ); ?></th>
                                <th><?php esc_html_e( 'Max Weight (kg)', 'auspost-shipping' ); ?></th>
                                <th><?php esc_html_e( 'Padding (cm)', 'auspost-shipping' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( $boxes ) : ?>
                                <?php foreach ( $boxes as $index => $box ) : ?>
                                    <tr>
                                        <td><input type="number" step="0.01" name="boxes[<?php echo esc_attr( $index ); ?>][length]" value="<?php echo esc_attr( $box['length'] ); ?>" /></td>
                                        <td><input type="number" step="0.01" name="boxes[<?php echo esc_attr( $index ); ?>][width]" value="<?php echo esc_attr( $box['width'] ); ?>" /></td>
                                        <td><input type="number" step="0.01" name="boxes[<?php echo esc_attr( $index ); ?>][height]" value="<?php echo esc_attr( $box['height'] ); ?>" /></td>
                                        <td><input type="number" step="0.01" name="boxes[<?php echo esc_attr( $index ); ?>][max_weight]" value="<?php echo esc_attr( $box['max_weight'] ); ?>" /></td>
                                        <td><input type="number" step="0.01" name="boxes[<?php echo esc_attr( $index ); ?>][padding]" value="<?php echo esc_attr( $box['padding'] ); ?>" /></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <tr>
                                <td><input type="number" step="0.01" name="boxes[new][length]" /></td>
                                <td><input type="number" step="0.01" name="boxes[new][width]" /></td>
                                <td><input type="number" step="0.01" name="boxes[new][height]" /></td>
                                <td><input type="number" step="0.01" name="boxes[new][max_weight]" /></td>
                                <td><input type="number" step="0.01" name="boxes[new][padding]" /></td>
                            </tr>
                        </tbody>
                    </table>
                    <?php submit_button( __( 'Save Boxes', 'auspost-shipping' ) ); ?>
                </form>
            </div>
            <?php
        }
    }
}

