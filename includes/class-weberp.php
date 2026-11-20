<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.interactive-design.gr
 * @since      1.0.0
 *
 * @package    Weberp
 * @subpackage Weberp/includes
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
 * @package    Weberp
 * @subpackage Weberp/includes
 * @author     George Nikolopoulos <georgen@interactive-design.gr>
 */
class Weberp
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Weberp_Loader    $loader    Maintains and registers all hooks for the plugin.
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
		if ( defined( 'WEBERP_VERSION' ) ) {
			$this->version = WEBERP_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'weberp';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Weberp_Loader. Orchestrates the hooks of the plugin.
	 * - Weberp_i18n. Defines internationalization functionality.
	 * - Weberp_Admin. Defines all hooks for the admin area.
	 * - Weberp_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{
		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-weberp-loader.php';

		/**
		 * The class responsible for communicating with the WebERP API
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-weberp-api.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-weberp-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-weberp-public.php';

		$this->loader = new Weberp_Loader();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{
		$plugin_admin = new Weberp_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'create_plugin_settings_page' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'setup_sections' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'setup_fields' );

		$this->loader->add_action( 'rest_api_init', $plugin_admin, 'setup_endpoint' );

		$this->loader->add_action( 'woocommerce_product_options_general_product_data', $plugin_admin, 'add_product_custom_fields' );
		$this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'save_product_custom_fields');

		$this->loader->add_action( 'save_post_product', $plugin_admin, 'update_product', 20, 3 );
		$this->loader->add_action( 'saved_product_cat', $plugin_admin, 'update_category', 20, 3 );

		$this->loader->add_action( 'woocommerce_checkout_order_created', $plugin_admin, 'new_order', 20 );
		$this->loader->add_action( 'woocommerce_store_api_checkout_order_processed', $plugin_admin, 'new_order', 20 );
		$this->loader->add_action( 'woocommerce_order_status_completed', $plugin_admin, 'complete_order', 20, 3 );
		$this->loader->add_action( 'woocommerce_order_status_cancelled', $plugin_admin, 'cancel_order', 20, 3 );
		$this->loader->add_action( 'woocommerce_order_status_failed', $plugin_admin, 'cancel_order', 20, 3 );

		$this->loader->add_action( 'woocommerce_order_actions', $plugin_admin, 'action_send_order_to_weberp', 10, 2 );
		$this->loader->add_action( 'woocommerce_order_action_send_order_to_weberp', $plugin_admin, 'send_order_to_weberp' );

		$this->loader->add_action( 'woocommerce_attribute_added', $plugin_admin, 'attribute_added', 10, 2 );
		$this->loader->add_action( 'woocommerce_attribute_updated', $plugin_admin, 'attribute_updated', 10, 3 );
		$this->loader->add_action( 'woocommerce_attribute_deleted', $plugin_admin, 'attribute_deleted', 10, 3 );

		$this->loader->add_action( 'create_term', $plugin_admin, 'term_created', 10, 4 );
		$this->loader->add_action( 'edited_terms', $plugin_admin, 'term_updated', 10, 3 );
		$this->loader->add_action( 'delete_term', $plugin_admin, 'term_deleted', 10, 5 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{
		$plugin_public = new Weberp_Public( $this->get_plugin_name(), $this->get_version() );

		if (get_option('enable_live_stock')) {
			$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );

			if (get_option('live_stock_type') == 'link') {
				$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
				$this->loader->add_action( 'woocommerce_before_add_to_cart_form', $plugin_public, 'add_stock_container' );
				$this->loader->add_filter( 'woocommerce_get_availability_text', $plugin_public, 'add_stock_link', 10, 2 );
			} else {
				$this->loader->add_action( 'woocommerce_before_add_to_cart_form', $plugin_public, 'add_stock_table' );
			}

			$this->loader->add_action( 'wp_ajax_get_live_stock', $plugin_public, 'get_live_stock' );
			$this->loader->add_action( 'wp_ajax_nopriv_get_live_stock', $plugin_public, 'get_live_stock' );
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Weberp_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}

}
