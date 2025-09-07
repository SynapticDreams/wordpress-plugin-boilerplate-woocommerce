<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://paulmiller3000.com
 * @since      1.0.0
 *
 * @package    Auspost_Shipping
 * @subpackage Auspost_Shipping/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Auspost_Shipping
 * @subpackage Auspost_Shipping/admin
 * @author     Paul Miller <hello@paulmiller3000.com>
 */
class Auspost_Shipping_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Auspost_Shipping_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Auspost_Shipping_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/auspost-shipping-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Auspost_Shipping_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Auspost_Shipping_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/auspost-shipping-admin.js', array( 'jquery' ), $this->version, false );

	}

    /**
    * Load dependencies for additional WooCommerce settings
    *
    * @since    1.0.0
    * @access   private
    */
    public function ausps_add_settings( $settings ) {
        $settings[] = include plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-auspost-shipping-wc-settings.php';

        return $settings;
    }

    /**
     * Add MyPost label creation to order actions dropdown.
     *
     * @param array $actions Existing actions.
     * @return array
     */
    public function add_mypost_order_action( $actions ) {
        $actions['mypost_create_label'] = __( 'Create MyPost Label', 'auspost-shipping' );
        return $actions;
    }

    /**
     * Handle MyPost label creation for an order.
     *
     * @param WC_Order $order Order object.
     */
    public function process_mypost_create_label( $order ) {
        $api_key    = get_option( 'auspost_shipping_mypost_business_api_key' );
        $api_secret = get_option( 'auspost_shipping_mypost_business_api_secret' );

        $api = new MyPost_API( $api_key, $api_secret );

        $payload = array(
            'order_id' => $order->get_id(),
        );

        $result = $api->create_label( $payload );

        if ( is_wp_error( $result ) ) {
            $order->add_order_note( sprintf( __( 'MyPost label error: %s', 'auspost-shipping' ), $result->get_error_message() ) );
            return;
        }

        $order->update_meta_data( '_mypost_label_url', $result['label_url'] );
        $order->update_meta_data( '_mypost_tracking_number', $result['tracking_number'] );
        $order->save();

        $order->add_order_note( __( 'MyPost label created successfully.', 'auspost-shipping' ) );
    }

    /**
     * Display MyPost label download link and tracking in order meta.
     *
     * @param WC_Order $order Order object.
     */
    public function display_mypost_meta( $order ) {
        $label_url       = $order->get_meta( '_mypost_label_url' );
        $tracking_number = $order->get_meta( '_mypost_tracking_number' );

        if ( $label_url ) {
            echo '<p><a href="' . esc_url( $label_url ) . '" target="_blank">' . esc_html__( 'Download MyPost Label', 'auspost-shipping' ) . '</a></p>';
        }

        if ( $tracking_number ) {
            echo '<p>' . sprintf( esc_html__( 'MyPost Tracking: %s', 'auspost-shipping' ), esc_html( $tracking_number ) ) . '</p>';
        }
    }

    /**
     * Register bulk action for creating MyPost labels.
     *
     * @param array $bulk_actions Existing bulk actions.
     * @return array
     */
    public function register_bulk_actions( $bulk_actions ) {
        $bulk_actions['mypost_create_labels'] = __( 'Create MyPost Labels', 'auspost-shipping' );
        return $bulk_actions;
    }

    /**
     * Handle bulk action for label creation.
     *
     * @param string $redirect_to Redirect URL.
     * @param string $action      Current action.
     * @param array  $order_ids   Order IDs.
     * @return string
     */
    public function handle_bulk_actions( $redirect_to, $action, $order_ids ) {
        if ( 'mypost_create_labels' !== $action ) {
            return $redirect_to;
        }

        $processed = 0;
        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( $order_id );
            if ( ! $order ) {
                continue;
            }
            $this->process_mypost_create_label( $order );
            $processed++;
        }

        $redirect_to = add_query_arg( 'mypost_labels_created', $processed, $redirect_to );
        return $redirect_to;
    }

    /**
     * Display admin notice after bulk action completes.
     */
    public function bulk_action_admin_notice() {
        if ( empty( $_REQUEST['mypost_labels_created'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        $count = (int) $_REQUEST['mypost_labels_created']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        printf( '<div class="updated notice"><p>%s</p></div>', esc_html( sprintf( _n( '%s label created.', '%s labels created.', $count, 'auspost-shipping' ), $count ) ) );
    }

}
