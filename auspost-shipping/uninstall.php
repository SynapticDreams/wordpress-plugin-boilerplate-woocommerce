<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://paulmiller3000.com
 * @since      1.0.0
 *
 * @package    Auspost_Shipping
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
        exit;
}

$option_names = array(
    'auspost_shipping_auspost_api_key',
    'auspost_shipping_pac_api_key',
    'auspost_shipping_mypost_business_api_key',
    'auspost_shipping_mypost_business_api_secret',
    'auspost_shipping_log',
    'woocommerce_auspost_shipping_settings',
);

/**
 * Delete plugin options for the current site.
 */
function auspost_shipping_delete_options() {
    global $option_names;

    foreach ( $option_names as $option_name ) {
        delete_option( $option_name );
    }
}

if ( is_multisite() ) {
    $site_ids = get_sites( array( 'fields' => 'ids' ) );
    foreach ( $site_ids as $site_id ) {
        switch_to_blog( $site_id );
        auspost_shipping_delete_options();
        restore_current_blog();
    }
} else {
    auspost_shipping_delete_options();
}
