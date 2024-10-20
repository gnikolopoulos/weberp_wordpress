<?php

/**
 * Define functions for communicating with the WebERP API
 *
 *
 * @link       https://www.interactive-design.gr
 * @since      1.0.0
 *
 * @package    Weberp
 * @subpackage Weberp/includes
 */

/**
 * Define functions for communicating with the WebERP API
 *
 *
 * @since      1.0.0
 * @package    Weberp
 * @subpackage Weberp/includes
 * @author     George Nikolopoulos <georgen@interactive-design.gr>
 */
class Weberp_Api
{

	private $base_url;

	private $order_status = [
		'completed' => 'complete',
		'shipped' => 'complete',
		'pending' => 'processing',
		'processing' => 'processing',
		'on-hold' => 'processing',
		'cancelled' => 'cancelled',
		'failed' => 'cancelled',
		'refunded' => 'cancelled',
	];

	/**
	 * Initialize the collections used to maintain the actions and filters.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		$this->base_url = get_option('api_url');
	}

	/**
	 * Login to the API
	 *
	 * @link  	 https://developer.wordpress.org/apis/handbook/making-http-requests/getting-data-from-an-external-service/
	 * @since    1.0.0
	 */
	private function login()
	{
		$params = array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( get_option('api_username') . ':' . get_option('api_password') )
			)
		);
		$response = wp_remote_post( $this->base_url . '/users/authenticate', $params );

		if (wp_remote_retrieve_response_code($response) == 200) {
			$login_data = json_decode(wp_remote_retrieve_body($response));
			set_transient('weberp_login_token', $login_data->data->token, $login_data->data->expires - time());
			return $login_data->data->token;
		} else {
			return false;
		}
	}

	/**
	 * Gor a given entity ID in wordpress, get the WebERP match
	 *
	 * @param  int    $wp_id    Eintity ID in WP
	 * @param  string $model    Entity model type
	 * @return mixed            Item ID in WebERP or false on error
	 */
	public function getItemId($wp_id, $model)
	{
		$token = get_transient('weberp_login_token');
		if ($token === false) {
			$token = $this->login();
		}

		if ($token === false) {
			return false;
		}

		$params = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/json'
			)
		);
		$response = wp_remote_get( $this->base_url . '/ecommercedata/' . $model . '/' . $wp_id, $params );

		if (wp_remote_retrieve_response_code($response) == 200) {
			$data = json_decode(wp_remote_retrieve_body($response));
			if (get_option('enable_logging')) {
				error_log(print_r($data, true));
			}

			return $data;
		} else {
			return false;
		}
	}

	/**
	 * Update category in WebERP
	 *
	 * @param  int    $category_id    Wordpress category id
	 * @return mixed
	 */
	public function updateCategory($category_id)
	{
		$token = get_transient('weberp_login_token');
		if ($token === false) {
			$token = $this->login();
		}

		if ($token === false) {
			return false;
		}

		$category_data = get_term_by('id', $category_id, 'product_cat');

		$erp_id = $this->getItemId($category_id, 'ProductCategories');
		if (!$erp_id) {
			return;
		}

		if ($category_data->parent != 0) {
			$parent_id = $this->getItemId($category_data->parent, 'ProductCategories');
			if (!$parent_id) {
				return;
			}
		}

		$params = array(
			'method'  => 'PUT',
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/json'
			),
			'body' => json_encode(array(
				"category" => array(
					"name" => $category_data->name,
					"parent_id" => $category_data->parent != 0 ? $parent_id->data->id : 1 // skipcq: PHP-W1066
				)
			))
		);

		$response = wp_remote_request( $this->base_url . '/product-categories/' . $erp_id->data->id, $params );

		if (wp_remote_retrieve_response_code($response) == 200) {
			$data = json_decode(wp_remote_retrieve_body($response));
			if (get_option('enable_logging')) {
				error_log(print_r($data, true));
			}

			return $data;
		} else {
			return false;
		}
	}

	/**
	 * Create a new category in WebERP
	 *
	 * @param  int    $category_id    Wordpress categoryID
	 * @return mixed
	 */
	public function createCategory($category_id)
	{
		$token = get_transient('weberp_login_token');
		if ($token === false) {
			$token = $this->login();
		}

		if ($token === false) {
			return false;
		}

		$category_data = get_term_by('id', $category_id, 'product_cat');

		if ($category_data->parent != 0) {
			$parent_id = $this->getItemId($category_data->parent, 'ProductCategories');
			if (!$parent_id) {
				return;
			}
		}

		$params = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/json'
			),
			'body' => json_encode(array(
				"category" => array(
					"id" => $category_id,
					"name" => $category_data->name,
					"parent_id" => $category_data->parent != 0 ? $parent_id->data->id : 1, // skipcq: PHP-W1066
					"active" => true
				)
			))
		);

		$response = wp_remote_post( $this->base_url . '/product-categories', $params );

		if (wp_remote_retrieve_response_code($response) == 200) {
			$data = json_decode(wp_remote_retrieve_body($response));
			if (get_option('enable_logging')) {
				error_log(print_r($data, true));
			}

			return $data;
		} else {
			return false;
		}
	}

	/**
	 * Create a new order in WebERP
	 *
	 * @param  int    $order_id    WooCommerce order ID
	 * @return mixed
	 */
	public function createOrder($order_id)
	{
		$token = get_transient('weberp_login_token');
		if ($token === false) {
			$token = $this->login();
		}

		if ($token === false) {
			return false;
		}

		$order = wc_get_order($order_id);

		$order_items = array();
		foreach ($order->get_items() as $item) {
			$erp_product_id = $this->getItemId($item->get_product_id(), 'Products');
			if (!$erp_product_id) {
				return;
			}

			if ($item->get_variation_id() != 0) {
				$erp_variation_id = $this->getItemId($item->get_variation_id(), 'Variations');
				if (!$erp_variation_id) {
					return;
				}
			}

			$order_items[] = array(
				"product_id" => $erp_product_id->data->id,
				"variation_id" => $item->get_variation_id() != 0 ? $erp_variation_id->data->id : null,
				"quantity" => $item->get_quantity(),
				"final_price" => ($item->get_total() + $item->get_total_tax()) / $item->get_quantity(),
			);
		}

		$order_fees = array();
		foreach ($order->get_fees() as $fee) {
			$order_fees[] = array(
				'name' => $fee->get_name(),
				'price' => $fee->get_total() + $fee->get_total_tax(),
			);
		}
		
		foreach ($order->get_shipping_methods() as $sm) {
			$order_fees[] = array(
				'name' => $sm->get_name(),
				'price' => $sm->get_total() + $sm->get_total_tax(),
			);
		}

		$countries = new WC_Countries();
		$country_states = $countries->get_states($order->get_billing_country());
		$state_name     = $country_states[$order->get_billing_state()];

		$params = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/json'
			),
			'body' => json_encode(array(
				"order" => array(
					"code" => $order->get_id(),
					"id" => $order_id,
					"customer" => array(
						"first_name" => $order->get_billing_first_name(),
						"last_name" => $order->get_billing_last_name(),
						"customer_group_id" => 1,
						"phone" => '',
						"mobile" => $order->get_billing_phone(),
						"email" => $order->get_billing_email(),
						"address" => mb_substr($order->get_billing_address_1(), 0, 44),
						"city" => $order->get_billing_city(),
						"state" => $state_name ?: null,
						"zip" => $order->get_billing_postcode(),
						"country_code" => $order->get_billing_country()
					),
					"total" => $order->get_total(),
					"shipping_method" => $order->get_shipping_method(),
					"payment_method" => $order->get_payment_method_title(),
					"status" => $this->order_status[$order->get_status()],
					"products" => $order_items,
					"fees" => $order_fees,
				)
			)
		));

		$response = wp_remote_post( $this->base_url . '/orders', $params );

		if (wp_remote_retrieve_response_code($response) == 200) {
			$data = json_decode(wp_remote_retrieve_body($response));
			if (get_option('enable_logging')) {
				error_log(print_r($data, true));
			}

			return $data;
		} else {
			return false;
		}
	}

	/**
	 * Mark an order as Complete in WebERP
	 *
	 * @param  int    $order_id    WooCommerce order id
	 * @return mixed
	 */
	public function completeOrder($order_id)
	{
		$token = get_transient('weberp_login_token');
		if ($token === false) {
			$token = $this->login();
		}

		if ($token === false) {
			return false;
		}

		$erp_order_id = $this->getItemId($order_id, 'Orders');
		if (!$erp_order_id) {
			return;
		}

		$params = array(
			'method' => 'PUT',
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/json'
			),
			'body' => json_encode(array(
				"order" => array(
					"status" => 'complete',
				)
			)
		));

		$response = wp_remote_request( $this->base_url . '/orders/' . $erp_order_id->data->id, $params );

		if (wp_remote_retrieve_response_code($response) == 200) {
			$data = json_decode(wp_remote_retrieve_body($response));
			if (get_option('enable_logging')) {
				error_log(print_r($data, true));
			}

			return $data;
		} else {
			return false;
		}
	}

	/**
	 * Delete an order from WebERP
	 *
	 * @param  int    $order_id    WooCommerce order id
	 * @return mixed
	 */
	public function cancelOrder($order_id)
	{
		$token = get_transient('weberp_login_token');
		if ($token === false) {
			$token = $this->login();
		}

		if ($token === false) {
			return false;
		}

		$erp_order_id = $this->getItemId($order_id, 'Orders');
		if (!$erp_order_id) {
			return;
		}

		$params = array(
			'method' => 'DELETE',
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/json'
			),
		);

		$response = wp_remote_request( $this->base_url . '/orders/' . $erp_order_id->data->id, $params );

		if (wp_remote_retrieve_response_code($response) == 200) {
			$data = json_decode(wp_remote_retrieve_body($response));
			if (get_option('enable_logging')) {
				error_log(print_r($data, true));
			}

			return $data;
		} else {
			return false;
		}
	}

	/**
	 * Get product data using WebERP API
	 *
	 * @param  int    $product_id    WooCommerce product id
	 * @return mixed
	 */
	public function getProduct($product_id)
	{
		$token = get_transient('weberp_login_token');
		if ($token === false) {
			$token = $this->login();
		}

		if ($token === false) {
			return false;
		}

		$erp_id = $this->getItemId($product_id, 'Products');
		if (!$erp_id) {
			return;
		}

		$params = array(
			'method' => 'GET',
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/json'
			),
		);

		$response = wp_remote_request( $this->base_url . '/products/' . $erp_id->data->id . '?include=stock', $params );

		if (wp_remote_retrieve_response_code($response) == 200) {
			$data = json_decode(wp_remote_retrieve_body($response));
			if (get_option('enable_logging')) {
				error_log(print_r($data, true));
			}

			return $data;
		} else {
			return false;
		}
	}

	/**
	 * Update a product in WebERP
	 *
	 * @param  int    $product_id    WooCommerce product id
	 * @return mixed
	 */
	public function updateProduct($product_id)
	{
		$token = get_transient('weberp_login_token');
		if ($token === false) {
			$token = $this->login();
		}

		if ($token === false) {
			return false;
		}

		$erp_id = $this->getItemId($product_id, 'Products');
		if (!$erp_id) {
			return;
		}

		$product = wc_get_product($product_id);

		$product_attributes = array();
		foreach ($product->get_attributes() as $attribute) {
			if (!$attribute->get_variation()) {
				foreach ($attribute->get_options() as $option) {
					$att = $this->getItemId($option, 'AttributeValues');
					if ($att) {
						$product_attributes[substr($attribute->get_name(), 3)] = $att->data->id;
					}
				}
			}
		}

		$product_categories = array();
		foreach ($product->get_category_ids() as $category) {
			$cat = $this->getItemId($category, 'ProductCategories');
			if ($cat) {
				$product_categories[] = $cat->data->id;
			}
		}

		$product_images = array();
		$counter = 1;

		$featured_image_id = get_post_thumbnail_id($product->get_id());
		if($featured_image_id) {
			$temp = array();
			$temp['path'] = wp_get_attachment_url($featured_image_id);
			$temp['label'] = $counter;
			$temp['sort_order'] = $counter;
			$counter++;

			$product_images[] = $temp;
		}

		foreach ($product->get_gallery_image_ids() as $image) {
			$temp = array();
			$temp['path'] = wp_get_attachment_url($image);
			$temp['label'] = $counter;
			$temp['sort_order'] = $counter;
			$counter++;

			$product_images[] = $temp;
		}

		$params = array(
			'method' => 'PUT',
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/json'
			),
			'body' => array(
				"product" => array(
					"id" => $product->get_id(),
					"name" => $product->get_name(),
					"sku" => $product->get_sku(),
					"product_barcode" => $product->get_type() != 'variable' ? $product->get_meta('_barcode') : null,
					"price" => $product->get_regular_price() ?: $product->get_price(),
					"discount_percent" => 0,
					"discount_amount" => 0,
					"final_price" => $product->get_price(),
					"buy_price" => 0,
					"active" => $product->get_status() === 'publish' ? true : false,
					"description" => $product->get_short_description(),
					"categories" => $product_categories,
					"attributes" => $product_attributes,
					"images" => $product_images,
				)
			)
		);

		if ($product->get_type() === 'variable') {
			$product_variations = array();
			foreach ($product->get_children() as $variation_id) {
				$variation = wc_get_product($variation_id);
				$size = false;
				foreach ($variation->get_attributes() as $key => $attribute) {
					$terms = get_terms($key);
					foreach ($terms as $term) {
						if ($term->name == $attribute) {
							$value = $this->getItemId($term->term_id, 'AttributeValues');
							if ($value) {
								$size = $value->data->id;
							}
						}
					}
				}

				if ($size) {
					$product_variations[] = array(
						'barcode' => $variation->get_sku(),
						'size' => $size,
					);
				}
			}

			$params['body']['product']['variations'] = $product_variations;
		}

		$params['body'] = json_encode($params['body']);

		$response = wp_remote_request( $this->base_url . '/products/' . $erp_id->data->id, $params );

		if (wp_remote_retrieve_response_code($response) == 200) {
			$data = json_decode(wp_remote_retrieve_body($response));
			if (get_option('enable_logging')) {
				error_log(print_r($data, true));
			}

			return $data;
		} else {
			return false;
		}
	}

	/**
	 * Create a new attribute in WebERP
	 *
	 * @param  int    $id    WooCommerce attribute id
	 * @param  array  $data  WooCommerce attribute data array
	 * @return mixed
	 */
	public function createAttribute($id, $data)
	{
		$token = get_transient('weberp_login_token');
		if ($token === false) {
			$token = $this->login();
		}

		if ($token === false) {
			return false;
		}

		$params = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/json'
			),
			'body' => json_encode(array(
				"label" => array(
					"id" => $id,
					"name" => $data['attribute_label'],
					"type" => 'dropdown',
					"attribute_code" => $data['attribute_name']
				)
			))
		);

		$response = wp_remote_post( $this->base_url . '/labels', $params );

		if (wp_remote_retrieve_response_code($response) == 200) {
			$data = json_decode(wp_remote_retrieve_body($response));
			if (get_option('enable_logging')) {
				error_log(print_r($data, true));
			}

			return $data;
		} else {
			return false;
		}
	}

	/**
	 * Update an existing attribute in WebERP
	 *
	 * @param  int    $id    WooCommerce attribute id
	 * @param  array  $data  WooCommerce attribute data array
	 * @return mixed
	 */
	public function updateAttribute($id, $data)
	{
		$token = get_transient('weberp_login_token');
		if ($token === false) {
			$token = $this->login();
		}

		if ($token === false) {
			return false;
		}

		$erp_id = $this->getItemId($id, 'AttributeLabels');
		if (!$erp_id) {
			return;
		}

		$params = array(
			'method' => 'PUT',
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/json'
			),
			'body' => json_encode(array(
				"label" => array(
					"name" => $data['attribute_label'],
				)
			))
		);

		$response = wp_remote_request( $this->base_url . '/labels/' . $erp_id->data->id, $params );

		if (wp_remote_retrieve_response_code($response) == 200) {
			$data = json_decode(wp_remote_retrieve_body($response));
			if (get_option('enable_logging')) {
				error_log(print_r($data, true));
			}

			return $data;
		} else {
			return false;
		}
	}

	/**
	 * Delete an existing attribute in WebERP
	 *
	 * @param  int    $id    WooCommerce attribute id
	 * @return mixed
	 */
	public function deleteAttribute($id)
	{
		$token = get_transient('weberp_login_token');
		if ($token === false) {
			$token = $this->login();
		}

		if ($token === false) {
			return false;
		}

		$erp_id = $this->getItemId($id, 'AttributeLabels');
		if (!$erp_id) {
			return;
		}

		$params = array(
			'method' => 'DELETE',
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/json'
			),
		);

		$response = wp_remote_request( $this->base_url . '/labels/' . $erp_id->data->id, $params );

		if (wp_remote_retrieve_response_code($response) == 200) {
			$data = json_decode(wp_remote_retrieve_body($response));
			if (get_option('enable_logging')) {
				error_log(print_r($data, true));
			}

			return $data;
		} else {
			return false;
		}
	}

	/**
	 * Create a new attribute value in WebERP
	 *
	 * @param  int    $attribute_id    WooCommerce attribute id
	 * @param  array  $term            WooCommerce term object
	 * @return mixed
	 */
	public function createValue($attribute_id, $term)
	{
		$token = get_transient('weberp_login_token');
		if ($token === false) {
			$token = $this->login();
		}

		if ($token === false) {
			return false;
		}

		$erp_id = $this->getItemId($attribute_id, 'AttributeLabels');
		if (!$erp_id) {
			return;
		}

		$params = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/json'
			),
			'body' => json_encode(array(
				"value" => array(
					"id" => $term->term_id,
					"attribute_value_name" => $term->name,
					"attribute_label_id" => $erp_id->data->id
				)
			))
		);
		$response = wp_remote_post( $this->base_url . '/values', $params );

		if (wp_remote_retrieve_response_code($response) == 200) {
			$data = json_decode(wp_remote_retrieve_body($response));
			if (get_option('enable_logging')) {
				error_log(print_r($data, true));
			}

			return $data;
		} else {
			return false;
		}
	}

	/**
	 * Update an existing attribute value in WebERP
	 *
	 * @param  array  $term            WooCommerce term object
	 * @return mixed
	 */
	public function updateValue($term)
	{
		$token = get_transient('weberp_login_token');
		if ($token === false) {
			$token = $this->login();
		}

		if ($token === false) {
			return false;
		}

		$erp_id = $this->getItemId($term->term_id, 'AttributeValues');
		if (!$erp_id) {
			return;
		}

		$params = array(
			'method' => 'PUT',
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/json'
			),
			'body' => json_encode(array(
				"value" => array(
					"attribute_value_name" => $term->name,
				)
			))
		);
		$response = wp_remote_request( $this->base_url . '/values/' . $erp_id->data->id, $params );

		if (wp_remote_retrieve_response_code($response) == 200) {
			$data = json_decode(wp_remote_retrieve_body($response));
			if (get_option('enable_logging')) {
				error_log(print_r($data, true));
			}

			return $data;
		} else {
			return false;
		}
	}

	/**
	 * Delete an existing attribute value in WebERP
	 *
	 * @param  int    $term_id    WooCommerce term id
	 * @return mixed
	 */
	public function deleteValue($term_id)
	{
		$token = get_transient('weberp_login_token');
		if ($token === false) {
			$token = $this->login();
		}

		if ($token === false) {
			return false;
		}

		$erp_id = $this->getItemId($term_id, 'AttributeValues');
		if (!$erp_id) {
			return;
		}

		$params = array(
			'method' => 'DELETE',
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type' => 'application/json'
			),
		);
		$response = wp_remote_request( $this->base_url . '/values/' . $erp_id->data->id, $params );

		if (wp_remote_retrieve_response_code($response) == 200) {
			$data = json_decode(wp_remote_retrieve_body($response));
			if (get_option('enable_logging')) {
				error_log(print_r($data, true));
			}

			return $data;
		} else {
			return false;
		}
	}

}