<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://paulmiller3000.com
 * @since      1.0.0
 *
 * @package    Auspost_Shipping
 * @subpackage Auspost_Shipping/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Auspost_Shipping
 * @subpackage Auspost_Shipping/includes
 * @author     Paul Miller <hello@paulmiller3000.com>
 */
class Auspost_Shipping {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Auspost_Shipping_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'AUSPOST_SHIPPING_VERSION' ) ) {
			$this->version = AUSPOST_SHIPPING_VERSION;
		} else {
			$this->version = '1.0.0';
		}
               $this->plugin_name = 'auspost-shipping';

               $this->load_dependencies();
               $this->set_locale();
               $this->define_admin_hooks();
               $this->define_public_hooks();
               $this->loader->add_filter( 'woocommerce_shipping_methods', $this, 'register_shipping_method' );

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Auspost_Shipping_Loader. Orchestrates the hooks of the plugin.
	 * - Auspost_Shipping_i18n. Defines internationalization functionality.
	 * - Auspost_Shipping_Admin. Defines all hooks for the admin area.
	 * - Auspost_Shipping_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-auspost-shipping-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-auspost-shipping-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
               require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-auspost-shipping-admin.php';
               require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-box-settings.php';

               /**
                * The class responsible for defining all actions that occur in the public-facing
                * side of the site.
                */
               require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-auspost-shipping-public.php';
               require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-rate-client-interface.php';
               require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-auspost-api.php';
               require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pac-rate-client.php';
               require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-contract-rate-client.php';
               require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-mypost-api.php';
               require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-auspost-shipping-logger.php';
               require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-auspost-shipping-method.php';
               require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-box-packer.php';

               $this->loader = new Auspost_Shipping_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Auspost_Shipping_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Auspost_Shipping_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

              $plugin_admin = new Auspost_Shipping_Admin( $this->get_plugin_name(), $this->get_version() );
              $box_settings = new Auspost_Box_Settings();

              $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
              $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

       // Add plugin settings to WooCommerce
       $this->loader->add_filter( 'woocommerce_get_settings_pages', $plugin_admin, 'ausps_add_settings' );

       // Box settings page
       $this->loader->add_action( 'admin_menu', $box_settings, 'register_menu' );
       $this->loader->add_action( 'admin_init', $box_settings, 'register_settings' );

       // MyPost label creation order actions.
       $this->loader->add_filter( 'woocommerce_order_actions', $plugin_admin, 'add_mypost_order_action' );
       $this->loader->add_action( 'woocommerce_order_action_mypost_create_label', $plugin_admin, 'process_mypost_create_label' );
       $this->loader->add_action( 'woocommerce_admin_order_data_after_shipping_address', $plugin_admin, 'display_mypost_meta' );

       // Bulk action for creating multiple labels.
       $this->loader->add_filter( 'bulk_actions-edit-shop_order', $plugin_admin, 'register_bulk_actions' );
       $this->loader->add_filter( 'handle_bulk_actions-edit-shop_order', $plugin_admin, 'handle_bulk_actions', 10, 3 );
       $this->loader->add_action( 'admin_notices', $plugin_admin, 'bulk_action_admin_notice' );

       }

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
        private function define_public_hooks() {

                $plugin_public = new Auspost_Shipping_Public( $this->get_plugin_name(), $this->get_version() );

                $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
                $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        }

       /**
        * Register the AusPost shipping method with WooCommerce.
        *
        * @param array $methods Existing shipping methods.
        * @return array Filtered shipping methods including AusPost.
        */
       public function register_shipping_method( $methods ) {
               $methods['auspost_shipping'] = 'Auspost_Shipping_Method';
               return $methods;
       }

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Auspost_Shipping_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
