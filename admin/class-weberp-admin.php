<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.interactive-design.gr
 * @since      1.0.0
 *
 * @package    Weberp
 * @subpackage Weberp/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Weberp
 * @subpackage Weberp/admin
 * @author     George Nikolopoulos <georgen@interactive-design.gr>
 */
class Weberp_Admin
{

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
	 * API helper Class
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $obj    API Helper class
	 */
	private $api;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version )
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->api = new Weberp_Api();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/weberp-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/weberp-admin.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Add the plugin settings page to WP menu at the very bottom
	 *
	 * @since    1.0.0
	 */
	public function create_plugin_settings_page()
	{
    $page_title = 'WebERP';
    $menu_title = 'WebERP';
    $capability = 'manage_options';
    $slug = 'weberp_fields';
    $callback = array( $this, 'plugin_settings_page_content' );
    $icon = 'dashicons-admin-plugins';
    $position = 100;

    add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
	}

	/**
	 * Include the menu page template.
	 * Template includes code to render the fields on the page
	 *
	 * @since    1.0.0
	 */
	public function plugin_settings_page_content()
	{
	  require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/weberp-admin-display.php';
	}

	/**
	 * Add the sections for the settings page
	 *
	 * @since    1.0.0
	 */
	public function setup_sections()
	{
	  add_settings_section( 'general', 'General', false, 'weberp_fields' );
	}

	/**
	 * Add the fields to their respective sections
	 * Array fields:
	 *		uid 			Field id
	 *		label 			Field name/label
	 * 		section 		Section name the field belongs to
	 * 		type			One of: text,textarea,select
	 *		options 		Array of options for select fields in key => value format
	 * 		placeholder 	Placeholder text
	 *		helper 			Help text that show up next to the field
	 *		supplemental	Text that shows up under each field
	 * 		default 		Default text/value
	 *
	 * @since    1.0.0
	 */
	public function setup_fields()
	{
		// get_option('option_name') to get option value
    $fields = array(
    	array(
        'uid' => 'api_url',
        'label' => 'API Base URL',
        'section' => 'general',
        'type' => 'text',
        'placeholder' => '',
        'helper' => 'Your installation\'s API url',
        'supplemental' => 'Include http(s) in the url',
        'default' => ''
	    ),
      array(
        'uid' => 'api_username',
        'label' => 'API Username',
        'section' => 'general',
        'type' => 'text',
        'placeholder' => '',
        'helper' => 'Account email, unless advised otherwise',
        'supplemental' => 'Case sensitive',
        'default' => ''
      ),
      array(
        'uid' => 'api_password',
        'label' => 'API Password',
        'section' => 'general',
        'type' => 'password',
        'placeholder' => '',
        'helper' => '',
        'supplemental' => 'Case sensitive',
        'default' => ''
	    ),
	    array(
        'uid' => 'enable_logging',
        'label' => 'Enable Logging?',
        'section' => 'general',
        'type' => 'select',
        'options' => [
        	0 => 'No',
        	1 => 'Yes',
        ],
        'placeholder' => '',
        'helper' => '',
        'supplemental' => '',
        'default' => ''
	    ),
	    array(
        'uid' => 'enable_live_stock',
        'label' => 'Enable Live Stock Tracking?',
        'section' => 'general',
        'type' => 'select',
        'options' => [
        	0 => 'No',
        	1 => 'Yes',
        ],
        'placeholder' => '',
        'helper' => '',
        'supplemental' => '',
        'default' => '0'
	    ),
	    array(
        'uid' => 'live_stock_type',
        'label' => 'Live Stock Type',
        'section' => 'general',
        'type' => 'select',
        'options' => [
        	'link' => 'Clickable Link',
        	'inline' => 'Inline Table',
        ],
        'placeholder' => '',
        'helper' => '',
        'supplemental' => '',
        'default' => 'link'
	    )
    );

    foreach( $fields as $field ){
      add_settings_field( $field['uid'], $field['label'], array( $this, 'field_callback' ), 'weberp_fields', $field['section'], $field );
      register_setting( 'weberp_fields', $field['uid'] );
    }
	}

	/**
	 * Setup a new REST API endpoint to check media files
	 * Endpoint is '/wp-json/weberp/v1/media'
	 *
	 * @return void
	 */
	public function setup_endpoint()
	{
		register_rest_route('weberp/v1', '/media', [
      'methods'             => WP_REST_Server::READABLE,
      'callback'            => [$this, 'weberp_find_media_by_filename'],
      'permission_callback' => '__return_true',
      'args'                => [
        'filename' => [
          'required'          => true,
          'type'              => 'string',
          'sanitize_callback' => 'sanitize_file_name',
          'validate_callback' => function ($value) {
            return is_string($value) && $value !== '';
          },
        ],
      ],
    ]);
	}

	/**
	 * Function that renders each field on the page, based on its options
	 *
	 * @since    1.0.0
	 */
	public function field_callback( $arguments )
	{
    $value = get_option( $arguments['uid'] ); // Get the current value, if there is one
    if( ! $value ) { // If no value exists
      $value = $arguments['default']; // Set to our default
    }

    // Check which type of field we want
    switch( $arguments['type'] ) {
    	case 'password':
      case 'text':
        printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
        break;
      case 'textarea':
        printf( '<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', $arguments['uid'], $arguments['placeholder'], $value );
        break;
	    case 'select':
        if( ! empty ( $arguments['options'] ) && is_array( $arguments['options'] ) ){
          $options_markup = '';
          foreach( $arguments['options'] as $key => $label ){
            $options_markup .= sprintf( '<option value="%s" %s>%s</option>', $key, selected( $value, $key, false ), $label );
          }
          printf( '<select name="%1$s" id="%1$s">%2$s</select>', $arguments['uid'], $options_markup );
        }
        break;
    }

    // If there is help text
    if( $helper = $arguments['helper'] ){
      printf( '<span class="helper"> %s</span>', $helper ); // Show it
    }

    // If there is supplemental text
    if( $supplimental = $arguments['supplemental'] ){
      printf( '<p class="description">%s</p>', $supplimental ); // Show it
    }
	}

	/**
	 * Add a custom barcode field
	 *
	 * @return void
	 */
	public function add_product_custom_fields()
	{
		global $woocommerce, $post;
		echo '<div class="product_custom_field barcode">';
		
		woocommerce_wp_text_input([
      'id'          => '_barcode',
      'label'       => __( 'Barcode', 'weberp' ),
      'placeholder' => 'Barcode',
      'desc_tip'    => 'true'
	  ]);

		echo '</div>';
	}

	/**
	 * Update or save the barcode field text
	 *
	 * @param  int $post_id Post/Product id
	 * @return void
	 */
	public function save_product_custom_fields($post_id)
	{
		$barcode_field = $_POST['_barcode'];
  	if (!empty($barcode_field)) {
      update_post_meta($post_id, '_barcode', esc_attr($barcode_field));
  	}
	}

	/**
	 * Update a product using WebERP API
	 *
	 * @param  int $post_id Post/Product id
	 * @param  obj $post    Post/Product Object
	 * @param  int $update  1 if updating, 0 if creating
	 * @return void
	 */
	public function update_product($post_id, $post, $update)
	{
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
	    return;
	  }

	  if($update) {
	  	$this->api->updateProduct($post_id);
	  }
	}

	/**
	 * Update category using WebERP API
	 *
	 * @param  int    $term_id        Term id
	 * @param  int    $taxonomy_id    Taxonomy id
	 * @param  int    $update         1 for update
	 * @return void
	 */
	public function update_category($term_id, $taxonomy_id, $update)
	{
		if ($update) {
			$this->api->updateCategory($term_id);
		} else {
			$this->api->createCategory($term_id);
		}
	}

	/**
	 * Create a new order using WebERP API
	 *
	 * @link   https://wp-kama.com/plugin/woocommerce/hook/woocommerce_thankyou
	 *
	 * @param  int    $order_id    Order id to create
	 * @return void
	 */
	public function new_order($order_id)
	{
		$this->api->createOrder($order_id);
	}

	/**
	 * Mark an order as completed using WebERP API
	 *
	 * @link   https://wp-kama.com/plugin/woocommerce/hook/woocommerce_order_status_(to)
	 *
	 * @param  int         $order_id             Order id to update
	 * @param  WC_Order    $order                Order object.
	 * @param  array       $status_transition    Status transition data.
	 * @return void
	 */
	public function complete_order($order_id, $order, $status_transition)
	{
		$this->api->completeOrder($order_id);
	}

	/**
	 * Cancel/Delete and order using WebERP API
	 *
	 * @link   https://wp-kama.com/plugin/woocommerce/hook/woocommerce_order_status_(to)
	 *
	 * @param  int         $order_id             Order id to delete
	 * @param  WC_Order    $order                Order object.
	 * @param  array       $status_transition    Status transition data.
	 * @return void
	 */
	public function cancel_order($order_id, $order, $status_transition)
	{
		$this->api->cancelOrder($order_id);
	}

	/**
	 * Add custom action to order actions dropdown
	 *
	 * @link   https://wp-kama.com/plugin/woocommerce/hook/woocommerce_order_actions
	 *
	 * @param  array            $actions    Actions array
	 * @param  WC_Order|null    $order      The order object or null if no order is available.
	 * @return array                        Final actions array
	 */
	public function action_send_order_to_weberp( $actions, $order )
	{
	  $actions['send_order_to_weberp'] = __( 'Send to WebERP', 'weberp' );
	  return $actions;
	}

	/**
	 * Execute the new_order function. Used by the order actions dropdown
	 *
	 * @param  object $order Order object
	 * @return void
	 */
	public function send_order_to_weberp( $order )
	{
		$existing_order_id = $this->api->getItemId($order->get_id(), 'Orders');

		if (!$existing_order_id) {
	   	$this->new_order($order->get_id());
	  }
	}

	/**
	 * Execute the createAttribute function. Used when a new attribute is created
	 *
	 * @link   https://wp-kama.com/plugin/woocommerce/hook/woocommerce_attribute_added
	 *
	 * @param  int    $id    Attribute ID
	 * @param  array  $data  Attribute data
	 * @return void
	 */
	public function attribute_added($id, $data)
	{
		$this->api->createAttribute($id, $data);
	}

	/**
	 * Execute the updateAttribute function. Used when an existing attribute is updated
	 *
	 * @link   https://wp-kama.com/plugin/woocommerce/hook/woocommerce_attribute_updated
	 *
	 * @param  int       $id          Attribute ID
	 * @param  array     $data        Attribute data array
	 * @param  string    $old_slug    Old attribute slug
	 * @return void
	 */
	public function attribute_updated($id, $data, $old_slug)
	{
		$this->api->updateAttribute($id, $data);
	}

	/**
	 * Execute the deleteAttribute function. Used when an existing attribute is deleted
	 *
	 * @link   https://wp-kama.com/plugin/woocommerce/hook/woocommerce_attribute_deleted
	 *
	 * @param  int       $id          Attribute ID
	 * @param  string    $name        Attribute name
	 * @param  string    $taxonomy    Attribute taxonomy name
	 * @return void
	 */
	public function attribute_deleted($id, $name, $taxonomy)
	{
		$this->api->deleteAttribute($id);
	}

	/**
	 * Execute the createValue function. Used when a new attribute term is created
	 *
	 * @link   https://developer.wordpress.org/reference/hooks/create_term/
	 *
	 * @param  int       $term_id     Term ID
	 * @param  int       $tt_id       Term taxonomy ID
	 * @param  string    $taxonomy    Term taxonomy slug
	 * @param  array     $args        Arguments passed to wp_insert_term()
	 * @return void
	 */
	public function term_created( $term_id, $tt_id, $taxonomy, $args )
	{
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		foreach($attribute_taxonomies as $item) {
			if('pa_' . $item->attribute_name == $taxonomy) {
				$this->api->createValue($item->attribute_id, get_term($term_id));
			}
		}
	}

	/**
	 * Execute the updateValue function. Used when an existing attribute term is updated
	 *
	 * @link   https://developer.wordpress.org/reference/hooks/edited_terms/
	 *
	 * @param  int       $term_id     Term ID
	 * @param  string    $taxonomy    Taxonomy slug
	 * @param  array     $args        Arguments passed to wp_update_term()
	 * @return void
	 */
	public function term_updated( $term_id, $taxonomy, $args )
	{
		$attribute_taxonomies = wc_get_attribute_taxonomies();
		foreach($attribute_taxonomies as $item) {
			if('pa_' . $item->attribute_name == $taxonomy) {
				$this->api->updateValue(get_term($term_id));
			}
		}
	}

	/**
	 * Execute the deleteValue function. Used when an existing attribute term is deleted
	 *
	 * @link   https://developer.wordpress.org/reference/hooks/delete_term/
	 *
	 * @param  int        $term_id         Term ID
	 * @param  int        $tt_id           Term taxonomy ID
	 * @param  string     $slug            Taxonomy slug.
	 * @param  WP_Term    $deleted_term    Copy of the already-deleted term
	 * @param  array      $ids             List of term object IDs
	 * @return void
	 */
	public function term_deleted( $term_id, $tt_id, $slug, $deleted_term, $ids )
	{
		$this->api->deleteValue($term_id);
	}

   /**
   * Find a Media Library attachment by exact filename.
   *
   * @param  WP_REST_Request    $request    The request object
   * @return mixed
   */
  public function weberp_find_media_by_filename(WP_REST_Request $request)
  {
    global $wpdb;

    $filename = $request->get_param('filename');

    if (!$filename) {
      return new WP_Error(
        'missing_filename',
        'Filename is required.',
        ['status' => 400]
      );
    }

    $sql = $wpdb->prepare(
      "
      SELECT p.ID
      FROM {$wpdb->posts} p
      INNER JOIN {$wpdb->postmeta} pm
          ON pm.post_id = p.ID
          AND pm.meta_key = '_wp_attached_file'
      WHERE p.post_type = 'attachment'
        AND LOWER(SUBSTRING_INDEX(pm.meta_value, '/', -1)) = LOWER(%s)
      LIMIT 1
      ",
      $filename
    );

    $attachment_id = $wpdb->get_var($sql);

    if (!$attachment_id) {
      return new WP_Error(
        'media_not_found',
        'No media attachment found with that filename.',
        [
          'status'   => 404,
          'filename' => $filename,
        ]
      );
    }

    $attachment_id = (int) $attachment_id;

    return rest_ensure_response([
      'id'         => $attachment_id,
      'filename'   => basename(get_attached_file($attachment_id)),
      'source_url' => wp_get_attachment_url($attachment_id),
      'mime_type'  => get_post_mime_type($attachment_id),
    ]);
	}

}
