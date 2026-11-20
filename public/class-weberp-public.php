<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://interactive-design.gr
 * @since      1.0.0
 *
 * @package    Weberp
 * @subpackage Weberp/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Weberp
 * @subpackage Weberp/public
 * @author     George Nikolopoulos <georgen@interactive-design.gr>
 */
class Weberp_Public
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
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version )
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/weberp-public.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        wp_register_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/weberp-public.js', array( 'jquery' ), $this->version, false );
        wp_localize_script( $this->plugin_name, 'weberp', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));

        wp_enqueue_script( $this->plugin_name );
    }

    /**
     * Add the container div and template used to render the table.
     *
     * @since    1.1.0
     */
    public function add_stock_container()
    {
        echo '<div class="stock_container"><table id="stock_table"></table></div>
            <template id="stock_table_row">
                <tr>
                    <td class="warehouse"></td>
                    <td class="qty"></td>
                </tr>
            </template>';
    }

    /**
     * Add the stock analysis table.
     *
     * @since    1.2.0
     */
    public function add_stock_table()
    {
        global $product;

        $weberp_api = new Weberp_Api();
        $erp_product_data = $weberp_api->getProduct($product->get_id());
        if (!$erp_product_data) {
            echo '';
        }

        echo '<div class="stock_container">
        <table id="stock_table">
            <thead>
                <tr>
                    <th>Κατάστημα</th>
                    <th>Ποσότητα</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($erp_product_data->product->stock as $item) {
            echo '<tr>
                    <td class="warehouse">'.esc_html($item->warehouse).'</td>
                    <td class="qty">'.esc_html($item->stock).'</td>
                </tr>';
        }
        echo '</tbody></table></div>';
    }

    /**
     * Add the clickable link next to the availability text.
     *
     * @since    1.1.0
     */
    public function add_stock_link( $availability_text, $product )
    {
        if(!$product->is_in_stock()) {
            return $availability_text;
        }

        $nonce = wp_create_nonce('live_stock_nonce');
        return $availability_text . "<span class=\"pull-right\">
                <a data-nonce=\"{$nonce}\" data-product-id=\"{$product->id}\" class=\"check_live_stock\" href=\"#\">".__('Διαθεσιμότητα καταστημάτων', 'weberp')."</a>
            </span>";
    }

    /**
     * Request the current product info from WebERP and return the stock entries.
     *
     * @since    1.1.0
     */
    public function get_live_stock()
    {
        if (!isset($_REQUEST['nonce'])) {
            exit("Nonce missing");
        }

        if (!wp_verify_nonce( sanitize_text_field(wp_unslash($_REQUEST['nonce'])), 'live_stock_nonce')) {
            exit("No naughty business please");
        }

        if (!isset($_REQUEST['product_id'])) {
            exit("Missing product id information");
        }

        $weberp_api = new Weberp_Api();
        $erp_product_data = $weberp_api->getProduct(sanitize_text_field(wp_unslash($_REQUEST['product_id'])));
        if (!$erp_product_data) {
            echo false;
        }

        echo json_encode($erp_product_data->product->stock);

        die();
    }

}
