<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://paulmiller3000.com
 * @since             1.0.0
 * @package           Auspost_Shipping
 *
 * @wordpress-plugin
 * Plugin Name:       Auspost Shipping
 * Plugin URI:        https://github.com/paulmiller3000/wordpress-plugin-boilerplate-woocommerce
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Paul Miller
 * Author URI:        https://paulmiller3000.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       auspost-shipping
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'AUSPOST_SHIPPING_VERSION', '1.0.0' );

if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . '/wp-admin/includes/plugin.php');
}

/**
* Display a message advising WooCommerce is required
*/
function ausps_missing_wc_notice() {
    $class = 'notice notice-error';
    $message = __( 'Auspost Shipping requires WooCommerce to be installed and active.', 'auspost-shipping' );
 
    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-auspost-shipping-activator.php
 */
function activate_auspost_shipping() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-auspost-shipping-activator.php';
    Auspost_Shipping_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-auspost-shipping-deactivator.php
 */
function deactivate_auspost_shipping() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-auspost-shipping-deactivator.php';
        Auspost_Shipping_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_auspost_shipping' );
register_deactivation_hook( __FILE__, 'deactivate_auspost_shipping' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-auspost-shipping.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function ausps_check_requirements() {
    if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        $plugin = new Auspost_Shipping();
        $plugin->run();

        return true;
    }

    add_action( 'admin_notices', 'ausps_missing_wc_notice' );

    return false;
}

add_action( 'plugins_loaded', 'ausps_check_requirements' );
